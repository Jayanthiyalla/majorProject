<?php
include 'config.php';

header('Content-Type: application/json');

$sql = "SELECT * FROM reviews ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(["error" => "No reviews found"]);
}

$conn->close();
?>