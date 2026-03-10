<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get complaint details
function getComplaintDetails($complaint_id) {
    global $conn;
    
    // Simple, direct query
    $query = "SELECT * FROM complaints WHERE complaint_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo "Query preparation failed: " . $conn->error;
        return null;
    }
    
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get timeline
function getComplaintTimeline($complaint_id) {
    global $conn;
    
    $query = "SELECT * FROM complaint_timeline WHERE complaint_id = ? ORDER BY performed_at ASC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $timeline = [];
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    return $timeline;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['track_complaint'])) {
    $complaint_id = trim($_POST['complaint_id']);
    
    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a Complaint ID']);
        exit;
    }
    
    $complaint = getComplaintDetails($complaint_id);
    
    if ($complaint) {
        $timeline = getComplaintTimeline($complaint_id);
        echo json_encode([
            'success' => true,
            'complaint' => $complaint,
            'timeline' => $timeline
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
    }
    exit;
}

// Get sample complaints for testing
function getSampleComplaints() {
    global $conn;
    
    $query = "SELECT complaint_id, name, status, created_at FROM complaints ORDER BY created_at DESC LIMIT 5";
    $result = $conn->query($query);
    
    if (!$result) {
        // Query failed, return empty array
        return [];
    }
    
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    
    return $complaints;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Tracking - Working Version</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .tracking-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-top: 50px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .search-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .input-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .input-group input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .input-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            display: none;
        }
        
        .loading.show {
            display: block;
        }
        
        .loading i {
            font-size: 2rem;
            color: #667eea;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .result-container {
            display: none;
        }
        
        .result-container.show {
            display: block;
        }
        
        .complaint-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e8ed;
        }
        
        .complaint-id {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        
        .status-pending { background: #95a5a6; }
        .status-in_progress { background: #f39c12; }
        .status-resolved { background: #27ae60; }
        .status-escalated { background: #e74c3c; }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .detail-value {
            color: #2c3e50;
            font-size: 16px;
        }
        
        .progress-timeline {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .progress-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 20px;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e1e8ed;
            z-index: 1;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e1e8ed;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .step-icon.completed {
            background: #27ae60;
            color: white;
        }
        
        .step-icon.current {
            background: #667eea;
            color: white;
            animation: pulse 2s infinite;
        }
        
        .step-label {
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
            max-width: 100px;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }
        
        .timeline-events {
            background: white;
            padding: 30px;
            border-radius: 15px;
        }
        
        .timeline-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .timeline-event {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            position: relative;
        }
        
        .timeline-event::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 40px;
            bottom: -25px;
            width: 2px;
            background: #e1e8ed;
        }
        
        .timeline-event:last-child::before {
            display: none;
        }
        
        .event-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            flex-shrink: 0;
            margin-top: 5px;
        }
        
        .event-content {
            flex: 1;
        }
        
        .event-action {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .event-description {
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .event-time {
            font-size: 12px;
            color: #95a5a6;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .sample-complaints {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .sample-title {
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 15px;
        }
        
        .sample-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sample-btn {
            background: white;
            border: 1px solid #1976d2;
            color: #1976d2;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .sample-btn:hover {
            background: #1976d2;
            color: white;
        }
        
        @media (max-width: 768px) {
            .tracking-card {
                padding: 20px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group input,
            .input-group button {
                width: 100%;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 30px;
            }
            
            .progress-steps::before {
                display: none;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tracking-card">
            <div class="header">
                <h1><i class="fas fa-search"></i> Track Your Complaint</h1>
                <p>Enter your Complaint ID to check the current status and progress</p>
            </div>
            
            <!-- Sample Complaints -->
            <div class="sample-complaints">
                <div class="sample-title">📋 Sample Complaint IDs for Testing:</div>
                <div class="sample-list">
                    <?php
                    $samples = getSampleComplaints();
                    if (!empty($samples)) {
                        foreach ($samples as $sample) {
                            echo '<button class="sample-btn" onclick="testTrack(\'' . htmlspecialchars($sample['complaint_id']) . '\')">' . htmlspecialchars($sample['complaint_id']) . '</button>';
                        }
                    } else {
                        echo '<span style="color: #666;">No sample complaints available. Submit a complaint first.</span>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Search Form -->
            <div class="search-form">
                <form id="trackingForm">
                    <div class="input-group">
                        <input type="text" 
                               id="complaintId" 
                               placeholder="Enter Complaint ID (e.g., CMP-2024-001)" 
                               required>
                        <button type="submit">
                            <i class="fas fa-search"></i> Track Complaint
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Loading -->
            <div id="loading" class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Searching for your complaint...</p>
            </div>
            
            <!-- Error Message -->
            <div id="errorMessage" class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p id="errorText">Complaint not found. Please check the Complaint ID and try again.</p>
            </div>
            
            <!-- Results -->
            <div id="resultContainer" class="result-container">
                <!-- Complaint Details -->
                <div class="complaint-details">
                    <div class="complaint-header">
                        <div class="complaint-id" id="complaintIdDisplay">CMP-2024-001</div>
                        <div class="status-badge" id="statusBadge">Pending</div>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Name</div>
                            <div class="detail-value" id="complainantName">John Doe</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="complainantEmail">john@example.com</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Mobile</div>
                            <div class="detail-value" id="complainantMobile">1234567890</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Category</div>
                            <div class="detail-value" id="complaintCategory">Garbage</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value" id="complaintLocation">Main Street</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Priority</div>
                            <div class="detail-value" id="complaintPriority">Normal</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Submitted</div>
                            <div class="detail-value" id="submittedDate">2024-01-01</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value" id="lastUpdated">2024-01-01</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" id="complaintDescription">This is a sample complaint description.</div>
                    </div>
                </div>
                
                <!-- Progress Timeline -->
                <div class="progress-timeline">
                    <div class="progress-title">📊 Progress Status</div>
                    <div class="progress-steps">
                        <div class="progress-step">
                            <div class="step-icon" id="step1">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="step-label">Submitted</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon" id="step2">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="step-label">Assigned</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon" id="step3">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="step-label">In Progress</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon" id="step4">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-label">Resolved</div>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline Events -->
                <div class="timeline-events">
                    <div class="timeline-title">📋 Activity Timeline</div>
                    <div id="timelineEvents">
                        <!-- Timeline events will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testTrack(complaintId) {
            document.getElementById('complaintId').value = complaintId;
            document.getElementById('trackingForm').dispatchEvent(new Event('submit'));
        }

        document.getElementById('trackingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const complaintId = document.getElementById('complaintId').value.trim();
            
            if (!complaintId) {
                showError('Please enter a Complaint ID');
                return;
            }
            
            // Show loading
            showLoading();
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('track_complaint', '1');
            formData.append('complaint_id', complaintId);
            
            fetch('track_working.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showResults(data.complaint, data.timeline);
                } else {
                    showError(data.message || 'Complaint not found');
                }
            })
            .catch(error => {
                hideLoading();
                showError('Error: ' + error.message);
            });
        });
        
        function showLoading() {
            document.getElementById('loading').classList.add('show');
            document.getElementById('errorMessage').classList.remove('show');
            document.getElementById('resultContainer').classList.remove('show');
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.remove('show');
        }
        
        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').classList.add('show');
            document.getElementById('resultContainer').classList.remove('show');
        }
        
        function showResults(complaint, timeline) {
            // Update complaint details
            document.getElementById('complaintIdDisplay').textContent = complaint.complaint_id;
            document.getElementById('complainantName').textContent = complaint.name;
            document.getElementById('complainantEmail').textContent = complaint.email;
            document.getElementById('complainantMobile').textContent = complaint.mobile;
            document.getElementById('complaintCategory').textContent = complaint.category;
            document.getElementById('complaintLocation').textContent = complaint.location;
            document.getElementById('complaintPriority').textContent = complaint.priority;
            document.getElementById('submittedDate').textContent = new Date(complaint.created_at).toLocaleDateString();
            document.getElementById('lastUpdated').textContent = new Date(complaint.updated_at || complaint.created_at).toLocaleDateString();
            document.getElementById('complaintDescription').textContent = complaint.description;
            
            // Update status badge
            const statusBadge = document.getElementById('statusBadge');
            statusBadge.textContent = complaint.status.replace('_', ' ').toUpperCase();
            statusBadge.className = 'status-badge status-' + complaint.status;
            
            // Update progress steps
            updateProgressSteps(complaint.status);
            
            // Update timeline
            updateTimeline(timeline);
            
            // Show results
            document.getElementById('errorMessage').classList.remove('show');
            document.getElementById('resultContainer').classList.add('show');
        }
        
        function updateProgressSteps(status) {
            const steps = ['pending', 'assigned', 'in_progress', 'resolved'];
            const currentStepIndex = steps.indexOf(status);
            
            for (let i = 1; i <= 4; i++) {
                const stepIcon = document.getElementById('step' + i);
                stepIcon.className = 'step-icon';
                
                if (i <= currentStepIndex + 1) {
                    stepIcon.classList.add('completed');
                }
                if (i === currentStepIndex + 1) {
                    stepIcon.classList.add('current');
                }
            }
        }
        
        function updateTimeline(timeline) {
            const timelineEvents = document.getElementById('timelineEvents');
            timelineEvents.innerHTML = '';
            
            if (timeline.length === 0) {
                timelineEvents.innerHTML = '<p style="text-align: center; color: #7f8c8d;">No timeline events available.</p>';
                return;
            }
            
            timeline.forEach(event => {
                const eventDiv = document.createElement('div');
                eventDiv.className = 'timeline-event';
                eventDiv.innerHTML = `
                    <div class="event-dot"></div>
                    <div class="event-content">
                        <div class="event-action">${event.action}</div>
                        <div class="event-description">${event.description}</div>
                        <div class="event-time">${new Date(event.performed_at).toLocaleString()}</div>
                    </div>
                `;
                timelineEvents.appendChild(eventDiv);
            });
        }
    </script>
</body>
</html>
