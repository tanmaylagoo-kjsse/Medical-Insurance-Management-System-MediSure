<?php
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

if (isset($_POST['add'])) {
    $newUser = $_POST['new_username'];
    $newPass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $newUser, $newPass);
    $stmt->execute();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $username = $_POST['edit_username'];

    $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
}

$result = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
</head>
<body>

<h2>Welcome <?php echo $_SESSION['username']; ?></h2>
<a href="logout.php">Logout</a>

<form method="POST">
<input type="text" name="new_username" required placeholder="New Username">
<input type="password" name="new_password" required placeholder="New Password">
<button type="submit" name="add">Add</button>
</form>

<hr>

<table border="1">
<tr><th>ID</th><th>Username</th><th>Actions</th></tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['username'] ?></td>
<td>
<a href="?delete=<?= $row['id'] ?>">Delete</a>
<form method="POST" style="display:inline;">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="text" name="edit_username" required>
<button type="submit" name="update">Update</button>
</form>
</td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>