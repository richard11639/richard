<?php
require_once 'db.php';

$existing = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
if ($existing > 0) {
    echo "Products already exist ({$existing}). Remove them before reseeding if needed.";
    exit;
}

$productNames = [
 'Fried Rice & Chicken','Jollof Rice & Fish','Pounded Yam & Egusi','Shawarma','Burger & Fries',
 'Spaghetti Bolognese','Seafood Platter','Grilled Fish','Vegetable Salad','Club Sandwich',
 'Chicken Wings','Beef Steak','Pancake Stack','Omelette Deluxe','Chicken Wrap',
 'Noodle Bowl','Sushi Pack','Taco Box','Pizza Margherita','BBQ Ribs',
 'Lamb Chops','Beef Stew','Vegetable Curry','Fruit Platter','Dessert Combo',
 'Ice Cream 3 scoops','Smoothie Pack','Coffee & Cake','Lunch Box A','Lunch Box B',
 'Party Pack 5','Family Pack 8','Mini Buffet','Corporate Lunch','Kids Meal',
 'Breakfast for Two','Romantic Dinner','Seafood Platter Deluxe','Chef Special','Street Food Box',
 'Rice & Stew','Plantain Meal','Beans & Dodo','Roast Chicken','Prawn Special',
 'Crispy Fish','Mushroom Risotto','Creamy Pasta','Garlic Bread','House Salad',
 'Savoury Pie','Wrap & Fries','Burger Combo','Signature Platter','Chef Salad'
];

$insert = $conn->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
foreach ($productNames as $i => $name) {
    $price = rand(800, 6800); // random sample price
    $desc = "Delicious {$name} prepared fresh.";
    $image = "images/product" . ($i + 1) . ".jpg"; // you can add images or keep fallback
    $insert->bind_param('ssds', $name, $desc, $price, $image);
    $insert->execute();
}
echo "Seeded " . count($productNames) . " products.";
