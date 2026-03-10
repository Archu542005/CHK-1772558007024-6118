<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Chatbot Test</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container" style="padding: 50px 20px;">
        <h1>Voice Chatbot Test</h1>
        <p>Test the voice command functionality of the AI chatbot.</p>
        
        <!-- Voice Status Check -->
        <div id="voiceStatus" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>Voice Recognition Status:</h3>
            <p id="voiceSupport">Checking...</p>
        </div>
        
        <!-- Test Instructions -->
        <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>How to Test:</h3>
            <ol>
                <li>Click the chatbot icon (bottom right)</li>
                <li>Click the microphone button 🎤</li>
                <li>Allow microphone access when prompted</li>
                <li>Speak clearly into your microphone</li>
                <li>The message will be automatically transcribed and sent</li>
            </ol>
        </div>
        
        <!-- Browser Compatibility -->
        <div style="background: #fff3e0; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>Browser Compatibility:</h3>
            <ul>
                <li>✅ Chrome (Recommended)</li>
                <li>✅ Edge (Recommended)</li>
                <li>⚠️ Firefox (Limited support)</li>
                <li>⚠️ Safari (Limited support)</li>
            </ul>
            <p><strong>Note:</strong> Voice recognition works best with Chrome or Edge browser.</p>
        </div>
    </div>

    <!-- Chatbot (copied from index.html) -->
    <div class="chatbot-container">
        <div class="chatbot-toggle">
            <i class="fas fa-comments"></i>
        </div>
        <div class="chatbot-window">
            <div class="chatbot-header">
                <h3>AI Assistant</h3>
                <div class="language-selector">
                    <select id="chatLanguage">
                        <option value="en">English</option>
                        <option value="hi">हिंदी</option>
                        <option value="mr">मराठी</option>
                    </select>
                </div>
            </div>
            <div class="chatbot-messages">
                <div class="message bot">
                    <p>Hello! How can I help you today? Try clicking the microphone button to speak!</p>
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="chatInput" placeholder="Type your message or click 🎤 to speak...">
                <button id="voiceButton" class="voice-button" title="Click to speak (Voice Input)">
                    <i class="fas fa-microphone"></i>
                </button>
                <button id="sendMessage"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        // Check voice recognition support
        function checkVoiceSupport() {
            const supportDiv = document.getElementById('voiceSupport');
            
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                supportDiv.innerHTML = '✅ Voice recognition is supported in your browser!';
                supportDiv.style.color = '#27ae60';
            } else {
                supportDiv.innerHTML = '❌ Voice recognition is not supported. Please use Chrome or Edge browser.';
                supportDiv.style.color = '#e74c3c';
            }
        }
        
        // Check on page load
        document.addEventListener('DOMContentLoaded', checkVoiceSupport);
    </script>
    
    <script src="js/script.js"></script>
    
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        
        h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        ol, ul {
            margin: 0;
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 8px;
        }
    </style>
</body>
</html>
