<?php
require_once 'config.php';
require_once 'DepartmentRouter.php';

// Initialize the department router
$router = new DepartmentRouter($conn);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $mobile = clean_input($_POST['mobile']);
    $address = clean_input($_POST['address']);
    $category = clean_input($_POST['category']);
    $description = clean_input($_POST['description']);
    $location = clean_input($_POST['location']);
    $priority = clean_input($_POST['priority']);
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($mobile)) $errors[] = "Mobile number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($category)) $errors[] = "Category is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($location)) $errors[] = "Location is required";
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate mobile
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Invalid mobile number format";
    }
    
    // If no errors, proceed with submission
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Generate unique complaint ID
            $complaint_id = generate_complaint_id();
            
            // Auto-assign department using the router
            $routingResult = $router->autoAssignDepartment($description, $category);
            $assigned_department = $routingResult['department'];
            $routing_confidence = $routingResult['confidence'];
            $routing_method = $routingResult['method'];
            
            // Handle file upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_result = upload_file($_FILES['image'], 'complaints');
                if ($upload_result['success']) {
                    $image_path = $upload_result['file_path'];
                } else {
                    $errors[] = $upload_result['message'];
                }
            }
            
            // Insert complaint into database
            $stmt = $conn->prepare("INSERT INTO complaints 
                (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, image_path, status, auto_assigned_department, routing_confidence, routing_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
            
            $user_id = $_SESSION['user_id'] ?? 0; // 0 for guest users
            $status = 'pending';
            
            $stmt->bind_param("sissssssssssds", 
                $complaint_id, $user_id, $name, $email, $mobile, $address, 
                $category, $description, $location, $priority, $image_path, 
                $assigned_department, $routing_confidence, $routing_method
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to submit complaint: " . $stmt->error);
            }
            $stmt->close();
            
            // Add to complaint timeline
            $timeline_description = "Complaint submitted and auto-assigned to " . $router->getDepartmentName($assigned_department);
            $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, action, description) VALUES (?, 'submitted', ?)");
            $timeline_stmt->bind_param("ss", $complaint_id, $timeline_description);
            $timeline_stmt->execute();
            $timeline_stmt->close();
            
            // Update routing log with actual complaint ID
            $update_routing = $conn->prepare("UPDATE routing_logs SET complaint_id = ? WHERE complaint_id = 'TEMP' ORDER BY created_at DESC LIMIT 1");
            $update_routing->bind_param("s", $complaint_id);
            $update_routing->execute();
            $update_routing->close();
            
            // Send notification to user
            $notification_title = 'Complaint Submitted Successfully';
            $notification_message = "Your complaint $complaint_id has been submitted and assigned to " . $router->getDepartmentName($assigned_department) . ". We will process it soon.";
            $notification_type = 'complaint_submitted';
            
            send_notification($user_id, $complaint_id, $notification_title, $notification_message, $notification_type);
            
            // Send notification to assigned department admin
            $admin_query = $conn->prepare("SELECT id FROM admin_users WHERE department = ? AND is_active = TRUE");
            $admin_query->bind_param("s", $assigned_department);
            $admin_query->execute();
            $admin_result = $admin_query->get_result();
            
            if ($admin_result->num_rows > 0) {
                $admin = $admin_result->fetch_assoc();
                $admin_notification_title = 'New Complaint Assigned';
                $admin_notification_message = "New complaint $complaint_id has been auto-assigned to your department. Priority: " . ucfirst($priority);
                $admin_notification_type = 'new_complaint';
                
                send_admin_notification($admin['id'], $complaint_id, $admin_notification_title, $admin_notification_message, $admin_notification_type);
            }
            $admin_query->close();
            
            // Commit transaction
            $conn->commit();
            
            // Store success data in session for success page
            $_SESSION['complaint_data'] = [
                'complaint_id' => $complaint_id,
                'assigned_department' => $router->getDepartmentName($assigned_department),
                'routing_confidence' => $routing_confidence,
                'routing_method' => $routing_method,
                'matched_keywords' => $routingResult['matched_keywords'] ?? []
            ];
            
            // Redirect to success page
            header('Location: submit_success_with_routing.php');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $errors[] = "Error submitting complaint: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - Smart Grievance Portal</title>
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
                <li><a href="submit_complaint.html" class="nav-link active">Submit Complaint</a></li>
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

    <!-- Submit Complaint Section -->
    <section class="complaint-section">
        <div class="container">
            <div class="complaint-form-container">
                <div class="form-header">
                    <h2><i class="fas fa-edit"></i> Submit Complaint</h2>
                    <p>Fill in the details below. Our system will automatically assign your complaint to the appropriate department.</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="complaintForm">
                    <div class="form-row">
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
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                            <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" required 
                                   value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category"><i class="fas fa-list"></i> Category</label>
                            <select id="category" name="category" required onchange="showCategoryHint()">
                                <option value="">Select Category</option>
                                <option value="garbage" <?php echo (isset($_POST['category']) && $_POST['category'] == 'garbage') ? 'selected' : ''; ?>>Garbage/Sanitation</option>
                                <option value="water" <?php echo (isset($_POST['category']) && $_POST['category'] == 'water') ? 'selected' : ''; ?>>Water Supply</option>
                                <option value="road" <?php echo (isset($_POST['category']) && $_POST['category'] == 'road') ? 'selected' : ''; ?>>Road/PWD</option>
                                <option value="electricity" <?php echo (isset($_POST['category']) && $_POST['category'] == 'electricity') ? 'selected' : ''; ?>>Electricity</option>
                                <option value="other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-home"></i> Address</label>
                        <textarea id="address" name="address" rows="2" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><i class="fas fa-comment"></i> Complaint Description</label>
                        <textarea id="description" name="description" rows="4" required 
                                  placeholder="Please describe your issue in detail. Include relevant keywords like 'pothole', 'water leak', 'street light', etc. for better department assignment."
                                  onkeyup="showRoutingPreview()"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small class="form-hint">💡 Tip: Include specific keywords like 'pothole', 'water leak', 'street light' for automatic department assignment</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location"><i class="fas fa-map-marker-alt"></i> Specific Location</label>
                            <input type="text" id="location" name="location" required 
                                   placeholder="e.g., Near City Mall, Main Street" 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="priority"><i class="fas fa-exclamation-triangle"></i> Priority</label>
                            <select id="priority" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="normal" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image"><i class="fas fa-camera"></i> Upload Image (Optional)</label>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)">
                        <div id="imagePreview" class="image-preview"></div>
                        <small>Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <!-- Routing Preview -->
                    <div id="routingPreview" class="routing-preview" style="display: none;">
                        <h4><i class="fas fa-robot"></i> Auto Department Assignment Preview</h4>
                        <div class="routing-result">
                            <div class="routing-item">
                                <span class="label">Suggested Department:</span>
                                <span class="value" id="suggestedDept">-</span>
                            </div>
                            <div class="routing-item">
                                <span class="label">Confidence:</span>
                                <span class="value" id="confidenceLevel">-</span>
                            </div>
                            <div class="routing-item">
                                <span class="label">Matched Keywords:</span>
                                <span class="value" id="matchedKeywords">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Complaint
                    </button>
                </form>
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
        let routingTimer;
        
        function showRoutingPreview() {
            const description = document.getElementById('description').value;
            const category = document.getElementById('category').value;
            
            if (description.length < 10) {
                document.getElementById('routingPreview').style.display = 'none';
                return;
            }
            
            // Debounce the API call
            clearTimeout(routingTimer);
            routingTimer = setTimeout(() => {
                // Show loading state
                document.getElementById('routingPreview').style.display = 'block';
                document.getElementById('suggestedDept').textContent = 'Analyzing...';
                document.getElementById('confidenceLevel').textContent = '...';
                document.getElementById('matchedKeywords').textContent = '...';
                
                // Call routing API
                fetch('test_routing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `description=${encodeURIComponent(description)}&category=${encodeURIComponent(category)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('suggestedDept').textContent = data.department_name;
                        document.getElementById('confidenceLevel').textContent = data.confidence + '%';
                        document.getElementById('matchedKeywords').textContent = data.matched_keywords.join(', ');
                        
                        // Update confidence color
                        const confidenceElement = document.getElementById('confidenceLevel');
                        if (data.confidence >= 80) {
                            confidenceElement.style.color = '#27ae60';
                        } else if (data.confidence >= 60) {
                            confidenceElement.style.color = '#f39c12';
                        } else {
                            confidenceElement.style.color = '#e74c3c';
                        }
                    }
                })
                .catch(error => {
                    console.error('Routing error:', error);
                    document.getElementById('suggestedDept').textContent = 'Error';
                    document.getElementById('confidenceLevel').textContent = '-';
                    document.getElementById('matchedKeywords').textContent = '-';
                });
            }, 500);
        }
        
        function showCategoryHint() {
            const category = document.getElementById('category').value;
            const hints = {
                'garbage': 'Keywords: garbage, trash, waste, sanitation, dustbin',
                'water': 'Keywords: water, supply, tap, leakage, pressure',
                'road': 'Keywords: road, pothole, street, highway, bridge',
                'electricity': 'Keywords: electricity, light, power, outage, transformer',
                'other': 'Will be reviewed manually'
            };
            
            const hintElement = document.querySelector('.form-hint');
            if (hints[category]) {
                hintElement.textContent = '💡 Tip: ' + hints[category];
            }
        }
        
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 10px;">`;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
    
    <style>
        .routing-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .routing-preview h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .routing-result {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .routing-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .routing-item .label {
            font-weight: 600;
            color: #495057;
        }
        
        .routing-item .value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-hint {
            color: #6c757d;
            font-style: italic;
            margin-top: 5px;
            display: block;
        }
        
        .image-preview {
            margin-top: 10px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger p {
            margin: 5px 0;
        }
    </style>
</body>
</html>
