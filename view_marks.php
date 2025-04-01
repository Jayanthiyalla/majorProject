<?php
session_start();

// Allow access for admin, faculty, and students
$allowed_roles = ['admin', 'faculty', 'student'];
if (!isset($_SESSION["username"]) || !in_array($_SESSION["role"], $allowed_roles)) {
    header("Location: index.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mvgr_iic_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT project_name, review1_marks, review2_marks, internal_marks, 
               (review1_marks + review2_marks + internal_marks) as total_marks,
               comments, submission_date 
        FROM project_marks 
        ORDER BY total_marks DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project Marks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* [Previous CSS styles remain the same] */
        
        .view-only {
            background-color: #f8f9fa;
        }
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
    <div class="marks-container">
        <?php if ($_SESSION["role"] === "admin"): ?>
            <a href="admin.php" class="btn btn-primary back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        <?php elseif ($_SESSION["role"] === "faculty"): ?>
            <a href="faculty.php" class="btn btn-primary back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        <?php else: ?>
            <a href="student.php" class="btn btn-primary back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        <?php endif; ?>
        
        <h1><i class="fas fa-clipboard-check"></i> Project Evaluation Marks</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered <?= ($_SESSION["role"] !== "admin") ? 'view-only' : '' ?>">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Project Name</th>
                            <th>Review 1</th>
                            <th>Review 2</th>
                            <th>Internal</th>
                            <th>Total</th>
                            <th>Comments</th>
                            <th>Last Updated</th>
                            <?php if ($_SESSION["role"] === "admin"): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while($row = $result->fetch_assoc()): 
                            $isTop3 = $rank <= 3;
                        ?>
                        <tr class="<?= $isTop3 ? 'top-3' : '' ?>">
                            <td>
                                <?php if ($isTop3): ?>
                                    <span class="badge bg-<?= $rank == 1 ? 'danger' : ($rank == 2 ? 'warning' : 'info') ?>">
                                        <?= $rank ?>
                                    </span>
                                <?php else: ?>
                                    <?= $rank ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                            <td><?= $row['review1_marks'] ?></td>
                            <td><?= $row['review2_marks'] ?></td>
                            <td><?= $row['internal_marks'] ?></td>
                            <td class="total-col"><?= $row['total_marks'] ?></td>
                            <td class="comments-col" title="<?= htmlspecialchars($row['comments']) ?>">
                                <?= htmlspecialchars($row['comments']) ?>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($row['submission_date'])) ?></td>
                            <?php if ($_SESSION["role"] === "admin"): ?>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_marks.php?project=<?= urlencode($row['project_name']) ?>" 
                                           class="btn btn-sm btn-warning" title="Edit Marks">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                        $rank++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-marks">
                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                <h3>No Marks Submitted Yet</h3>
                <p>No project marks have been entered yet. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
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
</html>