<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Empty by default in XAMPP
$dbname = "mvgr_iic_db"; // Or whatever you named your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch marks data
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
    <title>Project Marks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .marks-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        .table {
            margin-top: 20px;
        }
        .table th {
            background-color: #2c3e50;
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        .total-col {
            font-weight: bold;
            color: #2c3e50;
        }
        .top-3 {
            background-color: rgba(46, 204, 113, 0.1);
        }
        .badge {
            font-size: 0.8em;
            padding: 5px 8px;
        }
        .comments-col {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="marks-container">
        <h1>Project Evaluation Marks</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
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
                            <td><?= date('d M Y', strtotime($row['submission_date'])) ?></td>
                        </tr>
                        <?php 
                        $rank++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No marks have been submitted yet. Please check back later.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>