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



    document.querySelectorAll('[data-swimmer-toggle-form]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            const panel = document.querySelector('[data-swimmer-form-panel]');

            if (!panel) {
                return;
            }

            event.preventDefault();
            panel.classList.remove('d-none');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('[data-swimmer-form]').forEach(function (form) {
        const trainingGroups = JSON.parse(form.dataset.trainingGroups || '[]');
        const serialNumber = Number(form.dataset.nextSerialNumber || 1001);
        const birthYearInput = form.querySelector('[data-swimmer-birth-year]');
        const ageInput = form.querySelector('[data-swimmer-age]');
        const groupSelect = form.querySelector('[data-swimmer-group]');
        const barcodeInput = form.querySelector('[data-swimmer-barcode]');
        const startDateInput = form.querySelector('[data-swimmer-start-date]');
        const endDateInput = form.querySelector('[data-swimmer-end-date]');
        const priceInput = form.querySelector('[data-swimmer-price]');
        const paidInput = form.querySelector('[data-swimmer-paid]');
        const remainingInput = form.querySelector('[data-swimmer-remaining]');

        if (!birthYearInput || !ageInput || !groupSelect || !barcodeInput || !startDateInput || !endDateInput || !priceInput || !paidInput || !remainingInput) {
            return;
        }

        const selectedGroup = function () {
            return trainingGroups.find(function (group) {
                return String(group.id) === String(groupSelect.value || '');
            }) || null;
        };

        const formatNumber = function (value) {
            if (!Number.isFinite(value)) {
                return '0';
            }

            return value.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
        };

        const syncAge = function () {
            const currentYear = new Date().getFullYear();
            const birthYear = Number(birthYearInput.value || 0);
            const age = birthYear > 0 ? Math.max(0, currentYear - birthYear) : 0;
            ageInput.value = String(age);

            return age;
        };

        const syncEndDate = function () {
            const group = selectedGroup();
            const startDate = startDateInput.value;

            if (!group || !startDate) {
                return;
            }

            const trainingDaysPerWeek = Math.max(1, Number(group.training_days_per_week || 1));
            const availableTrainingDays = Math.max(1, Number(group.available_training_days || 1));
            const weeks = Math.max(1, Math.ceil(availableTrainingDays / trainingDaysPerWeek));
            const date = new Date(startDate + 'T00:00:00');
            date.setDate(date.getDate() + (weeks * 7));
            endDateInput.value = date.toISOString().slice(0, 10);
        };

        const syncRemaining = function () {
            const remaining = Number(priceInput.value || 0) - Number(paidInput.value || 0);
            remainingInput.value = formatNumber(remaining);
        };

        const syncBarcode = function () {
            syncAge();
            barcodeInput.value = String(serialNumber);
        };

        const syncPriceFromGroup = function () {
            const group = selectedGroup();

            if (!group) {
                return;
            }

            priceInput.value = formatNumber(Number(group.price || 0));
        };

        syncAge();
        syncEndDate();
        syncRemaining();
        syncBarcode();

        groupSelect.addEventListener('change', function () {
            syncPriceFromGroup();
            syncEndDate();
            syncRemaining();
        });

        birthYearInput.addEventListener('input', syncBarcode);
        startDateInput.addEventListener('change', syncEndDate);
        priceInput.addEventListener('input', function () {
            syncRemaining();
        });
        paidInput.addEventListener('input', syncRemaining);
    });

    document.querySelectorAll('[data-swimmer-files-form]').forEach(function (form) {
        const typeSelect = form.querySelector('[data-swimmer-file-type]');
        const fileInput = form.querySelector('[data-swimmer-file-input]');
        const helpText = form.querySelector('[data-swimmer-file-help]');

        if (!typeSelect || !fileInput) {
            return;
        }

        const syncInputMode = function () {
            const isMedical = typeSelect.value === 'medical_report';
            fileInput.multiple = isMedical;

            if (helpText) {
                helpText.textContent = isMedical
                    ? 'يمكنك رفع أكثر من صورة للتقرير الطبي في مرة واحدة.'
                    : 'يمكنك رفع صورة واحدة لهذا النوع من الملفات.';
            }
        };

        syncInputMode();
        typeSelect.addEventListener('change', syncInputMode);
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

        const setFollowFirstTime = function (timeInput, followsFirstTime) {
            if (!timeInput) {
                return;
            }

            timeInput.dataset.trainingGroupFollowFirstTime = followsFirstTime ? 'true' : 'false';
        };

        const followsFirstTime = function (timeInput) {
            return timeInput?.dataset.trainingGroupFollowFirstTime === 'true';
        };

        const currentFirstTime = function (firstRow) {
            const resolvedFirstRow = firstRow || scheduleRows()[0] || null;

            return normalizeTime(resolvedFirstRow?.querySelector('[data-training-group-schedule-time]')?.value || '');
        };

        const syncFollowerTimeState = function (timeInput, row, firstRow) {
            if (!timeInput) {
                return;
            }

            const isFirstRow = row === firstRow;

            if (isFirstRow) {
                return;
            }

            const firstTime = currentFirstTime(firstRow);
            const currentValue = normalizeTime(timeInput.value || '');
            setFollowFirstTime(timeInput, currentValue === '' || currentValue === firstTime);
        };

        const syncTimesFromFirstDay = function () {
            const rows = scheduleRows();
            const firstRow = rows[0] || null;
            // Track the previous first-day time so rows that were auto-filled earlier can keep following future changes.
            const previousFirstTime = normalizeTime(form.dataset.trainingGroupFirstTime || '');
            const firstTime = currentFirstTime(firstRow);

            rows.slice(1).forEach(function (row) {
                const timeInput = row.querySelector('[data-training-group-schedule-time]');

                if (!timeInput) {
                    return;
                }

                const currentValue = normalizeTime(timeInput.value || '');
                const isExplicitlyFollowing = followsFirstTime(timeInput);
                const isEmpty = currentValue === '';
                const matchesPreviousFirstTime = previousFirstTime !== '' && currentValue === previousFirstTime;
                // Sync rows that still follow the first day, are blank, or still match the previously auto-filled first-day time.
                const shouldSync = isExplicitlyFollowing || isEmpty || matchesPreviousFirstTime;

                if (!shouldSync) {
                    return;
                }

                timeInput.value = firstTime;
                setFollowFirstTime(timeInput, true);
            });

            form.dataset.trainingGroupFirstTime = firstTime;
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
            setFollowFirstTime(timeInput, index !== 0 && !entry?.time);

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

            syncTimesFromFirstDay();
            syncName();
        };

        syncScheduleRows();

        const handleScheduleTimeChange = function (event) {
            if (event.target?.matches('[data-training-group-schedule-time]')) {
                const row = event.target.closest('[data-training-group-row]');
                const firstRow = scheduleRows()[0] || null;

                if (row === firstRow) {
                    syncTimesFromFirstDay();
                } else {
                    syncFollowerTimeState(event.target, row, firstRow);
                }
            }

            syncName();
        };

        levelSelect.addEventListener('change', syncName);
        trainerSelect.addEventListener('change', syncName);
        trainingDaysInput.addEventListener('input', syncScheduleRows);
        scheduleContainer.addEventListener('input', handleScheduleTimeChange);
        scheduleContainer.addEventListener('change', handleScheduleTimeChange);
    });
});
