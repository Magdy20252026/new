document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const app = document.getElementById('dashboardApp');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const themeToggle = document.getElementById('themeToggle');
    const themeUrl = document.body.dataset.themeUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const applyTheme = function (theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme-mode', theme);
    };

    const savedTheme = localStorage.getItem('theme-mode');
    if (savedTheme === 'dark' || savedTheme === 'light') {
        applyTheme(savedTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', async function () {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);

            if (!themeUrl || !csrfToken) {
                return;
            }

            try {
                const response = await fetch(themeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ theme: nextTheme }),
                });

                if (!response.ok) {
                    throw new Error('theme update failed');
                }
            } catch (error) {
                applyTheme(currentTheme);
            }
        });
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            app.classList.toggle('sidebar-open');
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            app.classList.remove('sidebar-open');
        });
    }

    document.querySelectorAll('.branch-scope-select').forEach(function (select) {
        const target = document.querySelector(select.dataset.target);
        const syncScope = function () {
            if (!target) {
                return;
            }
            target.classList.toggle('d-none', select.value === 'all');
        };

        syncScope();
        select.addEventListener('change', syncScope);
    });
});
