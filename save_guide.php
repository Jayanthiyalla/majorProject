<?php
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$name = $conn->real_escape_string($_POST['name'] ?? '');
$title = $conn->real_escape_string($_POST['title'] ?? '');
$department = $conn->real_escape_string($_POST['department'] ?? '');

if (empty($name) || empty($title) || empty($department)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Check if guide already exists
$checkSql = "SELECT * FROM guides WHERE name = '$name' AND title = '$title' AND department = '$department'";
$result = $conn->query($checkSql);

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This supervisor already exists']);
    exit;
}

// Insert new guide
$sql = "INSERT INTO guides (name, title, department) VALUES ('$name', '$title', '$department')";
if ($conn->query($sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();