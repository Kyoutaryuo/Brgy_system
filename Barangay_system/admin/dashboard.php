<?php
session_start();
require_once '../config/database.php';
checkRole(['admin']);

// Get statistics
$user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$staff_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'")->fetch_assoc()['count'];
$document_count = $conn->query("SELECT COUNT(*) as count FROM documents WHERE status = 'active'")->fetch_assoc()['count'];
$total_requests = $conn->query("SELECT COUNT(*) as count FROM requests")->fetch_assoc()['count'];

// Get recent activity
$recent_logs = $conn->query("
    SELECT al.*, u.full_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay System</title>
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
                <div class="role">Administrator</div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <span class="icon">📊</span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <span class="icon">👥</span> Manage Users
                    </a>
                </li>
                <li>
                    <a href="manage_documents.php">
                        <span class="icon">📄</span> Manage Documents
                    </a>
                </li>
                <li>
                    <a href="view_history.php">
                        <span class="icon">📜</span> Request History
                    </a>
                </li>
                <li>
                    <a href="/barangay_system/staff/dashboard.php">
                        <span class="icon">🧾</span> Staff Panel
                    </a>
                </li>
                <li><a href="view_archive.php" class="active"><span class="icon">🗂️</span> Deleted Archive</a></li>
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
                <h1>Admin Dashboard</h1>
                <p>System overview and management.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info">
                        <h3><?= $user_count ?></h3>
                        <p>Registered Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">🧑‍💼</div>
                    <div class="stat-info">
                        <h3><?= $staff_count ?></h3>
                        <p>Staff Members</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon yellow">📄</div>
                    <div class="stat-info">
                        <h3><?= $document_count ?></h3>
                        <p>Document Types</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">📋</div>
                    <div class="stat-info">
                        <h3><?= $total_requests ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-10">
                        <a href="manage_users.php" class="btn btn-primary">👥 Manage Users</a>
                        <a href="manage_documents.php" class="btn btn-secondary">📄 Manage Documents</a>
                        <a href="view_history.php" class="btn btn-secondary">📜 View History</a>
                        <a href="/barangay_system/staff/dashboard.php" class="btn btn-success">🧾 Process Requests</a>
                        <a href="view_archive.php" class="btn btn-warning">🗂️ View Archive</a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>📜 Recent Activity</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_logs)): ?>
                        <p class="text-center">No recent activity.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                            <td><?= htmlspecialchars($log['details'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                            <td><?= formatDate($log['created_at']) ?></td>
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
