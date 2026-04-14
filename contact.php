<?php
include "config.php";

$messageSent = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
  $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  if (empty($name) || empty($email) || empty($message)) {
    $error = "All fields are required!";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $error = "Please provide a valid email address.";

  } else {

    // ✅ Check if user is logged in
    if (!isset($_SESSION['username'])) {
        $error = "You must be logged in to send a message.";
    } else {

        // ✅ Get user_id from username
        $username = $_SESSION['username'];
        $stmt_user = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $result = $stmt_user->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            $error = "User not found.";
        } else {

            $user_id = $user['id'];

            // ✅ Fixed insert
            $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, message) VALUES (?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("isss", $user_id, $name, $email, $message);
                if ($stmt->execute()) {
                    $messageSent = "Message sent successfully!";
                } else {
                    $error = "Something went wrong: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Failed to prepare statement: " . $conn->error;
            }
        }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact — MediSure</title>
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
  top: -150px; right: -150px;
  pointer-events: none;
}

.card {
  background: rgba(255,255,255,.97);
  border-radius: 20px;
  padding: 44px 40px;
  width: 420px;
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
  margin-bottom: 24px;
}

.contact-info {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.info-pill {
  flex: 1;
  background: #f0f7ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  padding: 12px;
  font-size: 12.5px;
  color: #1d4ed8;
  font-weight: 500;
}

.info-pill span {
  display: block;
  font-size: 11px;
  color: #64748b;
  font-weight: 400;
  margin-bottom: 2px;
  text-transform: uppercase;
  letter-spacing: .5px;
}

.divider {
  height: 1px;
  background: #e2e8f0;
  margin-bottom: 24px;
}

.field {
  margin-bottom: 16px;
}

label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  letter-spacing: .6px;
  text-transform: uppercase;
  margin-bottom: 7px;
}

input, textarea {
  width: 100%;
  padding: 13px 16px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  color: #0f172a;
  background: #f8fafc;
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .2s;
}

textarea {
  min-height: 110px;
  resize: vertical;
}

input:focus, textarea:focus {
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}

input::placeholder, textarea::placeholder { color: #94a3b8; }

.btn {
  width: 100%;
  padding: 14px;
  margin-top: 4px;
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
  <a href="home.php" class="back-home">← Back to Home</a>
  <div class="brand">MediSure</div>
  <h2>Contact Us</h2>
  <p class="subtitle">We're here to help. Reach out anytime.</p>

  <?php if (!empty($messageSent)) : ?>
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #bbf7d0;"><?php echo htmlspecialchars($messageSent); ?></div>
  <?php endif; ?>
  <?php if (!empty($error)) : ?>
    <div style="background:#fff1f2;color:#9f1239;padding:10px;border-radius:8px;margin:12px 0;border:1px solid #fecaca;"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="contact-info">
    <div class="info-pill">
      <span>Email</span>
      📧 support@medisure.com
    </div>
    <div class="info-pill">
      <span>Phone</span>
      📞 +91 1234567890
    </div>
  </div>

  <div class="divider"></div>

  <form id="contactForm" method="POST" action="contact.php" novalidate>
    <div class="field">
      <label for="name">Full Name</label>
      <input id="name" name="name" type="text" placeholder="Your name">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="you@example.com">
    </div>

    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" placeholder="Write your message here..."></textarea>
    </div>

    <button type="submit" class="btn">Send Message</button>
  </form>
</div>

<script src="validation.js"></script>
<script>
  const form = document.getElementById('contactForm');
  Validator.attachLiveValidation(form);

  form.addEventListener('submit', function(e) {
    if (!Validator.validateContact(form)) {
      e.preventDefault(); // stop submission only when client-side validation fails
    }
  });
</script>
</body>
</html>