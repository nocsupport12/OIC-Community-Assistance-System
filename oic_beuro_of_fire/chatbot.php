<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OIC POLICE</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

<style>
/* ================= LEFT IMAGE COLLAGE ================= */
.left-collage{
    position:absolute;
    left:10%;
    top:10%;
    width:550px;
    height:500px;
}

/* IMAGE STYLE */
.img-card{
    position:absolute;
    border-radius:30px;
    overflow:hidden;
    border:6px solid white;
    box-shadow:0 20px 40px rgba(0,0,0,0.4);
    transition:0.3s ease;
}

.img-card img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* INDIVIDUAL BOX SIZES */
.img1{ width:270px; height:210px; top:0; left:140px; z-index:3; }
.img2{ width:250px; height:200px; top:180px; left:0; z-index:2; }
.img3{ width:280px; height:220px; top:180px; left:280px; z-index:2; }
.img4{ width:260px; height:230px; top:340px; left:140px; z-index:1; }

.img-card:hover{
    transform:scale(1.05);
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media (max-width: 1200px){
    .left-collage{
        left:5%;
        width:450px;
        height:420px;
    }

    .img1{ width:220px; height:170px; left:110px; }
    .img2{ width:200px; height:160px; top:150px; }
    .img3{ width:230px; height:180px; top:150px; left:230px; }
    .img4{ width:210px; height:190px; top:290px; left:110px; }
}

/* Mobile */
@media (max-width: 768px){
    .left-collage{
        position:relative;
        left:0;
        top:120px;
        width:100%;
        height:auto;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:20px;
    }

    .img-card{
        position:relative;
        width:90%;
        height:200px; /* fallback height */
    }

    .img1,
    .img2,
    .img3,
    .img4{
        top:0;
        left:0;
        width:90%; /* make them full width on mobile */
        height:200px;
    }
}

/* ================= OTHER STYLES (unchanged) ================= */

/* CHATBOX, LOGO, FOOTER styles remain unchanged, assume in style.css */
</style>

</head>
<body class=" bg-gradient-to-br from-red-200 via-red-100 to-red-50">

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="assets/fire.jpg" class="bg active">
</div>

<!-- LOGO -->
<header class="logo text">
    <strong>ONE INTRATNET COMMUNITY</strong><br>
    Beuro of Fire <br>
</header>

<!-- LEFT IMAGE COLLAGE -->
<div class="left-collage">
    <div class="img-card img1"><img src="assets/fire1.png"></div>
    <div class="img-card img2"><img src="assets/fire2.png"></div>
    <div class="img-card img3"><img src="assets/fire3.png"></div>
    <div class="img-card img4"><img src="assets/fire4.png"></div>
</div>

<!-- CHATBOX -->
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
    <span>Click2Connect</span>
</div>

<!-- CHAT BUBBLE -->
<div class="chat-bubble" id="chatBubble" onclick="openChat()">Click2Connect</div>

<script src="Customer_Ai_UI_Function.js"></script>

</body>
</html>