<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and validate input
    $department = clean_input($_POST['department']);
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    // Validation
    if (empty($department)) {
        $errors[] = "Department selection is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, department, full_name, email, is_active FROM admin_users WHERE username = ? AND department = ? AND is_active = TRUE");
        $stmt->bind_param("ss", $username, $department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password (using plain text for demo, should be hashed in production)
            if ($password === 'admin123' || password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_department'] = $admin['department'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Remember me functionality
                if (isset($_POST['remember'])) {
                    setcookie('admin_username', $username, time() + (86400 * 30), "/");
                    setcookie('admin_department', $department, time() + (86400 * 30), "/");
                }
                
                $stmt->close();
                
                // Redirect to admin dashboard
                redirect('admin_dashboard.php');
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            $errors[] = "Invalid username or password";
        }
        $stmt->close();
    }
}

// Check for remember me cookies
if (!isset($_SESSION['admin_id']) && isset($_COOKIE['admin_username']) && isset($_COOKIE['admin_department'])) {
    $username = $_COOKIE['admin_username'];
    $department = $_COOKIE['admin_department'];
    
    $stmt = $conn->prepare("SELECT id, username, department, full_name, email, is_active FROM admin_users WHERE username = ? AND department = ? AND is_active = TRUE");
    $stmt->bind_param("ss", $username, $department);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_department'] = $admin['department'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $stmt->close();
        redirect('admin_dashboard.php');
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Grievance Portal</title>
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
                <li><a href="login.html" class="nav-link">User Login</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint.html" class="nav-link">Track Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link active">Admin Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Admin Login Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2><i class="fas fa-user-shield"></i> Department Admin Login</h2>
                    <p>Access your department dashboard to manage complaints</p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="adminLoginForm" method="POST">
                        <div class="form-group">
                            <label for="department"><i class="fas fa-building"></i> Select Department</label>
                            <select id="department" name="department" required>
                                <option value="">Select Your Department</option>
                                <option value="garbage" <?php echo isset($_POST['department']) && $_POST['department'] === 'garbage' ? 'selected' : ''; ?>>Garbage Department</option>
                                <option value="water" <?php echo isset($_POST['department']) && $_POST['department'] === 'water' ? 'selected' : ''; ?>>Water Department</option>
                                <option value="road" <?php echo isset($_POST['department']) && $_POST['department'] === 'road' ? 'selected' : ''; ?>>Road Department</option>
                                <option value="electricity" <?php echo isset($_POST['department']) && $_POST['department'] === 'electricity' ? 'selected' : ''; ?>>Electricity Department</option>
                                <option value="higher" <?php echo isset($_POST['department']) && $_POST['department'] === 'higher' ? 'selected' : ''; ?>>Higher Authority</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : (isset($_COOKIE['admin_username']) ? htmlspecialchars($_COOKIE['admin_username']) : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember" <?php echo isset($_COOKIE['admin_username']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Remember me
                            </label>
                            <a href="#" class="forgot-password">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                        </button>
                    </form>
                    
                    <div class="auth-links">
                        <p>Looking for user login? <a href="login.html">Click here</a></p>
                        <p><a href="index.html">Back to Home</a></p>
                    </div>
                </div>
                
                <div class="auth-info">
                    <h3>Department Dashboard Access</h3>
                    <p>As a department administrator, you can:</p>
                    <ul>
                        <li><i class="fas fa-clipboard-list"></i> View all complaints</li>
                        <li><i class="fas fa-edit"></i> Update complaint status</li>
                        <li><i class="fas fa-chart-bar"></i> Generate reports</li>
                        <li><i class="fas fa-bell"></i> Manage notifications</li>
                        <li><i class="fas fa-users"></i> Coordinate with other departments</li>
                    </ul>
                    
                    <div class="department-cards">
                        <h4>Available Departments</h4>
                        <div class="dept-card">
                            <i class="fas fa-trash"></i>
                            <h5>Garbage Department</h5>
                            <p>Manages waste collection and sanitation</p>
                        </div>
                        <div class="dept-card">
                            <i class="fas fa-tint"></i>
                            <h5>Water Department</h5>
                            <p>Handles water supply and leakage issues</p>
                        </div>
                        <div class="dept-card">
                            <i class="fas fa-road"></i>
                            <h5>Road Department</h5>
                            <p>Maintains road infrastructure</p>
                        </div>
                        <div class="dept-card">
                            <i class="fas fa-bolt"></i>
                            <h5>Electricity Department</h5>
                            <p>Manages power supply and connections</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h4><i class="fas fa-shield-alt"></i> Demo Credentials</h4>
                        <p>For testing purposes, use:</p>
                        <p><strong>Username:</strong> [department]_admin</p>
                        <p><strong>Password:</strong> admin123</p>
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
        // Demo credentials for testing
        const demoCredentials = {
            garbage: { username: 'garbage_admin', password: 'admin123' },
            water: { username: 'water_admin', password: 'admin123' },
            road: { username: 'road_admin', password: 'admin123' },
            electricity: { username: 'electricity_admin', password: 'admin123' },
            higher: { username: 'higher_admin', password: 'admin123' }
        };
        
        // Auto-fill demo credentials when department is selected
        document.getElementById('department').addEventListener('change', function() {
            const dept = this.value;
            if (demoCredentials[dept]) {
                document.getElementById('username').value = demoCredentials[dept].username;
                document.getElementById('password').value = demoCredentials[dept].password;
            }
        });
        
        // Auto-fill from cookies if they exist
        window.onload = function() {
            const deptCookie = '<?php echo isset($_COOKIE['admin_department']) ? htmlspecialchars($_COOKIE['admin_department']) : ''; ?>';
            const usernameCookie = '<?php echo isset($_COOKIE['admin_username']) ? htmlspecialchars($_COOKIE['admin_username']) : ''; ?>';
            
            if (deptCookie && usernameCookie) {
                document.getElementById('department').value = deptCookie;
                document.getElementById('username').value = usernameCookie;
                document.getElementById('password').value = 'admin123';
                
                // Auto-submit form if remember me was checked
                setTimeout(function() {
                    document.getElementById('adminLoginForm').submit();
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
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert p {
            margin: 0;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            margin-top: 1rem;
        }
        
        .info-card h4 {
            margin-bottom: 0.5rem;
        }
        
        .info-card p {
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
    </style>
</body>
</html>
