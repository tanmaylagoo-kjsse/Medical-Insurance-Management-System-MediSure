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

$success = '';
$error = '';

// Handle purchase POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;

    if ($plan_id <= 0) {
        $error = 'Invalid plan selected.';
    } else {
        $pstmt = $conn->prepare("SELECT id, name, price FROM plans WHERE id=?");
        if ($pstmt) {
            $pstmt->bind_param("i", $plan_id);
            $pstmt->execute();
            $pres = $pstmt->get_result();
            if ($plan = $pres->fetch_assoc()) {
                $priceStr = $plan['price'];
                // Extract numeric value from price string (e.g. "₹3,000/year")
                $num = preg_replace('/[^0-9\.\,]/', '', $priceStr);
                $num = str_replace(',', '', $num);
                $amount = floatval($num);

                $ins = $conn->prepare("INSERT INTO purchases (user_id, plan_id, purchase_amount) VALUES (?, ?, ?)");
                if ($ins) {
                    $ins->bind_param("iid", $user_id, $plan_id, $amount);
                    if ($ins->execute()) {
                        $success = 'Purchase completed — thank you!';
                    } else {
                        $error = 'Failed to record purchase: ' . $ins->error;
                    }
                    $ins->close();
                } else {
                    $error = 'Failed to prepare insert: ' . $conn->error;
                }

            } else {
                $error = 'Plan not found.';
            }
            $pstmt->close();
        } else {
            $error = 'Failed to retrieve plan: ' . $conn->error;
        }
    }
}

// If plan_id provided in GET, show confirmation; otherwise list plans
$selectedPlan = null;
if (isset($_GET['plan_id'])) {
    $pid = intval($_GET['plan_id']);
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
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Purchase Plan — MediSure</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue-dark: #0f2a6b;
      --blue-mid: #1d4ed8;
      --blue-bright: #3b82f6;
      --blue-light: #eff6ff;
      --blue-pale: #dbeafe;
      --text-primary: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --white: #ffffff;
      --surface: #f8fafc;
      --radius-md: 16px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--surface);
      color: var(--text-primary);
      min-height: 100vh;
      padding: 0;
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

    .header-right {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--text-muted);
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

    /* ── Page wrapper ── */
    .page {
      max-width: 780px;
      margin: 48px auto;
      padding: 0 24px 64px;
    }

    /* ── Breadcrumb ── */
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

    /* ── Section title ── */
    .page-title {
      font-family: 'Sora', sans-serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--text-primary);
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
      padding: 14px 18px;
      border-radius: var(--radius-md);
      margin-bottom: 24px;
      font-size: 14.5px;
      font-weight: 500;
    }

    .alert-success {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .alert-error {
      background: #fff1f2;
      color: #9f1239;
      border: 1px solid #fecaca;
    }

    .alert-icon { font-size: 18px; flex-shrink: 0; }

    /* ── Confirmation card ── */
    .confirm-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
    }

    .confirm-card-header {
      background: linear-gradient(135deg, #0f2a6b, #1d4ed8);
      padding: 28px 32px;
      color: white;
    }

    .confirm-card-header .plan-emoji { font-size: 40px; margin-bottom: 12px; display: block; }

    .confirm-card-header h2 {
      font-family: 'Sora', sans-serif;
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .confirm-card-header p {
      font-size: 14px;
      opacity: 0.8;
    }

    .confirm-card-body {
      padding: 28px 32px;
    }

    .price-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: var(--blue-light);
      border-radius: 12px;
      margin-bottom: 28px;
    }

    .price-label {
      font-size: 14px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .price-value {
      font-family: 'Sora', sans-serif;
      font-size: 22px;
      font-weight: 700;
      color: var(--blue-mid);
    }

    .confirm-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    /* ── Plans list ── */
    .plans-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .plan-row {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 22px 24px;
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.22s ease;
      position: relative;
      overflow: hidden;
    }

    .plan-row::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 4px;
      background: linear-gradient(180deg, var(--blue-mid), var(--blue-bright));
      opacity: 0;
      transition: opacity 0.22s;
    }

    .plan-row:hover {
      border-color: var(--blue-pale);
      box-shadow: 0 8px 24px rgba(29,78,216,0.10);
      transform: translateY(-2px);
    }

    .plan-row:hover::before { opacity: 1; }

    .plan-emoji-col { font-size: 32px; flex-shrink: 0; }

    .plan-info { flex: 1; }

    .plan-name {
      font-family: 'Sora', sans-serif;
      font-size: 16px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 4px;
    }

    .plan-desc {
      font-size: 13.5px;
      color: var(--text-muted);
      line-height: 1.5;
    }

    .plan-price-col {
      text-align: right;
      flex-shrink: 0;
    }

    .plan-price {
      font-family: 'Sora', sans-serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--blue-mid);
      margin-bottom: 10px;
    }

    /* ── Buttons ── */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 22px;
      background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
      color: white;
      border: none;
      border-radius: 22px;
      cursor: pointer;
      font-family: 'Outfit', sans-serif;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      box-shadow: 0 4px 12px rgba(29,78,216,0.25);
    }

    .btn:hover {
      box-shadow: 0 6px 18px rgba(29,78,216,0.35);
      transform: translateY(-1px);
    }

    .btn:active { transform: translateY(0); }

    .btn-ghost {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 20px;
      background: transparent;
      color: var(--text-muted);
      border: 1.5px solid var(--border);
      border-radius: 22px;
      cursor: pointer;
      font-family: 'Outfit', sans-serif;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s;
    }

    .btn-ghost:hover {
      border-color: var(--blue-pale);
      color: var(--blue-mid);
      background: var(--blue-light);
    }

    /* ── Plan emoji map ── */
    .plan-emojis { display: none; }
  </style>
</head>
<body>

<header>
  <a class="logo" href="home.php">
    <div class="logo-icon">🏥</div>
    MediSure
  </a>
  <div class="header-right">
    <div class="user-pill">
      <div class="user-avatar"><?php echo strtoupper(substr(htmlspecialchars($username), 0, 1)); ?></div>
      <?php echo htmlspecialchars($username); ?>
    </div>
  </div>
</header>

<div class="page">

  <div class="breadcrumb">
    <a href="home.php">🏠 Home</a>
    <span>›</span>
    <span><?php echo $selectedPlan ? '🛒 Confirm Purchase' : '📋 All Plans'; ?></span>
  </div>

  <h1 class="page-title"><?php echo $selectedPlan ? '🛒 Confirm Your Purchase' : '📋 Choose a Plan'; ?></h1>
  <p class="page-subtitle">
    <?php echo $selectedPlan
      ? 'Review the plan details below and confirm your purchase.'
      : 'Select the insurance plan that best fits your needs.';
    ?>
  </p>

  <?php if (!empty($success)) : ?>
    <div class="alert alert-success">
      <span class="alert-icon">✅</span>
      <span><?php echo htmlspecialchars($success); ?></span>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)) : ?>
    <div class="alert alert-error">
      <span class="alert-icon">⚠️</span>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
  <?php endif; ?>

  <?php if ($selectedPlan): ?>

    <div class="confirm-card">
      <div class="confirm-card-header">
        <span class="plan-emoji">🌟</span>
        <h2><?php echo htmlspecialchars($selectedPlan['name']); ?></h2>
        <p><?php echo htmlspecialchars($selectedPlan['description']); ?></p>
      </div>
      <div class="confirm-card-body">
        <div class="price-row">
          <span class="price-label">💳 Annual Premium</span>
          <span class="price-value"><?php echo htmlspecialchars($selectedPlan['price']); ?></span>
        </div>
        <form method="POST" action="buy.php">
          <input type="hidden" name="plan_id" value="<?php echo (int)$selectedPlan['id']; ?>">
          <div class="confirm-actions">
            <button class="btn" type="submit">✅ Confirm Purchase</button>
            <a class="btn-ghost" href="buy.php">← Back to Plans</a>
          </div>
        </form>
      </div>
    </div>

  <?php else: ?>

    <div class="plans-list">
      <?php
        $planEmojis = [
          'individual' => '👤',
          'senior'     => '👴',
          'corporate'  => '🏢',
          'critical'   => '❤️‍🩹',
          'comprehensive' => '🌟',
          'travel'     => '✈️',
          'pet'        => '🐾',
          'family'     => '👨‍👩‍👧‍👦',
        ];

        $res = $conn->query("SELECT id, name, price, description FROM plans ORDER BY id");
        while ($row = $res->fetch_assoc()):
          $nameLower = strtolower($row['name']);
          $emoji = '🛡️';
          foreach ($planEmojis as $key => $e) {
            if (str_contains($nameLower, $key)) { $emoji = $e; break; }
          }
      ?>
        <div class="plan-row">
          <div class="plan-emoji-col"><?php echo $emoji; ?></div>
          <div class="plan-info">
            <div class="plan-name"><?php echo htmlspecialchars($row['name']); ?></div>
            <div class="plan-desc"><?php echo htmlspecialchars($row['description']); ?></div>
          </div>
          <div class="plan-price-col">
            <div class="plan-price"><?php echo htmlspecialchars($row['price']); ?></div>
            <a href="buy.php?plan_id=<?php echo (int)$row['id']; ?>" class="btn">Buy Now →</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

  <?php endif; ?>

</div>

</body>
</html>