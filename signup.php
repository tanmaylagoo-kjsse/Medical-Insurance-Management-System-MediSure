<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password != $confirm) {
        echo "Passwords do not match";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT * FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "Username already exists";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "Signup successful<br>";
    } else {
        echo "Error";
    }
}

if (isset($_POST['add'])) {
    $username = $_POST['new_username'];
    $email = $_POST['new_email'];
    $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
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
    $email = $_POST['edit_email'];

    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup + CRUD</title>
</head>
<body>

<h2>Signup</h2>
<form method="POST">
    <input type="text" name="username" required placeholder="Username">
    <input type="email" name="email" required placeholder="Email">
    <input type="password" name="password" required placeholder="Password">
    <input type="password" name="confirm" required placeholder="Confirm Password">
    <button type="submit" name="signup">Signup</button>
</form>

<hr>

<h3>Add User</h3>
<form method="POST">
    <input type="text" name="new_username" required placeholder="Username">
    <input type="email" name="new_email" required placeholder="Email">
    <input type="password" name="new_password" required placeholder="Password">
    <button type="submit" name="add">Add</button>
</form>

<hr>

<?php
$result = $conn->query("SELECT * FROM users");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Actions</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['username']}</td>
            <td>{$row['email']}</td>
            <td>
                <a href='?delete={$row['id']}'>Delete</a>
                |
                <form method='POST' style='display:inline;'>
                    <input type='hidden' name='id' value='{$row['id']}'>
                    <input type='text' name='edit_username' required placeholder='New username'>
                    <input type='email' name='edit_email' required placeholder='New email'>
                    <button type='submit' name='update'>Update</button>
                </form>
            </td>
          </tr>";
}

echo "</table>";
?>

</body>
</html>