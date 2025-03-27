<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

include 'config.php';

// Check if the project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Project ID is missing.");
}

$project_id = (int)$_GET['id'];

// Fetch project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    die("Project not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 30px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .back-btn { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3>Project Details</h3>
            </div>
            <div class="card-body">
                <p><strong>Theme:</strong> <?= htmlspecialchars($project['theme'] ?? 'N/A') ?></p>
                <p><strong>Domain:</strong> <?= htmlspecialchars($project['domain'] ?? 'N/A') ?></p>
                <p><strong>Guide(s):</strong> <?= htmlspecialchars($project['guides'] ?? 'N/A') ?></p>
                <p><strong>Regd No.:</strong> <?= htmlspecialchars($project['regd_no'] ?? 'N/A') ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($project['dept'] ?? 'N/A') ?></p>
                <p><strong>Current Status:</strong> <?= htmlspecialchars($project['current_status'] ?? 'N/A') ?></p>
                <p><strong>IIC Focus Area:</strong> <?= htmlspecialchars($project['iic_focus_area'] ?? 'N/A') ?></p>
                <p><strong>Potential Impact:</strong> <?= htmlspecialchars($project['potential_impact'] ?? 'N/A') ?></p>
                <p><strong>Relevant SDGs:</strong> <?= htmlspecialchars($project['relevant_sdgs'] ?? 'N/A') ?></p>
                <p><strong>Aligned Indian National Schemes:</strong> <?= htmlspecialchars($project['indian_national_schemes'] ?? 'N/A') ?></p>
                <p><strong>Washington Accord POs:</strong> <?= htmlspecialchars($project['washington_accord_pos'] ?? 'N/A') ?></p>
                <p><strong>Academic Year:</strong> <?= htmlspecialchars($project['academic_year'] ?? 'N/A') ?></p>
                
                <p><strong>Rating:</strong> 
                    <span class="rating-stars">
                        <?= str_repeat('★', $project['rating'] ?? 0) . str_repeat('☆', 5 - ($project['rating'] ?? 0)) ?>
                    </span>
                </p>

                <p><strong>Team Members:</strong> <?= htmlspecialchars($project['team_members'] ?? 'N/A') ?></p>

                <!-- Back to Reports Button -->
                <a href="view_reports.php" class="btn btn-secondary back-btn">Back to Reports</a>
            </div>
        </div>
    </div>
</body>
</html>
