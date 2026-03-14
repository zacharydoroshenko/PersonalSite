<?php
// /public/login.php
require_once('../src/auth.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple hardcoded check for the checkpoint
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'password123') {
        $_SESSION['user_id'] = 1;
        header("Location: /dashboard.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<form method="POST">
    <h1>Admin Login</h1>
    <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
