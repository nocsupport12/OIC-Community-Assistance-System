<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Power2Connect</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="assets/carousel1.png" class="bg active">
    <img src="assets/carousel2.png" class="bg">
    <img src="assets/carousel3.png" class="bg">
    <img src="assets/carousel4.png" class="bg">
    <img src="assets/carousel7.png" class="bg">
    <img src="assets/carousel8.png" class="bg">
    <img src="assets/carousel9.png" class="bg">
    <img src="assets/carousel10.png" class="bg">
    <img src="assets/carousel11.png" class="bg">
    <img src="assets/carousel12.png" class="bg">
    <img src="assets/carousel13.png" class="bg">
    
</div>

<!-- LOGO -->
<header class="logo">
    <strong>POWER</strong><span><img src="assets/logo1.png" class="logo-img"></span><br>CONNECT
</header>

<!-- CHATBOX -->
<div class="chat-glass" id="chatBox">
    <div class="chat-header">
        <button class="win-btn minimize-btn" onclick="minimizeChat()"><img src="assets/Minimize.png" style="margin-top: -10px;"></button>
        <button class="win-btn maximize-btn" onclick="toggleMax()"><img src="assets/Maximize.png"></button>
        <button class="win-btn close" onclick="closeChat()"><img src="assets/Exit.png"></button>
    </div>

    <div class="chat-body" id="chatBody">
        <!-- LANGUAGE OPTIONS -->
        <div class="options" id="languageOptions">
            <button onclick="setLanguage('en')">🇺🇸 English</button>
            <button onclick="setLanguage('tl')">🇵🇭 Tagalog</button>
        </div>
        
        <!-- CHAT MESSAGES WILL APPEAR HERE -->
    </div>

    <div class="chat-input">
        <input id="userInput" placeholder="Type your message..." disabled>
        <button class="send-btn" onclick="sendText()" id="sendBtn" disabled>➤</button>
    </div>
</div>

<!-- MINIMIZE BAR -->
<div class="chat-mini" id="chatMini" onclick="restoreChat()">
    <span>💬 Click2Connect</span>
</div>

<!-- CHAT BUBBLE FOR OPENING -->
<div class="chat-bubble" id="chatBubble" onclick="openChat()">Power2Connect</div>

<!-- FOOTER -->
<footer class="site-footer">
    © 2026 Power2Connect - All Rights Reserved •
    <a href="about.php" target="_about.php">ABOUT</a>
</footer>

<script src="Customer_Ai_UI_Function.js"></script>
</body>
</html>