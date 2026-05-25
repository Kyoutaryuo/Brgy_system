<?php
require_once __DIR__ . '/../config/database.php';

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Document Request System</title>
    <link rel="stylesheet" href="/barangay_system/css/style.css">
</head>
<body>
