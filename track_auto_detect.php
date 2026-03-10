<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
$complaint = null;
$error = '';
$all_complaints = [];

// Get all complaints for auto-suggest
$all_query = "SELECT complaint_id, name, status FROM complaints ORDER BY created_at DESC";
$all_result = $conn->query($all_query);
if ($all_result) {
    while ($row = $all_result->fetch_assoc()) {
        $all_complaints[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_id = trim($_POST['complaint_id']);
    
    if (empty($complaint_id)) {
        $error = 'Please enter a Complaint ID';
    } else {
        // Try exact match first
        $query = "SELECT * FROM complaints WHERE complaint_id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $complaint_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $complaint = $result->fetch_assoc();
            } else {
                // Try case-insensitive match
                $query_case = "SELECT * FROM complaints WHERE LOWER(complaint_id) = LOWER(?)";
                $stmt_case = $conn->prepare($query_case);
                $stmt_case->bind_param("s", $complaint_id);
                $stmt_case->execute();
                $result_case = $stmt_case->get_result();
                
                if ($result_case->num_rows > 0) {
                    $complaint = $result_case->fetch_assoc();
                    $error = "Found with case-insensitive match: " . $complaint['complaint_id'];
                } else {
                    // Try partial match
                    $query_partial = "SELECT * FROM complaints WHERE complaint_id LIKE ?";
                    $like_pattern = "%" . $complaint_id . "%";
                    $stmt_partial = $conn->prepare($query_partial);
                    $stmt_partial->bind_param("s", $like_pattern);
                    $stmt_partial->execute();
                    $result_partial = $stmt_partial->get_result();
                    
                    if ($result_partial->num_rows > 0) {
                        $suggestions = [];
                        while ($row = $result_partial->fetch_assoc()) {
                            $suggestions[] = $row['complaint_id'];
                        }
                        $error = "Not found. Did you mean: " . implode(", ", $suggestions);
                    } else {
                        $error = "Complaint not found. Available IDs: " . implode(", ", array_column($all_complaints, 'complaint_id'));
                    }
                }
                $stmt_case->close();
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Detect Complaint Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; text-align: center; }
        .header h1 { color: #2c3e50; margin-bottom: 10px; }
        
        .search-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .form-group input { flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; }
        .form-group input:focus { border-color: #3498db; outline: none; }
        .form-group button { background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .form-group button:hover { background: #2980b9; }
        
        .available-ids { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .available-ids h3 { margin-bottom: 15px; color: #1976d2; }
        .id-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .id-card { background: white; padding: 15px; border-radius: 5px; border: 1px solid #1976d2; cursor: pointer; transition: all 0.3s ease; }
        .id-card:hover { background: #1976d2; color: white; transform: translateY(-2px); }
        .id-card .id { font-weight: bold; font-size: 14px; }
        .id-card .name { font-size: 12px; margin-top: 5px; opacity: 0.8; }
        .id-card .status { font-size: 11px; margin-top: 5px; padding: 2px 8px; border-radius: 10px; display: inline-block; }
        .status-pending { background: #95a5a6; color: white; }
        .status-in_progress { background: #f39c12; color: white; }
        .status-resolved { background: #27ae60; color: white; }
        
        .result-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .result-box h2 { color: #27ae60; margin-bottom: 20px; }
        .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; width: 150px; color: #2c3e50; }
        .detail-value { flex: 1; color: #555; }
        
        .error-box { background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #ffeaa7; }
        .error-box p { margin: 5px 0; }
        
        .format-info { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .format-info h3 { margin-bottom: 10px; color: #6c757d; }
        .format-list { display: flex; gap: 15px; flex-wrap: wrap; }
        .format-item { background: white; padding: 8px 12px; border-radius: 5px; border: 1px solid #dee2e6; font-family: monospace; }
        
        @media (max-width: 768px) {
            .form-group { flex-direction: column; }
            .form-group input, .form-group button { width: 100%; }
            .id-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Auto-Detect Complaint Tracker</h1>
            <p>Automatically detects and matches complaint ID formats</p>
        </div>
        
        <div class="format-info">
            <h3>📋 Supported ID Formats:</h3>
            <div class="format-list">
                <div class="format-item">CMP-2024-001</div>
                <div class="format-item">TEST-001</div>
                <div class="format-item">123</div>
                <div class="format-item">Any format in database</div>
            </div>
        </div>
        
        <?php if (!empty($all_complaints)): ?>
        <div class="available-ids">
            <h3>📋 Available Complaint IDs (Click to Track):</h3>
            <div class="id-grid">
                <?php foreach ($all_complaints as $complaint): ?>
                    <div class="id-card" onclick="trackComplaint('<?php echo htmlspecialchars($complaint['complaint_id']); ?>')">
                        <div class="id"><?php echo htmlspecialchars($complaint['complaint_id']); ?></div>
                        <div class="name"><?php echo htmlspecialchars($complaint['name']); ?></div>
                        <div class="status status-<?php echo htmlspecialchars($complaint['status']); ?>">
                            <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($complaint['status']))); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="search-box">
            <form method="POST">
                <div class="form-group">
                    <input type="text" 
                           name="complaint_id" 
                           placeholder="Enter Complaint ID (any format)" 
                           value="<?php echo isset($_POST['complaint_id']) ? htmlspecialchars($_POST['complaint_id']) : ''; ?>"
                           required>
                    <button type="submit">
                        <i class="fas fa-search"></i> Track
                    </button>
                </div>
            </form>
            
            <?php if (!empty($all_complaints)): ?>
                <p style="font-size: 12px; color: #6c757d;">
                    <i class="fas fa-info-circle"></i> 
                    Try: <strong><?php echo htmlspecialchars($all_complaints[0]['complaint_id']); ?></strong> or click any ID above
                </p>
            <?php endif; ?>
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
                <div class="detail-value" style="font-weight: bold; color: #2c3e50;"><?php echo htmlspecialchars($complaint['complaint_id']); ?></div>
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
                    <span class="status-badge status-<?php echo htmlspecialchars($complaint['status']); ?>" style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white;">
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
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="check_complaint_ids.php" style="color: #3498db; text-decoration: none; margin-right: 20px;">
                <i class="fas fa-tools"></i> Check ID Formats
            </a>
            <a href="index.html" style="color: #3498db; text-decoration: none;">
                <i class="fas fa-home"></i> Homepage
            </a>
        </div>
    </div>

    <script>
        function trackComplaint(complaintId) {
            document.querySelector('input[name="complaint_id"]').value = complaintId;
            document.querySelector('form').submit();
        }
        
        // Auto-complete functionality
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="complaint_id"]');
            const complaintIds = <?php echo json_encode(array_column($all_complaints, 'complaint_id')); ?>;
            
            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                const suggestions = complaintIds.filter(id => id.toLowerCase().includes(value));
                
                if (suggestions.length === 1 && suggestions[0].toLowerCase() === value) {
                    // Exact match found
                    this.style.borderColor = '#27ae60';
                } else if (suggestions.length > 0) {
                    // Partial matches found
                    this.style.borderColor = '#f39c12';
                } else {
                    // No matches
                    this.style.borderColor = '#e74c3c';
                }
            });
        });
    </script>
</body>
</html>
