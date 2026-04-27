<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <title>MLRS</title>

        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
            .bg-hero {
                background-image: url("assets/hero_bg.jpg");
            }
        </style>
    </head>
    <body class="antialiased">
<?php include('components/header.php'); ?>
<!-- hero -->
<div class="hero min-h-screen bg-hero">
  <div class="hero-overlay"></div>
  <div class="hero-content text-neutral-content text-center">
    <div class="max-w-md">
      <h1 class="mb-5 text-5xl font-bold">Welcome to Mhel and Linda Rice Store</h1>
      <a href="products.php">
        <button class="btn btn-soft btn-success">View out Products</button>
      </a>
    </div>
  </div>
</div>

<div style="margin: 5%;">
  <div class="chat chat-start">
  <div class="chat-image avatar">
    <div class="w-10 rounded-full">
      <img
        alt="Tailwind CSS chat bubble component"
        src="https://cdn.pixabay.com/photo/2016/08/08/09/17/avatar-1577909_640.png"
      />
    </div>
  </div>
  <div class="chat-header">
    Maria Isable
    <time class="text-xs opacity-50">12:45</time>
  </div>
  <div class="chat-bubble">Ang sarap ng bigas nyo!</div>
  <div class="chat-footer opacity-50">seen</div>
</div>
<div class="chat chat-end">
  <div class="chat-image avatar">
    <div class="w-10 rounded-full">
      <img
        alt="Tailwind CSS chat bubble component"
        src="assets/logo.png"
      />
    </div>
  </div>
  <div class="chat-header">
    Mhel And Linda
    <time class="text-xs opacity-50">12:46</time>
  </div>
  <div class="chat-bubble">Syempre Naman</div>
  <div class="chat-footer opacity-50">Seen at 12:46</div>
</div>
</div>

<figure class="diff aspect-16/5" tabindex="0">
  <div class="diff-item-1" role="img" tabindex="0">
    <img alt="daisy" src="assets/rice.jpg" />
  </div>
  <div class="diff-item-2" role="img">
    <img
      alt="daisy"
      src="assets/rice.jpg" style="filter: blur(25px); " />
  </div>
  <div class="diff-resizer">12sf</div>
</figure>
<?php include('components/footer.php'); ?>
    </body>
</html>
