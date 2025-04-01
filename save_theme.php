<?php
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $conn->real_escape_string($_POST['theme'] ?? '');
    
    if (empty($theme)) {
        echo json_encode(['status' => 'error', 'message' => 'Theme name is required']);
        exit;
    }
    
    // Check if theme already exists
    $check = $conn->query("SELECT id FROM projects WHERE theme = '$theme' LIMIT 1");
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Theme already exists']);
        exit;
    }
    
    // Insert into database (you might want a separate themes table)
    $conn->query("INSERT INTO projects (theme) VALUES ('$theme')");
    
    if ($conn->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Theme added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add theme']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}