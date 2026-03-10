<?php
require_once 'config.php';

// Create a test complaint if none exist
$query = "SELECT COUNT(*) as count FROM complaints";
$result = $conn->query($query);
$count = $result->fetch_assoc()['count'];

echo "<h2>Complaint System Test</h2>";

if ($count == 0) {
    echo "<p style='color: orange;'>⚠️ No complaints found in database. Creating test complaint...</p>";
    
    // Insert test complaint
    $complaint_id = 'CMP-2024-001';
    $insert_query = "INSERT INTO complaints 
        (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, status, created_at) 
        VALUES (?, 1, 'Test User', 'test@example.com', '1234567890', '123 Test Street', 'garbage', 'Test complaint about garbage collection', 'Test Location', 'normal', 'pending', NOW())";
    
    $stmt = $conn->prepare($insert_query);
    if ($stmt) {
        $stmt->bind_param("s", $complaint_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Test complaint created: " . htmlspecialchars($complaint_id) . "</p>";
            
            // Add timeline entry
            $timeline_query = "INSERT INTO complaint_timeline (complaint_id, action, description) VALUES (?, 'submitted', 'Complaint submitted successfully')";
            $timeline_stmt = $conn->prepare($timeline_query);
            $timeline_stmt->bind_param("s", $complaint_id);
            $timeline_stmt->execute();
            $timeline_stmt->close();
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create test complaint: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "<p style='color: green;'>✅ Found $count complaints in database</p>";
}

// Show all complaints
echo "<h3>All Complaints in Database</h3>";
$query = "SELECT complaint_id, name, email, category, status, created_at FROM complaints ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Category</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['complaint_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td><a href='track_complaint_simple.php?complaint_id=" . urlencode($row['complaint_id']) . "'>Track</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No complaints found</p>";
}

// Test tracking function directly
echo "<h3>Direct Function Test</h3>";
if (isset($_GET['test_id'])) {
    $test_id = clean_input($_GET['test_id']);
    
    function testGetComplaintDetails($complaint_id) {
        global $conn;
        
        $query = "SELECT c.*, 
                 CASE 
                    WHEN c.auto_assigned_department IS NOT NULL THEN 
                        (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.auto_assigned_department)
                    ELSE 
                        (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.category)
                 END as department_name
                 FROM complaints c 
                 WHERE c.complaint_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    $complaint = testGetComplaintDetails($test_id);
    
    if ($complaint) {
        echo "<p style='color: green;'>✅ Function test successful for ID: " . htmlspecialchars($test_id) . "</p>";
        echo "<pre>";
        print_r($complaint);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Function test failed for ID: " . htmlspecialchars($test_id) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div>
        <h3>Test Tracking</h3>
        <p><a href="test_complaints.php?test_id=CMP-2024-001">Test with CMP-2024-001</a></p>
        <p><a href="track_complaint_simple.php">Go to Tracking Form</a></p>
        <p><a href="debug_track.php">Go to Debug Tool</a></p>
    </div>
</body>
</html>
