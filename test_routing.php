<?php
require_once 'config.php';
require_once 'DepartmentRouter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = clean_input($_POST['description'] ?? '');
    $category = clean_input($_POST['category'] ?? '');
    
    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Description is required']);
        exit;
    }
    
    try {
        $router = new DepartmentRouter($conn);
        $result = $router->autoAssignDepartment($description, $category);
        
        echo json_encode([
            'success' => true,
            'department' => $result['department'],
            'department_name' => $router->getDepartmentName($result['department']),
            'confidence' => $result['confidence'],
            'method' => $result['method'],
            'matched_keywords' => $result['matched_keywords'],
            'score' => $result['score']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
