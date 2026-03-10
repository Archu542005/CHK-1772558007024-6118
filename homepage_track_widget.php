<?php
require_once 'config.php';

// Handle AJAX tracking request
if (isset($_POST['ajax_track'])) {
    $complaint_id = clean_input($_POST['complaint_id']);
    
    // Simple query
    $query = "SELECT * FROM complaints WHERE complaint_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $complaint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $complaint = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'complaint' => $complaint
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Complaint not found'
        ]);
    }
    exit;
}
?>

<!-- Add this tracking widget to your homepage -->
<div class="homepage-track-widget">
    <div class="track-widget-header">
        <h3><i class="fas fa-search"></i> Track Your Complaint</h3>
        <p>Enter your Complaint ID to check status</p>
    </div>
    
    <form id="homepageTrackForm">
        <div class="track-input-group">
            <input type="text" 
                   id="homeComplaintId" 
                   placeholder="Complaint ID (e.g., CMP-2024-001)" 
                   required>
            <button type="submit">
                <i class="fas fa-search"></i> Track
            </button>
        </div>
    </form>
    
    <div id="homeTrackResults" style="display: none;">
        <!-- Results will appear here -->
    </div>
    
    <div class="sample-ids">
        <small>Try: <a href="#" onclick="testHomeTrack('CMP-2024-001')">CMP-2024-001</a> | 
               <a href="#" onclick="testHomeTrack('CMP-2024-002')">CMP-2024-002</a></small>
    </div>
</div>

<script>
function testHomeTrack(id) {
    document.getElementById('homeComplaintId').value = id;
    document.getElementById('homepageTrackForm').dispatchEvent(new Event('submit'));
}

document.getElementById('homepageTrackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const complaintId = document.getElementById('homeComplaintId').value.trim();
    
    if (!complaintId) {
        alert('Please enter a Complaint ID');
        return;
    }
    
    // Show loading
    const resultsDiv = document.getElementById('homeTrackResults');
    resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    resultsDiv.style.display = 'block';
    
    // Send request
    const formData = new FormData();
    formData.append('ajax_track', '1');
    formData.append('complaint_id', complaintId);
    
    fetch('homepage_track_widget.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showHomeTrackResults(data.complaint);
        } else {
            showHomeTrackError(data.message);
        }
    })
    .catch(error => {
        showHomeTrackError('Error: ' + error.message);
    });
});

function showHomeTrackResults(complaint) {
    const resultsDiv = document.getElementById('homeTrackResults');
    resultsDiv.innerHTML = `
        <div class="home-track-success">
            <h4><i class="fas fa-check-circle" style="color: green;"></i> Complaint Found</h4>
            <div class="home-track-details">
                <p><strong>ID:</strong> ${complaint.complaint_id}</p>
                <p><strong>Name:</strong> ${complaint.name}</p>
                <p><strong>Status:</strong> <span class="home-status-badge ${complaint.status}">${complaint.status.replace('_', ' ')}</span></p>
                <p><strong>Submitted:</strong> ${new Date(complaint.created_at).toLocaleDateString()}</p>
            </div>
            <button class="home-track-close" onclick="closeHomeTrack()">×</button>
        </div>
    `;
}

function showHomeTrackError(message) {
    const resultsDiv = document.getElementById('homeTrackResults');
    resultsDiv.innerHTML = `
        <div class="home-track-error">
            <h4><i class="fas fa-exclamation-triangle" style="color: red;"></i> Not Found</h4>
            <p>${message}</p>
            <button class="home-track-close" onclick="closeHomeTrack()">×</button>
        </div>
    `;
}

function closeHomeTrack() {
    document.getElementById('homeTrackResults').style.display = 'none';
    document.getElementById('homeComplaintId').value = '';
}
</script>

<style>
.homepage-track-widget {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    max-width: 500px;
}

.track-widget-header {
    text-align: center;
    margin-bottom: 20px;
}

.track-widget-header h3 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.track-widget-header p {
    color: #7f8c8d;
    margin: 0;
}

.track-input-group {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.track-input-group input {
    flex: 1;
    padding: 12px;
    border: 2px solid #3498db;
    border-radius: 5px;
    font-size: 14px;
}

.track-input-group button {
    background: #3498db;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    cursor: pointer;
    white-space: nowrap;
}

.track-input-group button:hover {
    background: #2980b9;
}

.sample-ids {
    text-align: center;
    color: #7f8c8d;
}

.sample-ids a {
    color: #3498db;
    text-decoration: none;
}

.sample-ids a:hover {
    text-decoration: underline;
}

.home-track-success, .home-track-error {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    position: relative;
    margin-top: 15px;
}

.home-track-success {
    border-left: 4px solid #27ae60;
}

.home-track-error {
    border-left: 4px solid #e74c3c;
}

.home-track-success h4, .home-track-error h4 {
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.home-track-details p {
    margin: 8px 0;
    display: flex;
    justify-content: space-between;
}

.home-status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    color: white;
}

.home-status-badge.pending { background: #95a5a6; }
.home-status-badge.in_progress { background: #f39c12; }
.home-status-badge.resolved { background: #27ae60; }

.home-track-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #7f8c8d;
}

.home-track-close:hover {
    color: #2c3e50;
}

@media (max-width: 768px) {
    .track-input-group {
        flex-direction: column;
    }
    
    .home-track-details p {
        flex-direction: column;
        gap: 2px;
    }
}
</style>
