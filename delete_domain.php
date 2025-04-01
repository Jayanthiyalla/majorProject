<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';

if (empty($domain)) {
    echo json_encode(['success' => false, 'message' => 'Domain cannot be empty']);
    exit();
}

try {
    // Check if domain is used in any projects first
    $check = $conn->prepare("SELECT COUNT(*) FROM projects WHERE domain = ?");
    $check->bind_param("s", $domain);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();
    
    if ($count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete - domain is used in existing projects'
        ]);
        exit();
    }
    
    // Proceed with deletion
    $stmt = $conn->prepare("DELETE FROM domains WHERE domain = ?");
    $stmt->bind_param("s", $domain);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}