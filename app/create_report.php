<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $reviewDate = filter_input(INPUT_POST, 'reviewDate', FILTER_SANITIZE_STRING);
    $reviewTheme = filter_input(INPUT_POST, 'reviewTheme', FILTER_SANITIZE_STRING);
    $reviewContent = filter_input(INPUT_POST, 'reviewContent', FILTER_SANITIZE_STRING);
    $fileName = "";

    // Handle file upload
    if (!empty($_FILES["fileUpload"]["name"])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // File upload validation
       // $allowedTypes = ['ppt', 'pptx', 'pdf', 'doc', 'docx'];
        //$maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileExt = strtolower(pathinfo($_FILES["fileUpload"]["name"], PATHINFO_EXTENSION));
        
       /* if (!in_array($fileExt, $allowedTypes)) {
            die(json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PPT, PDF, DOC allowed.']));
        }
        
        if ($_FILES["fileUpload"]["size"] > $maxFileSize) {
            die(json_encode(['status' => 'error', 'message' => 'File too large. Max 5MB allowed.']));
        }
      */  
        $fileName = uniqid() . '_' . basename($_FILES["fileUpload"]["name"]);
        $targetFilePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $targetFilePath)) {
            die(json_encode(['status' => 'error', 'message' => 'File upload failed.']));
        }
    }

    // Insert data using prepared statement
    $stmt = $conn->prepare("INSERT INTO reviews (review_date, review_theme, review_content, file_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $reviewDate, $reviewTheme, $reviewContent, $fileName);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Review saved successfully!', 'id' => $conn->insert_id]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error saving review: ' . $conn->error]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: bold;
        }
        .btn {
            margin-right: 10px;
        }
        .review-card {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Create New Review</h2>
        <form id="reviewForm" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Review Date</label>
                <input type="date" class="form-control" name="reviewDate" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Review Theme</label>
                <input type="text" class="form-control" name="reviewTheme" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Review Content</label>
                <textarea class="form-control" name="reviewContent" rows="5" required></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Abstract/project file (Optional)</label>
                <input type="file" class="form-control" name="fileUpload" accept=".ppt,.pptx,.pdf,.doc,.docx">
                
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Review
                </button>
                <a href="edit_report.php?id=<?= $lastInsertedId ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Edit
                </a>
                <a href="send_email.php" class="btn btn-success">
                    <i class="fas fa-paper-plane me-2"></i>Email
                </a>
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </form>

        <div id="savedReview" class="review-card" style="display: none;">
            <h4><i class="fas fa-check-circle text-success me-2"></i>Review Saved</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date:</strong> <span id="savedDate"></span></p>
                    <p><strong>Theme:</strong> <span id="savedTheme"></span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>File:</strong> 
                        <a id="savedFile" href="#" target="_blank">
                            <i class="fas fa-file-alt me-2"></i><span>No file</span>
                        </a>
                    </p>
                </div>
            </div>
            <div class="mt-2">
                <p><strong>Content:</strong></p>
                <div id="savedContent" class="bg-white p-2 rounded"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#reviewForm").on("submit", function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                $('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);
                
                $.ajax({
                    url: "",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.status === 'success') {
                                $("#savedDate").text($("input[name='reviewDate']").val());
                                $("#savedTheme").text($("input[name='reviewTheme']").val());
                                $("#savedContent").text($("textarea[name='reviewContent']").val());
                                
                                if ($("input[name='fileUpload']")[0].files.length > 0) {
                                    var fileName = $("input[name='fileUpload']")[0].files[0].name;
                                    $("#savedFile span").text(fileName);
                                    $("#savedFile").attr("href", "uploads/" + fileName);
                                }
                                
                                $("#savedReview").show();
                                $("#reviewForm")[0].reset();
                            } else {
                                alert("Error: " + data.message);
                            }
                        } catch (e) {
                            alert("Error processing response");
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Error: " + error);
                    },
                    complete: function() {
                        $('button[type="submit"]').html('<i class="fas fa-save me-2"></i>Save Review').prop('disabled', false);
                    }
                });
            });
            
            $("input[name='fileUpload']").on("change", function() {
                if (this.files.length > 0) {
                    $(this).next("small").html("Selected: " + this.files[0].name);
                } else {
                    $(this).next("small").html("PPT, PDF, DOC files only (Max 5MB)");
                }
            });
        });
    </script>
</body>
</html>