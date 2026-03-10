<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
$complaint = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = trim($_POST['complaint_id']);
    
    if (empty($complaint_id)) {
        $error = 'Please enter a Complaint ID';
    } else {
        // Simple query
        $query = "SELECT * FROM complaints WHERE complaint_id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $complaint_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $complaint = $result->fetch_assoc();
            } else {
                $error = 'Complaint not found';
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

// Get sample complaints for testing
$sample_complaints = [];
$sample_query = "SELECT complaint_id, name, status FROM complaints ORDER BY created_at DESC LIMIT 5";
$sample_result = $conn->query($sample_query);
if ($sample_result) {
    while ($row = $sample_result->fetch_assoc()) {
        $sample_complaints[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Complaint Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .search-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .form-group input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-group button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .form-group button:hover {
            background: #2980b9;
        }
        
        .sample-complaints {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .sample-complaints h3 {
            margin-bottom: 10px;
            color: #1976d2;
        }
        
        .sample-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sample-btn {
            background: white;
            border: 1px solid #1976d2;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .sample-btn:hover {
            background: #1976d2;
            color: white;
        }
        
        .result-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .result-box h2 {
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #2c3e50;
        }
        
        .detail-value {
            flex: 1;
            color: #555;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .status-pending { background: #95a5a6; }
        .status-in_progress { background: #f39c12; }
        .status-resolved { background: #27ae60; }
        
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .debug-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .debug-info h3 {
            margin-bottom: 10px;
            color: #6c757d;
        }
        
        .debug-info pre {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Simple Complaint Tracker</h1>
            <p>Enter your Complaint ID to track status</p>
        </div>
        
        <?php if (!empty($sample_complaints)): ?>
        <div class="sample-complaints">
            <h3>📋 Sample Complaint IDs (click to test):</h3>
            <div class="sample-list">
                <?php foreach ($sample_complaints as $sample): ?>
                    <button class="sample-btn" onclick="testTrack('<?php echo htmlspecialchars($sample['complaint_id']); ?>')">
                        <?php echo htmlspecialchars($sample['complaint_id']); ?> (<?php echo htmlspecialchars($sample['status']); ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="search-box">
            <form method="POST">
                <div class="form-group">
                    <input type="text" 
                           name="complaint_id" 
                           placeholder="Enter Complaint ID (e.g., TEST-001)" 
                           value="<?php echo isset($_POST['complaint_id']) ? htmlspecialchars($_POST['complaint_id']) : ''; ?>"
                           required>
                    <button type="submit">
                        <i class="fas fa-search"></i> Track
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-triangle"></i>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($complaint): ?>
        <div class="result-box">
            <h2><i class="fas fa-check-circle"></i> Complaint Found!</h2>
            
            <div class="detail-row">
                <div class="detail-label">Complaint ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['complaint_id']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['name']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['email']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Mobile:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['mobile']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Category:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['category']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Location:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['location']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Priority:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['priority']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge status-<?php echo htmlspecialchars($complaint['status']); ?>">
                        <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($complaint['status']))); ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value"><?php echo htmlspecialchars($complaint['description']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Submitted:</div>
                <div class="detail-value"><?php echo date('d M Y H:i', strtotime($complaint['created_at'])); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Last Updated:</div>
                <div class="detail-value"><?php echo date('d M Y H:i', strtotime($complaint['updated_at'] ?? $complaint['created_at'])); ?></div>
            </div>
            
            <div class="debug-info">
                <h3>🔍 Debug Information</h3>
                <pre><?php print_r($complaint); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="debug_database.php" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-tools"></i> Debug Database
            </a>
        </div>
    </div>

    <script>
        function testTrack(complaintId) {
            document.querySelector('input[name="complaint_id"]').value = complaintId;
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
