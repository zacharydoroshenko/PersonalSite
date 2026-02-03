<?php
session_start();

// 1. Clear session data
$_SESSION = array();

// 2. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the server-side session file
session_destroy();
?>
<html>
<head><title>PHP Session Destroyed</title></head>
<body>
    <h1>Session Destroyed</h1>
    <a href="/hw2/index-state.html">Back to State Entry</a><br />
    <a href="sessions1-php.php">Back to Page 1</a><br />
    <a href="sessions2-php.php">Back to Page 2</a>
</body>
</html>