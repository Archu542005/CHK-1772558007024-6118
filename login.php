<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and validate input
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Remember me functionality
                if (isset($_POST['remember'])) {
                    setcookie('user_email', $email, time() + (86400 * 30), "/"); // 30 days
                    setcookie('user_password', $password, time() + (86400 * 30), "/");
                }
                
                $stmt->close();
                
                // Redirect to dashboard
                redirect('user_dashboard.php');
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
        $stmt->close();
    }
}

// Check for remember me cookies
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_email']) && isset($_COOKIE['user_password'])) {
    $email = $_COOKIE['user_email'];
    $password = $_COOKIE['user_password'];
    
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $stmt->close();
            redirect('user_dashboard.php');
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Smart Grievance Portal</title>
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
                <li><a href="login.html" class="nav-link active">Login</a></li>
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

    <!-- Login Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2><i class="fas fa-sign-in-alt"></i> User Login</h2>
                    <p>Login to access your dashboard and manage complaints</p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="loginForm" method="POST">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" required 
                                   value="<?php echo isset($_COOKIE['user_password']) ? htmlspecialchars($_COOKIE['user_password']) : ''; ?>">
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember" <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Remember me
                            </label>
                            <a href="#" class="forgot-password" onclick="showForgotPasswordForm()">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    
                    <!-- Forgot Password Form (Hidden by default) -->
                    <div id="forgotPasswordForm" style="display: none;">
                        <h3><i class="fas fa-key"></i> Reset Password</h3>
                        <p>Enter your email address to receive password reset instructions</p>
                        
                        <form id="forgotForm" method="POST" action="forgot_password.php">
                            <div class="form-group">
                                <label for="reset_email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="reset_email" name="reset_email" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Reset Link
                            </button>
                            
                            <button type="button" class="btn btn-secondary" onclick="hideForgotPasswordForm()">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </button>
                        </form>
                    </div>
                    
                    <div class="auth-links">
                        <p>Don't have an account? <a href="register.html">Register here</a></p>
                        <p><a href="index.html">Back to Home</a></p>
                    </div>
                </div>
                
                <div class="auth-info">
                    <h3>Welcome Back!</h3>
                    <p>Login to access:</p>
                    <ul>
                        <li><i class="fas fa-dashboard"></i> Your Dashboard</li>
                        <li><i class="fas fa-edit"></i> Submit New Complaints</li>
                        <li><i class="fas fa-search"></i> Track Complaint Status</li>
                        <li><i class="fas fa-history"></i> View Complaint History</li>
                        <li><i class="fas fa-bell"></i> Get Notifications</li>
                    </ul>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-question-circle"></i> Need Help?</h4>
                        <p>Contact our support team at support@grievance.gov.in</p>
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
        function showForgotPasswordForm() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').style.display = 'block';
            document.querySelector('.form-options').style.display = 'none';
            document.querySelector('.auth-links').style.display = 'none';
        }
        
        function hideForgotPasswordForm() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.querySelector('.form-options').style.display = 'flex';
            document.querySelector('.auth-links').style.display = 'block';
        }
        
        // Auto-fill from cookies if they exist
        window.onload = function() {
            const emailCookie = '<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>';
            const passwordCookie = '<?php echo isset($_COOKIE['user_password']) ? htmlspecialchars($_COOKIE['user_password']) : ''; ?>';
            
            if (emailCookie && passwordCookie) {
                // Auto-submit form if remember me was checked
                setTimeout(function() {
                    document.getElementById('loginForm').submit();
                }, 1000);
            }
        };
    </script>
    
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert p {
            margin: 0;
        }
        
        #forgotPasswordForm {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        #forgotPasswordForm h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        #forgotPasswordForm p {
            margin-bottom: 20px;
            color: #7f8c8d;
        }
    </style>
</body>
</html>
