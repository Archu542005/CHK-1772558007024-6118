<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.html');
}

// Get success message and complaint ID from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$complaint_id = isset($_SESSION['complaint_id']) ? $_SESSION['complaint_id'] : '';

// Clear session variables
unset($_SESSION['success_message']);
unset($_SESSION['complaint_id']);

// If no complaint ID, redirect to dashboard
if (empty($complaint_id)) {
    redirect('user_dashboard.php');
}
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
                <li><a href="register.html" class="nav-link">Register</a></li>
                <li><a href="login.html" class="nav-link">Login</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint.html" class="nav-link">Track Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link">Admin Login</a></li>
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
                
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
                
                <div class="complaint-id-card">
                    <h3>Your Complaint ID</h3>
                    <div class="complaint-id">
                        <span id="complaintIdDisplay"><?php echo htmlspecialchars($complaint_id); ?></span>
                        <button class="copy-btn" onclick="copyComplaintId()" title="Copy to clipboard">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="note">Please save this ID for future reference</p>
                </div>
                
                <div class="next-steps">
                    <h3>What happens next?</h3>
                    <div class="steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Review Process</h4>
                                <p>Your complaint will be reviewed by the concerned department</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Action Taken</h4>
                                <p>The department will take appropriate action on your complaint</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Status Updates</h4>
                                <p>You'll receive notifications about the status changes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="track_complaint.html?id=<?php echo htmlspecialchars($complaint_id); ?>" class="btn btn-primary">
                        <i class="fas fa-search"></i> Track This Complaint
                    </a>
                    <a href="submit_complaint.html" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Submit Another Complaint
                    </a>
                    <a href="user_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-dashboard"></i> Go to Dashboard
                    </a>
                </div>
                
                <div class="help-section">
                    <h3>Need Help?</h3>
                    <div class="help-options">
                        <div class="help-option">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>Helpline</h4>
                                <p>1800-123-4567</p>
                            </div>
                        </div>
                        <div class="help-option">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email Support</h4>
                                <p>support@grievance.gov.in</p>
                            </div>
                        </div>
                        <div class="help-option">
                            <i class="fas fa-comments"></i>
                            <div>
                                <h4>Live Chat</h4>
                                <p>Chat with our AI assistant</p>
                            </div>
                        </div>
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
        function copyComplaintId() {
            const complaintId = document.getElementById('complaintIdDisplay').textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(complaintId).then(() => {
                    showCopyNotification();
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                    fallbackCopyText(complaintId);
                });
            } else {
                fallbackCopyText(complaintId);
            }
        }
        
        function fallbackCopyText(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopyNotification();
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopyNotification() {
            const copyBtn = document.querySelector('.copy-btn');
            const originalHTML = copyBtn.innerHTML;
            
            copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            copyBtn.style.background = '#27ae60';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalHTML;
                copyBtn.style.background = '';
            }, 2000);
        }
        
        // Auto-redirect to dashboard after 30 seconds
        setTimeout(() => {
            window.location.href = 'user_dashboard.php';
        }, 30000);
    </script>
    
    <style>
        .success-section {
            padding: 100px 0 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #27ae60;
            margin-bottom: 20px;
            animation: bounceIn 0.6s ease;
        }
        
        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .success-container h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 2.5rem;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .success-message p {
            color: #155724;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .complaint-id-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .complaint-id-card h3 {
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .complaint-id {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        #complaintIdDisplay {
            font-size: 2rem;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            letter-spacing: 2px;
        }
        
        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .note {
            opacity: 0.9;
            margin: 0;
        }
        
        .next-steps {
            text-align: left;
            margin-bottom: 40px;
        }
        
        .next-steps h3 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: #3498db;
            color: white;
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
            gap: 15px;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        .help-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
        }
        
        .help-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .help-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .help-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .help-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .help-option i {
            font-size: 1.5rem;
            color: #3498db;
        }
        
        .help-option h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .help-option p {
            color: #7f8c8d;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 20px;
                margin: 20px;
            }
            
            .success-container h1 {
                font-size: 1.8rem;
            }
            
            #complaintIdDisplay {
                font-size: 1.5rem;
            }
            
            .complaint-id {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .steps {
                grid-template-columns: 1fr;
            }
            
            .help-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
