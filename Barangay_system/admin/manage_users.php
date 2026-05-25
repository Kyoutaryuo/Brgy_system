<?php
require_once '../config/database.php';
checkRole(['admin']);

$success = '';
$error   = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0 && $user_id != $_SESSION['user_id']) {

          if ($action === 'unlock_account') {

            $stmt = $conn->prepare("
                UPDATE users
                SET failed_attempts = 0,
                    account_status = 'active'
                WHERE id = ?
            ");

            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {

                logActivity(
                    $conn,
                    $_SESSION['user_id'],
                    'Unlock Account',
                    "Unlocked user account #$user_id"
                );

                $success = 'User account unlocked successfully.';
            }

            $stmt->close();

        } elseif ($action === 'change_role') {

            $new_role = $_POST['new_role'] ?? '';

            if (in_array($new_role, ['user', 'staff', 'admin'])) {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET role = ?
                    WHERE id = ? AND deleted_at IS NULL
                ");

                $stmt->bind_param("si", $new_role, $user_id);

                if ($stmt->execute()) {

                    logActivity(
                        $conn,
                        $_SESSION['user_id'],
                        'Change Role',
                        "Changed user #$user_id role to $new_role"
                    );

                    $success = 'User role updated successfully.';
                }

                $stmt->close();
            }

        } elseif ($action === 'toggle_lock') {

    $stmt = $conn->prepare("
        UPDATE users
        SET account_status =
            IF(account_status = 'Locked', 'active', 'Locked')
        WHERE id = ? AND deleted_at IS NULL
    ");

    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {

        logActivity(
            $conn,
            $_SESSION['user_id'],
            'Toggle Lock',
            "Changed lock status for user #$user_id"
        );

        $success = 'User lock status updated successfully.';
    }

    $stmt->close();

           
       } elseif ($action === 'delete') {

    // SOFT DELETE
    $reason = trim($_POST['deletion_reason'] ?? '');
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $fetch = $conn->prepare("
        SELECT *
        FROM users
        WHERE id = ? AND deleted_at IS NULL
    ");

    $fetch->bind_param("i", $user_id);
    $fetch->execute();

    $target = $fetch->get_result()->fetch_assoc();

    $fetch->close();

    if ($target) {

        $now        = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

        $arch = $conn->prepare("
            INSERT INTO deleted_users_archive
            (
                original_user_id,
                full_name,
                username,
                email,
                address,
                contact_number,
                role,
                user_status,
                user_created_at,
                deleted_by_id,
                deleted_by_name,
                deleted_by_role,
                deletion_reason,
                ip_address,
                deleted_at,
                expires_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $arch->bind_param(
            "issssssssissssss",
            $target['id'],
            $target['full_name'],
            $target['username'],
            $target['email'],
            $target['address'],
            $target['contact_number'],
            $target['role'],
            $target['status'],
            $target['created_at'],
            $_SESSION['user_id'],
            $_SESSION['full_name'],
            $_SESSION['role'],
            $reason,
            $ip,
            $now,
            $expires_at
        );

        if ($arch->execute()) {

            $arch->close();

            $soft = $conn->prepare("
                UPDATE users
                SET deleted_at = ?
                WHERE id = ?
            ");

            $soft->bind_param("si", $now, $user_id);

            if ($soft->execute()) {

                logActivity(
                    $conn,
                    $_SESSION['user_id'],
                    'Soft Delete User',
                    "Soft-deleted user #{$target['id']} ({$target['username']})"
                );

                $success = "User deleted successfully.";

            } else {

                $error = 'Failed to soft-delete the user.';
            }

            $soft->close();

        } else {

            $arch->close();

            $error = 'Failed to archive the user.';
        }

    } else {

        $error = 'User not found.';
    }
}
    }
}

// Only active (non-deleted) users
$result = $conn->query("
    SELECT *
    FROM users
    WHERE deleted_at IS NULL
    ORDER BY id DESC
");

$users = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
    <style>
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 10px; padding: 30px; width: 460px; max-width: 95vw; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .modal h3 { margin-top: 0; color: #c0392b; }
        .modal .form-group { margin-bottom: 15px; }
        .modal label { display: block; font-weight: 600; margin-bottom: 5px; }
        .modal textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; resize: vertical; font-family: inherit; box-sizing: border-box; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .user-info-preview { background: #fff5f5; border: 1px solid #f5c6cb; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 0.9em; line-height: 1.6; }
        .soft-delete-note { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 10px 14px; font-size: 0.88em; color: #856404; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>🏢 Barangay System</h2></div>
            <div class="sidebar-user">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role">Administrator</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="manage_users.php" class="active"><span class="icon">👥</span> Manage Users</a></li>
                <li><a href="manage_documents.php"><span class="icon">📄</span> Manage Documents</a></li>
                <li><a href="view_history.php"><span class="icon">📜</span> Request History</a></li>
                <li><a href="/barangay_system/staff/dashboard.php"><span class="icon">🧾</span> Staff Panel</a></li>
                <li><a href="view_archive.php"><span class="icon">🗂️</span> Deleted Archive</a></li>
                <li><a href="/barangay_system/logout.php"><span class="icon">🚪</span> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>👥 Manage Users</h1>
                <p>View, manage, and delete user accounts. Deleted users are recoverable within 30 days.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <div class="card">
                <div class="card-header"><h3>All Users (<?= count($users) ?>)</h3></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Username</th><th>Email</th>
                                    <th>Role</th><th>Status</th>
<th>Security</th><th>Registered</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
    <?php if (($user['account_status'] ?? 'active') === 'Locked'): ?>
        <span class="badge badge-danger">Locked</span>
    <?php else: ?>
        <span class="badge badge-success">Normal</span>
    <?php endif; ?>
</td>
                                    <td><?= formatDate($user['created_at']) ?></td>
                                    <td>
                                       <?php if (
    $user['id'] != $_SESSION['user_id']
    && $user['role'] !== 'admin'
): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="change_role">
                                                <select name="new_role" onchange="this.form.submit()" class="form-control" style="width:auto;display:inline;padding:5px;">
                                                    <option value="user"  <?= $user['role']==='user'  ? 'selected':'' ?>>User</option>
                                                    <option value="staff" <?= $user['role']==='staff' ? 'selected':'' ?>>Staff</option>
                                                    <option value="admin" <?= $user['role']==='admin' ? 'selected':'' ?>>Admin</option>
                                                </select>
                                            </form>
                                          <form method="POST" style="display:inline;">
    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
    <input type="hidden" name="action" value="toggle_lock">

    <button type="submit"
        class="btn btn-sm <?= ($user['account_status'] ?? 'active') === 'Locked' ? 'btn-success' : 'btn-danger' ?>"
        onclick="return confirm('Are you sure?')">

        <?= ($user['account_status'] ?? 'active') === 'Locked' ? '🔓 Unlock' : '🔒 Lock' ?>

    </button>
</form>
                                           
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="openDeleteModal(<?= $user['id'] ?>,'<?= addslashes(htmlspecialchars($user['full_name'])) ?>','<?= addslashes(htmlspecialchars($user['username'])) ?>','<?= addslashes(ucfirst($user['role'])) ?>')">
                                                🗑️ Delete
                                            </button>
                                        <?php else: ?>
                                            <span style="color:#999;">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteUserModal" class="modal-overlay">
        <div class="modal">
            <h3>🗑️ Delete User</h3>
            <div class="soft-delete-note">
                ♻️ <strong>Soft Delete:</strong> The user will be hidden from the system but can be
                <strong>restored within 30 days</strong> from the Deleted Archive.
            </div>
            <div class="user-info-preview" id="deleteUserPreview"></div>
            <form method="POST" id="deleteUserForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="form-group">
                    <label for="deletion_reason">Reason for Deletion <span style="color:red;">*</span></label>
                    <textarea name="deletion_reason" id="deletion_reason" rows="3"
                              placeholder="e.g., Duplicate account, Violation of barangay policy, Request of user..."
                              required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">🗑️ Soft Delete (Recoverable 30 days)</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/barangay_system/js/main.js"></script>
    <script>
        function openDeleteModal(userId, fullName, username, role) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserPreview').innerHTML =
                '<strong>Name:</strong> ' + fullName + '<br>' +
                '<strong>Username:</strong> ' + username + '<br>' +
                '<strong>Role:</strong> ' + role;
            document.getElementById('deletion_reason').value = '';
            document.getElementById('deleteUserModal').classList.add('active');
        }
        function closeDeleteModal() {
            document.getElementById('deleteUserModal').classList.remove('active');
        }
        document.getElementById('deleteUserModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>
