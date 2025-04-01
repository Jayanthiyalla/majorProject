<?php
// config.php - Database Configuration
$servername = "localhost";
$username = "root"; 
$password = "";
$database = "mvgr_iic_db";

try {
    $conn = new mysqli($servername, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("System error: " . $e->getMessage());
}

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>