<?php
require_once '../config/database.php';
checkRole(['user']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get active documents
$documents = $conn->query("
    SELECT * 
    FROM documents 
    WHERE status = 'active' 
    AND deleted_at IS NULL
    ORDER BY document_name
")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_id = intval($_POST['document_id'] ?? 0);
    $purpose = sanitize($conn, $_POST['purpose'] ?? '');
    $schedule_date = sanitize($conn, $_POST['schedule_date'] ?? '');
    $schedule_time = sanitize($conn, $_POST['schedule_time'] ?? '');
    
    // Validation
    if ($document_id <= 0) {
        $error = 'Please select a document type.';
    } elseif (empty($purpose)) {
        $error = 'Please enter the purpose of your request.';
    } elseif (empty($schedule_date)) {
        $error = 'Please select a schedule date.';
    } elseif (strtotime($schedule_date) < strtotime('today')) {
        $error = 'Schedule date cannot be in the past.';
    } elseif (empty($schedule_time)) {
        $error = 'Please select a schedule time.';
    } else {
        // Insert request
        $stmt = $conn->prepare("INSERT INTO requests (user_id, document_id, purpose, schedule_date, schedule_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisss", $user_id, $document_id, $purpose, $schedule_date, $schedule_time);
        
        if ($stmt->execute()) {
            $request_id = $stmt->insert_id;
            
            // Handle file uploads
            if (!empty($_FILES['files']['name'][0])) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = time() . '_' . basename($_FILES['files']['name'][$key]);
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $stmt2 = $conn->prepare("INSERT INTO request_files (request_id, file_name, file_path) VALUES (?, ?, ?)");
                            $stmt2->bind_param("iss", $request_id, $_FILES['files']['name'][$key], $file_path);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                    }
                }
            }
            
            logActivity($conn, $user_id, 'New Request', "Submitted request #$request_id");
            $success = 'Your request has been submitted successfully! Request ID: #' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
        } else {
            $error = 'Failed to submit request. Please try again.';
        }
        $stmt->close();
    }
}

// Set minimum date to today
$min_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request - Barangay System</title>
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
                    <a href="new_request.php" class="active">
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
                <h1>📝 New Document Request</h1>
                <p>Fill out the form below to submit a new document request.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="document_id">Document Type *</label>
                            <select id="document_id" name="document_id" class="form-control" required>
                                <option value="">-- Select Document --</option>
                                <?php foreach ($documents as $doc): ?>
                                    <option value="<?= $doc['id'] ?>" 
                                            data-requirements="<?= htmlspecialchars($doc['requirements']) ?>"
                                            data-fee="<?= $doc['fee'] ?>"
                                            data-days="<?= $doc['processing_days'] ?>">
                                        <?= htmlspecialchars($doc['document_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="requirements-display"></div>
                        
                        <div class="form-group">
                            <label for="purpose">Purpose of Request *</label>
                            <textarea id="purpose" name="purpose" class="form-control" 
                                      placeholder="Please state the purpose of your request (e.g., Employment, School Requirement, etc.)" 
                                      required><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="schedule_date">Preferred Schedule Date *</label>
                                <input type="date" id="schedule_date" name="schedule_date" 
                                       class="form-control" min="<?= $min_date ?>" required
                                       value="<?= htmlspecialchars($_POST['schedule_date'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="schedule_time">Preferred Time *</label>
                                <select id="schedule_time" name="schedule_time" class="form-control" required>
                                    <option value="">-- Select Time --</option>
                                    <option value="08:00:00">8:00 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Requirements (Optional)</label>
                            <div class="file-upload">
                                <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf">
                                <div class="icon">📁</div>
                                <p>Click to upload or drag and drop</p>
                                <small>JPG, PNG, PDF (Max 5MB each)</small>
                            </div>
                            <div class="file-list"></div>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>📌 Reminder:</strong> Please bring the original copies of your requirements when claiming your document.
                        </div>
                        
                        <div class="d-flex gap-10">
                            <button type="submit" class="btn btn-primary">
                                ✅ Submit Request
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="/barangay_system/js/main.js"></script>
</body>
</html>
