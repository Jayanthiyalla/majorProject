<?php
include 'config.php';

// Handle POST request first
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    try {
        // Validate inputs
        $reviewDate = filter_input(INPUT_POST, 'reviewDate', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$reviewTheme = filter_input(INPUT_POST, 'reviewTheme', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$reviewContent = filter_input(INPUT_POST, 'reviewContent', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$fileName = "";

// Additional validation
if (empty($reviewDate) || empty($reviewTheme) || empty($reviewContent)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}
        

        // Handle file upload
        if (!empty($_FILES["fileUpload"]["name"])) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES["fileUpload"]["name"], PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . basename($_FILES["fileUpload"]["name"]);
            $targetFilePath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $targetFilePath)) {
                throw new Exception("File upload failed");
            }
        }

        // Process batch selections
        $batchIds = isset($_POST['batch_ids']) ? implode(',', $_POST['batch_ids']) : '';

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO reviews (review_date, review_theme, review_content, file_name, batch_ids) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $reviewDate, $reviewTheme, $reviewContent, $fileName, $batchIds);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Review saved!', 
                'id' => $conn->insert_id
            ]);
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Review - MVGR IIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        
        /* Review Form Styles */
        .review-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .review-form-container h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        #reviewForm {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }
        #reviewForm label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #2c3e50;
        }
        #reviewForm input[type="text"],
        #reviewForm input[type="date"],
        #reviewForm input[type="file"],
        #reviewForm textarea {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        #reviewForm input:focus,
        #reviewForm textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
            outline: none;
        }
        #reviewForm textarea {
            height: 150px;
            resize: vertical;
        }
        #reviewForm button {
            background: #2c3e50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }
        #reviewForm button:hover {
            background: #3498db;
        }
        #reviewForm a {
            display: inline-block;
            margin-left: 15px;
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s;
        }
        #reviewForm a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 15px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #5a6268;
            color: white;
        }
        .back-btn i {
            margin-right: 5px;
        }
        
        /* Footer Styles */
        .site-footer {
            background:rgb(53, 50, 50);
            color: black;
            padding: 25px 0;
            text-align: center;
            width: 100%;
            margin-top: auto;
        }
        
        .footer-bar a {
            color:rgb(239, 240, 240);
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
            .review-form-container {
                padding: 15px;
            }
            #reviewForm {
                padding: 20px;
            }
        
        }
        .form-select {
    width: 100%;
    padding: 12px 15px;
    margin: 8px 0 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s;
}
.form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
    outline: none;
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
        <div class="review-form-container">
            <a href="admin.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back 
            </a>
            <h2>Create New Review</h2>
            <form id="reviewForm" method="POST" enctype="multipart/form-data">
                <label>Review Date:</label>
                <input type="date" name="reviewDate" required>
                
                <label>Review Theme:</label>
                <input type="text" name="reviewTheme" required>
                
                <label>Review Content:</label>
                <textarea name="reviewContent" required></textarea>
                
                <label>Upload File :</label>
                <input type="file" name="fileUpload" accept=".ppt,.pptx,.pdf,.doc,.docx,.zip">
                
<!-- Batch Selection Section -->
<div class="mb-4 batch-selection-wrapper">
    <label class="form-label">Select Batches (leave empty for all students):</label>
    
    <!-- Batch selection controls -->
    <div class="batch-actions mb-3 d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBatches">
            <i class="fas fa-check-square me-1"></i> Select All
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBatches">
            <i class="fas fa-square me-1"></i> Deselect All
        </button>
    </div>
    
    <!-- Batch list with checkboxes in a scrollable container -->
    <div class="batch-container border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
            <?php 
            $batches = $conn->query("SELECT batch_id, batch_name FROM batches ORDER BY batch_name");
            while ($batch = $batches->fetch_assoc()): ?>
                <div class="col">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="batch_ids[]" 
                               value="<?= $batch['batch_id'] ?>" 
                               id="batch-<?= $batch['batch_id'] ?>">
                        <label class="form-check-label w-100 d-flex align-items-center" for="batch-<?= $batch['batch_id'] ?>">
                            <span class="flex-grow-1"><?= htmlspecialchars($batch['batch_name']) ?></span>
                            <span class="badge bg-primary rounded-pill ms-2">
                                <i class="fas fa-users"></i>
                            </span>
                        </label>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
                
                
                <button type="submit">Save Review</button>
                <a href="view_reviews.php">View Reviews</a>
            </form>
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
    <script>
        $(document).ready(function() {
    $("#reviewForm").on("submit", function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: "create_review.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json', // Expect JSON response
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    window.location.href = "view_reviews.php";
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
                alert("Request failed. Check console for details.");
            }
        });
    });
});
$(document).ready(function() {
    // Select all batches
    $('#selectAllBatches').click(function() {
        $('.batch-container input[type="checkbox"]').prop('checked', true);
    });
    
    // Deselect all batches
    $('#deselectAllBatches').click(function() {
        $('.batch-container input[type="checkbox"]').prop('checked', false);
    });
    
    // [Keep the rest of your existing JavaScript code]
});

    </script>
</body>
</html>