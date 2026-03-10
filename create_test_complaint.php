<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create test complaint
function createTestComplaint() {
    global $conn;
    
    // Check if test complaints already exist
    $checkQuery = "SELECT COUNT(*) as count FROM complaints WHERE complaint_id LIKE 'TEST-%'";
    $result = $conn->query($checkQuery);
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo "<p style='color: green;'>✅ Test complaints already exist!</p>";
        return;
    }
    
    // Create test complaints
    $testComplaints = [
        [
            'id' => 'TEST-001',
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
            'id' => 'TEST-002',
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
            'id' => 'TEST-003',
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
    
    foreach ($testComplaints as $complaint) {
        // Insert complaint
        $insertQuery = "INSERT INTO complaints (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, status, created_at, updated_at) VALUES (?, 1, ?, ?, ?, 'Test Address', ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($insertQuery);
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
                echo "<p style='color: green;'>✅ Created test complaint: " . htmlspecialchars($complaint['id']) . "</p>";
                
                // Create timeline entries
                $timelineQuery = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'submitted', 'Complaint submitted successfully', NOW())";
                $timelineStmt = $conn->prepare($timelineQuery);
                $timelineStmt->bind_param("s", $complaint['id']);
                $timelineStmt->execute();
                
                // Add status-specific timeline entries
                if ($complaint['status'] === 'in_progress') {
                    $timelineQuery2 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'assigned', 'Complaint assigned to department', DATE_SUB(NOW(), INTERVAL 2 DAY))";
                    $timelineStmt2 = $conn->prepare($timelineQuery2);
                    $timelineStmt2->bind_param("s", $complaint['id']);
                    $timelineStmt2->execute();
                } elseif ($complaint['status'] === 'resolved') {
                    $timelineQuery2 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'assigned', 'Complaint assigned to department', DATE_SUB(NOW(), INTERVAL 5 DAY))";
                    $timelineStmt2 = $conn->prepare($timelineQuery2);
                    $timelineStmt2->bind_param("s", $complaint['id']);
                    $timelineStmt2->execute();
                    
                    $timelineQuery3 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'in_progress', 'Work started on complaint', DATE_SUB(NOW(), INTERVAL 3 DAY))";
                    $timelineStmt3 = $conn->prepare($timelineQuery3);
                    $timelineStmt3->bind_param("s", $complaint['id']);
                    $timelineStmt3->execute();
                    
                    $timelineQuery4 = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'resolved', 'Complaint successfully resolved', DATE_SUB(NOW(), INTERVAL 1 DAY))";
                    $timelineStmt4 = $conn->prepare($timelineQuery4);
                    $timelineStmt4->bind_param("s", $complaint['id']);
                    $timelineStmt4->execute();
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to create " . htmlspecialchars($complaint['id']) . ": " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_test'])) {
    createTestComplaint();
    
    // Redirect to tracking page
    echo "<script>setTimeout(() => { window.location.href = 'track_working.php'; }, 2000);</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test Complaints</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        
        .icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        p {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .test-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .test-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .test-info ul {
            color: #7f8c8d;
            padding-left: 20px;
        }
        
        .test-info li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-database"></i>
        </div>
        <h1>Test Complaint Setup</h1>
        <p>This page will create sample complaints in the database for testing the tracking functionality.</p>
        
        <div class="test-info">
            <h3>📋 Test Complaints to Create:</h3>
            <ul>
                <li><strong>TEST-001</strong> - Garbage collection issue (Pending)</li>
                <li><strong>TEST-002</strong> - Water leakage problem (In Progress)</li>
                <li><strong>TEST-003</strong> - Road pothole repair (Resolved)</li>
            </ul>
            <p>Each complaint will have complete timeline entries for realistic tracking.</p>
        </div>
        
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_test'])): ?>
            <div style="color: green; margin: 20px 0;">
                <i class="fas fa-check-circle"></i> Test complaints created successfully! Redirecting to tracking page...
            </div>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="create_test" class="btn">
                    <i class="fas fa-plus"></i> Create Test Complaints
                </button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="track_working.php" class="btn btn-secondary">
                <i class="fas fa-search"></i> Go to Tracking
            </a>
        </div>
    </div>
</body>
</html>
