<?php
session_start();

// 1. Capture POST data from index-state.html
if (isset($_POST['username'])) { 
    $_SESSION['username'] = $_POST['username']; 
}

// 2. Determine display name or session status
$is_session_active = isset($_SESSION['username']);
$name = $is_session_active ? htmlspecialchars($_SESSION['username']) : "No session found. Please enter a name first.";
?>
<!DOCTYPE html>
<html>
<head><title>PHP Sessions - Page 1</title></head>
<body>
    <h1>PHP Sessions Page 1</h1>
    
    <p><b>Status:</b> <?php echo $name; ?></p>
    
    <br>
    <?php if ($is_session_active): ?>
        <a href="sessions2-php.php">Go to Session Page 2</a><br>
        <form style="margin-top:30px" action="destroy-session-php.php" method="POST">
            <button type="submit">Destroy Session</button>
        </form>
    <?php else: ?>
        <a href="/hw2/index-state.html">Back to State Entry to start a session</a>
    <?php endif; ?>
</body>
</html>