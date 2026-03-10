<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Adding Location Columns to Complaints Table</h2>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ Database connected successfully</p>";

// Check if columns already exist
$check_lat = $conn->query("SHOW COLUMNS FROM complaints LIKE 'latitude'");
$check_lng = $conn->query("SHOW COLUMNS FROM complaints LIKE 'longitude'");

$lat_exists = $check_lat->num_rows > 0;
$lng_exists = $check_lng->num_rows > 0;

if ($lat_exists && $lng_exists) {
    echo "<p style='color: blue;'>ℹ️ Location columns already exist in complaints table</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Adding location columns...</p>";
    
    // Add latitude column
    if (!$lat_exists) {
        $add_lat = "ALTER TABLE complaints ADD COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'Latitude coordinate'";
        if ($conn->query($add_lat)) {
            echo "<p style='color: green;'>✅ Added latitude column</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add latitude column: " . $conn->error . "</p>";
        }
    }
    
    // Add longitude column
    if (!$lng_exists) {
        $add_lng = "ALTER TABLE complaints ADD COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'Longitude coordinate'";
        if ($conn->query($add_lng)) {
            echo "<p style='color: green;'>✅ Added longitude column</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add longitude column: " . $conn->error . "</p>";
        }
    }
    
    // Add index for better performance
    $add_index = "ALTER TABLE complaints ADD INDEX idx_location (latitude, longitude)";
    if ($conn->query($add_index)) {
        echo "<p style='color: green;'>✅ Added location index</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Index already exists or failed to add</p>";
    }
}

// Add sample location data for existing complaints
echo "<h3>Adding Sample Location Data</h3>";

// Get complaints without location data
$sample_query = "SELECT complaint_id, location FROM complaints WHERE latitude IS NULL OR longitude IS NULL LIMIT 10";
$result = $conn->query($sample_query);

if ($result->num_rows > 0) {
    // Sample coordinates for major Indian cities
    $sample_locations = [
        'Delhi' => ['lat' => 28.6139, 'lng' => 77.2090],
        'Mumbai' => ['lat' => 19.0760, 'lng' => 72.8777],
        'Bangalore' => ['lat' => 12.9716, 'lng' => 77.5946],
        'Chennai' => ['lat' => 13.0827, 'lng' => 80.2707],
        'Kolkata' => ['lat' => 22.5726, 'lng' => 88.3639],
        'Hyderabad' => ['lat' => 17.3850, 'lng' => 78.4867],
        'Pune' => ['lat' => 18.5204, 'lng' => 73.8567],
        'Ahmedabad' => ['lat' => 23.0225, 'lng' => 72.5714],
        'Jaipur' => ['lat' => 26.9124, 'lng' => 75.7873],
        'Lucknow' => ['lat' => 26.8467, 'lng' => 80.9462]
    ];
    
    $updated = 0;
    while ($row = $result->fetch_assoc()) {
        // Random city for sample data
        $city_names = array_keys($sample_locations);
        $random_city = $city_names[array_rand($city_names)];
        $coords = $sample_locations[$random_city];
        
        // Add some randomness to avoid exact same coordinates
        $lat = $coords['lat'] + (rand(-100, 100) / 10000);
        $lng = $coords['lng'] + (rand(-100, 100) / 10000);
        
        $update_query = "UPDATE complaints SET latitude = ?, longitude = ? WHERE complaint_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("dds", $lat, $lng, $row['complaint_id']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Updated " . htmlspecialchars($row['complaint_id']) . " with coordinates for $random_city</p>";
            $updated++;
        }
        $stmt->close();
    }
    
    if ($updated > 0) {
        echo "<p style='color: green;'><strong>✅ Added sample coordinates to $updated complaints</strong></p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ All complaints already have location data</p>";
}

// Show current table structure
echo "<h3>Current Complaints Table Structure</h3>";
$structure_query = "DESCRIBE complaints";
$structure_result = $conn->query($structure_query);

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f2f2f2;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $structure_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample data
echo "<h3>Sample Complaint Data with Locations</h3>";
$sample_data_query = "SELECT complaint_id, location, latitude, longitude FROM complaints WHERE latitude IS NOT NULL LIMIT 5";
$sample_data_result = $conn->query($sample_data_query);

if ($sample_data_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Complaint ID</th><th>Location</th><th>Latitude</th><th>Longitude</th></tr>";
    
    while ($row = $sample_data_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['complaint_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td>" . htmlspecialchars($row['latitude']) . "</td>";
        echo "<td>" . htmlspecialchars($row['longitude']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No complaints with location data found</p>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='complaint_heatmap.php' style='color: #3498db; text-decoration: none;'>📊 View Heatmap Visualization</a></li>";
echo "<li><a href='create_test_complaint.php' style='color: #3498db; text-decoration: none;'>📝 Create More Test Complaints</a></li>";
echo "<li><a href='index.html' style='color: #3498db; text-decoration: none;'>🏠 Return to Homepage</a></li>";
echo "</ol>";

echo "<p style='background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;'>";
echo "<strong>📌 Note:</strong> The heatmap uses OpenStreetMap (free) and Leaflet.js. ";
echo "For production use, you might want to consider Google Maps API for more features.";
echo "</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #3498db; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
ol { padding-left: 20px; }
li { margin: 10px 0; }
</style>
