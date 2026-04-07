<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS medisur_db");
$conn->select_db("medisur_db");

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    password VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    contact_email VARCHAR(100),
    phone VARCHAR(20)
)");

$conn->query("CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    provider_id INT,
    type VARCHAR(50),
    price VARCHAR(50),
    description TEXT,
    FOREIGN KEY (provider_id) REFERENCES providers(id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    plan_id INT,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
)");

$conn->query("ALTER TABLE purchases 
ADD COLUMN IF NOT EXISTS purchase_amount DECIMAL(10,2)");

$conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rating INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("INSERT IGNORE INTO providers (id, name, contact_email, phone) VALUES
(1, 'Star Health', 'support@starhealth.com', '9876543210'),
(2, 'HDFC ERGO', 'help@hdfcergo.com', '9123456780'),
(3, 'ICICI Lombard', 'care@icicilombard.com', '9988776655')
");

$conn->query("INSERT IGNORE INTO plans (id, name, provider_id, type, price, description) VALUES
(1, 'Basic Health Plan', 1, 'Individual', '₹3000/year', 'Covers basic hospitalization expenses'),
(2, 'Family Care Plan', 2, 'Family', '₹8000/year', 'Covers entire family with maternity benefits'),
(3, 'Premium Plus Plan', 3, 'Individual', '₹12000/year', 'High coverage with critical illness benefits')
");

$conn->query("INSERT IGNORE INTO purchases (id, user_id, plan_id, purchase_amount, purchase_date) VALUES
(1, 1, 1, 3000.00, '2026-03-20 10:30:00'),
(2, 2, 2, 8000.00, '2026-03-21 14:15:00'),
(3, 3, 3, 12000.00, '2026-03-22 18:45:00')
");

$conn->query("INSERT IGNORE INTO feedback (id, user_id, rating, message) VALUES
(1, 1, 5, 'Excellent plans and smooth experience'),
(2, 2, 4, 'Good coverage but premium is slightly high'),
(3, 3, 5, 'Very reliable insurance service')
");

$conn->query("INSERT IGNORE INTO contact_messages (id, name, email, message) VALUES
(1, 'Malhar Kausadikar', 'malhar@gmail.com', 'I want more details about family plans'),
(2, 'Anuj Vajha', 'anuj@gmail.com', 'How do I claim insurance?'),
(3, 'Tanmay Lagoo', 'tanmay@gmail.com', 'Do you cover pre-existing diseases?')
");

if (!isset($_SESSION['username']) && isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $_SESSION['username'] = $username;
    }
}
?>