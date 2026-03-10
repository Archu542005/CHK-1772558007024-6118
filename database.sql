-- Smart Grievance Management System Database
-- Database: GS_system

-- Create database
CREATE DATABASE IF NOT EXISTS GS_system;
USE GS_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(10) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_mobile (mobile)
);

-- Admin users table for department administrators
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department ENUM('garbage', 'water', 'road', 'electricity', 'higher') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_department (department)
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    category ENUM('garbage', 'water', 'road', 'electricity', 'other') NOT NULL,
    description TEXT NOT NULL,
    location TEXT NOT NULL,
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'resolved', 'escalated') DEFAULT 'pending',
    image_path VARCHAR(255),
    assigned_to INT NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    escalated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_complaint_id (complaint_id),
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
);

-- Complaint timeline/history table
CREATE TABLE IF NOT EXISTS complaint_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) NOT NULL,
    action ENUM('submitted', 'assigned', 'in_progress', 'escalated', 'resolved', 'closed') NOT NULL,
    description TEXT,
    performed_by INT NULL, -- admin user ID if performed by admin
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_complaint_timeline (complaint_id),
    INDEX idx_performed_at (performed_at)
);

-- Escalated complaints table for higher authority
CREATE TABLE IF NOT EXISTS escalated_complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) UNIQUE NOT NULL,
    original_department ENUM('garbage', 'water', 'road', 'electricity', 'other') NOT NULL,
    escalation_reason TEXT NOT NULL,
    escalation_level ENUM('level1', 'level2', 'level3') DEFAULT 'level1',
    assigned_higher_authority INT NULL,
    higher_notes TEXT NULL,
    status ENUM('pending_review', 'under_review', 'reassigned', 'resolved') DEFAULT 'pending_review',
    escalated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_higher_authority) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_escalated_complaint (complaint_id),
    INDEX idx_escalation_level (escalation_level),
    INDEX idx_status (status)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- null if for admin
    admin_id INT NULL, -- null if for user
    complaint_id VARCHAR(20) NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('complaint_submitted', 'status_updated', 'escalated', 'resolved', 'admin_assigned') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read),
    INDEX idx_admin_notifications (admin_id, is_read),
    INDEX idx_complaint_notifications (complaint_id)
);

-- Department statistics table
CREATE TABLE IF NOT EXISTS department_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department ENUM('garbage', 'water', 'road', 'electricity', 'other') NOT NULL,
    total_complaints INT DEFAULT 0,
    pending_complaints INT DEFAULT 0,
    in_progress_complaints INT DEFAULT 0,
    resolved_complaints INT DEFAULT 0,
    escalated_complaints INT DEFAULT 0,
    avg_resolution_time DECIMAL(10,2) DEFAULT 0, -- in hours
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_department (department),
    INDEX idx_department_stats (department)
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default admin users
INSERT INTO admin_users (username, password, department, full_name, email, mobile) VALUES
('garbage_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garbage', 'Garbage Department Admin', 'garbage@grievance.gov.in', '9876543210'),
('water_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'water', 'Water Department Admin', 'water@grievance.gov.in', '9876543211'),
('road_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'road', 'Road Department Admin', 'road@grievance.gov.in', '9876543212'),
('electricity_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'electricity', 'Electricity Department Admin', 'electricity@grievance.gov.in', '9876543213'),
('higher_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'higher', 'Higher Authority Admin', 'higher@grievance.gov.in', '9876543214');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('escalation_hours', '48', 'Hours after which complaints are automatically escalated'),
('complaint_id_prefix', 'CMP', 'Prefix for complaint IDs'),
('max_file_size', '5242880', 'Maximum file size for image uploads in bytes'),
('allowed_file_types', 'jpg,jpeg,png,gif', 'Allowed file types for image uploads'),
('auto_escalation_enabled', 'true', 'Enable automatic complaint escalation'),
('notification_email_enabled', 'true', 'Enable email notifications'),
('site_name', 'Smart Grievance Portal', 'Name of the grievance portal'),
('contact_email', 'support@grievance.gov.in', 'Contact email address'),
('contact_phone', '1800-123-4567', 'Contact phone number');

-- Insert department statistics
INSERT INTO department_stats (department, total_complaints, pending_complaints, in_progress_complaints, resolved_complaints, escalated_complaints) VALUES
('garbage', 0, 0, 0, 0, 0),
('water', 0, 0, 0, 0, 0),
('road', 0, 0, 0, 0, 0),
('electricity', 0, 0, 0, 0, 0),
('other', 0, 0, 0, 0, 0);

-- Create triggers for automatic complaint escalation
DELIMITER //

-- Trigger to check for escalation when complaint is updated
CREATE TRIGGER check_escalation_on_update
BEFORE UPDATE ON complaints
FOR EACH ROW
BEGIN
    -- Check if complaint is still pending after 48 hours
    IF NEW.status = 'pending' AND OLD.status = 'pending' THEN
        IF TIMESTAMPDIFF(HOUR, NEW.created_at, NOW()) > (SELECT CAST(setting_value AS UNSIGNED) FROM system_settings WHERE setting_key = 'escalation_hours') THEN
            SET NEW.status = 'escalated';
            SET NEW.escalated_at = NOW();
        END IF;
    END IF;
END//

-- Trigger to update department statistics
CREATE TRIGGER update_dept_stats_on_insert
AFTER INSERT ON complaints
FOR EACH ROW
BEGIN
    UPDATE department_stats 
    SET 
        total_complaints = total_complaints + 1,
        pending_complaints = pending_complaints + 1
    WHERE department = NEW.category;
END//

CREATE TRIGGER update_dept_stats_on_update
AFTER UPDATE ON complaints
FOR EACH ROW
BEGIN
    -- Update old department stats
    IF OLD.category != NEW.category THEN
        UPDATE department_stats 
        SET 
            total_complaints = total_complaints - 1,
            pending_complaints = CASE WHEN OLD.status = 'pending' THEN pending_complaints - 1 ELSE pending_complaints END,
            in_progress_complaints = CASE WHEN OLD.status = 'in_progress' THEN in_progress_complaints - 1 ELSE in_progress_complaints END,
            resolved_complaints = CASE WHEN OLD.status = 'resolved' THEN resolved_complaints - 1 ELSE resolved_complaints END,
            escalated_complaints = CASE WHEN OLD.status = 'escalated' THEN escalated_complaints - 1 ELSE escalated_complaints END
        WHERE department = OLD.category;
        
        UPDATE department_stats 
        SET 
            total_complaints = total_complaints + 1,
            pending_complaints = CASE WHEN NEW.status = 'pending' THEN pending_complaints + 1 ELSE pending_complaints END,
            in_progress_complaints = CASE WHEN NEW.status = 'in_progress' THEN in_progress_complaints + 1 ELSE in_progress_complaints END,
            resolved_complaints = CASE WHEN NEW.status = 'resolved' THEN resolved_complaints + 1 ELSE resolved_complaints END,
            escalated_complaints = CASE WHEN NEW.status = 'escalated' THEN escalated_complaints + 1 ELSE escalated_complaints END
        WHERE department = NEW.category;
    ELSE
        -- Update same department stats
        UPDATE department_stats 
        SET 
            pending_complaints = CASE 
                WHEN OLD.status = 'pending' AND NEW.status != 'pending' THEN pending_complaints - 1
                WHEN OLD.status != 'pending' AND NEW.status = 'pending' THEN pending_complaints + 1
                ELSE pending_complaints
            END,
            in_progress_complaints = CASE 
                WHEN OLD.status = 'in_progress' AND NEW.status != 'in_progress' THEN in_progress_complaints - 1
                WHEN OLD.status != 'in_progress' AND NEW.status = 'in_progress' THEN in_progress_complaints + 1
                ELSE in_progress_complaints
            END,
            resolved_complaints = CASE 
                WHEN OLD.status = 'resolved' AND NEW.status != 'resolved' THEN resolved_complaints - 1
                WHEN OLD.status != 'resolved' AND NEW.status = 'resolved' THEN resolved_complaints + 1
                ELSE resolved_complaints
            END,
            escalated_complaints = CASE 
                WHEN OLD.status = 'escalated' AND NEW.status != 'escalated' THEN escalated_complaints - 1
                WHEN OLD.status != 'escalated' AND NEW.status = 'escalated' THEN escalated_complaints + 1
                ELSE escalated_complaints
            END
        WHERE department = NEW.category;
    END IF;
END//

DELIMITER ;

-- Create stored procedures for common operations
DELIMITER //

-- Procedure to generate unique complaint ID
CREATE PROCEDURE generate_complaint_id(OUT new_complaint_id VARCHAR(20))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE sequence_num INT;
    
    SELECT setting_value INTO prefix FROM system_settings WHERE setting_key = 'complaint_id_prefix';
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(complaint_id, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
    INTO sequence_num 
    FROM complaints 
    WHERE complaint_id LIKE CONCAT(prefix, '%');
    
    SET new_complaint_id = CONCAT(prefix, LPAD(sequence_num, 6, '0'), YEAR(NOW()));
END//

-- Procedure to escalate complaints automatically
CREATE PROCEDURE auto_escalate_complaints()
BEGIN
    DECLARE escalation_hours INT;
    DECLARE done INT DEFAULT FALSE;
    DECLARE complaint_id_var VARCHAR(20);
    DECLARE user_id_var INT;
    DECLARE cursor_cur CURSOR FOR 
        SELECT complaint_id, user_id 
        FROM complaints 
        WHERE status = 'pending' 
        AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > (
            SELECT CAST(setting_value AS UNSIGNED) 
            FROM system_settings 
            WHERE setting_key = 'escalation_hours'
        );
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SELECT CAST(setting_value AS UNSIGNED) INTO escalation_hours 
    FROM system_settings 
    WHERE setting_key = 'escalation_hours';
    
    OPEN cursor_cur;
    
    read_loop: LOOP
        FETCH cursor_cur INTO complaint_id_var, user_id_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Update complaint status
        UPDATE complaints 
        SET status = 'escalated', escalated_at = NOW() 
        WHERE complaint_id = complaint_id_var;
        
        -- Add to escalated complaints table
        INSERT INTO escalated_complaints (complaint_id, original_department, escalation_reason)
        SELECT complaint_id, category, 
               CONCAT('Complaint automatically escalated after ', escalation_hours, ' hours')
        FROM complaints 
        WHERE complaint_id = complaint_id_var;
        
        -- Add to timeline
        INSERT INTO complaint_timeline (complaint_id, action, description)
        VALUES (complaint_id_var, 'escalated', 'Complaint automatically escalated due to timeout');
        
        -- Create notification for user
        INSERT INTO notifications (user_id, complaint_id, title, message, type)
        VALUES (user_id_var, complaint_id_var, 
                'Complaint Escalated', 
                'Your complaint has been escalated to higher authority due to delayed response.',
                'escalated');
    END LOOP;
    
    CLOSE cursor_cur;
END//

DELIMITER ;

-- Sample data for testing (optional)
-- INSERT INTO users (name, email, mobile, address, password) VALUES
-- ('Test User', 'test@example.com', '1234567890', '123 Test Street, Test City', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create indexes for better performance
CREATE INDEX idx_complaints_user_status ON complaints(user_id, status);
CREATE INDEX idx_complaints_category_status ON complaints(category, status);
CREATE INDEX idx_timeline_complaint_action ON complaint_timeline(complaint_id, action);
CREATE INDEX idx_notifications_user_type ON notifications(user_id, type, is_read);

-- Show database structure
SHOW TABLES;
DESCRIBE users;
DESCRIBE admin_users;
DESCRIBE complaints;
DESCRIBE complaint_timeline;
DESCRIBE escalated_complaints;
DESCRIBE notifications;
DESCRIBE department_stats;
DESCRIBE system_settings;
