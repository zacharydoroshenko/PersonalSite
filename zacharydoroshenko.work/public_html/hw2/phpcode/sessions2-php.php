<?php
session_start();

$is_session_active = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html>
<head><title>PHP Sessions - Page 2</title></head>
<body>
    <h1>PHP Sessions Page 2</h1>

    <?php if ($is_session_active): ?>
        <p><b>Session Name:</b> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <a href="sessions1-php.php">Back to Page 1</a>
    <?php else: ?>
        <p style="color:red;"><b>Error:</b> No active session found!</p>
        <a href="/hw2/index-state.html">Return to Dashboard</a>
    <?php endif; ?>
</body>
</html>