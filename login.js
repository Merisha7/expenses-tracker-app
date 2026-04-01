// ── 1. PASSWORD SHOW / HIDE (Login page) ──────────────────────────────────────
// This handles the eye button on the main login page password field

var toggleBtn = document.getElementById('togglePw');
var pwBox     = document.getElementById('password');
var eyeIcon   = document.getElementById('eyeIcon');

// Open eye SVG paths (password is hidden)
var EYE_OPEN = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';

// Crossed-out eye SVG paths (password is visible)
var EYE_SHUT = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';

// Only run if eye button exists (only on login page)
if (toggleBtn && pwBox && eyeIcon) {
    toggleBtn.addEventListener('click', function () {
        if (pwBox.type === 'password') {
            pwBox.type        = 'text';       // show the characters
            eyeIcon.innerHTML = EYE_SHUT;     // show crossed-out eye
        } else {
            pwBox.type        = 'password';   // hide the characters
            eyeIcon.innerHTML = EYE_OPEN;     // show open eye
        }
        pwBox.focus();
    });
}


// ── 2. PASSWORD SHOW / HIDE (Reset page — reusable for any field) ─────────────
// This is called from the inline onclick on the reset page eye buttons
// inputId = the id of the password input
// iconId  = the id of the SVG eye icon to swap

function toggleField(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type      = 'text';
        icon.innerHTML  = EYE_SHUT;   // show crossed-out eye
    } else {
        input.type      = 'password';
        icon.innerHTML  = EYE_OPEN;   // show open eye
    }
    input.focus();
}


// ── 3. LOGIN FORM VALIDATION (before sending to PHP) ─────────────────────────
var loginForm = document.getElementById('loginForm');
var loginBtn  = document.getElementById('loginBtn');
var spinner   = document.getElementById('spinner');
var btnText   = document.getElementById('btnText');

// Simple check: must have characters @ characters . characters
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Show or hide the spinner, and disable the button while loading
function setLoading(on) {
    if (!loginBtn) return;
    loginBtn.disabled     = on;
    spinner.style.display = on ? 'inline-block' : 'none';
    btnText.textContent   = on ? 'LOGGING IN…'  : 'LOGIN';
}

// When login form is submitted, check fields before PHP sees them
if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
        var email = document.getElementById('email').value.trim();
        var pass  = document.getElementById('password').value;

        if (!email || !pass) {
            e.preventDefault();              // stop form
            alert('Please fill in all fields.');
            return;
        }

        if (!isValidEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return;
        }

        if (pass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return;
        }

        // All good — show spinner and let PHP take over
        setLoading(true);
    });
}


// ── 4. RESET FORM VALIDATION (before sending to PHP) ─────────────────────────
var resetForm = document.getElementById('resetForm');

if (resetForm) {
    resetForm.addEventListener('submit', function (e) {
        var code    = resetForm.querySelector('[name="code"]').value.trim();
        var newPass = document.getElementById('newPassword').value;
        var confirm = document.getElementById('confirmPassword').value;

        // Check code is 6 digits
        if (code.length !== 6 || isNaN(code)) {
            e.preventDefault();
            alert('Please enter the 6-digit code.');
            return;
        }

        // Check new password length
        if (newPass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return;
        }

        // Check passwords match
        if (newPass !== confirm) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
    });
}


// ── 5. LOGOUT CONFIRMATION ────────────────────────────────────────────────────
// First click → button turns red and says "Confirm logout?"
// Second click → actually goes to the logout URL
// Click anywhere else → cancels and restores the button

function confirmLogout(event, link) {
    event.preventDefault();

    var originalText = link.textContent;

    // Turn button red and change text
    link.textContent      = '⚠️  Confirm logout?';
    link.style.background = 'linear-gradient(135deg, #e05a5a, #c0392b)';
    link.style.boxShadow  = '0 4px 18px rgba(224,90,90,0.4)';

    // If user clicks outside the button — cancel logout
    function cancelLogout(e) {
        if (!link.contains(e.target)) {
            link.textContent      = originalText;
            link.style.background = '';
            link.style.boxShadow  = '';
            document.removeEventListener('click', cancelLogout);
        }
    }

    // Small delay so this same click doesn't immediately cancel
    setTimeout(function () {
        document.addEventListener('click', cancelLogout);
    }, 50);

    // Second click on button = go ahead and log out
    link.addEventListener('click', function goLogout(e) {
        e.preventDefault();
        link.textContent   = 'Logging out…';
        link.style.opacity = '0.7';
        document.removeEventListener('click', cancelLogout);
        link.removeEventListener('click', goLogout);
        window.location.href = link.href;
    }, { once: true });   // only fires once

    return false;
}


// ── 6. PAGE LOAD ANIMATION ────────────────────────────────────────────────────
// Each form element fades in and slides up, one after another

var elements = document.querySelectorAll('.form-group, .forgot-link, .btn-primary, .register-link, .sub-text, .code-hint');

elements.forEach(function (el, index) {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(12px)';
    // Each item starts slightly later than the one before it
    el.style.transition = 'opacity 0.4s ease ' + (0.15 + index * 0.07) + 's, transform 0.4s ease ' + (0.15 + index * 0.07) + 's';

    requestAnimationFrame(function () {
        el.style.opacity   = '1';
        el.style.transform = 'translateY(0)';
    });
});