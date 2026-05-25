<?php
require_once '../config/database.php';
checkRole(['user']);

$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);

// Get request details
$stmt = $conn->prepare("
    SELECT r.*, d.document_name, d.requirements, d.fee, d.processing_days,
           u.full_name as processed_by_name
    FROM requests r 
    JOIN documents d ON r.document_id = d.id 
    LEFT JOIN users u ON r.processed_by = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param("ii", $request_id, $user_id);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🏢 Barangay System</h2>
            </div>
            
            <div class="sidebar-user">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role"><?= $_SESSION['role'] ?></div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <span class="icon">📊</span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="new_request.php">
                        <span class="icon">📝</span> New Request
                    </a>
                </li>
                <li>
                    <a href="/barangay_system/logout.php">
                        <span class="icon">🚪</span> Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Request #<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                <p>View your request details below.</p>
            </div>
            
            <div class="card mb-20">
                <div class="card-header">
                    <h3>📄 Request Information</h3>
                    <span class="badge badge-<?= getStatusBadge($request['status']) ?>">
                        <?= ucfirst($request['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <table style="border: none;">
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600; width: 200px;">Document Type:</td>
                            <td style="border: none; padding: 10px 0;"><?= htmlspecialchars($request['document_name']) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Purpose:</td>
                            <td style="border: none; padding: 10px 0;"><?= htmlspecialchars($request['purpose']) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Schedule:</td>
                            <td style="border: none; padding: 10px 0;">
                                <?= formatDate($request['schedule_date']) ?> at <?= formatTime($request['schedule_time']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Processing Fee:</td>
                            <td style="border: none; padding: 10px 0;">₱<?= number_format($request['fee'], 2) ?></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Date Submitted:</td>
                            <td style="border: none; padding: 10px 0;"><?= formatDate($request['created_at']) ?></td>
                        </tr>
                        <?php if ($request['processed_by_name']): ?>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Processed By:</td>
                            <td style="border: none; padding: 10px 0;"><?= htmlspecialchars($request['processed_by_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($request['remarks']): ?>
                        <tr>
                            <td style="border: none; padding: 10px 20px 10px 0; font-weight: 600;">Remarks:</td>
                            <td style="border: none; padding: 10px 0;"><?= htmlspecialchars($request['remarks']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <?php if ($request['requirements']): ?>
            <div class="card mb-20">
                <div class="card-header">
                    <h3>📋 Requirements</h3>
                </div>
                <div class="card-body">
                    <ul style="margin-left: 20px;">
                        <?php foreach (explode(',', $request['requirements']) as $req): ?>
                            <li style="margin-bottom: 8px;"><?= htmlspecialchars(trim($req)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($files)): ?>
            <div class="card mb-20">
                <div class="card-header">
                    <h3>📎 Uploaded Files</h3>
                </div>
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
            
            <?php if ($request['status'] === 'approved'): ?>
            <div class="alert alert-success">
                <strong>✅ Your request has been approved!</strong><br>
                Please visit the barangay hall on your scheduled date to claim your document. Don't forget to bring:
                <ul style="margin: 10px 0 0 20px;">
                    <li>Valid ID</li>
                    <li>Processing fee of ₱<?= number_format($request['fee'], 2) ?></li>
                    <li>Original copies of submitted requirements</li>
                </ul>
            </div>
            <?php elseif ($request['status'] === 'rejected'): ?>
            <div class="alert alert-danger">
                <strong>❌ Your request has been rejected.</strong><br>
                <?php if ($request['remarks']): ?>
                    Reason: <?= htmlspecialchars($request['remarks']) ?>
                <?php else: ?>
                    Please contact the barangay office for more information.
                <?php endif; ?>
            </div>
            <?php elseif ($request['status'] === 'pending'): ?>
            <div class="alert alert-warning">
                <strong>⏳ Your request is pending review.</strong><br>
                Please wait for the staff to process your request. You will see the status update here.
            </div>
            <?php endif; ?>
            
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </main>
    </div>
    
    <script src="/barangay_system/js/main.js"></script>
</body>
</html>
