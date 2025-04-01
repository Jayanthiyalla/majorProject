<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

include 'config.php';

$project_id = (int)$_GET['id'];

// Get project info
$project_query = $conn->prepare("SELECT theme FROM projects WHERE id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project = $project_query->get_result()->fetch_assoc();

// Get all ratings for this project
$ratings_query = $conn->prepare("
    SELECT fr.faculty_id, fr.rating, fr.rated_at, u.full_name 
    FROM faculty_ratings fr
    LEFT JOIN users u ON fr.faculty_id = u.username
    WHERE fr.project_id = ?
    ORDER BY fr.rated_at DESC
");
$ratings_query->bind_param("i", $project_id);
$ratings_query->execute();
$ratings = $ratings_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Ratings - <?= htmlspecialchars($project['theme']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Ratings for: <?= htmlspecialchars($project['theme']) ?></h2>
        
        <div class="card mt-4">
            <div class="card-header">
                <h4>Faculty Ratings</h4>
            </div>
            <div class="card-body">
                <?php if ($ratings->num_rows > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th>Rating</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($rating = $ratings->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($rating['full_name'] ?? $rating['faculty_id']) ?></td>
                                <td>
                                    <?= str_repeat('★', $rating['rating']) . str_repeat('☆', 5 - $rating['rating']) ?>
                                    (<?= $rating['rating'] ?>/5)
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($rating['rated_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No ratings submitted yet for this project.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="view_reports.php" class="btn btn-secondary mt-3">Back to Reports</a>
    </div>
</body>
</html>