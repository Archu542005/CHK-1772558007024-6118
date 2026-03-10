<?php
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get all complaint locations for admin map
 */
function getAllComplaintLocations() {
    global $conn;
    
    try {
        $query = "SELECT 
                    complaint_id, 
                    description, 
                    latitude, 
                    longitude, 
                    category as department,
                    status, 
                    priority,
                    name,
                    email,
                    mobile,
                    location as address,
                    created_at,
                    updated_at
                  FROM complaints 
                  WHERE latitude IS NOT NULL 
                    AND longitude IS NOT NULL 
                    AND latitude != '' 
                    AND longitude != ''
                    AND latitude BETWEEN -90 AND 90
                    AND longitude BETWEEN -180 AND 180
                  ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $complaints = [];
        
        while ($row = $result->fetch_assoc()) {
            // Validate coordinates
            $lat = floatval($row['latitude']);
            $lng = floatval($row['longitude']);
            
            // Skip invalid coordinates
            if (abs($lat) > 90 || abs($lng) > 180) {
                continue;
            }
            
            // Format marker data
            $complaint = [
                'id' => $row['complaint_id'],
                'title' => $row['complaint_id'],
                'description' => $row['description'],
                'lat' => $lat,
                'lng' => $lng,
                'department' => $row['department'],
                'status' => $row['status'],
                'priority' => $row['priority'],
                'name' => $row['name'],
                'email' => $row['email'],
                'mobile' => $row['mobile'],
                'address' => $row['address'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'icon' => getMarkerIcon($row['status'], $row['priority']),
                'color' => getMarkerColor($row['status'])
            ];
            
            $complaints[] = $complaint;
        }
        
        $stmt->close();
        
        // Get statistics
        $stats = getAdminMapStats();
        
        return [
            'success' => true,
            'data' => [
                'complaints' => $complaints,
                'stats' => $stats,
                'total_complaints' => count($complaints),
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get recent complaints for real-time updates
 */
function getRecentComplaints($last_update = null) {
    global $conn;
    
    try {
        $query = "SELECT 
                    complaint_id, 
                    description, 
                    latitude, 
                    longitude, 
                    category as department,
                    status, 
                    priority,
                    name,
                    created_at
                  FROM complaints 
                  WHERE latitude IS NOT NULL 
                    AND longitude IS NOT NULL";
        
        if ($last_update) {
            $query .= " AND updated_at > ?";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $conn->prepare($query);
        
        if ($last_update) {
            $stmt->bind_param("s", $last_update);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $complaints = [];
        
        while ($row = $result->fetch_assoc()) {
            $complaints[] = [
                'id' => $row['complaint_id'],
                'title' => $row['complaint_id'],
                'description' => $row['description'],
                'lat' => floatval($row['latitude']),
                'lng' => floatval($row['longitude']),
                'department' => $row['department'],
                'status' => $row['status'],
                'priority' => $row['priority'],
                'name' => $row['name'],
                'created_at' => $row['created_at'],
                'icon' => getMarkerIcon($row['status'], $row['priority']),
                'color' => getMarkerColor($row['status'])
            ];
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'data' => [
                'complaints' => $complaints,
                'count' => count($complaints)
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get filtered complaints for admin map
 */
function getFilteredComplaints() {
    global $conn;
    
    $department = $_GET['department'] ?? '';
    $status = $_GET['status'] ?? '';
    $priority = $_GET['priority'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $query = "SELECT 
                complaint_id, 
                description, 
                latitude, 
                longitude, 
                category as department,
                status, 
                priority,
                name,
                email,
                location as address,
                created_at,
                updated_at
              FROM complaints 
              WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
    
    $params = [];
    $types = '';
    
    if (!empty($department)) {
        $query .= " AND category = ?";
        $params[] = $department;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($priority)) {
        $query .= " AND priority = ?";
        $params[] = $priority;
        $types .= 's';
    }
    
    if (!empty($date_from)) {
        $query .= " AND created_at >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $query .= " AND created_at <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $complaints = [];
    
    while ($row = $result->fetch_assoc()) {
        $complaints[] = [
            'id' => $row['complaint_id'],
            'title' => $row['complaint_id'],
            'description' => $row['description'],
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'department' => $row['department'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'name' => $row['name'],
            'email' => $row['email'],
            'mobile' => $row['mobile'],
            'address' => $row['address'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'icon' => getMarkerIcon($row['status'], $row['priority']),
            'color' => getMarkerColor($row['status'])
        ];
    }
    
    return [
        'success' => true,
        'data' => [
            'complaints' => $complaints,
            'total' => count($complaints)
        ]
    ];
}

/**
 * Get marker icon based on status and priority
 */
function getMarkerIcon($status, $priority) {
    $status_icons = [
        'pending' => 'fas fa-clock',
        'in_progress' => 'fas fa-spinner',
        'resolved' => 'fas fa-check-circle',
        'escalated' => 'fas fa-exclamation-triangle'
    ];
    
    $priority_colors = [
        'urgent' => '#e74c3c',
        'high' => '#f39c12',
        'normal' => '#3498db',
        'low' => '#95a5a6'
    ];
    
    $icon = $status_icons[$status] ?? 'fas fa-map-marker';
    $color = $priority_colors[$priority] ?? '#3498db';
    
    return [
        'icon' => $icon,
        'color' => $color
    ];
}

/**
 * Get marker color based on status
 */
function getMarkerColor($status) {
    $colors = [
        'pending' => '#f39c12',
        'in_progress' => '#3498db',
        'resolved' => '#27ae60',
        'escalated' => '#e74c3c'
    ];
    
    return $colors[$status] ?? '#95a5a6';
}

/**
 * Get admin map statistics
 */
function getAdminMapStats() {
    global $conn;
    
    $stats = [];
    
    // Total complaints with locations
    $total_query = "SELECT COUNT(*) as count 
                    FROM complaints 
                    WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
    $result = $conn->query($total_query);
    $row = $result->fetch_assoc();
    $stats['total_with_location'] = $row['count'];
    
    // Complaints by status
    $status_query = "SELECT status, COUNT(*) as count 
                     FROM complaints 
                     WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                     GROUP BY status";
    $result = $conn->query($status_query);
    $stats['by_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_status'][] = $row;
    }
    
    // Complaints by department
    $dept_query = "SELECT category, COUNT(*) as count 
                    FROM complaints 
                    WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                    GROUP BY category";
    $result = $conn->query($dept_query);
    $stats['by_department'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_department'][] = $row;
    }
    
    // Recent complaints (last 24 hours)
    $recent_query = "SELECT COUNT(*) as count 
                    FROM complaints 
                    WHERE latitude IS NOT NULL 
                      AND longitude IS NOT NULL 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $conn->query($recent_query);
    $row = $result->fetch_assoc();
    $stats['last_24_hours'] = $row['count'];
    
    return $stats;
}

// Handle API requests
$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'all':
        echo json_encode(getAllComplaintLocations());
        break;
        
    case 'recent':
        $last_update = $_GET['last_update'] ?? null;
        echo json_encode(getRecentComplaints($last_update));
        break;
        
    case 'filtered':
        echo json_encode(getFilteredComplaints());
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}
?>
