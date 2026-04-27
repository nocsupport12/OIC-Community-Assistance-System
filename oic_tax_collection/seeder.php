<?php
session_start();
include("components/db.php");

// Fetch all products first
$products_res = $conn->query("SELECT id, product_name, price, stock FROM products");
$products = [];
while($p = $products_res->fetch_assoc()) {
    $products[] = $p;
}

if(empty($products)) {
    die("No products found. Add products first.");
}

// Dummy data generators
$names = ["Juan Dela Cruz","Maria Clara","Pedro Santos","Anna Reyes","Mark Lim","Jane Cruz","John Smith","Alice Johnson"];
$contacts = ["09171234567","09179876543","09170123456","09171239876","09171234568","09171234569"];
$payment_methods = ["Cash","GCash"];

// Helper function to generate random date in past 1 year
function randomDatePastYear() {
    $start = strtotime("-1 year");
    $end = time();
    return date("Y-m-d H:i:s", rand($start, $end));
}

// Loop to create 300 dummy orders
for($i=0; $i<300; $i++){
    $customer_name = $names[array_rand($names)];
    $contact_number = $contacts[array_rand($contacts)];
    $payment_method = $payment_methods[array_rand($payment_methods)];

    // Pick 1-3 random products per order
    $num_items = rand(1,3);
    $selected_products = [];
    for($j=0; $j<$num_items; $j++){
        $selected_products[] = $products[array_rand($products)];
    }

    $cash_given = null;
    $change_amount = null;
    $gcash_proof = null;
    $grand_total = 0;

    // Random order date within last year
    $order_date = randomDatePastYear();

    foreach($selected_products as $prod){
        $quantity = rand(1,5);
        $total = $prod['price'] * $quantity;
        $grand_total += $total;

        if($payment_method === "Cash"){
            $cash_given = $grand_total + rand(0,100); // enough cash
            $change_amount = $cash_given - $grand_total;
        } else {
            $gcash_proof = "dummy_proof_".rand(1,1000).".jpg";
        }

        // Insert order with custom order_date
        $stmt = $conn->prepare("INSERT INTO orders 
            (product_id, customer_name, contact_number, quantity, total_amount, payment_method, gcash_proof, cash_given, change_amount, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issidssdss",
            $prod['id'],
            $customer_name,
            $contact_number,
            $quantity,
            $total,
            $payment_method,
            $gcash_proof,
            $cash_given,
            $change_amount,
            $order_date
        );
        $stmt->execute();

        // Deduct stock (optional)
        $new_stock = max(0, $prod['stock'] - $quantity);
        $conn->query("UPDATE products SET stock = $new_stock WHERE id = {$prod['id']}");
    }
}

echo "300 dummy orders generated with random dates in the past year!";
?>
