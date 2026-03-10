<?php
require_once 'config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('admin_login.html');
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_department = $_SESSION['admin_department'];
$admin_email = $_SESSION['admin_email'];

// Get complaints based on department
if ($admin_department === 'higher') {
    // Higher authority can see escalated complaints
    $complaints_query = $conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email 
                                     FROM complaints c 
                                     JOIN users u ON c.user_id = u.id 
                                     WHERE c.status = 'escalated' 
                                     ORDER BY c.escalated_at DESC");
    $complaints_query->bind_param("");
    $complaints_query->execute();
} else {
    // Department admins see their department's complaints
    $complaints_query = $conn->prepare("SELECT c.*, u.name as user_name, u.email as user_email 
                                     FROM complaints c 
                                     JOIN users u ON c.user_id = u.id 
                                     WHERE c.category = ? 
                                     ORDER BY c.created_at DESC");
    $complaints_query->bind_param("s", $admin_department);
    $complaints_query->execute();
}

$complaints_result = $complaints_query->get_result();
$complaints = $complaints_result->fetch_all(MYSQLI_ASSOC);
$complaints_query->close();

// Get department statistics
if ($admin_department === 'higher') {
    $stats_query = $conn->prepare("SELECT 
        COUNT(*) as total_escalated,
        SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) as pending_escalated,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM complaints WHERE status IN ('escalated', 'in_progress', 'resolved')");
    $stats_query->bind_param("");
} else {
    $stats_query = $conn->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) as escalated
        FROM complaints WHERE category = ?");
    $stats_query->bind_param("s", $admin_department);
}

$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
$stats_query->close();

// Get admin notifications
$notifications_query = $conn->prepare("SELECT * FROM notifications WHERE admin_id = ? ORDER BY created_at DESC LIMIT 5");
$notifications_query->bind_param("i", $admin_id);
$notifications_query->execute();
$notifications_result = $notifications_query->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
$notifications_query->close();

// Mark notifications as read
if (!empty($notifications)) {
    $notification_ids = array_column($notifications, 'id');
    $ids_str = implode(',', $notification_ids);
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE id IN ($ids_str) AND admin_id = $admin_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Grievance Portal</title>
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

    <!-- Admin Dashboard Section -->
    <section class="admin-dashboard-section">
        <div class="container">
            <!-- Admin Header -->
            <div class="admin-header">
                <div class="admin-info">
                    <h2><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?></h2>
                    <p><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $admin_department))); ?> Department</p>
                </div>
                <div class="admin-actions">
                    <div class="notification-dropdown">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count"><?php echo count($notifications); ?></span>
                        </button>
                        <div class="notification-menu">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="notification-time"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-notifications">No new notifications</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="admin_logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total'] ?? $stats['total_escalated']; ?></h3>
                        <p><?php echo $admin_department === 'higher' ? 'Escalated' : 'Total'; ?> Complaints</p>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending'] ?? $stats['pending_escalated']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card in-progress">
                    <div class="stat-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['in_progress']; ?></h3>
                        <p>In Progress</p>
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
                <?php if ($admin_department !== 'higher'): ?>
                    <div class="stat-card escalated">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['escalated']; ?></h3>
                            <p>Escalated</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-cards">
                    <a href="#" class="action-card" onclick="showComplaintModal()">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h4>Assign Complaint</h4>
                        <p>Assign complaints to staff</p>
                    </a>
                    <a href="#" class="action-card" onclick="generateReport()">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Generate Report</h4>
                        <p>Download department reports</p>
                    </a>
                    <a href="admin_settings.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h4>Settings</h4>
                        <p>Manage department settings</p>
                    </a>
                    <?php if ($admin_department === 'higher'): ?>
                        <a href="escalated_complaints.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4>Escalated Cases</h4>
                            <p>View escalated complaints</p>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Complaints Management -->
            <div class="complaints-management">
                <div class="section-header">
                    <h3><?php echo $admin_department === 'higher' ? 'Escalated' : 'Department'; ?> Complaints</h3>
                    <div class="filter-actions">
                        <select id="statusFilter" onchange="filterComplaints()">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <?php if ($admin_department !== 'higher'): ?>
                                <option value="escalated">Escalated</option>
                            <?php endif; ?>
                        </select>
                        <select id="priorityFilter" onchange="filterComplaints()">
                            <option value="">All Priority</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <?php if (!empty($complaints)): ?>
                    <div class="complaints-table">
                        <table id="complaintsTable">
                            <thead>
                                <tr>
                                    <th>Complaint ID</th>
                                    <th>User Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Location</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr data-status="<?php echo $complaint['status']; ?>" data-priority="<?php echo $complaint['priority']; ?>">
                                        <td><strong><?php echo htmlspecialchars($complaint['complaint_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($complaint['user_name']); ?></td>
                                        <td>
                                            <span class="category-badge <?php echo $complaint['category']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo substr(htmlspecialchars($complaint['description']), 0, 50) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['location']); ?></td>
                                        <td>
                                            <span class="priority-badge <?php echo $complaint['priority']; ?>">
                                                <?php echo ucfirst($complaint['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-small btn-primary" onclick="viewComplaint('<?php echo htmlspecialchars($complaint['complaint_id']); ?>')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <select class="status-select" onchange="updateStatus('<?php echo htmlspecialchars($complaint['complaint_id']); ?>', this.value)">
                                                    <option value="">Update Status</option>
                                                    <option value="pending" <?php echo $complaint['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    <?php if ($admin_department !== 'higher'): ?>
                                                        <option value="escalated" <?php echo $complaint['status'] === 'escalated' ? 'selected' : ''; ?>>Escalate</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-complaints">
                        <i class="fas fa-inbox"></i>
                        <h4>No <?php echo $admin_department === 'higher' ? 'escalated' : 'department'; ?> complaints</h4>
                        <p>There are currently no complaints to manage.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Complaint Details Modal -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-list"></i> Complaint Details</h3>
                <button class="close-btn" onclick="closeComplaintModal()">&times;</button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Complaint details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeComplaintModal()">Close</button>
                <button class="btn btn-primary" id="updateStatusBtn" onclick="openStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Update Complaint Status</h3>
                <button class="close-btn" onclick="closeStatusModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm">
                    <div class="form-group">
                        <label for="newStatus">New Status</label>
                        <select id="newStatus" name="status" required>
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <?php if ($admin_department !== 'higher'): ?>
                                <option value="escalated">Escalate</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adminNotes">Admin Notes</label>
                        <textarea id="adminNotes" name="admin_notes" rows="4" placeholder="Add notes about this status update..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
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

        // Notification dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBtn = document.querySelector('.notification-btn');
            const notificationMenu = document.querySelector('.notification-menu');
            
            if (notificationBtn && notificationMenu) {
                notificationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    notificationMenu.classList.remove('active');
                });
                
                notificationMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        function filterComplaints() {
            const statusFilter = document.getElementById('statusFilter').value;
            const priorityFilter = document.getElementById('priorityFilter').value;
            const rows = document.querySelectorAll('#complaintsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.dataset.status;
                const priority = row.dataset.priority;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const priorityMatch = !priorityFilter || priority === priorityFilter;
                
                row.style.display = statusMatch && priorityMatch ? '' : 'none';
            });
        }

        function viewComplaint(complaintId) {
            currentComplaintId = complaintId;
            
            // Show loading
            document.getElementById('complaintDetails').innerHTML = '<p>Loading...</p>';
            document.getElementById('complaintModal').style.display = 'block';
            
            // Fetch complaint details
            fetch(`get_complaint_details.php?id=${complaintId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayComplaintDetails(data.complaint, data.timeline);
                    } else {
                        document.getElementById('complaintDetails').innerHTML = '<p>Error loading complaint details</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('complaintDetails').innerHTML = '<p>Error loading complaint details</p>';
                });
        }

        function displayComplaintDetails(complaint, timeline) {
            const detailsHtml = `
                <div class="complaint-info">
                    <div class="detail-row">
                        <span class="label">Complaint ID:</span>
                        <span class="value">${complaint.complaint_id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">User Name:</span>
                        <span class="value">${complaint.user_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Email:</span>
                        <span class="value">${complaint.user_email}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Category:</span>
                        <span class="value">${complaint.category}</span>
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
                        <span class="label">Current Status:</span>
                        <span class="value">
                            <span class="status-badge ${complaint.status.replace('_', '-')}">${complaint.status}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Date Submitted:</span>
                        <span class="value">${complaint.created_at}</span>
                    </div>
                    ${complaint.image_path ? `
                    <div class="detail-row">
                        <span class="label">Attachment:</span>
                        <span class="value">
                            <a href="uploads/${complaint.image_path}" target="_blank" class="view-image-btn">
                                <i class="fas fa-image"></i> View Image
                            </a>
                        </span>
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
            
            document.getElementById('complaintDetails').innerHTML = detailsHtml;
        }

        function closeComplaintModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }

        function openStatusUpdate() {
            closeComplaintModal();
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        function updateStatus(complaintId, newStatus) {
            if (!newStatus) return;
            
            if (confirm(`Are you sure you want to update the status to "${newStatus}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'update_status.php';
                
                const complaintIdInput = document.createElement('input');
                complaintIdInput.type = 'hidden';
                complaintIdInput.name = 'complaint_id';
                complaintIdInput.value = complaintId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                form.appendChild(complaintIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Status update form submission
        document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const status = document.getElementById('newStatus').value;
            const notes = document.getElementById('adminNotes').value;
            
            if (!status) {
                alert('Please select a status');
                return;
            }
            
            // Submit status update
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_status.php';
            
            const complaintIdInput = document.createElement('input');
            complaintIdInput.type = 'hidden';
            complaintIdInput.name = 'complaint_id';
            complaintIdInput.value = currentComplaintId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            
            const notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'admin_notes';
            notesInput.value = notes;
            
            form.appendChild(complaintIdInput);
            form.appendChild(statusInput);
            form.appendChild(notesInput);
            document.body.appendChild(form);
            form.submit();
        });

        function generateReport() {
            window.open('generate_report.php?department=<?php echo $admin_department; ?>', '_blank');
        }

        function showComplaintModal() {
            alert('Complaint assignment feature coming soon!');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .admin-dashboard-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-info h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .admin-info p {
            color: #7f8c8d;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .admin-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .notification-dropdown {
            position: relative;
        }
        
        .notification-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .notification-btn:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .notification-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }
        
        .notification-menu.active {
            display: block;
        }
        
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .notification-message {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .notification-time {
            color: #95a5a6;
            font-size: 12px;
        }
        
        .no-notifications {
            padding: 20px;
            text-align: center;
            color: #7f8c8d;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.pending .stat-icon {
            background: #f39c12;
        }
        
        .stat-card.in-progress .stat-icon {
            background: #3498db;
        }
        
        .stat-card.resolved .stat-icon {
            background: #27ae60;
        }
        
        .stat-card.escalated .stat-icon {
            background: #e74c3c;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
        }
        
        .stat-content p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .quick-actions h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .action-card h4 {
            margin-bottom: 10px;
        }
        
        .action-card p {
            opacity: 0.9;
            margin: 0;
        }
        
        .complaints-management {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h3 {
            color: #2c3e50;
            margin: 0;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-actions select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .complaints-table {
            overflow-x: auto;
        }
        
        .complaints-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .complaints-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .complaints-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .category-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .category-badge.garbage {
            background: #e74c3c;
        }
        
        .category-badge.water {
            background: #3498db;
        }
        
        .category-badge.road {
            background: #f39c12;
        }
        
        .category-badge.electricity {
            background: #9b59b6;
        }
        
        .category-badge.other {
            background: #95a5a6;
        }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .priority-badge.normal {
            background: #95a5a6;
        }
        
        .priority-badge.high {
            background: #f39c12;
        }
        
        .priority-badge.urgent {
            background: #e74c3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-small.btn-primary {
            background: #3498db;
        }
        
        .btn-small.btn-primary:hover {
            background: #2980b9;
        }
        
        .status-select {
            padding: 5px 10px;
            font-size: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .no-complaints {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .no-complaints i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-complaints h4 {
            margin-bottom: 10px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .close-btn:hover {
            opacity: 1;
        }
        
        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .complaint-info .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .complaint-info .detail-row:last-child {
            border-bottom: none;
        }
        
        .complaint-info .label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 150px;
        }
        
        .complaint-info .value {
            color: #7f8c8d;
        }
        
        .view-image-btn {
            color: #3498db;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #3498db;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .view-image-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .timeline-section {
            margin-top: 30px;
        }
        
        .timeline-section h4 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ecf0f1;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-dot {
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .timeline-dot.submitted,
        .timeline-dot.resolved {
            background: #27ae60;
        }
        
        .timeline-dot.assigned,
        .timeline-dot.in-progress {
            background: #3498db;
        }
        
        .timeline-dot.escalated {
            background: #e74c3c;
        }
        
        .timeline-content h5 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .timeline-content p {
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .timeline-content small {
            color: #95a5a6;
        }
        
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-actions {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</body>
</html>
