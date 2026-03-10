<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.html');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and validate input
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $category = clean_input($_POST['category']);
    $description = clean_input($_POST['description']);
    $location = clean_input($_POST['location']);
    $priority = clean_input($_POST['priority']);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($category)) {
        $errors[] = "Department category is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    } elseif (strlen($description) < 10) {
        $errors[] = "Description must be at least 10 characters long";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($priority)) {
        $errors[] = "Priority level is required";
    }
    
    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['image']);
        if ($upload_result['success']) {
            $image_path = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // If no errors, submit complaint
    if (empty($errors)) {
        // Generate complaint ID
        $complaint_id = generate_complaint_id();
        
        // Get user ID from session
        $user_id = $_SESSION['user_id'];
        
        // Insert complaint
        $stmt = $conn->prepare("INSERT INTO complaints (complaint_id, user_id, category, description, location, priority, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $complaint_id, $user_id, $category, $description, $location, $priority, $image_path);
        
        if ($stmt->execute()) {
            // Add to timeline
            $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, action, description) VALUES (?, 'submitted', 'Complaint submitted by user')");
            $timeline_stmt->bind_param("s", $complaint_id);
            $timeline_stmt->execute();
            $timeline_stmt->close();
            
            // Send notification to user
            send_notification($user_id, $complaint_id, 'Complaint Submitted Successfully', 
                            "Your complaint has been submitted with ID: $complaint_id. We will process it soon.", 
                            'complaint_submitted');
            
            // Send notification to relevant department admin
            $admin_query = $conn->prepare("SELECT id FROM admin_users WHERE department = ? AND is_active = TRUE");
            $admin_query->bind_param("s", $category);
            $admin_query->execute();
            $admin_result = $admin_query->get_result();
            
            if ($admin_result->num_rows > 0) {
                $admin = $admin_result->fetch_assoc();
                send_admin_notification($admin['id'], $complaint_id, 'New Complaint Assigned', 
                                    "A new complaint ($complaint_id) has been submitted to your department.", 
                                    'admin_assigned');
            }
            $admin_query->close();
            
            $stmt->close();
            
            // Set success message and complaint ID for display
            $success = "Your complaint has been successfully submitted. Your Complaint ID is: $complaint_id";
            
            // Store complaint ID in session for display on success page
            $_SESSION['complaint_id'] = $complaint_id;
            $_SESSION['success_message'] = $success;
            
            // Redirect to success page
            redirect('submit_success.php');
        } else {
            $errors[] = "Failed to submit complaint. Please try again.";
        }
    }
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    $_SESSION['form_data'] = $_POST;
    redirect('submit_complaint.html');
}
?>
