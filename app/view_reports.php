<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}
include 'config.php';

// Handle rating submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rate_project'])) {
    $project_id = (int)$_POST['project_id'];
    $rating = (int)$_POST['rating'];
    
    $stmt = $conn->prepare("UPDATE projects SET rating = ? WHERE id = ?");
    $stmt->bind_param("ii", $rating, $project_id);
    $stmt->execute();
}

// Fetch all projects
$projects = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
$domain_stats = $conn->query("SELECT domain, COUNT(*) as count FROM projects GROUP BY domain");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card { margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .chart-container { height: 300px; margin-bottom: 30px; }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .table-responsive { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <a href="admin.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>

        <h2 class="text-center mb-4">Project Analysis Dashboard</h2>
        
        <!-- Domain Distribution Chart -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Projects by Domain</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="domainChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Rating Distribution Chart -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4>Projects by Rating</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="ratingChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Projects List -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4>All Projects</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Theme</th>
                                <th>Domain</th>
                                <th>IIC Focus Area</th>
                                <th>Potential Impact</th>
                                <th>Academic Year</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($project = $projects->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['theme']) ?></td>
                                <td><?= htmlspecialchars($project['domain']) ?></td>
                                <td><?= htmlspecialchars($project['iic_focus_area']) ?></td>
                                <td><?= htmlspecialchars($project['potential_impact']) ?></td>
                                <td><?= htmlspecialchars($project['academic_year']) ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <select name="rating" class="form-select form-select-sm d-inline" style="width: auto;" onchange="this.form.submit()">
                                            <option value="0" <?= $project['rating'] == 0 ? 'selected' : '' ?>>0</option>
                                            <option value="1" <?= $project['rating'] == 1 ? 'selected' : '' ?>>1</option>
                                            <option value="2" <?= $project['rating'] == 2 ? 'selected' : '' ?>>2</option>
                                            <option value="3" <?= $project['rating'] == 3 ? 'selected' : '' ?>>3</option>
                                            <option value="4" <?= $project['rating'] == 4 ? 'selected' : '' ?>>4</option>
                                            <option value="5" <?= $project['rating'] == 5 ? 'selected' : '' ?>>5</option>
                                        </select>
                                        <input type="hidden" name="rate_project" value="1">
                                    </form>
                                    <span class="rating-stars">
                                        <?= str_repeat('★', $project['rating']) . str_repeat('☆', 5 - $project['rating']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="project_details.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Domain Distribution Chart
        const domainCtx = document.getElementById('domainChart').getContext('2d');
        const domainChart = new Chart(domainCtx, {
            type: 'pie',
            data: {
                labels: [<?php 
                    $domain_stats->data_seek(0);
                    while($row = $domain_stats->fetch_assoc()) {
                        echo "'".$row['domain']."',";
                    }
                ?>],
                datasets: [{
                    data: [<?php 
                        $domain_stats->data_seek(0);
                        while($row = $domain_stats->fetch_assoc()) {
                            echo $row['count'].","; 
                        }
                    ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#8AC24A', '#F06292', '#7986CB', '#E57373'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Rating Distribution Chart
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');
        const ratingChart = new Chart(ratingCtx, {
            type: 'bar',
            data: {
                labels: ['0 Stars', '1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Projects',
                    data: [
                        <?php
                        $ratings = $conn->query("SELECT rating, COUNT(*) as count FROM projects GROUP BY rating");
                        $rating_counts = [0,0,0,0,0,0];
                        while($row = $ratings->fetch_assoc()) {
                            $rating_counts[$row['rating']] = $row['count'];
                        }
                        echo implode(',', $rating_counts);
                        ?>
                    ],
                    backgroundColor: '#4BC0C0'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
