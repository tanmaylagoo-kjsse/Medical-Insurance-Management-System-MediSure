<?php
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$user_id = null;
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = (int)$row['id'];
    }
    $stmt->close();
}


function luhn_check($number) {
    $number = preg_replace('/\D/', '', $number);
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = intval($number[$i]);
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    return ($sum % 10) === 0;
}

$errors = [];
$success = '';
$selectedPlan = null;


$pid = 0;
if (isset($_GET['plan_id'])) $pid = intval($_GET['plan_id']);
if (isset($_POST['plan_id'])) $pid = intval($_POST['plan_id']);
if ($pid > 0) {
    $q = $conn->prepare("SELECT id, name, price, description FROM plans WHERE id=?");
    if ($q) {
        $q->bind_param("i", $pid);
        $q->execute();
        $r = $q->get_result();
        $selectedPlan = $r->fetch_assoc();
        $q->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment fields
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\s+/', '', ($_POST['card_number'] ?? ''));
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if (empty($card_name)) $errors[] = 'Cardholder name is required.';
    if (!preg_match('/^\d{13,19}$/', $card_number) || !luhn_check($card_number)) $errors[] = 'Invalid card number.';
    // expiry expected MM/YY or MM/YYYY
    if (!preg_match('/^(0[1-9]|1[0-2])[\/\\-](\d{2}|\d{4})$/', $expiry, $m)) {
        $errors[] = 'Invalid expiry format. Use MM/YY or MM/YYYY.';
    } else {
        $month = intval($m[1]);
        $year = intval($m[2]);
        if ($year < 100) { $year += 2000; }
        $exp_ts = strtotime(sprintf('%04d-%02d-01', $year, $month));
        // end of month
        $exp_end = strtotime('+1 month', $exp_ts) - 1;
        if ($exp_end < time()) $errors[] = 'Card has expired.';
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'Invalid CVV.';

    if (empty($selectedPlan)) $errors[] = 'Selected plan not found.';
    if ($user_id === null) $errors[] = 'User not found.';

    if (empty($errors)) {
        // Simulate payment processing (placeholder)
        // In real app integrate with payment gateway here.

        // Extract numeric price
        $priceStr = $selectedPlan['price'];
        $num = preg_replace('/[^0-9\.\,]/', '', $priceStr);
        $num = str_replace(',', '', $num);
        $amount = floatval($num);

        $ins = $conn->prepare("INSERT INTO purchases (user_id, plan_id, purchase_amount) VALUES (?, ?, ?)");
        if ($ins) {
            $ins->bind_param("iid", $user_id, $selectedPlan['id'], $amount);
            if ($ins->execute()) {
                $success = 'Payment successful — purchase recorded. Thank you!';
            } else {
                $errors[] = 'Failed to record purchase: ' . $ins->error;
            }
            $ins->close();
        } else {
            $errors[] = 'Failed to prepare insert: ' . $conn->error;
        }
    }
}
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment — MediSure</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body{font-family: 'Outfit', sans-serif;background:#f8fafc;padding:24px}
    .card{background:#fff;padding:20px;border-radius:10px;max-width:720px;margin:16px auto;border:1px solid #e2e8f0}
    .btn{background:#2563eb;color:#fff;padding:10px 14px;border:none;border-radius:8px;cursor:pointer}
    .field{margin-bottom:12px}
    label{display:block;margin-bottom:6px;color:#475569;font-weight:600}
    input[type=text], input[type=tel] {width:100%;padding:10px;border-radius:8px;border:1px solid #e2e8f0}
    .row{display:flex;gap:12px}
    .col{flex:1}
    .errors{background:#fff1f2;color:#9f1239;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #fecaca}
    .success{background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #bbf7d0}
  </style>
</head>
<body>

<div class="card">
  <h2>Payment Details</h2>
  <p>Signed in as <strong><?php echo htmlspecialchars($username); ?></strong></p>

  <?php if (!empty($errors)): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <p><a href="home.php" class="btn">Return Home</a></p>
  <?php elseif ($selectedPlan): ?>

    <div style="margin-bottom:16px">
      <strong><?php echo htmlspecialchars($selectedPlan['name']); ?></strong> — <?php echo htmlspecialchars($selectedPlan['price']); ?>
      <div style="color:#64748b;margin-top:6px"><?php echo htmlspecialchars($selectedPlan['description']); ?></div>
    </div>

    <form method="POST" action="payment.php">
      <input type="hidden" name="plan_id" value="<?php echo (int)$selectedPlan['id']; ?>">

      <div class="field">
        <label for="card_name">Cardholder Name</label>
        <input type="text" id="card_name" name="card_name" required value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
      </div>

      <div class="field">
        <label for="card_number">Card Number</label>
        <input type="tel" id="card_number" name="card_number" inputmode="numeric" placeholder="1234 5678 9012 3456" required value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
      </div>

      <div class="row">
        <div class="col field">
          <label for="expiry">Expiry (MM/YY)</label>
          <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required value="<?php echo htmlspecialchars($_POST['expiry'] ?? ''); ?>">
        </div>
        <div class="col field">
          <label for="cvv">CVV</label>
          <input type="tel" id="cvv" name="cvv" inputmode="numeric" placeholder="123" required value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
        </div>
      </div>

      <div style="margin-top:14px">
        <button class="btn" type="submit">Pay <?php echo htmlspecialchars($selectedPlan['price']); ?></button>
        <a style="margin-left:12px;color:#2563eb;text-decoration:none" href="buy.php">Cancel</a>
      </div>
    </form>

  <?php else: ?>
    <p>No plan selected. <a href="buy.php">Return to plans</a></p>
  <?php endif; ?>

</div>

</body>
</html>
