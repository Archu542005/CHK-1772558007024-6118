// Voice Chatbot - Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    console.log('Voice chatbot initializing...');
    
    // Get elements
    const chatbotToggle = document.querySelector('.chatbot-toggle');
    const chatbotWindow = document.querySelector('.chatbot-window');
    const chatInput = document.getElementById('chatInput');
    const sendButton = document.getElementById('sendMessage');
    const chatMessages = document.querySelector('.chatbot-messages');
    const languageSelector = document.getElementById('chatLanguage');
    const voiceButton = document.getElementById('voiceButton');
    
    let recognition;
    let isListening = false;
    let currentLanguage = 'en';
    
    // Check if all required elements exist
    if (!chatbotToggle || !chatbotWindow || !chatInput || !sendButton || !chatMessages) {
        console.error('Chatbot elements not found');
        return;
    }
    
    // Initialize voice recognition
    function initVoiceRecognition() {
        console.log('Initializing voice recognition...');
        
        if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            console.log('Speech recognition not supported');
            if (voiceButton) {
                voiceButton.style.display = 'none';
            }
            return false;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-US';
        
        recognition.onstart = function() {
            console.log('Voice recognition started');
            isListening = true;
            if (voiceButton) {
                voiceButton.classList.add('listening');
                voiceButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            }
        };
        
        recognition.onresult = function(event) {
            console.log('Voice result:', event.results[0][0].transcript);
            const transcript = event.results[0][0].transcript;
            chatInput.value = transcript;
            
            // Auto-send after voice input
            setTimeout(() => {
                sendMessage();
            }, 1000);
        };
        
        recognition.onerror = function(event) {
            console.error('Voice recognition error:', event.error);
            isListening = false;
            if (voiceButton) {
                voiceButton.classList.remove('listening');
                voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
            }
            
            let errorMessage = 'Voice recognition failed';
            if (event.error === 'no-speech') {
                errorMessage = 'No speech detected';
            } else if (event.error === 'not-allowed') {
                errorMessage = 'Microphone access denied';
            } else if (event.error === 'network') {
                errorMessage = 'Network error';
            }
            
            addMessage(errorMessage + '. Please try again.', false);
        };
        
        recognition.onend = function() {
            console.log('Voice recognition ended');
            isListening = false;
            if (voiceButton) {
                voiceButton.classList.remove('listening');
                voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
            }
        };
        
        console.log('Voice recognition initialized successfully');
        return true;
    }
    
    // Toggle voice recognition
    function toggleVoiceRecognition() {
        console.log('Toggling voice recognition, isListening:', isListening);
        
        if (!recognition) {
            if (!initVoiceRecognition()) {
                addMessage('Voice recognition not supported. Please use Chrome or Edge.', false);
                return;
            }
        }
        
        if (isListening) {
            recognition.stop();
        } else {
            // Update language
            const langMap = {
                'en': 'en-US',
                'hi': 'hi-IN',
                'mr': 'mr-IN'
            };
            recognition.lang = langMap[currentLanguage] || 'en-US';
            
            recognition.start();
        }
    }
    
    // Add message to chat
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
    
    // Get bot response
    function getBotResponse(userMessage) {
        const lowerMessage = userMessage.toLowerCase();
        
        const responses = {
            en: {
                greeting: "Hello! How can I help you today?",
                register: "To register, click on the Register button in the navigation menu.",
                submit: "To submit a complaint, first login to your account, then click on 'Submit Complaint'.",
                track: "To track your complaint, enter your complaint ID on the Track Complaint page.",
                default: "I'm here to help! You can ask me about registration, submitting complaints, or tracking status."
            },
            hi: {
                greeting: "नमस्ते! मैं आपकी कैसे मदद कर सकता हूँ?",
                register: "रजिस्टर करने के लिए, नेविगेशन मेनू में रजिस्टर बटन पर क्लिक करें।",
                submit: "शिकायत दर्ज करने के लिए, पहले अपने खाते में लॉगिन करें।",
                track: "अपनी शिकायत ट्रैक करने के लिए, ट्रैक शिकायत पृष्ठ पर अपनी शिकायत आईडी दर्ज करें।",
                default: "मैं मदद करने के लिए यहाँ हूँ! आप मुझसे रजिस्ट्रेशन, शिकायत दर्ज करने, या स्थिति ट्रैकिंग के बारे में पूछ सकते हैं।"
            },
            mr: {
                greeting: "नमस्कार! मी तुमची कशी मदत करू शकतो?",
                register: "नोंदणी करण्यासाठी, नेव्हिगेशन मेनूमध्ये रजिस्टर बटणवर क्लिक करा.",
                submit: "तक्रार नोंदवण्यासाठी, प्रथम तुमच्या खात्यात लॉगिन करा.",
                track: "तुमची तक्रार ट्रॅक करण्यासाठी, ट्रॅक तक्रार पृष्ठावर तुमची तक्रार आयडी प्रविष्ट करा.",
                default: "मी मदत करण्यासाठी इथे आहे! तुम्ही मला नोंदणी, तक्रार नोंदवणे, किंवा स्थिती ट्रॅकिंगबद्दल विचारू शकता."
            }
        };
        
        const langResponses = responses[currentLanguage];
        
        if (lowerMessage.includes('register') || lowerMessage.includes('नोंदणी') || lowerMessage.includes('रजिस्टर')) {
            return langResponses.register;
        } else if (lowerMessage.includes('submit') || lowerMessage.includes('complaint') || lowerMessage.includes('तक्रार') || lowerMessage.includes('शिकायत')) {
            return langResponses.submit;
        } else if (lowerMessage.includes('track') || lowerMessage.includes('ट्रॅक') || lowerMessage.includes('status')) {
            return langResponses.track;
        } else {
            return langResponses.default;
        }
    }
    
    // Send message
    function sendMessage() {
        const message = chatInput.value.trim();
        if (message) {
            addMessage(message, true);
            chatInput.value = '';
            
            // Simulate bot response
            setTimeout(() => {
                const response = getBotResponse(message);
                addMessage(response);
            }, 1000);
        }
    }
    
    // Initialize voice recognition
    const voiceSupported = initVoiceRecognition();
    
    // Add voice button event listener
    if (voiceButton && voiceSupported) {
        voiceButton.addEventListener('click', toggleVoiceRecognition);
        console.log('Voice button event listener added');
    } else if (voiceButton) {
        voiceButton.style.display = 'none';
        console.log('Voice button hidden (not supported)');
    }
    
    // Chatbot toggle
    chatbotToggle.addEventListener('click', () => {
        chatbotWindow.classList.toggle('active');
        if (chatbotWindow.classList.contains('active')) {
            chatInput.focus();
        }
    });
    
    // Language change
    if (languageSelector) {
        languageSelector.addEventListener('change', (e) => {
            currentLanguage = e.target.value;
            console.log('Language changed to:', currentLanguage);
        });
    }
    
    // Send button
    sendButton.addEventListener('click', sendMessage);
    
    // Enter key
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    console.log('Voice chatbot initialized successfully');
});
