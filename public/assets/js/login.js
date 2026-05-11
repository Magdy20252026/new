document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    const savedTheme = localStorage.getItem('theme-mode');
    if (savedTheme === 'dark' || savedTheme === 'light') {
        html.setAttribute('data-theme', savedTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme-mode', newTheme);
        });
    }

    if (togglePassword && passwordInput && togglePasswordIcon) {
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordIcon.className = isPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
        });
    }
});
