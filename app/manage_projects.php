<?php
require 'config.php'; // Database connection

// Handle File Upload
function uploadFile($file)
{
    $targetDir = "uploads/"; // Ensure this folder exists in your project
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    move_uploaded_file($file["tmp_name"], $targetFilePath);
    return $fileName;
}

// Add Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_project'])) {
    $domain = $_POST["domain"] ?? '';
    $theme = $_POST["theme"] ?? '';

    $guides = isset($_POST["guides"]) && is_array($_POST["guides"]) ? implode(",", $_POST["guides"]) : '';
    $regd_nos = isset($_POST["regd_no"]) && is_array($_POST["regd_no"]) ? implode(",", $_POST["regd_no"]) : '';
    $student_names = isset($_POST["name"]) && is_array($_POST["name"]) ? implode(",", $_POST["name"]) : '';
    $departments = isset($_POST["dept"]) && is_array($_POST["dept"]) ? implode(",", $_POST["dept"]) : '';

    $status = $_POST["current_status"] ?? '';
    $focus_area = $_POST["iic_focus_area"] ?? '';
    $impact = $_POST["potential_impact"] ?? '';
    $sdgs = $_POST["relevant_sdgs"] ?? '';
    $schemes = $_POST["aligned_schemes"] ?? '';
    $po = $_POST["washington_acord_pos"] ?? '';
    $academic_year = $_POST["academic_year"] ?? '';
    $fileName = isset($_FILES["file"]) && $_FILES["file"]["error"] == 0 ? uploadFile($_FILES["file"]) : '';


    $sql = "INSERT INTO projects (domain, theme, guides, regd_nos, student_names, departments, current_status, iic_focus_area, potential_impact, relevant_sdgs, aligned_schemes, washington_acord_pos, academic_year, file_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssss", $domain, $theme, $guides, $regd_nos, $student_names, $departments, $status, $focus_area, $impact, $sdgs, $schemes, $po, $academic_year, $fileName);

    if ($stmt->execute()) {
        echo "<script>alert('Project Added Successfully!'); window.location.href='manage_projects.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Delete Project
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM projects WHERE id=$id");
    echo "<script>alert('Project Deleted Successfully!'); window.location.href='manage_projects.php';</script>";
}

// Fetch Projects
$result = $conn->query("SELECT * FROM projects");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
    </style>
</head>
<body>
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
                                    <select name="domain" class="form-control" required>
                                        <option value="">Select Domain</option>
                                        <option value="e-Yantra">e-Yantra</option>
                                        <option value="Smart India Hackathon">Smart India Hackathon</option>
                                        <option value="Sports Technology">Sports Technology</option>
                                        <option value="Office Automation">Office Automation</option>
                                        <option value="Agriculture Technology">Agriculture Technology</option>
                                        <option value="Material Technology">Material Technology</option>
                                        <option value="Nano Technology">Nano Technology</option>
                                        <option value="MSME">MSME</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Theme:</label>
                                    <select name="theme" class="form-control" required>
                                        <option value="">Select Theme</option>
                                        <option value="Warehouse Drone">Warehouse Drone</option>
                                        <option value="Self Balancing Robot at Construction Site">Self Balancing Robot at Construction Site</option>
                                        <option value="Agent-Less System Vulnerability Scanner (The Fuzzer)">Agent-Less System Vulnerability Scanner (The Fuzzer)</option>
                                        <option value="Object Tracing By Using Image Processing">Object Tracing By Using Image Processing</option>
                                        <option value="Disaster Management Using Swarm Technology">Disaster Management Using Swarm Technology</option>
                                        <option value="Autonomous Water Surface Cleaner">Autonomous Water Surface Cleaner</option>
                                        <option value="Wearable Technology">Wearable Technology</option>
                                        <option value="IIC Office Automation">IIC Office Automation</option>
                                        <option value="Automated Seed Sowing Machine">Automated Seed Sowing Machine</option>
                                        <option value="Inner Coating of Paper Cups/Coconut Cups">Inner Coating of Paper Cups/Coconut Cups</option>
                                        <option value="Thermogravimetric Analysis">Thermogravimetric Analysis</option>
                                        <option value="Electromagnetic Shielding for EVs">Electromagnetic Shielding for EVs</option>
                                        <option value="Design And Performance Analysis Of Shell And Tube Heat Exchanger">Design And Performance Analysis Of Shell And Tube Heat Exchanger</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Guides:</label>
                                <div id="guides-list" class="mb-2">
                                    <div class="guide-entry">
                                        <select name="guides[]" class="form-control" required>
                                            <option value="">Select Guide</option>
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
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addGuide()">
                                    <i class="fas fa-plus"></i> Add Guide
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
                                    <select name="relevant_sdgs" class="form-control" required>
                                        <option value="">Select SDG</option>
                                        <option value="No Poverty">No Poverty</option>
                                        <option value="Zero Hunger">Zero Hunger</option>
                                        <option value="Good Health and Well-being">Good Health and Well-being</option>
                                        <option value="Quality Education">Quality Education</option>
                                        <option value="Gender Equality">Gender Equality</option>
                                        <option value="Clean Water and Sanitation">Clean Water and Sanitation</option>
                                        <option value="Affordable and Clean Energy">Affordable and Clean Energy</option>
                                        <option value="Industry, Innovation, and Infrastructure">Industry, Innovation, and Infrastructure</option>
                                        <option value="Sustainable Cities and Communities">Sustainable Cities and Communities</option>
                                        <option value="Climate Action">Climate Action</option>
                                        <option value="Life Below Water">Life Below Water</option>
                                        <option value="Life on Land">Life on Land</option>
                                        <option value="Peace, Justice, and Strong Institutions">Peace, Justice, and Strong Institutions</option>
                                        <option value="Partnerships for the Goals">Partnerships for the Goals</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Aligned Indian National Schemes:</label>
                                    <input type="text" name="aligned_schemes" class="form-control" required placeholder="Enter aligned national schemes">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Washington Accord POs:</label>
                                    <input type="text" name="washington_acord_pos" class="form-control" required placeholder="Enter Washington Accord POs">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Academic Year:</label>
                                    <select name="academic_year" class="form-control" required>
                                        <option value="">Select Academic Year</option>
                                        <option value="2024-2025">2024-2025</option>
                                        <option value="2025-2026">2025-2026</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload File:</label>
                                <input type="file" name="file" class="form-control" required>
                                <small class="text-muted">Upload project documentation or related files</small>
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
                    <div class="tab-pane fade" id="view-projects" role="tabpanel">
                        <div class="form-section">
                            <h3>Projects List</h3>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Domain</th>
                                            <th>Theme</th>
                                            <th>Status</th>
                                            <th>Year</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset the result pointer since we already fetched it once
                                        $result->data_seek(0);
                                        while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= $row['domain'] ?></td>
                                            <td><?= $row['theme'] ?></td>
                                            <td><?= $row['current_status'] ?></td>
                                            <td><?= $row['academic_year'] ?></td>
                                            <td class="action-btns">
                                                <button class="btn btn-info btn-sm view-btn" data-bs-toggle="modal" data-bs-target="#projectModal" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this project?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
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
            let guideList = document.getElementById('guides-list');
            let guideEntry = document.createElement("div");
            guideEntry.className = "guide-entry row g-2";
            guideEntry.innerHTML = `
                <div class="col-md-11">
                    <select name="guides[]" class="form-control form-control-sm" required>
                        <option value="">Select Guide</option>
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
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            guideList.appendChild(guideEntry);
        }
        
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
        
        // Function to load project details into modal
        function loadProjectDetails(projectId) {
            // In a real application, you would fetch this data via AJAX
            // For this example, we'll simulate it with the data we have
            const projects = <?php 
                $result->data_seek(0);
                $projects = [];
                while ($row = $result->fetch_assoc()) {
                    $projects[$row['id']] = $row;
                }
                echo json_encode($projects);
            ?>;
            
            const project = projects[projectId];
            if (!project) return;
            
            // Format the student details
            const regdNos = project.regd_nos.split(',');
            const studentNames = project.student_names.split(',');
            const departments = project.departments.split(',');
            
            let studentsHtml = '';
            for (let i = 0; i < regdNos.length; i++) {
                studentsHtml += `
                    <div class="project-details">
                        <strong>Student ${i+1}:</strong>
                        <div><strong>Regd No:</strong> ${regdNos[i]}</div>
                        <div><strong>Name:</strong> ${studentNames[i]}</div>
                        <div><strong>Department:</strong> ${departments[i]}</div>
                    </div>
                `;
            }
            
            // Format the guides
            const guides = project.guides.split(',').map(guide => `<div>${guide}</div>`).join('');
            
            // Build the modal content
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="project-details">
                            <strong>Domain:</strong>
                            <div>${project.domain}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Theme:</strong>
                            <div>${project.theme}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Current Status:</strong>
                            <div>${project.current_status}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>IIC Focus Area:</strong>
                            <div>${project.iic_focus_area}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Academic Year:</strong>
                            <div>${project.academic_year}</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="project-details">
                            <strong>Potential Impact:</strong>
                            <div>${project.potential_impact}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Relevant SDGs:</strong>
                            <div>${project.relevant_sdgs}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Aligned Indian National Schemes:</strong>
                            <div>${project.aligned_schemes}</div>
                        </div>
                        
                        <div class="project-details">
                            <strong>Washington Accord POs:</strong>
                            <div>${project.washington_acord_pos}</div>
                        </div>
                    </div>
                </div>
                
                <div class="project-details">
                    <strong>Guides:</strong>
                    ${guides}
                </div>
                
                <div class="project-details">
                    <strong>Students:</strong>
                    ${studentsHtml}
                </div>
                
                <div class="project-details">
                    <strong>Project File:</strong>
                    ${project.file_name ? 
                        `<a href="uploads/${project.file_name}" class="btn btn-info btn-sm" download>
                            <i class="fas fa-download"></i> Download File
                        </a>` : 
                        '<span class="badge bg-secondary">No File Uploaded</span>'}
                </div>
            `;
            
            document.getElementById('projectDetails').innerHTML = modalContent;
        }
    </script>
</body>
</html>