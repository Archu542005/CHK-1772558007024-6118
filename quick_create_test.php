<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Test Complaints...</h2>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ Database connected successfully</p>";

// Create test complaints
$test_complaints = [
    [
        'id' => 'CMP-2024-001',
        'name' => 'Rajesh Kumar',
        'email' => 'rajesh@example.com',
        'mobile' => '9876543210',
        'category' => 'garbage',
        'description' => 'Garbage not collected from main road for past 3 days. Overflowing bins causing health issues.',
        'location' => 'Main Road, Sector 15',
        'priority' => 'normal',
        'status' => 'pending'
    ],
    [
        'id' => 'CMP-2024-002',
        'name' => 'Priya Sharma',
        'email' => 'priya@example.com',
        'mobile' => '9876543211',
        'category' => 'water',
        'description' => 'Water leakage from main pipeline near community center. Wasting lot of water.',
        'location' => 'Community Center, Block A',
        'priority' => 'high',
        'status' => 'in_progress'
    ],
    [
        'id' => 'CMP-2024-003',
        'name' => 'Amit Patel',
        'email' => 'amit@example.com',
        'mobile' => '9876543212',
        'category' => 'road',
        'description' => 'Large pothole on highway causing accidents. Needs immediate repair.',
        'location' => 'National Highway 44',
        'priority' => 'urgent',
        'status' => 'resolved'
    ]
];

$created = 0;
$existing = 0;

foreach ($test_complaints as $complaint) {
    // Check if already exists
    $check_query = "SELECT complaint_id FROM complaints WHERE complaint_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $complaint['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Complaint " . htmlspecialchars($complaint['id']) . " already exists</p>";
        $existing++;
    } else {
        // Insert complaint
        $insert_query = "INSERT INTO complaints (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, status, created_at, updated_at) VALUES (?, 1, ?, ?, ?, 'Test Address', ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("sssssssss", 
                $complaint['id'], 
                $complaint['name'], 
                $complaint['email'], 
                $complaint['mobile'], 
                $complaint['category'], 
                $complaint['description'], 
                $complaint['location'], 
                $complaint['priority'], 
                $complaint['status']
            );
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Created complaint: " . htmlspecialchars($complaint['id']) . "</p>";
                $created++;
                
                // Create timeline entry
                $timeline_query = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'submitted', 'Complaint submitted successfully', NOW())";
                $timeline_stmt = $conn->prepare($timeline_query);
                $timeline_stmt->bind_param("s", $complaint['id']);
                $timeline_stmt->execute();
                $timeline_stmt->close();
                
                // Add status-specific timeline entries
                if ($complaint['status'] === 'in_progress') {
                    $timeline_query2 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'assigned', 'Complaint assigned to department', DATE_SUB(NOW(), INTERVAL 2 DAY))";
                    $timeline_stmt2 = $conn->prepare($timeline_query2);
                    $timeline_stmt2->bind_param("s", $complaint['id']);
                    $timeline_stmt2->execute();
                    $timeline_stmt2->close();
                } elseif ($complaint['status'] === 'resolved') {
                    $timeline_query2 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'assigned', 'Complaint assigned to department', DATE_SUB(NOW(), INTERVAL 5 DAY))";
                    $timeline_stmt2 = $conn->prepare($timeline_query2);
                    $timeline_stmt2->bind_param("s", $complaint['id']);
                    $timeline_stmt2->execute();
                    $timeline_stmt2->close();
                    
                    $timeline_query3 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'in_progress', 'Work started on complaint', DATE_SUB(NOW(), INTERVAL 3 DAY))";
                    $timeline_stmt3 = $conn->prepare($timeline_query3);
                    $timeline_stmt3->bind_param("s", $complaint['id']);
                    $timeline_stmt3->execute();
                    $timeline_stmt3->close();
                    
                    $timeline_query4 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'resolved', 'Complaint successfully resolved', DATE_SUB(NOW(), INTERVAL 1 DAY))";
                    $timeline_stmt4 = $conn->prepare($timeline_query4);
                    $timeline_stmt4->bind_param("s", $complaint['id']);
                    $timeline_stmt4->execute();
                    $timeline_stmt4->close();
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to create " . htmlspecialchars($complaint['id']) . ": " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>✅ Created: $created new complaints</p>";
echo "<p>⚠️ Already existed: $existing complaints</p>";

if ($created > 0) {
    echo "<hr>";
    echo "<h3>🎯 Test Complaint IDs Created:</h3>";
    echo "<ul>";
    foreach ($test_complaints as $complaint) {
        echo "<li><strong>" . htmlspecialchars($complaint['id']) . "</strong> - " . htmlspecialchars($complaint['name']) . " (" . htmlspecialchars($complaint['status']) . ")</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p style='text-align: center;'>";
    echo "<a href='track.php' style='background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📋 Test Tracking</a>";
    echo "<a href='track_working.php' style='background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px;'>🔍 Alternative Tracker</a>";
    echo "</p>";
} else {
    echo "<p>All test complaints already exist. You can now test the tracking system.</p>";
    echo "<p><a href='track.php'>📋 Go to Tracking</a></p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #3498db; }
ul { list-style-type: none; padding: 0; }
li { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #3498db; }
</style>
