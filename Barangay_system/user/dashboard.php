<?php
session_start();
require_once '../config/database.php';
checkRole(['user']);

$user_id = $_SESSION['user_id'];

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM requests WHERE user_id = ? GROUP BY status");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}
$stmt->close();

// Get recent requests
$stmt = $conn->prepare("
    SELECT r.*, d.document_name 
    FROM requests r 
    JOIN documents d ON r.document_id = d.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barangay System</title>
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
                    <a href="dashboard.php" class="active">
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
                <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h1>
                <p>Here's an overview of your document requests.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">📄</div>
                    <div class="stat-info">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon yellow">⏳</div>
                    <div class="stat-info">
                        <h3><?= $stats['pending'] + ($stats['processing'] ?? 0) ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <h3><?= $stats['approved'] ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">❌</div>
                    <div class="stat-info">
                        <h3><?= $stats['rejected'] ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-20">
                <div class="card-body">
                    <a href="new_request.php" class="btn btn-primary">
                        📝 Submit New Request
                    </a>
                </div>
            </div>
            
            <!-- Recent Requests -->
            <div class="card">
                <div class="card-header">
                    <h3>📋 Your Recent Requests</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_requests)): ?>
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <h3>No Requests Yet</h3>
                            <p>You haven't submitted any document requests.</p>
                            <a href="new_request.php" class="btn btn-primary mt-20">Submit Your First Request</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Document</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Date Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_requests as $request): ?>
                                        <tr>
                                            <td>#<?= str_pad($request['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($request['document_name']) ?></td>
                                            <td>
                                                <?= formatDate($request['schedule_date']) ?><br>
                                                <small><?= formatTime($request['schedule_time']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getStatusBadge($request['status']) ?>">
                                                    <?= ucfirst($request['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($request['created_at']) ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-secondary">
                                                    View
                                                </a>
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
