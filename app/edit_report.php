<?php
// 1. Start session and include config
session_start();
include 'config.php';

// 2. Check if ID exists and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid review ID";
    header("Location: admin.php");
    exit();
}

// 3. Sanitize the ID
$id = (int)$_GET['id'];

// 4. Fetch the review from database
$review = null;
try {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: admin.php");
    exit();
}

// 5. If review not found, redirect with error
if (!$review) {
    $_SESSION['error'] = "Review with ID $id not found";
    header("Location: admin.php");
    exit();
}

// 6. Handle form submission for updating review
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reviewDate = $_POST["reviewDate"];
    $reviewTheme = $_POST["reviewTheme"];
    $reviewContent = $_POST["reviewContent"];
    
    // File upload handling would go here
    
    $stmt = $conn->prepare("UPDATE reviews SET review_date=?, review_theme=?, review_content=? WHERE id=?");
    $stmt->bind_param("sssi", $reviewDate, $reviewTheme, $reviewContent, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Review updated successfully";
        header("Location: admin.php");
        exit();
    } else {
        $error = "Error updating review: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Edit Review</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Review Date</label>
                <input type="date" class="form-control" name="reviewDate" value="<?= htmlspecialchars($review['review_date']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Review Theme</label>
                <input type="text" class="form-control" name="reviewTheme" value="<?= htmlspecialchars($review['review_theme']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Review Content</label>
                <textarea class="form-control" name="reviewContent" rows="5" required><?= htmlspecialchars($review['review_content']) ?></textarea>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Update Review</button>
                <a href="admin.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>