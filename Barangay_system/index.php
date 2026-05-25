<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    header("Location: /barangay_system/$role/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle redirect error messages
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'account_deleted') {
        $error = 'Your account has been deleted by an administrator. Please contact your barangay office.';
    } elseif ($_GET['error'] === 'unauthorized') {
        $error = 'You do not have permission to access that page.';
    }
}

// Function to handle demo accounts
function checkDemoAccounts($username, $password) {
    $demoUsers = [
        'admin' => ['password' => 'admin123', 'role' => 'admin', 'full_name' => 'System Administrator', 'id' => 0],
        'staff' => ['password' => 'staff123', 'role' => 'staff', 'full_name' => 'Staff Member', 'id' => -1]
    ];

    if (isset($demoUsers[$username]) && $password === $demoUsers[$username]['password']) {
        return $demoUsers[$username];
    }
    return null;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {

        // Check demo accounts first
        $demoUser = checkDemoAccounts($username, $password);
        if ($demoUser) {
            // Set session variables
            $_SESSION['user_id'] = $demoUser['id'];
            $_SESSION['full_name'] = $demoUser['full_name'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $demoUser['role'];

            // Redirect based on role
            header("Location: /barangay_system/{$demoUser['role']}/dashboard.php");
            exit();
        }

        // Check regular users in DB
        $stmt = $conn->prepare("
          SELECT id, full_name, username, password, role, status, failed_attempts, account_status
            FROM users
          WHERE username = ? AND deleted_at IS NULL
");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

       if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    if ($user['status'] === 'inactive') {

        $error = 'Your account has been locked by the administrator. Please contact the administrator.';

    } elseif ($user['account_status'] === 'Locked') {

        $error = 'Your account is locked. Please contact admin.';

    } elseif (password_verify($password, $user['password'])) {

        // RESET FAILED ATTEMPTS
        $reset = $conn->prepare("
            UPDATE users
            SET failed_attempts = 0
            WHERE id = ?
        ");

        $reset->bind_param("i", $user['id']);
        $reset->execute();
        $reset->close();

        // SESSION VARIABLES
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        logActivity($conn, $user['id'], 'Login', 'User logged in successfully');

        header("Location: /barangay_system/{$user['role']}/dashboard.php");
        exit();

    } else {


    // Admin accounts never lock
    if ($user['role'] === 'admin') {

        $error = 'Invalid password.';

    } else {

        $attempts = $user['failed_attempts'] + 1;

        if ($attempts >= 3) {

            $lock = $conn->prepare("
                UPDATE users
                SET failed_attempts = ?, account_status = 'Locked'
                WHERE id = ?
            ");

            $lock->bind_param("ii", $attempts, $user['id']);
            $lock->execute();
            $lock->close();

            logActivity(
                $conn,
                $user['id'],
                'Account Locked',
                'User account locked after failed logins'
            );

            $error = 'Account locked after 3 failed login attempts. Please contact the Admin.';

        } else {

            $update = $conn->prepare("
                UPDATE users
                SET failed_attempts = ?
                WHERE id = ?
            ");

            $update->bind_param("ii", $attempts, $user['id']);
            $update->execute();
            $update->close();

                       $remaining = 3 - $attempts;

            $error = "Invalid password. Remaining attempts: $remaining";
        }
    }
       }
} else {

    $error = 'Invalid username or password.';
}

$stmt->close();
}
}
// Check for messages
if (empty($error)) {

    if (isset($_GET['registered'])) {
        $success = 'Registration successful! You can now log in.';
    }

    if (isset($_GET['logout'])) {
        $success = 'You have been logged out successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Document Request System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">🏢</div>
            <h1>Barangay System</h1>
            <p class="subtitle">Document Request Portal</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Enter your username" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    🔐 Login
                </button>
            </form>

            <p class="text-center mt-20">
                Don't have an account? <a href="register.php">Register here</a>
            </p>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

    
        </div>
    </div>
</body>
</html>