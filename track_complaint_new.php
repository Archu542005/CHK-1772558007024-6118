<?php
require_once 'config.php';

$errors = [];
$complaint = null;
$timeline = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = clean_input($_POST['complaint_id']);
    
    // Validation
    if (empty($complaint_id)) {
        $errors[] = "Complaint ID is required";
    }
    
    // If no errors, fetch complaint details
    if (empty($errors)) {
        try {
            // Main complaint query with auto-routing information
            $query = "SELECT c.*, 
                     CASE 
                        WHEN c.auto_assigned_department IS NOT NULL THEN 
                            (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.auto_assigned_department)
                        ELSE 
                            (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.category)
                     END as department_name,
                     CASE 
                        WHEN c.auto_assigned_department IS NOT NULL THEN c.auto_assigned_department
                        ELSE c.category
                     END as final_department
                     FROM complaints c 
                     WHERE c.complaint_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $complaint_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $complaint = $result->fetch_assoc();
                
                // Fetch complaint timeline
                $timeline_query = "SELECT ct.*, 
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
                
                $timeline_stmt = $conn->prepare($timeline_query);
                $timeline_stmt->bind_param("s", $complaint_id);
                $timeline_stmt->execute();
                $timeline_result = $timeline_stmt->get_result();
                
                while ($row = $timeline_result->fetch_assoc()) {
                    $timeline[] = $row;
                }
                
                $timeline_stmt->close();
                
            } else {
                $errors[] = "Invalid Complaint ID";
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle AJAX request for real-time tracking
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $complaint_id = clean_input($_GET['complaint_id'] ?? '');
    
    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
        exit;
    }
    
    try {
        $query = "SELECT c.*, 
                 CASE 
                    WHEN c.auto_assigned_department IS NOT NULL THEN 
                        (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.auto_assigned_department)
                    ELSE 
                        (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.category)
                 END as department_name,
                 CASE 
                    WHEN c.auto_assigned_department IS NOT NULL THEN c.auto_assigned_department
                    ELSE c.category
                 END as final_department
                 FROM complaints c 
                 WHERE c.complaint_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $complaint = $result->fetch_assoc();
            
            // Get timeline
            $timeline_query = "SELECT ct.*, 
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
            
            $timeline_stmt = $conn->prepare($timeline_query);
            $timeline_stmt->bind_param("s", $complaint_id);
            $timeline_stmt->execute();
            $timeline_result = $timeline_stmt->get_result();
            
            $timeline = [];
            while ($row = $timeline_result->fetch_assoc()) {
                $timeline[] = $row;
            }
            $timeline_stmt->close();
            
            echo json_encode([
                'success' => true,
                'complaint' => $complaint,
                'timeline' => $timeline
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Complaint ID']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
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
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-city"></i>
                <span>Smart Grievance Portal</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="register.html" class="nav-link">Register</a></li>
                <li><a href="login.html" class="nav-link">Login</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint_new.php" class="nav-link active">Track Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link">Admin Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Track Complaint Section -->
    <section class="track-section">
        <div class="container">
            <div class="track-container">
                <div class="form-header">
                    <h2><i class="fas fa-search"></i> Track Your Complaint</h2>
                    <p>Enter your Complaint ID to check the current status and progress</p>
                </div>
                
                <!-- Search Form -->
                <form method="POST" id="trackForm" class="track-form">
                    <div class="search-box">
                        <div class="input-group">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" 
                                   id="complaintId" 
                                   name="complaint_id" 
                                   placeholder="Enter Complaint ID (e.g., CMP-2024-001)" 
                                   required
                                   value="<?php echo isset($_POST['complaint_id']) ? htmlspecialchars($_POST['complaint_id']) : ''; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Track
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h4>Error</h4>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($complaint): ?>
                    <!-- Complaint Details -->
                    <div class="complaint-details">
                        <div class="details-header">
                            <h3><i class="fas fa-clipboard-list"></i> Complaint Details</h3>
                            <button class="btn btn-outline btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        
                        <div class="details-grid">
                            <div class="detail-item">
                                <label><i class="fas fa-hashtag"></i> Complaint ID</label>
                                <span class="complaint-id"><?php echo htmlspecialchars($complaint['complaint_id']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-user"></i> Name</label>
                                <span><?php echo htmlspecialchars($complaint['name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <span><?php echo htmlspecialchars($complaint['email']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-phone"></i> Mobile</label>
                                <span><?php echo htmlspecialchars($complaint['mobile']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-building"></i> Department</label>
                                <span><?php echo htmlspecialchars($complaint['department_name']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-map-marker-alt"></i> Location</label>
                                <span><?php echo htmlspecialchars($complaint['location']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-exclamation-triangle"></i> Priority</label>
                                <span class="priority-badge <?php echo $complaint['priority']; ?>">
                                    <?php echo ucfirst($complaint['priority']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <label><i class="fas fa-info-circle"></i> Status</label>
                                <span class="status-badge <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item full-width">
                                <label><i class="fas fa-home"></i> Address</label>
                                <span><?php echo htmlspecialchars($complaint['address']); ?></span>
                            </div>
                            
                            <div class="detail-item full-width">
                                <label><i class="fas fa-comment"></i> Description</label>
                                <span class="description"><?php echo htmlspecialchars($complaint['description']); ?></span>
                            </div>
                            
                            <?php if ($complaint['image_path']): ?>
                            <div class="detail-item full-width">
                                <label><i class="fas fa-image"></i> Attached Image</label>
                                <div class="image-container">
                                    <img src="<?php echo htmlspecialchars($complaint['image_path']); ?>" 
                                         alt="Complaint Image" 
                                         onclick="openImageModal(this.src)">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($complaint['admin_notes']): ?>
                            <div class="detail-item full-width">
                                <label><i class="fas fa-sticky-note"></i> Admin Notes</label>
                                <span class="admin-notes"><?php echo htmlspecialchars($complaint['admin_notes']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Progress Timeline -->
                    <div class="progress-timeline">
                        <h3><i class="fas fa-clock"></i> Complaint Progress</h3>
                        
                        <div class="timeline-container">
                            <?php 
                            $status_steps = ['submitted', 'assigned', 'in_progress', 'resolved'];
                            $current_status_index = array_search($complaint['status'], $status_steps);
                            
                            foreach ($status_steps as $index => $step): 
                                $is_completed = $index <= $current_status_index;
                                $is_current = $index == $current_status_index;
                                $step_info = getStepInfo($step, $complaint, $timeline);
                            ?>
                                <div class="timeline-step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="fas <?php echo $step_info['icon']; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h4><?php echo $step_info['title']; ?></h4>
                                        <p><?php echo $step_info['description']; ?></p>
                                        <?php if ($step_info['date']): ?>
                                            <small class="step-date"><?php echo $step_info['date']; ?></small>
                                        <?php endif; ?>
                                        <?php if ($step_info['notes']): ?>
                                            <p class="step-notes"><?php echo $step_info['notes']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Detailed Timeline -->
                        <?php if (!empty($timeline)): ?>
                            <div class="detailed-timeline">
                                <h4><i class="fas fa-history"></i> Detailed Timeline</h4>
                                <div class="timeline-events">
                                    <?php foreach ($timeline as $event): ?>
                                        <div class="timeline-event">
                                            <div class="event-dot <?php echo $event['action']; ?>"></div>
                                            <div class="event-content">
                                                <h5><?php echo ucfirst(str_replace('_', ' ', $event['action'])); ?></h5>
                                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                                <div class="event-meta">
                                                    <span class="event-date">
                                                        <i class="fas fa-calendar"></i> 
                                                        <?php echo date('M d, Y h:i A', strtotime($event['performed_at'])); ?>
                                                    </span>
                                                    <span class="event-performer">
                                                        <i class="fas fa-user"></i> 
                                                        <?php echo htmlspecialchars($event['performer_name']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="track_complaint_new.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Track Another Complaint
                        </a>
                        <a href="submit_complaint.html" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Submit New Complaint
                        </a>
                        <button class="btn btn-outline" onclick="shareComplaint()">
                            <i class="fas fa-share"></i> Share
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modalImage" src="" alt="Complaint Image">
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Smart Grievance Portal</h3>
                    <p>Making governance accessible and transparent for every citizen.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="register.html">Register</a></li>
                        <li><a href="login.html">Login</a></li>
                        <li><a href="submit_complaint.html">Submit Complaint</a></li>
                        <li><a href="track_complaint_new.php">Track Complaint</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-phone"></i> Helpline: 1800-123-4567</p>
                    <p><i class="fas fa-envelope"></i> support@grievance.gov.in</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Smart Grievance Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <script>
        // Auto-refresh complaint status every 30 seconds
        let refreshInterval;
        
        function startAutoRefresh() {
            refreshInterval = setInterval(function() {
                const complaintId = document.querySelector('.complaint-id');
                if (complaintId) {
                    refreshComplaintStatus(complaintId.textContent);
                }
            }, 30000);
        }
        
        function refreshComplaintStatus(complaintId) {
            fetch(`track_complaint_new.php?ajax=1&complaint_id=${encodeURIComponent(complaintId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateComplaintDisplay(data.complaint, data.timeline);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing status:', error);
                });
        }
        
        function updateComplaintDisplay(complaint, timeline) {
            // Update status badge
            const statusBadge = document.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge ${complaint.status.replace('_', '-')}`;
                statusBadge.textContent = complaint.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            
            // Show notification if status changed
            console.log('Complaint status refreshed');
        }
        
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'block';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        function shareComplaint() {
            const complaintId = document.querySelector('.complaint-id');
            if (complaintId) {
                const shareText = `Track my complaint: ${complaintId.textContent} on Smart Grievance Portal`;
                
                if (navigator.share) {
                    navigator.share({
                        title: 'Complaint Tracking',
                        text: shareText,
                        url: window.location.href
                    });
                } else {
                    // Fallback: copy to clipboard
                    navigator.clipboard.writeText(window.location.href).then(function() {
                        alert('Complaint tracking link copied to clipboard!');
                    });
                }
            }
        }
        
        // Format complaint ID input
        document.getElementById('complaintId').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            // Auto-format to CMP-YYYY-NNN pattern
            if (value.match(/^CMP\d{4}$/)) {
                value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-';
            } else if (value.match(/^CMP-\d{4}$/)) {
                value = value + '-';
            }
            e.target.value = value;
        });
        
        // Start auto-refresh if complaint is displayed
        <?php if ($complaint): ?>
            startAutoRefresh();
        <?php endif; ?>
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <?php
    // Helper function to get step information
    function getStepInfo($step, $complaint, $timeline) {
        $steps = [
            'submitted' => [
                'icon' => 'fa-paper-plane',
                'title' => 'Complaint Submitted',
                'description' => 'Your complaint has been successfully submitted to the system.'
            ],
            'assigned' => [
                'icon' => 'fa-user-check',
                'title' => 'Assigned to Department',
                'description' => 'Complaint has been assigned to the relevant department for processing.'
            ],
            'in_progress' => [
                'icon' => 'fa-spinner',
                'title' => 'In Progress',
                'description' => 'Department is actively working on resolving your complaint.'
            ],
            'resolved' => [
                'icon' => 'fa-check-circle',
                'title' => 'Resolved',
                'description' => 'Your complaint has been successfully resolved.'
            ]
        ];
        
        $step_info = $steps[$step] ?? ['icon' => 'fa-circle', 'title' => $step, 'description' => ''];
        
        // Find relevant timeline event for this step
        foreach ($timeline as $event) {
            if ($event['action'] === $step || ($step === 'assigned' && $event['action'] === 'assigned')) {
                $step_info['date'] = date('M d, Y h:i A', strtotime($event['performed_at']));
                $step_info['notes'] = $event['description'];
                break;
            }
        }
        
        // Use complaint dates as fallback
        if (!isset($step_info['date'])) {
            if ($step === 'submitted') {
                $step_info['date'] = date('M d, Y h:i A', strtotime($complaint['created_at']));
            } elseif ($step === 'resolved' && $complaint['resolved_at']) {
                $step_info['date'] = date('M d, Y h:i A', strtotime($complaint['resolved_at']));
            }
        }
        
        return $step_info;
    }
    ?>
    
    <style>
        .track-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .track-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .track-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .search-box {
            text-align: center;
        }
        
        .input-group {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
            border: 2px solid #3498db;
            border-radius: 50px;
            overflow: hidden;
        }
        
        .input-group i {
            padding: 15px 20px;
            color: #3498db;
            background: #f8f9fa;
        }
        
        .input-group input {
            flex: 1;
            padding: 15px;
            border: none;
            outline: none;
            font-size: 16px;
        }
        
        .input-group button {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .input-group button:hover {
            background: #2980b9;
        }
        
        .alert {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-icon {
            font-size: 1.5rem;
        }
        
        .complaint-details {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        .detail-item label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-item span {
            color: #495057;
            font-size: 1rem;
        }
        
        .complaint-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .priority-badge, .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
            display: inline-block;
        }
        
        .priority-badge.normal { background: #95a5a6; }
        .priority-badge.high { background: #f39c12; }
        .priority-badge.urgent { background: #e74c3c; }
        
        .status-badge.pending { background: #95a5a6; }
        .status-badge.in-progress { background: #f39c12; }
        .status-badge.resolved { background: #27ae60; }
        .status-badge.escalated { background: #e74c3c; }
        
        .description, .admin-notes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .image-container {
            margin-top: 10px;
        }
        
        .image-container img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .image-container img:hover {
            transform: scale(1.05);
        }
        
        .progress-timeline {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .timeline-container {
            margin: 30px 0;
        }
        
        .timeline-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }
        
        .timeline-step::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 50px;
            bottom: -30px;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-step:last-child::before {
            display: none;
        }
        
        .timeline-step.completed::before {
            background: #27ae60;
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            z-index: 1;
        }
        
        .timeline-step.completed .step-icon {
            background: #27ae60;
            color: white;
        }
        
        .timeline-step.current .step-icon {
            background: #3498db;
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }
        
        .step-content h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .step-content p {
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .step-date {
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        .step-notes {
            color: #495057;
            font-style: italic;
            margin-top: 10px;
        }
        
        .detailed-timeline {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }
        
        .timeline-events {
            margin-top: 20px;
        }
        
        .timeline-event {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-left: 20px;
        }
        
        .event-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3498db;
            margin-right: 15px;
            margin-top: 5px;
            flex-shrink: 0;
        }
        
        .event-dot.submitted { background: #3498db; }
        .event-dot.assigned { background: #f39c12; }
        .event-dot.in-progress { background: #e67e22; }
        .event-dot.resolved { background: #27ae60; }
        
        .event-content h5 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .event-content p {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .event-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            position: relative;
            margin: 5% auto;
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-content img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #bbb;
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .input-group {
                flex-direction: column;
                border-radius: 10px;
            }
            
            .input-group i {
                padding: 10px;
                background: none;
            }
            
            .timeline-step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-icon {
                margin: 0 auto 15px;
            }
            
            .timeline-step::before {
                left: 50%;
                transform: translateX(-50%);
                top: 50px;
            }
            
            .event-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</body>
</html>
