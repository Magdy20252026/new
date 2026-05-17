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

    document.querySelectorAll('[data-training-group-form]').forEach(function (form) {
        const weekDays = JSON.parse(form.dataset.weekDays || '{}');
        const initialSchedule = JSON.parse(form.dataset.initialSchedule || '[]');
        const levelSelect = form.querySelector('[data-training-group-level]');
        const trainerSelect = form.querySelector('[data-training-group-trainer]');
        const trainingDaysInput = form.querySelector('[data-training-group-days]');
        const nameInput = form.querySelector('[data-training-group-name]');
        const scheduleContainer = form.querySelector('[data-training-group-schedule]');

        if (!levelSelect || !trainerSelect || !trainingDaysInput || !nameInput || !scheduleContainer) {
            return;
        }

        const normalizeTime = function (value) {
            if (typeof value !== 'string') {
                return '';
            }

            return value.slice(0, 5);
        };

        const scheduleRows = function () {
            return Array.from(scheduleContainer.querySelectorAll('[data-training-group-row]'));
        };

        const createScheduleRow = function (index, entry) {
            const row = document.createElement('div');
            row.className = 'training-groups-schedule-row';
            row.dataset.trainingGroupRow = 'true';

            const dayField = document.createElement('div');
            const dayLabel = document.createElement('label');
            dayLabel.className = 'form-label';
            dayLabel.textContent = 'اليوم';

            const daySelect = document.createElement('select');
            daySelect.className = 'form-select';
            daySelect.name = 'schedule[' + index + '][day]';
            daySelect.dataset.trainingGroupScheduleDay = 'true';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            daySelect.appendChild(emptyOption);

            Object.entries(weekDays).forEach(function (dayEntry) {
                const option = document.createElement('option');
                option.value = dayEntry[0];
                option.textContent = dayEntry[1];
                option.selected = entry?.day === dayEntry[0];
                daySelect.appendChild(option);
            });

            dayField.appendChild(dayLabel);
            dayField.appendChild(daySelect);

            const timeField = document.createElement('div');
            const timeLabel = document.createElement('label');
            timeLabel.className = 'form-label';
            timeLabel.textContent = 'الساعة';

            const timeInput = document.createElement('input');
            timeInput.type = 'time';
            timeInput.className = 'form-control';
            timeInput.name = 'schedule[' + index + '][time]';
            timeInput.step = '60';
            timeInput.value = normalizeTime(entry?.time || '');
            timeInput.dataset.trainingGroupScheduleTime = 'true';

            timeField.appendChild(timeLabel);
            timeField.appendChild(timeInput);

            row.appendChild(dayField);
            row.appendChild(timeField);

            return row;
        };

        const syncName = function () {
            const level = levelSelect.value || '';
            const trainerName = trainerSelect.options[trainerSelect.selectedIndex]?.dataset.trainerName || '';
            const scheduleText = scheduleRows()
                .map(function (row) {
                    const day = row.querySelector('[data-training-group-schedule-day]')?.value || '';
                    const time = normalizeTime(row.querySelector('[data-training-group-schedule-time]')?.value || '');

                    if (!day || !time) {
                        return '';
                    }

                    return (weekDays[day] || day) + ' ' + time;
                })
                .filter(Boolean);

            nameInput.value = [level, trainerName, ...scheduleText].filter(Boolean).join(' - ');
        };

        const syncScheduleRows = function () {
            const totalRows = Math.max(1, Math.min(7, Number(trainingDaysInput.value || 1)));
            const rows = scheduleRows();

            while (rows.length > totalRows) {
                rows.pop()?.remove();
            }

            for (let index = scheduleRows().length; index < totalRows; index += 1) {
                scheduleContainer.appendChild(createScheduleRow(index, initialSchedule[index]));
            }

            scheduleRows().forEach(function (row, index) {
                row.querySelector('[data-training-group-schedule-day]').name = 'schedule[' + index + '][day]';
                row.querySelector('[data-training-group-schedule-time]').name = 'schedule[' + index + '][time]';
            });

            syncName();
        };

        syncScheduleRows();

        levelSelect.addEventListener('change', syncName);
        trainerSelect.addEventListener('change', syncName);
        trainingDaysInput.addEventListener('input', syncScheduleRows);
        scheduleContainer.addEventListener('input', syncName);
        scheduleContainer.addEventListener('change', syncName);
    });
});
