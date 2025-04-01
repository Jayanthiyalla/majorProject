<?php
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['domain'])) {
    $domain = trim($_POST['domain']);
    
    if (empty($domain)) {
        echo json_encode(['status' => 'error', 'message' => 'Domain name cannot be empty']);
        exit;
    }
    
    try {
        // Check if domain already exists
        $stmt = $conn->prepare("SELECT id FROM domains WHERE domain = ?");
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Domain already exists']);
            exit;
        }
        
        // Insert new domain
        $stmt = $conn->prepare("INSERT INTO domains (domain) VALUES (?)");
        $stmt->bind_param("s", $domain);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Domain added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add domain']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>