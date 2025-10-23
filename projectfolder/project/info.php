<?php
session_start();
include 'auth.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch blog posts including blog_title
$sql = "SELECT 
            tblblog.blog_id,
            tblblog.blog_title,
            tblblog.blog_content, 
            tblblog.date_posted, 
            tbluser.user_id AS posted_by, 
            tbluser.username AS posted_by_username
        FROM tblblog 
        JOIN tbluser ON tblblog.posted_by = tbluser.user_id 
        WHERE tblblog.blog_status = 'active'
        ORDER BY tblblog.date_posted DESC";

$result = $mysql->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>form</title>

    <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DreamLiving - Your Perfect Home Awaits</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script defer>
    function handleSubmit(e) {
      e.preventDefault();
      const form = document.getElementById('contact-form');
      const data = new FormData(form);
      const entries = Object.fromEntries(data.entries());
      alert(`Thank you, ${entries.name}. We'll get back to you soon!`);
      form.reset();
    }
  </script>
</head>
<body class="bg-gray-50 text-gray-800">
  <header class="bg-white shadow p-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-blue-700"><a href="realestate.php">REALESTATE</a></h1>
    <nav class="space-x-4">
      <a href="#about" class="text-gray-700 hover:text-blue-700">About</a>
      <a href="#design" class="text-gray-700 hover:text-blue-700">Design</a>
      <a href="#contact" class="text-gray-700 hover:text-blue-700">Contact</a>
    </nav>
  </header>

  <section class="relative h-screen bg-cover bg-center" style="background-image: url('https://source.unsplash.com/1600x900/?modern-house,luxury');">
    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="text-center text-white">
        <h2 class="text-5xl font-bold mb-4">Live in Luxury</h2>
        <p class="text-lg mb-6">Explore beautifully crafted modern homes tailored to your lifestyle</p>
        <a href="#contact" class="bg-blue-600 px-6 py-3 text-white rounded-full hover:bg-blue-700">Get in Touch</a>
      </div>
    </div>
  </section>

  <section id="about" class="py-16 px-8 max-w-6xl mx-auto">
    <h2 class="text-4xl font-semibold text-center mb-10">About the House</h2>
    <p class="text-lg leading-relaxed text-center text-gray-700">
      Nestled in a serene environment, our featured property blends modern architecture with natural surroundings. This smart home features five spacious bedrooms, a chef’s kitchen, solar energy integration, and voice-activated smart systems. Designed for comfort, sustainability, and elegance, it’s perfect for families or individuals seeking peace and luxury.
    </p>
  </section>

  <section id="design" class="py-16 bg-gray-100 px-8 max-w-6xl mx-auto">
    <h2 class="text-4xl font-semibold text-center mb-10">Design Features</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold mb-2">Open Floor Plan</h3>
        <p>Enjoy expansive living spaces with seamless transitions between rooms.</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold mb-2">Eco-Friendly Materials</h3>
        <p>Sustainable wood, natural stone, and recycled steel throughout the home.</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold mb-2">Smart Technology</h3>
        <p>Control lighting, temperature, and security with your voice or phone.</p>
      </div>
    </div>
  </section>

  <section id="contact" class="py-16 px-8 max-w-4xl mx-auto">
    <h2 class="text-4xl font-semibold text-center mb-10">Request More Info</h2>
    <form id="contact-form" onsubmit="handleSubmit(event)" class="bg-white p-8 shadow-lg rounded-lg space-y-6">
      <div>
        <label for="name" class="block font-semibold mb-2">Full Name</label>
        <input type="text" name="name" id="name" required class="w-full p-3 border rounded-lg" />
      </div>
      <div>
        <label for="email" class="block font-semibold mb-2">Email Address</label>
        <input type="email" name="email" id="email" required class="w-full p-3 border rounded-lg" />
      </div>
      <div>
        <label for="message" class="block font-semibold mb-2">Message</label>
        <textarea name="message" id="message" rows="5" class="w-full p-3 border rounded-lg" placeholder="Tell us what you're looking for..."></textarea>
      </div>
      <button type="submit" class="w-full bg-blue-700 text-white py-3 rounded-lg hover:bg-blue-800">Submit</button>
    </form>
  </section>

  <footer class="bg-white text-center text-sm text-gray-500 p-6 mt-16">
    &copy; 2025 DreamLiving. All rights reserved.
  </footer>
</body>
</html>