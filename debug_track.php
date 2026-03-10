<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Complaint Tracking Debug Tool</h2>";

// Check database connection
echo "<h3>Database Connection Check</h3>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Check if required tables exist
echo "<h3>Database Tables Check</h3>";
$tables = ['complaints', 'complaint_timeline', 'department_mapping'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
    }
}

// Show sample complaint IDs from database
echo "<h3>Sample Complaint IDs in Database</h3>";
$query = "SELECT complaint_id, status, created_at FROM complaints ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Complaint ID</th><th>Status</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['complaint_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No complaints found in database</p>";
}

// Test specific complaint ID if provided
if (isset($_GET['test_id'])) {
    $test_id = clean_input($_GET['test_id']);
    echo "<h3>Testing Complaint ID: " . htmlspecialchars($test_id) . "</h3>";
    
    // Test main query
    $query = "SELECT c.*, 
             CASE 
                WHEN c.auto_assigned_department IS NOT NULL THEN 
                    (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.auto_assigned_department)
                ELSE 
                    (SELECT dm.department_name FROM department_mapping dm WHERE dm.category_name = c.category)
             END as department_name
             FROM complaints c 
             WHERE c.complaint_id = ?";
    
    echo "<p><strong>Query:</strong> " . htmlspecialchars($query) . "</p>";
    echo "<p><strong>Parameter:</strong> " . htmlspecialchars($test_id) . "</p>";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo "<p style='color: red;'>❌ Query preparation failed: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("s", $test_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p><strong>Result count:</strong> " . $result->num_rows . "</p>";
        
        if ($result->num_rows > 0) {
            $complaint = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ Complaint found!</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($complaint as $key => $value) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($key) . "</td>";
                echo "<td>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test timeline query
            echo "<h4>Timeline Query Test</h4>";
            $timeline_query = "SELECT ct.*, 
                             CASE 
                                 WHEN ct.performed_by IS NOT NULL THEN 
                                     CASE 
                                         WHEN ct.performed_by IN (SELECT id FROM admin_users) THEN 
                                             (SELECT CONCAT(full_name, ' (Admin)') FROM admin_users WHERE id = ct.performed_by)
                                         ELSE 
                                             (SELECT CONCAT(name, ' (User)') FROM users WHERE id = ct.performed_by)
                                     END
                                 ELSE 'System'
                             END as performer_name
                             FROM complaint_timeline ct 
                             WHERE ct.complaint_id = ? 
                             ORDER BY ct.performed_at ASC";
            
            $timeline_stmt = $conn->prepare($timeline_query);
            if ($timeline_stmt === false) {
                echo "<p style='color: red;'>❌ Timeline query preparation failed: " . $conn->error . "</p>";
            } else {
                $timeline_stmt->bind_param("s", $test_id);
                $timeline_stmt->execute();
                $timeline_result = $timeline_stmt->get_result();
                
                echo "<p><strong>Timeline entries found:</strong> " . $timeline_result->num_rows . "</p>";
                
                if ($timeline_result->num_rows > 0) {
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                    echo "<tr><th>Action</th><th>Description</th><th>Performed At</th><th>Performer</th></tr>";
                    while ($row = $timeline_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['action']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['performed_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['performer_name']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color: orange;'>⚠️ No timeline entries found for this complaint</p>";
                }
                $timeline_stmt->close();
            }
            
        } else {
            echo "<p style='color: red;'>❌ Complaint not found in database</p>";
        }
        $stmt->close();
    }
}

// Test form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = clean_input($_POST['complaint_id']);
    echo "<h3>Form Submission Test</h3>";
    echo "<p><strong>Submitted Complaint ID:</strong> " . htmlspecialchars($complaint_id) . "</p>";
    
    // Redirect to test with this ID
    echo "<p><a href='debug_track.php?test_id=" . urlencode($complaint_id) . "'>Test this Complaint ID</a></p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Tracking Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .test-form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .test-form input { padding: 10px; margin-right: 10px; }
        .test-form button { padding: 10px 20px; background: #3498db; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="test-form">
        <h3>Test Complaint Tracking</h3>
        <form method="POST">
            <input type="text" name="complaint_id" placeholder="Enter Complaint ID to test" required>
            <button type="submit">Test Tracking</button>
        </form>
    </div>
    
    <div>
        <h3>Quick Tests</h3>
        <p><a href="debug_track.php?test_id=CMP-2024-001">Test with CMP-2024-001</a></p>
        <p><a href="debug_track.php?test_id=CMP-2024-002">Test with CMP-2024-002</a></p>
        <p><a href="debug_track.php?test_id=test123">Test with invalid ID</a></p>
    </div>
    
    <hr>
    
    <h3>Common Issues & Solutions</h3>
    <ul>
        <li><strong>Database connection failed:</strong> Check XAMPP MySQL service is running</li>
        <li><strong>Tables don't exist:</strong> Import the database.sql file</li>
        <li><strong>No complaints found:</strong> Submit a test complaint first</li>
        <li><strong>Invalid Complaint ID format:</strong> Check the correct format in database</li>
        <li><strong>Timeline empty:</strong> Check if timeline entries are being created when complaints are submitted</li>
    </ul>
    
    <hr>
    
    <h3>Manual Database Check</h3>
    <p>You can also check your database directly using phpMyAdmin:</p>
    <ol>
        <li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>
        <li>Select your database (GS_system)</li>
        <li>Browse the 'complaints' table to see existing complaints</li>
        <li>Copy a complaint ID and test it above</li>
    </ol>
</body>
</html>
