<?php
require_once 'header.php';
global $pdo;
$user = current_user();
$cart = $_SESSION['cart'] ?? [];
if(empty($cart)){ header('Location: shop.php'); exit; }

$subtotal = cart_subtotal($pdo);
$delivery_fee = floatval($_POST['delivery_fee'] ?? 1500);
$tax = round($subtotal * 0.05,2);
$total = $subtotal + $delivery_fee + $tax;

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])){
    if(!$user){ $_SESSION['redirect_after_login'] = 'checkout.php'; $_SESSION['flash'] = 'Please sign in to place order'; header('Location: signin.php'); exit; }

    $order_type = ($_POST['order_type'] ?? '') === 'Pickup' ? 'Pickup' : 'Delivery';
    $when = $_POST['when'] ?? 'ASAP';
    $payment_method = in_array($_POST['payment_method'] ?? 'Cash', ['Cash','Bank Transfer','Card','Wallet']) ? $_POST['payment_method'] : 'Cash';

    $delivery_info = null;
    if($order_type === 'Delivery'){
        $delivery_info = json_encode([
            'street'=>$_POST['street'] ?? '',
            'no'=>$_POST['no'] ?? '',
            'city'=>$_POST['city'] ?? '',
            'zip'=>$_POST['zip'] ?? '',
            'apt'=>$_POST['apt'] ?? '',
            'company'=>$_POST['company'] ?? '',
        ]);
    }

    $contact_info = json_encode([
        'first'=>$_POST['first_name'] ?? '',
        'last'=>$_POST['last_name'] ?? '',
        'phone'=>$_POST['phone'] ?? '',
        'email'=>$_POST['email'] ?? '',
    ]);

    $proofFilename = null;
    if($payment_method === 'Bank Transfer' && isset($_FILES['proof']) && $_FILES['proof']['error']===0){
        $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','pdf'];
        if(in_array(strtolower($ext), $allowed)){
            $fn = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
            $destFolder = __DIR__.'/uploads/proofs';
            if(!is_dir($destFolder)) mkdir($destFolder,0755,true);
            $dest = $destFolder.'/'.$fn;
            move_uploaded_file($_FILES['proof']['tmp_name'], $dest);
            $proofFilename = 'uploads/proofs/'.$fn;
        }
    }

    if($payment_method === 'Wallet'){
        if($user['wallet'] < $total){
            $_SESSION['flash'] = 'Insufficient wallet balance. Please deposit funds or choose another payment method.';
            header('Location: deposit.php'); exit;
        }
    }

    // create order
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id,subtotal,delivery_fee,tax,total,order_type,pickup_time,delivery_info,contact_info,payment_method,payment_proof,status) VALUES
            (:uid,:sub,:df,:tax,:tot,:otype,:ptime,:dinfo,:cinfo,:pmethod,:proof,'processing')");
        $stmt->execute([
            ':uid'=>$user['id'],
            ':sub'=>$subtotal,
            ':df'=>$delivery_fee,
            ':tax'=>$tax,
            ':tot'=>$total,
            ':otype'=>$order_type,
            ':ptime'=>($when==='ASAP'?'ASAP':($_POST['specific_time'] ?? '')),
            ':dinfo'=>$delivery_info,
            ':cinfo'=>$contact_info,
            ':pmethod'=>$payment_method,
            ':proof'=>$proofFilename
        ]);
        $order_id = $pdo->lastInsertId();

        // save items (use DB price)
        foreach($cart as $pid=>$qty){
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id'=>$pid]);
            $pr = $stmt->fetchColumn();
            $ins = $pdo->prepare("INSERT INTO order_items (order_id,product_id,qty,price) VALUES (:oid,:pid,:qty,:price)");
            $ins->execute([':oid'=>$order_id,':pid'=>$pid,':qty'=>$qty,':price'=>$pr ?: 0]);
        }

        if($payment_method === 'Wallet'){
            $pdo->prepare("UPDATE users SET wallet = wallet - :amt WHERE id = :id")->execute([':amt'=>$total,':id'=>$user['id']]);
        }

        $pdo->commit();
    } catch (Exception $e){
        $pdo->rollBack();
        die("Order failed: ".$e->getMessage());
    }

    unset($_SESSION['cart']);
    $_SESSION['flash'] = "Order placed. Your order ID: #$order_id";
    header('Location: orders.php'); exit;
}
?>

<main style="max-width:900px;margin:18px auto;padding:12px">
  <h2>Checkout</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="subtotal" value="<?=$subtotal?>">
    <input type="hidden" name="delivery_fee" value="<?=$delivery_fee?>">
    <input type="hidden" name="tax" value="<?=$tax?>">
    <input type="hidden" name="total" value="<?=$total?>">

    <h3>Order details</h3>
    <label>Order type:
      <select name="order_type">
        <option>Delivery</option>
        <option>Pickup</option>
      </select>
    </label>

    <label>When:
      <select name="when" id="whenSel" onchange="toggleTime()">
        <option>ASAP</option>
        <option>Specific time</option>
      </select>
    </label>
    <div id="timeBox" style="display:none">
      <label>Specific time <input type="time" name="specific_time"></label>
    </div>

    <h3>Delivery Address</h3>
    <label>Street * <input name="street"></label>
    <label>No. * <input name="no"></label>
    <label>City * <input name="city"></label>
    <label>ZIP / Postal code * <input name="zip"></label>
    <label>Apt. <input name="apt"></label>
    <label>Company name (optional) <input name="company"></label>

    <h3>Contact Info</h3>
    <label>First name <input name="first_name" value="<?=htmlspecialchars($user['first_name'] ?? '')?>"></label>
    <label>Last name <input name="last_name" value="<?=htmlspecialchars($user['last_name'] ?? '')?>"></label>
    <label>Phone number * <input name="phone"></label>
    <label>Email (optional) <input name="email" value="<?=htmlspecialchars($user['email'] ?? '')?>"></label>

    <h3>Payment</h3>
    <label><input type="radio" name="payment_method" value="Cash" checked> Cash</label><br>
    <label><input type="radio" name="payment_method" value="Bank Transfer"> Bank Transfer (send proof to 09021427575)</label>
    <div><label>Upload proof (optional): <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf"></label></div>
    <label><input type="radio" name="payment_method" value="Card"> Card (not integrated)</label><br>
    <label><input type="radio" name="payment_method" value="Wallet"> Wallet (use your deposited balance)</label>

    <h3>Summary</h3>
    <div>Subtotal: <?=money($subtotal)?></div>
    <div>Delivery fee: <?=money($delivery_fee)?></div>
    <div>Tax (5%): <?=money($tax)?></div>
    <div style="font-weight:900">Total: <?=money($total)?></div>

    <div style="margin-top:10px">
      <button class="btn" type="submit" name="place_order" style="background:#ff7b54;color:#fff;padding:10px 14px">Confirm Order</button>
      <a href="shop.php" class="btn" style="background:#fff;border:1px solid #e6ebf0;color:#071428;margin-left:8px">Back to shop</a>
    </div>
  </form>
</main>

<script>
function toggleTime(){
  var sel = document.getElementById('whenSel').value;
  document.getElementById('timeBox').style.display = (sel==='Specific time') ? 'block':'none';
}
</script>

<?php require_once 'footer.php'; ?>

