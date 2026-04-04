function togglePw(inputId) {
    var input = document.getElementById(inputId);
    var svg = document.getElementById('eye_' + inputId);
    if (!input || !svg) return;
    if (input.type === 'password') {
        input.type = 'text';
        svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/><line x1="1" y1="1" x2="23" y2="23" stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>';
    } else {
        input.type = 'password';
        svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="#b5acd4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="12" cy="12" r="3" stroke="#b5acd4" stroke-width="1.6" stroke-linejoin="round" fill="none"/>';
    }
}

var loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        var email = document.getElementById('email').value.trim();
        var pass = document.getElementById('password').value;
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
        document.getElementById('loginBtn').disabled = true;
        document.getElementById('spinner').style.display = 'inline-block';
        document.getElementById('btnText').textContent = 'LOGGING IN…';
    });
}

var resetForm = document.getElementById('resetForm');
if (resetForm) {
    resetForm.addEventListener('submit', function(e) {
        var code = resetForm.querySelector('[name="code"]').value.trim();
        var newpass = document.getElementById('new_password').value;
        var confirm = document.getElementById('confirm').value;
        if (code.length !== 6) {
            e.preventDefault();
            alert('Please enter the 6-digit code.');
            return;
        }
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
    setTimeout(function() { document.addEventListener('click', cancel); }, 50);
    btn.addEventListener('click', function go(e) {
        e.preventDefault();
        btn.textContent = 'Logging out…';
        document.removeEventListener('click', cancel);
        btn.removeEventListener('click', go);
        window.location.href = btn.href;
    }, { once: true });
    return false;
}

document.querySelectorAll('.field, .forgot, .btn, .bottom-link, .sub').forEach(function(el, i) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(10px)';
    el.style.transition = 'opacity .35s ease ' + (i * .06) + 's, transform .35s ease ' + (i * .06) + 's';
    requestAnimationFrame(function() {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });
});