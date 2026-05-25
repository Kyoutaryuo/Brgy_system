<?php
require_once '../config/database.php';
checkRole(['admin']);

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name         = sanitize($conn, $_POST['document_name'] ?? '');
        $description  = sanitize($conn, $_POST['description']   ?? '');
        $requirements = sanitize($conn, $_POST['requirements']  ?? '');
        $days         = intval($_POST['processing_days'] ?? 3);
        $fee          = floatval($_POST['fee'] ?? 0);

        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO documents (document_name, description, requirements, processing_days, fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssid", $name, $description, $requirements, $days, $fee);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Add Document', "Added document: $name");
                $success = 'Document type added successfully.';
            }
            $stmt->close();
        }

    } elseif ($action === 'edit') {
        $id           = intval($_POST['document_id'] ?? 0);
        $name         = sanitize($conn, $_POST['document_name'] ?? '');
        $description  = sanitize($conn, $_POST['description']   ?? '');
        $requirements = sanitize($conn, $_POST['requirements']  ?? '');
        $days         = intval($_POST['processing_days'] ?? 3);
        $fee          = floatval($_POST['fee'] ?? 0);

        if ($id > 0 && !empty($name)) {
            $stmt = $conn->prepare("UPDATE documents SET document_name=?, description=?, requirements=?, processing_days=?, fee=? WHERE id=? AND deleted_at IS NULL");
            $stmt->bind_param("sssidi", $name, $description, $requirements, $days, $fee, $id);
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Edit Document', "Edited document #$id");
                $success = 'Document updated successfully.';
            }
            $stmt->close();
        }

    } elseif ($action === 'toggle') {
        $id = intval($_POST['document_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE documents SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Toggle Document', "Toggled document #$id status");
            $success = 'Document status updated.';
        }
        $stmt->close();

    } elseif ($action === 'delete') {
        // SOFT DELETE
        $id     = intval($_POST['document_id'] ?? 0);
        $reason = trim($_POST['deletion_reason'] ?? '');
        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $fetch = $conn->prepare("SELECT * FROM documents WHERE id = ? AND deleted_at IS NULL");
        $fetch->bind_param("i", $id);
        $fetch->execute();
        $doc = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        if ($doc) {
            $now        = date('Y-m-d H:i:s');
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

            $arch = $conn->prepare("
                INSERT INTO deleted_documents_archive
                    (original_document_id, document_name, description, requirements,
                     processing_days, fee, document_status, document_created_at,
                     deleted_by_id, deleted_by_name, deleted_by_role,
                     deletion_reason, ip_address, deleted_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $arch->bind_param(
                "isssidsssisssss",
                $doc['id'], $doc['document_name'], $doc['description'], $doc['requirements'],
                $doc['processing_days'], $doc['fee'], $doc['status'], $doc['created_at'],
                $_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['role'],
                $reason, $ip, $now, $expires_at
            );

            if ($arch->execute()) {
                $arch->close();
                $soft = $conn->prepare("UPDATE documents SET deleted_at = ? WHERE id = ?");
                $soft->bind_param("si", $now, $id);
                if ($soft->execute()) {
                    logActivity($conn, $_SESSION['user_id'], 'Soft Delete Document',
                        "Soft-deleted document #{$doc['id']} ({$doc['document_name']}) — Reason: $reason — Recoverable until " . date('M j, Y', strtotime($expires_at)));
                    $success = "Document <strong>" . htmlspecialchars($doc['document_name']) . "</strong> has been deleted and can be restored from the archive within <strong>30 days</strong>.";
                } else {
                    $error = 'Failed to soft-delete the document.';
                }
                $soft->close();
            } else {
                $arch->close();
                $error = 'Failed to archive the document before deletion.';
            }
        } else {
            $error = 'Document not found or already deleted.';
        }
    }
}

// Only active (non-deleted) documents
$result = $conn->query("SELECT * FROM documents WHERE deleted_at IS NULL ORDER BY id DESC");
$documents = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - Barangay System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
    <style>
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 10px; padding: 30px; width: 460px; max-width: 95vw; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .modal h3 { margin-top: 0; }
        .modal .form-group { margin-bottom: 15px; }
        .modal label { display: block; font-weight: 600; margin-bottom: 5px; }
        .modal input[type=text], .modal input[type=number], .modal textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; box-sizing: border-box; }
        .modal textarea { resize: vertical; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .doc-info-preview { background: #fff5f5; border: 1px solid #f5c6cb; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 0.9em; line-height: 1.6; }
        .modal-delete h3 { color: #c0392b; }
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
                <li><a href="manage_users.php"><span class="icon">👥</span> Manage Users</a></li>
                <li><a href="manage_documents.php" class="active"><span class="icon">📄</span> Manage Documents</a></li>
                <li><a href="view_history.php"><span class="icon">📜</span> Request History</a></li>
                <li><a href="/barangay_system/staff/dashboard.php"><span class="icon">🧾</span> Staff Panel</a></li>
                <li><a href="view_archive.php"><span class="icon">🗂️</span> Deleted Archive</a></li>
                <li><a href="/barangay_system/logout.php"><span class="icon">🚪</span> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>📄 Manage Documents</h1>
                <p>Configure document types and requirements. Deleted documents are recoverable within 30 days.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <!-- Add New Document -->
            <div class="card mb-20">
                <div class="card-header"><h3>➕ Add New Document Type</h3></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label>Document Name *</label>
                                <input type="text" name="document_name" class="form-control" required placeholder="e.g., Barangay Clearance">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" class="form-control" placeholder="Brief description">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Requirements (comma-separated)</label>
                            <input type="text" name="requirements" class="form-control" placeholder="e.g., Valid ID, Cedula, 2x2 Photo">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label>Processing Days</label>
                                <input type="number" name="processing_days" class="form-control" value="3" min="1">
                            </div>
                            <div class="form-group">
                                <label>Processing Fee (₱)</label>
                                <input type="number" name="fee" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">➕ Add Document</button>
                    </form>
                </div>
            </div>

            <!-- Documents List -->
            <div class="card">
                <div class="card-header"><h3>All Document Types (<?= count($documents) ?>)</h3></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>Name</th><th>Requirements</th><th>Days</th><th>Fee</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['document_name']) ?></strong>
                                        <?php if ($doc['description']): ?>
                                            <br><small><?= htmlspecialchars($doc['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($doc['requirements'] ?: '-') ?></small></td>
                                    <td><?= $doc['processing_days'] ?></td>
                                    <td>₱<?= number_format($doc['fee'], 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $doc['status']==='active' ? 'success':'danger' ?>">
                                            <?= ucfirst($doc['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-secondary"
                                                data-modal="editModal<?= $doc['id'] ?>">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $doc['status']==='active' ? 'btn-danger':'btn-success' ?>">
                                                <?= $doc['status']==='active' ? 'Disable':'Enable' ?>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="openDocDeleteModal(<?= $doc['id'] ?>,'<?= addslashes(htmlspecialchars($doc['document_name'])) ?>','<?= addslashes(ucfirst($doc['status'])) ?>')">
                                            🗑️ Delete
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div id="editModal<?= $doc['id'] ?>" class="modal-overlay">
                                    <div class="modal">
                                        <div class="modal-header">
                                            <h3>Edit Document</h3>
                                            <button class="modal-close">&times;</button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                <div class="form-group">
                                                    <label>Document Name *</label>
                                                    <input type="text" name="document_name" class="form-control" required value="<?= htmlspecialchars($doc['document_name']) ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($doc['description']) ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Requirements (comma-separated)</label>
                                                    <input type="text" name="requirements" class="form-control" value="<?= htmlspecialchars($doc['requirements']) ?>">
                                                </div>
                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                                                    <div class="form-group">
                                                        <label>Processing Days</label>
                                                        <input type="number" name="processing_days" class="form-control" value="<?= $doc['processing_days'] ?>" min="1">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Fee (₱)</label>
                                                        <input type="number" name="fee" class="form-control" value="<?= $doc['fee'] ?>" min="0" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Document Modal -->
    <div id="deleteDocModal" class="modal-overlay">
        <div class="modal modal-delete">
            <h3>🗑️ Delete Document Type</h3>
            <div class="soft-delete-note">
                ♻️ <strong>Soft Delete:</strong> The document will be hidden from the system but can be
                <strong>restored within 30 days</strong> from the Deleted Archive.
            </div>
            <div class="doc-info-preview" id="deleteDocPreview"></div>
            <form method="POST" id="deleteDocForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="document_id" id="deleteDocId">
                <div class="form-group">
                    <label for="doc_deletion_reason">Reason for Deletion <span style="color:red;">*</span></label>
                    <textarea name="deletion_reason" id="doc_deletion_reason" rows="3"
                              placeholder="e.g., No longer offered, Replaced by another document type..."
                              required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDocDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">🗑️ Soft Delete (Recoverable 30 days)</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/barangay_system/js/main.js"></script>
    <script>
        function openDocDeleteModal(docId, docName, docStatus) {
            document.getElementById('deleteDocId').value = docId;
            document.getElementById('deleteDocPreview').innerHTML =
                '<strong>Document:</strong> ' + docName + '<br>' +
                '<strong>Status:</strong> ' + docStatus;
            document.getElementById('doc_deletion_reason').value = '';
            document.getElementById('deleteDocModal').classList.add('active');
        }
        function closeDocDeleteModal() {
            document.getElementById('deleteDocModal').classList.remove('active');
        }
        document.getElementById('deleteDocModal').addEventListener('click', function(e) {
            if (e.target === this) closeDocDeleteModal();
        });
    </script>
</body>
</html>
