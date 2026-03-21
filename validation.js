// =============================================
//  MediSure — Form Validation Library
//  validation.js
// =============================================

const Validator = {

  // ── Helpers ──────────────────────────────────────────────

  showError(input, message) {
    const field = input.closest('.field') || input.parentElement;
    this.clearError(input);
    input.classList.add('is-invalid');
    const err = document.createElement('span');
    err.className = 'error-msg';
    err.textContent = message;
    field.appendChild(err);
  },

  clearError(input) {
    const field = input.closest('.field') || input.parentElement;
    const old = field.querySelector('.error-msg');
    if (old) old.remove();
    input.classList.remove('is-invalid');
    input.classList.remove('is-valid');
  },

  markValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
  },

  isEmpty(value) {
    return value.trim() === '';
  },

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  },

  isValidPhone(phone) {
    return /^[\+]?[\d\s\-\(\)]{7,15}$/.test(phone);
  },

  isStrongPassword(password) {
    return password.length >= 8;
  },

  // ── Per-form validators ───────────────────────────────────

  validateLogin(form) {
    let valid = true;
    const username = form.querySelector('input[name="username"]');
    const password = form.querySelector('input[name="password"]');

    if (this.isEmpty(username.value)) {
      this.showError(username, 'Username is required.');
      valid = false;
    } else {
      this.markValid(username);
    }

    if (this.isEmpty(password.value)) {
      this.showError(password, 'Password is required.');
      valid = false;
    } else if (!this.isStrongPassword(password.value)) {
      this.showError(password, 'Password must be at least 8 characters.');
      valid = false;
    } else {
      this.markValid(password);
    }

    return valid;
  },

  validateSignup(form) {
    let valid = true;
    const username = form.querySelector('input[name="username"]');
    const email    = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');
    const confirm  = form.querySelector('input[name="confirm"]');

    if (this.isEmpty(username.value)) {
      this.showError(username, 'Username is required.');
      valid = false;
    } else if (username.value.trim().length < 3) {
      this.showError(username, 'Username must be at least 3 characters.');
      valid = false;
    } else {
      this.markValid(username);
    }

    if (this.isEmpty(email.value)) {
      this.showError(email, 'Email is required.');
      valid = false;
    } else if (!this.isValidEmail(email.value)) {
      this.showError(email, 'Enter a valid email address.');
      valid = false;
    } else {
      this.markValid(email);
    }

    if (this.isEmpty(password.value)) {
      this.showError(password, 'Password is required.');
      valid = false;
    } else if (!this.isStrongPassword(password.value)) {
      this.showError(password, 'Password must be at least 8 characters.');
      valid = false;
    } else {
      this.markValid(password);
    }

    if (this.isEmpty(confirm.value)) {
      this.showError(confirm, 'Please confirm your password.');
      valid = false;
    } else if (confirm.value !== password.value) {
      this.showError(confirm, 'Passwords do not match.');
      valid = false;
    } else {
      this.markValid(confirm);
    }

    return valid;
  },

  validateContact(form) {
    let valid = true;
    const name    = form.querySelector('input[name="name"]');
    const email   = form.querySelector('input[name="email"]');
    const message = form.querySelector('textarea[name="message"]');

    if (this.isEmpty(name.value)) {
      this.showError(name, 'Name is required.');
      valid = false;
    } else {
      this.markValid(name);
    }

    if (this.isEmpty(email.value)) {
      this.showError(email, 'Email is required.');
      valid = false;
    } else if (!this.isValidEmail(email.value)) {
      this.showError(email, 'Enter a valid email address.');
      valid = false;
    } else {
      this.markValid(email);
    }

    if (this.isEmpty(message.value)) {
      this.showError(message, 'Message cannot be empty.');
      valid = false;
    } else if (message.value.trim().length < 10) {
      this.showError(message, 'Message must be at least 10 characters.');
      valid = false;
    } else {
      this.markValid(message);
    }

    return valid;
  },

  validateFeedback(form) {
    let valid = true;
    const feedback = form.querySelector('textarea[name="feedback"]');

    if (this.isEmpty(feedback.value)) {
      this.showError(feedback, 'Feedback cannot be empty.');
      valid = false;
    } else if (feedback.value.trim().length < 10) {
      this.showError(feedback, 'Please write at least 10 characters.');
      valid = false;
    } else {
      this.markValid(feedback);
    }

    return valid;
  },

  // ── Real-time inline validation ───────────────────────────

  attachLiveValidation(form) {
    form.querySelectorAll('input, textarea').forEach(el => {
      el.addEventListener('blur', () => {
        this.clearError(el);
        const v = el.value.trim();

        if (el.type === 'email' && v && !this.isValidEmail(v)) {
          this.showError(el, 'Enter a valid email address.');
        } else if (el.name === 'confirm') {
          const pw = form.querySelector('input[name="password"]');
          if (pw && v && v !== pw.value) {
            this.showError(el, 'Passwords do not match.');
          } else if (v) {
            this.markValid(el);
          }
        } else if (el.name === 'password' && v) {
          if (!this.isStrongPassword(v)) {
            this.showError(el, 'At least 8 characters required.');
          } else {
            this.markValid(el);
          }
        } else if (v) {
          this.markValid(el);
        }
      });
    });
  },

  // ── Success toast ─────────────────────────────────────────

  showSuccess(message) {
    let toast = document.getElementById('ms-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'ms-toast';
      toast.style.cssText = `
        position:fixed; bottom:30px; left:50%; transform:translateX(-50%) translateY(80px);
        background:#10b981; color:white; padding:14px 28px; border-radius:50px;
        font-size:14px; font-weight:600; opacity:0; transition:all 0.4s cubic-bezier(.34,1.56,.64,1);
        z-index:9999; box-shadow:0 8px 24px rgba(16,185,129,.35); letter-spacing:.3px;
      `;
      document.body.appendChild(toast);
    }
    toast.textContent = '✓  ' + message;
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(-50%) translateY(0)';
    });
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(-50%) translateY(80px)';
    }, 3000);
  }
};

// ── Shared validation CSS injected once ──────────────────────
(function injectValidationStyles() {
  if (document.getElementById('ms-validation-styles')) return;
  const s = document.createElement('style');
  s.id = 'ms-validation-styles';
  s.textContent = `
    .error-msg {
      display: block;
      font-size: 12px;
      color: #ef4444;
      margin-top: 4px;
      text-align: left;
      padding-left: 4px;
      animation: fadeSlide .2s ease;
    }
    @keyframes fadeSlide {
      from { opacity:0; transform:translateY(-4px); }
      to   { opacity:1; transform:translateY(0);    }
    }
    input.is-invalid, textarea.is-invalid {
      border-color: #ef4444 !important;
      box-shadow: 0 0 0 3px rgba(239,68,68,.12) !important;
    }
    input.is-valid, textarea.is-valid {
      border-color: #10b981 !important;
      box-shadow: 0 0 0 3px rgba(16,185,129,.12) !important;
    }
    .field { position: relative; }
  `;
  document.head.appendChild(s);
})();