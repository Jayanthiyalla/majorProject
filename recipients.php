<?php
include 'config.php';
include 'header.php'; // Your existing header file

// Add new recipient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipient'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    
    if ($email) {
        $stmt = $conn->prepare("INSERT INTO email_recipients (email, name) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = TRUE");
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Recipient added/updated'];
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid email'];
    }
}

// Toggle recipient status
if (isset($_GET['toggle'])) {
    $id = filter_input(INPUT_GET, 'toggle', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("UPDATE email_recipients SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Recipient status updated'];
    }
}

// Fetch all recipients
$recipients = $conn->query("SELECT * FROM email_recipients ORDER BY is_active DESC, email ASC");
?>

<div class="container mt-4">
    <h2>Manage Email Recipients</h2>
    
    <?php include 'alerts.php'; // Your existing alert display ?>
    
    <div class="card mb-4">
        <div class="card-header">Add New Recipient</div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="col-md-5">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="name" class="form-control" placeholder="Name (optional)">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_recipient" class="btn btn-primary btn-block">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($recipient = $recipients->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($recipient['email']) ?></td>
                <td><?= htmlspecialchars($recipient['name'] ?? '') ?></td>
                <td>
                    <span class="badge badge-<?= $recipient['is_active'] ? 'success' : 'secondary' ?>">
                        <?= $recipient['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td>
                    <a href="recipients.php?toggle=<?= $recipient['id'] ?>" class="btn btn-sm btn-<?= $recipient['is_active'] ? 'warning' : 'success' ?>">
                        <?= $recipient['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; // Your existing footer file ?>