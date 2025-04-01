<?php
session_start();
include 'config.php';

// Ensure only the admin can access this page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

// Add new user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_user"])) {
    $username = trim($_POST["username"]);
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
    $role = $_POST["role"];

    if (!empty($username) && !empty($password) && !empty($role)) {
        $stmt = $conn->prepare("INSERT INTO credentials (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) {
            echo "<script>alert('User added successfully!'); window.location.href='manage_users.php';</script>";
        } else {
            echo "<script>alert('Error adding user! Try again.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('All fields are required!');</script>";
    }
}

// Fetch users excluding admin
$result = $conn->query("SELECT username, role FROM credentials WHERE role != 'admin'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - MVGR IIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Header and Footer Styles from Index */
        .top-bar {
            background-color: #2c3e50;
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
        .inside-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #ffffff;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        .site-footer {
            background-color: #2c3e50;
            color: white;
            padding: 25px 0;
            text-align: center;
            width: 100%;
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

        /* Manage Users Specific Styles */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .users-container {
            flex: 1;
            padding: 40px 20px;
            background-color: #f0f2f5;
        }
        .users-card {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
        }
        .users-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .users-header h2 {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #3a5bbf;
            border-color: #3a5bbf;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        .btn-secondary:hover {
            background-color: #6b6d7d;
            border-color: #6b6d7d;
            transform: translateY(-2px);
        }
        .btn-warning {
            background-color: #f6c23e;
            border-color: #f6c23e;
            color: #fff;
        }
        .btn-warning:hover {
            background-color: #dda20a;
            border-color: #dda20a;
            transform: translateY(-2px);
        }
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #4e73df;
            color: white;
            font-weight: 600;
        }
        .table td {
            vertical-align: middle;
        }
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        @media (max-width: 768px) {
            .action-btns {
                flex-direction: column;
            }
            .btn {
                width: 100%;
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
                <li><a href="#" id="loginLink">Admin Dashboard</a></li>
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
    <main class="users-container">
        <div class="users-card">
            <div class="users-header">
                <h2><i class="fas fa-users-cog me-2"></i>Manage Users</h2>
                <p>Add, edit, or remove system users</p>
            </div>

            <!-- Back to Dashboard Button -->
            <a href="admin.php" class="btn btn-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>

            <!-- Add User Form -->
            <div class="card mb-4 p-4">
                <h4 class="mb-3"><i class="fas fa-user-plus me-2"></i>Add New User</h4>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Username:</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Password:</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role:</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <option value="faculty">Faculty</option>
                                <option value="student">Student</option>
                                <option value="external">External</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add User
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card p-4">
                <h4 class="mb-3"><i class="fas fa-users me-2"></i>Existing Users</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                    <span class="badge bg-<?php 
    echo $row['role'] === 'faculty' ? 'info' : 
        ($row['role'] === 'student' ? 'success' : 'purple'); 
?>">
    <?php echo htmlspecialchars(ucfirst($row['role'])); ?>
</span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class='btn btn-warning btn-sm edit-btn' 
                                                    data-username='<?php echo $row['username']; ?>' 
                                                    data-role='<?php echo $row['role']; ?>'>
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <a href='delete_user.php?username=<?php echo $row['username']; ?>' 
                                               class='btn btn-danger btn-sm'
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll(".edit-btn").forEach(button => {
            button.addEventListener("click", function() {
                let username = this.getAttribute("data-username");
                let role = this.getAttribute("data-role");

                let newRole = prompt(`Edit role for ${username} (faculty/student/external):`, role);
                if (newRole && (newRole === "faculty" || newRole === "student" || newRole === "external")) {
                    window.location.href = `edit_user.php?username=${username}&role=${newRole}`;
                }
            });
        });
    </script>
</body>
</html>