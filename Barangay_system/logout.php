<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    logActivity($conn, $_SESSION['user_id'], 'Logout', 'User logged out');
}

// Destroy session
session_unset();
session_destroy();

header("Location: index.php?logout=1");
exit();
