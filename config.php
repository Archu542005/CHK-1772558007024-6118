<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'GS_system');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Session configuration
session_start();

// Application settings
define('SITE_NAME', 'Smart Grievance Portal');
define('SITE_URL', 'http://localhost/hack/');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Helper functions
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_mobile($mobile) {
    return preg_match('/^[0-9]{10}$/', $mobile);
}

function generate_complaint_id() {
    global $conn;
    
    // Get prefix from settings
    $prefix = 'CMP';
    $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'complaint_id_prefix'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $prefix = $row['setting_value'];
    }
    
    // Get next sequence number
    $result = $conn->query("SELECT COALESCE(MAX(CAST(SUBSTRING(complaint_id, LENGTH('$prefix') + 1) AS UNSIGNED)), 0) + 1 as next_num 
                           FROM complaints WHERE complaint_id LIKE '$prefix%'");
    $row = $result->fetch_assoc();
    $sequence = str_pad($row['next_num'], 6, '0', STR_PAD_LEFT);
    $year = date('Y');
    
    return $prefix . $sequence . $year;
}

function send_notification($user_id, $complaint_id, $title, $message, $type) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $complaint_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function send_admin_notification($admin_id, $complaint_id, $title, $message, $type) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, complaint_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $admin_id, $complaint_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function display_alert($message, $type = 'success') {
    $alert_class = $type === 'success' ? 'alert-success' : 'alert-danger';
    echo "<div class='alert $alert_class'>$message</div>";
}

function upload_file($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_ext;
    $upload_path = UPLOAD_DIR . $new_filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

function get_user_complaints($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaints = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $complaints;
}

function get_complaint_details($complaint_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email 
                           FROM complaints c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.complaint_id = ?");
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();
    $stmt->close();
    
    return $complaint;
}

function get_complaint_timeline($complaint_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM complaint_timeline WHERE complaint_id = ? ORDER BY performed_at ASC");
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeline = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $timeline;
}

function update_department_stats($department, $status_change, $operation = 'increment') {
    global $conn;
    
    $field = '';
    switch($status_change) {
        case 'pending':
            $field = 'pending_complaints';
            break;
        case 'in_progress':
            $field = 'in_progress_complaints';
            break;
        case 'resolved':
            $field = 'resolved_complaints';
            break;
        case 'escalated':
            $field = 'escalated_complaints';
            break;
    }
    
    if ($field) {
        $operator = $operation === 'increment' ? '+ 1' : '- 1';
        $conn->query("UPDATE department_stats SET $field = $field $operator WHERE department = '$department'");
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?>
