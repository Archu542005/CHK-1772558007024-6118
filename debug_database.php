<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Debug Tool</h2>";

// Test database connection
require_once 'config.php';

echo "<h3>1. Database Connection Test</h3>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
}

// Check if database exists
echo "<h3>2. Database Check</h3>";
$db_check = $conn->query("SHOW DATABASES LIKE 'GS_system'");
if ($db_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Database 'GS_system' exists</p>";
} else {
    echo "<p style='color: red;'>❌ Database 'GS_system' not found</p>";
    echo "<p>Please import the database.sql file first</p>";
    exit;
}

// Check if tables exist
echo "<h3>3. Table Check</h3>";
$tables_to_check = ['complaints', 'complaint_timeline'];
foreach ($tables_to_check as $table) {
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' not found</p>";
    }
}

// Check if complaints table has data
echo "<h3>4. Data Check</h3>";
$complaint_count = $conn->query("SELECT COUNT(*) as count FROM complaints");
$count = $complaint_count->fetch_assoc()['count'];
echo "<p>Total complaints in database: $count</p>";

if ($count == 0) {
    echo "<p style='color: orange;'>⚠️ No complaints found. Creating sample data...</p>";
    
    // Create sample complaint
    $sample_id = 'TEST-' . date('Y-m-d-His');
    $insert_query = "INSERT INTO complaints 
        (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, status, created_at) 
        VALUES (?, 1, 'Test User', 'test@example.com', '1234567890', '123 Test Street', 'garbage', 'Test complaint for debugging', 'Test Location', 'normal', 'pending', NOW())";
    
    $stmt = $conn->prepare($insert_query);
    if ($stmt) {
        $stmt->bind_param("s", $sample_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Created sample complaint: $sample_id</p>";
            
            // Create timeline entry
            $timeline_query = "INSERT INTO complaint_timeline (complaint_id, action, description, performed_at) VALUES (?, 'submitted', 'Complaint submitted', NOW())";
            $timeline_stmt = $conn->prepare($timeline_query);
            $timeline_stmt->bind_param("s", $sample_id);
            $timeline_stmt->execute();
            $timeline_stmt->close();
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create sample: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Show sample complaints
echo "<h3>5. Sample Complaints</h3>";
$sample_query = "SELECT complaint_id, name, status, created_at FROM complaints ORDER BY created_at DESC LIMIT 5";
$sample_result = $conn->query($sample_query);

if ($sample_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Created</th></tr>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['complaint_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No complaints found</p>";
}

// Test tracking query
echo "<h3>6. Tracking Query Test</h3>";
$test_id = null;
if ($sample_result->num_rows > 0) {
    $sample_result->data_seek(0);
    $first_row = $sample_result->fetch_assoc();
    $test_id = $first_row['complaint_id'];
    
    echo "<p>Testing with complaint ID: $test_id</p>";
    
    $track_query = "SELECT * FROM complaints WHERE complaint_id = ?";
    $track_stmt = $conn->prepare($track_query);
    
    if ($track_stmt) {
        $track_stmt->bind_param("s", $test_id);
        $track_stmt->execute();
        $track_result = $track_stmt->get_result();
        
        if ($track_result->num_rows > 0) {
            echo "<p style='color: green;'>✅ Tracking query works! Found complaint</p>";
            $complaint = $track_result->fetch_assoc();
            echo "<pre>";
            print_r($complaint);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Tracking query failed - no results</p>";
        }
        $track_stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Query preparation failed: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>7. Quick Links</h3>";
echo "<p><a href='track_simple.php'>📋 Go to Simple Tracker</a></p>";
echo "<p><a href='index.html'>🏠 Go to Homepage</a></p>";
echo "<p><a href='database.sql'>📥 Download Database Schema</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow: auto; }
</style>
