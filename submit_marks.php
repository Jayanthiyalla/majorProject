<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Empty by default in XAMPP
$dbname = "mvgr_iic_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$project = $_POST['project'];
$review1 = $_POST['review1'];
$review2 = $_POST['review2'];
$internal = $_POST['internal'];
$comments = $_POST['comments'];
$submitted_by = $_SESSION['username'];
$submission_date = date('Y-m-d H:i:s');

// First check if project already exists
$check_sql = "SELECT id FROM project_marks WHERE project_name = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $project);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    // Project exists - update the record
    $sql = "UPDATE project_marks 
            SET review1_marks = ?, 
                review2_marks = ?, 
                internal_marks = ?, 
                comments = ?, 
                submitted_by = ?, 
                submission_date = ?
            WHERE project_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissss", $review1, $review2, $internal, $comments, $submitted_by, $submission_date, $project);
} else {
    // Project doesn't exist - insert new record
    $sql = "INSERT INTO project_marks 
            (project_name, review1_marks, review2_marks, internal_marks, comments, submitted_by, submission_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siiisss", $project, $review1, $review2, $internal, $comments, $submitted_by, $submission_date);
}

// Execute and respond
if ($stmt->execute()) {
    header("Location: admin.php?success=1");
} else {
    header("Location: admin.php?error=" . urlencode($conn->error));
}

$check_stmt->close();
$stmt->close();
$conn->close();
?>