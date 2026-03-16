<?php
// reporting.zacharydoroshenko.work/public_html/index.php

// Errors logged server-side only — never shown to users
ini_set('display_errors', 0);
ini_set('log_errors',     1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../src/auth.php');

$action = $_GET['action'] ?? 'login';
$title  = 'Analytics Platform';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (!is_logged_in() && !in_array($action, ['login', 'do_login'])) {
    header("Location: index.php?action=login");
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function is_viewer_only() {
    return has_role('viewer') && !has_role('analyst') && !has_role('super_admin');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ── Routing ──────────────────────────────────────────────────────────────────
$view = null;

switch ($action) {

    // ── Auth ──────────────────────────────────────────────────────────────────
    case 'login':
        $view  = 'views/login.php';
        $title = 'Sign In';
        break;

    case 'do_login':
        csrf_verify();
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($user !== '' && $pass !== '' && validate_user($user, $pass)) {
            header("Location: index.php?action=view_reports");
        } else {
            header("Location: index.php?action=login&error=1");
        }
        exit;

    case 'logout':
        session_destroy();
        header("Location: index.php?action=login");
        exit;

    // ── Dashboard ─────────────────────────────────────────────────────────────
    case 'dashboard':
        if (is_viewer_only()) { header("Location: index.php?action=view_reports"); exit; }
        $view  = 'views/dashboard.php';
        $title = 'Dashboard';
        break;

    // ── Section reports ───────────────────────────────────────────────────────
    case 'performance_report':
        if (is_viewer_only() || !can_access_section('performance')) { $view = 'views/403.php'; break; }
        $view = 'views/reports/performance.php'; $title = 'Performance Report';
        break;

    case 'identity_report':
        if (is_viewer_only() || !can_access_section('identity')) { $view = 'views/403.php'; break; }
        $view = 'views/reports/identity.php'; $title = 'Identity Report';
        break;

    case 'behavior_report':
        if (is_viewer_only() || !can_access_section('behavior')) { $view = 'views/403.php'; break; }
        $view = 'views/reports/behavior.php'; $title = 'Behavior Report';
        break;

    // ── Report builder ────────────────────────────────────────────────────────
    case 'create_report':
        if (!has_role('super_admin') && !has_role('analyst')) { $view = 'views/403.php'; break; }
        $view = 'views/create_report.php'; $title = 'Create Report';
        break;

    // ── Saved reports ─────────────────────────────────────────────────────────
    case 'view_reports':
        $view  = 'views/view_reports.php';
        $title = 'Saved Reports';
        break;

    case 'report_detail':
        if (!isset($_GET['id'])) { header("Location: index.php?action=view_reports"); exit; }
        $view  = 'views/report_detail.php';
        $title = 'Report';
        break;

    // ── PDF export ────────────────────────────────────────────────────────────
    case 'export_pdf':
        if (!isset($_GET['id'])) { header("Location: index.php?action=view_reports"); exit; }
        include(__DIR__ . '/views/export_pdf.php');
        exit;

    // ── AJAX: table preview (analyst / super_admin only) ─────────────────────
    case 'table_preview':
        if (!has_role('analyst') && !has_role('super_admin')) {
            http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit;
        }
        include(__DIR__ . '/views/table_preview.php');
        exit;

    // ── User management ───────────────────────────────────────────────────────
    case 'manage_users':
        if (!has_role('super_admin')) { $view = 'views/403.php'; break; }
        $view = 'views/manage_users.php'; $title = 'Manage Users';
        break;

    // ── 404 ───────────────────────────────────────────────────────────────────
    default:
        http_response_code(404);
        $view  = 'views/404.php';
        $title = 'Page Not Found';
        break;
}

// Login page gets its own full-page layout (no sidebar)
if ($action === 'login') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — Analytics</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            background: #1a2332;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            overflow: hidden;
        }
        .login-header {
            background: #1a2332;
            padding: 32px 36px 28px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .login-header .brand-icon {
            font-size: 2.4rem;
            color: #4d9de0;
            display: block;
            margin-bottom: 10px;
        }
        .login-header h1 {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 4px;
        }
        .login-header p {
            color: #8899aa;
            font-size: .82rem;
            margin: 0;
        }
        .login-body {
            padding: 32px 36px 36px;
        }
        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: #444;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .form-control {
            border-radius: 6px;
            border: 1.5px solid #dde1e7;
            padding: 10px 14px;
            font-size: .92rem;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: #4d9de0;
            box-shadow: 0 0 0 3px rgba(77,157,224,.15);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 1.5px solid #dde1e7;
            color: #888;
        }
        .btn-signin {
            background: #1a2332;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 11px;
            font-size: .95rem;
            font-weight: 600;
            width: 100%;
            letter-spacing: .02em;
            transition: background .15s, transform .1s;
        }
        .btn-signin:hover  { background: #243044; color: #fff; }
        .btn-signin:active { transform: scale(.98); }
        .alert-danger {
            font-size: .85rem;
            border-radius: 6px;
            border: none;
            background: #fdf0f0;
            color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-bar-chart-fill brand-icon"></i>
            <h1>Analytics Platform</h1>
            <p>Sign in to your account</p>
        </div>
        <div class="login-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Invalid username or password.
                </div>
            <?php endif; ?>
            <form method="POST" action="index.php?action=do_login">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" id="username" name="username"
                               class="form-control" placeholder="Enter username"
                               autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-control" placeholder="Enter password"
                               autocomplete="current-password" required>
                    </div>
                </div>
                <button type="submit" class="btn-signin">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
// ── All other pages use the sidebar layout ────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Analytics</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; background: #f1f3f5; }
        #app-wrapper { display: flex; flex: 1; }

        #sidebar {
            width: 240px; min-width: 240px; min-height: 100vh;
            background: #1a2332; color: #c8d3e0;
            display: flex; flex-direction: column;
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        #sidebar .brand {
            padding: 20px 20px 14px;
            font-size: 1.05rem; font-weight: 700; color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.08);
            letter-spacing: .02em;
        }
        #sidebar .brand small { display:block; font-size:.7rem; font-weight:400; color:#8899aa; margin-top:2px; }
        #sidebar .nav-section {
            padding: 18px 14px 4px;
            font-size: .68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #5a7080;
        }
        #sidebar .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 20px; color: #c8d3e0;
            text-decoration: none; font-size: .88rem;
            border-left: 3px solid transparent;
            transition: background .15s, border-color .15s, color .15s;
        }
        #sidebar .nav-link:hover  { background: rgba(255,255,255,.06); color: #fff; }
        #sidebar .nav-link.active { background: rgba(255,255,255,.1); border-left-color: #4d9de0; color: #fff; }
        #sidebar .nav-link i      { font-size: 1rem; width: 18px; text-align: center; }
        #sidebar .sidebar-footer  { margin-top: auto; padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
        #sidebar .sidebar-user    { font-size: .82rem; color: #8899aa; margin-bottom: 8px; }
        #sidebar .sidebar-user strong { color: #fff; display: block; }

        #main-content { flex: 1; padding: 36px 40px; overflow-x: auto; }

        @media print {
            #sidebar, .no-print { display: none !important; }
            #main-content { padding: 0; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
<div id="app-wrapper">

<?php if (is_logged_in()): ?>
<nav id="sidebar">
    <div class="brand">
        <i class="bi bi-bar-chart-fill" style="color:#4d9de0;"></i> Analytics
        <small><?= htmlspecialchars($_SESSION['username'] ?? '') ?> &mdash; <?= ucfirst($_SESSION['role'] ?? '') ?></small>
    </div>

    <!-- <?php if (!is_viewer_only()): ?>
    <div class="nav-section">Overview</div>
    <a href="index.php?action=dashboard" class="nav-link <?= $action==='dashboard'?'active':'' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <div class="nav-section">Reports</div>
    <?php if (can_access_section('performance')): ?>
    <a href="index.php?action=performance_report" class="nav-link <?= $action==='performance_report'?'active':'' ?>">
        <i class="bi bi-lightning-charge"></i> Performance
    </a>
    <?php endif; ?>
    <?php if (can_access_section('identity')): ?>
    <a href="index.php?action=identity_report" class="nav-link <?= $action==='identity_report'?'active':'' ?>">
        <i class="bi bi-person-badge"></i> Identity
    </a>
    <?php endif; ?>
    <?php if (can_access_section('behavior')): ?>
    <a href="index.php?action=behavior_report" class="nav-link <?= $action==='behavior_report'?'active':'' ?>">
        <i class="bi bi-activity"></i> Behavior
    </a>
    <?php endif; ?>
    <?php endif; ?> -->

    <div class="nav-section">Saved Reports</div>
    <a href="index.php?action=view_reports" class="nav-link <?= $action==='view_reports'?'active':'' ?>">
        <i class="bi bi-folder2-open"></i> View Reports
    </a>
    <?php if (has_role('analyst') || has_role('super_admin')): ?>
    <a href="index.php?action=create_report" class="nav-link <?= $action==='create_report'?'active':'' ?>">
        <i class="bi bi-pencil-square"></i> Create Report
    </a>
    <?php endif; ?>

    <?php if (has_role('super_admin')): ?>
    <div class="nav-section">Admin</div>
    <a href="index.php?action=manage_users" class="nav-link <?= $action==='manage_users'?'active':'' ?>">
        <i class="bi bi-people"></i> Manage Users
    </a>
    <?php endif; ?>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <strong><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong>
            Signed in
        </div>
        <a href="index.php?action=logout" class="nav-link" style="padding:6px 0;">
            <i class="bi bi-box-arrow-left"></i> Sign Out
        </a>
    </div>
</nav>
<?php endif; ?>

<main id="main-content">
<?php
    if ($view && file_exists(__DIR__ . '/' . $view)) {
        include(__DIR__ . '/' . $view);
    } elseif ($view) {
        http_response_code(404);
        include(__DIR__ . '/views/404.php');
    }
?>
</main>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>