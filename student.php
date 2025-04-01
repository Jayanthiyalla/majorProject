<?php
session_start();

// Check if user is logged in as student
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Get student's registration number from session
$regd_no = $_SESSION['username'];

// Fetch all projects (students can view all projects)
$projects_query = "SELECT * FROM projects ORDER BY created_at DESC";
$projects_result = $conn->query($projects_query);
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - MVGR IIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 10px 15px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .project-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            border-left: 4px solid #4e73df;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .rating-stars {
            color: #ffc107;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            background-color: #6c757d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        .empty-message {
            color: #6c757d;
            font-style: italic;
        }
        /* Project Details Styles */
        .detail-row { margin-bottom: 15px; }
        .detail-label { font-weight: bold; color: #495057; }
        .detail-value { color: #212529; }
        .student-card { border-left: 4px solid #4e73df; margin-bottom: 15px; }
        .guide-card { border-left: 4px solid #1cc88a; margin-bottom: 15px; }
        .file-section { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .file-preview { max-width: 100%; max-height: 500px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3">
            <div class="text-center mb-4">
                <div class="user-avatar">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
                <h5 class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></h5>
                <p class="text-muted">Student</p>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="student.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_marks.php">
                        <i class="fas fa-clipboard-check"></i> View Marks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content p-4 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <?php
            // Check if we're viewing a specific project (from project_details.php)
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $project_id = (int)$_GET['id'];
                
                // Fetch the specific project details
                $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
                $stmt->bind_param("i", $project_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $project = $result->fetch_assoc();
                
                if ($project): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Project Details</h3>
                            <a class="nav-link active" href="student.php">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">Theme:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['theme'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Domain:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['domain'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Status:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['status'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Academic Year:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['academic_year'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-row">
                                        <span class="detail-label">IIC Focus Area:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['iic_focus_area'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Potential Impact:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['potential_impact'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Rating:</span>
                                        <span class="detail-value rating-stars">
                                            <?= str_repeat('★', $project['rating'] ?? 0) . str_repeat('☆', 5 - ($project['rating'] ?? 0)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="detail-row">
                                        <span class="detail-label">Description:</span>
                                        <span class="detail-value"><?= htmlspecialchars($project['description'] ?? 'No description available') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Project Team Section -->
                            <?php if (!empty($project['regd_nos'])): ?>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5><i class="fas fa-users me-2"></i> Project Team</h5>
                                    
                                    <div class="row mt-3">
                                        <?php
                                        // Split the comma-separated student data into arrays
                                        $regd_nos = explode(',', $project['regd_nos']);
                                        $student_names = explode(',', $project['student_names']);
                                        $departments = explode(',', $project['departments']);
                                        $guides = explode(',', $project['guides']);
                                        ?>
                                        
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user-tie me-2"></i> Supervisor</h6>
                                            <?php foreach ($guides as $guide): ?>
                                                <div class="card guide-card mb-2">
                                                    <div class="card-body py-2">
                                                        <?= htmlspecialchars(trim($guide)) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user-graduate me-2"></i> Students</h6>
                                            <?php for ($i = 0; $i < count($regd_nos); $i++): ?>
                                                <div class="card student-card mb-2">
                                                    <div class="card-body py-2">
                                                        <div><strong>Name:</strong> <?= htmlspecialchars(trim($student_names[$i] ?? 'N/A')) ?></div>
                                                        <div><strong>Regd No:</strong> <?= htmlspecialchars(trim($regd_nos[$i] ?? 'N/A')) ?></div>
                                                        <div><strong>Department:</strong> <?= htmlspecialchars(trim($departments[$i] ?? 'N/A')) ?></div>
                                                    </div>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- File Section -->
                            <?php if (!empty($project['file_path'])): ?>
                            <div class="file-section mt-4">
                                <h5><i class="fas fa-file-alt me-2"></i>Project File</h5>
                                <div class="d-flex align-items-center mt-2">
                                    <a href="uploads/<?= htmlspecialchars($project['file_path']) ?>" 
                                       class="btn btn-primary me-3" 
                                       download="<?= htmlspecialchars($project['file_path']) ?>">
                                        <i class="fas fa-download me-1"></i> Download File
                                    </a>
                                    <span><?= htmlspecialchars($project['file_path']) ?></span>
                                </div>
                                
                                <!-- File Preview -->
                                <div class="mt-3">
                                    <?php
                                    $file_ext = pathinfo($project['file_path'], PATHINFO_EXTENSION);
                                    $file_path = "uploads/" . $project['file_path'];
                                    
                                    if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <img src="<?= $file_path ?>" class="img-fluid file-preview" alt="Project Image">
                                    <?php elseif (strtolower($file_ext) === 'pdf'): ?>
                                        <iframe src="<?= $file_path ?>" class="file-preview w-100" style="height: 500px;" frameborder="0"></iframe>
                                    <?php else: ?>
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-file me-2"></i> File preview not available - Download to view
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                                <div class="alert alert-secondary mt-4">
                                    <i class="fas fa-info-circle me-2"></i> No file attached to this project
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Project not found.
                    </div>
                <?php endif;
            } else {
                // Show the regular dashboard with project summaries
                ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Projects</h5>
                                <p class="card-text display-4"><?= count($projects) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Active Projects</h5>
                                <p class="card-text display-4">
                                    <?= array_reduce($projects, function($carry, $project) {
                                        return $carry + (isset($project['status']) && $project['status'] === 'Active' ? 1 : 0);
                                    }, 0) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Top Rated</h5>
                                <p class="card-text display-4">
                                    <?= array_reduce($projects, function($carry, $project) {
                                        return max($carry, $project['rating'] ?? 0);
                                    }, 0) ?>/5
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3">All Projects</h4>
                
                <?php if (count($projects) > 0): ?>
                    <div class="row">
                        <?php foreach ($projects as $project): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card project-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title"><?= htmlspecialchars($project['theme'] ?? 'Untitled Project') ?></h5>
                                        <span class="badge bg-<?= 
    (isset($project['status']) && $project['status'] === 'Active') ? 'success' : 
    ((isset($project['status']) && $project['status'] === 'Completed') ? 'secondary' : 'warning')
?>">
    <?= htmlspecialchars($project['status'] ?? 'Unknown') ?>
</span>
                                            <?= htmlspecialchars($project['status'] ?? 'Unknown') ?>
                                        </span>
                                    </div>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?= htmlspecialchars($project['domain'] ?? 'No domain specified') ?>
                                    </h6>
                                    
                                    
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="rating-stars">
                                            <?= str_repeat('★', $project['rating'] ?? 0) . str_repeat('☆', 5 - ($project['rating'] ?? 0)) ?>
                                        </div>
                                        <a href="student.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No projects available at the moment.
                    </div>
                <?php endif;
            } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>