<?php
// reporting.zacharydoroshenko.work/public_html/index.php

// 1. Error Reporting (Keep this on until the 500 error is gone)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Load Auth from one level UP
// __DIR__ is public_html, so we go up to find src
require_once(__DIR__ . '/../src/auth.php'); 

$action = $_GET['action'] ?? 'login';

// 3. Auth Guard
if (!is_logged_in() && !in_array($action, ['login', 'do_login'])) {
    header("Location: index.php?action=login");
    exit;
}

// 4. Routing
switch ($action) {

case 'manage_users':
        if (!has_role('super_admin')) {
            include 'views/403.php'; // Create a simple "Access Denied" page
            exit;
        }
        $view = 'views/manage_users.php';
        break;

    case 'performance_report':
        if (!can_access_section('performance')) {
            include 'views/403.php';
            exit;
        }
        $view = 'views/reports/performance.php';
        break;

    case 'login':
        $view = 'views/login.php'; // Views are inside public_html
        $title = 'Admin Login';
        break;

    case 'do_login':
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';

        if (validate_user($user, $pass)) {
            // validate_user already sets the session variables (role, permissions, etc.)
            header("Location: index.php?action=dashboard");
            exit;
        } else {
            header("Location: index.php?action=login&error=1");
            exit;
        }
    case 'logout':
        session_destroy();
        header("Location: index.php?action=login");
        exit;

    case 'dashboard':
    default:
        $view = 'views/dashboard.php';
        $title = 'Analytics Dashboard';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; }
        nav { width: 200px; background: #333; color: #fff; min-height: 100vh; padding: 20px; }
        nav a { color: #fff; text-decoration: none; display: block; padding: 10px 0; }
        main { flex: 1; padding: 40px; background: #f4f4f4; }
    </style>
</head>
<body>
    <?php if (is_logged_in()): ?>
    <nav>
        <h3>Admin</h3>
        <a href="index.php?action=dashboard">Dashboard</a>
        <a href="index.php?action=logout">Logout</a>
    </nav>
    <?php endif; ?>

    <main>
        <?php 
            // Check if view exists before including to prevent another 500 error
            if (file_exists(__DIR__ . '/' . $view)) {
                include(__DIR__ . '/' . $view); 
            } else {
                echo "<h2>View not found: " . htmlspecialchars($view) . "</h2>";
            }
        ?>
    </main>
</body>
</html>
