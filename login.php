<<<<<<< HEAD
<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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

            header("Location: home.html");
            exit();

        } else {
            echo "Invalid password";
        }

    } else {
        echo "User not found";
    }
}
=======
<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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

            header("Location: home.html");
            exit();

        } else {
            echo "Invalid password";
        }

    } else {
        echo "User not found";
    }
}
>>>>>>> 9e3fa78525eb68c3fbbfc3ca037af4c5587c5510
?>