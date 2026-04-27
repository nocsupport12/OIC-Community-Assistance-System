<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Power2Connect-Home</title>
<link rel="icon" href="assets/onecircle.png" type="image/x-icon">
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="oic_accounting/assets/Bg 2.png" class="bg active">
    <img src="oic_accounting/assets/Bg 1.png" class="bg">
    <img src="oic_accounting/assets/Bg 3.png" class="bg">
    <img src="oic_accounting/assets/Bg 4.png" class="bg">
    <img src="assets/Bg 5.png" class="bg">
</div>

<!-- LOGO -->
<header class="logo">
    <strong>One Intanet</strong><span></span><br>Community
</header>

<div class="fab">
  <!-- a focusable div with tabindex is necessary to work on all browsers. role="button" is necessary for accessibility -->
  <div tabindex="0" role="button" class="btn btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full text-black"> Click to Connect </div>

  <!-- buttons that show up when FAB is open -->
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Police</button></div>
  <a href="oic_beuro_of_fire"><div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full transition-all duration-300 ease-in-out hover:bg-white hover:scale-110">Bureau of Fire</button></div></a>
  <a href="oic_health_center"><div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full transition-all duration-300 ease-in-out hover:bg-white hover:scale-110">Health Center</button></div></a>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Engineering</button></div>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Accounting</button></div>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Tax Collection</button></div>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Garbage Collector</button></div>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Office Of the Mayor</button></div>
  <div><button class="btn-lg !px-2 !py-2 bg-green-500 border-0 rounded-full">Community Assistace</button></div>
</div>

<script src="script.js"></script>
<script>
    // Disable image dragging
document.addEventListener("dragstart", function(e) {
    e.preventDefault();
});

// Disable right click
document.addEventListener("contextmenu", function(e) {
    e.preventDefault();
});

// Disable common inspect shortcuts
document.addEventListener("keydown", function(e) {

    // F12
    if (e.key === "F12") {
        e.preventDefault();
    }

    // Ctrl+Shift+I
    if (e.ctrlKey && e.shiftKey && e.key === "I") {
        e.preventDefault();
    }

    // Ctrl+Shift+J
    if (e.ctrlKey && e.shiftKey && e.key === "J") {
        e.preventDefault();
    }

    // Ctrl+U (view source)
    if (e.ctrlKey && e.key === "u") {
        e.preventDefault();
    }

    // Ctrl+Shift+C (inspect element)
    if (e.ctrlKey && e.shiftKey && e.key === "C") {
        e.preventDefault();
    }
});
</script>
</body>
</html>