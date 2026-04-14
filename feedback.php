<?php
include "config.php";

$success = "";
$error = "";

// Optional: get logged-in user
$user_id = null;
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['id'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $rating  = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $message = trim($_POST['feedback']);

    if (empty($message)) {
      $error = "Feedback cannot be empty!";
    } else {
      if ($user_id !== null) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, rating, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $rating, $message);
      } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, rating, message) VALUES (NULL, ?, ?)");
        $stmt->bind_param("is", $rating, $message);
      }

      if ($stmt) {
        if ($stmt->execute()) {
          $success = "Thank you for your feedback!";
        } else {
          $error = "Something went wrong: " . $stmt->error;
        }
        $stmt->close();
      } else {
        $error = "Failed to prepare statement: " . $conn->error;
      }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback — MediSure</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #0f1f3d 0%, #1a3a6b 50%, #1e4a8a 100%);
  padding: 40px 16px;
  position: relative;
  overflow: hidden;
}

body::before {
  content: '';
  position: absolute;
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(37,99,235,.15) 0%, transparent 70%);
  bottom: -150px; left: -100px;
  pointer-events: none;
}

.card {
  background: rgba(255,255,255,.97);
  border-radius: 20px;
  padding: 44px 40px;
  width: 400px;
  box-shadow: 0 24px 64px rgba(0,0,0,.3);
  animation: rise .5s cubic-bezier(.34,1.3,.64,1) both;
  position: relative; z-index: 1;
}

@keyframes rise {
  from { opacity:0; transform:translateY(28px) scale(.97); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

.brand {
  font-family: 'Playfair Display', serif;
  font-size: 13px;
  color: #2563eb;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-bottom: 6px;
}

h2 {
  font-size: 26px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 6px;
}

.subtitle {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 28px;
}

/* Star Rating */
.rating-row {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  flex-direction: row-reverse;
  justify-content: flex-end;
}

.rating-row input { display: none; }

.rating-row label {
  font-size: 28px;
  cursor: pointer;
  color: #e2e8f0;
  transition: color .15s, transform .15s;
  text-transform: none;
  letter-spacing: 0;
  font-weight: 400;
  margin: 0;
}

.rating-row label:hover,
.rating-row label:hover ~ label,
.rating-row input:checked ~ label {
  color: #f59e0b;
}

.rating-row label:hover { transform: scale(1.15); }

.field {
  margin-bottom: 18px;
}

label.text-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  letter-spacing: .6px;
  text-transform: uppercase;
  margin-bottom: 7px;
}

.char-count {
  font-size: 11.5px;
  color: #94a3b8;
  text-align: right;
  margin-top: 4px;
}

textarea {
  width: 100%;
  padding: 13px 16px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  color: #0f172a;
  background: #f8fafc;
  outline: none;
  min-height: 130px;
  resize: vertical;
  transition: border-color .2s, box-shadow .2s, background .2s;
}

textarea:focus {
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}

textarea::placeholder { color: #94a3b8; }

.btn {
  width: 100%;
  padding: 14px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .15s, box-shadow .15s;
  box-shadow: 0 4px 14px rgba(37,99,235,.4);
  letter-spacing: .3px;
}

.btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.45); }
.btn:active { transform: translateY(0); }

.back-home {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  color: #64748b;
  text-decoration: none;
  margin-bottom: 20px;
  transition: color .2s;
}
.back-home:hover { color: #2563eb; }
</style>
</head>
<body>

<div class="card">
  <a href="home.html" class="back-home">← Back to Home</a>
  <div class="brand">MediSure</div>
  <h2>Share Feedback</h2>
  <p class="subtitle">We'd love to hear your thoughts.</p>
  <?php if (!empty($success)) : ?>
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #bbf7d0;"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if (!empty($error)) : ?>
    <div style="background:#fff1f2;color:#9f1239;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #fecaca;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form id="feedbackForm" method="POST" action="feedback.php" novalidate>
    <div class="field">
      <label class="text-label">Rate your experience</label>
      <div class="rating-row">
        <input type="radio" id="s5" name="rating" value="5">
        <label for="s5" title="5 stars">★</label>
        <input type="radio" id="s4" name="rating" value="4">
        <label for="s4" title="4 stars">★</label>
        <input type="radio" id="s3" name="rating" value="3">
        <label for="s3" title="3 stars">★</label>
        <input type="radio" id="s2" name="rating" value="2">
        <label for="s2" title="2 stars">★</label>
        <input type="radio" id="s1" name="rating" value="1">
        <label for="s1" title="1 star">★</label>
      </div>
    </div>

    <div class="field">
      <label class="text-label" for="feedback">Your feedback</label>
      <textarea id="feedback" name="feedback" placeholder="Tell us what you think, what we can improve, or what you loved..."></textarea>
      <div class="char-count"><span id="charCount">0</span> characters</div>
    </div>

    <button type="submit" class="btn">Submit Feedback</button>
  </form>
</div>

<script src="validation.js"></script>
<script>
  const form     = document.getElementById('feedbackForm');
  const textarea = document.getElementById('feedback');
  const counter  = document.getElementById('charCount');

  textarea.addEventListener('input', () => {
    counter.textContent = textarea.value.length;
  });

  Validator.attachLiveValidation(form);

  form.addEventListener('submit', function(e) {
  if (!Validator.validateFeedback(form)) {
    e.preventDefault(); // only stop if invalid
  }
});
</script>
</body>
</html>