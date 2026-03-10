<?php
require_once 'config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $escalated_count = 0;
    
    // Call the auto-escalation stored procedure
    try {
        $stmt = $conn->prepare("CALL auto_escalate_complaints()");
        $stmt->execute();
        $stmt->close();
        
        // Get count of escalated complaints
        $count_query = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE status = 'escalated' AND escalated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $count_query->execute();
        $count_result = $count_query->get_result();
        $count_row = $count_result->fetch_assoc();
        $escalated_count = $count_row['count'];
        $count_query->close();
        
        echo json_encode([
            'success' => true, 
            'escalated_count' => $escalated_count,
            'message' => "Auto-escalation completed successfully"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Auto-escalation failed: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
