
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Power2Connect-Home</title>
<link rel="icon" href="assets/onecircle.png" type="image/x-icon">
<link rel="stylesheet" href="style.css">
<style>  
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
    margin-left: -5px;
    margin-top: 10px;
}

.upper-left-text{
    position: absolute;
    margin-left: 280px;
    margin-top: -80px;
    color: #246ddb;
    font-weight: 600;
    font-size: 70px;
    white-space: nowrap;
    left: 10px;
}

@media (max-width: 1200px){
    .upper-left-logo img{
        width: 150px;
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

/* RESET & BASE */
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
    font-family: 'Montserrat', sans-serif; 
}

/* BACKGROUND SLIDER */
.bg-slider { 
    position: fixed; 
    inset: 0; 
    z-index: -1; 
}

.bg { 
    position: absolute; 
    inset: 0; 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    opacity: 0; 
    transition: opacity 3s ease-in-out; 
}

.bg.active { 
    opacity: 1; 
}

</style>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="relative h-screen">

<!-- BACKGROUND CAROUSEL -->
<div class="bg-slider">
    <img src="carousel/landing (1).jpg" class="bg active">
    <img src="carousel/landing (2).jpg" class="bg">
    <img src="carousel/landing (3).jpg" class="bg">
    <img src="carousel/landing (4).jpg" class="bg">
    <img src="carousel/landing (5).jpg" class="bg">
</div>

<!-- LOGO -->
<header class="logo">
    <!-- <div class="upper-left-logo"><img src="oic_accounting/assets/logo.png"></div> -->
</header>

<div class="h-screen grid place-items-center">
    <div class="upper-left-text"><h1>One Intranet Community</h1></div>
  <!-- Button to open modal -->
   <div style="margin-top: 40px;">
  <button 
    class="px-1 py-3 text-lg font-semibold bg-[#007200] hover:bg-[#008000] rounded-lg transition-all duration-300 text-white"
    onclick="my_modal_3.showModal()"
  >
    Click to Connect
  </button>
  </div>

  <!-- Modal -->
<dialog id="my_modal_3" class="modal">
  <div class="modal-box relative px-4 py-3 bg-[#007200] text-white w-96 opacity-80">
    <!-- Close button -->
    <form method="dialog">
      <button class="btn btn-sm btn-ghost absolute right-4 top-2">✕</button>
    </form>

    <!-- Modal title -->
    <h3 class="text-lg font-bold mb-4 text-center">Department</h3>

    <!-- Grid layout: 2 columns, auto rows -->
    <div class="grid grid-cols-2 gap-3">
      <!-- Row 1 -->
      <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
       <a href="oic_police/"> <p>Police</p></a>
      </div>
      <!-- <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
        <p>Accounting</p>
      </div> -->

      <!-- Row 2 -->
      <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
       <a href="oic_health_center/"><p>Health Center</p></a>
      </div>
      <!-- <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
        <p>Garbage Collector</p>
      </div> -->

      <!-- Row 3 -->
      <!-- <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
         <a href="oic_engineering/"><p>Engineering</p></a>
      </div> -->
            <!-- <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
        <p>Bureau of Fire</p>
      </div> -->

      <!-- Row 4 -->
      <!-- <div class="bg-[#008000] p-4 rounded-lg text-center hover:bg-green-600 cursor-pointer border-2 border-white">
        <p>Community Assistance</p>
      </div> -->
    </div>
  </div>
</dialog>
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

