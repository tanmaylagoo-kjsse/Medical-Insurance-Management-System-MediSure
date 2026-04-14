<?php
include "config.php";

// Ensure session is active, then clear and destroy it
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Unset all session variables
$_SESSION = array();
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}

session_unset();
session_destroy();

// Remove "remember me" cookie if present
setcookie("username", "", time() - 3600, "/");

header("Location: login.html");
exit();
?>