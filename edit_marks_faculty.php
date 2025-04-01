<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Get project name from URL
$project_id = (int)$_GET['id'];

// Verify this faculty is a guide for this project
$verify_sql = "SELECT p.id, p.theme, GROUP_CONCAT(g.guide_name) as guides 
               FROM projects p
               JOIN project_guides g ON p.id = g.project_id
               WHERE p.id = ? AND g.guide_name = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("is", $project_id, $_SESSION['username']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header("Location: faculty.php?error=unauthorized");
    exit();
}

$project_data = $verify_result->fetch_assoc();

// Fetch existing marks if they exist
$marks_sql = "SELECT * FROM project_marks WHERE project_id = ?";
$marks_stmt = $conn->prepare($marks_sql);
$marks_stmt->bind_param("i", $project_id);
$marks_stmt->execute();
$marks_result = $marks_stmt->get_result();
$marks_data = $marks_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review1 = $_POST['review1'];
    $review2 = $_POST['review2'];
    $internal = $_POST['internal'];
    $comments = $_POST['comments'];
    
    if ($marks_data) {
        // Update existing marks
        $update_sql = "UPDATE project_marks 
                      SET review1_marks = ?, 
                          review2_marks = ?, 
                          internal_marks = ?, 
                          comments = ?,
                          submission_date = NOW()
                      WHERE project_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iiisi", $review1, $review2, $internal, $comments, $project_id);
    } else {
        // Insert new marks
        $insert_sql = "INSERT INTO project_marks 
                      (project_id, project_name, review1_marks, review2_marks, internal_marks, comments, faculty_id, submission_date)
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isiiiss", $project_id, $project_data['theme'], $review1, $review2, $internal, $comments, $_SESSION['user_id']);
    }
    
    if ($stmt->execute()) {
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
                        <h4>Enter Marks for <?= htmlspecialchars($project_data['theme']) ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Review 1 Marks (0-30)</label>
                                    <input type="number" class="form-control" name="review1" 
                                           value="<?= $marks_data['review1_marks'] ?? '' ?>" min="0" max="30" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Review 2 Marks (0-30)</label>
                                    <input type="number" class="form-control" name="review2" 
                                           value="<?= $marks_data['review2_marks'] ?? '' ?>" min="0" max="30" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Internal Marks (0-40)</label>
                                    <input type="number" class="form-control" name="internal" 
                                           value="<?= $marks_data['internal_marks'] ?? '' ?>" min="0" max="40" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comments</label>
                                <textarea class="form-control" name="comments" rows="3"><?= htmlspecialchars($marks_data['comments'] ?? '') ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="faculty.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit Marks</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>