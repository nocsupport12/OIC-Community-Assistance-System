// Customer_Ai_UI_Function.js

const chatBox = document.getElementById("chatBox");
const chatBody = document.getElementById("chatBody");
const input = document.getElementById("userInput");
const sendBtn = document.getElementById("sendBtn");
const chatMini = document.getElementById("chatMini");
const chatBubble = document.getElementById("chatBubble");
const languageOptions = document.getElementById("languageOptions");

// Gemini API Configuration
const GEMINI_API_KEY = 'AIzaSyDdQkrd4okOXiQCZtRI3Nj2GXmZJ143Kps';
const PROXY_URL = 'https://api.allorigins.win/raw?url=';
const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=${GEMINI_API_KEY}`;
const FULL_API_URL = PROXY_URL + encodeURIComponent(GEMINI_API_URL);

let currentLang = null;
let conversationState = "language_selection";
let conversationHistory = [];
let useLocalAI = false;
let chatSessionId = generateSessionId();
let currentUser = null;
let staffRequested = false;
let userPhone = null;
let userName = null;
let staffJoined = false;
let currentStaffName = null;
let staffMessageInterval = null;
let lastMessageCheck = new Date().toISOString();
let displayedMessageIds = new Set();
let currentTicketNumber = null;
let generatedTicketId = null;

// Generate unique session ID
function generateSessionId() {
    return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Format ticket number from database ID (e.g., 15 -> #015, 18 -> #018)
function formatTicketNumber(id) {
    return '#' + String(id).padStart(3, '0');
}

// Translations
const translations = {
    en: {
        welcome: "👋 Hello! Welcome to Power2Connect Customer Support.",
        options: "Please choose a category below:",
        inputPlaceholder: "Type your message here...",
        askPhone: "📞 Please enter your mobile number so our staff can contact you:",
        askName: "Please enter your name (optional):",
        invalidPhone: "❌ Invalid mobile number. Please enter a valid 11-digit Philippine mobile number (e.g., 09123456789)",
        phoneSubmitted: "Thank you! Your request has been sent to our staff. They will join the chat soon.",
        thankYou: "Is there anything else I can help you with today?",
        notUnderstood: "I'm sorry, I don't have enough information to answer that. Would you like to speak with a human agent?",
        languageSelected: "English selected. How can I help you today?",
        selectLanguage: "Please select your preferred language:",
        welcomeMessage: "Welcome to Power2Connect! Please select your preferred language:",
        aiThinking: "Power2Connect AI is thinking...",
        chooseIssue: "Please select a category:",
        errorServer: "Error connecting to server. Please try again.",
        selectLanguageFirst: "Please select a language first.",
        phoneLengthError: "❌ Mobile number must be 11 digits (e.g., 09123456789)",
        phoneNumberError: "❌ Please enter numbers only",
        cantAnswer: "I'm sorry, I couldn't answer your question. Would you like to speak with a human agent?",
        humanAgent: "👤 Talk to Human Agent",
        continueAI: "🤖 Continue with AI",
        submitting: "Submitting your request...",
        nameOptional: "Name is optional, you can skip by clicking Continue",
        phoneSuccess: "✅ Phone number saved! Staff will join shortly.",
        staffJoined: "👨‍💼 Staff has joined the chat. They will assist you now.",
        staffTyping: "Staff is typing...",
        staffMessage: "👨‍💼 Staff: ",
        solar: "☀️ Solar",
        internet: "🌐 Internet",
        followUp: "📋 Follow Up",
        ticketNumber: "🎫 Check Ticket Status",
        enterTicket: "Please enter your ticket number (e.g., #015, #018, #019):",
        ticketPlaceholder: "e.g., #018",
        ticketChecking: "Checking your ticket status...",
        ticketNotFound: "❌ Ticket not found. Please check the number and try again.",
        ticketFound: "✅ Ticket found! Here's your ticket status:",
        ticketStatus: "**🎫 Ticket Information**\n\n" +
            "• **Ticket #:** {ticket}\n" +
            "• **Name:** {name}\n" +
            "• **Status:** {status}\n" +
            "• **Date Requested:** {date} at {time}\n" +
            "• **Last Updated:** {updated}\n" +
            "• **Department:** {dept}\n" +
            "• **Assigned To:** {assigned}\n" +
            "• **Concern:** {concern}\n\n" +
            "**Remarks:**\n{message}",
        askTicketPhone: "📞 For security, please enter the mobile number used for this ticket:",
        invalidTicket: "❌ Invalid ticket format. Please use format # followed by numbers (e.g., #015, #018)",
        ticketVerified: "✅ Ticket verified. Here's your status:",
        retryTicket: "🔄 Try Again",
        mainMenu: "📋 Main Menu",
        talkToAgent: "👤 Talk to Agent",
        ticketGenerated: "✅ Your request has been submitted successfully!\n\n**Your Ticket Number: {ticket}**\n\nPlease save this ticket number for future reference. You can use it to check the status of your request in the Follow Up section.",
        saveTicket: "📝 Please save your ticket number: {ticket}",
        ticketInfo: "You will receive updates via SMS. Use your ticket number to check status anytime.",
        ticketFromDB: "Your ticket #{id} has been created in our system."
    },
    tl: {
        welcome: "👋 Kamusta! Maligayang pagdating sa Power2Connect Customer Support.",
        options: "Mangyaring pumili ng kategorya sa ibaba:",
        inputPlaceholder: "I-type ang iyong mensahe dito...",
        askPhone: "📞 Pakilagay ang iyong mobile number para ma-contact ka ng aming staff:",
        askName: "Pakilagay ang iyong pangalan (opsyonal):",
        invalidPhone: "❌ Hindi valid na mobile number. Pakilagay ng 11-digit na Philippine mobile number (hal. 09123456789)",
        phoneSubmitted: "Salamat! Naipadala na ang iyong request sa aming staff. Sila ay sasali sa chat.",
        thankYou: "May iba pa ba akong matutulungan sa iyo ngayon?",
        notUnderstood: "Paumanhin, hindi ko masagot ang iyong tanong. Gusto mo bang makausap ang aming human agent?",
        languageSelected: "Napiling wika: Tagalog. Paano kita matutulungan ngayon?",
        selectLanguage: "Mangyaring piliin ang iyong ninanais na wika:",
        welcomeMessage: "Maligayang pagdating sa Power2Connect! Mangyaring piliin ang iyong ninanais na wika:",
        aiThinking: "Ang Power2Connect AI ay nag-iisip...",
        chooseIssue: "Mangyaring pumili ng kategorya:",
        errorServer: "Error sa pagkonekta sa server. Pakisubukan muli.",
        selectLanguageFirst: "Mangyaring pumili muna ng wika.",
        phoneLengthError: "❌ Ang mobile number ay dapat 11 digits (hal. 09123456789)",
        phoneNumberError: "❌ Mga numero lamang ang ilagay",
        cantAnswer: "Paumanhin, hindi ko masagot ang iyong tanong. Gusto mo bang makausap ang aming human agent?",
        humanAgent: "👤 Kausapin ang Human Agent",
        continueAI: "🤖 Magpatuloy sa AI",
        submitting: "Ini-submit ang iyong request...",
        nameOptional: "Ang pangalan ay opsyonal, pwede mong i-skip sa pamamagitan ng pag-click ng Continue",
        phoneSuccess: "✅ Nai-save ang phone number! Sasali ang staff.",
        staffJoined: "👨‍💼 Sumali na ang staff sa chat. Sila na ang mag-aassist sa iyo.",
        staffTyping: "Nagta-type ang staff...",
        staffMessage: "👨‍💼 Staff: ",
        solar: "☀️ Solar",
        internet: "🌐 Internet",
        followUp: "📋 Follow Up",
        ticketNumber: "🎫 Tingnan ang Ticket Status",
        enterTicket: "Pakilagay ang iyong ticket number (hal., #015, #018, #019):",
        ticketPlaceholder: "hal., #018",
        ticketChecking: "Sinusuri ang ticket status...",
        ticketNotFound: "❌ Hindi nahanap ang ticket. Pakisuri ang number at subukan muli.",
        ticketFound: "✅ Nahanap ang ticket! Narito ang status:",
        ticketStatus: "**🎫 Impormasyon ng Ticket**\n\n" +
            "• **Ticket #:** {ticket}\n" +
            "• **Pangalan:** {name}\n" +
            "• **Status:** {status}\n" +
            "• **Petsa ng Request:** {date} ng {time}\n" +
            "• **Huling Update:** {updated}\n" +
            "• **Department:** {dept}\n" +
            "• **Nakatalaga:** {assigned}\n" +
            "• **Paksa:** {concern}\n\n" +
            "**Mga Remarks:**\n{message}",
        askTicketPhone: "📞 Para sa seguridad, pakilagay ang mobile number na ginamit para sa ticket na ito:",
        invalidTicket: "❌ Hindi valid na ticket format. Gamitin ang format # na sinusundan ng numero (hal., #015, #018)",
        ticketVerified: "✅ Na-verify ang ticket. Narito ang status:",
        retryTicket: "🔄 Subukan Muli",
        mainMenu: "📋 Main Menu",
        talkToAgent: "👤 Kausapin ang Agent",
        ticketGenerated: "✅ Matagumpay na naipadala ang iyong request!\n\n**Ang iyong Ticket Number: {ticket}**\n\nPakitandaan ang ticket number na ito para sa susunod na reference. Maaari mo itong gamitin para tingnan ang status ng iyong request sa Follow Up section.",
        saveTicket: "📝 Paki-save ang iyong ticket number: {ticket}",
        ticketInfo: "Makakatanggap ka ng updates sa pamamagitan ng SMS. Gamitin ang iyong ticket number para tingnan ang status kahit kailan.",
        ticketFromDB: "Ang iyong ticket #{id} ay nalikha na sa aming sistema."
    }
};

// Service categories with detailed information (fallback if database is empty)
const serviceCategories = {
    en: {
        solar: {
            title: "☀️ Solar Solutions",
            buying: {
                question: "💰 Buy Solar Panel",
                response: `**Solar Panel Purchase Options:**\n\n` +
                    `🏠 **Residential Solar Packages**\n` +
                    `• Basic Package: 2kW System - ₱150,000\n` +
                    `• Standard Package: 3kW System - ₱220,000\n` +
                    `• Premium Package: 5kW System - ₱350,000\n\n` +
                    `🏢 **Commercial Solar Solutions**\n` +
                    `• 10kW System - Starting at ₱650,000\n` +
                    `• 20kW System - Starting at ₱1.2M\n` +
                    `• Custom solutions available\n\n` +
                    `💰 **Payment Options**\n` +
                    `• Cash payment (5% discount)\n` +
                    `• Installment plans (0% interest for 12 months)\n` +
                    `• Bank financing available\n\n` +
                    `✅ **What's Included**\n` +
                    `• High-efficiency solar panels\n` +
                    `• Inverter system\n` +
                    `• Mounting hardware\n` +
                    `• Professional installation\n` +
                    `• 5-year warranty\n` +
                    `• 25-year panel performance warranty\n\n` +
                    `💡 **Interested?** Click "Talk to Human Agent" for a customized quote.`,
                isHumanAgent: false
            },
            installation: {
                question: "🔧 Solar Installation",
                response: `**Solar Panel Installation Services:**\n\n` +
                    `📋 **Installation Process**\n` +
                    `1. **Site Assessment** (Free consultation)\n` +
                    `   • Roof inspection\n` +
                    `   • Shading analysis\n` +
                    `   • Energy needs assessment\n\n` +
                    `2. **System Design**\n` +
                    `   • Custom system sizing\n` +
                    `   • Panel placement optimization\n` +
                    `   • Permit assistance\n\n` +
                    `3. **Professional Installation**\n` +
                    `   • Expert installation team\n` +
                    `   • 2-3 days for residential\n` +
                    `   • Minimal disruption\n\n` +
                    `4. **System Activation**\n` +
                    `   • Grid connection\n` +
                    `   • Testing and commissioning\n` +
                    `   • User training\n\n` +
                    `⏱️ **Installation Timeline**\n` +
                    `• Residential: 1-2 weeks (from permit to completion)\n` +
                    `• Commercial: 2-4 weeks (depending on size)\n\n` +
                    `🔧 **Installation Includes**\n` +
                    `• Professional mounting\n` +
                    `• Electrical wiring\n` +
                    `• Inverter setup\n` +
                    `• System testing\n` +
                    `• Clean-up\n\n` +
                    `📞 **Ready to install?** Contact our installation team today.`,
                isHumanAgent: false
            }
        },
        internet: {
            title: "🌐 Internet Solutions",
            buying: {
                question: "📡 Internet Plan",
                response: `**Internet Plans & Pricing:**\n\n` +
                    `🏠 **Residential Plans**\n\n` +
                    `**Plan 1: Basic Surf**\n` +
                    `• Speed: 25 Mbps\n` +
                    `• Price: ₱1,299/month\n` +
                    `• Perfect for: Browsing, email, social media\n` +
                    `• Includes: Free WiFi modem\n\n` +
                    `**Plan 2: Family Connect**\n` +
                    `• Speed: 50 Mbps\n` +
                    `• Price: ₱1,899/month\n` +
                    `• Perfect for: HD streaming, online classes\n` +
                    `• Includes: Free WiFi modem + 1 mesh extender\n\n` +
                    `**Plan 3: Power Stream**\n` +
                    `• Speed: 100 Mbps\n` +
                    `• Price: ₱2,499/month\n` +
                    `• Perfect for: 4K streaming, gaming\n` +
                    `• Includes: Free WiFi 6 router + mesh extender\n\n` +
                    `**Plan 4: Ultra Fast**\n` +
                    `• Speed: 200 Mbps\n` +
                    `• Price: ₱3,499/month\n` +
                    `• Perfect for: Multiple devices, heavy usage\n` +
                    `• Includes: Premium WiFi 6 router + 2 mesh extenders\n\n` +
                    `🏢 **Business Plans**\n` +
                    `• Business 50 Mbps: ₱3,500/month\n` +
                    `• Business 100 Mbps: ₱5,500/month\n` +
                    `• Business 200 Mbps: ₱8,500/month\n` +
                    `• Business 500 Mbps: ₱15,000/month\n` +
                    `• Dedicated fiber connection\n` +
                    `• Static IP included\n` +
                    `• 24/7 priority support\n\n` +
                    `🎁 **Promos**\n` +
                    `• First month free for annual plan\n` +
                    `• Free installation for 24-month contract\n` +
                    `• Bundle with solar and save 10%\n\n` +
                    `💡 **Want to apply?** Click "Talk to Human Agent" to process your application.`,
                isHumanAgent: false
            },
            installation: {
                question: "🛠️ Internet Installation",
                response: `**Internet Installation Services:**\n\n` +
                    `📋 **Installation Process**\n\n` +
                    `1. **Application Review**\n` +
                    `   • Check service availability\n` +
                    `   • Schedule installation date\n` +
                    `   • Document requirements\n\n` +
                    `2. **Site Survey**\n` +
                    `   • Technician visit\n` +
                    `   • Check connection point\n` +
                    `   • Plan cable routing\n\n` +
                    `3. **Physical Installation**\n` +
                    `   • Fiber cable installation\n` +
                    `   • Modem/router setup\n` +
                    `   • WiFi configuration\n\n` +
                    `4. **Activation & Testing**\n` +
                    `   • Service activation\n` +
                    `   • Speed test verification\n` +
                    `   • User orientation\n\n` +
                    `⏱️ **Installation Timeline**\n` +
                    `• Standard: 3-5 business days after application\n` +
                    `• Express: 24-48 hours (additional fee)\n` +
                    `• Installation time: 2-4 hours on-site\n\n` +
                    `💰 **Installation Fees**\n` +
                    `• Standard installation: ₱1,500\n` +
                    `• Free installation for 24-month plan\n` +
                    `• Additional outlets: ₱500 each\n\n` +
                    `📦 **What You'll Receive**\n` +
                    `• Fiber modem/router\n` +
                    `• Power adapter\n` +
                    `• Ethernet cable\n` +
                    `• User manual\n` +
                    `• Free WiFi (included in plan)\n\n` +
                    `🔧 **Requirements**\n` +
                    `• Valid ID\n` +
                    `• Proof of billing\n` +
                    `• Barangay clearance (for business)\n\n` +
                    `📞 **Ready to install?** Schedule your installation today.`,
                isHumanAgent: false
            }
        },
        followUp: {
            title: "📋 Follow Up",
            ticket: {
                question: "🎫 Check Ticket Status",
                response: `**Ticket Status Check**\n\n` +
                    `Enter your ticket number and registered mobile number to check the current status of your request.\n\n` +
                    `🔍 **What you can check:**\n` +
                    `• Current ticket status (Pending/In Progress/Completed)\n` +
                    `• Assignment details\n` +
                    `• Progress updates\n` +
                    `• Staff remarks\n\n` +
                    `📝 **Ticket number format:** # followed by numbers\n` +
                    `(e.g., #015, #018, #019)\n\n` +
                    `📱 **You'll need:**\n` +
                    `• Your ticket number\n` +
                    `• Registered mobile number\n\n` +
                    `Click "Check Ticket Status" to proceed.`,
                isHumanAgent: false
            }
        }
    },
    tl: {
        solar: {
            title: "☀️ Mga Solusyon sa Solar",
            buying: {
                question: "💰 Bumili ng Solar Panel",
                response: `**Mga Pagpipilian sa Pagbili ng Solar Panel:**\n\n` +
                    `🏠 **Residential Solar Packages**\n` +
                    `• Basic Package: 2kW System - ₱150,000\n` +
                    `• Standard Package: 3kW System - ₱220,000\n` +
                    `• Premium Package: 5kW System - ₱350,000\n\n` +
                    `🏢 **Commercial Solar Solutions**\n` +
                    `• 10kW System - Simula ₱650,000\n` +
                    `• 20kW System - Simula ₱1.2M\n` +
                    `• Custom solutions available\n\n` +
                    `💰 **Mga Pagpipilian sa Pagbabayad**\n` +
                    `• Cash payment (5% discount)\n` +
                    `• Installment plans (0% interest for 12 months)\n` +
                    `• Bank financing available\n\n` +
                    `✅ **Kasama sa Package**\n` +
                    `• High-efficiency solar panels\n` +
                    `• Inverter system\n` +
                    `• Mounting hardware\n` +
                    `• Professional installation\n` +
                    `• 5-year warranty\n` +
                    `• 25-year panel performance warranty\n\n` +
                    `💡 **Interesado?** I-click ang "Kausapin ang Human Agent" para sa customized quote.`,
                isHumanAgent: false
            },
            installation: {
                question: "🔧 Pag-install ng Solar",
                response: `**Mga Serbisyo sa Pag-install ng Solar Panel:**\n\n` +
                    `📋 **Proseso ng Pag-install**\n` +
                    `1. **Site Assessment** (Libreng consultation)\n` +
                    `   • Roof inspection\n` +
                    `   • Shading analysis\n` +
                    `   • Energy needs assessment\n\n` +
                    `2. **System Design**\n` +
                    `   • Custom system sizing\n` +
                    `   • Panel placement optimization\n` +
                    `   • Permit assistance\n\n` +
                    `3. **Professional Installation**\n` +
                    `   • Expert installation team\n` +
                    `   • 2-3 araw para sa residential\n` +
                    `   • Minimal disruption\n\n` +
                    `4. **System Activation**\n` +
                    `   • Grid connection\n` +
                    `   • Testing and commissioning\n` +
                    `   • User training\n\n` +
                    `⏱️ **Installation Timeline**\n` +
                    `• Residential: 1-2 linggo\n` +
                    `• Commercial: 2-4 na linggo\n\n` +
                    `🔧 **Kasama sa Installation**\n` +
                    `• Professional mounting\n` +
                    `• Electrical wiring\n` +
                    `• Inverter setup\n` +
                    `• System testing\n` +
                    `• Clean-up\n\n` +
                    `📞 **Handa nang magpa-install?** Tawagan ang installation team namin ngayon.`,
                isHumanAgent: false
            }
        },
        internet: {
            title: "🌐 Mga Solusyon sa Internet",
            buying: {
                question: "📡 Internet Plan",
                response: `**Mga Internet Plans at Presyo:**\n\n` +
                    `🏠 **Residential Plans**\n\n` +
                    `**Plan 1: Basic Surf**\n` +
                    `• Bilis: 25 Mbps\n` +
                    `• Presyo: ₱1,299/buwan\n` +
                    `• Para sa: Browsing, email, social media\n` +
                    `• Kasama: Libreng WiFi modem\n\n` +
                    `**Plan 2: Family Connect**\n` +
                    `• Bilis: 50 Mbps\n` +
                    `• Presyo: ₱1,899/buwan\n` +
                    `• Para sa: HD streaming, online classes\n` +
                    `• Kasama: Libreng WiFi modem + 1 mesh extender\n\n` +
                    `**Plan 3: Power Stream**\n` +
                    `• Bilis: 100 Mbps\n` +
                    `• Presyo: ₱2,499/buwan\n` +
                    `• Para sa: 4K streaming, gaming\n` +
                    `• Kasama: Libreng WiFi 6 router + mesh extender\n\n` +
                    `💡 **Gustong mag-apply?** I-click ang "Kausapin ang Human Agent" para sa application.`,
                isHumanAgent: false
            },
            installation: {
                question: "🛠️ Pag-install ng Internet",
                response: `**Mga Serbisyo sa Pag-install ng Internet:**\n\n` +
                    `📋 **Proseso ng Pag-install**\n\n` +
                    `1. **Application Review**\n` +
                    `   • I-check ang availability\n` +
                    `   • Mag-schedule ng installation\n` +
                    `   • Document requirements\n\n` +
                    `2. **Site Survey**\n` +
                    `   • Technician visit\n` +
                    `   • I-check ang connection point\n` +
                    `   • Planuhin ang cable routing\n\n` +
                    `3. **Physical Installation**\n` +
                    `   • Fiber cable installation\n` +
                    `   • Modem/router setup\n` +
                    `   • WiFi configuration\n\n` +
                    `4. **Activation & Testing**\n` +
                    `   • Service activation\n` +
                    `   • Speed test verification\n` +
                    `   • User orientation\n\n` +
                    `⏱️ **Installation Timeline**\n` +
                    `• Standard: 3-5 araw pagkatapos ng application\n` +
                    `• Express: 24-48 oras\n\n` +
                    `📞 **Handa nang magpa-install?** Mag-schedule ng installation ngayon.`,
                isHumanAgent: false
            }
        },
        followUp: {
            title: "📋 Follow Up",
            ticket: {
                question: "🎫 Tingnan ang Ticket Status",
                response: `**Pagsusuri ng Ticket Status**\n\n` +
                    `Ilagay ang iyong ticket number at registered mobile number para makita ang kasalukuyang status ng iyong request.\n\n` +
                    `🔍 **Ano ang maaaring tingnan:**\n` +
                    `• Kasalukuyang ticket status (Pending/In Progress/Completed)\n` +
                    `• Assignment details\n` +
                    `• Progress updates\n` +
                    `• Staff remarks\n\n` +
                    `📝 **Format ng ticket number:** # na sinusundan ng numero\n` +
                    `(hal., #015, #018, #019)\n\n` +
                    `📱 **Kailangan mo:**\n` +
                    `• Ang iyong ticket number\n` +
                    `• Registered mobile number\n\n` +
                    `I-click ang "Tingnan ang Ticket Status" para magpatuloy.`,
                isHumanAgent: false
            }
        }
    }
};

// Initialize chat
function initializeChat() {
    console.log("initializeChat() called");
    
    // Clear messages but keep language options
    const messages = chatBody.querySelectorAll('.msg');
    messages.forEach(msg => msg.remove());
    
    // Remove any dynamic elements
    const dynamicElements = chatBody.querySelectorAll('.options-grid, .info-form, #dynamicOptions, #helpOptions, #typingIndicator, #staffTypingIndicator, #phoneInputForm, #nameInputForm, #contactInfoForm, #humanAgentOptions, #categoryDropdowns, #ticketForm, #ticketPhoneForm, #ticketRetryOptions');
    dynamicElements.forEach(el => el.remove());
    
    // Show language selection
    showLanguageSelection();
}

function showLanguageSelection() {
    console.log("showLanguageSelection() called");
    
    // Add welcome message
    addMsg(translations.en.welcomeMessage, "left");
    
    // Ensure language options are visible
    if (languageOptions) {
        languageOptions.style.display = "flex";
        languageOptions.style.visibility = "visible";
        languageOptions.style.opacity = "1";
        languageOptions.style.pointerEvents = "auto";
    }
    
    // Disable input until language is selected
    input.disabled = true;
    sendBtn.disabled = true;
    input.placeholder = translations.en.selectLanguage;
    conversationState = "language_selection";
}

async function setLanguage(lang) {
    console.log("setLanguage() called with:", lang);
    currentLang = lang;
    
    const texts = translations[currentLang];
    addMsg(texts.languageSelected, "left");
    
    // Hide language options after selection
    if (languageOptions) {
        languageOptions.style.display = "none";
    }
    
    conversationHistory = [];
    useLocalAI = false;
    
    await createChatSession();
    
    // Start chat immediately after language selection
    setTimeout(() => {
        startChatSession();
    }, 1000);
}

async function createChatSession() {
    try {
        console.log("Creating chat session...");
        await fetch('chat/create_session.php', {
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

// START CHAT SESSION
function startChatSession() {
    const texts = translations[currentLang];
    addMsg(texts.welcome, "left");
    addMsg(texts.chooseIssue, "left");
    showCategoryDropdowns();
    
    // Enable input immediately
    input.disabled = false;
    sendBtn.disabled = false;
    input.placeholder = texts.inputPlaceholder;
    conversationState = "waiting";
    
    setTimeout(() => {
        input.focus();
    }, 500);
}

// ============= KNOWLEDGE BASE FUNCTIONS =============

// Search knowledge base from database
async function searchKnowledgeBase(query, language) {
    try {
        const response = await fetch('chat/knowledge_api.php?action=search&q=' + encodeURIComponent(query) + '&lang=' + language);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            return data.data[0]; // Return best match
        }
        return null;
    } catch (error) {
        console.error('Knowledge base error:', error);
        return null;
    }
}

// Get categories from database
async function getCategoriesFromDB(language) {
    try {
        const response = await fetch('chat/knowledge_api.php?action=categories&lang=' + language);
        const data = await response.json();
        
        // Log the response to see what we're getting
        console.log('Categories from DB:', data);
        
        return data.success ? data.data : [];
    } catch (error) {
        console.error('Categories error:', error);
        return [];
    }
}

// ============= AI RESPONSE FUNCTIONS =============

// GET AI RESPONSE - UPDATED to check knowledge base first
async function getAIResponse(userMessage) {
    // First, check knowledge base
    const knowledge = await searchKnowledgeBase(userMessage, currentLang);
    
    if (knowledge) {
        // Found in knowledge base
        conversationHistory.push({ role: 'user', content: userMessage });
        conversationHistory.push({ role: 'assistant', content: knowledge.answer });
        await saveMessage('ai', knowledge.answer);
        return { text: knowledge.answer };
    }
    
    // If not found, use existing AI logic
    if (useLocalAI) {
        return getLocalAIResponse(userMessage);
    }
    
    try {
        const systemPrompt = currentLang === 'tl' 
            ? `Ikaw ay isang customer support agent para sa Power2Connect. Tumulong sa mga customer sa mga isyu sa solar panels, internet plans, at iba pang serbisyo. Kung ang tanong ay hindi related sa iyong topics, sabihin na hindi mo ito masagot at mag-alok na makipag-usap sa human agent. Maging palakaibigan at propesyonal. Gamitin ang Tagalog.`
            : `You are a customer support agent for Power2Connect. Help customers with solar panels, internet plans, and other services. If the question is not related to your topics, say that you cannot answer it and offer to connect them to a human agent. Be friendly and professional. Use English.`;
        
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
                generationConfig: { maxOutputTokens: 500, temperature: 0.7 }
            })
        });
        
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        
        const data = await response.json();
        if (!data.candidates || !data.candidates[0] || !data.candidates[0].content) {
            throw new Error('Invalid response from AI');
        }
        
        const aiText = data.candidates[0].content.parts[0].text;
        
        conversationHistory.push({ role: 'user', content: userMessage });
        conversationHistory.push({ role: 'assistant', content: aiText });
        
        await saveMessage('ai', aiText);
        
        return { text: aiText };
        
    } catch (error) {
        console.error('Gemini API failed:', error);
        useLocalAI = true;
        return getLocalAIResponse(userMessage);
    }
}

// LOCAL AI RESPONSE (FALLBACK)
function getLocalAIResponse(userMessage) {
    const isTagalog = currentLang === 'tl';
    const lowerMessage = userMessage.toLowerCase();
    
    let response = '';
    
    const isRelatedToSupport = 
        lowerMessage.includes('solar') || lowerMessage.includes('panel') || 
        lowerMessage.includes('internet') || lowerMessage.includes('wifi') || 
        lowerMessage.includes('plan') || lowerMessage.includes('installation') ||
        lowerMessage.includes('buy') || lowerMessage.includes('price') ||
        lowerMessage.includes('cost') || lowerMessage.includes('araw') ||
        lowerMessage.includes('presyo') || lowerMessage.includes('pag-install') ||
        lowerMessage.includes('ticket') || lowerMessage.includes('follow') ||
        lowerMessage.includes('status') || lowerMessage.includes('#') ||
        lowerMessage.includes('pending') || lowerMessage.includes('progress') ||
        lowerMessage.includes('completed');
    
    if (!isRelatedToSupport) {
        response = isTagalog
            ? "Paumanhin, ang tanong mo ay hindi related sa aming support topics. Gusto mo bang makausap ang aming human agent?"
            : "I'm sorry, your question is not related to our support topics. Would you like to speak with a human agent?";
    }
    else if (lowerMessage.includes('solar')) {
        response = isTagalog 
            ? "Para sa solar concerns, maaari kang pumili ng kategorya mula sa dropdown menu sa itaas."
            : "For solar concerns, you can choose a category from the dropdown menu above.";
    }
    else if (lowerMessage.includes('internet')) {
        response = isTagalog
            ? "Para sa internet concerns, maaari kang pumili ng kategorya mula sa dropdown menu sa itaas."
            : "For internet concerns, you can choose a category from the dropdown menu above.";
    }
    else if (lowerMessage.includes('ticket') || lowerMessage.includes('follow') || lowerMessage.includes('#')) {
        response = isTagalog
            ? "Para sa ticket concerns, maaari mong i-check ang iyong ticket status sa pamamagitan ng 'Follow Up' sa ibaba. Gamitin ang ticket number na ibinigay sa iyo (hal., #015, #018)."
            : "For ticket concerns, you can check your ticket status through the 'Follow Up' below. Use the ticket number provided to you (e.g., #015, #018).";
    }
    else {
        response = isTagalog
            ? "Salamat sa iyong mensahe. Paano pa kita matutulungan?"
            : "Thank you for your message. How else can I help you?";
    }
    
    conversationHistory.push({ role: 'user', content: userMessage });
    conversationHistory.push({ role: 'assistant', content: response });
    
    return { text: response };
}

// ============= DROPDOWN FUNCTIONS =============

// SHOW CATEGORY DROPDOWNS - COMPLETELY DATABASE DRIVEN
async function showCategoryDropdowns() {
    const texts = translations[currentLang];
    
    // Get categories from database
    const dbCategories = await getCategoriesFromDB(currentLang);
    
    console.log('DB Categories for dropdowns:', dbCategories);
    
    const existingDropdowns = document.getElementById("categoryDropdowns");
    if (existingDropdowns) existingDropdowns.remove();
    
    const dropdownsDiv = document.createElement("div");
    dropdownsDiv.className = "category-dropdowns";
    dropdownsDiv.id = "categoryDropdowns";
    
    // If no categories in database, show fallback message
    if (dbCategories.length === 0) {
        const emptyDiv = document.createElement("div");
        emptyDiv.className = "text-center p-4 text-gray-500";
        emptyDiv.innerHTML = "No categories available. Please check back later.";
        dropdownsDiv.appendChild(emptyDiv);
    } else {
        // Group categories by dropdown group
        const groupedCategories = {};
        
        dbCategories.forEach(cat => {
            if (cat.dropdown_group_id && cat.group_name) {
                // Category belongs to a group
                const groupKey = `group_${cat.dropdown_group_id}`;
                if (!groupedCategories[groupKey]) {
                    groupedCategories[groupKey] = {
                        id: cat.dropdown_group_id,
                        name: cat.group_name,
                        icon: cat.group_icon || '📌',
                        // Use the group's color from database or default
                        color: cat.group_color || 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                        categories: []
                    };
                }
                groupedCategories[groupKey].categories.push(cat);
            }
        });
        
        console.log('Grouped categories:', groupedCategories);
        
        // Create dropdowns for each group from database
        for (const [key, group] of Object.entries(groupedCategories)) {
            if (group.categories.length === 0) continue;
            
            const groupDiv = document.createElement("div");
            groupDiv.className = "dropdown-container";
            
            let items = '';
            group.categories.forEach(cat => {
                // Escape category name for safe JavaScript
                const safeCatName = cat.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                items += `
                    <button class="dropdown-item" onclick="selectCategory('${safeCatName}')">
                        <span class="item-icon">${cat.icon}</span>
                        <span class="item-text">${cat.name}</span>
                    </button>
                `;
            });
            
            groupDiv.innerHTML = `
                <button class="dropdown-btn" onclick="toggleDropdown('group_${group.id}')" style="background: ${group.color};">
                    <span class="dropdown-icon">${group.icon}</span>
                    <span class="dropdown-title">${group.name}</span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="dropdown-content" id="group_${group.id}Dropdown">${items}</div>
            `;
            
            dropdownsDiv.appendChild(groupDiv);
        }
        
        // Always add Follow Up dropdown (special function)
        const followUpDiv = document.createElement("div");
        followUpDiv.className = "dropdown-container";
        followUpDiv.innerHTML = `
            <button class="dropdown-btn" onclick="toggleDropdown('followUp')" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                <span class="dropdown-icon">📋</span>
                <span class="dropdown-title">${texts.followUp}</span>
                <span class="dropdown-arrow">▼</span>
            </button>
            <div class="dropdown-content" id="followUpDropdown">
                <button class="dropdown-item" onclick="selectService('followUp', 'ticket')">
                    <span class="item-icon">🎫</span>
                    <span class="item-text">${texts.ticketNumber}</span>
                </button>
            </div>
        `;
        dropdownsDiv.appendChild(followUpDiv);
    }
    
    chatBody.appendChild(dropdownsDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Handle category selection from database
async function selectCategory(categoryName) {
    const texts = translations[currentLang];
    
    // Search for knowledge about this category
    const knowledge = await searchKnowledgeBase(categoryName, currentLang);
    
    // Remove dropdowns
    const dropdownsDiv = document.getElementById("categoryDropdowns");
    if (dropdownsDiv) dropdownsDiv.remove();
    
    addMsg(`Tell me about ${categoryName}`, "right");
    await saveMessage('user', `Tell me about ${categoryName}`);
    
    if (knowledge) {
        setTimeout(() => {
            addMsg(knowledge.answer, "left");
            setTimeout(() => askForMoreHelp(), 2000);
        }, 500);
    } else {
        setTimeout(() => {
            addMsg(`I can help you with ${categoryName}. What specific information do you need?`, "left");
            setTimeout(() => askForMoreHelp(), 2000);
        }, 500);
    }
}

// Toggle dropdown
function toggleDropdown(category) {
    const dropdown = document.getElementById(`${category}Dropdown`);
    if (!dropdown) return;
    
    const arrow = dropdown.previousElementSibling.querySelector('.dropdown-arrow');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-content').forEach(content => {
        if (content.id !== `${category}Dropdown` && content.classList.contains('show')) {
            content.classList.remove('show');
            const otherArrow = content.previousElementSibling.querySelector('.dropdown-arrow');
            if (otherArrow) otherArrow.textContent = '▼';
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
    arrow.textContent = dropdown.classList.contains('show') ? '▲' : '▼';
}

// Select service from dropdown (for Follow Up only)
async function selectService(category, serviceType) {
    const texts = translations[currentLang];
    const categories = serviceCategories[currentLang] || serviceCategories.en;
    
    // Close all dropdowns
    document.querySelectorAll('.dropdown-content').forEach(content => {
        content.classList.remove('show');
        const arrow = content.previousElementSibling.querySelector('.dropdown-arrow');
        if (arrow) arrow.textContent = '▼';
    });
    
    // Remove dropdowns after selection
    const dropdownsDiv = document.getElementById("categoryDropdowns");
    if (dropdownsDiv) dropdownsDiv.remove();
    
    if (category === 'followUp' && serviceType === 'ticket') {
        // Handle ticket check
        const service = categories[category][serviceType];
        addMsg(service.question, "right");
        await saveMessage('user', service.question);
        
        setTimeout(() => {
            addMsg(service.response, "left");
            setTimeout(() => {
                showTicketInputForm();
            }, 2000);
        }, 500);
    }
}

// ============= TICKET FUNCTIONS =============

// Show ticket input form
function showTicketInputForm() {
    const texts = translations[currentLang];
    
    const formDiv = document.createElement("div");
    formDiv.className = "info-form";
    formDiv.id = "ticketForm";
    formDiv.innerHTML = `
        <div class="form-group">
            <input type="text" id="ticketNumberInput" placeholder="${texts.ticketPlaceholder}" class="form-input" required>
            <small style="color: #666; display: block; margin-top: 5px;">${texts.enterTicket}</small>
        </div>
        <button onclick="checkTicketNumber()" class="submit-btn" style="background: #f97316;">Check Ticket</button>
        <button onclick="cancelTicketCheck()" class="submit-btn" style="background: #666; margin-top: 8px;">Cancel</button>
    `;
    
    chatBody.appendChild(formDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Check ticket number
function checkTicketNumber() {
    const ticketInput = document.getElementById("ticketNumberInput");
    if (!ticketInput) return;
    
    let ticketNumber = ticketInput.value.trim().toUpperCase();
    const texts = translations[currentLang];
    
    // Remove form
    document.getElementById("ticketForm")?.remove();
    
    // Add # if not present
    if (!ticketNumber.startsWith('#')) {
        ticketNumber = '#' + ticketNumber;
    }
    
    // Validate ticket format - just numbers after #
    const numPart = ticketNumber.replace('#', '');
    if (!/^\d+$/.test(numPart) || numPart.length > 4) {
        addMsg(texts.invalidTicket, "left");
        setTimeout(() => {
            askForMoreHelp();
        }, 2000);
        return;
    }
    
    addMsg(ticketNumber, "right");
    currentTicketNumber = ticketNumber;
    
    showTypingIndicator();
    
    // Store ticket number for later use
    sessionStorage.setItem('currentTicket', ticketNumber);
    
    // Ask for phone number first
    setTimeout(() => {
        hideTypingIndicator();
        addMsg(texts.askTicketPhone, "left");
        showTicketPhoneInput(ticketNumber);
    }, 1000);
}

// Show ticket phone input
function showTicketPhoneInput(ticketNumber) {
    const texts = translations[currentLang];
    
    const formDiv = document.createElement("div");
    formDiv.className = "info-form";
    formDiv.id = "ticketPhoneForm";
    formDiv.innerHTML = `
        <div class="form-group">
            <input type="tel" id="ticketPhoneInput" placeholder="09123456789" class="form-input" required 
                   onkeypress="return event.charCode >= 48 && event.charCode <= 57" 
                   maxlength="11">
            <small style="color: #666; display: block; margin-top: 5px;">${texts.phoneLengthError}</small>
        </div>
        <button onclick="verifyTicketWithDatabase('${ticketNumber}')" class="submit-btn" style="background: #f97316;">Verify & Check Status</button>
        <button onclick="cancelTicketCheck()" class="submit-btn" style="background: #666; margin-top: 8px;">Cancel</button>
    `;
    
    chatBody.appendChild(formDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Verify ticket with database
async function verifyTicketWithDatabase(ticketNumber) {
    const phoneInput = document.getElementById("ticketPhoneInput");
    if (!phoneInput) return;
    
    const phone = phoneInput.value.trim();
    const texts = translations[currentLang];
    
    // Remove form
    document.getElementById("ticketPhoneForm")?.remove();
    
    // Validate phone
    if (!/^\d+$/.test(phone)) {
        alert(texts.phoneNumberError);
        showTicketPhoneInput(ticketNumber);
        return;
    }
    
    if (phone.length !== 11) {
        alert(texts.phoneLengthError);
        showTicketPhoneInput(ticketNumber);
        return;
    }
    
    if (!/^09\d{9}$/.test(phone)) {
        alert(texts.invalidPhone);
        showTicketPhoneInput(ticketNumber);
        return;
    }
    
    addMsg(phone, "right");
    
    showTypingIndicator();
    
    try {
        // Use absolute path to ensure correct location
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const apiUrl = baseUrl + 'chat/get_ticket_status.php';
        
        console.log('Sending request to:', apiUrl);
        console.log('Data:', { ticket_number: ticketNumber, phone: phone });
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_number: ticketNumber,
                phone: phone
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Response:', data);
        
        hideTypingIndicator();
        
        if (data.success) {
            // Ticket found and verified - regardless of status
            const ticket = data.ticket;
            
            addMsg(texts.ticketVerified, "left");
            
            // Create formatted status message
            const statusMsg = texts.ticketStatus
                .replace('{ticket}', ticket.ticket)
                .replace('{name}', ticket.name)
                .replace('{status}', ticket.status)
                .replace('{date}', ticket.date)
                .replace('{time}', ticket.time)
                .replace('{updated}', ticket.updated)
                .replace('{dept}', ticket.dept)
                .replace('{assigned}', ticket.assigned)
                .replace('{concern}', ticket.concern)
                .replace('{message}', ticket.message);
            
            addMsg(statusMsg, "left");
            
            // Update conversation history
            conversationHistory.push({ 
                role: 'system', 
                content: `Ticket ${ticketNumber} status: ${ticket.status_raw}` 
            });
            
            setTimeout(() => {
                askForMoreHelp();
            }, 4000);
            
        } else {
            // Ticket not found or phone mismatch
            addMsg(data.message, "left");
            
            // Show retry options
            setTimeout(() => {
                const optionsDiv = document.createElement("div");
                optionsDiv.className = "options";
                optionsDiv.id = "ticketRetryOptions";
                optionsDiv.innerHTML = `
                    <button onclick="retryTicketCheck()" style="background: #f97316; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
                        🔄 ${texts.retryTicket}
                    </button>
                    <button onclick="showCategoryDropdowns()" style="background: #3b82f6; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
                        📋 ${texts.mainMenu}
                    </button>
                    <button onclick="requestHumanAgent()" style="background: #22c55e; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
                        👤 ${texts.talkToAgent}
                    </button>
                `;
                chatBody.appendChild(optionsDiv);
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 1500);
        }
        
    } catch (error) {
        hideTypingIndicator();
        console.error('Detailed error:', error);
        
        // More specific error message
        let errorMsg = "❌ Error connecting to server. ";
        
        if (error.message.includes('Failed to fetch')) {
            errorMsg += "Please check if the server is running and the chat/get_ticket_status.php file exists.";
        } else {
            errorMsg += "Please try again.";
        }
        
        addMsg(errorMsg, "left");
        
        // Show debug info in console
        console.log('Current URL:', window.location.href);
        console.log('Base URL:', window.location.origin + window.location.pathname);
        
        setTimeout(() => {
            askForMoreHelp();
        }, 3000);
    }
}

// Retry ticket check
function retryTicketCheck() {
    document.getElementById("ticketRetryOptions")?.remove();
    const lastTicket = sessionStorage.getItem('currentTicket');
    if (lastTicket) {
        showTicketInputForm();
    } else {
        showCategoryDropdowns();
    }
}

// Cancel ticket check
function cancelTicketCheck() {
    document.getElementById("ticketForm")?.remove();
    document.getElementById("ticketPhoneForm")?.remove();
    document.getElementById("ticketRetryOptions")?.remove();
    
    addMsg("Ticket check cancelled", "right");
    setTimeout(() => {
        askForMoreHelp();
    }, 1000);
}

// ============= MESSAGE FUNCTIONS =============

// SAVE MESSAGE TO DATABASE
async function saveMessage(sender_type, message) {
    try {
        await fetch('chat/save_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: chatSessionId,
                sender_type: sender_type,
                message: message
            })
        });
    } catch (error) {
        console.error('Error saving message:', error);
    }
}

// SEND TEXT MESSAGE
async function sendText() {
    if (currentLang === null) {
        addMsg(translations.en.selectLanguageFirst, "left");
        return;
    }
    
    const text = input.value.trim();
    if (!text) return;
    
    input.value = "";
    addMsg(text, "right");
    await saveMessage('user', text);
    
    if (!staffJoined) {
        await processUserInput(text);
    }
}

// PROCESS USER INPUT
async function processUserInput(text) {
    showTypingIndicator();
    
    try {
        const aiResponse = await getAIResponse(text);
        hideTypingIndicator();
        
        addMsg(aiResponse.text, "left");
        
        const needsHumanAgent = aiResponse.text.toLowerCase().includes('human agent') || 
                                aiResponse.text.toLowerCase().includes('makausap ang aming human agent');
        
        if (needsHumanAgent) {
            setTimeout(() => {
                askForHumanAgentConfirmation();
            }, 1500);
        } else {
            setTimeout(() => {
                askForMoreHelp();
            }, 2000);
        }
        
    } catch (error) {
        hideTypingIndicator();
        const texts = translations[currentLang];
        addMsg(texts.notUnderstood, "left");
        
        setTimeout(() => {
            askForHumanAgentConfirmation();
        }, 1500);
    }
}

// ============= HUMAN AGENT FUNCTIONS =============

// ASK FOR HUMAN AGENT CONFIRMATION
function askForHumanAgentConfirmation() {
    const texts = translations[currentLang];
    addMsg(texts.cantAnswer, "left");
    
    const optionsDiv = document.createElement("div");
    optionsDiv.className = "options";
    optionsDiv.id = "humanAgentOptions";
    optionsDiv.innerHTML = `
        <button onclick="requestHumanAgent()" style="background: #f97316; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
            👤 ${texts.humanAgent}
        </button>
        <button onclick="continueWithAI()" style="background: #3b82f6; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
            🤖 ${texts.continueAI}
        </button>
    `;
    
    chatBody.appendChild(optionsDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function continueWithAI() {
    document.getElementById("humanAgentOptions")?.remove();
    addMsg(translations[currentLang].continueAI, "right");
    setTimeout(() => {
        askForMoreHelp();
    }, 1000);
}

function requestHumanAgent() {
    document.getElementById("humanAgentOptions")?.remove();
    addMsg(translations[currentLang].humanAgent, "right");
    setTimeout(() => {
        askForContactInfo();
    }, 1000);
}

// ASK FOR CONTACT INFORMATION
function askForContactInfo() {
    const texts = translations[currentLang];
    addMsg(texts.askPhone, "left");
    
    const formDiv = document.createElement("div");
    formDiv.className = "info-form";
    formDiv.id = "contactInfoForm";
    formDiv.innerHTML = `
        <div class="form-group">
            <input type="tel" id="userPhoneInput" placeholder="09123456789" class="form-input" required 
                   onkeypress="return event.charCode >= 48 && event.charCode <= 57" 
                   maxlength="11">
            <small style="color: #666; display: block; margin-top: 5px;">${texts.phoneLengthError}</small>
        </div>
        <div class="form-group">
            <input type="text" id="userNameInput" placeholder="${texts.askName}" class="form-input">
            <small style="color: #666; display: block; margin-top: 5px;">${texts.nameOptional}</small>
        </div>
        <button onclick="submitContactInfo()" class="submit-btn">Submit</button>
        <button onclick="skipContactInfo()" class="submit-btn" style="background: #666; margin-top: 8px;">Skip for now</button>
    `;
    
    chatBody.appendChild(formDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// SUBMIT CONTACT INFORMATION - UPDATED TO USE DATABASE ID AS TICKET NUMBER
async function submitContactInfo() {
    const phone = document.getElementById("userPhoneInput").value.trim();
    const name = document.getElementById("userNameInput").value.trim();
    const texts = translations[currentLang];

    // Check if phone is already submitted within 6 hours
    const lastSubmitted = localStorage.getItem(`submitted_${phone}`);
    if (lastSubmitted) {
        const elapsed = Date.now() - parseInt(lastSubmitted);
        if (elapsed < 6 * 60 * 60 * 1000) { // 6 hours in ms
            alert("You already submitted your issue. Please try again after 6 hours.");
            return;
        }
    }

    if (!/^\d+$/.test(phone)) {
        alert(texts.phoneNumberError);
        return;
    }

    if (phone.length !== 11) {
        alert(texts.phoneLengthError);
        return;
    }

    if (!/^09\d{9}$/.test(phone)) {
        alert(texts.invalidPhone);
        return;
    }

    document.getElementById("contactInfoForm")?.remove();

    if (name) {
        addMsg(name, "right");
        userName = name;
    }
    addMsg(phone, "right");
    userPhone = phone;

    showTypingIndicator();

    try {
        // Save user to database
        const userResponse = await fetch('chat/save_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mobile_number: phone,
                name: name
            })
        });

        const userData = await userResponse.json();

        // Save callback request - using your existing save_callback.php
        const callbackResponse = await fetch('chat/save_callback.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: chatSessionId,
                phone: phone,
                name: name,
                reason: "Customer requested human agent assistance"
            })
        });

        const callbackData = await callbackResponse.json();
        
        if (callbackData.success) {
            hideTypingIndicator();
            
            // Get the ticket ID from the database response
            const ticketId = callbackData.request_id; // This comes from mysqli_insert_id()
            
            // Format the ticket number (e.g., 15 -> #015, 18 -> #018)
            const formattedTicket = formatTicketNumber(ticketId);
            
            // Show success message with ticket number from database
            const ticketMsg = texts.ticketGenerated.replace('{ticket}', formattedTicket);
            addMsg(ticketMsg, "left");
            
            // Additional info about ticket
            setTimeout(() => {
                addMsg(`📝 Your ticket #${ticketId} has been recorded in our system. Use ${formattedTicket} for follow-ups.`, "left");
                addMsg(texts.ticketInfo, "left");
            }, 2000);
            
            staffRequested = true;

            // Store submission timestamp and ticket number
            localStorage.setItem(`submitted_${phone}`, Date.now());
            localStorage.setItem(`ticket_${phone}`, formattedTicket);
            localStorage.setItem(`ticket_id_${phone}`, ticketId);
            
            // Save ticket number to session for follow-up
            sessionStorage.setItem('lastTicket', formattedTicket);
            sessionStorage.setItem('lastTicketId', ticketId);

            input.disabled = false;
            sendBtn.disabled = false;
            input.placeholder = texts.inputPlaceholder;

            startStaffMessageCheck();

            setTimeout(() => askForMoreHelp(), 4000);
        } else {
            hideTypingIndicator();
            addMsg("Error submitting request. Please try again.", "left");
            setTimeout(() => askForMoreHelp(), 2000);
        }
    } catch (error) {
        hideTypingIndicator();
        console.error('Error:', error);
        addMsg(texts.errorServer, "left");
        setTimeout(() => askForMoreHelp(), 2000);
    }
}

function skipContactInfo() {
    document.getElementById("contactInfoForm")?.remove();
    addMsg("Skipped contact information", "right");
    setTimeout(() => {
        askForMoreHelp();
    }, 1000);
}

// ============= STAFF MESSAGE FUNCTIONS =============

// STAFF MESSAGE CHECKING FUNCTIONS
function startStaffMessageCheck() {
    if (staffMessageInterval) {
        clearInterval(staffMessageInterval);
    }
    
    console.log("Started checking for staff messages");
    staffMessageInterval = setInterval(checkForStaffMessages, 3000);
}

async function checkForStaffMessages() {
    if (!chatSessionId) return;
    
    try {
        const response = await fetch('chat/get_staff_messages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                session_id: chatSessionId,
                last_check: lastMessageCheck
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.messages && data.messages.length > 0) {
            hideStaffTypingIndicator();
            
            if (!staffJoined && data.messages.length > 0) {
                staffJoined = true;
                showStaffJoinedMessage();
            }
            
            data.messages.forEach(msg => {
                if (msg.sender_type === 'staff') {
                    const messageId = `staff_${msg.id}_${msg.created_at}`;
                    
                    if (!displayedMessageIds.has(messageId)) {
                        const texts = translations[currentLang];
                        addMsg(texts.staffMessage + msg.message, "left");
                        displayedMessageIds.add(messageId);
                        
                        conversationHistory.push({ 
                            role: 'staff', 
                            content: msg.message 
                        });
                    }
                }
            });
            
            lastMessageCheck = data.last_check;
        }
    } catch (error) {
        console.error('Error checking for staff messages:', error);
    }
}

function showStaffJoinedMessage() {
    const texts = translations[currentLang];
    addMsg(texts.staffJoined, "left");
    conversationState = "staff_chat";
}

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

function hideStaffTypingIndicator() {
    const typingIndicator = document.getElementById('staffTypingIndicator');
    if (typingIndicator) typingIndicator.remove();
}

// ============= HELP FUNCTIONS =============

// ASK FOR MORE HELP
function askForMoreHelp() {
    const texts = translations[currentLang];
    
    setTimeout(() => {
        addMsg(texts.thankYou, "left");
        showHelpOptions();
    }, 1000);
}

// SHOW HELP OPTIONS - UPDATED WITH QUICK TICKET CHECK
function showHelpOptions() {
    const helpDiv = document.createElement("div");
    helpDiv.className = "options";
    helpDiv.id = "helpOptions";
    
    // Check if user has a recent ticket
    const lastTicket = sessionStorage.getItem('lastTicket') || localStorage.getItem('lastTicket');
    
    let buttons = '<button onclick="startNewTopic()" style="background: #3b82f6; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">🆕 New Topic</button>';
    buttons += '<button onclick="showCategoryDropdowns()" style="background: #22c55e; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">📋 Main Menu</button>';
    
    if (lastTicket) {
        buttons += `<button onclick="quickCheckTicket('${lastTicket}')" style="background: #f97316; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">
            🎫 Check Ticket ${lastTicket}
        </button>`;
    } else {
        buttons += '<button onclick="showTicketInputForm()" style="background: #f97316; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">🎫 Check My Ticket</button>';
    }
    
    buttons += '<button onclick="requestHumanAgent()" style="background: #3b82f6; color: white; padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;">👤 Talk to Human Agent</button>';
    
    helpDiv.innerHTML = buttons;
    chatBody.appendChild(helpDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Quick check ticket function
function quickCheckTicket(ticketNumber) {
    document.getElementById("helpOptions")?.remove();
    currentTicketNumber = ticketNumber;
    addMsg(`📋 Checking ticket: ${ticketNumber}`, "right");
    setTimeout(() => {
        addMsg(translations[currentLang].askTicketPhone, "left");
        showTicketPhoneInput(ticketNumber);
    }, 1000);
}

// START NEW TOPIC
function startNewTopic() {
    const helpOptions = document.getElementById("helpOptions");
    if (helpOptions) helpOptions.remove();
    
    addMsg("--- New Conversation Topic ---", "left");
    
    setTimeout(() => {
        const texts = translations[currentLang];
        addMsg(texts.chooseIssue, "left");
        showCategoryDropdowns();
    }, 500);
}

// ============= UI HELPER FUNCTIONS =============

// TYPING INDICATOR
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

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

// ADD MESSAGE
function addMsg(text, side) {
    const d = document.createElement("div");
    d.className = "msg " + side;
    d.innerHTML = text.replace(/\n/g, '<br>');
    
    if (languageOptions && languageOptions.parentNode === chatBody && languageOptions.style.display !== 'none') {
        chatBody.insertBefore(d, languageOptions.nextSibling);
    } else {
        chatBody.appendChild(d);
    }
    
    chatBody.scrollTop = chatBody.scrollHeight;
}

// ============= CHAT CONTROL FUNCTIONS =============

function minimizeChat() {
    chatBox.style.display = "none";
    chatMini.style.display = "flex";
    chatBubble.style.display = "none";
}

function restoreChat() {
    chatBox.style.display = "flex";
    chatMini.style.display = "none";
    chatBubble.style.display = "none";
}

function toggleMax() {
    chatBox.classList.toggle("maximized");
}

function closeChat() {
    chatBox.style.display = "none";
    chatMini.style.display = "none";
    chatBubble.style.display = "flex";
    
    if (staffMessageInterval) {
        clearInterval(staffMessageInterval);
    }
}

function openChat() {
    chatBox.style.display = "flex";
    chatBubble.style.display = "none";
    
    // Initialize chat if not already done
    if (!currentLang) {
        initializeChat();
    }
}

// BACKGROUND SLIDER
const slides = document.querySelectorAll(".bg");
let current = 0;
setInterval(() => {
    slides[current].classList.remove("active");
    current = (current + 1) % slides.length;
    slides[current].classList.add("active");
}, 5000);

// INITIALIZE - SET INITIAL STATE (BUBBLE VISIBLE, CHAT HIDDEN)
window.onload = function() {
    console.log("Window loaded - setting initial state");
    
    // Set initial display states
    chatBox.style.display = "none";
    chatMini.style.display = "none";
    chatBubble.style.display = "flex";
    
    // Don't initialize chat yet - wait for bubble click
    
    input.addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            sendText();
        }
    });
};







// GET AI RESPONSE with usage logging
async function getAIResponse(userMessage) {
    const startTime = Date.now();
    let source = 'unknown';
    let response = '';
    
    // First, check knowledge base
    const knowledge = await searchKnowledgeBase(userMessage, currentLang);
    
    if (knowledge) {
        // Found in knowledge base
        source = 'database';
        response = knowledge.answer;
        conversationHistory.push({ role: 'user', content: userMessage });
        conversationHistory.push({ role: 'assistant', content: response });
        await saveMessage('ai', response);
        
        // Log usage
        await logAIUsage(userMessage, response, source, Date.now() - startTime);
        
        return { text: response };
    }
    
    // If not found, use Gemini AI
    if (useLocalAI) {
        source = 'local';
        response = getLocalAIResponse(userMessage).text;
        
        // Log usage
        await logAIUsage(userMessage, response, source, Date.now() - startTime);
        
        return { text: response };
    }
    
    try {
        const systemPrompt = currentLang === 'tl' 
            ? `Ikaw ay isang customer support agent para sa Power2Connect...`
            : `You are a customer support agent for Power2Connect...`;
        
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
                generationConfig: { maxOutputTokens: 500, temperature: 0.7 }
            })
        });
        
        if (!response.ok) throw new Error(`API error: ${response.status}`);
        
        const data = await response.json();
        const aiText = data.candidates[0].content.parts[0].text;
        
        source = 'gemini';
        response = aiText;
        
        conversationHistory.push({ role: 'user', content: userMessage });
        conversationHistory.push({ role: 'assistant', content: aiText });
        await saveMessage('ai', aiText);
        
        // Log usage
        await logAIUsage(userMessage, response, source, Date.now() - startTime);
        
        return { text: aiText };
        
    } catch (error) {
        console.error('Gemini API failed:', error);
        useLocalAI = true;
        
        source = 'local';
        response = getLocalAIResponse(userMessage).text;
        
        // Log usage
        await logAIUsage(userMessage, response, source, Date.now() - startTime);
        
        return { text: response };
    }
}

// Function to log AI usage
async function logAIUsage(userMessage, aiResponse, source, responseTime) {
    try {
        await fetch('chat/log_ai_usage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: chatSessionId,
                user_message: userMessage,
                ai_response: aiResponse,
                source: source,
                response_time: responseTime
            })
        });
    } catch (error) {
        console.error('Error logging AI usage:', error);
    }
}