document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const app = document.getElementById('dashboardApp');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const themeToggle = document.getElementById('themeToggle');
    const themeUrl = document.body.dataset.themeUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const desktopQuery = window.matchMedia('(min-width: 992px)');

    const syncSidebarState = function () {
        if (!app) {
            return;
        }

        if (desktopQuery.matches) {
            const savedState = localStorage.getItem('dashboard-sidebar');
            app.classList.toggle('sidebar-collapsed', savedState === 'collapsed');
            app.classList.remove('sidebar-open');
            return;
        }

        app.classList.remove('sidebar-collapsed');
        app.classList.remove('sidebar-open');
    };

    const applyTheme = function (theme) {
        html.setAttribute('data-theme', theme);
        html.setAttribute('data-bs-theme', theme);
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

    syncSidebarState();

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            if (!app) {
                return;
            }

            if (desktopQuery.matches) {
                const collapsed = app.classList.toggle('sidebar-collapsed');
                localStorage.setItem('dashboard-sidebar', collapsed ? 'collapsed' : 'expanded');
                app.classList.remove('sidebar-open');

                return;
            }

            app.classList.toggle('sidebar-open');
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            app.classList.remove('sidebar-open');
        });
    }

    desktopQuery.addEventListener('change', syncSidebarState);

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

    document.querySelectorAll('[data-auto-submit-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            select.form?.requestSubmit();
        });
    });

    document.querySelectorAll('.trainer-hours-form').forEach(function (form) {
        const trainerSelect = form.querySelector('[data-trainer-hours-trainer]');
        const hoursInput = form.querySelector('[data-trainer-hours-input]');
        const totalInput = form.querySelector('[data-trainer-hours-total]');

        if (!trainerSelect || !hoursInput || !totalInput) {
            return;
        }

        const formatNumber = function (value) {
            if (!Number.isFinite(value)) {
                return '';
            }

            return value.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
        };

        const syncTotal = function () {
            const selectedOption = trainerSelect.options[trainerSelect.selectedIndex];
            const hourlyRate = Number(selectedOption?.dataset.hourlyRate || 0);
            const hours = Number(hoursInput.value || 0);
            totalInput.value = formatNumber(hourlyRate * hours);
        };

        syncTotal();
        trainerSelect.addEventListener('change', syncTotal);
        hoursInput.addEventListener('input', syncTotal);
    });
});
