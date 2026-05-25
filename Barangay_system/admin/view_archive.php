<?php
require_once '../config/database.php';
checkRole(['admin']);

$tab     = $_GET['tab'] ?? 'users';
$success = '';
$error   = '';

// ── Handle Restore Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $archive_id = intval($_POST['archive_id'] ?? 0);

    if ($action === 'restore_user' && $archive_id > 0) {
        // Fetch archive record (must not be restored yet and not expired)
        $fetch = $conn->prepare("
            SELECT * FROM deleted_users_archive
            WHERE id = ? AND restored_at IS NULL AND expires_at > NOW()
        ");
        $fetch->bind_param("i", $archive_id);
        $fetch->execute();
        $rec = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        if ($rec) {
            // Check if the username is taken by an active user
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND deleted_at IS NULL");
            $check->bind_param("s", $rec['username']);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $error = "Cannot restore: username <strong>" . htmlspecialchars($rec['username']) . "</strong> is already taken by another active user.";
            } else {
                // Restore the soft-deleted user (clear deleted_at)
                $restore = $conn->prepare("UPDATE users SET deleted_at = NULL WHERE id = ?");
                $restore->bind_param("i", $rec['original_user_id']);
                $restore->execute();

                if ($restore->affected_rows > 0) {
                    // Mark archive record as restored
                    $now = date('Y-m-d H:i:s');
                    $mark = $conn->prepare("UPDATE deleted_users_archive SET restored_at = ?, restored_by_id = ?, restored_by_name = ? WHERE id = ?");
                    $mark->bind_param("sisi", $now, $_SESSION['user_id'], $_SESSION['full_name'], $archive_id);
                    $mark->execute();
                    $mark->close();
                    logActivity($conn, $_SESSION['user_id'], 'Restore User', "Restored user #{$rec['original_user_id']} ({$rec['username']}) from archive #{$archive_id}");
                    $success = "User <strong>" . htmlspecialchars($rec['full_name']) . "</strong> has been restored successfully.";
                } else {
                    // User row no longer exists (hard-deleted manually); re-insert
                    $ins = $conn->prepare("
                        INSERT INTO users (id, full_name, username, password, email, address, contact_number, role, status, created_at, deleted_at)
                        SELECT original_user_id, full_name, username, '', email, address, contact_number, role, user_status, user_created_at, NULL
                        FROM deleted_users_archive WHERE id = ?
                    ");
                    $ins->bind_param("i", $archive_id);
                    if ($ins->execute()) {
                        $now  = date('Y-m-d H:i:s');
                        $mark = $conn->prepare("UPDATE deleted_users_archive SET restored_at = ?, restored_by_id = ?, restored_by_name = ? WHERE id = ?");
                        $mark->bind_param("sisi", $now, $_SESSION['user_id'], $_SESSION['full_name'], $archive_id);
                        $mark->execute();
                        $mark->close();
                        logActivity($conn, $_SESSION['user_id'], 'Restore User (Re-insert)', "Re-inserted user from archive #{$archive_id}");
                        $success = "User <strong>" . htmlspecialchars($rec['full_name']) . "</strong> has been restored (re-created).";
                    } else {
                        $error = 'Failed to restore the user.';
                    }
                    $ins->close();
                }
                $restore->close();
            }
        } else {
            $error = 'Archive record not found, already restored, or the 30-day window has expired.';
        }

    } elseif ($action === 'restore_document' && $archive_id > 0) {
        $fetch = $conn->prepare("
            SELECT * FROM deleted_documents_archive
            WHERE id = ? AND restored_at IS NULL AND expires_at > NOW()
        ");
        $fetch->bind_param("i", $archive_id);
        $fetch->execute();
        $rec = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        if ($rec) {
            // Try to restore via soft-delete flag first
            $restore = $conn->prepare("UPDATE documents SET deleted_at = NULL WHERE id = ?");
            $restore->bind_param("i", $rec['original_document_id']);
            $restore->execute();

            if ($restore->affected_rows > 0) {
                $now  = date('Y-m-d H:i:s');
                $mark = $conn->prepare("UPDATE deleted_documents_archive SET restored_at = ?, restored_by_id = ?, restored_by_name = ? WHERE id = ?");
                $mark->bind_param("sisi", $now, $_SESSION['user_id'], $_SESSION['full_name'], $archive_id);
                $mark->execute();
                $mark->close();
                logActivity($conn, $_SESSION['user_id'], 'Restore Document', "Restored document #{$rec['original_document_id']} ({$rec['document_name']}) from archive #{$archive_id}");
                $success = "Document <strong>" . htmlspecialchars($rec['document_name']) . "</strong> has been restored successfully.";
            } else {
                // Re-insert if gone
                $ins = $conn->prepare("INSERT INTO documents (document_name, description, requirements, processing_days, fee, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("sssidss",
                    $rec['document_name'], $rec['description'], $rec['requirements'],
                    $rec['processing_days'], $rec['fee'], $rec['document_status'], $rec['document_created_at']
                );
                if ($ins->execute()) {
                    $now  = date('Y-m-d H:i:s');
                    $mark = $conn->prepare("UPDATE deleted_documents_archive SET restored_at = ?, restored_by_id = ?, restored_by_name = ? WHERE id = ?");
                    $mark->bind_param("sisi", $now, $_SESSION['user_id'], $_SESSION['full_name'], $archive_id);
                    $mark->execute();
                    $mark->close();
                    logActivity($conn, $_SESSION['user_id'], 'Restore Document (Re-insert)', "Re-inserted document from archive #{$archive_id}");
                    $success = "Document <strong>" . htmlspecialchars($rec['document_name']) . "</strong> has been restored (re-created).";
                } else {
                    $error = 'Failed to restore the document.';
                }
                $ins->close();
            }
            $restore->close();
        } else {
            $error = 'Archive record not found, already restored, or the 30-day window has expired.';
        }
    }
}

// Helper: days-remaining label
function daysRemaining($expires_at) {
    if (!$expires_at) return '';
    $diff = (int) ceil((strtotime($expires_at) - time()) / 86400);
    if ($diff <= 0)  return '<span style="color:#c0392b;font-weight:700;">Expired</span>';
    if ($diff <= 3)  return "<span style='color:#c0392b;font-weight:700;'>⚠️ $diff day(s) left</span>";
    if ($diff <= 7)  return "<span style='color:#e67e22;font-weight:600;'>$diff days left</span>";
    return "<span style='color:#27ae60;'>$diff days left</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Archive - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
    <style>
        .tab-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 24px; border-radius: 6px; border: 2px solid #ddd; background: #fff;
                   cursor: pointer; font-size: 1em; font-weight: 600; color: #555; text-decoration: none; }
        .tab-btn.active { background: #c0392b; border-color: #c0392b; color: #fff; }
        .tab-btn:hover:not(.active) { background: #f5f5f5; }
        .archive-badge { display: inline-block; background: #c0392b; color: #fff;
                         border-radius: 4px; padding: 2px 8px; font-size: 0.8em; font-weight: 700; }
        .deleted-by-cell { font-size: 0.88em; }
        .deleted-by-cell .who { font-weight: 700; color: #2c3e50; }
        .deleted-by-cell .role-tag { font-size: 0.8em; color: #888; }
        .reason-cell { font-size: 0.85em; color: #555; font-style: italic; max-width: 200px; word-break: break-word; }
        .empty-archive { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-archive .icon { font-size: 3em; }
        .expiry-cell { font-size: 0.85em; text-align: center; }
        .restored-badge { background: #27ae60; color: #fff; border-radius: 4px; padding: 2px 8px; font-size: 0.8em; font-weight: 700; }
        .btn-restore { background: #27ae60; color: #fff; border: none; border-radius: 5px; padding: 5px 12px; cursor: pointer; font-size: 0.85em; font-weight: 600; }
        .btn-restore:hover { background: #1e8449; }
        tr.restored-row { opacity: 0.6; }
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
                <li><a href="manage_users.php"><span class="icon">👥</span> Manage Users</a></li>
                <li><a href="manage_documents.php"><span class="icon">📄</span> Manage Documents</a></li>
                <li><a href="view_history.php"><span class="icon">📜</span> Request History</a></li>
                <li><a href="/barangay_system/staff/dashboard.php"><span class="icon">🧾</span> Staff Panel</a></li>
                <li><a href="view_archive.php"><span class="icon">🗂️</span> Deleted Archive</a></li>
                <li><a href="/barangay_system/logout.php"><span class="icon">🚪</span> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>🗂️ Deleted Archive</h1>
                <p>Soft-deleted records are recoverable within <strong>30 days</strong> of deletion. After that they are permanently purged.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <!-- Tabs -->
            <div class="tab-bar">
                <a href="?tab=users"     class="tab-btn <?= $tab==='users'     ? 'active':'' ?>">👥 Deleted Users</a>
                <a href="?tab=documents" class="tab-btn <?= $tab==='documents' ? 'active':'' ?>">📄 Deleted Documents</a>
            </div>

            <?php if ($tab === 'users'): ?>
            <?php
                $archived_users = $conn->query("
                    SELECT * FROM deleted_users_archive ORDER BY deleted_at DESC
                ")->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="card">
                <div class="card-header">
                    <h3>Deleted Users <span class="archive-badge"><?= count($archived_users) ?> records</span></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($archived_users)): ?>
                        <div class="empty-archive"><div class="icon">✅</div><p>No deleted users on record.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Archive #</th><th>Original ID</th><th>Name</th><th>Username</th>
                                    <th>Role</th><th>Was Status</th><th>Registered</th>
                                    <th>Deleted By</th><th>Reason</th><th>Deleted On</th>
                                    <th>Expires / Restored</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_users as $rec): ?>
                                <?php $isRestored = !empty($rec['restored_at']); ?>
                                <?php $isExpired  = !$isRestored && !empty($rec['expires_at']) && strtotime($rec['expires_at']) < time(); ?>
                                <tr class="<?= $isRestored ? 'restored-row' : '' ?>">
                                    <td><?= $rec['id'] ?></td>
                                    <td>#<?= $rec['original_user_id'] ?></td>
                                    <td><?= htmlspecialchars($rec['full_name']) ?></td>
                                    <td><?= htmlspecialchars($rec['username']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $rec['role']==='admin'?'danger':($rec['role']==='staff'?'info':'secondary') ?>">
                                            <?= ucfirst($rec['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $rec['user_status']==='active'?'success':'danger' ?>">
                                            <?= ucfirst($rec['user_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($rec['user_created_at']) ?></td>
                                    <td class="deleted-by-cell">
                                        <div class="who"><?= htmlspecialchars($rec['deleted_by_name']) ?></div>
                                        <div class="role-tag"><?= ucfirst($rec['deleted_by_role']) ?> · ID #<?= $rec['deleted_by_id'] ?></div>
                                    </td>
                                    <td class="reason-cell"><?= htmlspecialchars($rec['deletion_reason'] ?: '—') ?></td>
                                    <td>
                                        <?= date('M j, Y', strtotime($rec['deleted_at'])) ?><br>
                                        <small><?= date('g:i A', strtotime($rec['deleted_at'])) ?></small>
                                    </td>
                                    <td class="expiry-cell">
                                        <?php if ($isRestored): ?>
                                            <span class="restored-badge">✅ Restored</span><br>
                                            <small>by <?= htmlspecialchars($rec['restored_by_name'] ?? '—') ?></small><br>
                                            <small><?= date('M j, Y', strtotime($rec['restored_at'])) ?></small>
                                        <?php elseif (!empty($rec['expires_at'])): ?>
                                            <?= daysRemaining($rec['expires_at']) ?><br>
                                            <small>Exp: <?= date('M j, Y', strtotime($rec['expires_at'])) ?></small>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$isRestored && !$isExpired): ?>
                                            <form method="POST" onsubmit="return confirm('Restore this user?')">
                                                <input type="hidden" name="action" value="restore_user">
                                                <input type="hidden" name="archive_id" value="<?= $rec['id'] ?>">
                                                <input type="hidden" name="tab" value="users">
                                                <button type="submit" class="btn-restore">♻️ Restore</button>
                                            </form>
                                        <?php elseif ($isExpired): ?>
                                            <span style="color:#aaa;font-size:0.8em;">Expired</span>
                                        <?php else: ?>
                                            <span style="color:#aaa;font-size:0.8em;">—</span>
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

            <?php else: ?>
            <?php
                $archived_docs = $conn->query("
                    SELECT * FROM deleted_documents_archive ORDER BY deleted_at DESC
                ")->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="card">
                <div class="card-header">
                    <h3>Deleted Document Types <span class="archive-badge"><?= count($archived_docs) ?> records</span></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($archived_docs)): ?>
                        <div class="empty-archive"><div class="icon">✅</div><p>No deleted document types on record.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Archive #</th><th>Original ID</th><th>Document Name</th>
                                    <th>Days</th><th>Fee</th><th>Was Status</th><th>Created</th>
                                    <th>Deleted By</th><th>Reason</th><th>Deleted On</th>
                                    <th>Expires / Restored</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_docs as $rec): ?>
                                <?php $isRestored = !empty($rec['restored_at']); ?>
                                <?php $isExpired  = !$isRestored && !empty($rec['expires_at']) && strtotime($rec['expires_at']) < time(); ?>
                                <tr class="<?= $isRestored ? 'restored-row' : '' ?>">
                                    <td><?= $rec['id'] ?></td>
                                    <td>#<?= $rec['original_document_id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($rec['document_name']) ?></strong>
                                        <?php if ($rec['description']): ?><br><small><?= htmlspecialchars($rec['description']) ?></small><?php endif; ?>
                                    </td>
                                    <td><?= $rec['processing_days'] ?></td>
                                    <td>₱<?= number_format($rec['fee'], 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $rec['document_status']==='active'?'success':'danger' ?>">
                                            <?= ucfirst($rec['document_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($rec['document_created_at']) ?></td>
                                    <td class="deleted-by-cell">
                                        <div class="who"><?= htmlspecialchars($rec['deleted_by_name']) ?></div>
                                        <div class="role-tag"><?= ucfirst($rec['deleted_by_role']) ?> · ID #<?= $rec['deleted_by_id'] ?></div>
                                    </td>
                                    <td class="reason-cell"><?= htmlspecialchars($rec['deletion_reason'] ?: '—') ?></td>
                                    <td>
                                        <?= date('M j, Y', strtotime($rec['deleted_at'])) ?><br>
                                        <small><?= date('g:i A', strtotime($rec['deleted_at'])) ?></small>
                                    </td>
                                    <td class="expiry-cell">
                                        <?php if ($isRestored): ?>
                                            <span class="restored-badge">✅ Restored</span><br>
                                            <small>by <?= htmlspecialchars($rec['restored_by_name'] ?? '—') ?></small><br>
                                            <small><?= date('M j, Y', strtotime($rec['restored_at'])) ?></small>
                                        <?php elseif (!empty($rec['expires_at'])): ?>
                                            <?= daysRemaining($rec['expires_at']) ?><br>
                                            <small>Exp: <?= date('M j, Y', strtotime($rec['expires_at'])) ?></small>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$isRestored && !$isExpired): ?>
                                            <form method="POST" onsubmit="return confirm('Restore this document?')">
                                                <input type="hidden" name="action" value="restore_document">
                                                <input type="hidden" name="archive_id" value="<?= $rec['id'] ?>">
                                                <input type="hidden" name="tab" value="documents">
                                                <button type="submit" class="btn-restore">♻️ Restore</button>
                                            </form>
                                        <?php elseif ($isExpired): ?>
                                            <span style="color:#aaa;font-size:0.8em;">Expired</span>
                                        <?php else: ?>
                                            <span style="color:#aaa;font-size:0.8em;">—</span>
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
            <?php endif; ?>

        </main>
    </div>

    <script src="/barangay_system/js/main.js"></script>
</body>
</html>
