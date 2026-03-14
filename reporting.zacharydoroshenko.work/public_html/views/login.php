<h2>Login Required</h2>
<?php if (isset($_GET['error'])): ?>
    <p class="error">Invalid username or password.</p>
<?php endif; ?>
<form action="index.php?action=do_login" method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>
