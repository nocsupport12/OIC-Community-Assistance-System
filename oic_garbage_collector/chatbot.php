<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OIC GARBAGE COLLECTION PAGE</title>
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

.bg-slider{
    opacity: 0.8;
}

/* FOOTER */
.site-footer{
    color: #03071E;
}

/* IMAGE STYLE */
.img-card{
    position:absolute;
    border-radius:30px;
    overflow:hidden;
    border:3px solid white;
    box-shadow:0 20px 40px rgba(0,0,0,0.4);
    transition:0.3s ease;
}

.img-card img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* LOGO MANIPULATION */
.logo{
    position: fixed;
    top: 5px;
    left: -20px;
    z-index: 1000;
    display: flex;
    align-items: center;
}

.upper-left-logo img{
    width: 150px;
    height: auto;
}

.upper-left-text{
    margin-left: -25px;
    margin-top: -10px;
    color: black;
    font-weight: 600;
    font-size: 22px;
    white-space: nowrap;
    left: 10px;
}

@media (max-width: 1200px){
    .upper-left-logo img{
        width: 120px;
    }

    .upper-left-text{
        font-size: 18px;
    }
}

@media (max-width: 768px){
    .upper-left-logo img{
        width: 100px;
    }

    .upper-left-text{
        font-size: 16px;
    }
}

/* INDIVIDUAL BOX SIZES */
.img1{ width:300px; height:320px; top:30px; left:140px; z-index:1; }
.img2{ width:230px; height:290px; top:150px; left:20px; z-index:1; }
.img3{ width:160px; height:160px; top:250px; left:260px; z-index:2; }
.img4{ width:230px; height:230px; top:280px; left:140px; z-index:1; }

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


/* ================= OTHER STYLES (unchanged) ================= */

/* CHATBOX, LOGO, FOOTER styles remain unchanged, assume in style.css */
</style>

</head>
<body>

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="assets/gc5.png" class="bg active">
</div>

<!-- LOGO -->
 
<!-- LOGO -->
<header class="logo">
    <div class="upper-left-logo"><img src="assets/logo.png"></div>
    <div class="upper-left-text">GARBAGE COLLECTION PAGE</div>
</header>


<!-- LEFT IMAGE COLLAGE -->
<div class="left-collage">
    <div class="img-card img1"><img src="assets/gc2.png"></div>
    <div class="img-card img2"><img src="assets/gc4.png"></div>
    <div class="img-card img3"><img src="assets/gc1.png"></div>
    <div class="img-card img4"><img src="assets/gc3.png"></div>
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
    <span>💬 Click2Connect</span>
</div>

<!-- CHAT BUBBLE -->
<div class="chat-bubble" id="chatBubble" onclick="openChat()">Click2Connect</div>

<!-- FOOTER -->
<footer class="site-footer">
    © 2026 One Intranet Corporation • All Rights Reserved 
</footer>

<script src="Customer_Ai_UI_Function.js"></script>

</body>
</html>