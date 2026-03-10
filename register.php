<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and validate input
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $mobile = clean_input($_POST['mobile']);
    $address = clean_input($_POST['address']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = "Name should contain only letters and spaces";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($mobile)) {
        $errors[] = "Mobile number is required";
    } elseif (!validate_mobile($mobile)) {
        $errors[] = "Mobile number must be 10 digits";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $check_email->close();
    }
    
    // Check if mobile already exists
    if (empty($errors)) {
        $check_mobile = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
        $check_mobile->bind_param("s", $mobile);
        $check_mobile->execute();
        $result = $check_mobile->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Mobile number already registered";
        }
        $check_mobile->close();
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, address, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $mobile, $address, $hashed_password);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $stmt->close();
            
            // Send welcome notification
            send_notification($user_id, null, 'Welcome to ' . SITE_NAME, 
                            'Your account has been created successfully. You can now submit and track complaints.', 
                            'complaint_submitted');
            
            $success = "Registration successful! You can now login.";
            
            // Redirect to login page after 2 seconds
            header("refresh:2;url=login.html");
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Smart Grievance Portal</title>
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
                <li><a href="register.html" class="nav-link active">Register</a></li>
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

    <!-- Registration Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2><i class="fas fa-user-plus"></i> User Registration</h2>
                    <p>Create your account to submit and track complaints</p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form id="registrationForm" method="POST">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                            <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" required 
                                   value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address"><i class="fas fa-home"></i> Address</label>
                            <textarea id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </form>
                    
                    <div class="auth-links">
                        <p>Already have an account? <a href="login.html">Login here</a></p>
                        <p><a href="index.html">Back to Home</a></p>
                    </div>
                </div>
                
                <div class="auth-info">
                    <h3>Why Register?</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Submit complaints quickly</li>
                        <li><i class="fas fa-check-circle"></i> Track complaint status</li>
                        <li><i class="fas fa-check-circle"></i> Get notifications</li>
                        <li><i class="fas fa-check-circle"></i> View complaint history</li>
                        <li><i class="fas fa-check-circle"></i> Priority support</li>
                    </ul>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-shield-alt"></i> Your Data is Safe</h4>
                        <p>We use industry-standard encryption to protect your personal information.</p>
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
    </style>
</body>
</html>
