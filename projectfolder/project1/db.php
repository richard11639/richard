<?php
// db.php - configure DB connection here
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'ric_restaurant';

// connect
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($conn->connect_error) {
    die("DB connect error: " . $conn->connect_error);
}

// ensure database exists and select it
if (! $conn->select_db($DB_NAME)) {
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($DB_NAME);
}

// set charset
$conn->set_charset('utf8mb4');

// create products table
$conn->query("
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// create orders table with the columns you listed
$conn->query("
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  order_type VARCHAR(50) DEFAULT NULL,
  delivery_time VARCHAR(255) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  apt VARCHAR(255) DEFAULT NULL,
  city VARCHAR(255) DEFAULT NULL,
  company VARCHAR(255) DEFAULT NULL,
  first_name VARCHAR(255) DEFAULT NULL,
  last_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  payment_method VARCHAR(64) DEFAULT NULL,
  proof_filename VARCHAR(512) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  delivery_fee DECIMAL(10,2) DEFAULT 0,
  tax DECIMAL(10,2) DEFAULT 0,
  subtotal DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Seed sample products only if table is empty
$res = $conn->query("SELECT COUNT(*) AS c FROM products");
$row = $res->fetch_assoc();
if ((int)$row['c'] === 0) {

    $seed = [
      // original 5 (with picsum images)
      ['Fried Rice & Chicken','Delicious Fried Rice & Chicken prepared fresh.',2500.00,'https://picsum.photos/seed/friedrice/800/600'],
      ['Jollof Rice & Fish','Delicious Jollof Rice & Fish.',2700.00,'https://picsum.photos/seed/jollof/800/600'],
      ['Pounded Yam & Egusi','Pounded Yam served with Egusi.',3500.00,'https://picsum.photos/seed/poundedyam/800/600'],
      ['Shawarma','Classic Shawarma wrap.',1500.00,'https://picsum.photos/seed/shawarma/800/600'],
      ['Burger & Fries','Beef burger with fries.',2000.00,'https://picsum.photos/seed/burger/800/600'],

      // the rest (use descriptive seeds so picsum returns varied bright images)
      ['Spaghetti Bolognese','Savoury spaghetti with meat sauce.',1500.00,'https://picsum.photos/seed/spaghetti/800/600'],
      ['Seafood Platter','Mix of prawns, calamari, and fish.',6500.00,'https://picsum.photos/seed/seafood/800/600'],
      ['Grilled Fish','Seasoned fish grilled to perfection.',4000.00,'https://picsum.photos/seed/grilledfish/800/600'],
      ['Vegetable Salad','Fresh mixed vegetables with dressing.',1800.00,'https://picsum.photos/seed/salad/800/600'],
      ['Club Sandwich','Triple-layer chicken club sandwich.',2200.00,'https://picsum.photos/seed/sandwich/800/600'],
      ['Chicken Wings','Spicy fried chicken wings.',2500.00,'https://picsum.photos/seed/wings/800/600'],
      ['Beef Steak','Grilled beef steak with sauce.',7500.00,'https://picsum.photos/seed/steak/800/600'],
      ['Pancake Stack','Fluffy pancakes with syrup.',1600.00,'https://picsum.photos/seed/pancakes/800/600'],
      ['Omelette Deluxe','Cheese and veggie omelette.',1400.00,'https://picsum.photos/seed/omelette/800/600'],
      ['Chicken Wrap','Tortilla wrap with chicken filling.',2000.00,'https://picsum.photos/seed/wrap/800/600'],
      ['Noodle Bowl','Asian style noodle bowl.',2300.00,'https://picsum.photos/seed/noodles/800/600'],
      ['Sushi Pack','8-piece assorted sushi.',5200.00,'https://picsum.photos/seed/sushi/800/600'],
      ['Taco Box','3 tacos with salsa.',3000.00,'https://picsum.photos/seed/tacos/800/600'],
      ['Pizza Margherita','Classic tomato and cheese pizza.',4500.00,'https://picsum.photos/seed/pizza/800/600'],
      ['BBQ Ribs','Barbecue pork ribs.',7000.00,'https://picsum.photos/seed/ribs/800/600'],
      ['Lamb Chops','Grilled lamb chops with herbs.',8500.00,'https://picsum.photos/seed/lamb/800/600'],
      ['Beef Stew','Slow-cooked beef stew.',5000.00,'https://picsum.photos/seed/stew/800/600'],
      ['Vegetable Curry','Spicy mixed vegetable curry.',2700.00,'https://picsum.photos/seed/curry/800/600'],
      ['Fruit Platter','Assorted seasonal fruits.',2500.00,'https://picsum.photos/seed/fruit/800/600'],
      ['Dessert Combo','Selection of cakes and pastries.',3000.00,'https://picsum.photos/seed/dessert/800/600'],
      ['Ice Cream 3 scoops','Any 3 flavors.',1500.00,'https://picsum.photos/seed/icecream/800/600'],
      ['Smoothie Pack','Mixed fruit smoothies.',2000.00,'https://picsum.photos/seed/smoothie/800/600'],
      ['Coffee & Cake','Slice of cake with hot coffee.',1800.00,'https://picsum.photos/seed/coffee/800/600'],
      ['Lunch Box A','Rice, chicken, and salad.',2500.00,'https://picsum.photos/seed/lunchbox/800/600'],
      ['Lunch Box B','Jollof rice, beef, and plantain.',2600.00,'https://picsum.photos/seed/lunchboxb/800/600'],
      ['Party Pack 5','Food pack for 5 people.',12000.00,'https://picsum.photos/seed/party/800/600'],
      ['Family Pack 8','Food pack for 8 people.',18000.00,'https://picsum.photos/seed/family/800/600'],
      ['Mini Buffet','Buffet for 10 persons.',30000.00,'https://picsum.photos/seed/buffet/800/600'],
      ['Corporate Lunch','Special meal for meetings.',5000.00,'https://picsum.photos/seed/corporate/800/600'],
      ['Kids Meal','Child-size meal with juice.',2000.00,'https://picsum.photos/seed/kidsmeal/800/600'],
      ['Breakfast for Two','Full breakfast for 2.',5000.00,'https://picsum.photos/seed/breakfast/800/600'],
      ['Romantic Dinner','Dinner for 2 with dessert.',15000.00,'https://picsum.photos/seed/romantic/800/600'],
      ['Seafood Platter Deluxe','Bigger mix of seafood.',9000.00,'https://picsum.photos/seed/seafooddeluxe/800/600'],
      ['Chef Special','Daily chef special dish.',6000.00,'https://picsum.photos/seed/chefspecial/800/600'],
      ['Street Food Box','Local street food combo.',2200.00,'https://picsum.photos/seed/streetfood/800/600'],
      ['Rice & Stew','Steamed rice with beef stew.',2500.00,'https://picsum.photos/seed/riceandstew/800/600'],
      ['Plantain Meal','Fried plantain with beans.',2000.00,'https://picsum.photos/seed/plantain/800/600'],
      ['Beans & Dodo','Beans porridge with fried plantain.',2200.00,'https://picsum.photos/seed/beansdodo/800/600'],
      ['Roast Chicken','Whole roasted chicken.',7500.00,'https://picsum.photos/seed/roastchicken/800/600'],
      ['Prawn Special','Spicy prawns with rice.',6500.00,'https://picsum.photos/seed/prawns/800/600'],
      ['Crispy Fish','Deep-fried crispy fish.',5000.00,'https://picsum.photos/seed/crispyfish/800/600'],
      ['Mushroom Risotto','Creamy Italian rice with mushrooms.',4800.00,'https://picsum.photos/seed/risotto/800/600'],
      ['Creamy Pasta','Pasta in creamy sauce.',3500.00,'https://picsum.photos/seed/creampasta/800/600'],
      ['Garlic Bread','Buttery garlic bread loaf.',1200.00,'https://picsum.photos/seed/garlicbread/800/600'],
      ['House Salad','Fresh greens and toppings.',1800.00,'https://picsum.photos/seed/housesalad/800/600'],
      ['Savoury Pie','Meat pie with filling.',2000.00,'https://picsum.photos/seed/pie/800/600'],
      ['Wrap & Fries','Tortilla wrap with fries.',2800.00,'https://picsum.photos/seed/wrapfries/800/600'],
      ['Burger Combo','Burger, fries & drink.',3000.00,'https://picsum.photos/seed/burgercombo/800/600'],
      ['Signature Platter','Chef assorted platter.',8000.00,'https://picsum.photos/seed/signature/800/600'],
      ['Chef Salad','Protein-packed salad.',3200.00,'https://picsum.photos/seed/chefsalad/800/600'],
      ['Chicken Soup','Warm chicken soup.',2500.00,'https://picsum.photos/seed/chickensoup/800/600'],
      ['Vegetable Stir Fry','Mixed veggies stir-fried.',3000.00,'https://picsum.photos/seed/stirfry/800/600'],
      ['Shrimp Fried Rice','Fried rice with shrimps.',4500.00,'https://picsum.photos/seed/shrimpfriedrice/800/600'],
      ['Pepper Soup','Spicy goat meat soup.',3500.00,'https://picsum.photos/seed/peppersoup/800/600'],
      ['Samosa Pack','6 pcs samosas.',2000.00,'https://picsum.photos/seed/samosa/800/600'],
      ['Spring Rolls','6 pcs vegetable rolls.',1800.00,'https://picsum.photos/seed/springrolls/800/600'],
      ['Fried Yam & Sauce','Yam fries with pepper sauce.',2200.00,'https://picsum.photos/seed/friedyam/800/600'],
      ['Efo Riro','Spinach stew with beef.',4000.00,'https://picsum.photos/seed/eforiro/800/600'],
      ['Okra Soup','Okra soup with fish.',3500.00,'https://picsum.photos/seed/okrasoup/800/600'],
      ['Ofada Rice','Local rice with sauce.',3000.00,'https://picsum.photos/seed/ofada/800/600'],
      ['Moi Moi','Steamed bean cake.',1500.00,'https://picsum.photos/seed/moimoi/800/600'],
      ['Akara & Pap','Bean cakes with pap.',1800.00,'https://picsum.photos/seed/akara/800/600'],
      ['Suya Platter','Spicy beef suya with onions.',4000.00,'https://picsum.photos/seed/suya/800/600'],
      ['Turkey Wings','Grilled turkey wings.',5000.00,'https://picsum.photos/seed/turkey/800/600'],
      ['Yam Porridge','Traditional yam porridge.',2500.00,'https://picsum.photos/seed/yamporridge/800/600'],
      ['Egusi Soup','Egusi soup with assorted meat.',4500.00,'https://picsum.photos/seed/egusi/800/600'],
      ['Ewedu Soup','Ewedu with amala.',3200.00,'https://picsum.photos/seed/ewedu/800/600'],
      ['Banga Soup','Palm nut soup.',4000.00,'https://picsum.photos/seed/banga/800/600'],
      ['Oha Soup','Oha soup with beef.',3800.00,'https://picsum.photos/seed/ohasoup/800/600'],
      ['Nsala Soup','Catfish white soup.',4200.00,'https://picsum.photos/seed/nsala/800/600'],
      ['Ogbono Soup','Ogbono with assorted meat.',3700.00,'https://picsum.photos/seed/ogbono/800/600'],
      ['Abacha (African Salad)','Cassava salad.',2500.00,'https://picsum.photos/seed/abacha/800/600'],
      ['Ukodo (Yam Pepper Soup)','Spicy yam pepper soup.',3300.00,'https://picsum.photos/seed/ukodo/800/600'],
      ['Kilishi Pack','Dried spicy beef.',4000.00,'https://picsum.photos/seed/kilishi/800/600'],
      ['Asun','Spicy roasted goat meat.',5000.00,'https://picsum.photos/seed/asun/800/600'],
      ['Nkwobi','Cow foot in spicy sauce.',5500.00,'https://picsum.photos/seed/nkwobi/800/600'],
      ['Isi Ewu','Goat head delicacy.',6500.00,'https://picsum.photos/seed/isiewu/800/600'],
      ['Fufu & Soup','Fufu with soup of choice.',3000.00,'https://picsum.photos/seed/fufu/800/600'],
      ['White Rice & Sauce','Plain rice with stew.',2200.00,'https://picsum.photos/seed/whiterice/800/600'],
      ['Coconut Rice','Rice cooked in coconut milk.',3500.00,'https://picsum.photos/seed/coconutrice/800/600'],
      ['Fried Plantain','Crispy dodo.',1800.00,'https://picsum.photos/seed/plantainfried/800/600'],
      ['Chin Chin','Sweet crunchy snack.',1500.00,'https://picsum.photos/seed/chinchin/800/600'],
      ['Puff Puff','Sweet fried dough balls.',1200.00,'https://picsum.photos/seed/puffpuff/800/600'],
      ['Meat Pie','Classic meat pie.',2000.00,'https://picsum.photos/seed/meatpie/800/600'],
      ['Fish Pie','Savory fish pie.',2000.00,'https://picsum.photos/seed/fishpie/800/600'],
      ['Chicken Pie','Savory chicken pie.',2000.00,'https://picsum.photos/seed/chickenpie/800/600'],
      ['Gizzard Skewers','Spicy grilled gizzard.',3000.00,'https://picsum.photos/seed/gizzard/800/600'],
      ['Plantain Chips','Crispy plantain chips.',1000.00,'https://picsum.photos/seed/plantainchips/800/600'],
      ['Popcorn Pack','Sweet or salty popcorn.',1200.00,'https://picsum.photos/seed/popcorn/800/600'],
      ['Small Chops Platter','Mix of snacks for parties.',5000.00,'https://picsum.photos/seed/smallchops/800/600'],
      ['Gala Sausage Roll','Packaged sausage snack.',500.00,'https://picsum.photos/seed/gala/800/600'],
      ['Meat Kebab','Grilled meat on skewers.',2500.00,'https://picsum.photos/seed/kebab/800/600'],
      ['Corn on the Cob','Boiled or roasted corn.',1500.00,'https://picsum.photos/seed/corn/800/600'],
      ['Roasted Plantain (Boli)','Boli with groundnut sauce.',2500.00,'https://picsum.photos/seed/roastedboli/800/600'],
      ['Roasted Yam','Yam with pepper sauce.',2000.00,'https://picsum.photos/seed/roastedyam/800/600'],
      ['Boiled Yam & Egg Sauce','Yam with spicy egg sauce.',2800.00,'https://picsum.photos/seed/boiledyam/800/600'],
      ['Tea & Bread','Breakfast classic.',1000.00,'https://picsum.photos/seed/teaandbread/800/600'],
      ['Indomie Special','Instant noodles with extras.',2000.00,'https://picsum.photos/seed/indomie/800/600'],
      ['Palm Wine','Local tapped palm wine.',1500.00,'https://picsum.photos/seed/palmwine/800/600'],
      ['Zobo Drink','Hibiscus chilled drink.',1200.00,'https://picsum.photos/seed/zobo/800/600']
    ];

    $stmt = $conn->prepare("INSERT INTO products (name,description,price,image) VALUES (?,?,?,?)");
    if ($stmt) {
        foreach ($seed as $s) {
            $name = $s[0];
            $desc = $s[1];
            $price = (float)$s[2];
            $img = $s[3];
            $stmt->bind_param('ssds', $name, $desc, $price, $img);
            $stmt->execute();
        }
        $stmt->close();
    } else {
        error_log("Prepare failed for seeding products: " . $conn->error);
    }
}
