<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB CONNECTION
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ---------------- HELPERS ----------------

function sanitize($conn, $data) {
    return htmlspecialchars(trim($conn->real_escape_string($data)));
}

function isLoggedIn() {
    return isset($_SESSION['role']) &&
           isset($_SESSION['user_id']) &&
           $_SESSION['user_id'] >= -1;
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['role']) && isset($_SESSION['user_id']) && $_SESSION['user_id'] >= -1;
    }
}

function checkRole($roles = []) {
    global $conn;

    if (!isLoggedIn()) {
        header("Location: /barangay_system/index.php");
        exit();
    }

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        header("Location: /barangay_system/index.php?error=unauthorized");
        exit();
    }

    // ── Verify the logged-in user still exists in the DB ──
    // Skip for demo accounts (id <= 0) — they have no real DB row.
    $uid = intval($_SESSION['user_id']);
    if ($uid > 0) {
        $check = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $check->bind_param("i", $uid);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $check->close();
            session_unset();
            session_destroy();
            header("Location: /barangay_system/index.php?error=account_deleted");
            exit();
        }
        $check->close();
    }
}

// FIXED: logActivity now actually executes the statement
function logActivity($conn, $user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

function formatDate($date) {
    return $date ? date('F j, Y', strtotime($date)) : 'N/A';
}

function formatTime($time) {
    return $time ? date('g:i A', strtotime($time)) : 'N/A';
}

function getStatusBadge($status) {
    $badges = [
        'pending'    => 'warning',
        'processing' => 'info',
        'approved'   => 'success',
        'rejected'   => 'danger',
        'claimed'    => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}
