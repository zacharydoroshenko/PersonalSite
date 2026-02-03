<?php
session_start();

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();
?>
<!DOCTYPE html>
<html>
<head><title>Session Destroyed</title></head>
<body>
    <h1>Session Successfully Cleared</h1>
    <p>The server-side data has been wiped.</p>
    <a href="/hw2/phpcode/sessions1-php.php">Back to session 1</a>
</body>
</html>