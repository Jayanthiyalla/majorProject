<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

include 'config.php';

// Check if the project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Project ID is missing.");
}

$project_id = (int)$_GET['id'];

// Fetch project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

// Split the comma-separated student data into arrays
$regd_nos = explode(',', $project['regd_nos']);
$student_names = explode(',', $project['student_names']);
$departments = explode(',', $project['departments']);
$guides = explode(',', $project['guides']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container { margin-top: 30px; max-width: 900px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .back-btn { margin-top: 20px; }
        .file-section { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .file-preview { max-width: 100%; max-height: 500px; margin-top: 15px; }
        .detail-row { margin-bottom: 15px; }
        .detail-label { font-weight: bold; color: #495057; }
        .detail-value { color: #212529; }
        .student-card { border-left: 4px solid #4e73df; margin-bottom: 15px; }
        .guide-card { border-left: 4px solid #1cc88a; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Project Details</h3>
                <a href="view_reports.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Reports
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
                            <span class="detail-label">Current Status:</span>
                            <span class="detail-value"><?= htmlspecialchars($project['current_status'] ?? 'N/A') ?></span>
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
                            <span class="detail-label">Relevant SDGs:</span>
                            <span class="detail-value"><?= htmlspecialchars($project['relevant_sdgs'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Aligned Indian National Schemes:</span>
                            <span class="detail-value"><?= htmlspecialchars($project['aligned_schemes'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Washington Accord POs:</span>
                            <span class="detail-value"><?= htmlspecialchars($project['washington_acord_pos'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5><i class="fas fa-users me-2"></i> Project Team</h5>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-user-tie me-2"></i> Supervisor</h6>
                                <?php foreach ($guides as $guide): ?>
                                            <?= htmlspecialchars(trim($guide)) ?>   
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6><i class="fas fa-user-graduate me-2"></i> Students</h6>
                                <?php for ($i = 0; $i < count($regd_nos); $i++): ?>
                                    <div class="card student-card mb-2">
                                        <div class="card-body py-2">
                                            <div><strong>Name:</strong> <?= htmlspecialchars(trim($student_names[$i])) ?></div>
                                            <div><strong>Regd No:</strong> <?= htmlspecialchars(trim($regd_nos[$i])) ?></div>
                                            <div><strong>Department:</strong> <?= htmlspecialchars(trim($departments[$i])) ?></div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="detail-row">
                            <span class="detail-label">Potential Impact:</span>
                            
                            <span class="detail-value"><?= htmlspecialchars($project['theme'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>

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
                        <?php elseif (in_array(strtolower($file_ext), ['doc', 'docx'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-file-word me-2"></i> Word document - Download to view
                            </div>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>