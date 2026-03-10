<?php
require_once 'config.php';

class DepartmentRouter {
    private $conn;
    private $keywords = [];
    private $departmentMapping = [];
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->loadKeywords();
        $this->loadDepartmentMapping();
    }
    
    /**
     * Load all keywords from database
     */
    private function loadKeywords() {
        $query = "SELECT department, keyword, priority FROM department_keywords WHERE is_active = TRUE ORDER BY priority DESC";
        $result = $this->conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $this->keywords[strtolower($row['keyword'])] = [
                'department' => $row['department'],
                'priority' => $row['priority']
            ];
        }
    }
    
    /**
     * Load department mapping
     */
    private function loadDepartmentMapping() {
        $query = "SELECT category_name, department_name, department_code FROM department_mapping WHERE is_active = TRUE";
        $result = $this->conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $this->departmentMapping[$row['category_name']] = [
                'name' => $row['department_name'],
                'code' => $row['department_code']
            ];
        }
    }
    
    /**
     * Auto-assign department based on complaint text and category
     */
    public function autoAssignDepartment($complaintText, $category = null) {
        $startTime = microtime(true);
        
        // Clean and prepare text
        $cleanText = $this->cleanText($complaintText);
        $words = explode(' ', $cleanText);
        
        // Method 1: Keyword-based routing
        $keywordResult = $this->routeByKeywords($words);
        
        // Method 2: Category-based routing (fallback)
        $categoryResult = $this->routeByCategory($category);
        
        // Determine best result
        $finalResult = $this->determineBestResult($keywordResult, $categoryResult);
        
        $processingTime = round((microtime(true) - $startTime) * 1000);
        
        // Log routing decision
        $this->logRouting($finalResult, $complaintText, $category, $processingTime);
        
        return $finalResult;
    }
    
    /**
     * Route based on keyword matching
     */
    private function routeByKeywords($words) {
        $departmentScores = [];
        $matchedKeywords = [];
        
        foreach ($words as $word) {
            $word = strtolower(trim($word));
            
            // Check exact keyword match
            if (isset($this->keywords[$word])) {
                $dept = $this->keywords[$word]['department'];
                $priority = $this->keywords[$word]['priority'];
                
                if (!isset($departmentScores[$dept])) {
                    $departmentScores[$dept] = 0;
                    $matchedKeywords[$dept] = [];
                }
                
                $departmentScores[$dept] += $priority;
                $matchedKeywords[$dept][] = $word;
            }
            
            // Check partial keyword matches
            foreach ($this->keywords as $keyword => $data) {
                if (strpos($word, $keyword) !== false || strpos($keyword, $word) !== false) {
                    $dept = $data['department'];
                    $priority = $data['priority'];
                    
                    if (!isset($departmentScores[$dept])) {
                        $departmentScores[$dept] = 0;
                        $matchedKeywords[$dept] = [];
                    }
                    
                    $departmentScores[$dept] += ($priority * 0.5); // Lower score for partial matches
                    $matchedKeywords[$dept][] = $keyword;
                }
            }
        }
        
        if (empty($departmentScores)) {
            return null;
        }
        
        // Find department with highest score
        $bestDept = array_keys($departmentScores, max($departmentScores))[0];
        $maxScore = $departmentScores[$bestDept];
        $totalScore = array_sum($departmentScores);
        
        return [
            'department' => $bestDept,
            'confidence' => round(($maxScore / $totalScore) * 100, 2),
            'method' => 'keyword',
            'matched_keywords' => $matchedKeywords[$bestDept],
            'score' => $maxScore
        ];
    }
    
    /**
     * Route based on category selection
     */
    private function routeByCategory($category) {
        if (!$category || !isset($this->departmentMapping[$category])) {
            return null;
        }
        
        return [
            'department' => $category,
            'confidence' => 85.00, // High confidence for explicit category selection
            'method' => 'category',
            'matched_keywords' => [$category],
            'score' => 85
        ];
    }
    
    /**
     * Determine the best routing result
     */
    private function determineBestResult($keywordResult, $categoryResult) {
        // Prefer keyword routing if confidence > 70%
        if ($keywordResult && $keywordResult['confidence'] > 70) {
            return $keywordResult;
        }
        
        // Otherwise use category result or fallback to keyword result
        if ($categoryResult) {
            return $categoryResult;
        }
        
        // Fallback to keyword result or default
        if ($keywordResult) {
            return $keywordResult;
        }
        
        // Default to General Administration
        return [
            'department' => 'other',
            'confidence' => 0,
            'method' => 'manual',
            'matched_keywords' => [],
            'score' => 0
        ];
    }
    
    /**
     * Clean and normalize text for processing
     */
    private function cleanText($text) {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove special characters except spaces
        $text = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text);
        
        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        return trim($text);
    }
    
    /**
     * Log routing decision for analysis
     */
    private function logRouting($result, $complaintText, $category, $processingTime) {
        $stmt = $this->conn->prepare("INSERT INTO routing_logs 
            (complaint_id, original_category, assigned_department, routing_method, confidence_score, matched_keywords, processing_time_ms) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $complaintId = $result['complaint_id'] ?? 'TEMP';
        $matchedKeywords = implode(', ', $result['matched_keywords'] ?? []);
        
        $stmt->bind_param("ssssdsi", 
            $complaintId, 
            $category, 
            $result['department'], 
            $result['method'], 
            $result['confidence'], 
            $matchedKeywords, 
            $processingTime
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get department name from code
     */
    public function getDepartmentName($departmentCode) {
        if (isset($this->departmentMapping[$departmentCode])) {
            return $this->departmentMapping[$departmentCode]['name'];
        }
        
        // Check if it's already a department code from keywords
        $deptNames = [
            'PWD' => 'Public Works Department',
            'WAT' => 'Water Supply Department',
            'ELE' => 'Electricity Department',
            'SAN' => 'Sanitation Department',
            'GEN' => 'General Administration'
        ];
        
        return $deptNames[$departmentCode] ?? 'Unknown Department';
    }
    
    /**
     * Add new keyword to database
     */
    public function addKeyword($department, $keyword, $priority = 1) {
        $stmt = $this->conn->prepare("INSERT INTO department_keywords (department, keyword, priority) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $department, $keyword, $priority);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $this->keywords[strtolower($keyword)] = [
                'department' => $department,
                'priority' => $priority
            ];
        }
        
        return $result;
    }
    
    /**
     * Get routing statistics
     */
    public function getRoutingStats() {
        $query = "SELECT routing_method, COUNT(*) as count, AVG(confidence_score) as avg_confidence 
                 FROM routing_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                 GROUP BY routing_method";
        
        $result = $this->conn->query($query);
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * Test routing with sample text
     */
    public function testRouting($testText) {
        return $this->autoAssignDepartment($testText);
    }
}

// Example usage and testing
if (isset($_GET['test_routing']) && $_GET['test_routing'] == '1') {
    $router = new DepartmentRouter($conn);
    
    $testTexts = [
        "There is a big pothole on the main road near my house",
        "No water supply for the past 3 days in our area",
        "Street lights are not working on the highway",
        "Garbage is not being collected from our colony"
    ];
    
    echo "<h3>Auto Department Routing Test Results</h3>";
    foreach ($testTexts as $text) {
        $result = $router->autoAssignDepartment($text);
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Complaint:</strong> " . htmlspecialchars($text) . "<br>";
        echo "<strong>Assigned Department:</strong> " . $router->getDepartmentName($result['department']) . "<br>";
        echo "<strong>Confidence:</strong> " . $result['confidence'] . "%<br>";
        echo "<strong>Method:</strong> " . $result['method'] . "<br>";
        echo "<strong>Matched Keywords:</strong> " . implode(', ', $result['matched_keywords']) . "<br>";
        echo "</div>";
    }
}
?>
