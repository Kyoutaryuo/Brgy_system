<?php
require_once '../config/database.php';
checkRole(['admin']);

// Filters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if ($status_filter) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $where[] = "DATE(r.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where[] = "DATE(r.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT r.*, d.document_name, u.full_name as requester_name, 
           s.full_name as staff_name
    FROM requests r 
    JOIN documents d ON r.document_id = d.id 
    JOIN users u ON r.user_id = u.id
    LEFT JOIN users s ON r.processed_by = s.id
    $where_clause
    ORDER BY r.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $requests = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - Barangay System</title>
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
                    <a href="dashboard.php">
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
                    <a href="view_history.php" class="active">
                        <span class="icon">📜</span> Request History
                    </a>
                </li>                
                <li>
                    <a href="/barangay_system/staff/dashboard.php">
                        <span class="icon">🧾</span> Staff Panel
                    </a>
                </li>
                <li>
                    <a href="view_archive.php">
                        <span class="icon">🗂️</span> Deleted Archive
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
                <h1>📜 Request History</h1>
                <p>View all document requests.</p>
            </div>
            
            <!-- Filters -->
            <div class="card mb-20">
                <div class="card-body">
                    <form method="GET" action="" class="d-flex gap-10 align-center">
                        <select name="status" class="form-control" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="claimed" <?= $status_filter === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                        </select>
                        
                        <input type="date" name="date_from" class="form-control" style="width: auto;" 
                               value="<?= htmlspecialchars($date_from) ?>" placeholder="From">
                        
                        <input type="date" name="date_to" class="form-control" style="width: auto;" 
                               value="<?= htmlspecialchars($date_to) ?>" placeholder="To">
                        
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="view_history.php" class="btn btn-secondary">Clear</a>
                    </form>
                </div>
            </div>
            
            <!-- Results -->
            <div class="card">
                <div class="card-header">
                    <h3>Results (<?= count($requests) ?> records)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($requests)): ?>
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <h3>No Records Found</h3>
                            <p>No requests match your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Requester</th>
                                        <th>Document</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td>#<?= str_pad($req['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($req['requester_name']) ?></td>
                                            <td><?= htmlspecialchars($req['document_name']) ?></td>
                                            <td>
                                                <?= formatDate($req['schedule_date']) ?><br>
                                                <small><?= formatTime($req['schedule_time']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getStatusBadge($req['status']) ?>">
                                                    <?= ucfirst($req['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($req['staff_name'] ?? '-') ?></td>
                                            <td><?= formatDate($req['created_at']) ?></td>
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
