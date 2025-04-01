<?php
require 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid project ID']);
    exit;
}

$projectId = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(null);
    exit;
}

$project = $result->fetch_assoc();
echo json_encode($project);

$stmt->close();
$conn->close();