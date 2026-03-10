<?php
require_once 'config.php';

// Simple tracking function for homepage
function trackComplaintSimple($complaint_id) {
    global $conn;
    
    // Basic query without complex joins
    $query = "SELECT * FROM complaints WHERE complaint_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Handle AJAX request for tracking
if (isset($_POST['track_complaint'])) {
    $complaint_id = clean_input($_POST['complaint_id']);
    $complaint = trackComplaintSimple($complaint_id);
    
    if ($complaint) {
        echo json_encode([
            'success' => true,
            'complaint' => $complaint
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Complaint ID'
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Complaint - Homepage</title>
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
                <li><a href="track_on_homepage.php" class="nav-link active">Track Complaint</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link">Admin Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="hero-track">
        <div class="container">
            <div class="track-hero">
                <h1><i class="fas fa-search"></i> Track Your Complaint</h1>
                <p>Enter your Complaint ID to check the current status and progress</p>
                
                <!-- Simple Tracking Form -->
                <div class="track-form-container">
                    <form id="simpleTrackForm">
                        <div class="input-group">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" 
                                   id="complaintIdInput" 
                                   placeholder="Enter Complaint ID (e.g., CMP-2024-001)" 
                                   required>
                            <button type="submit">
                                <i class="fas fa-search"></i> Track
                            </button>
                        </div>
                    </form>
                    
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" style="display: none; text-align: center; margin: 20px 0;">
                        <i class="fas fa-spinner fa-spin"></i> Searching...
                    </div>
                    
                    <!-- Results Container -->
                    <div id="trackResults" style="display: none;">
                        <!-- Results will be loaded here -->
                    </div>
                </div>
                
                <!-- Sample IDs for Testing -->
                <div class="sample-ids">
                    <h4>Sample Complaint IDs for Testing:</h4>
                    <div class="sample-buttons">
                        <button class="sample-btn" onclick="testTrack('CMP-2024-001')">CMP-2024-001</button>
                        <button class="sample-btn" onclick="testTrack('CMP-2024-002')">CMP-2024-002</button>
                        <button class="sample-btn" onclick="testTrack('TEST-001')">TEST-001</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                        <li><a href="index.html">Home</a></li>
                        <li><a href="track_on_homepage.php">Track Complaint</a></li>
                        <li><a href="submit_complaint.html">Submit Complaint</a></li>
                        <li><a href="admin_login.html">Admin Login</a></li>
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

    <script>
        function testTrack(complaintId) {
            document.getElementById('complaintIdInput').value = complaintId;
            document.getElementById('simpleTrackForm').dispatchEvent(new Event('submit'));
        }

        document.getElementById('simpleTrackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const complaintId = document.getElementById('complaintIdInput').value.trim();
            
            if (!complaintId) {
                alert('Please enter a Complaint ID');
                return;
            }
            
            // Show loading
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('trackResults').style.display = 'none';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('track_complaint', '1');
            formData.append('complaint_id', complaintId);
            
            fetch('track_on_homepage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingIndicator').style.display = 'none';
                
                if (data.success) {
                    displayComplaintResults(data.complaint);
                } else {
                    displayError(data.message);
                }
            })
            .catch(error => {
                document.getElementById('loadingIndicator').style.display = 'none';
                displayError('Error: ' + error.message);
            });
        });
        
        function displayComplaintResults(complaint) {
            const resultsHtml = `
                <div class="complaint-result">
                    <div class="result-header">
                        <h3><i class="fas fa-check-circle" style="color: green;"></i> Complaint Found</h3>
                    </div>
                    <div class="result-details">
                        <div class="detail-row">
                            <strong>Complaint ID:</strong> ${complaint.complaint_id}
                        </div>
                        <div class="detail-row">
                            <strong>Name:</strong> ${complaint.name}
                        </div>
                        <div class="detail-row">
                            <strong>Email:</strong> ${complaint.email}
                        </div>
                        <div class="detail-row">
                            <strong>Mobile:</strong> ${complaint.mobile}
                        </div>
                        <div class="detail-row">
                            <strong>Category:</strong> ${complaint.category}
                        </div>
                        <div class="detail-row">
                            <strong>Location:</strong> ${complaint.location}
                        </div>
                        <div class="detail-row">
                            <strong>Priority:</strong> <span class="priority-badge ${complaint.priority}">${complaint.priority}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Status:</strong> <span class="status-badge ${complaint.status}">${complaint.status.replace('_', ' ')}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong> ${complaint.description}
                        </div>
                        <div class="detail-row">
                            <strong>Submitted:</strong> ${new Date(complaint.created_at).toLocaleString()}
                        </div>
                    </div>
                    <div class="result-actions">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-secondary" onclick="clearResults()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('trackResults').innerHTML = resultsHtml;
            document.getElementById('trackResults').style.display = 'block';
        }
        
        function displayError(message) {
            const resultsHtml = `
                <div class="complaint-result error">
                    <div class="result-header">
                        <h3><i class="fas fa-exclamation-triangle" style="color: red;"></i> Error</h3>
                    </div>
                    <div class="error-message">
                        <p>${message}</p>
                        <p>Please check the Complaint ID and try again.</p>
                    </div>
                    <div class="result-actions">
                        <button class="btn btn-secondary" onclick="clearResults()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('trackResults').innerHTML = resultsHtml;
            document.getElementById('trackResults').style.display = 'block';
        }
        
        function clearResults() {
            document.getElementById('trackResults').style.display = 'none';
            document.getElementById('complaintIdInput').value = '';
            document.getElementById('complaintIdInput').focus();
        }
    </script>
    
    <style>
        .hero-track {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 100px 0 50px;
            min-height: 100vh;
        }
        
        .track-hero {
            text-align: center;
            color: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .track-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .track-hero p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .track-form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .input-group {
            display: flex;
            align-items: center;
            border: 2px solid #3498db;
            border-radius: 50px;
            overflow: hidden;
            background: white;
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
        
        .complaint-result {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .complaint-result.error {
            border-left: 4px solid #e74c3c;
        }
        
        .result-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .result-header h3 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row strong {
            color: #2c3e50;
            min-width: 150px;
            text-align: left;
        }
        
        .priority-badge, .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
        }
        
        .priority-badge.normal, .status-badge.pending { background: #95a5a6; }
        .priority-badge.high, .status-badge.in_progress { background: #f39c12; }
        .priority-badge.urgent { background: #e74c3c; }
        .status-badge.resolved { background: #27ae60; }
        
        .error-message {
            color: #e74c3c;
            padding: 20px;
            text-align: center;
        }
        
        .result-actions {
            margin-top: 20px;
            text-align: center;
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
            margin: 0 5px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .sample-ids {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .sample-ids h4 {
            color: white;
            margin-bottom: 15px;
        }
        
        .sample-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sample-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sample-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .track-hero h1 {
                font-size: 2rem;
            }
            
            .input-group {
                flex-direction: column;
                border-radius: 10px;
            }
            
            .input-group i {
                padding: 10px;
                background: none;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .detail-row strong {
                min-width: auto;
            }
        }
    </style>
</body>
</html>
