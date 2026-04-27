<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OIC POLICE</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

</head>
<body>

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="assets/carousel1.png" class="bg active">
</div>

<!-- LOGO -->
<header class="logo">
    <strong>ONE INTRATNET COMMUNITY</strong>
    <span><img src="assets/logo1.png" class="logo-img"></span><br>
    POLICE DEPARTMENT
</header>

<!-- LEFT IMAGE COLLAGE USING TAILWIND -->
<div class="relative lg:absolute lg:left-1/10 lg:top-10 w-full max-w-[550px] lg:h-[500px] flex flex-col lg:flex-none mx-auto lg:mx-0">

  <!-- Image 1 -->
  <div class="absolute lg:top-0 lg:left-[140px] z-30 rounded-2xl overflow-hidden border-6 border-white shadow-2xl transform transition-transform duration-300 hover:scale-105 w-[270px] h-[210px]">
    <img src="assets/Bg 1.png" class="w-full h-full object-cover rounded-2xl">
  </div>

  <!-- Image 2 -->
  <div class="absolute lg:top-[180px] lg:left-0 z-20 rounded-2xl overflow-hidden border-6 border-white shadow-2xl transform transition-transform duration-300 hover:scale-105 w-[270px] h-[210px]">
    <img src="assets/Bg 2.png" class="w-full h-full object-cover rounded-2xl">
  </div>

  <!-- Image 3 -->
  <div class="absolute lg:top-[180px] lg:left-[280px] z-20 rounded-2xl overflow-hidden border-6 border-white shadow-2xl transform transition-transform duration-300 hover:scale-105 w-[270px] h-[210px]">
    <img src="assets/Bg 3.png" class="w-full h-full object-cover rounded-2xl">
  </div>

  <!-- Image 4 -->
  <div class="absolute lg:top-[340px] lg:left-[140px] z-10 rounded-2xl overflow-hidden border-6 border-white shadow-2xl transform transition-transform duration-300 hover:scale-105 w-[270px] h-[210px]">
    <img src="assets/Bg 4.png" class="w-full h-full object-cover rounded-2xl">
  </div>

</div>

<!-- CHATBOX (UNCHANGED) -->
<div class="chat-glass" id="chatBox">
    <div class="chat-header">
        <button class="win-btn minimize-btn" onclick="minimizeChat()">
            <img src="assets/Minimize.png" style="margin-top: -10px;">
        </button>
        <button class="win-btn maximize-btn" onclick="toggleMax()">
            <img src="assets/Maximize.png">
        </button>
        <button class="win-btn close" onclick="closeChat()">
            <img src="assets/Exit.png">
        </button>
    </div>

    <div class="chat-body" id="chatBody">
        <div class="options" id="languageOptions">
            <button onclick="setLanguage('en')">🇺🇸 English</button>
            <button onclick="setLanguage('tl')">🇵🇭 Tagalog</button>
        </div>
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

<!-- CHAT BUBBLE -->
<div class="chat-bubble" id="chatBubble" onclick="openChat()">Click2Connect</div>

<!-- FOOTER -->
<footer class="site-footer">
    © 2026 Power2Connect - All Rights Reserved •
    <a href="about.php" target="_about.php">ABOUT</a>
</footer>

<script src="Customer_Ai_UI_Function.js"></script>

</body>
</html>