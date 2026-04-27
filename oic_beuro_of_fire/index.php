<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Documentation</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<style>
  /* Letter animation */
  .animated-title {
    display: inline-block;
    font-family: 'Pacifico', cursive;
    color: #22c55e;
    white-space: nowrap;
  }

  .animated-title span {
    display: inline-block;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.6s forwards;
  }

  /* Stagger animation for Welcome To */
  .animated-title.welcome span:nth-child(1) { animation-delay: 0s; }
  .animated-title.welcome span:nth-child(2) { animation-delay: 0.1s; }
  .animated-title.welcome span:nth-child(3) { animation-delay: 0.2s; }
  .animated-title.welcome span:nth-child(4) { animation-delay: 0.3s; }
  .animated-title.welcome span:nth-child(5) { animation-delay: 0.4s; }
  .animated-title.welcome span:nth-child(6) { animation-delay: 0.5s; }
  .animated-title.welcome span:nth-child(7) { animation-delay: 0.6s; }
  .animated-title.welcome span:nth-child(8) { animation-delay: 0.7s; }
  .animated-title.welcome span:nth-child(9) { animation-delay: 0.8s; }
  .animated-title.welcome span:nth-child(10){ animation-delay: 0.9s; }

  /* Stagger animation for One Intranet Community */
  .animated-title.power span:nth-child(1)  { animation-delay: 1.2s; }
  .animated-title.power span:nth-child(2)  { animation-delay: 1.3s; }
  .animated-title.power span:nth-child(3)  { animation-delay: 1.4s; }
  .animated-title.power span:nth-child(4)  { animation-delay: 1.5s; }
  .animated-title.power span:nth-child(5)  { animation-delay: 1.6s; }
  .animated-title.power span:nth-child(6)  { animation-delay: 1.7s; }
  .animated-title.power span:nth-child(7)  { animation-delay: 1.8s; }
  .animated-title.power span:nth-child(8)  { animation-delay: 1.9s; }
  .animated-title.power span:nth-child(9)  { animation-delay: 2.0s; }
  .animated-title.power span:nth-child(10) { animation-delay: 2.1s; }
  .animated-title.power span:nth-child(11) { animation-delay: 2.2s; }
  .animated-title.power span:nth-child(12) { animation-delay: 2.3s; }
  .animated-title.power span:nth-child(13) { animation-delay: 2.4s; }
  .animated-title.power span:nth-child(14) { animation-delay: 2.5s; }
  .animated-title.power span:nth-child(15) { animation-delay: 2.6s; }
  .animated-title.power span:nth-child(16) { animation-delay: 2.7s; }
  .animated-title.power span:nth-child(17) { animation-delay: 2.8s; }
  .animated-title.power span:nth-child(18) { animation-delay: 2.9s; }
  .animated-title.power span:nth-child(19) { animation-delay: 3.0s; }
  .animated-title.power span:nth-child(20) { animation-delay: 3.1s; }
  .animated-title.power span:nth-child(21) { animation-delay: 3.2s; }
  .animated-title.power span:nth-child(22) { animation-delay: 3.3s; }

  /* Stagger animation for Health Center */
  .animated-title.health span:nth-child(1)  { animation-delay: 3.6s; }
  .animated-title.health span:nth-child(2)  { animation-delay: 3.7s; }
  .animated-title.health span:nth-child(3)  { animation-delay: 3.8s; }
  .animated-title.health span:nth-child(4)  { animation-delay: 3.9s; }
  .animated-title.health span:nth-child(5)  { animation-delay: 4.0s; }
  .animated-title.health span:nth-child(6)  { animation-delay: 4.1s; }
  .animated-title.health span:nth-child(7)  { animation-delay: 4.2s; }
  .animated-title.health span:nth-child(8)  { animation-delay: 4.3s; }
  .animated-title.health span:nth-child(9)  { animation-delay: 4.4s; }
  .animated-title.health span:nth-child(10) { animation-delay: 4.5s; }
  .animated-title.health span:nth-child(11) { animation-delay: 4.6s; }
  .animated-title.health span:nth-child(12) { animation-delay: 4.7s; }
  .animated-title.health span:nth-child(13) { animation-delay: 4.8s; }

  @keyframes fadeInUp {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
  }

  /* Responsive font sizes */
  @media (min-width: 320px) { .animated-title { font-size: 2rem; } }
  @media (min-width: 640px) { .animated-title { font-size: 3rem; } }
  @media (min-width: 768px) { .animated-title { font-size: 3.5rem; } }
  @media (min-width: 1024px) { .animated-title { font-size: 4rem; } }
</style>
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center">

  <div class="text-center max-w-full px-4">

    <!-- Welcome To -->
    <h1 class="animated-title welcome mb-4">
      <span>W</span><span>e</span><span>l</span><span>c</span><span>o</span><span>m</span><span>e</span>
      <span>&nbsp;</span>
      <span>T</span><span>o</span>
    </h1>
    <br>
    <!-- One Intranet Community -->
    <h1 class="animated-title power mb-4">
      <span>O</span><span>n</span><span>e</span><span>&nbsp;</span>
      <span>I</span><span>n</span><span>t</span><span>r</span><span>a</span><span>n</span><span>e</span><span>t</span><span>&nbsp;</span>
      <span>C</span><span>o</span><span>m</span><span>m</span><span>u</span><span>n</span><span>i</span><span>t</span><span>y</span>
    </h1>
    <br>
    <!-- Health Center -->
    <h1 class="animated-title health">
      <span>B</span><span>e</span><span>u</span><span>r</span><span>o</span>
      <span>&nbsp;</span> <span>o</span><span>f</span>   <span>&nbsp;</span>
      <span>F</span><span>i</span><span>r</span><span>e</span>
    </h1>

  </div>
<script>
  setTimeout(() => {
    window.location.href = "chatbot.php"; // Change to your target page
  }, 7000); // 6000 milliseconds = 6 seconds
</script>
</body>
</html>