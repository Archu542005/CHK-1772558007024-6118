<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Complaint Map | Grievance Redressal System</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-stats {
            display: flex;
            gap: 30px;
        }
        
        .header-stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Main Layout */
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }
        
        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            width: 100%;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        /* Map Section */
        .map-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .map-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e1e8ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .map-title {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .map-controls {
            display: flex;
            gap: 10px;
        }
        
        .map-btn {
            background: white;
            border: 2px solid #e1e8ed;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .map-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .map-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        #map {
            height: 650px;
            width: 100%;
        }
        
        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .stat-item h4 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #3498db;
        }
        
        /* Status Legend */
        .legend {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .legend h4 {
            margin-bottom: 12px;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        .legend-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        /* Loading */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 650px;
            background: #f8f9fa;
        }
        
        .loading-spinner {
            font-size: 2rem;
            color: #3498db;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom Popup Styles */
        .custom-popup {
            min-width: 280px;
            max-width: 350px;
        }
        
        .popup-header {
            background: #3498db;
            color: white;
            padding: 12px 15px;
            margin: -15px -15px 15px -15px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .popup-body {
            padding: 15px;
        }
        
        .popup-row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .popup-label {
            font-weight: 600;
            color: #2c3e50;
            min-width: 100px;
            font-size: 13px;
        }
        
        .popup-value {
            color: #555;
            font-size: 13px;
            flex: 1;
            word-break: break-word;
        }
        
        .popup-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            margin-top: 10px;
        }
        
        .status-pending { background: #f39c12; }
        .status-in_progress { background: #3498db; }
        .status-resolved { background: #27ae60; }
        .status-escalated { background: #e74c3c; }
        
        /* Real-time Indicator */
        .realtime-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #27ae60;
        }
        
        .realtime-dot {
            width: 8px;
            height: 8px;
            background: #27ae60;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .map-section {
                order: 1;
                min-height: 600px;
            }
            
            #map {
                height: 500px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-stats {
                width: 100%;
                justify-content: space-around;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .legend-items {
                grid-template-columns: 1fr;
            }
            
            .map-controls {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-map-marked-alt"></i>
                Admin Dashboard - Complaint Map
            </h1>
            <div class="header-stats">
                <div class="header-stat">
                    <span class="stat-number" id="totalComplaints">0</span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="header-stat">
                    <span class="stat-number" id="activeComplaints">0</span>
                    <span class="stat-label">Active</span>
                </div>
                <div class="header-stat">
                    <span class="stat-number" id="recentComplaints">0</span>
                    <span class="stat-label">24 Hours</span>
                </div>
                <div class="realtime-indicator">
                    <div class="realtime-dot"></div>
                    <span>Live</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Filters Card -->
            <div class="sidebar-card">
                <h3><i class="fas fa-filter"></i> Filters</h3>
                
                <div class="filter-group">
                    <label for="departmentFilter">Department</label>
                    <select id="departmentFilter">
                        <option value="">All Departments</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter">
                        <option value="">All Status</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="priorityFilter">Priority</label>
                    <select id="priorityFilter">
                        <option value="">All Priorities</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="dateFrom">Date From</label>
                    <input type="date" id="dateFrom">
                </div>
                
                <div class="filter-group">
                    <label for="dateTo">Date To</label>
                    <input type="date" id="dateTo">
                </div>
                
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-check"></i> Apply Filters
                </button>
                
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
            
            <!-- Statistics Card -->
            <div class="sidebar-card">
                <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                
                <div class="stats-grid" id="statsGrid">
                    <!-- Stats will be populated here -->
                </div>
                
                <!-- Status Legend -->
                <div class="legend">
                    <h4><i class="fas fa-palette"></i> Status Legend</h4>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f39c12;"></div>
                            <span>Pending</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #3498db;"></div>
                            <span>In Progress</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #e74c3c;"></div>
                            <span>Escalated</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #27ae60;"></div>
                            <span>Resolved</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Map Section -->
        <section class="map-section">
            <div class="map-header">
                <h2 class="map-title">
                    <i class="fas fa-map"></i>
                    Complaint Locations
                </h2>
                <div class="map-controls">
                    <button class="map-btn active" id="markersBtn" onclick="toggleMarkers()">
                        <i class="fas fa-map-pin"></i> Markers
                    </button>
                    <button class="map-btn" id="clusterBtn" onclick="toggleClusters()">
                        <i class="fas fa-object-group"></i> Clusters
                    </button>
                    <button class="map-btn" onclick="resetView()">
                        <i class="fas fa-compress"></i> Reset View
                    </button>
                    <button class="map-btn" onclick="refreshData()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div id="map">
                <div class="loading">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Global variables
        let map;
        let markersLayer;
        let clusterLayer;
        let allComplaints = [];
        let currentMarkers = [];
        let lastUpdateTime = null;
        let refreshInterval;
        
        // Initialize map
        function initMap() {
            // Create map centered on India (adjust based on your location)
            map = L.map('map').setView([20.5937, 78.9629], 5);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(map);
            
            // Initialize layers
            markersLayer = L.layerGroup().addTo(map);
            
            // Load initial data
            loadComplaintData();
            
            // Start real-time updates
            startRealTimeUpdates();
        }
        
        // Load complaint data from API
        async function loadComplaintData() {
            try {
                const response = await fetch('admin_map_api.php?action=all');
                const data = await response.json();
                
                if (data.success) {
                    allComplaints = data.data.complaints;
                    lastUpdateTime = data.data.last_updated;
                    
                    // Update header stats
                    updateHeaderStats(data.data.stats);
                    
                    // Update filter options
                    updateFilterOptions(data.data.stats);
                    
                    // Update sidebar stats
                    updateSidebarStats(data.data.stats);
                    
                    // Show markers
                    showMarkers();
                    
                    // Hide loading
                    document.querySelector('.loading').style.display = 'none';
                } else {
                    console.error('API Error:', data.error);
                    showError('Failed to load complaint data');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                showError('Network error. Please try again.');
            }
        }
        
        // Show markers on map
        function showMarkers() {
            // Clear existing markers
            markersLayer.clearLayers();
            currentMarkers = [];
            
            allComplaints.forEach(complaint => {
                const marker = createCustomMarker(complaint);
                const popup = createPopupContent(complaint);
                
                marker.bindPopup(popup, {
                    maxWidth: 350,
                    className: 'custom-popup'
                });
                
                markersLayer.addLayer(marker);
                currentMarkers.push(marker);
            });
            
            // Fit map to show all markers
            if (currentMarkers.length > 0) {
                const group = new L.featureGroup(currentMarkers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Create custom marker with icon
        function createCustomMarker(complaint) {
            const iconHtml = `
                <div style="
                    background: ${complaint.color};
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    color: white;
                ">
                    <i class="${complaint.icon.icon}" style="color: ${complaint.icon.color};"></i>
                </div>
            `;
            
            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-marker',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            });
            
            return L.marker([complaint.lat, complaint.lng], { icon: customIcon });
        }
        
        // Create popup content
        function createPopupContent(complaint) {
            return `
                <div class="custom-popup">
                    <div class="popup-header">
                        <span>${complaint.id}</span>
                        <i class="fas fa-times" onclick="this.parentElement.parentElement.parentElement._source.closePopup()" style="cursor: pointer;"></i>
                    </div>
                    <div class="popup-body">
                        <div class="popup-row">
                            <span class="popup-label">Department:</span>
                            <span class="popup-value">${complaint.department}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Priority:</span>
                            <span class="popup-value">${complaint.priority}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Description:</span>
                            <span class="popup-value">${complaint.description}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Address:</span>
                            <span class="popup-value">${complaint.address}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Name:</span>
                            <span class="popup-value">${complaint.name}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Email:</span>
                            <span class="popup-value">${complaint.email}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Mobile:</span>
                            <span class="popup-value">${complaint.mobile}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Created:</span>
                            <span class="popup-value">${new Date(complaint.created_at).toLocaleString()}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Updated:</span>
                            <span class="popup-value">${new Date(complaint.updated_at).toLocaleString()}</span>
                        </div>
                        <div style="text-align: center;">
                            <span class="popup-status status-${complaint.status}">
                                ${complaint.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Apply filters
        async function applyFilters() {
            const department = document.getElementById('departmentFilter').value;
            const status = document.getElementById('statusFilter').value;
            const priority = document.getElementById('priorityFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            // Build query string
            const params = new URLSearchParams({
                action: 'filtered',
                department: department,
                status: status,
                priority: priority,
                date_from: dateFrom,
                date_to: dateTo
            });
            
            try {
                const response = await fetch(`admin_map_api.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    // Update current data
                    allComplaints = data.data.complaints;
                    
                    // Refresh display
                    showMarkers();
                    
                    // Update total count
                    document.getElementById('totalComplaints').textContent = data.data.total;
                } else {
                    console.error('Filter Error:', data.error);
                    showError('Failed to apply filters');
                }
            } catch (error) {
                console.error('Error applying filters:', error);
                showError('Network error. Please try again.');
            }
        }
        
        // Reset filters
        function resetFilters() {
            document.getElementById('departmentFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('priorityFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            // Reload all data
            loadComplaintData();
        }
        
        // Toggle markers view
        function toggleMarkers() {
            document.getElementById('markersBtn').classList.add('active');
            document.getElementById('clusterBtn').classList.remove('active');
            showMarkers();
        }
        
        // Toggle clusters view (placeholder for clustering)
        function toggleClusters() {
            document.getElementById('clusterBtn').classList.add('active');
            document.getElementById('markersBtn').classList.remove('active');
            // Clustering would require additional plugin like Leaflet.markercluster
            showMarkers();
        }
        
        // Reset map view
        function resetView() {
            if (currentMarkers.length > 0) {
                const group = new L.featureGroup(currentMarkers);
                map.fitBounds(group.getBounds().pad(0.1));
            } else {
                map.setView([20.5937, 78.9629], 5);
            }
        }
        
        // Refresh data
        function refreshData() {
            loadComplaintData();
        }
        
        // Start real-time updates
        function startRealTimeUpdates() {
            // Check for updates every 30 seconds
            refreshInterval = setInterval(async () => {
                try {
                    const response = await fetch(`admin_map_api.php?action=recent&last_update=${lastUpdateTime}`);
                    const data = await response.json();
                    
                    if (data.success && data.data.count > 0) {
                        console.log(`Found ${data.data.count} new complaints`);
                        loadComplaintData(); // Reload all data
                    }
                } catch (error) {
                    console.error('Error checking for updates:', error);
                }
            }, 30000); // 30 seconds
        }
        
        // Update header statistics
        function updateHeaderStats(stats) {
            document.getElementById('totalComplaints').textContent = stats.total_with_location || 0;
            
            const activeCount = stats.by_status
                .filter(item => item.status !== 'resolved')
                .reduce((sum, item) => sum + parseInt(item.count), 0);
            document.getElementById('activeComplaints').textContent = activeCount;
            
            document.getElementById('recentComplaints').textContent = stats.last_24_hours || 0;
        }
        
        // Update filter options
        function updateFilterOptions(stats) {
            // Update departments
            const deptSelect = document.getElementById('departmentFilter');
            stats.by_department.forEach(item => {
                const option = document.createElement('option');
                option.value = item.category;
                option.textContent = `${item.category} (${item.count})`;
                deptSelect.appendChild(option);
            });
            
            // Update statuses
            const statusSelect = document.getElementById('statusFilter');
            stats.by_status.forEach(item => {
                const option = document.createElement('option');
                option.value = item.status;
                option.textContent = `${item.status.replace('_', ' ')} (${item.count})`;
                statusSelect.appendChild(option);
            });
            
            // Update priorities
            const prioritySelect = document.getElementById('priorityFilter');
            // Priority options would be added based on your data
            ['urgent', 'high', 'normal', 'low'].forEach(priority => {
                const option = document.createElement('option');
                option.value = priority;
                option.textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
                prioritySelect.appendChild(option);
            });
        }
        
        // Update sidebar statistics
        function updateSidebarStats(stats) {
            const statsGrid = document.getElementById('statsGrid');
            statsGrid.innerHTML = '';
            
            // Status stats
            stats.by_status.forEach(item => {
                const statItem = document.createElement('div');
                statItem.className = 'stat-item';
                statItem.innerHTML = `
                    <h4>${item.status.replace('_', ' ')}</h4>
                    <div class="stat-value">${item.count}</div>
                `;
                statsGrid.appendChild(statItem);
            });
        }
        
        // Show error message
        function showError(message) {
            const mapDiv = document.getElementById('map');
            mapDiv.innerHTML = `
                <div style="display: flex; justify-content: center; align-items: center; height: 650px; flex-direction: column;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 20px;"></i>
                    <p style="color: #e74c3c; font-size: 1.2rem;">${message}</p>
                    <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
