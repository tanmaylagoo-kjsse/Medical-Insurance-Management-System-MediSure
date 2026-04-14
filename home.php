<?php
include "config.php";

// 🔐 Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediSure</title>

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
  --radius-sm: 10px;
  --radius-md: 16px;
  --radius-lg: 24px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  font-family: 'Outfit', sans-serif;
  background: var(--surface);
  color: var(--text-primary);
}

body { display: flex; flex-direction: column; }
main { flex: 1; }

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
}

.logo-icon {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
}

nav { display: flex; align-items: center; gap: 6px; }

nav a {
  text-decoration: none;
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  padding: 8px 14px;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}

nav a:hover { background: var(--blue-light); color: var(--blue-mid); }

.user-greeting {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  background: var(--blue-light);
  border-radius: 22px;
  font-size: 14px;
  font-weight: 600;
  color: var(--blue-mid);
}

.user-avatar {
  width: 26px; height: 26px;
  background: linear-gradient(135deg, var(--blue-mid), var(--blue-bright));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white;
  font-size: 12px;
  font-weight: 700;
}

.btn-logout {
  padding: 8px 20px;
  border-radius: 22px;
  cursor: pointer;
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 600;
  border: 1.5px solid #e2e8f0;
  color: #64748b;
  background: white;
  transition: all 0.2s;
}
.btn-logout:hover { border-color: #ef4444; color: #ef4444; background: #fff5f5; }

/* ── Hero ── */
.hero {
  background: linear-gradient(135deg, #0f2a6b 0%, #1d4ed8 50%, #3b82f6 100%);
  color: white;
  text-align: center;
  padding: 80px 24px 90px;
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 360px; height: 360px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
}

.hero::after {
  content: '';
  position: absolute;
  bottom: -60px; left: -60px;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: rgba(255,255,255,0.04);
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 20px;
}

.hero h1 {
  font-family: 'Sora', sans-serif;
  font-size: clamp(28px, 5vw, 48px);
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 16px;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.hero p {
  font-size: 17px;
  opacity: 0.85;
  margin-bottom: 36px;
  font-weight: 300;
}

.search-box {
  display: flex;
  justify-content: center;
  gap: 0;
  max-width: 560px;
  margin: 0 auto;
}

.search-box input {
  flex: 1;
  padding: 14px 20px;
  border-radius: 14px 0 0 14px;
  border: none;
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  background: rgba(255,255,255,0.96);
  color: var(--text-primary);
  outline: none;
}

.search-box input::placeholder { color: #94a3b8; }

.search-box button {
  padding: 14px 24px;
  background: #0f2a6b;
  color: white;
  border: none;
  border-radius: 0 14px 14px 0;
  cursor: pointer;
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  font-weight: 600;
  transition: background 0.2s;
  white-space: nowrap;
}
.search-box button:hover { background: #172f7a; }

.hero-stats {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-top: 48px;
  position: relative;
  z-index: 1;
}

.stat { text-align: center; }
.stat-num { font-family: 'Sora', sans-serif; font-size: 26px; font-weight: 700; }
.stat-label { font-size: 12px; opacity: 0.7; margin-top: 4px; letter-spacing: 0.5px; text-transform: uppercase; }

/* ── Plans section ── */
.section {
  padding: 56px 48px;
}

.section-header {
  text-align: center;
  margin-bottom: 40px;
}

.section-header h2 {
  font-family: 'Sora', sans-serif;
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.section-header p {
  color: var(--text-muted);
  font-size: 15px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  max-width: 1100px;
  margin: 0 auto;
}

.card {
  background: var(--white);
  padding: 28px 24px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border);
  transition: all 0.25s ease;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--blue-mid), var(--blue-bright));
  opacity: 0;
  transition: opacity 0.25s;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 32px rgba(29,78,216,0.12);
  border-color: var(--blue-pale);
}

.card:hover::before { opacity: 1; }

.card-emoji {
  font-size: 36px;
  margin-bottom: 14px;
  display: block;
  line-height: 1;
}

.card h3 {
  font-family: 'Sora', sans-serif;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.card p {
  font-size: 13.5px;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 16px;
}

.card-cta {
  font-size: 13px;
  font-weight: 600;
  color: var(--blue-mid);
  display: flex;
  align-items: center;
  gap: 4px;
}

/* ── Trust bar ── */
.trust-bar {
  background: var(--blue-light);
  border-top: 1px solid var(--blue-pale);
  border-bottom: 1px solid var(--blue-pale);
  padding: 20px 48px;
  display: flex;
  justify-content: center;
  gap: 48px;
  flex-wrap: wrap;
}

.trust-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  font-weight: 500;
  color: var(--blue-dark);
}

/* ── Footer ── */
footer {
  background: #0f172a;
  color: #94a3b8;
  text-align: center;
  padding: 28px 20px;
  font-size: 13.5px;
}

footer a { color: #64748b; text-decoration: none; margin: 0 8px; }
footer a:hover { color: #94a3b8; }
</style>
</head>

<body>

<header>
  <div class="logo">
    <div class="logo-icon">🏥</div>
    MediSure
  </div>

  <nav>
    <a href="contact.php">📞 Contact Us</a>
    <a href="feedback.php">💬 Feedback</a>

    <div class="user-greeting">
      <div class="user-avatar"><?php echo strtoupper(substr(htmlspecialchars($username), 0, 1)); ?></div>
      Hi, <?php echo htmlspecialchars($username); ?> 👋
    </div>

    <button class="btn-logout" onclick="location.href='logout.php'">Logout 🚪</button>
  </nav>
</header>

<main>

<section class="hero">
  <div class="hero-badge">✨ Welcome back, <?php echo htmlspecialchars($username); ?>!</div>
  <h1>Comprehensive Health Coverage<br>for Your Future 🛡️</h1>
  <p>Access world-class medical insurance plans — simple, fast, and affordable.</p>

  <div class="search-box">
    <input type="text" id="searchInput" placeholder="🔍 Search plans — Individual, Family, Senior...">
    <button onclick="searchPlans()">Search Plans</button>
  </div>

  
</section>

<section class="section">
  <div class="section-header">
    <h2>Find the Right Plan for You</h2>
    <p>Choose from 8 carefully designed insurance plans to match every lifestyle and budget.</p>
  </div>

  <div class="grid" id="plansGrid">

    <div class="card" onclick="location.href='individual.html'">
      <span class="card-emoji">👤</span>
      <h3>Individual Plans</h3>
      <p>Coverage tailored specifically to your personal health needs.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='senior.html'">
      <span class="card-emoji">👴</span>
      <h3>Senior Citizen Plans</h3>
      <p>Specialised healthcare coverage designed for seniors 60+.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='corporate.html'">
      <span class="card-emoji">🏢</span>
      <h3>Corporate Insurance</h3>
      <p>Flexible group plans built for teams and businesses of all sizes.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='critical.html'">
      <span class="card-emoji">❤️‍🩹</span>
      <h3>Critical Illness</h3>
      <p>Lump-sum protection against cancer, stroke, and serious diseases.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='comprehensive.html'">
      <span class="card-emoji">🌟</span>
      <h3>Comprehensive Health</h3>
      <p>All-in-one coverage for complete peace of mind.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='travel.html'">
      <span class="card-emoji">✈️</span>
      <h3>Travel Medical</h3>
      <p>Stay protected with global health coverage wherever you go.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='pet.html'">
      <span class="card-emoji">🐾</span>
      <h3>Pet Insurance</h3>
      <p>Quality veterinary care coverage for your beloved pets.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

    <div class="card" onclick="location.href='family.html'">
      <span class="card-emoji">👨‍👩‍👧‍👦</span>
      <h3>Family Floater</h3>
      <p>One smart policy that covers your entire family under one sum.</p>
      <div class="card-cta">Explore Plans →</div>
    </div>

  </div>
</section>

</main>

<footer>
  <p style="margin-bottom:10px;">© 2026 MediSure | All Rights Reserved 🛡️| Made By Malhar Kausadikar Anuj Vajha Tanmay Lagoo</p>
  <p>
    <a href="contact.php">Contact</a> ·
    <a href="feedback.php">Feedback</a> ·
    <a href="#">Privacy Policy</a> ·
    <a href="#">Terms of Use</a>
  </p>
</footer>

<script>
const searchInput = document.getElementById("searchInput");
const cards = document.querySelectorAll(".card");

searchInput.addEventListener("keyup", searchPlans);

function searchPlans() {
  const query = searchInput.value.toLowerCase();
  cards.forEach(card => {
    const text = card.innerText.toLowerCase();
    card.style.display = text.includes(query) ? "block" : "none";
  });
}
</script>

</body>
</html>