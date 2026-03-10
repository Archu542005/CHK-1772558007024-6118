<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.html');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user's complaints
$complaints = get_user_complaints($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - Smart Grievance Portal</title>
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
                <li><a href="user_dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="submit_complaint.html" class="nav-link">Submit Complaint</a></li>
                <li><a href="track_complaint.html" class="nav-link">Track Complaint</a></li>
                <li><a href="profile.php" class="nav-link active">My Account</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- My Complaints Section -->
    <section class="complaints-section">
        <div class="container">
            <div class="section-header">
                <h2><i class="fas fa-clipboard-list"></i> My Complaints</h2>
                <p>View and manage all your submitted complaints</p>
                <a href="submit_complaint.html" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Submit New Complaint
                </a>
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
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($complaint['complaint_id']); ?></strong></td>
                                    <td>
                                        <span class="category-badge <?php echo $complaint['category']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo substr(htmlspecialchars($complaint['description']), 0, 60) . '...'; ?></td>
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
                                    <td><?php echo date('M d, Y', strtotime($complaint['updated_at'])); ?></td>
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
                    <h3>No complaints yet</h3>
                    <p>You haven't submitted any complaints yet.</p>
                    <a href="submit_complaint.html" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Submit Your First Complaint
                    </a>
                </div>
            <?php endif; ?>
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
                        <li><a href="user_dashboard.php">Dashboard</a></li>
                        <li><a href="submit_complaint.html">Submit Complaint</a></li>
                        <li><a href="track_complaint.html">Track Complaint</a></li>
                        <li><a href="profile.php">My Account</a></li>
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
    
    <style>
        .complaints-section {
            padding: 100px 0 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .section-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .section-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .section-header p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .complaints-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
        
        .category-badge.garbage { background: #e74c3c; }
        .category-badge.water { background: #3498db; }
        .category-badge.road { background: #f39c12; }
        .category-badge.electricity { background: #9b59b6; }
        .category-badge.other { background: #95a5a6; }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .priority-badge.normal { background: #95a5a6; }
        .priority-badge.high { background: #f39c12; }
        .priority-badge.urgent { background: #e74c3c; }
        
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
        
        .btn-small.btn-primary { background: #3498db; }
        .btn-small.btn-secondary { background: #95a5a6; }
        
        .no-complaints {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .no-complaints i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .no-complaints h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .no-complaints p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
