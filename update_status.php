<?php
require_once 'config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('admin_login.html');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = clean_input($_POST['complaint_id']);
    $new_status = clean_input($_POST['status']);
    $admin_notes = clean_input($_POST['admin_notes']);
    $admin_id = $_SESSION['admin_id'];
    $admin_department = $_SESSION['admin_department'];
    
    // Validation
    if (empty($complaint_id)) {
        $errors[] = "Complaint ID is required";
    }
    
    if (empty($new_status)) {
        $errors[] = "Status is required";
    }
    
    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'resolved'];
    if ($admin_department !== 'higher') {
        $valid_statuses[] = 'escalated';
    }
    
    if (!in_array($new_status, $valid_statuses)) {
        $errors[] = "Invalid status";
    }
    
    // If no errors, update complaint
    if (empty($errors)) {
        // Get current complaint details
        $current_complaint = get_complaint_details($complaint_id);
        
        if (!$current_complaint) {
            $errors[] = "Complaint not found";
        } else {
            // Check if admin has permission to update this complaint
            if ($admin_department !== 'higher' && $current_complaint['category'] !== $admin_department) {
                $errors[] = "You don't have permission to update this complaint";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update complaint status
                    $update_fields = "status = ?, updated_at = NOW()";
                    $update_params = [$new_status];
                    $update_types = "s";
                    
                    // Set resolved_at if status is resolved
                    if ($new_status === 'resolved') {
                        $update_fields .= ", resolved_at = NOW()";
                    }
                    
                    // Set escalated_at if status is escalated
                    if ($new_status === 'escalated') {
                        $update_fields .= ", escalated_at = NOW()";
                    }
                    
                    // Update admin notes if provided
                    if (!empty($admin_notes)) {
                        $update_fields .= ", admin_notes = ?";
                        $update_params[] = $admin_notes;
                        $update_types .= "s";
                    }
                    
                    // Assign to admin if not already assigned
                    if (empty($current_complaint['assigned_to'])) {
                        $update_fields .= ", assigned_to = ?";
                        $update_params[] = $admin_id;
                        $update_types .= "i";
                    }
                    
                    $update_params[] = $complaint_id;
                    $update_types .= "s";
                    
                    $stmt = $conn->prepare("UPDATE complaints SET $update_fields WHERE complaint_id = ?");
                    $stmt->bind_param($update_types, ...$update_params);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Add to timeline
                    $timeline_description = "Status updated to " . str_replace('_', ' ', $new_status);
                    if (!empty($admin_notes)) {
                        $timeline_description .= ". Notes: " . $admin_notes;
                    }
                    
                    $timeline_stmt = $conn->prepare("INSERT INTO complaint_timeline (complaint_id, action, description, performed_by) VALUES (?, ?, ?, ?)");
                    $timeline_action = $new_status === 'escalated' ? 'escalated' : $new_status;
                    $timeline_stmt->bind_param("sssi", $complaint_id, $timeline_action, $timeline_description, $admin_id);
                    $timeline_stmt->execute();
                    $timeline_stmt->close();
                    
                    // If escalated, add to escalated complaints table
                    if ($new_status === 'escalated') {
                        $escalation_reason = !empty($admin_notes) ? $admin_notes : 'Complaint escalated by department admin';
                        
                        $escalated_stmt = $conn->prepare("INSERT INTO escalated_complaints (complaint_id, original_department, escalation_reason) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE escalation_reason = ?");
                        $escalated_stmt->bind_param("ssss", $complaint_id, $current_complaint['category'], $escalation_reason, $escalation_reason);
                        $escalated_stmt->execute();
                        $escalated_stmt->close();
                        
                        // Notify higher authority
                        $higher_admin_query = $conn->prepare("SELECT id FROM admin_users WHERE department = 'higher' AND is_active = TRUE");
                        $higher_admin_query->execute();
                        $higher_admin_result = $higher_admin_query->get_result();
                        
                        if ($higher_admin_result->num_rows > 0) {
                            $higher_admin = $higher_admin_result->fetch_assoc();
                            send_admin_notification($higher_admin['id'], $complaint_id, 'Complaint Escalated', 
                                                "Complaint $complaint_id has been escalated for review.", 
                                                'escalated');
                        }
                        $higher_admin_query->close();
                    }
                    
                    // Send notification to user
                    $notification_title = 'Complaint Status Updated';
                    $notification_message = "Your complaint $complaint_id status has been updated to " . str_replace('_', ' ', $new_status);
                    $notification_type = 'status_updated';
                    
                    send_notification($current_complaint['user_id'], $complaint_id, $notification_title, $notification_message, $notification_type);
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $success = "Complaint status updated successfully!";
                    
                    // Set success message in session
                    $_SESSION['success_message'] = $success;
                    
                    // Redirect back to admin dashboard
                    redirect('admin_dashboard.php');
                    
                } catch (Exception $e) {
                    // Rollback transaction
                    $conn->rollback();
                    $errors[] = "Failed to update complaint status: " . $e->getMessage();
                }
            }
        }
    }
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    redirect('admin_dashboard.php');
}
?>
