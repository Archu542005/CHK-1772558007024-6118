<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Complaint ID Format Check</h2>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
}

echo "<h3>1. Checking Complaint ID Formats in Database</h3>";

// Get all complaint IDs
$query = "SELECT complaint_id, name, status, created_at FROM complaints ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Complaint ID</th><th>Name</th><th>Status</th><th>Created Date</th><th>Format</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $complaint_id = $row['complaint_id'];
        $format = '';
        
        // Analyze the format
        if (strpos($complaint_id, 'CMP-') === 0) {
            $format = 'CMP-YYYY-NNN';
        } elseif (strpos($complaint_id, 'TEST-') === 0) {
            $format = 'TEST-XXX';
        } elseif (is_numeric($complaint_id)) {
            $format = 'Numeric';
        } else {
            $format = 'Other';
        }
        
        echo "<tr>";
        echo "<td style='font-weight: bold; color: #2c3e50;'>" . htmlspecialchars($complaint_id) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td style='background: #e3f2fd;'>" . $format . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Testing Different ID Formats</h3>";
    
    // Test different formats
    $test_formats = [
        'CMP-2024-001',
        'CMP-2024-002', 
        'TEST-001',
        'TEST-002',
        '1',
        '2',
        '123',
        '456'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Test ID</th><th>Result</th><th>Found?</th></tr>";
    
    foreach ($test_formats as $test_id) {
        $test_query = "SELECT * FROM complaints WHERE complaint_id = ?";
        $test_stmt = $conn->prepare($test_query);
        
        if ($test_stmt) {
            $test_stmt->bind_param("s", $test_id);
            $test_stmt->execute();
            $test_result = $test_stmt->get_result();
            
            if ($test_result->num_rows > 0) {
                $found_complaint = $test_result->fetch_assoc();
                echo "<tr>";
                echo "<td style='font-weight: bold;'>" . htmlspecialchars($test_id) . "</td>";
                echo "<td style='color: green;'>✅ FOUND - " . htmlspecialchars($found_complaint['name']) . "</td>";
                echo "<td style='color: green;'>✅ Yes</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td style='font-weight: bold;'>" . htmlspecialchars($test_id) . "</td>";
                echo "<td style='color: red;'>❌ Not Found</td>";
                echo "<td style='color: red;'>❌ No</td>";
                echo "</tr>";
            }
            $test_stmt->close();
        }
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>❌ No complaints found in database</p>";
    echo "<p>Creating sample complaints with different ID formats...</p>";
    
    // Create complaints with different formats
    $sample_complaints = [
        [
            'id' => 'CMP-2024-001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'category' => 'garbage',
            'description' => 'Garbage collection issue',
            'status' => 'pending'
        ],
        [
            'id' => 'CMP-2024-002',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'category' => 'water',
            'description' => 'Water leakage problem',
            'status' => 'in_progress'
        ],
        [
            'id' => 'TEST-001',
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'category' => 'road',
            'description' => 'Road damage issue',
            'status' => 'resolved'
        ],
        [
            'id' => '123',
            'name' => 'Numeric User',
            'email' => 'numeric@example.com',
            'category' => 'electricity',
            'description' => 'Electricity problem',
            'status' => 'pending'
        ]
    ];
    
    foreach ($sample_complaints as $complaint) {
        $insert_query = "INSERT INTO complaints (complaint_id, user_id, name, email, mobile, address, category, description, location, priority, status, created_at) VALUES (?, 1, ?, ?, ?, 'Test Address', ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("sssssssss", 
                $complaint['id'], 
                $complaint['name'], 
                $complaint['email'], 
                '1234567890',
                $complaint['category'], 
                $complaint['description'], 
                'Test Location',
                'normal',
                $complaint['status']
            );
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Created: " . htmlspecialchars($complaint['id']) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create " . htmlspecialchars($complaint['id']) . ": " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
    
    echo "<p><a href='check_complaint_ids.php'>🔄 Refresh to see created complaints</a></p>";
}

echo "<h3>3. System Settings Check</h3>";
$settings_query = "SELECT * FROM system_settings WHERE setting_key = 'complaint_id_prefix'";
$settings_result = $conn->query($settings_query);

if ($settings_result && $settings_result->num_rows > 0) {
    $setting = $settings_result->fetch_assoc();
    echo "<p><strong>Complaint ID Prefix:</strong> " . htmlspecialchars($setting['setting_value']) . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No complaint ID prefix setting found</p>";
    
    // Insert default setting
    $insert_setting = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES ('complaint_id_prefix', 'CMP-', 'Prefix for complaint IDs')";
    $conn->query($insert_setting);
    echo "<p style='color: green;'>✅ Created default complaint ID prefix: CMP-</p>";
}

echo "<hr>";
echo "<h3>4. Quick Actions</h3>";
echo "<p><a href='track_simple.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Go to Simple Tracker</a></p>";
echo "<p><a href='index.html' style='background: #95a5a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🏠 Go to Homepage</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
p { margin: 10px 0; }
</style>
