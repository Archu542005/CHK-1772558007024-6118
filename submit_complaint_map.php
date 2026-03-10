<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint - Grievance Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .map-section {
            margin: 20px 0;
        }
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .location-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .location-coords {
            font-family: monospace;
            color: #3498db;
            font-size: 14px;
        }
        .btn-clear-location {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }
        .btn-clear-location:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                <li><a href="submit_complaint_map.php" class="nav-link active">Submit Complaint</a></li>
                <li><a href="track.php" class="nav-link">Track Complaint</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Submit Your Complaint</h2>
            
            <form id="complaintForm" method="POST" action="process_complaint.php" enctype="multipart/form-data">
                <!-- Personal Information -->
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile Number *</label>
                        <input type="tel" id="mobile" name="mobile" pattern="[0-9]{10}" required>
                    </div>
                </div>

                <!-- Location Selection -->
                <div class="form-section map-section">
                    <h3><i class="fas fa-map-marked-alt"></i> Select Location on Map</h3>
                    
                    <div class="location-info">
                        <p><strong>Instructions:</strong> Click on the map to select the exact location of your complaint</p>
                        <div class="location-coords">
                            Selected Location: <span id="selectedCoords">Not selected</span>
                        </div>
                        <button type="button" class="btn-clear-location" onclick="clearLocation()">
                            <i class="fas fa-times"></i> Clear Location
                        </button>
                    </div>
                    
                    <div id="locationMap" class="map-container">
                        <div style="display: flex; justify-content: center; align-items: center; height: 100%; background: #f8f9fa;">
                            <i class="fas fa-spinner fa-spin"></i> Loading map...
                        </div>
                    </div>
                    
                    <input type="hidden" id="latitude" name="latitude" required>
                    <input type="hidden" id="longitude" name="longitude" required>
                    <input type="hidden" id="selectedAddress" name="location">
                </div>

                <!-- Complaint Details -->
                <div class="form-section">
                    <h3>Complaint Details</h3>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="garbage">Garbage Collection</option>
                            <option value="water">Water Supply</option>
                            <option value="road">Road Damage</option>
                            <option value="electricity">Electricity</option>
                            <option value="street_light">Street Light</option>
                            <option value="drainage">Drainage</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority *</label>
                        <select id="priority" name="priority" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="5" required placeholder="Please describe your complaint in detail..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">Upload Image (Optional)</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Maximum file size: 5MB</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Complaint
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let locationMap;
        let locationMarker;
        let selectedLat, selectedLng;

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeLocationMap();
        });

        function initializeLocationMap() {
            // Create map centered on India
            locationMap = L.map('locationMap').setView([20.5937, 78.9629], 5);

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(locationMap);

            // Add click event to map
            locationMap.on('click', function(e) {
                selectLocation(e.latlng.lat, e.latlng.lng);
            });

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    locationMap.setView([lat, lng], 13);
                    selectLocation(lat, lng);
                }, function(error) {
                    console.log('Could not get location:', error);
                });
            }
        }

        function selectLocation(lat, lng) {
            selectedLat = lat;
            selectedLng = lng;

            // Remove existing marker
            if (locationMarker) {
                locationMap.removeLayer(locationMarker);
            }

            // Add new marker
            locationMarker = L.marker([lat, lng]).addTo(locationMap);

            // Update display
            document.getElementById('selectedCoords').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            // Get address using reverse geocoding (simplified)
            getAddressFromCoords(lat, lng);
        }

        function clearLocation() {
            if (locationMarker) {
                locationMap.removeLayer(locationMarker);
                locationMarker = null;
            }
            
            selectedLat = null;
            selectedLng = null;
            
            document.getElementById('selectedCoords').textContent = 'Not selected';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            document.getElementById('selectedAddress').value = '';
        }

        function getAddressFromCoords(lat, lng) {
            // Using Nominatim reverse geocoding
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('selectedAddress').value = data.display_name;
                    }
                })
                .catch(error => {
                    console.log('Error getting address:', error);
                    document.getElementById('selectedAddress').value = `Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                });
        }

        // Form validation
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
            if (!selectedLat || !selectedLng) {
                e.preventDefault();
                alert('Please select a location on the map');
                return false;
            }
        });
    </script>
</body>
</html>
