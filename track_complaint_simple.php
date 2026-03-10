<?php
require_once 'config.php';

// Database Query for Complaint Tracking
function getComplaintDetails($complaint_id) {
    global $conn;
    
    // Main complaint query with department information
    $query = "SELECT c.*, 
             CASE 
                WHEN c.auto_assigned_department IS NOT NULL THEN 
                    (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.auto_assigned_department)
                ELSE 
                    (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.category)
             END as department_name
             FROM complaints c 
             WHERE c.complaint_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Database Query for Complaint Timeline
function getComplaintTimeline($complaint_id) {
    global $conn;
    
    $query = "SELECT ct.*, 
             CASE 
                 WHEN ct.performed_by IS NOT NULL THEN 
                     CASE 
                         WHEN ct.performed_by IN (SELECT id FROM admin_users) THEN 
                             (SELECT CONCAT(full_name, ' (Admin)') FROM admin_users WHERE id = ct.performed_by)
                         ELSE 
                             (SELECT CONCAT(name, ' (User)') FROM users WHERE id = ct.performed_by)
                     END
                 ELSE 'System'
             END as performer_name
             FROM complaint_timeline ct 
             WHERE ct.complaint_id = ? 
             ORDER BY ct.performed_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $timeline = [];
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    return $timeline;
}

// Process form submission or GET parameter
$errors = [];
$complaint = null;
$timeline = [];
$complaint_id = '';

// Handle GET parameter (for direct links)
if (isset($_GET['complaint_id'])) {
    $complaint_id = clean_input($_GET['complaint_id']);
}
// Handle POST submission
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = clean_input($_POST['complaint_id']);
}

// Process complaint tracking if ID is provided
if (!empty($complaint_id)) {
    // Validation
    if (empty($complaint_id)) {
        $errors[] = "Complaint ID is required";
    } else {
        // Fetch complaint details
        $complaint = getComplaintDetails($complaint_id);
        
        if (!$complaint) {
            $errors[] = "Invalid Complaint ID";
        } else {
            // Fetch timeline
            $timeline = getComplaintTimeline($complaint_id);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Complaint - Smart Grievance Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-city"></i>
                <span>Smart Grievance Portal</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="track_complaint_simple.php" class="nav-link active">Track Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link">Admin Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="track-section">
        <div class="container">
            <div class="track-container">
                <h2><i class="fas fa-search"></i> Track Your Complaint</h2>
                <p>Enter your Complaint ID to check the current status</p>
                
                <!-- Search Form -->
                <form method="POST" class="search-form">
                    <div class="form-group">
                        <label for="complaint_id">Complaint ID</label>
                        <input type="text" 
                               id="complaint_id" 
                               name="complaint_id" 
                               placeholder="Enter Complaint ID (e.g., CMP-2024-001)" 
                               required
                               value="<?php echo !empty($complaint_id) ? htmlspecialchars($complaint_id) : (isset($_POST['complaint_id']) ? htmlspecialchars($_POST['complaint_id']) : ''); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Track Complaint
                        </button>
                    </div>
                </form>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Complaint Details -->
                <?php if ($complaint): ?>
                    <div class="complaint-info">
                        <h3><i class="fas fa-clipboard-list"></i> Complaint Details</h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Complaint ID:</label>
                                <span><?php echo htmlspecialchars($complaint['complaint_id']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Name:</label>
                                <span><?php echo htmlspecialchars($complaint['name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($complaint['email']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Mobile:</label>
                                <span><?php echo htmlspecialchars($complaint['mobile']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Department:</label>
                                <span><?php echo htmlspecialchars($complaint['department_name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Location:</label>
                                <span><?php echo htmlspecialchars($complaint['location']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <label>Priority:</label>
                                <span class="priority <?php echo $complaint['priority']; ?>">
                                    <?php echo ucfirst($complaint['priority']); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <label>Status:</label>
                                <span class="status <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="info-item full">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($complaint['address']); ?></span>
                            </div>
                            
                            <div class="info-item full">
                                <label>Description:</label>
                                <span><?php echo htmlspecialchars($complaint['description']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Timeline -->
                    <div class="progress-section">
                        <h3><i class="fas fa-clock"></i> Complaint Progress</h3>
                        
                        <div class="progress-steps">
                            <?php
                            $steps = [
                                'submitted' => ['Submitted', 'fa-paper-plane'],
                                'assigned' => ['Assigned to Department', 'fa-user-check'],
                                'in_progress' => ['In Progress', 'fa-spinner'],
                                'resolved' => ['Resolved', 'fa-check-circle']
                            ];
                            
                            $current_status = $complaint['status'];
                            $step_index = array_search($current_status, array_keys($steps));
                            
                            foreach ($steps as $status => $step_info):
                                $is_completed = array_search($status, array_keys($steps)) <= $step_index;
                                $is_current = $status === $current_status;
                            ?>
                                <div class="step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="fas <?php echo $step_info[1]; ?>"></i>
                                    </div>
                                    <div class="step-info">
                                        <h4><?php echo $step_info[0]; ?></h4>
                                        <?php
                                        // Find timeline date for this step
                                        $step_date = '';
                                        foreach ($timeline as $event) {
                                            if ($event['action'] === $status) {
                                                $step_date = date('M d, Y h:i A', strtotime($event['performed_at']));
                                                break;
                                            }
                                        }
                                        if ($step_date) {
                                            echo '<p class="date">' . $step_date . '</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Detailed Timeline -->
                        <?php if (!empty($timeline)): ?>
                            <div class="detailed-timeline">
                                <h4><i class="fas fa-history"></i> Detailed Timeline</h4>
                                
                                <?php foreach ($timeline as $event): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot <?php echo $event['action']; ?>"></div>
                                        <div class="timeline-content">
                                            <h5><?php echo ucfirst(str_replace('_', ' ', $event['action'])); ?></h5>
                                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                                            <small class="timeline-meta">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo date('M d, Y h:i A', strtotime($event['performed_at'])); ?> |
                                                <i class="fas fa-user"></i> 
                                                <?php echo htmlspecialchars($event['performer_name']); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="actions">
                        <a href="track_complaint_simple.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Track Another
                        </a>
                        <button onclick="window.print()" class="btn btn-outline">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Smart Grievance Management System. All rights reserved.</p>
        </div>
    </footer>

    <style>
        .track-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .track-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .track-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .track-container p {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .search-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message p {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .complaint-info, .progress-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .complaint-info h3, .progress-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-item.full {
            grid-column: 1 / -1;
        }
        
        .info-item label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #495057;
        }
        
        .priority, .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            display: inline-block;
        }
        
        .priority.normal, .status.pending { background: #95a5a6; }
        .priority.high, .status.in-progress { background: #f39c12; }
        .priority.urgent, .status.escalated { background: #e74c3c; }
        .status.resolved { background: #27ae60; }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #6c757d;
        }
        
        .step.completed .step-icon {
            background: #27ae60;
            color: white;
        }
        
        .step.current .step-icon {
            background: #3498db;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }
        
        .step-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #2c3e50;
        }
        
        .step-info .date {
            margin: 0;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .detailed-timeline {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .detailed-timeline h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
            margin-top: 5px;
            flex-shrink: 0;
        }
        
        .timeline-dot.submitted { background: #3498db; }
        .timeline-dot.assigned { background: #f39c12; }
        .timeline-dot.in_progress { background: #e67e22; }
        .timeline-dot.resolved { background: #27ae60; }
        
        .timeline-content h5 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .timeline-content p {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .timeline-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }
        
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-group {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 30px;
            }
            
            .progress-steps::before {
                display: none;
            }
        }
    </style>
</body>
</html>
