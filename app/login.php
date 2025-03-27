<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST["username"]);
    $pass = trim($_POST["password"]);

    if (!empty($user) && !empty($pass)) {
        $stmt = $conn->prepare("SELECT username, password, role FROM credentials WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            if (password_verify($pass, $row["password"])) {
                // Store session variables
                $_SESSION["username"] = $row["username"];
                $_SESSION["role"] = $row["role"];

                // Redirect based on role
                if ($row["role"] == "admin") {
                    header("Location: admin.php");
                } elseif ($row["role"] == "faculty") {
                    header("Location: faculty_dashboard.php");
                } elseif ($row["role"] == "student") {
                    header("Location: student_dashboard.php");
                } else {
                    echo "<script>alert('Unknown role detected!'); window.location.href='index.php';</script>";
                }
                exit();
            } else {
                echo "<script>alert('Invalid password!'); window.location.href='index.php';</script>";
            }
        } else {
            echo "<script>alert('Invalid username! Try again.'); window.location.href='index.php';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('All fields are required!'); window.location.href='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #36b9cc;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: var(--secondary-color);
            font-family: 'Nunito', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .card-header h3 {
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 2rem;
            background-color: white;
        }
        
        .form-control {
            height: 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding-left: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            background-color: white;
            border-right: none;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            height: 45px;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #3a5bbf;
        }
        
        .forgot-password {
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .forgot-password:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1rem;
            color: var(--dark-color);
        }
        
        .brand-logo {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-icon {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card card">
                <div class="card-header">
                    <div class="brand-logo">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Login to Your Account</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text input-icon"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text input-icon"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                            <a href="#" class="forgot-password float-end">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login w-100">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    <div class="login-footer mt-3">
                        <p>Don't have an account? <a href="#" class="text-primary">Contact Admin</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>