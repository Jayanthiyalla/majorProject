<?php
include 'config.php';

// Fetch review to email
$id = $_GET['id'] ?? null;
$review = null;

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();
}

// Handle email sending
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $to = filter_input(INPUT_POST, 'recipient_email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'email_subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'email_message', FILTER_SANITIZE_STRING);
    $from = "no-reply@yourdomain.com";
    
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid recipient email address']));
    }

    // Prepare email headers
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Build HTML email content
    $email_content = "<html><body>";
    $email_content .= "<h2>Review Details</h2>";
    $email_content .= "<p><strong>Date:</strong> " . htmlspecialchars($review['review_date']) . "</p>";
    $email_content .= "<p><strong>Theme:</strong> " . htmlspecialchars($review['review_theme']) . "</p>";
    $email_content .= "<p><strong>Content:</strong><br>" . nl2br(htmlspecialchars($review['review_content'])) . "</p>";
    
    // Add file attachment if exists
    $attachment_path = null;
    if (!empty($review['file_name']) && file_exists("uploads/" . $review['file_name'])) {
        $attachment_path = "uploads/" . $review['file_name'];
    }
    
    // Send email with attachment if exists
    if ($attachment_path) {
        // Read the file content
        $file_content = file_get_contents($attachment_path);
        $file_content = chunk_split(base64_encode($file_content));
        
        // Generate a boundary string
        $boundary = md5(time());
        
        // Add attachment to headers
        $headers = "From: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        // Email body with attachment
        $email_body = "--$boundary\r\n";
        $email_body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $email_body .= $email_content . "\r\n\r\n";
        
        // Attachment
        $email_body .= "--$boundary\r\n";
        $email_body .= "Content-Type: application/octet-stream; name=\"" . basename($attachment_path) . "\"\r\n";
        $email_body .= "Content-Disposition: attachment; filename=\"" . basename($attachment_path) . "\"\r\n";
        $email_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $email_body .= $file_content . "\r\n\r\n";
        $email_body .= "--$boundary--";
        
        $message = $email_body;
    } else {
        $message = $email_content;
    }

    // Send email
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Review by Email</title>
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
        .file-info {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        .file-info a {
            margin-left: 10px;
        }
        #emailPreview {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Send Review by Email</h2>
        
        <?php if ($review): ?>
        <form id="emailForm" method="POST">
            <input type="hidden" name="id" value="<?= $review['id'] ?>">
            
            <div class="mb-3">
                <label class="form-label">Recipient Email</label>
                <input type="email" class="form-control" name="recipient_email" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Subject</label>
                <input type="text" class="form-control" name="email_subject" value="Performance Review: <?= htmlspecialchars($review['review_theme']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Message</label>
                <textarea class="form-control" name="email_message" rows="5" required>Dear Team,

Please find the performance review attached for your reference.

Best regards,
Management</textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Review Details</label>
                <div class="review-card">
                    <p><strong>Date:</strong> <?= htmlspecialchars($review['review_date']) ?></p>
                    <p><strong>Theme:</strong> <?= htmlspecialchars($review['review_theme']) ?></p>
                    <p><strong>Content:</strong> <?= nl2br(htmlspecialchars($review['review_content'])) ?></p>
                    <?php if (!empty($review['file_name'])): ?>
                    <p><strong>Attachment:</strong> 
                        <a href="uploads/<?= $review['file_name'] ?>" target="_blank" class="text-primary">
                            <i class="fas fa-file-alt me-1"></i><?= $review['file_name'] ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Preview</label>
                <div id="emailPreview"></div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send Email
                </button>
                <a href="admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </form>

        <div id="emailStatus" class="alert alert-success mt-3" style="display: none;"></div>
        <?php else: ?>
        <div class="alert alert-danger">
            Review not found. Please select a valid review to email.
        </div>
        <div class="text-center">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Update email preview when fields change
            function updatePreview() {
                const subject = $("input[name='email_subject']").val();
                const message = $("textarea[name='email_message']").val();
                
                const previewContent = `
                    <p><strong>Subject:</strong> ${subject}</p>
                    <hr>
                    <p>${message.replace(/\n/g, '<br>')}</p>
                    <hr>
                    <p><em>Review details will be included in the email</em></p>
                `;
                
                $("#emailPreview").html(previewContent);
            }
            
            // Initial preview update
            updatePreview();
            
            // Update preview on input changes
            $("input[name='email_subject'], textarea[name='email_message']").on('input', updatePreview);
            
            // Form submission handler
            $("#emailForm").on("submit", function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...').prop('disabled', true);
                
                $.ajax({
                    url: "",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            const statusAlert = $("#emailStatus");
                            
                            statusAlert.removeClass("alert-danger alert-success");
                            
                            if (data.status === 'success') {
                                statusAlert.addClass("alert-success").text(data.message).show();
                            } else {
                                statusAlert.addClass("alert-danger").text(data.message).show();
                            }
                            
                            $('html, body').animate({
                                scrollTop: statusAlert.offset().top - 100
                            }, 500);
                        } catch (e) {
                            alert("Error processing response");
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#emailStatus").removeClass("alert-success").addClass("alert-danger")
                            .text("Error: " + error).show();
                    },
                    complete: function() {
                        $('button[type="submit"]').html('<i class="fas fa-paper-plane me-2"></i>Send Email').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>