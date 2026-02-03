<?php
session_start();
$name = $_SESSION['username'] ?? "You do not have a name set";
?>
<!DOCTYPE html>
<html>
<head><title>PHP Sessions</title></head>
<body>
    <h1>PHP Sessions Page 2</h1>
    <p><b>Name:</b> <?php echo htmlspecialchars($name); ?></p>
    
    <br>
    <a href="sessions1-php.php">Session Page 1</a><br>
    <a href="/hw2/index-state.html">Back to State Entry</a><br>
    
    <form style="margin-top:30px" action="destroy-session-php.php" method="POST">
        <button type="submit">Destroy Session</button>
    </form>
</body>
</html>