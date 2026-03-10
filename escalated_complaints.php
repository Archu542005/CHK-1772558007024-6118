<?php
require_once 'config.php';

// Check if admin is logged in and is higher authority
if (!is_admin_logged_in() || $_SESSION['admin_department'] !== 'higher') {
    redirect('admin_login.html');
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

// Get escalated complaints
$escalated_query = $conn->prepare("SELECT ec.*, c.*, u.name as user_name, u.email as user_email 
                                   FROM escalated_complaints ec 
                                   JOIN complaints c ON ec.complaint_id = c.complaint_id 
                                   JOIN users u ON c.user_id = u.id 
                                   ORDER BY ec.escalated_at DESC");
$escalated_query->execute();
$escalated_result = $escalated_query->get_result();
$escalated_complaints = $escalated_result->fetch_all(MYSQLI_ASSOC);
$escalated_query->close();

// Get escalation statistics
$stats_query = $conn->prepare("SELECT 
    COUNT(*) as total_escalated,
    SUM(CASE WHEN ec.status = 'pending_review' THEN 1 ELSE 0 END) as pending_review,
    SUM(CASE WHEN ec.status = 'under_review' THEN 1 ELSE 0 END) as under_review,
    SUM(CASE WHEN ec.status = 'reassigned' THEN 1 ELSE 0 END) as reassigned,
    SUM(CASE WHEN ec.status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM escalated_complaints ec");
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
$stats_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalated Complaints - Smart Grievance Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-city"></i>
                <span>Smart Grievance Portal</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="register.html" class="nav-link">Register</a></li>
                <li><a href="login.html" class="nav-link">User Login</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint.html" class="nav-link">Track Complaint</a></li>
                <li><a href="admin_login.html" class="nav-link">Admin Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Escalated Complaints Section -->
    <section class="escalated-section">
        <div class="container">
            <!-- Header -->
            <div class="section-header">
                <div class="header-content">
                    <h2><i class="fas fa-exclamation-triangle"></i> Escalated Complaints</h2>
                    <p>Higher Authority Dashboard - Manage escalated complaints</p>
                </div>
                <div class="header-actions">
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="admin_logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_escalated']; ?></h3>
                        <p>Total Escalated</p>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_review']; ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-card in-progress">
                    <div class="stat-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['under_review']; ?></h3>
                        <p>Under Review</p>
                    </div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
                <div class="stat-card reassigned">
                    <div class="stat-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['reassigned']; ?></h3>
                        <p>Reassigned</p>
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="filter-section">
                <div class="filter-controls">
                    <select id="statusFilter" onchange="filterEscalated()">
                        <option value="">All Status</option>
                        <option value="pending_review">Pending Review</option>
                        <option value="under_review">Under Review</option>
                        <option value="reassigned">Reassigned</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <select id="departmentFilter" onchange="filterEscalated()">
                        <option value="">All Departments</option>
                        <option value="garbage">Garbage</option>
                        <option value="water">Water</option>
                        <option value="road">Road</option>
                        <option value="electricity">Electricity</option>
                        <option value="other">Other</option>
                    </select>
                    <select id="priorityFilter" onchange="filterEscalated()">
                        <option value="">All Priority</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="autoEscalateCheck()">
                        <i class="fas fa-sync"></i> Check Auto Escalations
                    </button>
                    <button class="btn btn-secondary" onclick="generateEscalationReport()">
                        <i class="fas fa-download"></i> Generate Report
                    </button>
                </div>
            </div>

            <!-- Escalated Complaints Table -->
            <div class="escalated-table">
                <?php if (!empty($escalated_complaints)): ?>
                    <table id="escalatedTable">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>User Name</th>
                                <th>Original Dept</th>
                                <th>Description</th>
                                <th>Priority</th>
                                <th>Escalation Level</th>
                                <th>Escalation Reason</th>
                                <th>Escalated On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escalated_complaints as $complaint): ?>
                                <tr data-status="<?php echo $complaint['status']; ?>" data-department="<?php echo $complaint['original_department']; ?>" data-priority="<?php echo $complaint['priority']; ?>">
                                    <td><strong><?php echo htmlspecialchars($complaint['complaint_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($complaint['user_name']); ?></td>
                                    <td>
                                        <span class="category-badge <?php echo $complaint['original_department']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['original_department'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo substr(htmlspecialchars($complaint['description']), 0, 50) . '...'; ?></td>
                                    <td>
                                        <span class="priority-badge <?php echo $complaint['priority']; ?>">
                                            <?php echo ucfirst($complaint['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="escalation-level-badge <?php echo $complaint['escalation_level']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['escalation_level'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($complaint['escalation_reason'], 0, 30)) . '...'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['escalated_at'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-small btn-primary" onclick="viewEscalatedComplaint('<?php echo htmlspecialchars($complaint['complaint_id']); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <select class="action-select" onchange="handleEscalatedAction('<?php echo htmlspecialchars($complaint['complaint_id']); ?>', this.value)">
                                                <option value="">Action</option>
                                                <option value="review">Start Review</option>
                                                <option value="reassign">Reassign</option>
                                                <option value="resolve">Resolve</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-escalated">
                        <i class="fas fa-shield-alt"></i>
                        <h3>No Escalated Complaints</h3>
                        <p>There are currently no escalated complaints requiring higher authority intervention.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Escalated Complaint Details Modal -->
    <div id="escalatedModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Escalated Complaint Details</h3>
                <button class="close-btn" onclick="closeEscalatedModal()">&times;</button>
            </div>
            <div class="modal-body" id="escalatedDetails">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEscalatedModal()">Close</button>
                <button class="btn btn-primary" id="takeActionBtn" onclick="takeAction()">Take Action</button>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> <span id="actionTitle">Take Action</span></h3>
                <button class="close-btn" onclick="closeActionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="actionForm">
                    <div class="form-group">
                        <label for="actionType">Action Type</label>
                        <select id="actionType" name="action_type" required onchange="updateActionForm()">
                            <option value="">Select Action</option>
                            <option value="review">Start Review</option>
                            <option value="reassign">Reassign to Department</option>
                            <option value="resolve">Mark as Resolved</option>
                        </select>
                    </div>
                    
                    <div id="reassignOptions" style="display: none;">
                        <div class="form-group">
                            <label for="reassignDepartment">Reassign to Department</label>
                            <select id="reassignDepartment" name="reassign_department">
                                <option value="">Select Department</option>
                                <option value="garbage">Garbage Department</option>
                                <option value="water">Water Department</option>
                                <option value="road">Road Department</option>
                                <option value="electricity">Electricity Department</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="higherNotes">Higher Authority Notes</label>
                        <textarea id="higherNotes" name="higher_notes" rows="4" placeholder="Add your notes and instructions..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="escalationLevel">Escalation Level</label>
                        <select id="escalationLevel" name="escalation_level">
                            <option value="level1">Level 1</option>
                            <option value="level2">Level 2</option>
                            <option value="level3">Level 3</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Action</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Smart Grievance Portal</h3>
                    <p>Making governance accessible and transparent for every citizen.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="register.html">Register</a></li>
                        <li><a href="login.html">Login</a></li>
                        <li><a href="submit_complaint.html">Submit Complaint</a></li>
                        <li><a href="track_complaint.html">Track Complaint</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-phone"></i> Helpline: 1800-123-4567</p>
                    <p><i class="fas fa-envelope"></i> support@grievance.gov.in</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Smart Grievance Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <script>
        let currentComplaintId = '';

        function filterEscalated() {
            const statusFilter = document.getElementById('statusFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;
            const priorityFilter = document.getElementById('priorityFilter').value;
            const rows = document.querySelectorAll('#escalatedTable tbody tr');
            
            rows.forEach(row => {
                const status = row.dataset.status;
                const department = row.dataset.department;
                const priority = row.dataset.priority;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const departmentMatch = !departmentFilter || department === departmentFilter;
                const priorityMatch = !priorityFilter || priority === priorityFilter;
                
                row.style.display = statusMatch && departmentMatch && priorityMatch ? '' : 'none';
            });
        }

        function viewEscalatedComplaint(complaintId) {
            currentComplaintId = complaintId;
            
            // Show loading
            document.getElementById('escalatedDetails').innerHTML = '<p>Loading...</p>';
            document.getElementById('escalatedModal').style.display = 'block';
            
            // Fetch escalated complaint details
            fetch(`get_escalated_details.php?id=${complaintId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayEscalatedDetails(data.complaint, data.timeline);
                    } else {
                        document.getElementById('escalatedDetails').innerHTML = '<p>Error loading complaint details</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('escalatedDetails').innerHTML = '<p>Error loading complaint details</p>';
                });
        }

        function displayEscalatedDetails(complaint, timeline) {
            const detailsHtml = `
                <div class="escalated-info">
                    <div class="detail-row">
                        <span class="label">Complaint ID:</span>
                        <span class="value">${complaint.complaint_id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">User Name:</span>
                        <span class="value">${complaint.user_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Original Department:</span>
                        <span class="value">${complaint.original_department}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Priority:</span>
                        <span class="value">
                            <span class="priority-badge ${complaint.priority}">${complaint.priority}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Description:</span>
                        <span class="value">${complaint.description}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Location:</span>
                        <span class="value">${complaint.location}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Escalation Reason:</span>
                        <span class="value">${complaint.escalation_reason}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Escalation Level:</span>
                        <span class="value">
                            <span class="escalation-level-badge ${complaint.escalation_level}">${complaint.escalation_level}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Escalated On:</span>
                        <span class="value">${complaint.escalated_at}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Current Status:</span>
                        <span class="value">
                            <span class="status-badge ${complaint.status.replace('_', '-')}">${complaint.status}</span>
                        </span>
                    </div>
                    ${complaint.higher_notes ? `
                    <div class="detail-row">
                        <span class="label">Higher Authority Notes:</span>
                        <span class="value">${complaint.higher_notes}</span>
                    </div>
                    ` : ''}
                </div>
                
                ${timeline.length > 0 ? `
                <div class="timeline-section">
                    <h4>Complaint Timeline</h4>
                    <div class="timeline">
                        ${timeline.map(event => `
                        <div class="timeline-item">
                            <div class="timeline-dot ${event.action}"></div>
                            <div class="timeline-content">
                                <h5>${event.action.charAt(0).toUpperCase() + event.action.slice(1).replace('_', ' ')}</h5>
                                <p>${event.description}</p>
                                <small>${event.performed_at}</small>
                            </div>
                        </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('escalatedDetails').innerHTML = detailsHtml;
        }

        function closeEscalatedModal() {
            document.getElementById('escalatedModal').style.display = 'none';
        }

        function takeAction() {
            closeEscalatedModal();
            document.getElementById('actionModal').style.display = 'block';
        }

        function closeActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        function updateActionForm() {
            const actionType = document.getElementById('actionType').value;
            const reassignOptions = document.getElementById('reassignOptions');
            
            if (actionType === 'reassign') {
                reassignOptions.style.display = 'block';
            } else {
                reassignOptions.style.display = 'none';
            }
            
            // Update action title
            const actionTitle = document.getElementById('actionTitle');
            switch(actionType) {
                case 'review':
                    actionTitle.textContent = 'Start Review';
                    break;
                case 'reassign':
                    actionTitle.textContent = 'Reassign Complaint';
                    break;
                case 'resolve':
                    actionTitle.textContent = 'Resolve Complaint';
                    break;
                default:
                    actionTitle.textContent = 'Take Action';
            }
        }

        function handleEscalatedAction(complaintId, action) {
            if (!action) return;
            
            currentComplaintId = complaintId;
            
            // Set action type and open modal
            document.getElementById('actionType').value = action;
            updateActionForm();
            document.getElementById('actionModal').style.display = 'block';
        }

        function autoEscalateCheck() {
            if (confirm('Check for complaints that need automatic escalation?')) {
                fetch('auto_escalate.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Auto-escalation completed. ${data.escalated_count} complaints were escalated.`);
                            location.reload();
                        } else {
                            alert('Auto-escalation failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during auto-escalation.');
                    });
            }
        }

        function generateEscalationReport() {
            window.open('generate_escalation_report.php', '_blank');
        }

        // Action form submission
        document.getElementById('actionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const actionType = document.getElementById('actionType').value;
            const reassignDepartment = document.getElementById('reassignDepartment').value;
            const higherNotes = document.getElementById('higherNotes').value;
            const escalationLevel = document.getElementById('escalationLevel').value;
            
            if (!actionType) {
                alert('Please select an action type');
                return;
            }
            
            // Submit action
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'handle_escalated_action.php';
            
            const complaintIdInput = document.createElement('input');
            complaintIdInput.type = 'hidden';
            complaintIdInput.name = 'complaint_id';
            complaintIdInput.value = currentComplaintId;
            
            const actionTypeInput = document.createElement('input');
            actionTypeInput.type = 'hidden';
            actionTypeInput.name = 'action_type';
            actionTypeInput.value = actionType;
            
            const reassignDepartmentInput = document.createElement('input');
            reassignDepartmentInput.type = 'hidden';
            reassignDepartmentInput.name = 'reassign_department';
            reassignDepartmentInput.value = reassignDepartment;
            
            const higherNotesInput = document.createElement('input');
            higherNotesInput.type = 'hidden';
            higherNotesInput.name = 'higher_notes';
            higherNotesInput.value = higherNotes;
            
            const escalationLevelInput = document.createElement('input');
            escalationLevelInput.type = 'hidden';
            escalationLevelInput.name = 'escalation_level';
            escalationLevelInput.value = escalationLevel;
            
            form.appendChild(complaintIdInput);
            form.appendChild(actionTypeInput);
            form.appendChild(reassignDepartmentInput);
            form.appendChild(higherNotesInput);
            form.appendChild(escalationLevelInput);
            document.body.appendChild(form);
            form.submit();
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .escalated-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-content h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header-content p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .filter-section {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .escalated-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .escalated-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .escalated-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .escalated-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .escalation-level-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .escalation-level-badge.level1 {
            background: #f39c12;
        }
        
        .escalation-level-badge.level2 {
            background: #e67e22;
        }
        
        .escalation-level-badge.level3 {
            background: #e74c3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .action-select {
            padding: 5px 10px;
            font-size: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .no-escalated {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .no-escalated i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-escalated h3 {
            margin-bottom: 10px;
        }
        
        .escalated-info .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .escalated-info .detail-row:last-child {
            border-bottom: none;
        }
        
        .escalated-info .label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 150px;
        }
        
        .escalated-info .value {
            color: #7f8c8d;
        }
        
        #reassignOptions {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .header-actions {
                justify-content: center;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-controls,
            .filter-actions {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</body>
</html>
