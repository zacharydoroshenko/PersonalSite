<?php
// public_html/views/manage_users.php
if (!has_role('super_admin')) die('Unauthorized');

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_user = $_POST['username'];
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $new_role = $_POST['role'];
    $perms = json_encode($_POST['permissions'] ?? []);

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, permissions) VALUES (?, ?, ?, ?)");
    $stmt->execute([$new_user, $new_pass, $new_role, $perms]);
    echo "<p style='color:green;'>User added successfully!</p>";
}

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>

<h2>User Management</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="viewer">Viewer</option>
        <option value="analyst">Analyst</option>
        <option value="super_admin">Super Admin</option>
    </select>
    <br><br>
    <strong>Permissions (for Analysts):</strong><br>
    <input type="checkbox" name="permissions[]" value="performance"> Performance<br>
    <input type="checkbox" name="permissions[]" value="behavior"> Behavior<br>
    <br>
    <button type="submit" name="add_user">Create User</button>
</form>

<hr>
<table>
    <tr><th>User</th><th>Role</th><th>Perms</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= $u['role'] ?></td>
        <td><?= $u['permissions'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>