<?php
// Initialize session - PHP handles cookie creation and /tmp storage automatically
session_start();

// Capture name from POST or keep existing session name
if (isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username'];
}

$name = $_SESSION['username'] ?? null;
?>
<html>
<head><title>PHP Sessions</title></head>
<body>
    <h1>PHP Sessions Page 1</h1>
    <?php if ($name): ?>
        <p><b>Name:</b> <?php echo htmlspecialchars($name); ?></p>
    <?php else: ?>
        <p><b>Name:</b> You do not have a name set</p>
    <?php endif; ?>

    <br/><br/>
    <a href="sessions2-php.php">Session Page 2</a><br/>
    <a href="/hw2/index-state.html">Back to State Entry</a><br/>

    <form style="margin-top:30px" action="destroy-session-php.php" method="POST">
        <button type="submit">Destroy Session</button>
    </form>
</body>
</html>