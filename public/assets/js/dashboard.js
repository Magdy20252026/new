document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const app = document.getElementById('dashboardApp');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const themeToggle = document.getElementById('themeToggle');

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

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                app.classList.toggle('sidebar-open');
            } else {
                app.classList.toggle('sidebar-collapsed');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            app.classList.remove('sidebar-open');
        });
    }
});