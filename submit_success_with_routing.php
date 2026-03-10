<?php
require_once 'config.php';

// Check if complaint data exists in session
if (!isset($_SESSION['complaint_data'])) {
    redirect('submit_complaint.html');
}

$complaint_data = $_SESSION['complaint_data'];
$complaint_id = $complaint_data['complaint_id'];
$assigned_department = $complaint_data['assigned_department'];
$routing_confidence = $complaint_data['routing_confidence'];
$routing_method = $complaint_data['routing_method'];
$matched_keywords = $complaint_data['matched_keywords'];

// Clear complaint data from session
unset($_SESSION['complaint_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Submitted - Smart Grievance Portal</title>
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
                <li><a href="user_dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint.html" class="nav-link">Track Complaint</a></li>
                <li><a href="profile.php" class="nav-link">My Account</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Success Section -->
    <section class="success-section">
        <div class="container">
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1>Complaint Submitted Successfully!</h1>
                <p>Your complaint has been registered and automatically assigned to the appropriate department.</p>
                
                <!-- Complaint Details -->
                <div class="complaint-details">
                    <div class="detail-card">
                        <h3><i class="fas fa-hashtag"></i> Complaint ID</h3>
                        <div class="detail-value">
                            <span id="complaintId"><?php echo htmlspecialchars($complaint_id); ?></span>
                            <button class="copy-btn" onclick="copyComplaintId()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h3><i class="fas fa-building"></i> Assigned Department</h3>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($assigned_department); ?>
                            <span class="confidence-badge" style="margin-left: 10px;">
                                <?php echo $routing_confidence; ?>% confidence
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h3><i class="fas fa-robot"></i> Routing Method</h3>
                        <div class="detail-value">
                            <?php 
                            $method_labels = [
                                'keyword' => 'Keyword Analysis',
                                'category' => 'Category Selection',
                                'manual' => 'Manual Assignment'
                            ];
                            echo $method_labels[$routing_method] ?? 'Unknown';
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($matched_keywords)): ?>
                    <div class="detail-card">
                        <h3><i class="fas fa-tags"></i> Matched Keywords</h3>
                        <div class="detail-value">
                            <div class="keywords-container">
                                <?php foreach ($matched_keywords as $keyword): ?>
                                    <span class="keyword-tag"><?php echo htmlspecialchars($keyword); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Next Steps -->
                <div class="next-steps">
                    <h3><i class="fas fa-info-circle"></i> What Happens Next?</h3>
                    <div class="steps-grid">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Department Review</h4>
                                <p><?php echo htmlspecialchars($assigned_department); ?> will review your complaint within 24 hours.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Status Update</h4>
                                <p>You'll receive notifications when the status changes.</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Resolution</h4>
                                <p>Your complaint will be resolved within the stipulated timeframe.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="track_complaint.html?id=<?php echo urlencode($complaint_id); ?>" class="btn btn-primary">
                        <i class="fas fa-search"></i> Track Complaint
                    </a>
                    <a href="submit_complaint.html" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Submit Another Complaint
                    </a>
                    <a href="user_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
                
                <!-- Contact Info -->
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> For urgent issues: 1800-123-4567</p>
                    <p><i class="fas fa-envelope"></i> Email: support@grievance.gov.in</p>
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
                        <li><a href="user_dashboard.php">Dashboard</a></li>
                        <li><a href="submit_complaint.html">Submit Complaint</a></li>
                        <li><a href="track_complaint.html">Track Complaint</a></li>
                        <li><a href="profile.php">My Account</a></li>
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
        function copyComplaintId() {
            const complaintId = document.getElementById('complaintId').textContent;
            
            navigator.clipboard.writeText(complaintId).then(function() {
                // Show success message
                const copyBtn = document.querySelector('.copy-btn');
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                copyBtn.style.background = '#27ae60';
                
                setTimeout(function() {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.style.background = '';
                }, 2000);
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = complaintId;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show success message
                const copyBtn = document.querySelector('.copy-btn');
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(function() {
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
                }, 2000);
            });
        }
        
        // Auto-redirect to dashboard after 10 seconds
        let seconds = 10;
        const countdownElement = document.createElement('p');
        countdownElement.innerHTML = `<i class="fas fa-clock"></i> Redirecting to dashboard in <span id="countdown">${seconds}</span> seconds...`;
        countdownElement.style.cssText = 'text-align: center; color: #7f8c8d; margin-top: 20px;';
        
        document.querySelector('.action-buttons').appendChild(countdownElement);
        
        const countdown = setInterval(function() {
            seconds--;
            document.getElementById('countdown').textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'user_dashboard.php';
            }
        }, 1000);
    </script>
    
    <style>
        .success-section {
            padding: 100px 0 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .success-container h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .success-container p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .complaint-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: left;
        }
        
        .detail-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .copy-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #2980b9;
        }
        
        .confidence-badge {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .keywords-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .keyword-tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        
        .next-steps {
            margin-bottom: 30px;
        }
        
        .next-steps h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .step-number {
            background: #3498db;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .step-content h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .step-content p {
            color: #7f8c8d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        
        .contact-info p {
            color: #7f8c8d;
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 20px;
            }
            
            .complaint-details {
                grid-template-columns: 1fr;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
