<?php
// checkout.php
session_start();
require_once 'db.php';

$cart = $_SESSION['cart'] ?? [];
$items = [];
$subtotal = 0.0;
if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT id,name,price,image FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $qty = intval($cart[$r['id']] ?? 0);
        $line = $r;
        $line['qty'] = $qty;
        $line['subtotal'] = $qty * $r['price'];
        $subtotal += $line['subtotal'];
        $items[] = $line;
    }
}

$DELIVERY_FEE = 3000.00;
$TAX_RATE = 0.05;
$errors = [];
// if form posted, forward to order_sucess (server side) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // forward to order_sucess.php via POST with form fields — we'll let order_sucess handle DB insertion
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Checkout</title><link rel="stylesheet" href="css/style.css"></head>
<body>
    <a href="index.php" style="margin-left:12px">Back</a>
<div class="container checkout-panel">
  <h2>Checkout</h2>

  <?php if (empty($items)): ?>
    <div class="small">Your cart is empty. <a href="index.php">Go back to shop</a></div>
  <?php else: ?>
    <form method="post" action="order_sucess.php" enctype="multipart/form-data">
      <h3>Order details</h3>
      <div class="small">Order type</div>
      <div style="display:flex;gap:8px;margin-bottom:8px">
        <label><input type="radio" name="order_type" value="delivery" checked> Delivery</label>
        <label><input type="radio" name="order_type" value="pickup"> Pickup</label>
      </div>

      <div class="small">When</div>
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <label><input type="radio" name="when" value="asap" checked> ASAP</label>
        <label><input type="radio" name="when" value="scheduled"> Specific time</label>
        <input class="input" type="time" name="specific_time" placeholder="Time (if scheduled)">
      </div>
      

  <!-- ✅ New Address Block -->
  <h4>Delivery Address</h4>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
    <input class="input" name="street" placeholder="Street *" required>
    <input class="input" name="number" placeholder="No. *" required>
    <input class="input" name="city" placeholder="City *" required>
    <input class="input" name="postcode" placeholder="ZIP / Postal code *" required>
    <input class="input" name="apt" placeholder="Apt.">
    <input class="input" name="floor" placeholder="Floor number">
    <input class="input" name="company" placeholder="Company name (optional)">
  </div>
  <div style="margin-top:8px">
    <button type="button" class="btn ghost" onclick="getLocation()">📍 Use my location</button>
    <input type="hidden" id="latitude" name="latitude">
    <input type="hidden" id="longitude" name="longitude">
  </div>
  <!-- ✅ End of new address block -->

  <h4>Contact Info</h4>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
    <input class="input" name="firstname" placeholder="First name" required>
    <input class="input" name="lastname" placeholder="Last name" required>
    <input class="input" name="phone" placeholder="Phone number" required>
    <input class="input" name="email" placeholder="Email (optional)">
  </div>


      <h4>Contact & address</h4>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <input class="input" name="firstname" placeholder="First name" required>
        <input class="input" name="lastname" placeholder="Last name" required>
        <input class="input" name="phone" placeholder="Phone number" required>
        <input class="input" name="company" placeholder="Company (optional)">
        <input class="input" name="address" placeholder="Address (street)">
        <input class="input" name="apt" placeholder="Apt / Suite">
        <input class="input" name="city" placeholder="City">
        <input class="input" name="postcode" placeholder="Postal code">
      </div>

      <h4>Payment</h4>
      <div style="display:flex;gap:8px;margin-bottom:8px">
        <label><input type="radio" name="payment_method" value="cash" checked> Cash</label>
        <label><input type="radio" name="payment_method" value="transfer"> Bank Transfer</label>
        <label><input type="radio" name="payment_method" value="card"> Card (not integrated)</label>
      </div>

      <div class="small">If you choose transfer, send proof to <strong>09021427575</strong> (you can upload an image)</div>
      <div style="margin-top:8px">
        <label class="small">Upload proof (optional)</label>
        <input type="file" name="proof_file" accept="image/*">
      </div>

      <div style="margin-top:12px">
        <label class="small">Add comment to order</label>
        <textarea name="comments" rows="3" class="input"></textarea>
      </div>

      <h3>Summary</h3>
      <div>
        <?php foreach ($items as $it): ?>
          <div style="display:flex;justify-content:space-between">
            <div><?=htmlspecialchars($it['name'])?> × <?=$it['qty']?></div>
            <div>₦<?=number_format($it['subtotal'],2)?></div>
          </div>
        <?php endforeach; ?>
        <hr>
        <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>₦<?=number_format($subtotal,2)?></span></div>

        <div id="deliveryRow" style="display:flex;justify-content:space-between"><span>Delivery fee</span><span>₦<?=number_format($DELIVERY_FEE,2)?></span></div>

        <div style="display:flex;justify-content:space-between"><span>Tax (5%)</span><span>₦<?=number_format($subtotal * $TAX_RATE,2)?></span></div>

        <?php
          $calculated_total = $subtotal + ($DELIVERY_FEE) + ($subtotal * $TAX_RATE);
        ?>
        <div class="summary" style="display:flex;justify-content:space-between;margin-top:8px">
          <span>Total</span><span>₦<?=number_format($calculated_total,2)?></span>
        </div>
      </div>

      <input type="hidden" name="delivery_fee" value="<?=number_format($DELIVERY_FEE,2,'.','')?>">
      <input type="hidden" name="tax_rate" value="<?=$TAX_RATE?>">
      <input type="hidden" name="subtotal" value="<?=number_format($subtotal,2,'.','')?>">
      <div style="margin-top:12px">
        <button class="btn" type="submit">Confirm Order</button>
        <a href="index.php" class="btn ghost" style="text-decoration:none;padding:8px 12px;display:inline-block">Back to shop</a>
      </div>
    </form>
  <?php endif; ?>
</div>
<script>
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        document.getElementById("latitude").value = position.coords.latitude;
        document.getElementById("longitude").value = position.coords.longitude;
        alert("✅ Location captured!");
      },
      function(error) {
        alert("❌ Unable to get location: " + error.message);
      }
    );
  } else {
    alert("❌ Geolocation is not supported by this browser.");
  }
}
</script>

</body>
</html>
