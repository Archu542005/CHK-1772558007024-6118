<?php
require_once 'config.php';

// All functions (clean_input, get_complaint_details, get_complaint_timeline) are already in config.php

$errors = [];
$complaint = null;
$timeline = [];

// Handle both GET and POST
$complaint_id = '';
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['complaintId'])) {
    $complaint_id = clean_input($_GET['complaintId']);
    // Debug: Log the received complaint ID
    error_log("Track.php: Received complaint ID: " . $complaint_id);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = clean_input($_POST['complaint_id']);
    error_log("Track.php: Received POST complaint ID: " . $complaint_id);
}

if (!empty($complaint_id)) {
    // Validation
    if (empty($complaint_id)) {
        $errors[] = "Complaint ID is required";
    } else {
        // Get complaint details (function from config.php)
        $complaint = get_complaint_details($complaint_id);
        
        if (!$complaint) {
            $errors[] = "Complaint not found";
            error_log("Track.php: Complaint not found: " . $complaint_id);
        } else {
            // Get complaint timeline (function from config.php)
            $timeline = get_complaint_timeline($complaint_id);
            error_log("Track.php: Found complaint: " . $complaint_id);
        }
    }
} else {
    // Debug: Show what was received
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        error_log("Track.php: GET params received: " . json_encode($_GET));
    }
}

// Return JSON response for AJAX requests
if (isset($_GET['ajax']) && !empty($complaint)) {
    header('Content-Type: application/json');
    
    // Format timeline for display
    $formatted_timeline = [];
    foreach ($timeline as $event) {
        $formatted_timeline[] = [
            'action' => $event['action'],
            'description' => $event['description'],
            'performed_at' => date('M d, Y h:i A', strtotime($event['performed_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'complaint' => [
            'complaint_id' => $complaint['complaint_id'],
            'category' => ucfirst(str_replace('_', ' ', $complaint['category'])),
            'description' => $complaint['description'],
            'location' => $complaint['location'],
            'status' => ucfirst(str_replace('_', ' ', $complaint['status'])),
            'priority' => ucfirst($complaint['priority']),
            'created_at' => date('M d, Y h:i A', strtotime($complaint['created_at'])),
            'updated_at' => date('M d, Y h:i A', strtotime($complaint['updated_at'])),
            'resolved_at' => $complaint['resolved_at'] ? date('M d, Y h:i A', strtotime($complaint['resolved_at'])) : null,
            'image_path' => $complaint['image_path']
        ],
        'timeline' => $formatted_timeline
    ]);
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
                <li><a href="track_complaint.html" class="nav-link active">Track Complaint</a></li>
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
                <div class="track-form">
                    <h2><i class="fas fa-search"></i> Track Your Complaint</h2>
                    <p>Enter your complaint ID to check the current status</p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="trackForm" method="GET">
                        <div class="form-group">
                            <label for="complaintId"><i class="fas fa-hashtag"></i> Complaint ID</label>
                            <input type="text" id="complaintId" name="complaintId" required 
                                   value="<?php echo isset($_GET['complaintId']) ? htmlspecialchars($_GET['complaintId']) : ''; ?>"
                                   placeholder="Enter your complaint ID (e.g., CMP2024001)">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Track Complaint
                        </button>
                    </form>
                    
                    <?php
                    // Show sample complaint IDs
                    $sample_query = "SELECT complaint_id, name, status FROM complaints ORDER BY created_at DESC LIMIT 5";
                    $sample_result = $conn->query($sample_query);
                    if ($sample_result && $sample_result->num_rows > 0):
                    ?>
                        <div class="sample-ids" style="background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;">
                            <h4 style="margin-bottom: 10px; color: #1976d2;">📋 Sample Complaint IDs (Click to test):</h4>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php while ($sample = $sample_result->fetch_assoc()): ?>
                                    <a href="?complaintId=<?php echo urlencode($sample['complaint_id']); ?>" 
                                       style="background: white; border: 1px solid #1976d2; color: #1976d2; padding: 5px 10px; border-radius: 15px; text-decoration: none; font-size: 12px;">
                                        <?php echo htmlspecialchars($sample['complaint_id']); ?> (<?php echo htmlspecialchars($sample['status']); ?>)
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data" style="background: #fff3cd; padding: 15px; border-radius: 10px; margin-top: 20px;">
                            <p style="color: #856404; margin: 0;">
                                <i class="fas fa-info-circle"></i> No complaints found in database. 
                                <a href="create_test_complaint.php" style="color: #856404; text-decoration: underline;">Create test complaints</a>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="track-links">
                        <p><a href="submit_complaint.html">Submit New Complaint</a></p>
                        <p><a href="index.html">Back to Home</a></p>
                    </div>
                </div>
                
                <div class="track-info">
                    <h3>How to Track?</h3>
                    <div class="tracking-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Get Your Complaint ID</h4>
                                <p>You receive a unique ID when you submit a complaint</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Enter the ID</h4>
                                <p>Type your complaint ID in the search box above</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>View Status</h4>
                                <p>Check real-time status and updates</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-guide">
                        <h4>Status Meanings</h4>
                        <div class="status-item">
                            <span class="status-badge pending">Pending</span>
                            <p>Complaint received and under review</p>
                        </div>
                        <div class="status-item">
                            <span class="status-badge in-progress">In Progress</span>
                            <p>Department is working on your complaint</p>
                        </div>
                        <div class="status-item">
                            <span class="status-badge resolved">Resolved</span>
                            <p>Complaint has been successfully resolved</p>
                        </div>
                        <div class="status-item">
                            <span class="status-badge escalated">Escalated</span>
                            <p>Complaint escalated to higher authority</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Complaint Result Section -->
    <?php if ($complaint): ?>
        <section id="complaintResult" class="complaint-result" style="display: block;">
            <div class="container">
                <div class="result-container">
                    <h3><i class="fas fa-clipboard-list"></i> Complaint Details</h3>
                    <div class="complaint-details">
                        <div class="detail-row">
                            <span class="label">Complaint ID:</span>
                            <span class="value"><?php echo htmlspecialchars($complaint['complaint_id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Category:</span>
                            <span class="value"><?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Priority:</span>
                            <span class="value">
                                <span class="priority-badge <?php echo $complaint['priority']; ?>">
                                    <?php echo ucfirst($complaint['priority']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Description:</span>
                            <span class="value"><?php echo htmlspecialchars($complaint['description']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Location:</span>
                            <span class="value"><?php echo htmlspecialchars($complaint['location']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Current Status:</span>
                            <span class="status-badge <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Date Submitted:</span>
                            <span class="value"><?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Last Updated:</span>
                            <span class="value"><?php echo date('M d, Y h:i A', strtotime($complaint['updated_at'])); ?></span>
                        </div>
                        <?php if ($complaint['resolved_at']): ?>
                            <div class="detail-row">
                                <span class="label">Resolved On:</span>
                                <span class="value"><?php echo date('M d, Y h:i A', strtotime($complaint['resolved_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($complaint['image_path']): ?>
                            <div class="detail-row">
                                <span class="label">Attachment:</span>
                                <span class="value">
                                    <a href="uploads/<?php echo htmlspecialchars($complaint['image_path']); ?>" target="_blank" class="view-image-btn">
                                        <i class="fas fa-image"></i> View Image
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($timeline)): ?>
                        <div class="timeline-section">
                            <h4>Complaint Timeline</h4>
                            <div class="timeline">
                                <?php foreach ($timeline as $event): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot <?php echo $event['action']; ?>"></div>
                                        <div class="timeline-content">
                                            <h5><?php echo ucfirst(str_replace('_', ' ', $event['action'])); ?></h5>
                                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                                            <small><?php echo date('M d, Y h:i A', strtotime($event['performed_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="result-actions">
                        <button class="btn btn-primary" onclick="printDetails()">
                            <i class="fas fa-print"></i> Print Details
                        </button>
                        <button class="btn btn-secondary" onclick="trackAnother()">
                            <i class="fas fa-search"></i> Track Another
                        </button>
                        <?php if ($complaint['status'] === 'pending' && is_logged_in()): ?>
                            <a href="edit_complaint.php?id=<?php echo htmlspecialchars($complaint['complaint_id']); ?>" class="btn btn-outline">
                                <i class="fas fa-edit"></i> Edit Complaint
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

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
                        <li><a href="track_complaint.html">Track Complaint</a></li>
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
        function printDetails() {
            window.print();
        }
        
        function trackAnother() {
            window.location.href = 'track_complaint.html';
        }
        
        // Auto-fill complaint ID from URL parameter
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const complaintId = urlParams.get('complaintId');
            if (complaintId) {
                document.getElementById('complaintId').value = complaintId;
            }
        };
        
        // AJAX tracking (optional enhancement)
        document.getElementById('trackForm').addEventListener('submit', function(e) {
            const complaintId = document.getElementById('complaintId').value;
            
            if (complaintId) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                submitBtn.disabled = true;
                
                // Optional: Make AJAX request for faster response
                // For now, we'll let the form submit normally
            }
        });
    </script>
    
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid transparent;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert p {
            margin: 0;
        }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .priority-badge.normal {
            background: #95a5a6;
        }
        
        .priority-badge.high {
            background: #f39c12;
        }
        
        .priority-badge.urgent {
            background: #e74c3c;
        }
        
        .view-image-btn {
            color: #3498db;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #3498db;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .view-image-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .timeline-dot.submitted {
            background: #27ae60;
        }
        
        .timeline-dot.assigned {
            background: #3498db;
        }
        
        .timeline-dot.in-progress {
            background: #f39c12;
        }
        
        .timeline-dot.escalated {
            background: #e74c3c;
        }
        
        .timeline-dot.resolved {
            background: #27ae60;
        }
        
        .timeline-dot.closed {
            background: #95a5a6;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #3498db;
            color: #3498db;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        @media print {
            .navbar,
            .footer,
            .track-section,
            .result-actions {
                display: none;
            }
            
            .complaint-result {
                padding: 0;
            }
            
            .result-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
    
    <script>
        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const trackForm = document.getElementById('trackForm');
            const complaintIdInput = document.getElementById('complaintId');
            
            if (trackForm) {
                trackForm.addEventListener('submit', function(e) {
                    const complaintId = complaintIdInput.value.trim();
                    
                    // Validation
                    if (!complaintId) {
                        e.preventDefault();
                        alert('Please enter a Complaint ID');
                        return;
                    }
                    
                    // Log for debugging
                    console.log('Submitting complaint ID:', complaintId);
                    
                    // Allow form to submit normally (GET method)
                    return true;
                });
            }
            
            // Auto-focus on input field
            if (complaintIdInput) {
                complaintIdInput.focus();
            }
        });
    </script>
</body>
</html>
