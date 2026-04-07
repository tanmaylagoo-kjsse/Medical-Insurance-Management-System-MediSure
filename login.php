<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
        } else {
            echo "Invalid password<br>";
        }
    } else {
        echo "User not found<br>";
    }
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>CRUD Dashboard</title>
</head>
<body>

<h2>Login</h2>
<form method="POST">
    <input type="text" name="username" required placeholder="Username">
    <input type="password" name="password" required placeholder="Password">
    <button type="submit" name="login">Login</button>
</form>

<hr>

<?php if (isset($_SESSION['username'])): ?>

<h2>Welcome <?php echo $_SESSION['username']; ?></h2>

<form method="POST">
    <input type="text" name="new_username" required placeholder="New Username">
    <input type="password" name="new_password" required placeholder="New Password">
    <button type="submit" name="add">Add</button>
</form>

<hr>

<?php
$result = $conn->query("SELECT * FROM users");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Actions</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['username']}</td>
            <td>
                <a href='?delete={$row['id']}'>Delete</a>
                |
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='id' value='{$row['id']}'>
                    <input type='text' name='edit_username' required placeholder='New username'>
                    <button type='submit' name='update'>Update</button>
                </form>
            </td>
          </tr>";
}

echo "</table>";
?>

<?php else: ?>
<p>Please login to access dashboard</p>
<?php endif; ?>

</body>
</html>