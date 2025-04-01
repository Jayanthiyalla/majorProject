<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mvgr_iic_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get project name from URL
$project = urldecode($_GET['project']);

// Fetch existing marks
$sql = "SELECT * FROM project_marks WHERE project_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $project);
$stmt->execute();
$result = $stmt->get_result();
$project_data = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review1 = $_POST['review1'];
    $review2 = $_POST['review2'];
    $internal = $_POST['internal'];
    $comments = $_POST['comments'];
    
    $update_sql = "UPDATE project_marks 
                  SET review1_marks = ?, 
                      review2_marks = ?, 
                      internal_marks = ?, 
                      comments = ?,
                      submission_date = NOW()
                  WHERE project_name = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iiiss", $review1, $review2, $internal, $comments, $project);
    
    if ($update_stmt->execute()) {
        header("Location: view_marks.php?success=1");
    } else {
        header("Location: view_marks.php?error=1");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Marks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Edit Marks for <?= htmlspecialchars($project) ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Review 1 Marks</label>
                                    <input type="number" class="form-control" name="review1" 
                                           value="<?= $project_data['review1_marks'] ?>" min="0" max="100" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Review 2 Marks</label>
                                    <input type="number" class="form-control" name="review2" 
                                           value="<?= $project_data['review2_marks'] ?>" min="0" max="100" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Internal Marks</label>
                                    <input type="number" class="form-control" name="internal" 
                                           value="<?= $project_data['internal_marks'] ?>" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comments</label>
                                <textarea class="form-control" name="comments" rows="3"><?= htmlspecialchars($project_data['comments']) ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="view_marks.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Marks</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>