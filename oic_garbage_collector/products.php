<?php
include("components/db.php");
date_default_timezone_set('Asia/Manila');

// Fetch all products
$query = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <title>MLRS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Prevent layout shift on hover */
    .product-card {
      transition: all 0.3s ease;
      transform: translateY(0);
    }
    .product-card:hover {
      transform: translateY(-5px) scale(1.02);
    }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 min-h-screen">
<?php include('components/header.php'); ?>
  <main class="px-4 sm:px-6 md:px-8 lg:px-10 xl:px-16 py-8">
    <div class="text-center mb-10">
      <h1 class="text-3xl md:text-4xl font-bold text-green-700">Available Products</h1>
      <p class="text-gray-500 text-sm md:text-base mt-1">Browse our latest stocks and prices</p>
    </div>

    <!-- Product Grid -->
    <div class="grid gap-5 sm:gap-6 md:gap-8 
                grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 
                justify-items-center">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="product-card group bg-white rounded-xl shadow-md w-full max-w-[270px] 
                      overflow-hidden border border-gray-100 
                      hover:shadow-xl transition-all duration-300">
            <div class="overflow-hidden">
              <img 
                src="<?= htmlspecialchars($row['image_path']) ?>" 
                alt="<?= htmlspecialchars($row['product_name']) ?>" 
                class="w-full h-44 object-cover transition-transform duration-500 group-hover:scale-110"
              />
            </div>
            <div class="p-4">
              <div class="flex justify-between items-center mb-1">
                <h2 class="text-base md:text-lg font-semibold text-gray-800 truncate group-hover:text-green-700">
                  <?= htmlspecialchars($row['product_name']) ?>
                </h2>
                <span class="text-green-700 font-medium text-sm">₱<?= number_format($row['price'], 2) ?></span>
              </div>
              <p class="text-sm text-gray-600 mb-1">Stock: <span class="font-medium"><?= (int)$row['stock'] ?></span></p>
              <p class="text-xs text-gray-400">Added: <?= date('M d, Y', strtotime($row['created_at'])) ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-gray-500 text-center col-span-full">No products available.</p>
      <?php endif; ?>
    </div>
  </main>
<?php include('components/footer.php'); ?>
</body>
</html>
