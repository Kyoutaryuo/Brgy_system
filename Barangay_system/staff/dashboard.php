<?php
session_start();
require_once '../config/database.php';
checkRole(['staff', 'admin']);

// 🔥 SESSION SAFETY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// =====================
// GET STATISTICS
// =====================
$stats = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status");

while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

$total_requests = array_sum($stats);

// =====================
// GET ALL REQUESTS
// =====================
$pending_requests = $conn->query("
    SELECT r.*, 
           d.document_name,
           u.full_name as requester_name,
           u.contact_number
    FROM requests r
    LEFT JOIN documents d ON r.document_id = d.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.status IN ('pending', 'processing', 'approved', 'claimed', 'rejected')
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="sidebar-header">
            <h2>🏢 Barangay System</h2>
        </div>

        <div class="sidebar-user">
            <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
            <div class="role"><?= ucfirst($_SESSION['role']) ?></div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="/barangay_system/admin/dashboard.php">⚙️ Admin Panel</a></li>
            <?php endif; ?>

            <li><a href="/barangay_system/logout.php">🚪 Logout</a></li>
        </ul>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="page-header">
            <h1>Staff Dashboard</h1>
            <p>Process and manage document requests.</p>
        </div>

        <!-- STATS -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon blue">📄</div>
                <div>
                    <h3><?= $total_requests ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div>
                    <h3><?= ($stats['pending'] ?? 0) + ($stats['processing'] ?? 0) ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div>
                    <h3><?= $stats['approved'] ?? 0 ?></h3>
                    <p>Approved</p>
                </div>
            </div>
<div class="stat-card">
    <div class="stat-icon blue">📥</div>
    <div>
        <h3><?= $stats['claimed'] ?? 0 ?></h3>
        <p>Claimed</p>
    </div>
</div>
            <div class="stat-card">
                <div class="stat-icon red">❌</div>
                <div>
                    <h3><?= $stats['rejected'] ?? 0 ?></h3>
                    <p>Rejected</p>
                </div>
            </div>

        </div>

        <!-- REQUEST TABLE -->
        <div class="card">

            <div class="card-header">
                <h3>📋 All Request History</h3>
            </div>

            <div class="card-body">

                <?php if (empty($pending_requests)): ?>

                    <div class="empty-state">
                        <div class="icon">✅</div>
                        <h3>All Caught Up!</h3>
                        <p>No pending requests at the moment.</p>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table>

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Requester</th>
                                    <th>Document</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($pending_requests as $request): ?>

                                    <tr>

                                        <td>
                                            #<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?>

                                            <?php if (!empty($request['contact_number'])): ?>
                                                <br>
                                                <small><?= htmlspecialchars($request['contact_number']) ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($request['document_name'] ?? 'Deleted Document') ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-<?= getStatusBadge($request['status']) ?>">
                                                <?= ucfirst($request['status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= $request['created_at'] ? formatDate($request['created_at']) : 'N/A' ?>
                                        </td>

                                        <td>

                                            <!-- 🔥 SAFE REVIEW LINK -->
                                            <?php if (!empty($request['id'])): ?>
                                                <a href="process_request.php?id=<?= intval($request['id']) ?>"
                                                   class="btn btn-sm btn-primary">
                                                    Review
                                                </a>
                                            <?php else: ?>
                                                <span style="color:red;">Invalid</span>
                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>

<script src="/barangay_system/js/main.js"></script>

</body>
</html>