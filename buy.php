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

// If plan_id provided in GET, show confirmation page
$selectedPlan = null;
if (isset($_GET['plan_id'])) {
    $pid = intval($_GET['plan_id']);
    if ($pid > 0) {
        $q = $conn->prepare("SELECT p.id, p.name, p.price, p.claim_amount, p.description, p.type, pr.name AS provider_name
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
}

// Helper: format INR
function fmt_inr($num) {
    if ($num === null || $num === '') return 'Custom Pricing';
    $num = (int)$num;
    // Indian number formatting: last 3 digits then groups of 2
    $str = (string)$num;
    if (strlen($str) <= 3) return '₹' . $str;
    $last3 = substr($str, -3);
    $rest  = substr($str, 0, -3);
    $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
    return '₹' . $rest . ',' . $last3;
}


$typeEmoji = [
    'individual'    => '👤',
    'senior'        => '👴',
    'corporate'     => '🏢',
    'critical illness' => '❤️‍🩹',
    'comprehensive' => '🌟',
    'travel'        => '✈️',
    'pet'           => '🐾',
    'family'        => '👨‍👩‍👧‍👦',
];

function plan_emoji($type, $map) {
    $t = strtolower(trim($type));
    foreach ($map as $k => $e) {
        if (str_contains($t, $k)) return $e;
    }
    return '🛡️';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $selectedPlan ? 'Confirm Purchase' : 'All Plans'; ?> — MediSure</title>
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

    /* ── Page ── */
    .page {
      max-width: 820px;
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

    /* ── Confirm card ── */
    .confirm-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(15,42,107,0.08);
    }

    .confirm-card-header {
      background: linear-gradient(135deg, #0f2a6b, #1d4ed8);
      padding: 32px 36px;
      color: white;
    }

    .confirm-card-header .plan-emoji { font-size: 44px; margin-bottom: 14px; display: block; }

    .confirm-card-header .provider-badge {
      display: inline-block;
      background: rgba(255,255,255,0.18);
      border-radius: 20px;
      padding: 3px 12px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .confirm-card-header h2 {
      font-family: 'Sora', sans-serif;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .confirm-card-header p { font-size: 14px; opacity: 0.8; line-height: 1.6; }

    .confirm-card-body { padding: 32px 36px; }

    .amounts-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 28px;
    }

    .amount-box {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 18px 20px;
    }

    .amount-box.highlight {
      background: var(--blue-light);
      border-color: var(--blue-pale);
    }

    .amount-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .amount-value {
      font-family: 'Sora', sans-serif;
      font-size: 22px;
      font-weight: 700;
      color: var(--blue-mid);
    }

    .amount-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 3px;
    }

    .confirm-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    /* ── Plans list ── */
    /* Filter bar */
    .filter-bar {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 24px;
    }

    .filter-btn {
      padding: 6px 16px;
      border-radius: 20px;
      border: 1.5px solid var(--border);
      background: var(--white);
      color: var(--text-muted);
      font-family: 'Outfit', sans-serif;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.18s;
    }

    .filter-btn:hover,
    .filter-btn.active {
      background: var(--blue-mid);
      border-color: var(--blue-mid);
      color: white;
    }

    .plans-list {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .plan-row {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 18px;
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
      transition: opacity 0.2s;
    }

    .plan-row:hover {
      border-color: var(--blue-pale);
      box-shadow: 0 8px 24px rgba(29,78,216,0.10);
      transform: translateY(-2px);
    }

    .plan-row:hover::before { opacity: 1; }

    .plan-emoji-col { font-size: 30px; flex-shrink: 0; }

    .plan-info { flex: 1; min-width: 0; }

    .plan-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
    }

    .plan-type-badge {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      color: var(--blue-mid);
      background: var(--blue-light);
      padding: 2px 8px;
      border-radius: 10px;
    }

    .plan-provider {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .plan-name {
      font-family: 'Sora', sans-serif;
      font-size: 15px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .plan-desc {
      font-size: 13px;
      color: var(--text-muted);
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .plan-price-col {
      text-align: right;
      flex-shrink: 0;
      min-width: 140px;
    }

    .plan-premium-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 3px;
    }

    .plan-premium {
      font-family: 'Sora', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--blue-mid);
      margin-bottom: 2px;
    }

    .plan-premium-sub {
      font-size: 11px;
      color: var(--text-muted);
      margin-bottom: 10px;
    }

    .plan-claim-label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 2px;
    }

    .plan-claim {
      font-size: 13px;
      font-weight: 600;
      color: #059669;
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

    @media (max-width: 640px) {
      header { padding: 14px 20px; }
      .page { padding: 0 16px 48px; }
      .plan-row { flex-wrap: wrap; }
      .plan-price-col { min-width: 100%; text-align: left; margin-top: 12px; }
      .amounts-row { grid-template-columns: 1fr; }
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
    <?php if ($selectedPlan): ?>
      <a href="buy.php">📋 All Plans</a>
      <span>›</span>
      <span>🛒 Confirm Purchase</span>
    <?php else: ?>
      <span>📋 All Plans</span>
    <?php endif; ?>
  </div>

  <?php if ($selectedPlan): ?>

    <h1 class="page-title">🛒 Confirm Your Purchase</h1>
    <p class="page-subtitle">Review the plan details and proceed to payment.</p>

    <div class="confirm-card">
      <div class="confirm-card-header">
        <span class="plan-emoji"><?php echo plan_emoji($selectedPlan['type'], $typeEmoji); ?></span>
        <div class="provider-badge"><?php echo htmlspecialchars($selectedPlan['provider_name'] ?? $selectedPlan['type']); ?></div>
        <h2><?php echo htmlspecialchars($selectedPlan['name']); ?></h2>
        <p><?php echo htmlspecialchars($selectedPlan['description']); ?></p>
      </div>
      <div class="confirm-card-body">
        <div class="amounts-row">
          <div class="amount-box highlight">
            <div class="amount-label">💳 Yearly Premium</div>
            <div class="amount-value"><?php echo fmt_inr($selectedPlan['price']); ?></div>
            <div class="amount-sub">Billed annually</div>
          </div>
          <div class="amount-box">
            <div class="amount-label">🏥 Claim Amount</div>
            <div class="amount-value" style="color:#059669"><?php echo fmt_inr($selectedPlan['claim_amount']); ?></div>
            <div class="amount-sub">Sum insured</div>
          </div>
        </div>
        <div class="confirm-actions">
          <a class="btn" href="payment.php?plan_id=<?php echo (int)$selectedPlan['id']; ?>">Proceed to Payment →</a>
          <a class="btn-ghost" href="buy.php">← Back to Plans</a>
        </div>
      </div>
    </div>

  <?php else: ?>

    <h1 class="page-title">📋 Choose a Plan</h1>
    <p class="page-subtitle">Browse all available insurance plans. Click any plan to view details and purchase.</p>

    <?php
      // Collect distinct types for filter buttons
      $types_res = $conn->query("SELECT DISTINCT type FROM plans ORDER BY type");
      $all_types = [];
      while ($t = $types_res->fetch_assoc()) $all_types[] = $t['type'];
    ?>

    <div class="filter-bar">
      <button class="filter-btn active" onclick="filterPlans('all', this)">All</button>
      <?php foreach ($all_types as $t): ?>
        <button class="filter-btn" onclick="filterPlans('<?php echo htmlspecialchars($t); ?>', this)">
          <?php echo plan_emoji($t, $typeEmoji) . ' ' . htmlspecialchars(ucfirst($t)); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="plans-list" id="plansList">
      <?php
        $res = $conn->query("SELECT p.id, p.name, p.price, p.claim_amount, p.description, p.type,
                                    pr.name AS provider_name
                             FROM plans p
                             LEFT JOIN providers pr ON pr.id = p.provider_id
                             ORDER BY p.type, p.id");
        while ($row = $res->fetch_assoc()):
          $emoji   = plan_emoji($row['type'], $typeEmoji);
          $premium = $row['price'] ? fmt_inr($row['price']) : 'Custom Pricing';
          $claim   = fmt_inr($row['claim_amount']);
      ?>
        <div class="plan-row" data-type="<?php echo htmlspecialchars($row['type']); ?>">
          <div class="plan-emoji-col"><?php echo $emoji; ?></div>
          <div class="plan-info">
            <div class="plan-meta">
              <span class="plan-type-badge"><?php echo htmlspecialchars($row['type']); ?></span>
              <span class="plan-provider"><?php echo htmlspecialchars($row['provider_name'] ?? ''); ?></span>
            </div>
            <div class="plan-name"><?php echo htmlspecialchars($row['name']); ?></div>
            <div class="plan-desc"><?php echo htmlspecialchars($row['description']); ?></div>
          </div>
          <div class="plan-price-col">
            <div class="plan-premium-label">Yearly Premium</div>
            <div class="plan-premium"><?php echo $premium; ?></div>
            <div class="plan-premium-sub"><?php echo $row['price'] ? 'per year' : 'contact for quote'; ?></div>
            <div class="plan-claim-label">Claim Amount</div>
            <div class="plan-claim"><?php echo $claim; ?></div>
            <a href="buy.php?plan_id=<?php echo (int)$row['id']; ?>" class="btn">Buy Now →</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

  <?php endif; ?>

</div>

<script>
function filterPlans(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.plan-row').forEach(row => {
    row.style.display = (type === 'all' || row.dataset.type === type) ? 'flex' : 'none';
  });
}
</script>

</body>
</html>