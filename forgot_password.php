<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['reset_email']);
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database (you might need to create a password_resets table)
            // For now, we'll simulate sending an email
            
            // In a real application, you would send an email with the reset link
            // For demo purposes, we'll just show success message
            
            $success = "Password reset instructions have been sent to your email address.";
            
            // Send notification
            send_notification($user['id'], null, 'Password Reset Request', 
                            'A password reset request was initiated for your account.', 
                            'complaint_submitted');
            
        } else {
            $errors[] = "Email address not found";
        }
        $stmt->close();
    }
}

// Redirect back to login with message
if (!empty($success)) {
    $_SESSION['success_message'] = $success;
    redirect('login.php');
} elseif (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    redirect('login.php');
}
?>
