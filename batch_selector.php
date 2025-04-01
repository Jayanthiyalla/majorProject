<?php
// batch_selector.php (updated version)
require_once 'config.php';
session_start();

// Fetch batches with student counts
$batches = [];
$result = $conn->query("
    SELECT b.batch_id, b.batch_name, COUNT(s.student_id) as student_count
    FROM batches b
    LEFT JOIN students s ON b.batch_id = s.batch_id
    GROUP BY b.batch_id
    ORDER BY b.batch_id
");

if ($result) {
    $batches = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MVGR IIC - Batch Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .batch-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .batch-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .batch-item:last-child {
            border-bottom: none;
        }
        .batch-actions {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Select Batches</h2>
        
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?>">
                <?= htmlspecialchars($_SESSION['alert']['message']) ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>
        
        <form method="post" action="send_email.php">
            <div class="mb-3">
                <label class="form-label">Select Batches :</label>
                
                <!-- Batch selection controls -->
                <div class="batch-actions mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deselect All</button>
                </div>
                
                <!-- Batch list with checkboxes -->
                <div class="batch-container">
                    <?php foreach ($batches as $batch): ?>
                        <div class="form-check batch-item">
                            <input class="form-check-input" type="checkbox" 
                                   name="selected_batches[]" 
                                   value="<?= htmlspecialchars($batch['batch_id']) ?>" 
                                   id="batch-<?= htmlspecialchars($batch['batch_id']) ?>">
                            <label class="form-check-label" for="batch-<?= htmlspecialchars($batch['batch_id']) ?>">
                                <?= htmlspecialchars($batch['batch_name']) ?> 
                                (<?= $batch['student_count'] ?> students)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary mt-3">Send Emails</button>
        </form>
    </div>

    <script>
        // Select/Deselect All functionality
        document.getElementById('selectAll').addEventListener('click', function() {
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                checkbox.checked = true;
            });
        });
        
        document.getElementById('deselectAll').addEventListener('click', function() {
            document.querySelectorAll('.form-check-input').forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    </script>
</body>
</html>