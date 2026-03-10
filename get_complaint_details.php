<?php
require_once 'config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $complaint_id = clean_input($_GET['id']);
    
    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
        exit;
    }
    
    // Get complaint details
    $complaint = get_complaint_details($complaint_id);
    
    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        exit;
    }
    
    // Check if admin has permission to view this complaint
    $admin_department = $_SESSION['admin_department'];
    if ($admin_department !== 'higher' && $complaint['category'] !== $admin_department) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    // Get complaint timeline
    $timeline = get_complaint_timeline($complaint_id);
    
    // Format timeline for display
    $formatted_timeline = [];
    foreach ($timeline as $event) {
        $formatted_timeline[] = [
            'action' => $event['action'],
            'description' => $event['description'],
            'performed_at' => date('M d, Y h:i A', strtotime($event['performed_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'complaint' => [
            'complaint_id' => $complaint['complaint_id'],
            'user_name' => $complaint['user_name'],
            'user_email' => $complaint['user_email'],
            'category' => ucfirst(str_replace('_', ' ', $complaint['category'])),
            'description' => $complaint['description'],
            'location' => $complaint['location'],
            'status' => ucfirst(str_replace('_', ' ', $complaint['status'])),
            'priority' => ucfirst($complaint['priority']),
            'created_at' => date('M d, Y h:i A', strtotime($complaint['created_at'])),
            'updated_at' => date('M d, Y h:i A', strtotime($complaint['updated_at'])),
            'resolved_at' => $complaint['resolved_at'] ? date('M d, Y h:i A', strtotime($complaint['resolved_at'])) : null,
            'escalated_at' => $complaint['escalated_at'] ? date('M d, Y h:i A', strtotime($complaint['escalated_at'])) : null,
            'image_path' => $complaint['image_path'],
            'admin_notes' => $complaint['admin_notes']
        ],
        'timeline' => $formatted_timeline
    ]);
}
?>
