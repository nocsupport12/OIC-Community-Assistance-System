<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OIC HEALTH CENTER</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        /*chatbot.php*/
        /* ================= LEFT IMAGE COLLAGE ================= */
        .left-collage {
            position: absolute;
            left: 1%;
            top: 5%;
            width: 550px;
            height: 450px;
        }

        /* IMAGE STYLE */
        .img-card {
            position: absolute;
            border-radius: 30px;
            overflow: hidden;
            border: 6px solid white;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            transition: 0.3s ease;
        }

        .img-card img {
            width: 100%;
            height: 100%;
         
        }

        /* ===== NEW: FORMS BUTTON + DROPDOWN (simple, matches chat style) ===== */
        .forms-dropdown-container {
            position: relative;
            display: inline-block;
            width: 100%;
            margin: 12px 0 8px 0;

        }

        .forms-toggle-btn {
            background: #ffffff;
            border: none;
            color: black;
            padding: 10px 16px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: 0.2s;
        }

        .forms-toggle-btn:hover {
            background: #f7a714;
        }

        .forms-toggle-btn .arrow {
            font-size: 1.2rem;
        }

        .forms-menu {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            margin-top: 6px;
            padding: 12px 8px;
            list-style: none;
            border: 1px solid #3f556b;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .forms-menu li {
            margin: 6px 0;
        }

        .forms-menu a {
            color: #000000;
            text-decoration: none;
            display: block;
            padding: 10px 16px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.06);
            font-weight: 500;
            transition: 0.2s;
            border-left: 4px solid transparent;
        }

        .forms-menu a:hover {
            background: rgba(236, 144, 5, 0.97);
            border-left: 4px solid #ffb347;
            color: black;
        }

        /* show menu when toggled */
        .forms-menu.show {
            display: block;
        }

        /* INDIVIDUAL BOX SIZES */
        .img1 {
            width: 270px;
            height: 210px;
            top: 0;
            left: 140px;
            z-index: 3;
        }

        .img2 {
            width: 250px;
            height: 200px;
            top: 180px;
            left: 0;
            z-index: 2;
        }

        .img3 {
            width: 280px;
            height: 220px;
            top: 180px;
            left: 280px;
            z-index: 2;
        }

        .img4 {
            width: 260px;
            height: 230px;
            top: 340px;
            left: 140px;
            z-index: 1;
        }

        /* ================= RESPONSIVE ================= */

        /* Tablet */
        @media (max-width: 1200px) {
            .left-collage {
                left: 5%;
                width: 450px;
                height: 420px;
            }

            .img1 {
                width: 220px;
                height: 170px;
                left: 110px;
            }

            .img2 {
                width: 200px;
                height: 160px;
                top: 150px;
            }

            .img3 {
                width: 230px;
                height: 180px;
                top: 150px;
                left: 230px;
            }

            .img4 {
                width: 210px;
                height: 190px;
                top: 290px;
                left: 110px;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .left-collage {
                position: relative;
                left: 0;
                top: 120px;
                width: 100%;
                height: auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }

            .img-card {
                position: relative;
                width: 90%;
                height: 200px;
                /* fallback height */
            }

            .img1,
            .img2,
            .img3,
            .img4 {
                top: 0;
                left: 0;
                width: 90%;
                /* make them full width on mobile */
                height: 200px;
            }
        }

        body {
            margin: 0;
            /* remove default body margin */
            height: 100vh;
            /* make body full height */
            background-image: url("assets/image.png") ;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            /* keeps gradient fixed on scroll */
        }

        .logo {
            position: fixed;
            top: 5px;
            left: -20px;
            z-index: 1000;
            display: flex;
            align-items: center;
        }

        .upper-left-logo img {
            width: 150px;
            height: auto;
        }

        .upper-left-text {
            margin-left: -25px;
            margin-top: -10px;
            color: white;
            font-weight: 600;
            font-size: 22px;
            white-space: nowrap;
            left: 10px;
        }

        @media (max-width: 1200px) {
            .upper-left-logo img {
                width: 120px;
            }

            .upper-left-text {
                font-size: 18px;
            }
        }

        @media (max-width: 768px) {
            .upper-left-logo img {
                width: 100px;
            }

            .upper-left-text {
                font-size: 16px;
            }
        }

        a{
            text-decoration: none;
        }


        /* ================= EXTRA RESPONSIVE FIX ================= */

/* Make chatbox responsive */
@media (max-width: 768px) {

    /* Fix logo */
    .logo {
        position: relative;
        left: 0;
        justify-content: center;
        margin-top: 10px;
    }

    .upper-left-text {
        margin-left: 0;
        font-size: 16px;
        text-align: center;
    }

    /* Fix background scaling */
    body {
        background-position: center;
    }

    /* Fix left collage doctor image */
    .left-collage {
        position: relative !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        margin-top: 30px;
    }

    .img-card.img1 {
        width: 90% !important;
        height: auto !important;
        margin: 0 auto !important;
    }

    .img-card img {
        width: 100% !important;
        height: auto !important;
        object-fit: contain !important;
    }

    /* Chat container full width */
    .chat-glass {
        width: 95% !important;
        right: 2.5% !important;
        left: 2.5% !important;
        bottom: 10px !important;
    }

    /* Footer center */
    .site-footer {
        text-align: center;
        padding: 10px;
        font-size: 14px;
    }
}




    </style>

</head>

<body >

    <!-- LOGO -->
    <header class="logo">
        <div class="upper-left-logo"><img src="assets/logo.png" alt="OIC logo"></div>
        <div class="upper-left-text"><a href="../index.php">I'm Your Online Health Care Assistance</a></div>
    </header>

    <!-- LEFT IMAGE COLLAGE -->
    <div class="left-collage">
        <div class="img-card img1" style="height: 120%; background-size:cover; margin-top: 5%; border: none; box-shadow: none; width:100%;"><img
                src="assets/doc_smile.png"></div>
    </div>

    <!-- CHATBOX (with integrated forms button & hidden link list) -->
    <div class="chat-glass" id="chatBox">
        <div class="chat-header">
            <button class="win-btn minimize-btn" onclick="minimizeChat()">
                <img src="assets/Minimize.png" style="margin-top: -10px;" alt="min">
            </button>
            <button class="win-btn maximize-btn" onclick="toggleMax()">
                <img src="assets/Maximize.png" alt="max">
            </button>
            <button class="win-btn close" onclick="closeChat()">
                <img src="assets/Exit.png" alt="close">
            </button>
        </div>

        <div class="chat-body" id="chatBody">
            <!-- language options (original) -->
             <p style="font-weight:bolder; text-align:center; font-size:20px; background:white; border-radius:20px;">Pumili ng iyong nais na Wika</p>
            <div class="options" id="languageOptions">
                <button onclick="setLanguage('en')"><p style="font-weight:bolder;">🇺🇸 ENGLISH</p></button>
                <button onclick="setLanguage('tl')"><p style="font-weight:bolder;">🇵🇭 TAGALOG</p></button>
            </div>

            <!-- ===== NEW FORMS DROPDOWN BUTTON (replaces old static link) ===== -->
            <!-- <div class="forms-dropdown-container">
                <button class="forms-toggle-btn" id="formsToggleBtn" onclick="toggleFormsMenu(event)">
                    <span>📋 Health Care Forms</span>
                    <span class="arrow">▼</span>
                </button>
                <ul class="forms-menu" id="formsMenu">
                    <li><a href="forms/health.html" target="_blank">Health Care Form</a></li>
                </ul>
            </div> -->

            <!-- ===== NEW FOLLOW UP DROPDOWN BUTTON ===== -->
            <!-- <div class="forms-dropdown-container">
                <button class="forms-toggle-btn" id="followUpToggleBtn" onclick="toggleFollowUpMenu(event)">
                    <span>📋 Follow Up</span>
                    <span class="arrow">▼</span>
                </button>
                <ul class="forms-menu" id="followUpMenu">
                    <li><a href="forms/ticket.html" target="_blank">Follow Up Form</a></li>
                </ul>
            </div> -->

        </div>

        <!-- chat input (disabled as per original) -->
        <div class="chat-input">
            <input id="userInput" placeholder="Type your message..." disabled>
            <button class="send-btn" onclick="sendText()" id="sendBtn" disabled>➤</button>
        </div>
    </div>

    <!-- MINIMIZE BAR -->
    <div class="chat-mini" id="chatMini" onclick="restoreChat()">
        <span>Click2Connect</span>
    </div>

    <!-- CHAT BUBBLE -->
    <div class="chat-bubble" id="chatBubble" onclick="openChat()">Click2Connect</div>

    <!-- FOOTER -->
    <footer class="site-footer" style="color: black;">
        © 2026 One Intranet Corporation • All Rights Reserved
    </footer>

    <!-- JavaScript (original functions + new dropdown toggle) -->
    <script>
        // ---------------------- original chat functions (stubs, link to external) -----------------
        // Assuming Customer_Ai_UI_Function.js defines: minimizeChat, toggleMax, closeChat,
        // setLanguage, sendText, openChat, restoreChat, etc.
        // We'll also provide fallback definitions to avoid errors, but preserve external calls.

        function minimizeChat() { if (window.minimizeChat) window.minimizeChat(); else console.log('minimize'); }
        function toggleMax() { if (window.toggleMax) window.toggleMax(); else console.log('max'); }
        function closeChat() { if (window.closeChat) window.closeChat(); else console.log('close'); }
        function setLanguage(lang) { if (window.setLanguage) window.setLanguage(lang); else alert('Language: ' + lang); }
        function sendText() { if (window.sendText) window.sendText(); else console.log('send'); }
        function openChat() {
            if (window.openChat) window.openChat(); else {
                // simple fallback to show chat
                document.getElementById('chatBox').style.display = 'block';
                document.getElementById('chatMini').style.display = 'none';
                document.getElementById('chatBubble').style.display = 'none';
            }
        }
        function restoreChat() {
            if (window.restoreChat) window.restoreChat(); else {
                document.getElementById('chatBox').style.display = 'block';
                document.getElementById('chatMini').style.display = 'none';
                document.getElementById('chatBubble').style.display = 'none';
            }
        }

        // ---------------------- NEW: toggle forms menu (click button shows/hides hyperlinks) -----
        function toggleFormsMenu(event) {
            event.stopPropagation();  // prevent accidental closure from header
            const menu = document.getElementById('formsMenu');
            menu.classList.toggle('show');

            // optional: rotate arrow
            const arrow = document.querySelector('#formsToggleBtn .arrow');
            if (menu.classList.contains('show')) {
                arrow.innerHTML = '▲';
            } else {
                arrow.innerHTML = '▼';
            }
        }

        // ---------------------- NEW: toggle follow up menu -----
        function toggleFollowUpMenu(event) {
            event.stopPropagation();  // prevent accidental closure from header
            const menu = document.getElementById('followUpMenu');
            menu.classList.toggle('show');

            // optional: rotate arrow
            const arrow = document.querySelector('#followUpToggleBtn .arrow');
            if (menu.classList.contains('show')) {
                arrow.innerHTML = '▲';
            } else {
                arrow.innerHTML = '▼';
            }
        }

        // optional: click outside to close forms menu? (nice UX)
        document.addEventListener('click', function (event) {
            // Close forms menu if clicked outside
            const formsContainer = document.querySelector('.forms-dropdown-container:first-of-type');
            const formsMenu = document.getElementById('formsMenu');
            if (formsContainer && !formsContainer.contains(event.target) && formsMenu.classList.contains('show')) {
                formsMenu.classList.remove('show');
                const arrow = document.querySelector('#formsToggleBtn .arrow');
                if (arrow) arrow.innerHTML = '▼';
            }
            
            // Close follow up menu if clicked outside
            const followUpContainer = document.querySelector('.forms-dropdown-container:last-of-type');
            const followUpMenu = document.getElementById('followUpMenu');
            if (followUpContainer && !followUpContainer.contains(event.target) && followUpMenu.classList.contains('show')) {
                followUpMenu.classList.remove('show');
                const arrow = document.querySelector('#followUpToggleBtn .arrow');
                if (arrow) arrow.innerHTML = '▼';
            }
        });

        // Also handle the case where the chat might be closed/reopened — keep menu intact
        // no extra init needed

        // For demonstration: if external script loads, it may override these functions; fine.
        // Ensure chat visibility (just a default state – chat visible as in original)
        window.onload = function () {
            // if you want chat visible initially (as original). Usually chatBox visible, mini hidden, bubble hidden?
            // from original code it seems chatBox is visible by default? we keep as is.
            // But we also sync with possible external.
            var chatBox = document.getElementById('chatBox');
            var mini = document.getElementById('chatMini');
            var bubble = document.getElementById('chatBubble');
            if (chatBox && mini && bubble) {
                // default visible (like original – i think chatBox visible, bubble hidden)
                // but your original may have bubble visible. We'll follow typical: chatBox visible, others hidden? 
                // Actually looking at your HTML: chatBox, chatMini, chatBubble all present. No initial style hide.
                // But external JS might set them. We'll set a sensible default: only chatBox visible.
                chatBox.style.display = 'block';
                mini.style.display = 'none';
                bubble.style.display = 'none';
            }
            // arrow default down (already)
        };
    </script>

    <!-- reference to external script (unchanged) -->
    <script src="Customer_Ai_UI_Function.js"></script>
</body>

</html>