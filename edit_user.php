<?php
session_start();
include 'config.php';

// Check admin access
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

if (isset($_GET['username']) && isset($_GET['role'])) {
    $username = $_GET['username'];
    $newRole = $_GET['role'];
    
    $stmt = $conn->prepare("UPDATE credentials SET role = ? WHERE username = ?");
    $stmt->bind_param("ss", $newRole, $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('User role updated successfully!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error updating user role!'); window.location.href='manage_users.php';</script>";
    }
    $stmt->close();
} else {
    header("Location: manage_users.php");
}
?>