<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Eco Chat UI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen w-full bg-gradient-to-br from-sky-400 to-green-300 flex items-center justify-center">

  <div class="w-full h-full flex items-center justify-between px-16">

    <!-- LEFT SIDE (Image Collage) -->
    <div class="relative w-[450px] h-[450px]">

      <div class="absolute top-0 left-16 w-72 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
        <img src="Bg 1.png" class="w-full h-full object-cover">
      </div>

      <div class="absolute top-40 left-0 w-64 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
        <img src="https://via.placeholder.com/300x300" class="w-full h-full object-cover">
      </div>

      <div class="absolute top-60 left-40 w-72 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
        <img src="https://via.placeholder.com/400x300" class="w-full h-full object-cover">
      </div>

    </div>

    <!-- RIGHT SIDE (Chat Window) -->
    <div class="w-[380px] h-[600px] bg-white/20 backdrop-blur-xl rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-white/30">

      <!-- Top Bar -->
      <div class="h-10 bg-green-500 flex items-center justify-between px-4">
        <div class="flex gap-2">
          <div class="w-3 h-3 bg-black rounded-full"></div>
          <div class="w-3 h-3 bg-black rounded-full"></div>
        </div>
        <div class="font-semibold text-white">-  □  ×</div>
      </div>

      <!-- Chat Messages -->
      <div class="flex-1 p-4 space-y-4 flex flex-col justify-end">

        <div class="bg-white text-gray-700 px-4 py-2 rounded-xl w-fit max-w-[70%] shadow">
          Huh?
        </div>

        <div class="bg-white text-gray-700 px-4 py-2 rounded-xl w-fit max-w-[70%] shadow">
          Hello.
        </div>

        <div class="self-end bg-green-500 text-white px-4 py-2 rounded-xl w-fit max-w-[70%] shadow">
          Hehe.
        </div>

        <div class="self-end bg-green-500 text-white px-4 py-2 rounded-xl w-fit max-w-[70%] shadow">
          Hi po.
        </div>

      </div>

      <!-- Input Area -->
      <div class="p-3 bg-white/30 backdrop-blur-md flex items-center gap-2">
        <input 
          type="text" 
          placeholder="Type a message..." 
          class="flex-1 px-4 py-2 rounded-full outline-none bg-white/70"
        >
        <button class="bg-green-500 p-3 rounded-full text-white hover:bg-green-600 transition">
          ➤
        </button>
      </div>

    </div>

  </div>

</body>
</html>