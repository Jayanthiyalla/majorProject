<?php
session_start();
require 'config.php'; // Database connection

// Handle File Upload with validation
function uploadFile($file) {
    $targetDir = "uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    if (!in_array($fileType, $allowedTypes)) {
        die("Error: Only PDF, DOC, PPT files are allowed.");
    }
    
    // Validate file size (50MB max)
    if ($file["size"] > 500000000) {
        die("Error: File is too large. Max 5MB allowed.");
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        return $targetFilePath; // Return full path instead of just filename
    } else {
        die("Error uploading file.");
    }
}

// Add Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_project'])) {
    // Sanitize inputs
    $domain = $conn->real_escape_string($_POST["domain"] ?? '');
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
    $filePath = '';
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
        $filePath = uploadFile($_FILES["file"]);
    }

    // Use prepared statement with correct column name (file_path)
    $sql = "INSERT INTO projects (
                domain, theme, guides, regd_nos, student_names, departments, 
                current_status, iic_focus_area, potential_impact, relevant_sdgs, 
                aligned_schemes, washington_acord_pos, academic_year, file_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param(
        "ssssssssssssss", 
        $domain, $theme, $guides, $regd_nos, $student_names, $departments,
        $status, $focus_area, $impact, $sdgs, $schemes, $po, $academic_year, $filePath
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Project Added Successfully!";
        header("Location: manage_projects.php");
        exit();
    } else {
        // Delete uploaded file if project creation failed
        if (!empty($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        die("Error: " . $stmt->error);
    }
}
// Convert to proper case before saving
$domain = ucwords(strtolower($conn->real_escape_string($_POST["domain"] ?? '')));

// Delete Project with file cleanup
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']); // Sanitize input
    
    // First get file path if exists
    $filePath = '';
    $result = $conn->query("SELECT file_path FROM projects WHERE id=$id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filePath = $row['file_path'];
    }
    
    // Delete project
    if ($conn->query("DELETE FROM projects WHERE id=$id")) {
        // Delete associated file if exists
        if (!empty($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        $_SESSION['success'] = "Project Deleted Successfully!";
    } else {
        $_SESSION['error'] = "Error deleting project: " . $conn->error;
    }
    header("Location: manage_projects.php");
    exit();
}

// Fetch Projects
// In manage_projects.php
$result = $conn->query("SELECT * FROM projects ORDER BY id DESC");?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #f5f7fa;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
          
        /* Top Bar Styles */
        .top-bar {
            background:rgb(52, 51, 51);
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
        
        /* Header Container */
        .inside-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #ffffff;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .site-logo {
            flex-shrink: 0;
        }
        .main-navigation {
            flex-grow: 1;
            text-align: right;
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
            margin-top: auto;
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
        .btn-primary:hover {
            background-color: #3a5bbf;
            border-color: #3a5bbf;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        .table {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #4e73df;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .add-btn {
            margin-right: 5px;
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
        h2, h3 {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link.active {
            color: #4e73df;
            font-weight: 600;
        }
        .tab-content {
            padding: 20px 0;
        }
        .project-details {
            margin-bottom: 15px;
        }
        .project-details strong {
            display: block;
            color: #4e73df;
            margin-bottom: 5px;
        }
        .modal-lg {
            max-width: 800px;
        }
        /* Add to your existing styles */
/* Add to your existing styles */
.newDomainInput {
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
}

.input-group > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
} 
/* Year picker styles */
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
/* Filter controls styles */
.yearFilter {
    border-radius: 4px;
    border: 1px solid #ced4da;
    padding: 0.375rem 0.75rem;
    height: calc(1.5em + 0.75rem + 2px);
    background-color: white;
}

.resetFilter {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    display: flex;
    align-items: center;
}

.filter-label {
    font-size: 0.875rem;
    color: #495057;
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

.input-group > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
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
.newThemeInput {
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
}

.input-group > .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
  </style>
</head>
<body>
<div class="top-bar">
        <nav>
            <ul class="top-menu">
                <li><a href="https://mvgrce.com/">About MVGR</a></li>
                <li><a href="admin.php">Admin Dashboard</a></li>
                <li><a href="https://iic.mvgrglug.com/sih-ideas-presentations/">SIH Ideas & Presentations</a></li>
                <li><a href="https://iic.mvgrglug.com/no-title/">Press & Media</a></li>
            </ul>
        </nav>
    </div>
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
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Manage Projects</h2>
                <a href="admin.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Admin
                </a>
            </div>
            
            <div class="card-body">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs" id="projectTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-project" type="button" role="tab">Add Project</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="view-tab" data-bs-toggle="tab" data-bs-target="#view-projects" type="button" role="tab">View Projects</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                     <div class="tab-content">
                    <!-- Add Project Tab -->
                            <div class="tab-pane fade show active" id="add-project" role="tabpanel">
                                <form method="POST" enctype="multipart/form-data" class="form-section">
                                  <input type="hidden" name="add_project" value="1">
                            
                                   <div class="row">
                                         <div class="col-md-6 mb-3">
                                              <label class="form-label">Domain:</label>
                                                    <div class="input-group">
                                                          <select name="domain" class="form-control" id="domainSelect" required>
                                                                       <option value="">Select Domain</option>
                                                                                 <?php
                                                                                 $domains = $conn->query("SELECT domain FROM domains ORDER BY domain");
                                                                                 while ($domain = $domains->fetch_assoc()) {
                                                                                     echo '<option value="' . htmlspecialchars($domain['domain']) . '">' . 
                                                                                          htmlspecialchars($domain['domain']) . '</option>';
                                                                                 }
                                                                                 ?>
                                                                                   </select>
                                                                                   <button type="button" class="btn btn-outline-primary" id="addDomainBtn">
                                                                                         <i class="fas fa-plus"></i> Add
                                                                                </button>

            
        
                                                    </div>
                                                                             <div id="newDomainInput" class="mt-2" style="display: none;">
                                                                                   <div class="input-group">
                                                                                        <input type="text" class="form-control" id="newDomainName" placeholder="Enter new domain name">
                                                                                              <button class="btn btn-success" id="saveDomainBtn">
                                                                                                             <i class="fas fa-check"></i> Save
                                                                                                </button>
                                                                                                <button class="btn btn-danger" id="cancelDomainBtn">
                                                                                                        <i class="fas fa-times"></i> Cancel
                                                                                                </button>
            
                                                                                    </div>
                                                                                </div>
                                            </div>
        

                                          
                                                                               
                                            <div class="col-md-6 mb-3">
    <label class="form-label">Theme:</label>
    <div class="input-group">
        <select name="theme" class="form-control" id="themeSelect" required>
            <option value="">Select Theme</option>
            <?php
            $themes = $conn->query("SELECT DISTINCT theme FROM projects ORDER BY theme");
            while ($theme = $themes->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($theme['theme']) . '">' . 
                     htmlspecialchars($theme['theme']) . '</option>';
            }
            ?>
        </select>
        <button type="button" class="btn btn-outline-primary" id="addThemeBtn">
            <i class="fas fa-plus"></i> Add
        </button>
    </div>
    <div id="newThemeInput" class="mt-2" style="display: none;">
        <div class="input-group">
            <input type="text" class="form-control" id="newThemeName" placeholder="Enter new theme name">
            <button class="btn btn-success" id="saveThemeBtn">
                <i class="fas fa-check"></i> Save
            </button>
            <button class="btn btn-danger" id="cancelThemeBtn">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>
                            </div>
                            
                            <div class="mb-3">
    <label class="form-label">Supervisors:</label>
    <div id="guides-list" class="mb-2">
        <div class="guide-entry row g-2 mb-2">
            <div class="col-md-11">
                <select name="guides[]" class="form-control guide-select" required>
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
                <div id="newGuideInput" class="mt-2" style="display: none;">
                    <div class="input-group">
                        <input type="text" class="form-control" id="newGuideName" placeholder="Enter supervisor name">
                        <input type="text" class="form-control" id="newGuideTitle" placeholder="Title (e.g., Prof)">
                        <input type="text" class="form-control" id="newGuideDept" placeholder="Department">
                        <button class="btn btn-success" id="saveGuideBtn">
                            <i class="fas fa-check"></i> Save
                        </button>
                        <button class="btn btn-danger" id="cancelGuideBtn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 delete-guide-btn" disabled>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" id="addNewGuideBtn">
        <i class="fas fa-plus"></i> Add New Supervisor
    </button>
    <button type="button" class="btn btn-secondary btn-sm" id="addGuideBtn">
        <i class="fas fa-plus"></i> Add Existing Supervisor
    </button>
</div>
                            
                            <div class="mb-3">
                                <label class="form-label">Students:</label>
                                <div id="students-list" class="mb-2"></div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addStudent()">
                                    <i class="fas fa-plus"></i> Add Student
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Status:</label>
                                    <select name="current_status" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="Ongoing">Ongoing</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Prototype Developed">Prototype Developed</option>
                                        <option value="In Research Phase">In Research Phase</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">IIC Focus Area:</label>
                                    <input type="text" name="iic_focus_area" class="form-control" required placeholder="Enter IIC Focus Area">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Potential Impact:</label>
                                <textarea name="potential_impact" class="form-control" required placeholder="Describe potential impact" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Relevant SDGs:</label>
                                    
                                    <select class="form-control" id="sdgDropdown">
                                        <option value="">Select SDG</option>
                                        <option value="No Poverty">SDG 1: No Poverty</option>
                                        <option value="Zero Hunger">SDG 2: Zero Hunger</option>
                                        <option value="Good Health and Well-being">SDG 3:Good Health and Well-being</option>
                                        <option value="Quality Education">SDG 4: Quality Education</option>
                                        <option value="Gender Equality">SDG 5: Gender Equality</option>
                                        <option value="Clean Water and Sanitation">SDG 6:Clean Water and Sanitation</option>
                                        <option value="Affordable and Clean Energy">SDG 7:Affordable and Clean Energy</option>
                                        <option value="Decent Work and Economic Growth">SDG 8: Decent Work and Economic Growth</option>
                                        <option value="Industry, Innovation, and Infrastructures">SDG 9:Industry, Innovation, and Infrastructure</option>
                                        <option value="Required Inqualities">SDG 10:Required Inqualities</option>
                                        <option value="Sustainble Cities and Communities">SDG 11:Sustainble Cities and Communities</option>
                                        <option value="Responsible Consumption and Production">SDG 12:Responsible Consumption and Production</option>
                                        <option value="Climate Action">SDG 13:Climate Action</option>
                                        <option value="Life Below Water">SDG 14:Life Below Water</option>
                                        <option value="Life Below On Land">SDG 15:Life Below On Land</option>
                                        <option value="Peace, Justice and strong Institutions">SDG 16:Peace, Justice and strong Institutions</option>
                                        <option value="Partnerships for the Goals">SDG 17:Partnerships For the Goals</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addSdgBtn">Add</button>
                                    <button type="button" class="btn btn-danger mt-2" id="clearSdgBtn">Clear All</button>

                                    
                                     <div id="selectedSdgsContainer" class="mt-2"></div>
    
                                        <input type="hidden" name="relevant_sdgs" id="sdgsHiddenInput">

                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Aligned Indian National Schemes:</label>
                                    <input type="text" name="aligned_schemes" class="form-control" required placeholder="Enter aligned national schemes">
                                </div>
                            </div>
                            
                            <div class="row">
                            <div class="col-md-6 mb-3">
    <label class="form-label">Washington Accord POs:
        
    </label>
    <div class="input-group">
        <input type="text" name="washington_acord_pos" id="washingtonPos" 
               class="form-control ai-output" readonly required>
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
                                    <input type="text" name="academic_year" id="academicYear" class="form-control" required readonly placeholder="Select Academic Year">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload File:</label>
                                <input type="file" name="file" class="form-control" required>
                                <small class="text-muted">Upload Abstract or problem statement</small>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save"></i> Save Project
                                </button>
                                <button type="reset" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- View Projects Tab -->
                    <!-- View Projects Tab -->
<div class="tab-pane fade" id="view-projects" role="tabpanel">
    <div class="form-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Projects List</h3>
            <div class="d-flex align-items-center">
                <label for="yearFilter" class="me-2 mb-0">Filter by Year:</label>
                <select id="yearFilter" class="form-select form-select-sm me-2" style="width: 150px;">
                    <option value="">All Years</option>
                    <?php
                    // Get distinct academic years from the database
                    $yearsQuery = $conn->query("SELECT DISTINCT academic_year FROM projects ORDER BY academic_year DESC");
                    while ($yearRow = $yearsQuery->fetch_assoc()) {
                        echo "<option value='{$yearRow['academic_year']}'>{$yearRow['academic_year']}</option>";
                    }
                    ?>
                </select>
                <button id="resetFilter" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-sync-alt"></i> Reset
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="projectsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Domain</th>
                        <th>Theme</th>
                        <th>Status</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()): ?>
                    <tr data-year="<?= htmlspecialchars($row['academic_year']?? '') ?>">
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['domain'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['theme']??'') ?></td>
                        <td><?= htmlspecialchars($row['current_status'] ?? '') ?: 'Not set' ?></td>
                        <td><?= htmlspecialchars($row['academic_year']?? '') ?></td>
                        <td class="action-btns">
    <button class="btn btn-info btn-sm view-btn" data-bs-toggle="modal" data-bs-target="#projectModal" data-id="<?= $row['id'] ?>">
        <i class="fas fa-eye"></i> View
    </button>
    <a href="edit_project.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this project?')">
        <i class="fas fa-trash"></i> Delete
    </a>
    <button class="btn btn-info summarize-btn" data-project-id="{{ project.id }}">Summarize</button>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="summaryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Project Summary</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="summaryContent">
        <!-- AI-generated summary will appear here -->
      </div>
    </div>
  </div>
</div>

    <!-- Project Details Modal -->
    <div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalLabel">Project Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="projectDetails">
                    <!-- Project details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
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
// Custom academic year picker (2023-2035)
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
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.getElementById('themeSelect');
    const addThemeBtn = document.getElementById('addThemeBtn');
    const newThemeInput = document.getElementById('newThemeInput');
    const newThemeName = document.getElementById('newThemeName');
    const saveThemeBtn = document.getElementById('saveThemeBtn');
    const cancelThemeBtn = document.getElementById('cancelThemeBtn');

    // Auto-capitalize theme names
    newThemeName.addEventListener('input', function(e) {
        this.value = this.value
            .toLowerCase()
            .replace(/\b\w/g, function(char) {
                return char.toUpperCase();
            });
    });

    // Show/hide new theme input
    addThemeBtn.addEventListener('click', function() {
        newThemeInput.style.display = 'block';
        newThemeName.focus();
    });
    
    // Cancel adding new theme
    cancelThemeBtn.addEventListener('click', function() {
        newThemeInput.style.display = 'none';
        newThemeName.value = '';
    });
    
    // Save new theme
    saveThemeBtn.addEventListener('click', function() {
        const themeValue = newThemeName.value.trim();
        
        if (themeValue) {
            // Check if theme already exists (case-insensitive)
            let exists = false;
            for (let i = 0; i < themeSelect.options.length; i++) {
                if (themeSelect.options[i].value.toLowerCase() === themeValue.toLowerCase()) {
                    exists = true;
                    break;
                }
            }
            
            if (!exists) {
                // Add to dropdown immediately (optimistic UI update)
                const newOption = new Option(themeValue, themeValue);
                themeSelect.add(newOption);
                themeSelect.value = themeValue;
                
                // Hide input and clear
                newThemeInput.style.display = 'none';
                newThemeName.value = '';
                
                // Save to database via AJAX
                saveThemeToDatabase(themeValue);
            } else {
                alert('This theme already exists!');
                newThemeName.focus();
            }
        } else {
            alert('Please enter a theme name');
            newThemeName.focus();
        }
    });

    // Save to database via AJAX
    function saveThemeToDatabase(themeName) {
        $.ajax({
            url: 'save_theme.php',
            type: 'POST',
            data: { theme: themeName },
            dataType: 'json',
            success: function(response) {
                if (response.status !== 'success') {
                    // Remove from dropdown if save failed
                    for (let i = 0; i < themeSelect.options.length; i++) {
                        if (themeSelect.options[i].value === themeName) {
                            themeSelect.remove(i);
                            break;
                        }
                    }
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                // Remove from dropdown if save failed
                for (let i = 0; i < themeSelect.options.length; i++) {
                    if (themeSelect.options[i].value === themeName) {
                        themeSelect.remove(i);
                        break;
                    }
                }
                alert('Error saving theme: ' + error);
            }
        });
    }
});
// Add this to your existing JavaScript
document.querySelectorAll('.summarize-btn').forEach(button => {
    button.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        
        // Show loading state
        const modalBody = document.querySelector('#summaryModal .modal-body');
        modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generating summary...</div>';
        
        // Call Flask endpoint
        fetch(`/summarize-project/${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                } else {
                    modalBody.innerHTML = `<p>${data.summary}</p>`;
                }
            })
            .catch(error => {
                modalBody.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            });
        
        // Show modal
        $('#summaryModal').modal('show');
    });
});
// Get references to HTML elements
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const sdgDropdown = document.getElementById('sdgDropdown');
    const addSdgBtn = document.getElementById('addSdgBtn');
    const clearSdgBtn = document.getElementById('clearSdgBtn');
    const selectedSdgsContainer = document.getElementById('selectedSdgsContainer');
    const sdgsHiddenInput = document.getElementById('sdgsHiddenInput');
    const washingtonPosInput = document.getElementById('washingtonPos');
    const refreshPosBtn = document.getElementById('refreshPosBtn');

    // Array to store selected SDGs
    let selectedSdgs = [];

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
            const index = parseInt(e.target.getAttribute('data-index'));
            if (!isNaN(index)) {
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
                        aria-label="Remove" data-index="${index}"></button>
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

// Handle "Clear All" button click
clearSdgBtn.addEventListener('click', function () {
    selectedSdgs = []; // Empty the array
    updateSdgsDisplay();
});
// Add to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const addNewGuideBtn = document.getElementById('addNewGuideBtn');
    const newGuideInput = document.getElementById('newGuideInput');
    const newGuideName = document.getElementById('newGuideName');
    const newGuideTitle = document.getElementById('newGuideTitle');
    const newGuideDept = document.getElementById('newGuideDept');
    const saveGuideBtn = document.getElementById('saveGuideBtn');
    const cancelGuideBtn = document.getElementById('cancelGuideBtn');
    const guideSelects = document.querySelectorAll('.guide-select');

    // Show/hide new guide input
    addNewGuideBtn.addEventListener('click', function() {
        newGuideInput.style.display = 'block';
        newGuideName.focus();
    });
    
    // Cancel adding new guide
    cancelGuideBtn.addEventListener('click', function() {
        newGuideInput.style.display = 'none';
        newGuideName.value = '';
        newGuideTitle.value = '';
        newGuideDept.value = '';
    });
    
    // Save new guide
    saveGuideBtn.addEventListener('click', function() {
        const name = newGuideName.value.trim();
        const title = newGuideTitle.value.trim();
        const dept = newGuideDept.value.trim();
        
        if (name && title && dept) {
            const fullName = `${title} ${name}, ${dept}`;
            
            // Add to all guide selects
            guideSelects.forEach(select => {
                const option = new Option(fullName, fullName);
                select.add(option);
                select.value = fullName; // Select the new option
            });
            
            // Hide input and clear
            newGuideInput.style.display = 'none';
            newGuideName.value = '';
            newGuideTitle.value = '';
            newGuideDept.value = '';
            
            // Save to database via AJAX (you'll need to implement this)
            saveGuideToDatabase(name, title, dept);
        } else {
            alert('Please fill all fields');
            if (!name) newGuideName.focus();
            else if (!title) newGuideTitle.focus();
            else newGuideDept.focus();
        }
    });

    // Save to database via AJAX
    function saveGuideToDatabase(name, title, dept) {
        $.ajax({
            url: 'save_guide.php',
            type: 'POST',
            data: { 
                name: name,
                title: title,
                department: dept
            },
            dataType: 'json',
            success: function(response) {
                if (response.status !== 'success') {
                    // Remove from dropdowns if save failed
                    guideSelects.forEach(select => {
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value === `${title} ${name}, ${dept}`) {
                                select.remove(i);
                                break;
                            }
                        }
                    });
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                // Remove from dropdowns if save failed
                guideSelects.forEach(select => {
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value === `${title} ${name}, ${dept}`) {
                            select.remove(i);
                            break;
                        }
                    }
                });
                alert('Error saving guide: ' + error);
            }
        });
    }
});

// Project filtering by academic year
document.addEventListener('DOMContentLoaded', function() {
    const yearFilter = document.getElementById('yearFilter');
    const resetFilter = document.getElementById('resetFilter');
    const projectsTable = document.getElementById('projectsTable');
    
    yearFilter.addEventListener('change', function() {
        const selectedYear = this.value;
        const rows = projectsTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            if (selectedYear === '' || row.getAttribute('data-year') === selectedYear) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    resetFilter.addEventListener('click', function() {
        yearFilter.value = '';
        const rows = projectsTable.querySelectorAll('tbody tr');
        rows.forEach(row => row.style.display = '');
    });

});

// Comprehensive SDG to Washington Accord PO mapping with weights

    document.addEventListener('DOMContentLoaded', function() {
    const domainSelect = document.getElementById('domainSelect');
    const addDomainBtn = document.getElementById('addDomainBtn');
    const newDomainInput = document.getElementById('newDomainInput');
    const newDomainName = document.getElementById('newDomainName');
    const saveDomainBtn = document.getElementById('saveDomainBtn');
    const cancelDomainBtn = document.getElementById('cancelDomainBtn');

    document.getElementById('newDomainName').addEventListener('input', function(e) {
        this.value = this.value
            .toLowerCase()
            .replace(/\b\w/g, function(char) {
                return char.toUpperCase();
            });
    });

    
    // Show/hide new domain input
    addDomainBtn.addEventListener('click', function() {
        newDomainInput.style.display = 'block';
        newDomainName.focus();
    });
    
    // Cancel adding new domain
    cancelDomainBtn.addEventListener('click', function() {
        newDomainInput.style.display = 'none';
        newDomainName.value = '';
    });
    
    // Save new domain
    saveDomainBtn.addEventListener('click', function() {
        const domainValue = newDomainName.value.trim();
        
        if (domainValue) {
            // Check if domain already exists (case-insensitive)
            let exists = false;
            for (let i = 0; i < domainSelect.options.length; i++) {
                if (domainSelect.options[i].value.toLowerCase() === domainValue.toLowerCase()) {
                    exists = true;
                    break;
                }
            }
            
            if (!exists) {
                // Add to dropdown immediately (optimistic UI update)
                const newOption = new Option(domainValue, domainValue);
                domainSelect.add(newOption);
                domainSelect.value = domainValue;
                
                // Hide input and clear
                newDomainInput.style.display = 'none';
                newDomainName.value = '';
                
                // Save to database via AJAX
                saveDomainToDatabase(domainValue);
            } else {
                alert('This domain already exists!');
                newDomainName.focus();
            }
        } else {
            alert('Please enter a domain name');
            newDomainName.focus();
        }
    });
// Case-insensitive domain selection
document.getElementById('domainSelect').addEventListener('change', function() {
    const selected = this.value.toLowerCase();
    const options = this.options;
    
    for (let i = 0; i < options.length; i++) {
        if (options[i].value.toLowerCase() === selected) {
            this.selectedIndex = i;
            break;
        }
    }
});
    // Save to database via AJAX
    function saveDomainToDatabase(domainName) {
        $.ajax({
            url: 'save_domain.php',
            type: 'POST',
            data: { domain: domainName },
            dataType: 'json',
            success: function(response) {
                if (response.status !== 'success') {
                    // Remove from dropdown if save failed
                    for (let i = 0; i < domainSelect.options.length; i++) {
                        if (domainSelect.options[i].value === domainName) {
                            domainSelect.remove(i);
                            break;
                        }
                    }
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                // Remove from dropdown if save failed
                for (let i = 0; i < domainSelect.options.length; i++) {
                    if (domainSelect.options[i].value === domainName) {
                        domainSelect.remove(i);
                        break;
                    }
                }
                alert('Error saving domain: ' + error);
            }
        });
    }
});
        
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
        
        // Add one student by default when page loads
        document.addEventListener('DOMContentLoaded', function() {
            addStudent();
            
            // Set up event listeners for view buttons
            document.querySelectorAll('.view-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-id');
                    loadProjectDetails(projectId);
                });
            });
        });
        // Add to your DOMContentLoaded event
document.getElementById('refreshPosBtn').addEventListener('click', updateWashingtonPos);

// Modify your existing SDG update function
function updateSdgsDisplay() {
    // ... existing badge creation code ...
    updateWashingtonPos(); // Auto-update POs when SDGs change
}
        
        // Function to load project details into modal
        // Replace your existing loadProjectDetails function with this:
function loadProjectDetails(projectId) {
    // Fetch project details via AJAX
    $.ajax({
        url: 'get_project_details.php',
        type: 'GET',
        data: { id: projectId },
        dataType: 'json',
        success: function(project) {
            if (!project) {
                $('#projectDetails').html('<div class="alert alert-danger">Project not found</div>');
                return;
            }

            // Format the student details
            let studentsHtml = '';
            if (project.regd_nos && project.student_names && project.departments) {
                const regdNos = project.regd_nos.split(',');
                const studentNames = project.student_names.split(',');
                const departments = project.departments.split(',');
                
                for (let i = 0; i < regdNos.length; i++) {
                    studentsHtml += `
                        <div class="project-details">
                            <strong>Student ${i+1}:</strong>
                            <div><strong>Regd No:</strong> ${regdNos[i] || 'N/A'}</div>
                            <div><strong>Name:</strong> ${studentNames[i] || 'N/A'}</div>
                            <div><strong>Department:</strong> ${departments[i] || 'N/A'}</div>
                        </div>
                    `;
                }
            }

            // Format the guides
            let guidesHtml = 'No supervisors listed';
            if (project.guides) {
                const guides = project.guides.split(',');
                guidesHtml = guides.map(guide => `<div>${guide}</div>`).join('');
            }

            // Build the modal content
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="project-details">
                            <strong>Domain:</strong>
                            <div>${project.domain || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Theme:</strong>
                            <div>${project.theme || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
    <strong>Current Status:</strong>
    <div>${project.current_status ? project.current_status : 'Not set'}</div>
</div>
                        
                        <div class="project-details">
                            <strong>IIC Focus Area:</strong>
                            <div>${project.iic_focus_area || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Academic Year:</strong>
                            <div>${project.academic_year || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="project-details">
                            <strong>Potential Impact:</strong>
                            <div>${project.potential_impact || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Relevant SDGs:</strong>
                            <div>${project.relevant_sdgs || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Aligned Indian National Schemes:</strong>
                            <div>${project.aligned_schemes || 'N/A'}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Washington Accord POs:</strong>
                            <div>${project.washington_acord_pos || 'N/A'}</div>
                        </div>
                    </div>
                </div>
                
                <div class="project-details">
                    <strong>Supervisors:</strong>
                    ${guidesHtml}
                </div>
                
                <div class="project-details">
                    <strong>Students:</strong>
                    ${studentsHtml || 'No students listed'}
                </div>
                
                <div class="project-details">
                    <strong>Project File:</strong>
                    ${project.file_path ? 
                        `<a href="${project.file_path}" class="btn btn-info btn-sm" target="_blank" download>
                            <i class="fas fa-download"></i> Download File
                        </a>` : 
                        '<span class="badge bg-secondary">No File Uploaded</span>'}
                </div>
            `;
            
            $('#projectDetails').html(modalContent);
        },
        error: function(xhr, status, error) {
            $('#projectDetails').html('<div class="alert alert-danger">Error loading project details: ' + error + '</div>');
        }
    });
}
    </script>
</body>
<!-- Footer (same as index.html) -->
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
</html>