<?php
require '../vendor/autoload.php';
include 'config.php';
include 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Determine if this is a review email or batch email
$isReviewEmail = isset($_GET['review_id']);
$isBatchEmail = isset($_POST['selected_batches']);

try {
    $mail = new PHPMailer(true);
    $mailConfig = include 'mail_config.php';

    // Common SMTP settings
    $mail->isSMTP();
    $mail->Host       = $mailConfig['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailConfig['username'];
    $mail->Password   = $mailConfig['password'];
    $mail->SMTPSecure = $mailConfig['encryption'];
    $mail->Port       = $mailConfig['port'];

    // Common sender settings
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addReplyTo($mailConfig['reply_to']);
    $mail->isHTML(false); // Set to plain text only

    if ($isReviewEmail) {
        /*********************** REVIEW EMAIL FUNCTIONALITY ***********************/
        $reviewId = filter_input(INPUT_GET, 'review_id', FILTER_VALIDATE_INT);
        // Fetch review and its associated batches
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $review = $stmt->get_result()->fetch_assoc();

        if (!$review) {
            throw new Exception("Review not found");
        }

        // Get batches associated with this review
        $batchIds = !empty($review['batch_ids']) ? explode(',', $review['batch_ids']) : [];
        
        if (!empty($batchIds)) {
            // Send to specific batches
            $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
            $stmt = $conn->prepare("SELECT email, name FROM students WHERE batch_id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($batchIds)), ...$batchIds);
            $stmt->execute();
            $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            // Send to all students if no batches specified
            $recipients = $conn->query("SELECT email, name FROM students")->fetch_all(MYSQLI_ASSOC);
        }

        // Add recipients
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient['email'], $recipient['name']);
        }

        // Build plain text email with all details
        $mail->Subject = "Review: " . $review['review_theme'];
        
        $emailBody = "REVIEW NOTIFICATION\n";
        $emailBody .= "==================\n\n";
        $emailBody .= "Date: " . $review['review_date'] . "\n";
        $emailBody .= "Theme: " . $review['review_theme'] . "\n\n";
        $emailBody .= "Content:\n";
        $emailBody .= str_repeat('-', 50) . "\n";
        $emailBody .= $review['review_content'] . "\n\n";
        
        // Add file attachment if available
        if (!empty($review['file_name'])) {
            $filePath = 'uploads/' . $review['file_name'];
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath, $review['file_name']);
                $emailBody .= "Attached File: " . $review['file_name'] . "\n";
            } else {
                $emailBody .= "Note: File attachment not found on server.\n";
            }
        }
        
        $emailBody .= "This is an automated message from MVGR IIC.";
        
        $mail->Body = $emailBody;
        
        $successMessage = 'Review sent to ' . count($recipients) . ' recipients';

    } else if ($isBatchEmail) {
        /*********************** BATCH EMAIL FUNCTIONALITY ***********************/
        $selectedBatches = $_POST['selected_batches'];
        $placeholders = implode(',', array_fill(0, count($selectedBatches), '?'));
        
        // Get batch names
        $stmt = $conn->prepare("SELECT batch_name FROM batches WHERE batch_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($selectedBatches)), ...$selectedBatches);
        $stmt->execute();
        $batchNames = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'batch_name');

        // Get recipients
        $stmt = $conn->prepare("SELECT email, name FROM students WHERE batch_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($selectedBatches)), ...$selectedBatches);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($recipients)) {
            throw new Exception("No recipients found in selected batches");
        }

        // Add recipients (BCC)
        foreach ($recipients as $recipient) {
            $mail->addBCC($recipient['email'], $recipient['name']);
        }

        // Plain text email content
        $mail->Subject = "MVGR IIC: Notification";
        $mail->Body = "Notification for batches: " . implode(', ', $batchNames) . "\n\n" .
                     "Total recipients: " . count($recipients) . "\n\n" .
                     "This is an automated message from MVGR IIC.";
        
        $successMessage = 'Batch email sent to ' . count($recipients) . ' recipients';
    }

    $mail->send();
    $_SESSION['alert'] = ['type' => 'success', 'message' => $successMessage];

} catch (Exception $e) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Failed to send email: ' . $e->getMessage()
    ];
    error_log("Email Error: " . $e->getMessage());
}

header('Location: ' . ($isBatchEmail ? 'batch_selector.php' : 'view_reviews.php'));
exit();