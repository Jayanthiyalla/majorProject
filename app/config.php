<?php
$servername = "localhost"; // Keep it localhost
$username = "root"; // Default XAMPP MySQL user
$password = ""; // No password (unless set)
$database = "mvgr_iic_db"; // Your actual DB name

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
