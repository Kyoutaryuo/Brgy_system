<?php
require_once '../config/database.php';
checkRole(['staff', 'admin']);

$staff_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Determine if this is a demo account (id <= 0 means not a real DB user).
// Demo accounts cannot be stored as processed_by due to the FK constraint.
$is_demo_account = ($staff_id <= 0);

// Get request details
$stmt = $conn->prepare("
    SELECT r.*, d.document_name, d.requirements, d.fee, d.processing_days,
           u.full_name as requester_name, u.email, u.contact_number, u.address
    FROM requests r
    JOIN documents d ON r.document_id = d.id
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    header("Location: dashboard.php");
    exit();
}

// Get uploaded files
$stmt = $conn->prepare("SELECT * FROM request_files WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $remarks = sanitize($conn, $_POST['remarks'] ?? '');

    // For real (non-demo) accounts, verify the user still exists in the DB.
    if (!$is_demo_account) {
        $user_check = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $user_check->bind_param("i", $staff_id);
        $user_check->execute();
        $user_check->store_result();
        $still_exists = $user_check->num_rows > 0;
        $user_check->close();

        if (!$still_exists) {
            session_unset();
            session_destroy();
            header("Location: /barangay_system/index.php?error=account_deleted");
            exit();
        }
    }

    // processed_by must be NULL for demo accounts (id <= 0 has no FK row).
    // For real accounts use the actual staff_id.
    $processed_by = $is_demo_account ? null : $staff_id;

    if ($action === 'approve') {
        // Use NULL for demo accounts; bind as integer otherwise
        if ($is_demo_account) {
            $stmt = $conn->prepare("UPDATE requests SET status = 'approved', remarks = ?, processed_by = NULL, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $remarks, $request_id);
        } else {
            $stmt = $conn->prepare("UPDATE requests SET status = 'approved', remarks = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $remarks, $staff_id, $request_id);
        }

        if ($stmt->execute()) {
            logActivity($conn, $staff_id, 'Approve Request', "Approved request #$request_id");
            $success = 'Request has been approved successfully!';
            $request['status'] = 'approved';
        } else {
            $error = 'Failed to approve request. Error: ' . $conn->error;
        }
        $stmt->close();

    } elseif ($action === 'reject') {
        if (empty($remarks)) {
            $error = 'Please provide a reason for rejection.';
        } else {
            if ($is_demo_account) {
                $stmt = $conn->prepare("UPDATE requests SET status = 'rejected', remarks = ?, processed_by = NULL, processed_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $remarks, $request_id);
            } else {
                $stmt = $conn->prepare("UPDATE requests SET status = 'rejected', remarks = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
                $stmt->bind_param("sii", $remarks, $staff_id, $request_id);
            }

            if ($stmt->execute()) {
                logActivity($conn, $staff_id, 'Reject Request', "Rejected request #$request_id: $remarks");
                $success = 'Request has been rejected.';
                $request['status'] = 'rejected';
            } else {
                $error = 'Failed to reject request. Error: ' . $conn->error;
            }
            $stmt->close();
        }

    } elseif ($action === 'claimed') {
        if ($is_demo_account) {
            $stmt = $conn->prepare("UPDATE requests SET status = 'claimed', processed_by = NULL, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $request_id);
        } else {
            $stmt = $conn->prepare("UPDATE requests SET status = 'claimed', processed_by = ?, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("ii", $staff_id, $request_id);
        }

        if ($stmt->execute()) {
            logActivity($conn, $staff_id, 'Mark Claimed', "Marked request #$request_id as claimed");
            $success = 'Request has been marked as claimed!';
            $request['status'] = 'claimed';
        } else {
            $error = 'Failed to mark as claimed. Error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Request - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🏢 Barangay System</h2>
            </div>
            <div class="sidebar-user">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role"><?= ucfirst($_SESSION['role']) ?></div>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <span class="icon">📊</span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/barangay_system/logout.php">
                        <span class="icon">🚪</span> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Process Request #<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                <p>Review and process this document request.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <div class="card-header"><h3>👤 Requester Information</h3></div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($request['requester_name']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($request['contact_number'] ?: 'N/A') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email'] ?: 'N/A') ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($request['address'] ?: 'N/A') ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>📄 Request Details</h3>
                        <span class="badge badge-<?= getStatusBadge($request['status']) ?>">
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>Document:</strong> <?= htmlspecialchars($request['document_name']) ?></p>
                        <p><strong>Purpose:</strong> <?= htmlspecialchars($request['purpose']) ?></p>
                        <p><strong>Schedule:</strong> <?= formatDate($request['schedule_date']) ?> at <?= formatTime($request['schedule_time']) ?></p>
                        <p><strong>Fee:</strong> ₱<?= number_format($request['fee'], 2) ?></p>
                        <p><strong>Submitted:</strong> <?= formatDate($request['created_at']) ?></p>
                    </div>
                </div>
            </div>

            <?php if ($request['requirements']): ?>
            <div class="card mt-20">
                <div class="card-header"><h3>📋 Requirements Checklist</h3></div>
                <div class="card-body">
                    <ul style="margin-left: 20px;">
                        <?php foreach (explode(',', $request['requirements']) as $req): ?>
                            <li style="margin-bottom: 8px;">
                                <label><input type="checkbox"> <?= htmlspecialchars(trim($req)) ?></label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($files)): ?>
            <div class="card mt-20">
                <div class="card-header"><h3>📎 Submitted Documents</h3></div>
                <div class="card-body">
                    <?php foreach ($files as $file): ?>
                        <div class="file-item">
                            <span>📄 <?= htmlspecialchars($file['file_name']) ?></span>
                            <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="btn btn-sm btn-secondary">View</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($request['status'], ['pending', 'processing'])): ?>
            <div class="card mt-20">
                <div class="card-header"><h3>⚡ Take Action</h3></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="remarks">Remarks / Notes (Required for rejection)</label>
                            <textarea id="remarks" name="remarks" class="form-control"
                                      placeholder="Enter any remarks or reason for rejection..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-10">
                            <button type="submit" name="action" value="approve" class="btn btn-success"
                                    onclick="return confirm('Are you sure you want to APPROVE this request?')">
                                ✅ Approve Request
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to REJECT this request?')">
                                ❌ Reject Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($request['status'] === 'approved'): ?>
            <div class="card mt-20">
                <div class="card-body">
                    <form method="POST" action="">
                        <button type="submit" name="action" value="claimed" class="btn btn-primary"
                                onclick="return confirm('Mark this document as CLAIMED by the requester?')">
                            📥 Mark as Claimed
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-20">
                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </main>
    </div>

    <script src="/barangay_system/js/main.js"></script>
</body>
</html>
