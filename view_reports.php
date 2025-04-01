<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}
include 'config.php';

// Handle rating submission
if (isset($_POST['rate_project']) && isset($_POST['project_id']) && isset($_POST['rating'])) {
    $project_id = $conn->real_escape_string($_POST['project_id']);
    $rating = (int)$_POST['rating'];
    $admin_id = $_SESSION['username'];
    
    // Ensure rating is between 0 and 5
    $rating = max(0, min(5, $rating));
    
    // Check if rating exists
    $check_query = "SELECT id FROM admin_ratings WHERE project_id = '$project_id' AND admin_id = '$admin_id'";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $query = "UPDATE admin_ratings SET rating = $rating, rated_at = NOW() 
                  WHERE project_id = '$project_id' AND admin_id = '$admin_id'";
    } else {
        $query = "INSERT INTO admin_ratings (project_id, admin_id, rating, rated_at) 
                  VALUES ('$project_id', '$admin_id', $rating, NOW())";
    }
    if ($conn->query($query)) {
        // Recalculate combined rating
        $recalc_query = "UPDATE projects p
                        SET rating = 
                            (SELECT IFNULL(AVG(rating), 0) FROM admin_ratings WHERE project_id = p.id) * 0.4 +
                            (SELECT IFNULL(AVG(rating), 0) FROM faculty_ratings WHERE project_id = p.id) * 0.3 +
                            (SELECT IFNULL(AVG(rating), 0) FROM external_ratings WHERE project_id = p.id) * 0.3
                        WHERE id = '$project_id'";
        $conn->query($recalc_query);
        
        // Success - reload the page to show updated rating
        header("Location: view_reports.php" . (!empty($selected_year) ? "?academic_year=$selected_year" : ""));
        exit();
    } else {
        // Error handling
        echo "<div class='alert alert-danger'>Error updating rating: " . $conn->error . "</div>";
    }
}

// Get selected academic year from GET parameter
$selected_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';

// Build page title based on selected year
$page_title = "All Projects Reports";
if (!empty($selected_year)) {
    $page_title = "All Projects Reports for Academic Year " . htmlspecialchars($selected_year);
}

// Build SQL queries with year filter
$projects_query = "SELECT 
    p.*,
    (SELECT IFNULL(AVG(rating), 0) FROM admin_ratings ar WHERE ar.project_id = p.id) as admin_rating,
    (SELECT COUNT(rating) FROM admin_ratings ar WHERE ar.project_id = p.id) as admin_count,
    IFNULL((SELECT AVG(rating) FROM faculty_ratings fr WHERE fr.project_id = p.id), 0) as faculty_avg,
    IFNULL((SELECT COUNT(rating) FROM faculty_ratings fr WHERE fr.project_id = p.id), 0) as faculty_count,
    IFNULL((SELECT AVG(rating) FROM external_ratings er WHERE er.project_id = p.id), 0) as external_avg,
    IFNULL((SELECT COUNT(rating) FROM external_ratings er WHERE er.project_id = p.id), 0) as external_count
FROM projects p";
$domain_stats_query = "SELECT domain, COUNT(*) as count FROM projects";
$ratings_query = "SELECT rating, COUNT(*) as count FROM projects GROUP BY rating";

if (!empty($selected_year)) {
    $filter = " WHERE academic_year = '" . $conn->real_escape_string($selected_year) . "'";
    $projects_query .= $filter;
    $domain_stats_query .= $filter . " GROUP BY domain";
    $ratings_query = "SELECT rating, COUNT(*) as count FROM projects" . $filter . " GROUP BY rating";
} else {
    $domain_stats_query .= " GROUP BY domain";
}

$projects = $conn->query($projects_query);
$domain_stats = $conn->query($domain_stats_query);
$ratings = $conn->query($ratings_query);
$rating_details = $conn->query("
    SELECT 
        p.id, 
        p.theme, 
        p.domain, 
        p.rating as combined_rating,
        IFNULL((SELECT AVG(rating) FROM admin_ratings ar WHERE ar.project_id = p.id), 0) as admin_avg,
        IFNULL((SELECT COUNT(rating) FROM admin_ratings ar WHERE ar.project_id = p.id), 0) as admin_count,
        IFNULL((SELECT AVG(rating) FROM faculty_ratings fr WHERE fr.project_id = p.id), 0) as faculty_avg,
        IFNULL((SELECT AVG(rating) FROM external_ratings er WHERE er.project_id = p.id), 0) as external_avg,
        IFNULL((SELECT COUNT(rating) FROM faculty_ratings fr WHERE fr.project_id = p.id), 0) as faculty_count,
        IFNULL((SELECT COUNT(rating) FROM external_ratings er WHERE er.project_id = p.id), 0) as external_count
    FROM projects p
    " . (!empty($selected_year) ? "WHERE p.academic_year = '" . $conn->real_escape_string($selected_year) . "'" : "") . "
    GROUP BY p.id, p.theme, p.domain, p.rating
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #f5f7fa;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
          
        /* Top Bar Styles */
        .top-bar {
            background:rgb(52, 51, 51);
            text-align: center;
            width: 100%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px;
        }
        .top-menu {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }
        .top-menu li {
            display: inline-block;
        }
        .top-menu a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            padding: 10px;
            display: inline-block;
            transition: all 0.3s;
        }
        .top-menu a:hover {
            color: #3498db;
            text-decoration: none;
        }
        
        /* Header Container */
        .inside-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #ffffff;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .site-logo {
            flex-shrink: 0;
        }
        .main-navigation {
            flex-grow: 1;
            text-align: right;
        }
        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: flex-end;
        }
        .menu li {
            margin-left: 20px;
        }
        .menu a {
            color: #2c3e50;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            padding: 5px 0;
            position: relative;
        }
        .menu a:hover {
            color: #3498db;
        }
        .menu a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: #3498db;
            bottom: 0;
            left: 0;
            transition: width 0.3s;
        }
        .menu a:hover:after {
            width: 100%;
        }
        .site-footer {
            background-color: #2c3e50;
            color: white;
            padding: 25px 0;
            text-align: center;
            width: 100%;
            margin-top: auto;
        }
        
        .footer-bar a {
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-bar a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        .card { margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .chart-container { height: 300px; margin-bottom: 30px; }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .table-responsive { margin-top: 20px; }
        .form-inline {
            display: inline-block;
        }
        .form-inline label {
            font-weight: 500;
            margin-bottom: 0;
            vertical-align: middle;
        }
        .form-select {
            display: inline-block;
            width: auto;
            vertical-align: middle;
        }
        @media (max-width: 767.98px) {
            .text-md-end {
                text-align: left !important;
                margin-top: 15px;
            }
            .form-inline {
                display: block;
            }
            .form-inline label {
                display: block;
                margin-bottom: 5px;
            }
            .form-select {
                width: 100%;
            }
        }
        .text-end {
            text-align: right;
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="top-bar">
        <nav>
            <ul class="top-menu">
                <li><a href="https://mvgrce.com/">About MVGR</a></li>
                <li><a href="admin.php">Admin Dashboard</a></li>
                <li><a href="https://iic.mvgrglug.com/sih-ideas-presentations/">SIH Ideas & Presentations</a></li>
                <li><a href="https://iic.mvgrglug.com/no-title/">Press & Media</a></li>
            </ul>
        </nav>
    </div>
    <header class="site-header">
        <div class="inside-header">
            <div class="site-logo">
                <a href="https://iic.mvgrglug.com/" rel="home">
                    <img class="header-image" alt="mvgriic"
                        src="https://iic.mvgrglug.com/wp-content/uploads/2024/09/cropped-iic-logo-1.png"
                        width="150">
                </a>
            </div>
            <nav class="main-navigation">
                <ul class="menu">
                    <li><a href="https://iic.mvgrglug.com/our-initiatives/">Our Initiatives</a></li>
                    <li><a href="https://iic.mvgrglug.com/team/">Team</a></li>
                    <li><a href="https://iic.mvgrglug.com/sih-2024/">SIH-2024</a></li>
                    <li><a href="https://iic.mvgrglug.com/synergyx/">SynergyX</a></li>
                    <li><a href="https://iic.mvgrglug.com/startups/">Startups</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <a href="admin.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>

        <!-- Page Title -->
        <h1 class="page-title">
            <?php echo $page_title; ?>
        </h1>

        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h4 class="mb-0">Project Analysis Dashboard</h4>
            </div>
            <div class="col-md-6 text-md-end">
                <form method="GET" class="form-inline d-inline-block">
                    <label for="academic_year" class="me-2">Filter by Academic Year:</label>
                    <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php
                        $years = $conn->query("SELECT DISTINCT academic_year FROM projects ORDER BY academic_year DESC");
                        while ($year = $years->fetch_assoc()) {
                            $selected = ($year['academic_year'] == $selected_year) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($year['academic_year']) . '" ' . $selected . '>' . 
                                 htmlspecialchars($year['academic_year']) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>
        
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
                                    <strong>Combined:</strong> 
                                    <?= $project['rating'] ? number_format((float)$project['rating'], 1) : 'Not rated' ?>/5<br>
                                    <small class="text-muted">
                                        <strong>Admin:</strong> <?= $project['admin_count'] > 0 ? number_format((float)$project['admin_rating'], 1) : 'N/A' ?>
                                        (<?= $project['admin_count'] ?> ratings)<br>
                                        <strong>Faculty:</strong> <?= $project['faculty_count'] > 0 ? number_format((float)$project['faculty_avg'], 1) : 'N/A' ?>
                                        (<?= $project['faculty_count'] ?> ratings)<br>
                                        <strong>External:</strong> <?= $project['external_count'] > 0 ? number_format((float)$project['external_avg'], 1) : 'N/A' ?>
                                        (<?= $project['external_count'] ?> ratings)
                                    </small>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <select name="rating" class="form-select form-select-sm d-inline" style="width: auto;" onchange="this.form.submit()">
                                            <option value="0" <?= ($project['admin_rating'] ?? 0) == 0 ? 'selected' : '' ?>>0</option>
                                            <option value="1" <?= ($project['admin_rating'] ?? 0) == 1 ? 'selected' : '' ?>>1</option>
                                            <option value="2" <?= ($project['admin_rating'] ?? 0) == 2 ? 'selected' : '' ?>>2</option>
                                            <option value="3" <?= ($project['admin_rating'] ?? 0) == 3 ? 'selected' : '' ?>>3</option>
                                            <option value="4" <?= ($project['admin_rating'] ?? 0) == 4 ? 'selected' : '' ?>>4</option>
                                            <option value="5" <?= ($project['admin_rating'] ?? 0) == 5 ? 'selected' : '' ?>>5</option>
                                        </select>
                                        <input type="hidden" name="rate_project" value="1">
                                    </form>
                                    <span class="rating-stars">
                                        <?= 
                                            $project['rating'] > 0 
                                            ? str_repeat('★', (int)round($project['rating'])) . str_repeat('☆', 5 - (int)round($project['rating'])) 
                                            : '☆☆☆☆☆ (Unrated)'
                                        ?>
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
        // Initialize charts with data from PHP
        document.addEventListener('DOMContentLoaded', function() {
            // Domain Distribution Chart
            const domainCtx = document.getElementById('domainChart').getContext('2d');
            new Chart(domainCtx, {
                type: 'pie',
                data: {
                    labels: [<?php 
                        $domain_stats->data_seek(0);
                        while($row = $domain_stats->fetch_assoc()) {
                            echo "'".addslashes($row['domain'])."',";
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
            new Chart(ratingCtx, {
                type: 'bar',
                data: {
                    labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                    datasets: [{
                        label: 'Number of Projects',
                        data: [
                            <?php
                            $rating_counts = [0, 0, 0, 0, 0]; // Initialize array for 1-5 stars
                            $projects_by_rating = $conn->query("
                                SELECT FLOOR(rating) as rounded_rating, COUNT(*) as count 
                                FROM projects 
                                WHERE rating > 0
                                " . (!empty($selected_year) ? "AND academic_year = '" . $conn->real_escape_string($selected_year) . "'" : "") . "
                                GROUP BY rounded_rating
                                ORDER BY rounded_rating
                            ");
                            while($row = $projects_by_rating->fetch_assoc()) {
                                if ($row['rounded_rating'] >= 1 && $row['rounded_rating'] <= 5) {
                                    $rating_counts[$row['rounded_rating']-1] = $row['count'];
                                }
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
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Projects'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Rating'
                            }
                        }
                    }
                }
            });
        });
    </script>
    <footer class="site-footer">
        <div class="inside-site-info">
            <div class="footer-bar">
                <p class="has-small-font-size">
                    © 2024 by IIC is licensed under 
                    <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/?ref=chooser-v1" 
                        target="_blank" rel="noreferrer noopener">CC BY-NC-ND 4.0</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>