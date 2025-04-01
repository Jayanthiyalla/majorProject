<?php
include 'config.php';

$result = $conn->query("SELECT * FROM reviews");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reviews - MVGR IIC</title>
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
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
          
        /* Top Bar Styles */
        .top-bar {
            background-color: gray;
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
        
        /* Main Content */
        .site-main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 40px 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            flex-grow: 1;
        }
        
        /* Reviews Table Styles */
        .reviews-container {
            overflow-x: auto;
        }
        .reviews-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .reviews-table th {
            background-color: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .reviews-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }
        .reviews-table tr:hover {
            background-color: #f5f5f5;
        }
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .edit-btn {
            background-color: #3498db;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
        .delete-btn {
            background-color: #e74c3c;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
        .email-btn {
            background-color: #2ecc71;
        }
        .email-btn:hover {
            background-color: #27ae60;
        }
        .download-btn {
            background-color: #9b59b6;
        }
        .download-btn:hover {
            background-color: #8e44ad;
        }
        .create-new-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .create-new-btn:hover {
            background-color: #3498db;
            color: white;
        }
        .no-file {
            color: #7f8c8d;
            font-style: italic;
        }
        
        /* Footer Styles */
        .site-footer {
            background-color: grey;
            color: black;
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
            .reviews-table {
                display: block;
                overflow-x: auto;
            }
            .action-btn {
                display: block;
                margin: 5px 0;
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
                <li><a href="admin.php">Admin Dashboard</a></li>
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

    <!-- Main Content -->
    <main class="site-main">
        <div class="reviews-container">
            <h2>Saved Reviews</h2>
            <a href="create_review.php" class="create-new-btn">
                <i class="fas fa-plus"></i> Create New Review
            </a>
            <a href="admin.php" class="back-btn" style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; transition: all 0.3s;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Theme</th>
                        <th>Content</th>
                        <th>File</th>
                        <th>Batches</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['review_date']) ?></td>
                        <td><?= htmlspecialchars($row['review_theme']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['review_content'])) ?></td>
                        <td>
                        
                        <?php if ($row['file_name']): ?>
            <a href="uploads/<?= htmlspecialchars($row['file_name']) ?>" download class="action-btn download-btn">
                <i class="fas fa-download"></i> Download
            </a>
        <?php else: ?>
            <span class="no-file">No File</span>
        <?php endif; ?>
                            <?php 
        if (!empty($row['batch_ids'])) {
            $batchIds = explode(',', $row['batch_ids']);
            $batchNames = [];
            foreach ($batchIds as $batchId) {
                $batchStmt = $conn->prepare("SELECT batch_name FROM batches WHERE batch_id = ?");
                $batchStmt->bind_param("i", $batchId);
                $batchStmt->execute();
                $batchResult = $batchStmt->get_result();
                if ($batchRow = $batchResult->fetch_assoc()) {
                    $batchNames[] = htmlspecialchars($batchRow['batch_name']);
                }
            }
            echo implode(', ', $batchNames);
        } else {
            echo '<span class="no-file">All Batches</span>';
        }
        ?>
                        </td>
                        <td>
                            <a href="edit_review.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_review.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this review?')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                            
<a href="send_email.php?review_id=<?= $row['id'] ?>" class="action-btn email-btn">
                                 <i class="fas fa-envelope"></i> Email
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
    
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
</body>
</html>