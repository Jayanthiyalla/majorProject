<?php
session_start();
require 'config.php';

// Check if user is admin
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html");
    exit();
}

// Get project ID
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch project data
$project = [];
if ($project_id > 0) {
    $result = $conn->query("SELECT * FROM projects WHERE id = $project_id");
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        // Convert comma-separated values to arrays
        // Convert comma-separated values to arrays
$project['guides'] = !empty($project['guides']) ? explode(',', $project['guides']) : [];
$project['regd_nos'] = !empty($project['regd_nos']) ? explode(',', $project['regd_nos']) : [];
$project['student_names'] = !empty($project['student_names']) ? explode(',', $project['student_names']) : [];
$project['departments'] = !empty($project['departments']) ? explode(',', $project['departments']) : [];
$project['relevant_sdgs'] = !empty($project['relevant_sdgs']) ? explode(',', $project['relevant_sdgs']) : [];
    } else {
        $_SESSION['error'] = "Project not found";
        header("Location: manage_projects.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid project ID";
    header("Location: manage_projects.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_project'])) {
    // Sanitize inputs
    $domain = ucwords(strtolower(trim($conn->real_escape_string($_POST["domain"] ?? ''))));
    $theme = $conn->real_escape_string($_POST["theme"] ?? '');
    
    $guides = isset($_POST["guides"]) && is_array($_POST["guides"]) ? 
              implode(",", array_map([$conn, 'real_escape_string'], $_POST["guides"])) : '';
    
    $regd_nos = isset($_POST["regd_no"]) && is_array($_POST["regd_no"]) ? 
                implode(",", array_map([$conn, 'real_escape_string'], $_POST["regd_no"])) : '';
    
    $student_names = isset($_POST["name"]) && is_array($_POST["name"]) ? 
                     implode(",", array_map([$conn, 'real_escape_string'], $_POST["name"])) : '';
    
    $departments = isset($_POST["dept"]) && is_array($_POST["dept"]) ? 
                   implode(",", array_map([$conn, 'real_escape_string'], $_POST["dept"])) : '';
    
    $status = $conn->real_escape_string($_POST["current_status"] ?? '');
    $focus_area = $conn->real_escape_string($_POST["iic_focus_area"] ?? '');
    $impact = $conn->real_escape_string($_POST["potential_impact"] ?? '');
    $sdgs = $conn->real_escape_string($_POST["relevant_sdgs"] ?? '');
    $schemes = $conn->real_escape_string($_POST["aligned_schemes"] ?? '');
    $po = $conn->real_escape_string($_POST["washington_acord_pos"] ?? '');
    $academic_year = $conn->real_escape_string($_POST["academic_year"] ?? '');
    
    // File upload handling
    $filePath = $project['file_path'] ?? '';
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        // Delete old file if exists
        if (!empty($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        $filePath = uploadFile($_FILES["file"]);
    }

    // Update query
    $sql = "UPDATE projects SET 
            domain = ?, 
            theme = ?,
            guides = ?,
            regd_nos = ?,
            student_names = ?,
            departments = ?,
            current_status = ?,
            iic_focus_area = ?,
            potential_impact = ?,
            relevant_sdgs = ?,
            aligned_schemes = ?,
            washington_acord_pos = ?,
            academic_year = ?,
            file_path = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssi",  // 15 placeholders = 15 characters in type string
        $domain, 
        $theme, 
        $guides, 
        $regd_nos, 
        $student_names,  
        $departments, 
        $current_status, 
        $iic_focus_area,  
        $potential_impact, 
        $relevant_sdgs, 
        $aligned_schemes,  
        $washington_acord_pos, 
        $academic_year,  
        $file_path,   
        $project_id   // Ensure $project_id is passed last
    );

    
    


    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Project Updated Successfully!";
        header("Location: manage_projects.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating project: " . $stmt->error;
    }
}

// Function to upload file (same as in manage_projects.php)
function uploadFile($file) {
    $targetDir = "uploads/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    if (!in_array($fileType, $allowedTypes)) {
        die("Error: Only PDF, DOC, PPT files are allowed.");
    }
    
    if ($file["size"] > 50000000) {
        die("Error: File is too large. Max 5MB allowed.");
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        return $targetFilePath;
    } else {
        die("Error uploading file.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* Use the same styles as your manage_projects.php */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: none;
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        .form-section {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .student-entry, .guide-entry {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fc;
            border-radius: 5px;
        }
        .action-btns .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        /* SDG Badges */
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.75em;
            display: inline-flex;
            align-items: center;
        }
        .btn-close {
            font-size: 0.7rem;
            opacity: 1;
            filter: invert(1);
        }
        /* Year picker styles */
        .yearpicker .ui-datepicker-header {
            padding: 0;
        }
        .yearpicker .ui-datepicker-month {
            display: none;
        }
        .yearpicker .ui-datepicker-calendar {
            display: none;
        }
        .yearpicker .ui-datepicker-year {
            width: 100%;
            font-size: 14px;
            padding: 15px;
            border: none;
            outline: none;
        }
        .yearpicker .ui-datepicker-prev,
        .yearpicker .ui-datepicker-next {
            display: none;
        }
        /* AI-Specific Styles */
        .ai-badge {
            font-size: 0.7em;
            background: linear-gradient(135deg, #6e48aa 0%, #9d50bb 100%);
            color: white;
            padding: 0.2em 0.5em;
            border-radius: 10px;
            margin-left: 0.5em;
            vertical-align: middle;
        }
        .ai-output {
            background-color: #f8f9fa;
            border-right: none;
        }
        .ai-updated {
            animation: aiHighlight 2s ease-out;
            border-color: #6e48aa;
        }
        @keyframes aiHighlight {
            0% { box-shadow: 0 0 0 0 rgba(110, 72, 170, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(110, 72, 170, 0); }
            100% { box-shadow: 0 0 0 0 rgba(110, 72, 170, 0); }
        }
        .refreshPosBtn {
            border-left: none;
            transition: all 0.3s ease;
        }
        .refreshPosBtn:hover {
            background-color: #f1f1f1;
            color: #6e48aa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Edit Project</h2>
                <a href="manage_projects.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="form-section">
                    <input type="hidden" name="update_project" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Domain:</label>
                            <input type="text" name="domain" class="form-control" required 
                             value="<?= htmlspecialchars($project['domain'] ?? '') ?>">

                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Theme:</label>
                            <input type="text" name="theme" class="form-control" required 
                                   value="<?= htmlspecialchars($project['theme'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Guides Section -->
                    <div class="mb-3">
                        <label class="form-label">Supervisors:</label>
                        <div id="guides-list" class="mb-2">
                            <?php foreach ($project['guides'] as $index => $guide): ?>
                            <div class="guide-entry row g-2 mb-2">
                                <div class="col-md-11">
                                    <select name="guides[]" class="form-control" required>
                                        <option value="">Select Supervisors</option>
                                        <option value="Dr Praveen Kalla, Prof, Mech" <?= $guide == 'Dr Praveen Kalla, Prof, Mech' ? 'selected' : '' ?>>Dr Praveen Kalla, Prof, Mech</option>
                                        <option value="Dr K S Ravi Kumar, Prof, EEE" <?= $guide == 'Dr K S Ravi Kumar, Prof, EEE' ? 'selected' : '' ?>>Dr K S Ravi Kumar, Prof, EEE</option>
                                        <option value="Dr Raja Ramesh D, Assoc Prof, ECE" <?= $guide == 'Dr Raja Ramesh D, Assoc Prof, ECE' ? 'selected' : '' ?>>Dr Raja Ramesh D, Assoc Prof, ECE</option>
                                        <option value="Sri M Vamsi Krishna, Sr Asst Prof, CSE" <?= $guide == 'Sri M Vamsi Krishna, Sr Asst Prof, CSE' ? 'selected' : '' ?>>Sri M Vamsi Krishna, Sr Asst Prof, CSE</option>
                                        <option value="Smt M Swarna, Assoc Prof, IT" <?= $guide == 'Smt M Swarna, Assoc Prof, IT' ? 'selected' : '' ?>>Smt M Swarna, Assoc Prof, IT</option>
                                        <option value="Sri Ch Varun, Dist Asst Prof, Mech" <?= $guide == 'Sri Ch Varun, Dist Asst Prof, Mech' ? 'selected' : '' ?>>Sri Ch Varun, Dist Asst Prof, Mech</option>
                                        <option value="Dr GVSR Pavan Kumar, Assoc Prof, Chemistry" <?= $guide == 'Dr GVSR Pavan Kumar, Assoc Prof, Chemistry' ? 'selected' : '' ?>>Dr GVSR Pavan Kumar, Assoc Prof, Chemistry</option>
                                        <option value="Dr VSR Naga Santosi, Asst Prof, Phy" <?= $guide == 'Dr VSR Naga Santosi, Asst Prof, Phy' ? 'selected' : '' ?>>Dr VSR Naga Santosi, Asst Prof, Phy</option>
                                        <option value="Sri K Pavan Kumar, Dist Asst Prof, Mech" <?= $guide == 'Sri K Pavan Kumar, Dist Asst Prof, Mech' ? 'selected' : '' ?>>Sri K Pavan Kumar, Dist Asst Prof, Mech</option>
                                        <option value="Ms R Hema Latha, Sr Asst Prof, Chem" <?= $guide == 'Ms R Hema Latha, Sr Asst Prof, Chem' ? 'selected' : '' ?>>Ms R Hema Latha, Sr Asst Prof, Chem</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100 delete-guide-btn" <?= count($project['guides']) <= 1 ? 'disabled' : '' ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="addGuideBtn">
                            <i class="fas fa-plus"></i> Add Supervisor
                        </button>
                    </div>
                    
                    <!-- Students Section -->
                    <div class="mb-3">
                        <label class="form-label">Students:</label>
                        <div id="students-list" class="mb-2">
                        <?php 
$studentCount = count($project['student_names'] ?? []);
for ($i = 0; $i < $studentCount; $i++): 
?>
                            <div class="student-entry row g-2">
        <div class="col-md-4">
            <input type="text" name="regd_no[]" class="form-control form-control-sm" 
                   placeholder="Regd No." value="<?= htmlspecialchars($project['regd_nos'][$i] ?? '') ?>" required>
        </div>

                                <div class="col-md-4">
                                    <input type="text" name="name[]" class="form-control form-control-sm" 
                                           placeholder="Student Name" value="<?= htmlspecialchars($project['student_names'][$i] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <select name="dept[]" class="form-control form-control-sm" required>
                                        <option value="">Select Dept</option>
                                        <option value="CSE" <?= ($project['departments'][$i] ?? '') == 'CSE' ? 'selected' : '' ?>>CSE</option>
                                        <option value="IT" <?= ($project['departments'][$i] ?? '') == 'IT' ? 'selected' : '' ?>>IT</option>
                                        <option value="ECE" <?= ($project['departments'][$i] ?? '') == 'ECE' ? 'selected' : '' ?>>ECE</option>
                                        <option value="EEE" <?= ($project['departments'][$i] ?? '') == 'EEE' ? 'selected' : '' ?>>EEE</option>
                                        <option value="CHEM" <?= ($project['departments'][$i] ?? '') == 'CHEM' ? 'selected' : '' ?>>CHEM</option>
                                        <option value="MECH" <?= ($project['departments'][$i] ?? '') == 'MECH' ? 'selected' : '' ?>>MECH</option>
                                        <option value="CIVIL" <?= ($project['departments'][$i] ?? '') == 'CIVIL' ? 'selected' : '' ?>>CIVIL</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addStudent()">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </div>
                    
                    <!-- Status and Focus Area -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Status:</label>
                            <select name="current_status" class="form-control" required>
    <option value="">Select Status</option>
    <option value="Ongoing" <?= ($project['current_status'] ?? '') == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
    <option value="Completed" <?= ($project['current_status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
    <option value="Prototype Developed" <?= ($project['current_status'] ?? '') == 'Prototype Developed' ? 'selected' : '' ?>>Prototype Developed</option>
    <option value="In Research Phase" <?= ($project['current_status'] ?? '') == 'In Research Phase' ? 'selected' : '' ?>>In Research Phase</option>
</select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IIC Focus Area:</label>
                            <input type="text" name="iic_focus_area" class="form-control" required 
                                   value="<?= htmlspecialchars($project['iic_focus_area'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Potential Impact -->
                    <div class="mb-3">
                        <label class="form-label">Potential Impact:</label>
                        <textarea name="potential_impact" class="form-control" required 
                                  rows="3"><?= htmlspecialchars($project['potential_impact'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- SDGs and National Schemes -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Relevant SDGs:</label>
                            <select class="form-control" id="sdgDropdown">
                                <option value="">Select SDG</option>
                                <option value="No Poverty">SDG 1: No Poverty</option>
                                <option value="Zero Hunger">SDG 2: Zero Hunger</option>
                                <option value="Good Health and Well-being">SDG 3: Good Health and Well-being</option>
                                <option value="Quality Education">SDG 4: Quality Education</option>
                                <option value="Gender Equality">SDG 5: Gender Equality</option>
                                <option value="Clean Water and Sanitation">SDG 6: Clean Water and Sanitation</option>
                                <option value="Affordable and Clean Energy">SDG 7: Affordable and Clean Energy</option>
                                <option value="Decent Work and Economic Growth">SDG 8: Decent Work and Economic Growth</option>
                                <option value="Industry, Innovation and Infrastructure">SDG 9: Industry, Innovation and Infrastructure</option>
                                <option value="Reduced Inequalities">SDG 10: Reduced Inequalities</option>
                                <option value="Sustainable Cities and Communities">SDG 11: Sustainable Cities and Communities</option>
                                <option value="Responsible Consumption and Production">SDG 12: Responsible Consumption and Production</option>
                                <option value="Climate Action">SDG 13: Climate Action</option>
                                <option value="Life Below Water">SDG 14: Life Below Water</option>
                                <option value="Life on Land">SDG 15: Life on Land</option>
                                <option value="Peace, Justice and Strong Institutions">SDG 16: Peace, Justice and Strong Institutions</option>
                                <option value="Partnerships for the Goals">SDG 17: Partnerships for the Goals</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="addSdgBtn">Add</button>
                            <button type="button" class="btn btn-danger mt-2" id="clearSdgBtn">Clear All</button>
                            
                            <div id="selectedSdgsContainer" class="mt-2">
    <?php foreach (($project['relevant_sdgs'] ?? []) as $sdg): ?>
        <?php if (!empty($sdg)): ?>
        <span class="badge bg-primary d-flex align-items-center me-2 mb-2">
        <?= htmlspecialchars($sdg ?? '') ?>
            <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove"></button>
        </span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
                            
<input type="hidden" name="relevant_sdgs" id="sdgsHiddenInput" value="<?= htmlspecialchars(implode(',', $project['relevant_sdgs'] ?? [])) ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aligned Indian National Schemes:</label>
                            <input type="text" name="aligned_schemes" class="form-control" required 
                                   value="<?= htmlspecialchars($project['aligned_schemes'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Washington Accord and Academic Year -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Washington Accord POs:</label>
                            <div class="input-group">
                                <input type="text" name="washington_acord_pos" id="washingtonPos" 
                                       class="form-control ai-output" readonly required
                                       value="<?= htmlspecialchars($project['washington_acord_pos'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="button" id="refreshPosBtn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Automatically generated based on selected SDG weights
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Academic Year:</label>
                            <input type="text" name="academic_year" id="academicYear" class="form-control" required 
                                   value="<?= htmlspecialchars($project['academic_year'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="mb-3">
                        <label class="form-label">Upload File:</label>
                        <?php if (!empty($project['file_path'])): ?>
                        <div class="mb-2">
                            <a href="<?= htmlspecialchars($project['file_path']) ?>" class="btn btn-sm btn-success" download>
                                <i class="fas fa-download"></i> Download Current File
                            </a>
                            <span class="ms-2"><?= basename($project['file_path']) ?></span>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="file" class="form-control">
                        <small class="text-muted">Leave blank to keep current file</small>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save"></i> Update Project
                        </button>
                        <button type="reset" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Year picker for academic year
        $(function() {
            $('#academicYear').datepicker({
                changeYear: true,
                showButtonPanel: true,
                dateFormat: 'yy',
                yearRange: '2023:2035',
                minDate: new Date(2023, 0, 1),
                maxDate: new Date(2035, 11, 31),
                onClose: function(dateText, inst) {
                    const year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                    if (year) {
                        $(this).val(year + '-' + (parseInt(year) + 1));
                    }
                },
                beforeShow: function(input, inst) {
                    inst.dpDiv.addClass('yearpicker');
                    setTimeout(function() {
                        inst.dpDiv.find('.ui-datepicker-year').focus();
                    }, 1);
                }
            });
            
            // Style the year picker
            $('body').on('mousedown', '.ui-datepicker-year', function(e) {
                e.stopPropagation();
            });
        });

        // SDG Selection System
        document.addEventListener('DOMContentLoaded', function() {
            const sdgDropdown = document.getElementById('sdgDropdown');
            const addSdgBtn = document.getElementById('addSdgBtn');
            const clearSdgBtn = document.getElementById('clearSdgBtn');
            const selectedSdgsContainer = document.getElementById('selectedSdgsContainer');
            const sdgsHiddenInput = document.getElementById('sdgsHiddenInput');
            const washingtonPosInput = document.getElementById('washingtonPos');
            const refreshPosBtn = document.getElementById('refreshPosBtn');

            // Initialize with existing SDGs
            let selectedSdgs = <?= !empty($project['relevant_sdgs']) ? json_encode($project['relevant_sdgs']) : '[]' ?>;
            
            // Corrected SDG to Washington Accord PO mapping with weights
            const sdgPoKnowledgeBase = {
                "No Poverty": { "PO1": 0.9, "PO4": 0.8, "PO7": 0.7 },
                "Zero Hunger": { "PO2": 0.9, "PO5": 0.85, "PO9": 0.75 },
                "Good Health and Well-being": { "PO3": 0.95, "PO6": 0.8, "PO8": 0.7 },
                "Quality Education": { "PO1": 0.85, "PO4": 0.8, "PO10": 0.75 },
                "Gender Equality": { "PO5": 0.9, "PO7": 0.8, "PO9": 0.7 },
                "Clean Water and Sanitation": { "PO2": 0.85, "PO6": 0.8, "PO8": 0.75 },
                "Affordable and Clean Energy": { "PO3": 0.9, "PO7": 0.8, "PO10": 0.7 },
                "Decent Work and Economic Growth": { "PO1": 0.85, "PO4": 0.8, "PO9": 0.75 },
                "Industry, Innovation and Infrastructure": { "PO2": 0.9, "PO5": 0.85, "PO8": 0.7 },
                "Reduced Inequalities": { "PO3": 0.85, "PO6": 0.8, "PO10": 0.75 },
                "Sustainable Cities and Communities": { "PO1": 0.9, "PO4": 0.85, "PO7": 0.7 },
                "Responsible Consumption and Production": { "PO2": 0.85, "PO5": 0.8, "PO9": 0.75 },
                "Climate Action": { "PO3": 0.95, "PO6": 0.9, "PO8": 0.8 },
                "Life Below Water": { "PO1": 0.85, "PO4": 0.8, "PO10": 0.75 },
                "Life on Land": { "PO2": 0.85, "PO5": 0.8, "PO7": 0.75 },
                "Peace, Justice and Strong Institutions": { "PO3": 0.9, "PO6": 0.85, "PO9": 0.7 },
                "Partnerships for the Goals": { "PO1": 0.8, "PO4": 0.75, "PO8": 0.7 }
            };

            // Threshold for including POs in the final list
            const PO_THRESHOLD = 0.7;

            // Event Listeners
            addSdgBtn.addEventListener('click', addSdg);
            clearSdgBtn.addEventListener('click', clearAllSdgs);
            refreshPosBtn.addEventListener('click', updateWashingtonPos);
            selectedSdgsContainer.addEventListener('click', handleSdgRemoval);

            // Core Functions
            function addSdg() {
                const selectedValue = sdgDropdown.value;
                
                if (!selectedValue) {
                    alert('Please select an SDG first');
                    return;
                }
                
                if (selectedSdgs.includes(selectedValue)) {
                    alert('This SDG has already been selected');
                    return;
                }
                
                selectedSdgs.push(selectedValue);
                updateSdgsDisplay();
            }

            function handleSdgRemoval(e) {
                if (e.target.classList.contains('btn-close')) {
                    const sdgText = e.target.parentElement.textContent.trim();
                    const index = selectedSdgs.findIndex(sdg => sdgText.includes(sdg));
                    if (index !== -1) {
                        selectedSdgs.splice(index, 1);
                        updateSdgsDisplay();
                    }
                }
            }

            function clearAllSdgs() {
                if (selectedSdgs.length === 0) return;
                
                if (confirm('Are you sure you want to clear all selected SDGs?')) {
                    selectedSdgs = [];
                    updateSdgsDisplay();
                }
            }

            function updateSdgsDisplay() {
                // Clear existing badges
                selectedSdgsContainer.innerHTML = '';
                
                // Create new badges for selected SDGs
                selectedSdgs.forEach((sdgValue, index) => {
                    const option = sdgDropdown.querySelector(`option[value="${sdgValue}"]`);
                    if (!option) return;
                    
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary d-flex align-items-center me-2 mb-2';
                    badge.innerHTML = `
                        ${option.text}
                        <button type="button" class="btn-close btn-close-white ms-2" 
                                aria-label="Remove"></button>
                    `;
                    selectedSdgsContainer.appendChild(badge);
                });
                
                // Update hidden input
                sdgsHiddenInput.value = selectedSdgs.join(',');
                
                // Update Washington POs
                updateWashingtonPos();
            }

            function calculateWashingtonPos() {
                const poScores = {};
                
                // Aggregate scores from all selected SDGs
                selectedSdgs.forEach(sdg => {
                    const poWeights = sdgPoKnowledgeBase[sdg] || {};
                    for (const [po, weight] of Object.entries(poWeights)) {
                        poScores[po] = (poScores[po] || 0) + weight;
                    }
                });
                
                // Filter and sort POs
                const recommendedPos = Object.entries(poScores)
                    .filter(([_, score]) => score >= PO_THRESHOLD)
                    .sort((a, b) => {
                        // Sort by score (descending) then by PO number (ascending)
                        return b[1] - a[1] || parseInt(a[0].substring(2)) - parseInt(b[0].substring(2));
                    })
                    .map(([po]) => po);
                
                return recommendedPos.length > 0 
                    ? recommendedPos.join(', ') 
                    : 'No relevant POs identified';
            }

            function updateWashingtonPos() {
                if (selectedSdgs.length === 0) {
                    washingtonPosInput.value = 'Select SDGs to generate POs';
                    return;
                }
                
                // Show loading state
                washingtonPosInput.value = 'Analyzing SDG combinations...';
                washingtonPosInput.classList.add('ai-updating');
                
                // Simulate processing delay (better UX)
                setTimeout(() => {
                    washingtonPosInput.value = calculateWashingtonPos();
                    washingtonPosInput.classList.remove('ai-updating');
                    washingtonPosInput.classList.add('ai-updated');
                    
                    setTimeout(() => {
                        washingtonPosInput.classList.remove('ai-updated');
                    }, 2000);
                }, 500);
            }

            // Initialize
            updateSdgsDisplay();
        });

        // Add Student Function
        function addStudent() {
            let studentList = document.getElementById("students-list");
            let studentEntry = document.createElement("div");
            studentEntry.className = "student-entry row g-2";
            studentEntry.innerHTML = `
                <div class="col-md-4">
                    <input type="text" name="regd_no[]" class="form-control form-control-sm" placeholder="Regd No." required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="name[]" class="form-control form-control-sm" placeholder="Student Name" required>
                </div>
                <div class="col-md-3">
                    <select name="dept[]" class="form-control form-control-sm" required>
                        <option value="">Select Dept</option>
                        <option value="CSE">CSE</option>
                        <option value="IT">IT</option>
                        <option value="ECE">ECE</option>
                        <option value="EEE">EEE</option>
                        <option value="CHEM">CHEM</option>
                        <option value="MECH">MECH</option>
                        <option value="CIVIL">CIVIL</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            studentList.appendChild(studentEntry);
        }

        // Add Guide Function
        function addGuide() {
            const guidesList = document.getElementById('guides-list');
            const newGuideEntry = document.createElement('div');
            newGuideEntry.className = 'guide-entry row g-2 mb-2';
            newGuideEntry.innerHTML = `
                <div class="col-md-11">
                    <select name="guides[]" class="form-control" required>
                        <option value="">Select Supervisors</option>
                        <option value="Dr Praveen Kalla, Prof, Mech">Dr Praveen Kalla, Prof, Mech</option>
                        <option value="Dr K S Ravi Kumar, Prof, EEE">Dr K S Ravi Kumar, Prof, EEE</option>
                        <option value="Dr Raja Ramesh D, Assoc Prof, ECE">Dr Raja Ramesh D, Assoc Prof, ECE</option>
                        <option value="Sri M Vamsi Krishna, Sr Asst Prof, CSE">Sri M Vamsi Krishna, Sr Asst Prof, CSE</option>
                        <option value="Smt M Swarna, Assoc Prof, IT">Smt M Swarna, Assoc Prof, IT</option>
                        <option value="Sri Ch Varun, Dist Asst Prof, Mech">Sri Ch Varun, Dist Asst Prof, Mech</option>
                        <option value="Dr GVSR Pavan Kumar, Assoc Prof, Chemistry">Dr GVSR Pavan Kumar, Assoc Prof, Chemistry</option>
                        <option value="Dr VSR Naga Santosi, Asst Prof, Phy">Dr VSR Naga Santosi, Asst Prof, Phy</option>
                        <option value="Sri K Pavan Kumar, Dist Asst Prof, Mech">Sri K Pavan Kumar, Dist Asst Prof, Mech</option>
                        <option value="Ms R Hema Latha, Sr Asst Prof, Chem">Ms R Hema Latha, Sr Asst Prof, Chem</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger w-100 delete-guide-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            guidesList.appendChild(newGuideEntry);
            
            // Enable delete button on the first entry if we have multiple entries
            const guideEntries = document.querySelectorAll('.guide-entry');
            if (guideEntries.length > 1) {
                guideEntries[0].querySelector('.delete-guide-btn').disabled = false;
            }
        }

        // Delete supervisor field
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-guide-btn')) {
                const guideEntry = e.target.closest('.guide-entry');
                const guideEntries = document.querySelectorAll('.guide-entry');
                
                if (guideEntry && guideEntries.length > 1) {
                    guideEntry.remove();
                    
                    // If only one entry remains, disable its delete button
                    if (document.querySelectorAll('.guide-entry').length === 1) {
                        document.querySelector('.delete-guide-btn').disabled = true;
                    }
                }
            }
        });

        // Initialize the add button
        document.getElementById('addGuideBtn').addEventListener('click', addGuide);
    </script>
</body>
</html>