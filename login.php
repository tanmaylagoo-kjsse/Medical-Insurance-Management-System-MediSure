<?php
session_start();
include "config.php";

// AUTO LOGIN USING COOKIE (optional but good)
if (!isset($_SESSION['username']) && isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
    header("Location: home.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['username'] = $user['username'];

            // remember me cookie
            if ($remember) {
                setcookie("username", $user['username'], time() + (7 * 24 * 60 * 60), "/");
            }

            // 🔥 MAIN THING YOU WANT
            header("Location: home.php");
            exit();

        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<form method="POST">
    <input type="text" name="username" required placeholder="Username">
    <input type="password" name="password" required placeholder="Password">

    <label>
        <input type="checkbox" name="remember"> Remember Me
    </label>

    <button type="submit">Login</button>
</form>

</body>
</html>