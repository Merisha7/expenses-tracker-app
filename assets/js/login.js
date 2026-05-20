/* ================================
   1. OTP HANDLING
================================ */

function setupOtpBoxes(formId, hiddenInputId) {
    var form = document.getElementById(formId);
    if (!form) return;

    var boxes  = form.querySelectorAll('.otp-box');
    var hidden = document.getElementById(hiddenInputId);

    boxes.forEach(function(box, i) {

        // Input typing
        box.addEventListener('input', function() {
            box.value = box.value.replace(/[^0-9]/g, '').slice(-1);
            box.classList.toggle('filled', box.value !== '');

            if (box.value && i < boxes.length - 1) {
                boxes[i + 1].focus();
            }

            checkAutoSubmit();
        });

        // Backspace handling (fixed)
        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace') {
                if (box.value) {
                    box.value = '';
                    box.classList.remove('filled');
                } else if (i > 0) {
                    boxes[i - 1].focus();
                }
            }
        });

        // Paste handling (fixed length)
        box.addEventListener('paste', function(e) {
            e.preventDefault();

            var digits = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/[^0-9]/g, '')
                .slice(0, boxes.length);

            digits.split('').forEach(function(d, j) {
                if (boxes[j]) {
                    boxes[j].value = d;
                    boxes[j].classList.add('filled');
                }
            });

            boxes[Math.min(digits.length, boxes.length - 1)].focus();
            checkAutoSubmit();
        });
    });

    // Auto-submit when all boxes filled for verify page only
    function checkAutoSubmit() {
        var code = Array.from(boxes).map(b => b.value).join('');
        if (formId === 'verifyForm' && code.length === boxes.length) {
            if (hidden) hidden.value = code;
            form.submit();
        }
    }

    // Submit fallback
    form.addEventListener('submit', function() {
        if (hidden) {
            hidden.value = Array.from(boxes).map(b => b.value).join('');
        }
    });
}

// Init
setupOtpBoxes('verifyForm', 'verifyHiddenCode');
setupOtpBoxes('resetForm',  'resetHiddenCode');


/* ================================
   2. FORM VALIDATION
================================ */

// Login
var loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        var email = document.getElementById('email').value.trim();
        var pass  = document.getElementById('password').value;

        if (!email || !pass) {
            e.preventDefault();
            alert('Please fill in all fields.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email.');
            return;
        }

        if (pass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return;
        }

        // Spinner
        var btn = document.getElementById('loginBtn');
        var spinner = document.getElementById('spinner');
        var text = document.getElementById('btnText');

        if (btn && spinner && text) {
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'LOGGING IN…';

            // Safety fallback (prevents stuck button)
            setTimeout(function() {
                btn.disabled = false;
                spinner.style.display = 'none';
                text.textContent = 'LOGIN';
            }, 5000);
        }
    });
}

// Reset password
var resetForm = document.getElementById('resetForm');
if (resetForm) {
    resetForm.addEventListener('submit', function(e) {
        var newpass = document.getElementById('new_password').value;
        var confirm = document.getElementById('confirm').value;

        if (newpass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return;
        }

        if (newpass !== confirm) {
            e.preventDefault();
            alert('Passwords do not match.');
            return;
        }
    });
}


/* ================================
   3. UI EFFECTS
================================ */

// Toggle password visibility
function togglePw(id) {
    var input = document.getElementById(id);
    var svg   = document.getElementById('eye_' + id);
    if (!input || !svg) return;

    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';

    svg.innerHTML = isHidden
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" stroke="#b5acd4" stroke-width="1.6" fill="none"/>
           <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" stroke="#b5acd4" stroke-width="1.6" fill="none"/>
           <line x1="1" y1="1" x2="23" y2="23" stroke="#b5acd4" stroke-width="1.6"/>`
        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="#b5acd4" stroke-width="1.6" fill="none"/>
           <circle cx="12" cy="12" r="3" stroke="#b5acd4" stroke-width="1.6" fill="none"/>`;
}


// Logout confirm
function confirmLogout(event, btn) {
    event.preventDefault();

    var original = btn.textContent;
    btn.textContent = '⚠️ Confirm logout?';
    btn.style.background = 'linear-gradient(135deg,#e05a5a,#c0392b)';

    function cancel(e) {
        if (!btn.contains(e.target)) {
            btn.textContent = original;
            btn.style.background = '';
            document.removeEventListener('click', cancel);
        }
    }

    setTimeout(function() {
        document.addEventListener('click', cancel);
    }, 50);

    btn.addEventListener('click', function go(e) {
        e.preventDefault();
        btn.textContent = 'Logging out…';
        document.removeEventListener('click', cancel);
        btn.removeEventListener('click', go);
        window.location.href = btn.href;
    }, { once: true });

    return false;
}


/* ================================
   4. PAGE ANIMATIONS
================================ */

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.field, .forgot, .btn, .bottom-link, .sub')
        .forEach(function(el, i) {
            el.style.cssText =
                'opacity:0; transform:translateY(10px); transition:opacity .35s ease ' +
                (i * .06) + 's, transform .35s ease ' + (i * .06) + 's';

            requestAnimationFrame(function() {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });
        });
});