<?php
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$user_id  = null;
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $user_id = (int)$row['id'];
    $stmt->close();
}

function luhn_check($number) {
    $number = preg_replace('/\D/', '', $number);
    $sum = 0; $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = intval($number[$i]);
        if ($alt) { $n *= 2; if ($n > 9) $n -= 9; }
        $sum += $n; $alt = !$alt;
    }
    return ($sum % 10) === 0;
}

// Indian number formatting
function fmt_inr($num) {
    if ($num === null || $num === '') return 'Custom Pricing';
    $num = (int)$num;
    $str  = (string)$num;
    if (strlen($str) <= 3) return '₹' . $str;
    $last3 = substr($str, -3);
    $rest  = substr($str, 0, -3);
    $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
    return '₹' . $rest . ',' . $last3;
}

$errors = [];
$success = '';
$selectedPlan = null;

// Resolve plan_id from GET or POST
$pid = 0;
if (isset($_GET['plan_id']))  $pid = intval($_GET['plan_id']);
if (isset($_POST['plan_id'])) $pid = intval($_POST['plan_id']);

if ($pid > 0) {
    $q = $conn->prepare("SELECT p.id, p.name, p.price, p.claim_amount, p.description, p.type,
                                pr.name AS provider_name
                         FROM plans p
                         LEFT JOIN providers pr ON pr.id = p.provider_id
                         WHERE p.id = ?");
    if ($q) {
        $q->bind_param("i", $pid);
        $q->execute();
        $r = $q->get_result();
        $selectedPlan = $r->fetch_assoc();
        $q->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name   = trim($_POST['card_name']   ?? '');
    $card_number = preg_replace('/\s+/', '', ($_POST['card_number'] ?? ''));
    $expiry      = trim($_POST['expiry']      ?? '');
    $cvv         = trim($_POST['cvv']         ?? '');

    if (empty($card_name))
        $errors[] = 'Cardholder name is required.';

    if (!preg_match('/^\d{13,19}$/', $card_number) || !luhn_check($card_number))
        $errors[] = 'Invalid card number.';

    if (!preg_match('/^(0[1-9]|1[0-2])[\/\-](\d{2}|\d{4})$/', $expiry, $m)) {
        $errors[] = 'Invalid expiry format. Use MM/YY.';
    } else {
        $year = intval($m[2]);
        if ($year < 100) $year += 2000;
        $exp_end = strtotime('+1 month', strtotime(sprintf('%04d-%02d-01', $year, intval($m[1])))) - 1;
        if ($exp_end < time()) $errors[] = 'Card has expired.';
    }

    if (!preg_match('/^\d{3,4}$/', $cvv))
        $errors[] = 'Invalid CVV.';

    if (empty($selectedPlan))
        $errors[] = 'Selected plan not found.';

    if ($user_id === null)
        $errors[] = 'User session error. Please log in again.';

    if (empty($errors)) {
        // Use the numeric price stored in DB directly — no string parsing needed
        $amount = floatval($selectedPlan['price'] ?? 0);

        $ins = $conn->prepare("INSERT INTO purchases (user_id, plan_id, purchase_amount) VALUES (?, ?, ?)");
        if ($ins) {
            $ins->bind_param("iid", $user_id, $selectedPlan['id'], $amount);
            if ($ins->execute()) {
                $success = 'Payment successful — your plan is now active. Thank you!';
            } else {
                $errors[] = 'Failed to record purchase: ' . $ins->error;
            }
            $ins->close();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}

$typeEmoji = [
    'individual'       => '👤',
    'senior'           => '👴',
    'corporate'        => '🏢',
    'critical illness' => '❤️‍🩹',
    'comprehensive'    => '🌟',
    'travel'           => '✈️',
    'pet'              => '🐾',
    'family'           => '👨‍👩‍👧‍👦',
];

function plan_emoji($type, $map) {
    $t = strtolower(trim($type ?? ''));
    foreach ($map as $k => $e) { if (str_contains($t, $k)) return $e; }
    return '🛡️';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment — MediSure</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue-dark:   #0f2a6b;
      --blue-mid:    #1d4ed8;
      --blue-bright: #3b82f6;
      --blue-light:  #eff6ff;
      --blue-pale:   #dbeafe;
      --text-primary:#0f172a;
      --text-muted:  #64748b;
      --border:      #e2e8f0;
      --white:       #ffffff;
      --surface:     #f8fafc;
      --radius-md:   16px;
      --green:       #059669;
      --green-light: #ecfdf5;
      --green-border:#a7f3d0;
      --red:         #9f1239;
      --red-light:   #fff1f2;
      --red-border:  #fecaca;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--surface);
      color: var(--text-primary);
      min-height: 100vh;
    }

    /* ── Header ── */
    header {
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      padding: 14px 48px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .logo {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 22px;
      color: var(--blue-mid);
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .logo-icon {
      width: 32px; height: 32px;
      background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
    }

    .user-pill {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      background: var(--blue-light);
      border-radius: 22px;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--blue-mid);
    }

    .user-avatar {
      width: 24px; height: 24px;
      background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: white;
      font-size: 11px;
      font-weight: 700;
    }

    /* ── Layout ── */
    .page {
      max-width: 760px;
      margin: 48px auto;
      padding: 0 24px 64px;
    }

    .breadcrumb {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .breadcrumb a {
      color: var(--blue-mid);
      text-decoration: none;
      font-weight: 500;
    }

    .breadcrumb a:hover { text-decoration: underline; }

    .page-title {
      font-family: 'Sora', sans-serif;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .page-subtitle {
      font-size: 15px;
      color: var(--text-muted);
      margin-bottom: 32px;
    }

    /* ── Alerts ── */
    .alert {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 16px 18px;
      border-radius: var(--radius-md);
      margin-bottom: 24px;
      font-size: 14.5px;
      font-weight: 500;
    }

    .alert-success { background: var(--green-light); color: var(--green); border: 1px solid var(--green-border); }
    .alert-error   { background: var(--red-light);   color: var(--red);   border: 1px solid var(--red-border); }
    .alert ul { padding-left: 18px; }
    .alert ul li { margin-top: 4px; }

    /* ── Payment layout: two columns ── */
    .payment-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }

    /* ── Form card ── */
    .form-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(15,42,107,0.07);
    }

    .form-card-header {
      padding: 22px 28px;
      border-bottom: 1px solid var(--border);
    }

    .form-card-header h2 {
      font-family: 'Sora', sans-serif;
      font-size: 18px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-card-body { padding: 28px; }

    .field { margin-bottom: 18px; }

    .field label {
      display: block;
      margin-bottom: 7px;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .field input {
      width: 100%;
      padding: 11px 14px;
      border-radius: 10px;
      border: 1.5px solid var(--border);
      font-family: 'Outfit', sans-serif;
      font-size: 14.5px;
      color: var(--text-primary);
      background: var(--surface);
      transition: border-color 0.18s, box-shadow 0.18s;
      outline: none;
    }

    .field input:focus {
      border-color: var(--blue-mid);
      box-shadow: 0 0 0 3px rgba(29,78,216,0.12);
      background: var(--white);
    }

    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .card-icons {
      display: flex;
      gap: 6px;
      margin-bottom: 8px;
    }

    .card-icon {
      font-size: 20px;
      opacity: 0.7;
    }

    /* ── Plan summary card ── */
    .summary-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(15,42,107,0.07);
    }

    .summary-header {
      background: linear-gradient(135deg, #0f2a6b, #1d4ed8);
      padding: 22px 24px;
      color: white;
    }

    .summary-header .s-emoji { font-size: 36px; margin-bottom: 10px; display: block; }

    .summary-header .s-provider {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      opacity: 0.7;
      margin-bottom: 4px;
    }

    .summary-header .s-name {
      font-family: 'Sora', sans-serif;
      font-size: 16px;
      font-weight: 700;
    }

    .summary-body { padding: 20px 24px; }

    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 13.5px;
    }

    .summary-row:last-child { border-bottom: none; }

    .summary-row .s-label { color: var(--text-muted); font-weight: 500; }

    .summary-row .s-val {
      font-weight: 700;
      color: var(--text-primary);
      font-family: 'Sora', sans-serif;
    }

    .summary-row .s-val.green { color: var(--green); }
    .summary-row .s-val.blue  { color: var(--blue-mid); }

    .summary-total {
      margin-top: 16px;
      padding: 14px 16px;
      background: var(--blue-light);
      border-radius: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .summary-total .t-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
    }

    .summary-total .t-val {
      font-family: 'Sora', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: var(--blue-mid);
    }

    /* ── Buttons ── */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 12px 28px;
      background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
      color: white;
      border: none;
      border-radius: 22px;
      cursor: pointer;
      font-family: 'Outfit', sans-serif;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(29,78,216,0.25);
      width: 100%;
      margin-top: 8px;
    }

    .btn:hover {
      box-shadow: 0 6px 20px rgba(29,78,216,0.38);
      transform: translateY(-1px);
    }

    .btn-ghost {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 10px 20px;
      background: transparent;
      color: var(--text-muted);
      border: 1.5px solid var(--border);
      border-radius: 22px;
      font-family: 'Outfit', sans-serif;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s;
      width: 100%;
      margin-top: 10px;
      text-align: center;
    }

    .btn-ghost:hover {
      border-color: var(--blue-pale);
      color: var(--blue-mid);
      background: var(--blue-light);
    }

    .secure-note {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 14px;
      justify-content: center;
    }

    /* ── Success state ── */
    .success-card {
      background: var(--white);
      border: 1px solid var(--green-border);
      border-radius: var(--radius-md);
      padding: 48px 40px;
      text-align: center;
      box-shadow: 0 4px 24px rgba(5,150,105,0.08);
    }

    .success-icon { font-size: 64px; margin-bottom: 20px; display: block; }

    .success-card h2 {
      font-family: 'Sora', sans-serif;
      font-size: 24px;
      font-weight: 700;
      color: var(--green);
      margin-bottom: 10px;
    }

    .success-card p {
      font-size: 15px;
      color: var(--text-muted);
      margin-bottom: 28px;
    }

    @media (max-width: 680px) {
      header { padding: 14px 20px; }
      .page { padding: 0 16px 48px; }
      .payment-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<header>
  <a class="logo" href="home.php">
    <div class="logo-icon">🏥</div>
    MediSure
  </a>
  <div class="user-pill">
    <div class="user-avatar"><?php echo strtoupper(substr(htmlspecialchars($username), 0, 1)); ?></div>
    <?php echo htmlspecialchars($username); ?>
  </div>
</header>

<div class="page">

  <div class="breadcrumb">
    <a href="home.php">🏠 Home</a>
    <span>›</span>
    <a href="buy.php">📋 All Plans</a>
    <span>›</span>
    <?php if ($selectedPlan): ?>
      <a href="buy.php?plan_id=<?php echo (int)$selectedPlan['id']; ?>">🛒 <?php echo htmlspecialchars($selectedPlan['name']); ?></a>
      <span>›</span>
    <?php endif; ?>
    <span>💳 Payment</span>
  </div>

  <?php if (!empty($success)): ?>

    <div class="success-card">
      <span class="success-icon">✅</span>
      <h2>Payment Successful!</h2>
      <p><?php echo htmlspecialchars($success); ?></p>
      <a href="home.php" class="btn" style="max-width:240px;margin:0 auto">🏠 Return Home</a>
    </div>

  <?php elseif ($selectedPlan): ?>

    <h1 class="page-title">💳 Payment</h1>
    <p class="page-subtitle">Complete your purchase securely. Your card details are never stored.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <span>⚠️</span>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="payment-grid">

      <!-- Payment form -->
      <div class="form-card">
        <div class="form-card-header">
          <h2>💳 Card Details</h2>
        </div>
        <div class="form-card-body">
          <form method="POST" action="payment.php" autocomplete="on">
            <input type="hidden" name="plan_id" value="<?php echo (int)$selectedPlan['id']; ?>">

            <div class="field">
              <label for="card_name">Cardholder Name</label>
              <input type="text" id="card_name" name="card_name" placeholder="Name as on card"
                     autocomplete="cc-name" required
                     value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
            </div>

            <div class="field">
              <label for="card_number">Card Number</label>
              <div class="card-icons">
                <span class="card-icon">💳</span>
                <span style="font-size:12px;color:var(--text-muted);align-self:center">Visa / Mastercard / RuPay</span>
              </div>
              <input type="tel" id="card_number" name="card_number"
                     inputmode="numeric" placeholder="1234 5678 9012 3456"
                     autocomplete="cc-number" maxlength="19" required
                     value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>"
                     oninput="this.value=this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()">
            </div>

            <div class="field-row">
              <div class="field">
                <label for="expiry">Expiry Date</label>
                <input type="text" id="expiry" name="expiry" placeholder="MM/YY"
                       autocomplete="cc-exp" maxlength="5" required
                       value="<?php echo htmlspecialchars($_POST['expiry'] ?? ''); ?>"
                       oninput="if(this.value.length==2&&!this.value.includes('/'))this.value+='/'">
              </div>
              <div class="field">
                <label for="cvv">CVV</label>
                <input type="tel" id="cvv" name="cvv"
                       inputmode="numeric" placeholder="123"
                       autocomplete="cc-csc" maxlength="4" required
                       value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
              </div>
            </div>

            <button class="btn" type="submit">
              Pay <?php echo fmt_inr($selectedPlan['price']); ?> →
            </button>

          </form>

          <a class="btn-ghost" href="buy.php?plan_id=<?php echo (int)$selectedPlan['id']; ?>">← Back to Plan</a>

  
        </div>
      </div>

      <!-- Order summary -->
      <div class="summary-card">
        <div class="summary-header">
          <span class="s-emoji"><?php echo plan_emoji($selectedPlan['type'], $typeEmoji); ?></span>
          <div class="s-provider"><?php echo htmlspecialchars($selectedPlan['provider_name'] ?? ''); ?></div>
          <div class="s-name"><?php echo htmlspecialchars($selectedPlan['name']); ?></div>
        </div>
        <div class="summary-body">
          <div class="summary-row">
            <span class="s-label">Plan Type</span>
            <span class="s-val"><?php echo htmlspecialchars(ucfirst($selectedPlan['type'] ?? '')); ?></span>
          </div>
          <div class="summary-row">
            <span class="s-label">Claim Amount</span>
            <span class="s-val green"><?php echo fmt_inr($selectedPlan['claim_amount']); ?></span>
          </div>
          <div class="summary-row">
            <span class="s-label">Billing Cycle</span>
            <span class="s-val">Annual</span>
          </div>
          <div class="summary-total">
            <span class="t-label">Yearly Premium</span>
            <span class="t-val"><?php echo fmt_inr($selectedPlan['price']); ?></span>
          </div>
        </div>
      </div>

    </div>

  <?php else: ?>

    <div class="alert alert-error">
      <span>⚠️</span>
      <span>No plan selected. <a href="buy.php" style="color:inherit;font-weight:700">Browse all plans →</a></span>
    </div>

  <?php endif; ?>

</div>

</body>
</html>