<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.html');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user's complaints
$complaints = get_user_complaints($user_id);

// Get user notifications
$notifications_query = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifications_query->bind_param("i", $user_id);
$notifications_query->execute();
$notifications_result = $notifications_query->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
$notifications_query->close();

// Get complaint statistics
$stats_query = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) as escalated
    FROM complaints WHERE user_id = ?");
$stats_query->bind_param("i", $user_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();
$stats_query->close();

// Mark notifications as read
if (!empty($notifications)) {
    $notification_ids = array_column($notifications, 'id');
    $ids_str = implode(',', $notification_ids);
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE id IN ($ids_str) AND user_id = $user_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Smart Grievance Portal</title>
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
                <li><a href="login.html" class="nav-link">Login</a></li>
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

    <!-- Dashboard Section -->
    <section class="dashboard-section">
        <div class="container">
            <!-- Welcome Header -->
            <div class="dashboard-header">
                <div class="welcome-message">
                    <h2><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>Manage your complaints and track their status</p>
                </div>
                <div class="user-actions">
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
                    <a href="logout.php" class="btn btn-secondary">
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
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Complaints</p>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
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
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-cards">
                    <a href="submit_complaint.html" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h4>Submit Complaint</h4>
                        <p>File a new grievance</p>
                    </a>
                    <a href="track_complaint.html" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>Track Complaint</h4>
                        <p>Check complaint status</p>
                    </a>
                    <a href="my_complaints.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h4>My Complaints</h4>
                        <p>View all complaints</p>
                    </a>
                    <a href="profile.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h4>Profile Settings</h4>
                        <p>Update your information</p>
                    </a>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="recent-complaints">
                <div class="section-header">
                    <h3>Recent Complaints</h3>
                    <a href="my_complaints.php" class="view-all">View All</a>
                </div>
                
                <?php if (!empty($complaints)): ?>
                    <div class="complaints-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Complaint ID</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($complaints, 0, 5) as $complaint): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($complaint['complaint_id']); ?></strong></td>
                                        <td>
                                            <span class="category-badge <?php echo $complaint['category']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo substr(htmlspecialchars($complaint['description']), 0, 50) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($complaint['location']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo str_replace('_', '-', $complaint['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="track_complaint.html?id=<?php echo htmlspecialchars($complaint['complaint_id']); ?>" class="btn-small btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($complaint['status'] === 'pending'): ?>
                                                    <a href="edit_complaint.php?id=<?php echo htmlspecialchars($complaint['complaint_id']); ?>" class="btn-small btn-secondary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php endif; ?>
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
                        <h4>No complaints yet</h4>
                        <p>You haven't submitted any complaints yet.</p>
                        <a href="submit_complaint.html" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Submit Your First Complaint
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
    </script>
    
    <style>
        .dashboard-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-message p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .user-actions {
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
        
        .recent-complaints {
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
        }
        
        .section-header h3 {
            color: #2c3e50;
            margin: 0;
        }
        
        .view-all {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .view-all:hover {
            color: #2980b9;
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
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-small.btn-primary {
            background: #3498db;
        }
        
        .btn-small.btn-secondary {
            background: #95a5a6;
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
        
        @media (max-width: 768px) {
            .dashboard-header {
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
                gap: 10px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
