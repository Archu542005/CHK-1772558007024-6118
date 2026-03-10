-- Auto Department Routing System for Grievance Management
-- Add to existing database

-- Keywords table for department routing
CREATE TABLE IF NOT EXISTS department_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(50) NOT NULL,
    keyword VARCHAR(100) NOT NULL,
    priority INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_keyword (keyword)
);

-- Department mapping table
CREATE TABLE IF NOT EXISTS department_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_category (category_name)
);

-- Insert default department mappings
INSERT INTO department_mapping (category_name, department_name, department_code) VALUES
('garbage', 'Sanitation Department', 'SAN'),
('water', 'Water Supply Department', 'WAT'),
('road', 'Public Works Department', 'PWD'),
('electricity', 'Electricity Department', 'ELE'),
('other', 'General Administration', 'GEN');

-- Insert default keywords for each department
INSERT INTO department_keywords (department, keyword, priority) VALUES
-- PWD Department Keywords
('PWD', 'road', 1),
('PWD', 'pothole', 1),
('PWD', 'potholes', 1),
('PWD', 'road damage', 2),
('PWD', 'street', 1),
('PWD', 'highway', 2),
('PWD', 'bridge', 2),
('PWD', 'traffic', 2),
('PWD', 'footpath', 2),
('PWD', 'pavement', 2),

-- Water Supply Department Keywords
('WAT', 'water', 1),
('WAT', 'water supply', 1),
('WAT', 'tap', 2),
('WAT', 'pipeline', 2),
('WAT', 'leakage', 2),
('WAT', 'no water', 1),
('WAT', 'water pressure', 2),
('WAT', 'contamination', 2),
('WAT', 'sewage', 2),
('WAT', 'drainage', 2),

-- Electricity Department Keywords
('ELE', 'electricity', 1),
('ELE', 'light', 1),
('ELE', 'street light', 1),
('ELE', 'streetlight', 1),
('ELE', 'power', 1),
('ELE', 'power cut', 1),
('ELE', 'outage', 2),
('ELE', 'transformer', 2),
('ELE', 'wiring', 2),
('ELE', 'meter', 2),

-- Sanitation Department Keywords
('SAN', 'garbage', 1),
('SAN', 'trash', 1),
('SAN', 'waste', 1),
('SAN', 'sanitation', 1),
('SAN', 'cleanliness', 2),
('SAN', 'dustbin', 2),
('SAN', 'dumping', 2),
('SAN', 'sewer', 2),
('SAN', 'toilet', 2),
('SAN', 'public toilet', 2);

-- Add auto_assigned field to complaints table if not exists
ALTER TABLE complaints 
ADD COLUMN IF NOT EXISTS auto_assigned_department VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS routing_confidence DECIMAL(3,2) NULL,
ADD COLUMN IF NOT EXISTS routing_method ENUM('keyword', 'category', 'manual') DEFAULT 'manual';

-- Create routing logs table
CREATE TABLE IF NOT EXISTS routing_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(50) NOT NULL,
    original_category VARCHAR(50) NULL,
    assigned_department VARCHAR(50) NULL,
    routing_method ENUM('keyword', 'category', 'manual') NOT NULL,
    confidence_score DECIMAL(3,2) NULL,
    matched_keywords TEXT NULL,
    processing_time_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_id (complaint_id),
    INDEX idx_routing_method (routing_method)
);
