<?php
// ../src/auth.php
session_start();
require_once(__DIR__ . '/db_config.php');

function validate_user($username, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Debug: User not found in database.");
    }

    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $_SESSION['permissions'] = json_decode($user['permissions'] ?? '[]', true);

        // die("Debug: Login Success! Session role is: " . $_SESSION['role']); 
        return true;
    } else {
        echo "Typed Pass: " . $password . "<br>";
        echo "DB Hash: " . $user['password_hash'] . "<br>";
        if (password_verify($password, $user['password_hash'])) {
            echo "Match found!";
        } else {
            echo "No match.";
        }
        die();

    }
}

function is_logged_in() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

// Authorization Helper: Check if user has one of the required roles
function has_role($allowed_roles) {
    if (!is_logged_in()) return false;
    if (is_string($allowed_roles)) $allowed_roles = [$allowed_roles];
    return in_array($_SESSION['role'], $allowed_roles);
}

// Authorization Helper: Check if analyst has access to a specific report category
function can_access_section($section) {
    if ($_SESSION['role'] === 'super_admin') return true;
    if ($_SESSION['role'] === 'analyst') {
        return in_array($section, $_SESSION['permissions'] ?? []);
    }
    return false;
}
