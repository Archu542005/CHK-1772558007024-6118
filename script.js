// Image Slider functionality
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.prev-slide');
    const nextBtn = document.querySelector('.next-slide');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[index].classList.add('active');
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }

    function startSlideShow() {
        slideInterval = setInterval(nextSlide, 3000);
    }

    function stopSlideShow() {
        clearInterval(slideInterval);
    }

    if (slides.length > 0) {
        // Start automatic slideshow
        startSlideShow();

        // Button controls
        if (prevBtn) prevBtn.addEventListener('click', () => {
            stopSlideShow();
            prevSlide();
            startSlideShow();
        });

        if (nextBtn) nextBtn.addEventListener('click', () => {
            stopSlideShow();
            nextSlide();
            startSlideShow();
        });

        // Pause on hover
        const slider = document.querySelector('.slider');
        if (slider) {
            slider.addEventListener('mouseenter', stopSlideShow);
            slider.addEventListener('mouseleave', startSlideShow);
        }
    }

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Animated counter for statistics
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;

    const countUp = () => {
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(countUp, 1);
            } else {
                counter.innerText = target;
            }
        });
    };

    // Trigger counter animation when stats section is in view
    const statsSection = document.querySelector('.stats');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    countUp();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        observer.observe(statsSection);
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Remove error class on input
                    field.addEventListener('input', function() {
                        this.classList.remove('error');
                    });
                }
            });

            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailRegex.test(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                }
            });

            // Phone number validation
            const phoneFields = form.querySelectorAll('input[type="tel"]');
            phoneFields.forEach(field => {
                const phoneRegex = /^[0-9]{10}$/;
                if (field.value && !phoneRegex.test(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    });

    // Chatbot functionality
    const chatbotToggle = document.querySelector('.chatbot-toggle');
    const chatbotWindow = document.querySelector('.chatbot-window');
    const chatInput = document.getElementById('chatInput');
    const sendButton = document.getElementById('sendMessage');
    const chatMessages = document.querySelector('.chatbot-messages');
    const languageSelector = document.getElementById('chatLanguage');

    // Voice recognition variables
    let recognition;
    let isListening = false;
    let voiceButton = document.getElementById('voiceButton');
    let voiceStatus;

    // Initialize voice recognition
    function initializeVoiceRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US'; // Default language
            
            recognition.onstart = function() {
                isListening = true;
                if (voiceButton) {
                    voiceButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                    voiceButton.classList.add('listening');
                }
                
                // Show listening status
                if (voiceStatus) {
                    voiceStatus.textContent = 'Listening...';
                    voiceStatus.classList.add('show');
                }
                
                console.log('Voice recognition started');
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                chatInput.value = transcript;
                console.log('Voice result:', transcript);
                
                // Hide listening status
                if (voiceStatus) {
                    voiceStatus.textContent = 'Processing...';
                }
                
                // Automatically send the message after voice input
                setTimeout(() => {
                    sendButton.click();
                    
                    // Hide status after sending
                    setTimeout(() => {
                        if (voiceStatus) {
                            voiceStatus.classList.remove('show');
                        }
                    }, 1000);
                }, 500);
            };
            
            recognition.onerror = function(event) {
                console.error('Speech recognition error:', event.error);
                isListening = false;
                if (voiceButton) {
                    voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
                    voiceButton.classList.remove('listening');
                }
                
                // Hide status and show error
                if (voiceStatus) {
                    voiceStatus.classList.remove('show');
                }
                
                // Show error message to user
                let errorMessage = 'Voice recognition failed. Please try again.';
                if (event.error === 'no-speech') {
                    errorMessage = 'No speech detected. Please try again.';
                } else if (event.error === 'not-allowed') {
                    errorMessage = 'Microphone access denied. Please allow microphone access.';
                } else if (event.error === 'network') {
                    errorMessage = 'Network error. Please check your connection.';
                }
                
                addMessage(errorMessage, false);
            };
            
            recognition.onend = function() {
                isListening = false;
                if (voiceButton) {
                    voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
                    voiceButton.classList.remove('listening');
                }
                
                // Hide status if still showing
                if (voiceStatus && voiceStatus.textContent === 'Listening...') {
                    voiceStatus.classList.remove('show');
                }
                
                console.log('Voice recognition ended');
            };
            
            return true;
        } else {
            console.log('Speech recognition not supported');
            return false;
        }
    }

    // Create voice status indicator
    function createVoiceStatus() {
        voiceStatus = document.createElement('div');
        voiceStatus.className = 'voice-status';
        voiceStatus.id = 'voiceStatus';
        
        // Insert voice status
        const chatbotInput = document.querySelector('.chatbot-input');
        if (chatbotInput) {
            chatbotInput.appendChild(voiceStatus);
        }
    }

    // Toggle voice recognition
    function toggleVoiceRecognition() {
        if (!recognition) {
            addMessage('Voice recognition is not supported in your browser. Please try Chrome or Edge.', false);
            return;
        }
        
        if (isListening) {
            recognition.stop();
        } else {
            // Update language based on selected chat language
            const langMap = {
                'en': 'en-US',
                'hi': 'hi-IN',
                'mr': 'mr-IN'
            };
            
            recognition.lang = langMap[currentLanguage] || 'en-US';
            
            // Show status
            if (voiceStatus) {
                voiceStatus.textContent = 'Listening...';
                voiceStatus.classList.add('show');
            }
            
            recognition.start();
        }
    }

    // Update voice language when chat language changes
    function updateVoiceLanguage() {
        if (recognition) {
            const langMap = {
                'en': 'en-US',
                'hi': 'hi-IN',
                'mr': 'mr-IN'
            };
            
            recognition.lang = langMap[currentLanguage] || 'en-US';
        }
    }

    // Chatbot responses in different languages
    const responses = {
        en: {
            greeting: "Hello! How can I help you today?",
            options: "You can ask me about:\n• How to register\n• How to submit a complaint\n• How to track complaints\n• Available departments",
            register: "To register, click on the Register button in the navigation menu. Fill in your details and submit the form.",
            submit: "To submit a complaint, first login to your account, then click on 'Submit Complaint'. Choose the appropriate department and provide details.",
            track: "To track your complaint, enter your complaint ID on the Track Complaint page. You can find this ID in your confirmation email.",
            departments: "Available departments:\n• Garbage/Kachra\n• Water Leakage\n• Road Damage\n• Electricity\n• Other",
            default: "I'm here to help! You can ask me about registration, submitting complaints, tracking status, or available departments."
        },
        hi: {
            greeting: "नमस्ते! मैं आज आपकी कैसे मदद कर सकता हूँ?",
            options: "आप मुझसे पूछ सकते हैं:\n• कैसे रजिस्टर करें\n• शिकायत कैसे दर्ज करें\n• शिकायत कैसे ट्रैक करें\n• उपलब्ध विभाग",
            register: "रजिस्टर करने के लिए, नेविगेशन मेनू में रजिस्टर बटन पर क्लिक करें। अपना विवरण भरें और फॉर्म जमा करें।",
            submit: "शिकायत दर्ज करने के लिए, पहले अपने खाते में लॉगिन करें, फिर 'शिकायत दर्ज करें' पर क्लिक करें। उपयुक्त विभाग चुनें और विवरण प्रदान करें।",
            track: "अपनी शिकायत ट्रैक करने के लिए, ट्रैक शिकायत पृष्ठ पर अपनी शिकायत आईडी दर्ज करें। आप इस आईडी को अपने पुष्टिकरण ईमेल में पा सकते हैं।",
            departments: "उपलब्ध विभाग:\n• कचरा/कचरा\n• पानी रिसाव\n• सड़क क्षति\n• बिजली\n• अन्य",
            default: "मैं मदद करने के लिए यहाँ हूँ! आप मुझसे रजिस्ट्रेशन, शिकायत दर्ज करने, स्थिति ट्रैकिंग, या उपलब्ध विभागों के बारे में पूछ सकते हैं।"
        },
        mr: {
            greeting: "नमस्कार! मी आज तुमची कशी मदत करू शकतो?",
            options: "तुम्ही मला विचारू शकता:\n• कसे नोंदणी करावे\n• तक्रार कशी नोंदवावी\n• तक्रार कशी ट्रॅक करावी\n• उपलब्ध विभाग",
            register: "नोंदणी करण्यासाठी, नेव्हिगेशन मेनूमध्ये रजिस्टर बटणवर क्लिक करा. तुमचे तपशील भरा आणि फॉर्म सबमिट करा.",
            submit: "तक्रार नोंदवण्यासाठी, प्रथम तुमच्या खात्यात लॉगिन करा, नंतर 'तक्रार नोंदवा' वर क्लिक करा. योग्य विभाग निवडा आणि तपशील प्रदान करा.",
            track: "तुमची तक्रार ट्रॅक करण्यासाठी, ट्रॅक तक्रार पृष्ठावर तुमची तक्रार आयडी प्रविष्ट करा. तुम्ही ही आयडी तुमच्या पुष्टीकरण ईमेलमध्ये शोधू शकता.",
            departments: "उपलब्ध विभाग:\n• कचरा/कचरा\n• पाणी गळती\n• रस्ता नुकसान\n• वीज\n• इतर",
            default: "मी मदत करण्यासाठी इथे आहे! तुम्ही मला नोंदणी, तक्रार नोंदवणे, स्थिती ट्रॅकिंग, किंवा उपलब्ध विभागांबद्दल विचारू शकता."
        }
    };

    let currentLanguage = 'en';

    if (chatbotToggle && chatbotWindow) {
        // Initialize voice recognition
        const voiceSupported = initializeVoiceRecognition();
        
        // Create voice status indicator if voice is supported
        if (voiceSupported) {
            createVoiceStatus();
            
            // Add event listener to voice button
            if (voiceButton) {
                voiceButton.addEventListener('click', toggleVoiceRecognition);
            }
        } else {
            // Hide voice button if not supported
            if (voiceButton) {
                voiceButton.style.display = 'none';
            }
        }
        
        chatbotToggle.addEventListener('click', () => {
            chatbotWindow.classList.toggle('active');
            if (chatbotWindow.classList.contains('active')) {
                chatInput.focus();
            }
        });

        if (languageSelector) {
            languageSelector.addEventListener('change', (e) => {
                currentLanguage = e.target.value;
                updateVoiceLanguage(); // Update voice language when chat language changes
            });
        }

        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
            
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = message;
            
            messageDiv.appendChild(messageContent);
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function getBotResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            const langResponses = responses[currentLanguage];

            if (lowerMessage.includes('register') || lowerMessage.includes('नोंदणी') || lowerMessage.includes('रजिस्टर')) {
                return langResponses.register;
            } else if (lowerMessage.includes('submit') || lowerMessage.includes('complaint') || lowerMessage.includes('तक्रार') || lowerMessage.includes('शिकायत')) {
                return langResponses.submit;
            } else if (lowerMessage.includes('track') || lowerMessage.includes('ट्रॅक') || lowerMessage.includes('status')) {
                return langResponses.track;
            } else if (lowerMessage.includes('department') || lowerMessage.includes('विभाग')) {
                return langResponses.departments;
            } else if (lowerMessage.includes('help') || lowerMessage.includes('मदत') || lowerMessage.includes('मदत')) {
                return langResponses.options;
            } else {
                return langResponses.default;
            }
        }

        function sendMessage() {
            const message = chatInput.value.trim();
            if (message) {
                addMessage(message, true);
                chatInput.value = '';

                // Simulate bot typing delay
                setTimeout(() => {
                    const response = getBotResponse(message);
                    addMessage(response);
                }, 1000);
            }
        }

        if (sendButton) {
            sendButton.addEventListener('click', sendMessage);
        }

        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }
    }

    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        }
    });

    // File upload preview
    const fileInput = document.getElementById('image');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }

                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }

                // Create preview (optional)
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You could add a preview element here if needed
                    console.log('Image loaded successfully');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Loading states for forms
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        const form = button.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Re-enable after 5 seconds (in case of server issues)
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = button.getAttribute('data-original-text') || button.textContent;
                }, 5000);
            });
        }
    });

    // Tooltip functionality
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // Print functionality
    window.printPage = function() {
        window.print();
    };

    // Copy to clipboard functionality
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    };

    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Add CSS animations dynamically
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .error {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
        }
        
        .tooltip {
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 10000;
            white-space: nowrap;
        }
    `;
    document.head.appendChild(style);
});

// Complaint Map Functionality
let complaintMap = null;
let mapMarkers = [];
let mapVisible = false;

// Open/Close Complaint Map
function openComplaintMap() {
    const mapSection = document.getElementById('complaintMapSection');
    
    if (mapSection) {
        if (mapVisible) {
            mapSection.style.display = 'none';
            mapVisible = false;
        } else {
            mapSection.style.display = 'block';
            mapVisible = true;
            
            // Initialize map if not already done
            if (!complaintMap) {
                initializeComplaintMap();
            }
            
            // Scroll to map section
            mapSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

// Toggle Map View
function toggleMapView() {
    const mapSection = document.getElementById('complaintMapSection');
    if (mapSection) {
        if (mapSection.style.display === 'none') {
            mapSection.style.display = 'block';
            if (!complaintMap) {
                initializeComplaintMap();
            }
        } else {
            mapSection.style.display = 'none';
        }
    }
}

// Reset Map View
function resetMapView() {
    if (complaintMap && mapMarkers.length > 0) {
        const group = L.featureGroup(mapMarkers);
        complaintMap.fitBounds(group.getBounds().pad(0.1));
    }
}

// Refresh Map Data
function refreshMapData() {
    if (complaintMap) {
        loadComplaintData();
    }
}

// Initialize Complaint Map
function initializeComplaintMap() {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        // Load Leaflet dynamically
        const leafletCss = document.createElement('link');
        leafletCss.rel = 'stylesheet';
        leafletCss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCss);
        
        const leafletJs = document.createElement('script');
        leafletJs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJs.onload = function() {
            createMap();
        };
        document.head.appendChild(leafletJs);
    } else {
        createMap();
    }
}

// Create Map
function createMap() {
    const mapContainer = document.getElementById('complaintMap');
    
    if (!mapContainer) return;
    
    // Clear loading
    mapContainer.innerHTML = '';
    
    // Create map
    complaintMap = L.map('complaintMap').setView([20.5937, 78.9629], 5);
    
    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(complaintMap);
    
    // Load complaint data
    loadComplaintData();
}

// Load Complaint Data
async function loadComplaintData() {
    try {
        const response = await fetch('admin_map_api.php?action=all');
        const data = await response.json();
        
        if (data.success) {
            displayComplaintMarkers(data.data.complaints);
        } else {
            console.error('API Error:', data.error);
            showMapError('Failed to load complaint data');
        }
    } catch (error) {
        console.error('Error loading data:', error);
        showMapError('Network error. Please try again.');
    }
}

// Display Complaint Markers
function displayComplaintMarkers(complaints) {
    // Clear existing markers
    mapMarkers.forEach(marker => complaintMap.removeLayer(marker));
    mapMarkers = [];
    
    complaints.forEach(complaint => {
        // Validate coordinates
        if (!complaint.lat || !complaint.lng) return;
        
        const marker = createComplaintMarker(complaint);
        const popup = createComplaintPopup(complaint);
        
        marker.bindPopup(popup, {
            maxWidth: 350,
            className: 'custom-popup'
        });
        
        complaintMap.addLayer(marker);
        mapMarkers.push(marker);
    });
    
    // Fit map to show all markers
    if (mapMarkers.length > 0) {
        const group = L.featureGroup(mapMarkers);
        complaintMap.fitBounds(group.getBounds().pad(0.1));
    }
}

// Create Complaint Marker
function createComplaintMarker(complaint) {
    const iconHtml = `
        <div style="
            background: ${getMarkerColor(complaint.status)};
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
            <i class="${getMarkerIcon(complaint.status)}"></i>
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

// Create Complaint Popup
function createComplaintPopup(complaint) {
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
                <div style="text-align: center;">
                    <span class="popup-status status-${complaint.status}">
                        ${complaint.status.replace('_', ' ').toUpperCase()}
                    </span>
                </div>
            </div>
        </div>
    `;
}

// Get Marker Color based on Status
function getMarkerColor(status) {
    const colors = {
        'pending': '#f39c12',
        'in_progress': '#3498db',
        'resolved': '#27ae60',
        'escalated': '#e74c3c'
    };
    return colors[status] || '#95a5a6';
}

// Get Marker Icon based on Status
function getMarkerIcon(status) {
    const icons = {
        'pending': 'fas fa-clock',
        'in_progress': 'fas fa-spinner',
        'resolved': 'fas fa-check-circle',
        'escalated': 'fas fa-exclamation-triangle'
    };
    return icons[status] || 'fas fa-map-marker';
}

// Show Map Error
function showMapError(message) {
    const mapContainer = document.getElementById('complaintMap');
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div style="display: flex; justify-content: center; align-items: center; height: 500px; flex-direction: column;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 20px;"></i>
                <p style="color: #e74c3c; font-size: 1.2rem;">${message}</p>
                <button onclick="loadComplaintData()" style="margin-top: 20px; padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-redo"></i> Retry
                </button>
            </div>
        `;
    }
}
