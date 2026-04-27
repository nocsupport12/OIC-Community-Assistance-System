const chatBox = document.getElementById("chatBox");
const chatBody = document.getElementById("chatBody");
const input = document.getElementById("userInput");
const sendBtn = document.getElementById("sendBtn");
const chatMini = document.getElementById("chatMini");
const chatBubble = document.getElementById("chatBubble");
const mainOptions = document.getElementById("mainOptions");
const infoForm = document.getElementById("infoForm");
const languageOptions = document.getElementById("languageOptions");

// Gemini API Configuration - Using CORS proxy
const GEMINI_API_KEY = 'AIzaSyDdQkrd4okOXiQCZtRI3Nj2GXmZJ143Kps';
// CORS proxy to bypass browser restrictions
const PROXY_URL = 'https://api.allorigins.win/raw?url='; // Alternative: 'https://corsproxy.io/?'
const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=${GEMINI_API_KEY}`;
const FULL_API_URL = PROXY_URL + encodeURIComponent(GEMINI_API_URL);

let currentLang = null;
let conversationState = "language_selection";
let conversationHistory = [];
let useLocalAI = false; // Fallback flag
let chatSessionId = generateSessionId();
let staffJoined = false;
let currentStaffName = null;
let staffMessageInterval = null;
let displayedMessageIds = new Set();
let lastMessageCheck = new Date().toISOString();

// Generate unique session ID
function generateSessionId() {
    return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Simple translations for initial messages
const translations = {
    en: {
        welcome: "👋 Hello! Welcome to Power2Connect Customer Support. How can I help you today?",
        options: "Please choose from these common issues or type your question:",
        inputPlaceholder: "Type your question here...",
        contactPrompt: "To assist you better, how would you like to be contacted?",
        contactChoices: "Please choose:",
        emailOption: "📧 Provide Email Address",
        phoneOption: "📱 Provide Phone Number",
        skipOption: "➡️ Skip for now",
        askEmail: "Please enter your email address:",
        askPhone: "Please enter your phone number:",
        submitting: "Thank you! Your information has been submitted.",
        agentMessage: "Our support team will contact you shortly.",
        thankYou: "Is there anything else I can help you with today?",
        notUnderstood: "I'll connect you with our support team for further assistance.",
        languageSelected: "English selected. How can I help you today?",
        selectLanguage: "Please select your preferred language:",
        welcomeMessage: "Welcome to Power2Connect! Please select your preferred language:",
        aiThinking: "Power2Connect AI is thinking...",
        staffJoined: "👨‍💼 Support staff has joined the chat. Please continue your conversation.",
        staffTyping: "Staff is typing..."
    },
    tl: {
        welcome: "👋 Kamusta! Maligayang pagdating sa Power2Connect Customer Support. Paano kita matutulungan ngayon?",
        options: "Mangyaring pumili mula sa mga karaniwang isyu o i-type ang iyong tanong:",
        inputPlaceholder: "I-type ang iyong tanong dito...",
        contactPrompt: "Para mas mabuti kang matulungan, paano mo gustong ma-contact?",
        contactChoices: "Mangyaring pumili:",
        emailOption: "📧 Magbigay ng Email Address",
        phoneOption: "📱 Magbigay ng Phone Number",
        skipOption: "➡️ Laktawan muna",
        askEmail: "Pakilagay ang iyong email address:",
        askPhone: "Pakilagay ang iyong phone number:",
        submitting: "Salamat! Naipadala na ang iyong impormasyon.",
        agentMessage: "Ang aming support team ay makikipag-ugnayan sa iyo sa lalong madaling panahon.",
        thankYou: "May iba pa ba akong matutulungan sa iyo ngayon?",
        notUnderstood: "Ikokonekta kita sa aming support team para sa karagdagang tulong.",
        languageSelected: "Napiling wika: Tagalog. Paano kita matutulungan ngayon?",
        selectLanguage: "Mangyaring piliin ang iyong ninanais na wika:",
        welcomeMessage: "Maligayang pagdating sa Power2Connect! Mangyaring piliin ang iyong ninanais na wika:",
        aiThinking: "Ang Power2Connect AI ay nag-iisip...",
        staffJoined: "👨‍💼 Ang support staff ay sumali na sa chat. Maari nang ipagpatuloy ang inyong usapan.",
        staffTyping: "Nagta-type ang staff..."
    }
};

// Common issues for quick selection (still available as shortcuts)
const commonIssues = {
    en: {
        "billing": {
            question: "📋 Billing & Payment Issues",
            response: "For billing concerns, please provide your account details or describe your issue.",
            needsContact: true
        },
        "internet": {
            question: "⚡ Internet Connection Problems",
            response: "I can help with connection issues. Please tell me what's happening.",
            needsContact: true
        },
        "speed": {
            question: "📶 Slow Internet Speed",
            response: "For speed issues, let me help you troubleshoot.",
            needsContact: false
        },
        "account": {
            question: "👤 Account & Profile Help",
            response: "For account assistance, I can help you with various account-related issues.",
            needsContact: true
        },
        "technical": {
            question: "🔧 Technical Support",
            response: "For technical issues, I'll guide you through troubleshooting steps.",
            needsContact: true
        },
        "other": {
            question: "📞 Talk to Human Agent",
            response: "I'll connect you with a live customer service agent. Please provide your contact information.",
            needsContact: true
        }
    },
    tl: {
        "billing": {
            question: "📋 Mga Isyu sa Billing at Bayarin",
            response: "Para sa mga alalahanin sa billing, pakibigay ang iyong account details o ilarawan ang iyong isyu.",
            needsContact: true
        },
        "internet": {
            question: "⚡ Mga Problema sa Internet Connection",
            response: "Maaari kitang tulungan sa mga isyu sa koneksyon. Pakisabi sa akin kung ano ang nangyayari.",
            needsContact: true
        },
        "speed": {
            question: "📶 Mabagal na Internet Speed",
            response: "Para sa mga isyu sa bilis, hayaan mo akong tulungan kang mag-troubleshoot.",
            needsContact: false
        },
        "account": {
            question: "👤 Tulong sa Account at Profile",
            response: "Para sa tulong sa account, maaari kitang tulungan sa iba't ibang isyu na may kinalaman sa account.",
            needsContact: true
        },
        "technical": {
            question: "🔧 Technical Support",
            response: "Para sa mga teknikal na isyu, gagabayan kita sa mga hakbang sa pag-troubleshoot.",
            needsContact: true
        },
        "other": {
            question: "📞 Kausapin ang Human Agent",
            response: "Ikokonekta kita sa isang live na customer service agent. Pakibigay ang iyong contact information.",
            needsContact: true
        }
    }
};

/* ========== STAFF INTEGRATION FUNCTIONS ========== */

/* CHECK IF STAFF HAS JOINED */
async function checkStaffStatus() {
    if (staffJoined) return true;
    
    try {
        const response = await fetch('check_staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: chatSessionId })
        });
        
        const data = await response.json();
        if (data.staff_joined && data.staff_name) {
            staffJoined = true;
            currentStaffName = data.staff_name;
            showStaffJoinedMessage();
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error checking staff status:', error);
        return false;
    }
}

/* SHOW STAFF JOINED MESSAGE */
function showStaffJoinedMessage() {
    const texts = translations[currentLang];
    addMsg(texts.staffJoined, "left");
    
    // Stop AI and enable direct chat
    conversationState = "staff_chat";
    
    // Clear any existing interval first
    if (staffMessageInterval) {
        clearInterval(staffMessageInterval);
    }
    
    // Start checking for new staff messages every 2 seconds
    staffMessageInterval = setInterval(checkForNewStaffMessages, 2000);
}

/* CHECK FOR NEW STAFF MESSAGES - SINGLE SOURCE OF TRUTH */
async function checkForNewStaffMessages() {
    if (!staffJoined) {
        if (staffMessageInterval) {
            clearInterval(staffMessageInterval);
            staffMessageInterval = null;
        }
        return;
    }
    
    try {
        const response = await fetch('get_staff_messages_fixed.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                session_id: chatSessionId,
                last_check: lastMessageCheck
            })
        });
        
        const data = await response.json();
        if (data.messages && data.messages.length > 0) {
            // Hide staff typing indicator if showing
            hideStaffTypingIndicator();
            
            // Add new messages
            data.messages.forEach(msg => {
                if (msg.sender_type === 'staff') {
                    // Create unique message ID to prevent duplicates
                    const messageId = `staff_${msg.id}_${msg.created_at}`;
                    
                    if (!displayedMessageIds.has(messageId)) {
                        addMsg(msg.message, "left");
                        displayedMessageIds.add(messageId);
                    }
                }
            });
            
            // Update last check time
            lastMessageCheck = data.last_check;
        }
    } catch (error) {
        console.error('Error getting staff messages:', error);
    }
}

/* SHOW STAFF TYPING INDICATOR */
function showStaffTypingIndicator() {
    const texts = translations[currentLang];
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'staffTypingIndicator';
    typingDiv.innerHTML = `
        <span style="margin-right: 8px; font-size: 12px; color: #666;">${texts.staffTyping}</span>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
    `;
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* HIDE STAFF TYPING INDICATOR */
function hideStaffTypingIndicator() {
    const typingIndicator = document.getElementById('staffTypingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

/* CREATE CHAT SESSION */
async function createChatSession() {
    try {
        await fetch('create_chat_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                session_id: chatSessionId,
                language: currentLang
            })
        });
    } catch (error) {
        console.error('Error creating chat session:', error);
    }
}

/* SAVE MESSAGE TO DATABASE */
async function saveMessage(sender_type, message, sender_id = null) {
    try {
        await fetch('save_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: chatSessionId,
                sender_type: sender_type,
                sender_id: sender_id,
                message: message
            })
        });
    } catch (error) {
        console.error('Error saving message:', error);
    }
}

/* ========== AI FUNCTIONS (Modified for Staff Integration) ========== */

/* GET AI RESPONSE - Check staff first */
async function getAIResponse(userMessage) {
    // Check if staff has joined
    const staffCheck = await checkStaffStatus();
    if (staffCheck) {
        return {
            text: "",
            needsContact: false,
            staffMode: true
        };
    }
    
    // If staff hasn't joined, proceed with AI
    if (useLocalAI) {
        return getLocalAIResponse(userMessage);
    }
    
    try {
        const texts = translations[currentLang];
        const systemPrompt = currentLang === 'tl' 
            ? `Ikaw ay isang customer support agent para sa Power2Connect. Tumulong sa mga customer sa mga isyu sa billing, internet, account, teknikal na suporta. Maging palakaibigan at propesyonal. Gamitin ang Tagalog.`
            : `You are a customer support agent for Power2Connect. Help customers with billing issues, internet problems, account concerns, technical support. Be friendly and professional. Use English.`;
        
        const historyContext = conversationHistory
            .slice(-3)
            .map(msg => `${msg.role}: ${msg.content}`)
            .join('\n');
        
        const prompt = `${systemPrompt}\n\n${historyContext}\nCustomer: ${userMessage}\nAssistant:`;
        
        const response = await fetch(FULL_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contents: [{ parts: [{ text: prompt }] }],
                generationConfig: { maxOutputTokens: 250, temperature: 0.7 }
            })
        });
        
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        
        const data = await response.json();
        if (!data.candidates || !data.candidates[0] || !data.candidates[0].content) {
            throw new Error('Invalid response from AI');
        }
        
        const aiText = data.candidates[0].content.parts[0].text;
        
        // Save to conversation history
        conversationHistory.push({ role: 'user', content: userMessage });
        conversationHistory.push({ role: 'assistant', content: aiText });
        
        // Save to database
        await saveMessage('ai', aiText);
        
        return {
            text: aiText,
            needsContact: checkIfContactNeeded(userMessage, aiText),
            staffMode: false
        };
        
    } catch (error) {
        console.error('Gemini API failed:', error);
        useLocalAI = true;
        return getLocalAIResponse(userMessage);
    }
}

/* LOCAL AI RESPONSE (Fallback when API fails) */
function getLocalAIResponse(userMessage) {
    const isTagalog = currentLang === 'tl';
    const lowerMessage = userMessage.toLowerCase();
    
    let response = '';
    let needsContact = false;
    
    // Smart keyword matching with better responses
    if (lowerMessage.includes('bill') || lowerMessage.includes('payment') || lowerMessage.includes('invoice') || 
        lowerMessage.includes('bayad') || lowerMessage.includes('singil')) {
        response = isTagalog 
            ? "**Para sa Billing Concerns:**\n\nMaaari kitang tulungan sa:\n• Pag-check ng balance\n• Pag-report ng billing error\n• Payment arrangements\n\nPara matulungan kita, kailangan ko ng iyong account number."
            : "**For Billing Concerns:**\n\nI can help you with:\n• Balance checking\n• Reporting billing errors\n• Payment arrangements\n\nTo assist you, I need your account number.";
        needsContact = true;
    }
    else if (lowerMessage.includes('internet') || lowerMessage.includes('connection') || lowerMessage.includes('wifi') ||
             lowerMessage.includes('koneksyon') || lowerMessage.includes('wifi')) {
        response = isTagalog
            ? "**Para sa Internet Issues:**\n\nMga karaniwang solusyon:\n1. I-restart ang router/modem\n2. Check cable connections\n3. Reboot your device\n\nAnong specific na problema: walang internet o intermittent?"
            : "**For Internet Issues:**\n\nCommon solutions:\n1. Restart router/modem\n2. Check cable connections\n3. Reboot your device\n\nWhat specific problem: no internet or intermittent?";
        needsContact = false;
    }
    else if (lowerMessage.includes('slow') || lowerMessage.includes('speed') || lowerMessage.includes('bagal') || lowerMessage.includes('bilis')) {
        response = isTagalog
            ? "**Slow Internet Troubleshooting:**\n\n1. Test speed: speedtest.net\n2. Wired vs Wireless test\n3. Time of day matters\n\nAnong speed ang nakukuha mo?"
            : "**Slow Internet Troubleshooting:**\n\n1. Test speed: speedtest.net\n2. Wired vs Wireless test\n3. Time of day matters\n\nWhat speed are you getting?";
        needsContact = false;
    }
    else if (lowerMessage.includes('account') || lowerMessage.includes('profile') || lowerMessage.includes('password') ||
             lowerMessage.includes('account') || lowerMessage.includes('password')) {
        response = isTagalog
            ? "**Account Assistance:**\n\nMaaaring gawin:\n• Update personal info\n• Reset password\n• View account details\n\nAnong specific na tulong ang kailangan mo?"
            : "**Account Assistance:**\n\nAvailable actions:\n• Update personal info\n• Reset password\n• View account details\n\nWhat specific help do you need?";
        needsContact = true;
    }
    else if (lowerMessage.includes('outage') || lowerMessage.includes('blackout') || lowerMessage.includes('brownout') ||
             lowerMessage.includes('putol') || lowerMessage.includes('wala')) {
        response = isTagalog
            ? "**Service Outage Report:**\n\n1. Location mo? (barangay/city)\n2. Kailan nagsimula?\n3. Apektado ba ang kapitbahay?\n\nMagre-report ako sa technical team."
            : "**Service Outage Report:**\n\n1. Your location? (barangay/city)\n2. When did it start?\n3. Are neighbors affected?\n\nI'll report to technical team.";
        needsContact = true;
    }
    else if (lowerMessage.includes('hello') || lowerMessage.includes('hi') || lowerMessage.includes('kamusta') || 
             lowerMessage.includes('help') || lowerMessage.includes('tulong')) {
        response = isTagalog
            ? "**Power2Connect Support:**\n\nMaaari kitang tulungan sa:\n• 📋 Billing & Payments\n• 🌐 Internet Issues\n• 👤 Account Help\n• 🔧 Technical Support\n\nAnong area ang kailangan ng tulong?"
            : "**Power2Connect Support:**\n\nI can help you with:\n• 📋 Billing & Payments\n• 🌐 Internet Issues\n• 👤 Account Help\n• 🔧 Technical Support\n\nWhich area needs assistance?";
        needsContact = false;
    }
    else {
        response = isTagalog
            ? "Salamat sa iyong mensahe! Para mas mabuti kitang matulungan:\n1. I-describe ang concern nang detalyado\n2. Sabihin ang location mo\n\nPaano pa kita matutulungan?"
            : "Thank you for your message! To better assist you:\n1. Describe your concern in detail\n2. Tell me your location\n\nHow else can I help you?";
        needsContact = true;
    }
    
    // Add to conversation history for local mode too
    conversationHistory.push({ role: 'user', content: userMessage });
    conversationHistory.push({ role: 'assistant', content: response });
    
    return {
        text: response,
        needsContact: needsContact,
        staffMode: false
    };
}

/* CHECK IF CONTACT INFO IS NEEDED */
function checkIfContactNeeded(userMessage, aiResponse) {
    const lowerMessage = userMessage.toLowerCase();
    const lowerResponse = aiResponse.toLowerCase();
    
    // Keywords that indicate contact info might be needed
    const contactKeywords = [
        'contact', 'call', 'email', 'phone', 'number', 'reach', 'speak', 'talk',
        'agent', 'representative', 'human', 'person', 'callback',
        'tawag', 'telepono', 'numero', 'email', 'makipag-ugnayan', 'kausap'
    ];
    
    const issueKeywords = [
        'billing', 'payment', 'bill', 'invoice', 'charge',
        'account', 'profile', 'password', 'username',
        'technical', 'setup', 'install', 'router', 'modem',
        'service', 'outage', 'disconnected',
        'bayad', 'singil', 'account',
        'teknikal', 'setup', 'install',
        'serbisyo', 'putol'
    ];
    
    // Check if message contains contact-related keywords
    const hasContactKeyword = contactKeywords.some(keyword => 
        lowerMessage.includes(keyword) || lowerResponse.includes(keyword)
    );
    
    const hasIssueKeyword = issueKeywords.some(keyword => 
        lowerMessage.includes(keyword)
    );
    
    // Return true if both conditions are met
    return hasIssueKeyword && hasContactKeyword;
}

/* SHOW TYPING INDICATOR */
function showTypingIndicator() {
    const texts = translations[currentLang];
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'typingIndicator';
    typingDiv.innerHTML = `
        <span style="margin-right: 8px; font-size: 12px; color: #666;">${texts.aiThinking}</span>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
    `;
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* HIDE TYPING INDICATOR */
function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

/* ========== CHAT FUNCTIONS ========== */

/* INITIALIZE CHAT */
function initializeChat() {
    // Clear any existing messages except language options
    clearInitialMessages();
    
    // Show language selection screen
    showLanguageSelection();
}

/* SHOW LANGUAGE SELECTION SCREEN */
function showLanguageSelection() {
    // Add welcome message
    addMsg("Welcome to Power2Connect! Please select your preferred language:", "left");
    
    // Make sure language options are visible
    languageOptions.style.display = "flex";
    
    // Disable input until language is selected
    input.disabled = true;
    sendBtn.disabled = true;
    input.placeholder = "Select a language to start chat";
    
    conversationState = "language_selection";
}

/* LANGUAGE SELECTION */
async function setLanguage(lang) {
    currentLang = lang;
    conversationState = "greeting";
    
    // Show language confirmation
    const texts = translations[currentLang];
    addMsg(texts.languageSelected, "left");
    
    // Remove language options
    languageOptions.style.display = "none";
    
    // Clear conversation history for new session
    conversationHistory = [];
    useLocalAI = false; // Reset to try API again
    
    // Create chat session in database
    await createChatSession();
    
    // Initialize chat after language selection
    setTimeout(() => {
        startChatSession();
    }, 1000);
}

/* START CHAT SESSION AFTER LANGUAGE SELECTION */
function startChatSession() {
    const texts = translations[currentLang];
    addMsg(texts.welcome, "left");
    addMsg(texts.options, "left");
    showOptions();
    
    // Enable input
    input.disabled = false;
    sendBtn.disabled = false;
    input.placeholder = texts.inputPlaceholder;
    conversationState = "waiting";
    
    // Start checking for staff every 5 seconds
    setInterval(checkStaffStatus, 5000);
    
    // Focus on input
    setTimeout(() => {
        input.focus();
    }, 500);
}

/* SHOW OPTION BUTTONS (Quick shortcuts) */
function showOptions() {
    const issues = commonIssues[currentLang] || commonIssues.en;
    
    // Clear existing dynamic options if any
    const existingOptions = document.getElementById("dynamicOptions");
    if (existingOptions) existingOptions.remove();
    
    // Create new options
    const optionsDiv = document.createElement("div");
    optionsDiv.className = "options-grid";
    optionsDiv.id = "dynamicOptions";
    
    // Add buttons for each issue
    Object.keys(issues).forEach(key => {
        const issue = issues[key];
        const button = document.createElement("button");
        button.className = "option-btn";
        button.innerHTML = `<span class="option-icon">${issue.question.substring(0, 2)}</span>
                           <span class="option-text">${issue.question.substring(3)}</span>`;
        button.onclick = () => selectIssue(key);
        optionsDiv.appendChild(button);
    });
    
    chatBody.appendChild(optionsDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* HANDLE ISSUE SELECTION (Quick options) */
async function selectIssue(issueKey) {
    const issues = commonIssues[currentLang] || commonIssues.en;
    const issue = issues[issueKey];
    
    // Remove options
    const optionsDiv = document.getElementById("dynamicOptions");
    if (optionsDiv) optionsDiv.remove();
    
    // Show user's selection
    addMsg(issue.question, "right");
    
    // Show bot response
    setTimeout(async () => {
        showTypingIndicator();
        
        try {
            const aiResponse = await getAIResponse(issue.question);
            hideTypingIndicator();
            addMsg(aiResponse.text, "left");
            
            // If AI suggests contact or issue needs contact
            if (issue.needsContact || aiResponse.needsContact) {
                setTimeout(() => {
                    askContactMethod();
                }, 1500);
            } else {
                setTimeout(() => {
                    askForMoreHelp();
                }, 2000);
            }
        } catch (error) {
            hideTypingIndicator();
            // Fallback to predefined response
            addMsg(issue.response, "left");
            
            if (issue.needsContact) {
                setTimeout(() => {
                    askContactMethod();
                }, 1000);
            } else {
                setTimeout(() => {
                    askForMoreHelp();
                }, 2000);
            }
        }
    }, 500);
}

/* HANDLE USER TEXT INPUT */
async function sendText() {
    if (currentLang === null) {
        addMsg("Please select a language first.", "left");
        return;
    }
    
    const text = input.value.trim();
    if (!text) return;
    
    // Clear input
    input.value = "";
    
    // Show user message
    addMsg(text, "right");
    
    // Save customer message to database
    await saveMessage('customer', text);
    
    // Process with appropriate handler
    await processUserInput(text);
}

async function processUserInput(text) {
    // Check if staff has joined
    if (staffJoined) {
        // When staff is chatting, just save message and wait for response
        showStaffTypingIndicator();
        return;
    }
    
    // Otherwise use AI
    showTypingIndicator();
    
    try {
        const aiResponse = await getAIResponse(text);
        hideTypingIndicator();
        
        if (aiResponse.staffMode) {
            // Staff has joined, AI stops responding
            return;
        }
        
        // Show AI response
        addMsg(aiResponse.text, "left");
        
        // Check if contact info needed
        if (aiResponse.needsContact) {
            setTimeout(() => {
                askContactMethod();
            }, 1500);
        } else {
            // Ask if more help needed
            setTimeout(() => {
                askForMoreHelp();
            }, 2000);
        }
        
    } catch (error) {
        hideTypingIndicator();
        
        // Fallback response
        const texts = translations[currentLang];
        addMsg(texts.notUnderstood, "left");
        
        // Ask for contact method as fallback
        setTimeout(() => {
            askContactMethod();
        }, 1000);
    }
}

/* ========== CONTACT & FORM FUNCTIONS ========== */

/* ASK FOR CONTACT METHOD */
function askContactMethod() {
    const texts = translations[currentLang];
    
    addMsg(texts.contactPrompt, "left");
    addMsg(texts.contactChoices, "left");
    
    // Show contact method options
    const contactOptionsDiv = document.createElement("div");
    contactOptionsDiv.className = "options";
    contactOptionsDiv.id = "contactMethodOptions";
    contactOptionsDiv.innerHTML = `
        <button onclick="chooseEmail()">${texts.emailOption}</button>
        <button onclick="choosePhone()">${texts.phoneOption}</button>
        <button onclick="skipContact()">${texts.skipOption}</button>
    `;
    
    chatBody.appendChild(contactOptionsDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* CHOOSE EMAIL */
function chooseEmail() {
    const texts = translations[currentLang];
    
    // Remove contact options
    const contactOptions = document.getElementById("contactMethodOptions");
    if (contactOptions) contactOptions.remove();
    
    // Ask for email
    addMsg(texts.emailOption, "right");
    setTimeout(() => {
        addMsg(texts.askEmail, "left");
        showEmailForm();
    }, 500);
}

/* CHOOSE PHONE */
function choosePhone() {
    const texts = translations[currentLang];
    
    // Remove contact options
    const contactOptions = document.getElementById("contactMethodOptions");
    if (contactOptions) contactOptions.remove();
    
    // Ask for phone
    addMsg(texts.phoneOption, "right");
    setTimeout(() => {
        addMsg(texts.askPhone, "left");
        showPhoneForm();
    }, 500);
}

/* SHOW EMAIL FORM */
function showEmailForm() {
    const texts = translations[currentLang];
    
    const formDiv = document.createElement("div");
    formDiv.className = "info-form";
    formDiv.id = "emailForm";
    formDiv.innerHTML = `
        <div class="form-group">
            <input type="email" id="userEmail" placeholder="example@email.com" class="form-input" required>
        </div>
        <div class="form-group">
            <input type="text" id="userName" placeholder="Your Name (Optional)" class="form-input">
        </div>
        <button onclick="submitEmail()" class="submit-btn">Submit Email</button>
        <button onclick="backToContactMethod()" class="submit-btn" style="background: #666; margin-top: 8px;">← Back</button>
    `;
    
    chatBody.appendChild(formDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* SHOW PHONE FORM */
function showPhoneForm() {
    const texts = translations[currentLang];
    
    const formDiv = document.createElement("div");
    formDiv.className = "info-form";
    formDiv.id = "phoneForm";
    formDiv.innerHTML = `
        <div class="form-group">
            <input type="tel" id="userPhone" placeholder="09XX-XXX-XXXX" class="form-input" required>
        </div>
        <div class="form-group">
            <input type="text" id="userNamePhone" placeholder="Your Name (Optional)" class="form-input">
        </div>
        <button onclick="submitPhone()" class="submit-btn">Submit Phone Number</button>
        <button onclick="backToContactMethod()" class="submit-btn" style="background: #666; margin-top: 8px;">← Back</button>
    `;
    
    chatBody.appendChild(formDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* SUBMIT EMAIL */
function submitEmail() {
    const texts = translations[currentLang];
    const email = document.getElementById("userEmail").value.trim();
    const name = document.getElementById("userName").value.trim();
    
    if (!email) {
        alert("Please enter your email address");
        return;
    }
    
    // Remove form
    const form = document.getElementById("emailForm");
    if (form) form.remove();
    
    // Show confirmation
    addMsg(email, "right");
    setTimeout(() => {
        addMsg(texts.submitting, "left");
        addMsg(texts.agentMessage, "left");
        
        // Save to server
        sendToServer({ email, phone: "", name });
        
        // Ask if more help needed
        setTimeout(askForMoreHelp, 1500);
    }, 500);
}

/* SUBMIT PHONE */
function submitPhone() {
    const texts = translations[currentLang];
    const phone = document.getElementById("userPhone").value.trim();
    const name = document.getElementById("userNamePhone").value.trim();
    
    if (!phone) {
        alert("Please enter your phone number");
        return;
    }
    
    // Remove form
    const form = document.getElementById("phoneForm");
    if (form) form.remove();
    
    // Show confirmation
    addMsg(phone, "right");
    setTimeout(() => {
        addMsg(texts.submitting, "left");
        addMsg(texts.agentMessage, "left");
        
        // Save to server
        sendToServer({ email: "", phone, name });
        
        // Ask if more help needed
        setTimeout(askForMoreHelp, 1500);
    }, 500);
}

/* SKIP CONTACT */
function skipContact() {
    const texts = translations[currentLang];
    
    // Remove contact options
    const contactOptions = document.getElementById("contactMethodOptions");
    if (contactOptions) contactOptions.remove();
    
    // Show message
    addMsg(texts.skipOption, "right");
    setTimeout(() => {
        addMsg("No problem! A support agent will assist you through this chat.", "left");
        
        // Ask if more help needed
        setTimeout(askForMoreHelp, 1500);
    }, 500);
}

/* BACK TO CONTACT METHOD */
function backToContactMethod() {
    // Remove form
    const emailForm = document.getElementById("emailForm");
    const phoneForm = document.getElementById("phoneForm");
    if (emailForm) emailForm.remove();
    if (phoneForm) phoneForm.remove();
    
    // Show contact method options again
    askContactMethod();
}

/* ASK FOR MORE HELP */
function askForMoreHelp() {
    const texts = translations[currentLang];
    
    setTimeout(() => {
        addMsg(texts.thankYou, "left");
        showHelpOptions();
    }, 1000);
}

/* SHOW HELP OPTIONS */
function showHelpOptions() {
    const helpDiv = document.createElement("div");
    helpDiv.className = "options";
    helpDiv.id = "helpOptions";
    helpDiv.innerHTML = `
        <button onclick="startNewTopic()">🆕 New Topic</button>
        <button onclick="clearChat()">🗑️ Clear Chat</button>
    `;
    
    chatBody.appendChild(helpDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* START NEW TOPIC */
function startNewTopic() {
    // Remove help options
    const helpOptions = document.getElementById("helpOptions");
    if (helpOptions) helpOptions.remove();
    
    // Add separator
    addMsg("--- New Conversation Topic ---", "left");
    
    // Show options again
    setTimeout(() => {
        const texts = translations[currentLang];
        addMsg(texts.options, "left");
        showOptions();
    }, 500);
}

/* CLEAR CHAT - Reset to language selection */
function clearChat() {
    // Remove help options
    const helpOptions = document.getElementById("helpOptions");
    if (helpOptions) helpOptions.remove();
    
    // Clear all chat messages
    const allMessages = chatBody.querySelectorAll('.msg, .options, .options-grid, .info-form, #dynamicOptions, #contactMethodOptions, #emailForm, #phoneForm, #typingIndicator, #staffTypingIndicator');
    allMessages.forEach(msg => {
        msg.remove();
    });
    
    // Clear conversation history
    conversationHistory = [];
    useLocalAI = false;
    staffJoined = false;
    currentStaffName = null;
    chatSessionId = generateSessionId();
    displayedMessageIds.clear();
    lastMessageCheck = new Date().toISOString();
    
    // Clear interval if exists
    if (staffMessageInterval) {
        clearInterval(staffMessageInterval);
        staffMessageInterval = null;
    }
    
    // Reset to language selection
    currentLang = null;
    conversationState = "language_selection";
    
    // Disable input
    input.disabled = true;
    sendBtn.disabled = true;
    input.placeholder = "Select a language to start chat";
    
    // Show welcome message again
    addMsg("Welcome to Power2Connect! Please select your preferred language:", "left");
    
    // Show language options
    languageOptions.style.display = "flex";
    
    // Scroll to show language options
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* CLEAR INITIAL MESSAGES */
function clearInitialMessages() {
    // Remove all messages except language options
    const allMessages = chatBody.querySelectorAll('.msg');
    allMessages.forEach(msg => {
        if (!msg.parentElement.id || !['languageOptions'].includes(msg.parentElement.id)) {
            msg.remove();
        }
    });
    
    // Remove any dynamic content
    const dynamicElements = chatBody.querySelectorAll('.options-grid, .info-form, #dynamicOptions, #contactMethodOptions, #emailForm, #phoneForm, #helpOptions, #typingIndicator, #staffTypingIndicator');
    dynamicElements.forEach(el => el.remove());
}

/* MESSAGE FUNCTION */
function addMsg(text, side) {
    const d = document.createElement("div");
    d.className = "msg " + side;
    d.innerHTML = text.replace(/\n/g, '<br>');
    chatBody.appendChild(d);
    chatBody.scrollTop = chatBody.scrollHeight;
}

/* SEND DATA TO SERVER */
function sendToServer(data) {
    // Simulate saving data (replace with actual backend)
    console.log("Saving user data:", data);
    
    // For demo purposes, just log to console
    fetch("save_query.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            email: data.email,
            phone: data.phone,
            name: data.name,
            query: "Customer support query",
            timestamp: new Date().toISOString(),
            lang: currentLang
        })
    })
    .then(res => res.json())
    .then(response => {
        console.log("Query saved:", response);
    })
    .catch(err => {
        console.error("Error saving query:", err);
    });
}

/* CHAT CONTROLS */
function minimizeChat() {
    chatBox.style.display = "none";
    chatMini.style.display = "block";
    chatBubble.style.display = "none";
}

function restoreChat() {
    chatBox.style.display = "flex";
    chatMini.style.display = "none";
}

function toggleMax() {
    chatBox.classList.toggle("maximized");
}

function closeChat() {
    chatBox.style.display = "none";
    chatMini.style.display = "none";
    chatBubble.style.display = "flex";
}

function openChat() {
    chatBox.style.display = "flex";
    chatBubble.style.display = "none";
}

/* BACKGROUND SLIDER */
const slides = document.querySelectorAll(".bg");
let current = 0;
setInterval(() => {
    slides[current].classList.remove("active");
    current = (current + 1) % slides.length;
    slides[current].classList.add("active");
}, 5000);

// Initialize when page loads
window.onload = function() {
    // Initialize the chat with language selection
    initializeChat();
    
    // Make sure chat bubble is hidden initially if chat box is open
    if (chatBox.style.display !== "none") {
        chatBubble.style.display = "none";
    }
    
    // Allow Enter key to send messages
    input.addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            sendText();
        }
    });
};

