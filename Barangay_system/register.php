<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: /barangay_system/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $full_name = sanitize($conn, $_POST['full_name'] ?? '');
    $username = sanitize($conn, $_POST['username'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = sanitize($conn, $_POST['address'] ?? '');
    $contact_number = sanitize($conn, $_POST['contact_number'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 4) {
        $errors[] = 'Username must be at least 4 characters.';
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username already exists.';
        }
        $stmt->close();
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    if($contact_number && !preg_match('/^[0-9+\-\s]+$/', $contact_number)) {
        $errors[] = 'Contact number can only contain digits, spaces, + and - characters.';
    }
    if (strlen($address) > 255) {
        $errors[] = 'Address cannot exceed 255 characters.';
    }
    
    // If no errors, create account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
       $stmt = $conn->prepare("
    INSERT INTO users (
        full_name,
        username,
        password,
        email,
        address,
        contact_number,
        role,
        status,
        failed_attempts,
        account_status
    )
    VALUES (
        ?, ?, ?, ?, ?, ?,
        'user',
        'active',
        0,
        'active'
    )
");;
        $stmt->bind_param("ssssss", $full_name, $username, $hashed_password, $email, $address, $contact_number);
        
        if ($stmt->execute()) {
            logActivity($conn, $stmt->insert_id, 'Registration', 'New user registered');
            header("Location: index.php?registered=1");
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
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
    <title>Register - Barangay Document Request System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box" style="max-width: 500px;">
            <div class="logo">🏢</div>
            <h1>Create Account</h1>
            <p class="subtitle">Register to request barangay documents</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Enter your full name" required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Choose a username (min 4 characters)" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email (optional)"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" class="form-control" 
                           placeholder="Enter your contact number" required
                           value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <textarea id="address" name="address" class="form-control" 
                              placeholder="Enter your complete address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Create a password (min 6 characters)" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    📝 Create Account
                </button>
            </form>
            
            <p class="text-center mt-20">
                Already have an account? <a href="index.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
