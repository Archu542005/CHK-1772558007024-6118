<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.html');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user details from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean_input($_POST['name']);
    $mobile = clean_input($_POST['mobile']);
    $address = clean_input($_POST['address']);
    $current_password = clean_input($_POST['current_password']);
    $new_password = clean_input($_POST['new_password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($mobile)) {
        $errors[] = "Mobile number is required";
    } elseif (!validate_mobile($mobile)) {
        $errors[] = "Mobile number must be 10 digits";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required";
    }
    
    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user_details['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        
        if (empty($confirm_password)) {
            $errors[] = "Please confirm your new password";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // Check if mobile number is already taken by another user
    if (empty($errors)) {
        $check_mobile = $conn->prepare("SELECT id FROM users WHERE mobile = ? AND id != ?");
        $check_mobile->bind_param("si", $mobile, $user_id);
        $check_mobile->execute();
        $mobile_result = $check_mobile->get_result();
        
        if ($mobile_result->num_rows > 0) {
            $errors[] = "Mobile number already registered by another user";
        }
        $check_mobile->close();
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Update basic info
        $update_fields = "name = ?, mobile = ?, address = ?";
        $update_params = [$name, $mobile, $address];
        $update_types = "sss";
        
        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields .= ", password = ?";
            $update_params[] = $hashed_password;
            $update_types .= "s";
        }
        
        $update_params[] = $user_id;
        $update_types .= "i";
        
        $stmt = $conn->prepare("UPDATE users SET $update_fields WHERE id = ?");
        $stmt->bind_param($update_types, ...$update_params);
        
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            
            // Update session variables
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $user_details['email']; // Email doesn't change
            
            // Refresh user details
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_details = $result->fetch_assoc();
            $stmt->close();
            
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Grievance Portal</title>
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
                <li><a href="profile.php" class="nav-link active">My Account</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($user_name); ?></h3>
                        <p><?php echo htmlspecialchars($user_email); ?></p>
                    </div>
                    
                    <div class="profile-menu">
                        <a href="profile.php" class="menu-item active">
                            <i class="fas fa-user"></i> Personal Information
                        </a>
                        <a href="my_complaints.php" class="menu-item">
                            <i class="fas fa-clipboard-list"></i> My Complaints
                        </a>
                        <a href="notifications.php" class="menu-item">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="settings.php" class="menu-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="logout.php" class="menu-item logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                
                <div class="profile-content">
                    <div class="content-header">
                        <h2><i class="fas fa-user"></i> Personal Information</h2>
                        <p>Update your personal details and manage your account</p>
                    </div>
                    
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
                    
                    <div class="profile-forms">
                        <!-- Personal Information Form -->
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                                        <input type="text" id="name" name="name" required 
                                               value="<?php echo htmlspecialchars($user_details['name']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                                        <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" required 
                                               value="<?php echo htmlspecialchars($user_details['mobile']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address"><i class="fas fa-home"></i> Address</label>
                                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($user_details['address']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_details['email']); ?>" readonly>
                                    <small>Email address cannot be changed</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Information
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Form -->
                        <div class="form-section">
                            <h3>Change Password</h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password"><i class="fas fa-check"></i> Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account Statistics -->
                    <div class="account-stats">
                        <h3>Account Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Member Since</h4>
                                    <p><?php echo date('F j, Y', strtotime($user_details['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Total Complaints</h4>
                                    <p>
                                        <?php 
                                        $count_query = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE user_id = ?");
                                        $count_query->bind_param("i", $user_id);
                                        $count_query->execute();
                                        $count_result = $count_query->get_result();
                                        $count = $count_result->fetch_assoc()['count'];
                                        echo $count;
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>Resolved Complaints</h4>
                                    <p>
                                        <?php 
                                        $resolved_query = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE user_id = ? AND status = 'resolved'");
                                        $resolved_query->bind_param("i", $user_id);
                                        $resolved_query->execute();
                                        $resolved_result = $resolved_query->get_result();
                                        $resolved = $resolved_result->fetch_assoc()['count'];
                                        echo $resolved;
                                        ?>
                                    </p>
                                </div>
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
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        function updatePasswordStrengthIndicator(strength) {
            const indicator = document.getElementById('passwordStrength');
            if (!indicator) {
                const strengthDiv = document.createElement('div');
                strengthDiv.id = 'passwordStrength';
                strengthDiv.style.marginTop = '5px';
                document.getElementById('new_password').parentNode.appendChild(strengthDiv);
            }
            
            const indicatorElement = document.getElementById('passwordStrength');
            const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthColor = ['#e74c3c', '#f39c12', '#f1c40f', '#2ecc71', '#27ae60'];
            
            if (password.length > 0) {
                indicatorElement.innerHTML = `<span style="color: ${strengthColor[strength]}">Password Strength: ${strengthText[strength]}</span>`;
            } else {
                indicatorElement.innerHTML = '';
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        });
    </script>
    
    <style>
        .profile-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .profile-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .profile-header h3 {
            margin-bottom: 5px;
            font-size: 1.3rem;
        }
        
        .profile-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .profile-menu {
            display: flex;
            flex-direction: column;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .menu-item.logout {
            background: rgba(231, 76, 60, 0.2);
            margin-top: 20px;
        }
        
        .menu-item.logout:hover {
            background: rgba(231, 76, 60, 0.3);
        }
        
        .profile-content {
            padding: 30px;
        }
        
        .content-header {
            margin-bottom: 30px;
        }
        
        .content-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .content-header p {
            color: #7f8c8d;
        }
        
        .profile-forms {
            display: grid;
            gap: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
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
        
        .account-stats {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .account-stats h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .stat-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #7f8c8d;
            margin: 0;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                padding: 20px;
            }
            
            .profile-content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
