<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $role = trim($_POST["role"]);
    
    // Validate inputs
    if (empty($username) || empty($role)) {
        echo "<script>alert('Username and role are required!'); window.location.href='../index.html';</script>";
        exit();
    }
    
    try {
        // First check if email column exists
        $check_column = $conn->query("SHOW COLUMNS FROM credentials LIKE 'email'");
        $email_column_exists = ($check_column->num_rows > 0);
        
        if ($email_column_exists) {
            // Check if the username exists in the database
            $stmt = $conn->prepare("SELECT username FROM credentials WHERE username = ? AND role = ?");
            $stmt->bind_param("ss", $username, $role);
        } else {
            // Fallback if email column doesn't exist
            $stmt = $conn->prepare("SELECT username FROM credentials WHERE username = ? AND role = ?");
            $stmt->bind_param("ss", $username, $role);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // Generate a new random password
            $new_password = bin2hex(random_bytes(8)); // 16 character random password
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $update_stmt = $conn->prepare("UPDATE credentials SET password = ? WHERE username = ?");
            $update_stmt->bind_param("ss", $hashed_password, $username);
            $update_stmt->execute();
            
            // Get email if column exists
            $email = '';
            if ($email_column_exists) {
                $email_stmt = $conn->prepare("SELECT email FROM credentials WHERE username = ?");
                $email_stmt->bind_param("s", $username);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                if ($email_result->num_rows > 0) {
                    $email_row = $email_result->fetch_assoc();
                    $email = $email_row['email'];
                }
            }
            
            if ($email_column_exists && !empty($email)) {
                // Send email with new password
                $to = $email;
                $subject = "Password Reset Request";
                $message = "Hello $username,\n\nYour password has been reset.\n\nNew Password: $new_password\n\nPlease login and change your password immediately.\n\nRegards,\nAdmin Team";
                $headers = "From: no-reply@iic.mvgrglug.com";
                
                if (mail($to, $subject, $message, $headers)) {
                    echo "<script>alert('New password sent to your email!'); window.location.href='../index.html';</script>";
                } else {
                    echo "<script>alert('Password was reset but email failed to send. Your new password is: $new_password'); window.location.href='../index.html';</script>";
                }
            } else {
                echo "<script>alert('Password was reset. Your new password is: $new_password'); window.location.href='../index.html';</script>";
            }
        } else {
            echo "<script>alert('Invalid username for the selected role!'); window.location.href='../index.html';</script>";
        }
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo "<script>alert('An error occurred. Please contact administrator.'); window.location.href='../index.html';</script>";
        error_log("Password reset error: " . $e->getMessage());
    }
    exit();
}
?>