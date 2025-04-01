<?php
session_start();
include 'config.php';

// Check admin access
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

if (isset($_GET['username'])) {
    $username = $_GET['username'];
    
    // Prevent deleting admin account
    if ($username === 'admin') {
        echo "<script>alert('Cannot delete admin account!'); window.location.href='manage_users.php';</script>";
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM credentials WHERE username = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('User deleted successfully!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('Error deleting user!'); window.location.href='manage_users.php';</script>";
    }
    $stmt->close();
} else {
    header("Location: manage_users.php");
}
?>