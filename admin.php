
<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

include 'config.php';
// Fetch existing ratings for the modal dropdown
$ratings_query = "SELECT p.id, p.theme, 
    (SELECT rating FROM admin_ratings WHERE project_id = p.id AND admin_id = '{$_SESSION['username']}') as admin_rating
    FROM projects p";
$ratings_result = $conn->query($ratings_query);
$project_ratings = [];
while ($row = $ratings_result->fetch_assoc()) {
    $project_ratings[$row['id']] = $row;
}
// Handle marks submission from the modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project'])) {
    $project_id = $conn->real_escape_string($_POST['project']);
    $review1 = (int)$_POST['review1'];
    $review2 = (int)$_POST['review2'];
    $internal = (int)$_POST['internal'];
    $comments = $conn->real_escape_string($_POST['comments']);
    $admin_id = $_SESSION['username'];
    
    // Calculate average rating (you can adjust weights as needed)
    $average_rating = ($review1 + $review2 + $internal) / 3;
    $admin_rating = round($average_rating);
    
    // Insert or update admin rating
    $check_query = "SELECT id FROM admin_ratings WHERE project_id = '$project_id' AND admin_id = '$admin_id'";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows > 0) {
        $query = "UPDATE admin_ratings SET rating = '$admin_rating', rated_at = NOW() 
                  WHERE project_id = '$project_id' AND admin_id = '$admin_id'";
    } else {
        $query = "INSERT INTO admin_ratings (project_id, admin_id, rating, rated_at) 
                  VALUES ('$project_id', '$admin_id', '$admin_rating', NOW())";
    }
    
    if ($conn->query($query)) {
        // Update the project's admin marks
        $update_query = "UPDATE projects SET 
                        review1_marks = '$review1',
                        review2_marks = '$review2',
                        internal_marks = '$internal',
                        admin_comments = '$comments',
                        admin_rating = '$admin_rating'
                        WHERE id = '$project_id'";
        
        if ($conn->query($update_query)) {
            // Recalculate combined rating
            $recalc_query = "UPDATE projects p
                            SET rating = 
                                (SELECT IFNULL(AVG(rating), 0) FROM admin_ratings WHERE project_id = p.id) * 0.4 +
                                (SELECT IFNULL(AVG(rating), 0) FROM faculty_ratings WHERE project_id = p.id) * 0.3 +
                                (SELECT IFNULL(AVG(rating), 0) FROM external_ratings WHERE project_id = p.id) * 0.3
                            WHERE id = '$project_id'";
            $conn->query($recalc_query);
            
            $_SESSION['success'] = "Marks submitted successfully!";
        } else {
            $_SESSION['error'] = "Error updating project: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Error submitting rating: " . $conn->error;
    }
    
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset Default Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
          
        /* Top Bar Styles */
        .top-bar {
            background-color: #2c3e50;
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
        
        /* Main Content - Dashboard */
        .site-main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            background-color: #f0f2f5;
        }
        
        .dashboard-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .welcome-message {
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
        }
        
        .welcome-message h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-top: 4px solid #3498db;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #3498db;
        }
        
        .dashboard-card h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.4rem;
        }
        
        .dashboard-card p {
            color: #7f8c8d;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .dashboard-card a {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
            width: 80%;
        }
        
        .dashboard-card a:hover {
            background: #2980b9;
            transform: scale(1.03);
        }
        
        .logout-container {
            text-align: center;
            margin-top: 40px;
        }
        
        .logout-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 1.1rem;
        }
        
        .logout-btn:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        
        /* Footer Styles */
        .site-footer {
            background-color: #2c3e50;
            color: white;
            padding: 25px 0;
            text-align: center;
            width: 100%;
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
        /* Marks Management Card Specific Styles */
.marks-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 80%;
    margin: 0 auto;
}

.marks-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 15px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s;
    font-weight: 500;
    text-align: center;
}

.marks-btn:hover {
    background: #2980b9;
    transform: scale(1.03);
}

.marks-btn i {
    margin-right: 8px;
}
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .menu {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            .menu li {
                margin-left: 0;
            }
            .inside-header {
                flex-direction: column;
                gap: 15px;
            }
            .main-navigation {
                text-align: center;
            }
            .welcome-message h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <nav>
            <ul class="top-menu">
                <li><a href="https://mvgrce.com/">About MVGR</a></li>
                <li><a href="#" id="loginLink">Admin Dashboard</a></li>
                <li><a href="https://iic.mvgrglug.com/sih-ideas-presentations/">SIH Ideas & Presentations</a></li>
                <li><a href="https://iic.mvgrglug.com/no-title/">Press & Media</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Header -->
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

    <!-- Main Content - Dashboard -->
    <main class="site-main">
        <div class="dashboard-container">
            <div class="welcome-message">
                <h1>Welcome, <?php echo $_SESSION['username']; ?> <i class="fas fa-crown"></i></h1>
                <p>Administrator Dashboard - Manage all system functions</p>
            </div>
            
            <div class="dashboard-cards">
            <div class="dashboard-card">
                    <i class="fas fa-project-diagram"></i>
                    <h3>Manage Projects</h3>
                    <p>View, edit, and manage all innovation projects and initiatives</p>
                    <a href="manage_projects.php">Access Panel</a>
                </div>
                <div class="dashboard-card">
                    <i class="fas fa-file-alt"></i>
                    <h3>About Reviews</h3>
                    <p>Generate new review and documentation for institutional records</p>
                    <a href="create_review.php">Access Panel</a>
                </div>
                
                <div class="dashboard-card">
    <i class="fas fa-clipboard-check"></i>
    <h3>Marks Management</h3>
    <p>Submit and view review marks for student projects</p>
    <div class="marks-actions">
        <a href="#" data-bs-toggle="modal" data-bs-target="#marksModal" class="marks-btn">
            <i class="fas fa-edit"></i> Enter Marks
        </a>
        <a href="view_marks.php" class="marks-btn">
            <i class="fas fa-eye"></i> View Marks
        </a>
    </div>
</div>
                
                
                
                <div class="dashboard-card">
                    <i class="fas fa-chart-bar"></i>
                    <h3>View Reports</h3>
                    <p>Analyze system reports, statistics, and performance metrics</p>
                    <a href="view_reports.php">Access Panel</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-users-cog"></i>
                    <h3>Manage Users</h3>
                    <p>User accounts, permissions, and system access management</p>
                    <a href="manage_users.php">Access Panel</a>
                </div>
            </div>
            
            <div class="logout-container">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </main>
    
    <!-- Marks Submission Modal -->
    <div class="modal fade" id="marksModal" tabindex="-1" aria-labelledby="marksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="marksModalLabel">Enter Project Marks</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="admin.php" method="POST">
                    <div class="form-group project-select">
                        <label for="projectSelect">Select Project</label>
                        <select class="form-control" id="projectSelect" name="project" required onchange="updateCurrentRating(this)">
                            <option value="">-- Select Project --</option>
                            <?php 
                            $projects = $conn->query("SELECT id, theme FROM projects");
                            while ($project = $projects->fetch_assoc()): 
                                $current_rating = isset($project_ratings[$project['id']]) ? $project_ratings[$project['id']]['admin_rating'] : 0;
                            ?>
                                <option value="<?= $project['id'] ?>" data-rating="<?= $current_rating ?>">
                                    <?= htmlspecialchars($project['theme']) ?>
                                    <?php if ($current_rating): ?>
                                        (Current Rating: <?= $current_rating ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="review1">Review 1 Marks</label>
                                <input type="number" class="form-control" id="review1" name="review1" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="review2">Review 2 Marks</label>
                                <input type="number" class="form-control" id="review2" name="review2" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="internal">Internal Marks</label>
                                <input type="number" class="form-control" id="internal" name="internal" min="0" max="100" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <select class="form-control" id="rating" name="rating" required>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Fair</option>
                            <option value="3">3 - Good</option>
                            <option value="4">4 - Very Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Comments</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Marks</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    
    <!-- Footer -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function updateCurrentRating(select) {
    const selectedOption = select.options[select.selectedIndex];
    const currentRating = selectedOption.getAttribute('data-rating');
    const ratingSelect = document.getElementById('rating');
    
    if (currentRating) {
        ratingSelect.value = currentRating;
    } else {
        ratingSelect.value = '3'; // Default value if no rating exists
    }
}
</script>
</body>
</html>