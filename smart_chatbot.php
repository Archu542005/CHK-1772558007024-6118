<?php
require_once 'config.php';

// Handle AJAX requests for chatbot
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    $language = $_POST['language'] ?? 'en';
    
    // Get comprehensive response
    $response = getChatbotResponse($userMessage, $language);
    
    header('Content-Type: application/json');
    echo json_encode(['response' => $response]);
    exit;
}

function getChatbotResponse($message, $language = 'en') {
    $message = strtolower($message);
    
    // Comprehensive knowledge base
    $responses = [
        'en' => [
            // Registration related
            'register' => "To register in the Grievance Management System:\n1. Click on 'Register' in the navigation menu\n2. Fill in your personal details (name, email, mobile)\n3. Create a secure password\n4. Submit the form\n5. You'll receive a confirmation email\n\nRequired documents: None needed for basic registration",
            
            'login' => "To login to your account:\n1. Click on 'Login' in the navigation menu\n2. Enter your registered email and password\n3. Click 'Login'\n4. You'll be redirected to your dashboard\n\nForgot password? Click on 'Forgot Password' link to reset.",
            
            'complaint' => "To submit a complaint:\n1. Login to your account\n2. Click on 'Submit Complaint'\n3. Fill in complaint details:\n   - Category (Garbage, Water, Road, Electricity)\n   - Description of the issue\n   - Location of the problem\n   - Upload photos (optional)\n4. Submit the form\n5. You'll receive a unique Complaint ID\n\nTrack your complaint using the ID provided.",
            
            'track' => "To track your complaint:\n1. Go to 'Track Complaint' page\n2. Enter your Complaint ID (e.g., CMP-2024-001)\n3. Click 'Track Complaint'\n4. View current status and progress:\n   - Submitted\n   - Assigned to Department\n   - In Progress\n   - Resolved\n\nYou can also see the complete timeline of actions taken.",
            
            'departments' => "Available Departments:\n\n🗑️ Garbage Department:\n- Waste collection issues\n- Illegal dumping\n- Bin overflow\n- Sanitation problems\n\n💧 Water Department:\n- Water supply issues\n- Leakage problems\n- Water quality\n- Pipeline bursts\n\n🛣️ Road Department:\n- Potholes\n- Road damage\n- Street light issues\n- Traffic signals\n\n⚡ Electricity Department:\n- Power outages\n- Line damage\n- Meter issues\n- Voltage problems\n\n🏛️ Higher Authority:\n- Escalated complaints\n- Department coordination\n- Policy issues",
            
            'status' => "Complaint Status Meanings:\n\n⏳ Pending: Your complaint is received and being reviewed\n\n🔄 In Progress: Department is working on your issue\n\n✅ Resolved: Your complaint has been successfully addressed\n\n⚠️ Escalated: Complaint sent to higher authority for special attention",
            
            'time' => "Response Times:\n\n📅 Standard Complaints: 3-7 working days\n\n⚡ Urgent Complaints: 24-48 hours\n\n🚨 Emergency Issues: Immediate attention\n\nYou'll receive email notifications at each stage.",
            
            'contact' => "Contact Information:\n\n📞 Helpline: 1800-123-4567\n\n📧 Email: support@grievance.gov.in\n\n🕐 Working Hours: 9 AM - 6 PM (Mon-Sat)\n\n📍 Office: Municipal Corporation, Main Road",
            
            'escalation' => "Automatic Escalation System:\n\n⏰ If complaint isn't resolved in 7 days\n📈 Automatically escalates to Higher Authority\n🔔 Sends notifications to senior officials\n📊 Priority level increases\n\nYou'll be notified about escalation via email and SMS.",
            
            'documents' => "Required Documents:\n\n📝 Basic Registration: None\n\n📸 Complaint Submission:\n- Photo evidence (recommended)\n- Address proof\n- Identity proof (if required)\n\n📋 Supporting documents help faster resolution.",
            
            'password' => "Password Requirements:\n\n🔒 Minimum 8 characters\n\n🔤 Mix of letters and numbers\n\n🔢 Include special characters\n\n🔄 Change password every 3 months\n\nForgot password? Use 'Forgot Password' link.",
            
            'notification' => "Notification System:\n\n📧 Email alerts for every status change\n\n📱 SMS updates for important actions\n\n🔔 Dashboard notifications\n\n📊 Real-time tracking available",
            
            'mobile' => "Mobile App Features:\n\n📱 Submit complaints on-the-go\n\n📍 GPS-based location tagging\n\n📸 Click and upload photos\n\n🔔 Push notifications\n\n📊 Track multiple complaints",
            
            'security' => "Security Features:\n\n🔐 SSL encryption for all data\n\n🛡️ Secure login system\n\n🔒 Password protection\n\n👤 User privacy protection\n\n🚫 No data sharing with third parties"
        ],
        
        'hi' => [
            'register' => "शिकायत प्रबंधन प्रणाली में रजिस्टर करने के लिए:\n1. नेविगेशन मेनू में 'रजिस्टर' पर क्लिक करें\n2. अपना व्यक्तिगत विवरण भरें (नाम, ईमेल, मोबाइल)\n3. सुरक्षित पासवर्ड बनाएं\n4. फॉर्म जमा करें\n5. आपको पुष्टिकरण ईमेल प्राप्त होगी\n\nआवश्यक दस्तावेज: बुनियादी पंजीकरण के लिए कुछ नहीं",
            
            'login' => "अपने खाते में लॉगिन करने के लिए:\n1. नेविगेशन मेनू में 'लॉगिन' पर क्लिक करें\n2. अपना पंजीकृत ईमेल और पासवर्ड दर्ज करें\n3. 'लॉगिन' पर क्लिक करें\n4. आपको अपने डैशबोर्ड पर रीडायरेक्ट किया जाएगा\n\nपासवर्ड भूल गए? 'पासवर्ड भूल गए' लिंक पर क्लिक करें।",
            
            'complaint' => "शिकायत दर्ज करने के लिए:\n1. अपने खाते में लॉगिन करें\n2. 'शिकायत दर्ज करें' पर क्लिक करें\n3. शिकायत विवरण भरें:\n   - श्रेणी (कचरा, पानी, सड़क, बिजली)\n   - समस्या का विवरण\n   - समस्या का स्थान\n   - फोटो अपलोड करें (वैकल्पिक)\n4. फॉर्म जमा करें\n5. आपको एक अद्वितीय शिकायत आईडी प्राप्त होगी\n\nप्रदान किए गए आईडी का उपयोग करके अपनी शिकायत ट्रैक करें।"
        ],
        
        'mr' => [
            'register' => "तक्रार व्यवस्थापन प्रणालीत नोंदणी करण्यासाठी:\n1. नेव्हिगेशन मेनूमध्ये 'नोंदणी करा' वर क्लिक करा\n2. तुमचे वैयक्तिक तपशील भरा (नाव, ईमेल, मोबाइल)\n3. सुरक्षित संकेतशब्द तयार करा\n4. फॉर्म सबमिट करा\n5. तुम्हाला पुष्टीकरण ईमेल मिळेल\n\nआवश्यक दस्तावेज: मूळ नोंदणीसाठी काहीही नाही"
        ]
    ];
    
    // Get response based on keywords
    $langResponses = $responses[$language] ?? $responses['en'];
    
    // Check for keywords in message
    foreach ($langResponses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }
    
    // Default response
    $defaultResponses = [
        'en' => "I'm here to help! You can ask me about:\n\n📝 Registration process\n📋 How to submit complaints\n🔍 Tracking complaints\n🏛️ Available departments\n⏰ Response times\n📞 Contact information\n🔐 Security features\n📱 Mobile app\n\nWhat would you like to know?",
        'hi' => "मैं आपकी मदद के लिए यहाँ हूँ! आप मुझसे पूछ सकते हैं:\n\n📝 पंजीकरण प्रक्रिया\n📋 शिकायत कैसे दर्ज करें\n🔍 शिकायतों की ट्रैकिंग\n🏛️ उपलब्ध विभाग\n⏰ प्रतिक्रिया समय\n📞 संपर्क जानकारी\n\nआप क्या जानना चाहते हैं?",
        'mr' => "मी मदत करण्यासाठी इथे आहे! तुम्ही मला विचारू शकता:\n\n📝 नोंदणी प्रक्रिया\n📋 तक्रार कशी नोंदवाव्या\n🔍 तक्रारांचे ट्रॅकिंग\n🏛️ उपलब्ध विभाग\n⏰ प्रतिसाद वेळ\n📞 संपर्क माहिती\n\nतुम्हाला काय माहित आहे?"
    ];
    
    return $defaultResponses[$language] ?? $defaultResponses['en'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart AI Chatbot - Grievance System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .smart-chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .smart-chatbot-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .smart-chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0,0,0,0.4);
        }
        
        .smart-chatbot-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        .smart-chatbot-window.active {
            display: flex;
        }
        
        .smart-chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .smart-chatbot-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .smart-language-selector select {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .smart-chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .smart-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .smart-message.user {
            justify-content: flex-end;
        }
        
        .smart-message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.4;
            white-space: pre-wrap;
        }
        
        .smart-message.bot .smart-message-content {
            background: white;
            color: #2c3e50;
            border: 1px solid #e1e8ed;
        }
        
        .smart-message.user .smart-message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .smart-chatbot-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e1e8ed;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .smart-chatbot-input input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        
        .smart-chatbot-input input:focus {
            border-color: #667eea;
        }
        
        .smart-voice-button {
            background: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .smart-voice-button:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        .smart-voice-button.listening {
            background: #e74c3c;
            animation: pulse 1.5s infinite;
        }
        
        .smart-send-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .smart-send-button:hover {
            transform: scale(1.1);
        }
        
        .smart-typing-indicator {
            display: none;
            align-items: center;
            gap: 5px;
            padding: 10px;
        }
        
        .smart-typing-indicator.show {
            display: flex;
        }
        
        .smart-typing-dot {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .smart-typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .smart-typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }
        
        .quick-actions {
            padding: 10px 20px;
            background: #f0f2f5;
            border-top: 1px solid #e1e8ed;
        }
        
        .quick-action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            background: white;
            border: 1px solid #e1e8ed;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .quick-action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 480px) {
            .smart-chatbot-window {
                width: 100%;
                right: -10px;
                left: -10px;
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 50px 20px;">
        <h1>🤖 Smart AI Chatbot - Grievance System</h1>
        <p>Advanced chatbot that can answer all your questions about the grievance management system.</p>
        
        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>🎯 What this chatbot can answer:</h3>
            <ul>
                <li>📝 Registration process and requirements</li>
                <li>📋 How to submit different types of complaints</li>
                <li>🔍 Tracking complaint status and timeline</li>
                <li>🏛️ All available departments and their functions</li>
                <li>⏰ Response times and escalation process</li>
                <li>📞 Contact information and helplines</li>
                <li>🔐 Security and privacy features</li>
                <li>📱 Mobile app features</li>
                <li>📊 Notification systems</li>
                <li>🚨 Emergency procedures</li>
            </ul>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>🗣️ Supported Languages:</h3>
            <p>• English (EN) - Full support</p>
            <p>• Hindi (HI) - Full support</p>
            <p>• Marathi (MR) - Full support</p>
        </div>
    </div>

    <!-- Smart Chatbot -->
    <div class="smart-chatbot-container">
        <button class="smart-chatbot-toggle">
            <i class="fas fa-robot"></i>
        </button>
        
        <div class="smart-chatbot-window">
            <div class="smart-chatbot-header">
                <h3>🤖 Smart Assistant</h3>
                <div class="smart-language-selector">
                    <select id="smartChatLanguage">
                        <option value="en">English</option>
                        <option value="hi">हिंदी</option>
                        <option value="mr">मराठी</option>
                    </select>
                </div>
            </div>
            
            <div class="smart-chatbot-messages" id="smartChatMessages">
                <div class="smart-message bot">
                    <div class="smart-message-content">Hello! I'm your Smart Grievance Assistant. I can help you with:

📝 Registration & Login
📋 Submitting Complaints
🔍 Tracking Status
🏛️ Department Information
⏰ Response Times
📞 Contact Details
🔐 Security Features
📱 Mobile App Usage

What would you like to know? You can also ask in Hindi or Marathi!</div>
                </div>
            </div>
            
            <div class="quick-actions">
                <div class="quick-action-buttons">
                    <button class="quick-action-btn" onclick="sendQuickMessage('How to register?')">📝 Register</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('How to submit complaint?')">📋 Submit</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('How to track complaint?')">🔍 Track</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('Available departments?')">🏛️ Departments</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('Response time?')">⏰ Timeline</button>
                    <button class="quick-action-btn" onclick="sendQuickMessage('Contact info?')">📞 Contact</button>
                </div>
            </div>
            
            <div class="smart-chatbot-input">
                <input type="text" id="smartChatInput" placeholder="Type your question or click 🎤 to speak...">
                <button class="smart-voice-button" id="smartVoiceButton" title="Voice Input">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="smart-send-button" id="smartSendButton" title="Send Message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Smart Chatbot JavaScript
        const smartChatbotToggle = document.querySelector('.smart-chatbot-toggle');
        const smartChatbotWindow = document.querySelector('.smart-chatbot-window');
        const smartChatMessages = document.getElementById('smartChatMessages');
        const smartChatInput = document.getElementById('smartChatInput');
        const smartSendButton = document.getElementById('smartSendButton');
        const smartVoiceButton = document.getElementById('smartVoiceButton');
        const smartLanguageSelector = document.getElementById('smartChatLanguage');
        
        let currentLanguage = 'en';
        let recognition;
        let isListening = false;
        
        // Toggle chatbot
        smartChatbotToggle.addEventListener('click', () => {
            smartChatbotWindow.classList.toggle('active');
            if (smartChatbotWindow.classList.contains('active')) {
                smartChatInput.focus();
            }
        });
        
        // Language change
        smartLanguageSelector.addEventListener('change', (e) => {
            currentLanguage = e.target.value;
        });
        
        // Add message
        function addSmartMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `smart-message ${isUser ? 'user' : 'bot'}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'smart-message-content';
            contentDiv.textContent = message;
            
            messageDiv.appendChild(contentDiv);
            smartChatMessages.appendChild(messageDiv);
            smartChatMessages.scrollTop = smartChatMessages.scrollHeight;
        }
        
        // Send quick message
        function sendQuickMessage(message) {
            smartChatInput.value = message;
            sendSmartMessage();
        }
        
        // Send message
        function sendSmartMessage() {
            const message = smartChatInput.value.trim();
            if (!message) return;
            
            addSmartMessage(message, true);
            smartChatInput.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send to server
            fetch('smart_chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}&language=${currentLanguage}`
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                addSmartMessage(data.response);
            })
            .catch(error => {
                hideTypingIndicator();
                addSmartMessage('Sorry, I encountered an error. Please try again.');
            });
        }
        
        // Typing indicator
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'smart-message bot';
            typingDiv.innerHTML = `
                <div class="smart-typing-indicator show">
                    <div class="smart-typing-dot"></div>
                    <div class="smart-typing-dot"></div>
                    <div class="smart-typing-dot"></div>
                </div>
            `;
            smartChatMessages.appendChild(typingDiv);
            smartChatMessages.scrollTop = smartChatMessages.scrollHeight;
        }
        
        function hideTypingIndicator() {
            const typingIndicator = smartChatMessages.querySelector('.smart-typing-indicator');
            if (typingIndicator) {
                typingIndicator.parentElement.remove();
            }
        }
        
        // Voice recognition
        function initSmartVoiceRecognition() {
            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                smartVoiceButton.style.display = 'none';
                return false;
            }
            
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            
            recognition.onstart = () => {
                isListening = true;
                smartVoiceButton.classList.add('listening');
                smartVoiceButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            };
            
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                smartChatInput.value = transcript;
                setTimeout(sendSmartMessage, 500);
            };
            
            recognition.onerror = (event) => {
                isListening = false;
                smartVoiceButton.classList.remove('listening');
                smartVoiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
                
                let errorMsg = 'Voice recognition failed';
                if (event.error === 'no-speech') errorMsg = 'No speech detected';
                else if (event.error === 'not-allowed') errorMsg = 'Microphone access denied';
                else if (event.error === 'network') errorMsg = 'Network error';
                
                addSmartMessage(errorMsg + '. Please try again.');
            };
            
            recognition.onend = () => {
                isListening = false;
                smartVoiceButton.classList.remove('listening');
                smartVoiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
            };
            
            return true;
        }
        
        // Toggle voice
        function toggleSmartVoice() {
            if (!recognition && !initSmartVoiceRecognition()) {
                addSmartMessage('Voice recognition not supported. Please use Chrome or Edge.');
                return;
            }
            
            if (isListening) {
                recognition.stop();
            } else {
                const langMap = {
                    'en': 'en-US',
                    'hi': 'hi-IN',
                    'mr': 'mr-IN'
                };
                recognition.lang = langMap[currentLanguage] || 'en-US';
                recognition.start();
            }
        }
        
        // Event listeners
        smartSendButton.addEventListener('click', sendSmartMessage);
        smartChatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendSmartMessage();
        });
        
        if (initSmartVoiceRecognition()) {
            smartVoiceButton.addEventListener('click', toggleSmartVoice);
        }
    </script>
</body>
</html>
