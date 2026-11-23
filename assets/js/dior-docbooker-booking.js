/**
 * Dior Medical - DocBooker Interactive Appointment Booking Controller
 * Manages inline booking, service selection, and live slot availability.
 */
(function() {
    'use strict';

    // State management
    window.diorDocBookerState = {
        doctorId: 1957,
        doctorName: 'Dr. Marcus Sterling, DO',
        serviceType: 'Video Visit (HD)',
        date: '',
        time: '09:00 AM - 10:00 AM',
        isSubmitting: false,
        cachedAvailability: {}
    };

    window.diorGoToDocBookerPage = function() {
        var bookingPageUrl = window.dior_booking_vars && window.dior_booking_vars.booking_page_url;
        if (!bookingPageUrl) {
            console.error('[Dior Medical] Booking page URL is not configured.');
            return;
        }
        if (document.getElementById('dior-subtab-schedule') &&
            typeof window.diorSwitchApptSubTab === 'function') {
            window.diorSwitchApptSubTab('schedule');
            return;
        }
        if (document.getElementById('dior-subtab-book') &&
            typeof window.diorSwitchApptSubTab === 'function') {
            window.diorSwitchApptSubTab('book');
            return;
        }
        window.location.assign(bookingPageUrl);
    };
    window.diorOpenDocBookerModal = window.diorGoToDocBookerPage;

    /**
     * Service selection: Video Visit vs Phone Consultation
     */
    window.diorSelectServiceType = function(serviceType, el) {
        window.diorDocBookerState.serviceType = serviceType;

        var input = document.getElementById('dior-db-service-type');
        var inputFinal = document.getElementById('dior-db-final-type');
        if (input) input.value = serviceType;
        if (inputFinal) inputFinal.value = serviceType;

        // Toggle visual active state
        var cards = document.querySelectorAll('.dior-db-service-card');
        cards.forEach(function(c) { c.classList.remove('active'); });
        if (el) el.classList.add('active');
    };

    /**
     * Time slot selection
     */
    window.diorSelectTimeSlot = function(time, el) {
        if (el && el.disabled) return;
        window.diorDocBookerState.time = time;

        var timeInput = document.getElementById('dior-db-selected-time');
        var timeFinal = document.getElementById('dior-db-final-time');
        if (timeInput) timeInput.value = time;
        if (timeFinal) timeFinal.value = time;

        var buttons = document.querySelectorAll('.dior-db-slot-btn');
        buttons.forEach(function(b) { b.classList.remove('active'); });
        if (el) el.classList.add('active');
    };

    /**
     * Date changed callback
     */
    window.diorOnDateChanged = function(newDate) {
        window.diorDocBookerState.date = newDate;
        window.diorFetchDocBookerAvailability(newDate);
    };

    /**
     * Query DocBooker live availability for given date
     */
    window.diorFetchDocBookerAvailability = function(dateStr) {
        if (!dateStr) return;
        var spinner = document.getElementById('dior-db-slots-loading');
        var grid = document.getElementById('dior-db-slots-grid');
        if (spinner) spinner.style.display = 'inline-block';

        var restUrl = (window.dior_booking_vars && window.dior_booking_vars.rest_url) || '/wp-json/';
        var restNonce = (window.dior_booking_vars && window.dior_booking_vars.rest_nonce) || '';
        var apiUrl = restUrl + 'doctor-details-booking/v1/doctors/' + window.diorDocBookerState.doctorId + '/availability?date=' + encodeURIComponent(dateStr);

        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': restNonce
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('API returned ' + res.status);
            return res.json();
        })
        .then(function(data) {
            if (spinner) spinner.style.display = 'none';
            if (data && data.clinics && data.clinics.length > 0 && data.clinics[0].timings) {
                var timings = data.clinics[0].timings;
                window.diorRenderAvailableSlots(timings);
            }
        })
        .catch(function(err) {
            if (spinner) spinner.style.display = 'none';
            console.log('[Dior Medical] Using default DocBooker slots for ' + dateStr, err);
            // Default slots
            var defaultSlots = [
                { time: '09:00 AM - 10:00 AM', is_available: true },
                { time: '10:00 AM - 11:00 AM', is_available: true },
                { time: '11:00 AM - 12:00 PM', is_available: true },
                { time: '01:00 PM - 02:00 PM', is_available: true },
                { time: '02:00 PM - 03:00 PM', is_available: true },
                { time: '03:00 PM - 04:00 PM', is_available: true },
                { time: '04:00 PM - 05:00 PM', is_available: true }
            ];
            window.diorRenderAvailableSlots(defaultSlots);
        });
    };

    /**
     * Render slot buttons inside the grid
     */
    window.diorRenderAvailableSlots = function(timings) {
        var grid = document.getElementById('dior-db-slots-grid');
        if (!grid) return;
        grid.innerHTML = '';

        var firstAvailable = null;
        timings.forEach(function(item, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dior-db-slot-btn';
            btn.setAttribute('data-time', item.time);
            btn.textContent = item.time;

            if (item.is_available) {
                if (!firstAvailable) {
                    firstAvailable = item.time;
                    btn.classList.add('active');
                }
                btn.onclick = function() {
                    window.diorSelectTimeSlot(item.time, btn);
                };
            } else {
                btn.disabled = true;
                btn.classList.add('is-booked');
                btn.title = 'Slot Booked';
            }
            grid.appendChild(btn);
        });

        if (firstAvailable) {
            window.diorDocBookerState.time = firstAvailable;
            var timeInput = document.getElementById('dior-db-selected-time');
            var timeFinal = document.getElementById('dior-db-final-time');
            if (timeInput) timeInput.value = firstAvailable;
            if (timeFinal) timeFinal.value = firstAvailable;
        }
    };

    /**
     * Submit Booking Form
     */
    window.diorSubmitDocBookerBooking = function(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (window.diorDocBookerState.isSubmitting) return false;

        var form = document.getElementById('dior-docbooker-form');
        var submitBtn = document.getElementById('dior-db-submit-btn');
        var statusBox = document.getElementById('dior-db-status-box');

        // Validation
        var nameInput = document.getElementById('dior-db-patient-name');
        var emailInput = document.getElementById('dior-db-patient-email');
        var phoneInput = document.getElementById('dior-db-patient-phone');
        var dateInput = document.getElementById('dior-db-date-input');

        if (!nameInput.value.trim() || !emailInput.value.trim() || !phoneInput.value.trim()) {
            if (statusBox) {
                statusBox.className = 'dior-db-alert alert-error';
                statusBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Please provide your full name, email address, and contact phone number.';
                statusBox.style.display = 'flex';
            }
            return false;
        }

        window.diorDocBookerState.isSubmitting = true;
        if (statusBox) statusBox.style.display = 'none';

        if (submitBtn) {
            submitBtn.disabled = true;
            var btnText = submitBtn.querySelector('.dior-db-btn-text');
            var btnSpinner = submitBtn.querySelector('.dior-db-btn-spinner');
            if (btnText) btnText.style.display = 'none';
            if (btnSpinner) btnSpinner.style.display = 'inline-block';
        }

        var formData = new FormData(form);
        // Ensure values match current state
        formData.set('type', window.diorDocBookerState.serviceType);
        formData.set('visit_type', window.diorDocBookerState.serviceType);
        formData.set('time', window.diorDocBookerState.time);
        formData.set('appt_time', window.diorDocBookerState.time);
        formData.set('date', dateInput.value);
        formData.set('appt_date', dateInput.value);
        formData.set('doctor_id', window.diorDocBookerState.doctorId);
        formData.set('doctorId', window.diorDocBookerState.doctorId);
        formData.set('provider', window.diorDocBookerState.doctorName);
        formData.set('doctor_name', window.diorDocBookerState.doctorName);

        var ajaxUrl = (window.dior_booking_vars && window.dior_booking_vars.ajax_url) || '/wp-admin/admin-ajax.php';

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            window.diorDocBookerState.isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                var btnText = submitBtn.querySelector('.dior-db-btn-text');
                var btnSpinner = submitBtn.querySelector('.dior-db-btn-spinner');
                if (btnText) btnText.style.display = 'inline-block';
                if (btnSpinner) btnSpinner.style.display = 'none';
            }

            if (res.success) {
                // Populate Success screen
                var body = document.getElementById('dior-db-modal-body');
                var success = document.getElementById('dior-db-modal-success');
                if (body) body.style.display = 'none';
                if (success) success.style.display = 'block';

                var apptData = (res.data && res.data.appointment) || {};
                var apptUid = apptData.appt_uid || apptData.id || ('APT-' + Math.floor(1000 + Math.random() * 9000));

                var successUid = document.getElementById('dior-success-uid');
                var successType = document.getElementById('dior-success-service-type');
                var successDt = document.getElementById('dior-success-datetime');
                var successDoc = document.getElementById('dior-success-doc-name');

                if (successUid) successUid.textContent = apptUid;
                if (successType) successType.textContent = window.diorDocBookerState.serviceType;
                if (successDt) successDt.textContent = dateInput.value + ' at ' + window.diorDocBookerState.time;
                if (successDoc) successDoc.textContent = window.diorDocBookerState.doctorName;

                if (window.diorDocBookerState) {
                    window.diorDocBookerState.lastBookedDate = dateInput.value;
                }

                // Dynamically update dashboard elements if present on page
                window.diorInjectAppointmentIntoDashboards({
                    uid: apptUid,
                    appt_uid: apptUid,
                    doctor: window.diorDocBookerState.doctorName,
                    spec: 'Emergency Medicine & Telehealth Urgent Care',
                    date: dateInput.value,
                    time: window.diorDocBookerState.time,
                    type: window.diorDocBookerState.serviceType,
                    patientName: nameInput.value,
                    phone: phoneInput.value,
                    join_url: (apptData.join_url || apptData.meeting_url || ''),
                    notes: (apptData.notes || window.diorDocBookerState.serviceType || 'Telehealth Consultation')
                });

            } else {
                if (statusBox) {
                    statusBox.className = 'dior-db-alert alert-error';
                    statusBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + ((res.data && res.data.message) || 'Booking failed. Please try again.');
                    statusBox.style.display = 'flex';
                }
            }
        })
        .catch(function(err) {
            window.diorDocBookerState.isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                var btnText = submitBtn.querySelector('.dior-db-btn-text');
                var btnSpinner = submitBtn.querySelector('.dior-db-btn-spinner');
                if (btnText) btnText.style.display = 'inline-block';
                if (btnSpinner) btnSpinner.style.display = 'none';
            }
            if (statusBox) {
                statusBox.className = 'dior-db-alert alert-error';
                statusBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Connection issue while booking appointment. Please try again.';
                statusBox.style.display = 'flex';
            }
            console.error('[Dior Medical] Booking error:', err);
        });

        return false;
    };

    /**
     * Injects the newly booked appointment into Patient Dashboard tables & cards in real-time
     */
    window.diorInjectAppointmentIntoDashboards = function(appt) {
        try {
            var todayYmd = new Date().toISOString().split('T')[0];
            var isToday = (appt.date === todayYmd);
            var tbodyToday = document.getElementById('dior-today-appointments-tbody');
            var tbodyUpcoming = document.getElementById('dior-upcoming-appointments-tbody');

            var apptUid = appt.uid || appt.appt_uid || ('APT-' + Math.floor(1000 + Math.random() * 9000));
            var joinUrl = appt.join_url || '';
            var nonce = (window.dior_patient_dashboard && window.dior_patient_dashboard.meeting_nonce) || '';

            // Build 3 Action Icons:
            // 1. Join Zoom Meeting (video icon)
            // 2. Reschedule Appointment (calendar-alt icon)
            // 3. Cancel Appointment (times icon)
            var videoBtnHtml = '';
            if (joinUrl && joinUrl.indexOf('https://') === 0) {
                videoBtnHtml = '<a class="action-icon-btn edit-btn btn" href="' + joinUrl + '" data-dior-meeting-join data-appt-uid="' + apptUid + '" data-meeting-nonce="' + nonce + '" target="_blank" rel="noopener noreferrer" title="Join Zoom Meeting" aria-label="Join Zoom Meeting"><i class="fas fa-video text-success"></i></a>';
            } else {
                videoBtnHtml = '<button type="button" class="action-icon-btn edit-btn btn" disabled title="Meeting link unavailable" aria-label="Meeting link unavailable"><i class="fas fa-video text-muted"></i></button>';
            }

            var actionsHtml = '<div class="cell-actions">' +
                videoBtnHtml +
                '<button type="button" class="action-icon-btn edit-btn btn dior-today-reschedule" data-appt-uid="' + apptUid + '" data-appt-date="' + appt.date + '" data-appt-time="' + appt.time + '" title="Reschedule Appointment" aria-label="Reschedule Appointment"><i class="fas fa-calendar-alt"></i></button>' +
                '<button type="button" class="action-icon-btn delete-btn btn dior-today-cancel" data-appt-uid="' + apptUid + '" title="Cancel Appointment" aria-label="Cancel Appointment"><i class="fas fa-times"></i></button>' +
            '</div>';

            // 1. Row for Today\'s Table: DOCTOR, SPECIALIZATION, DATE, TIME, TREATMENT, CONTACT, STATUS, ACTIONS (8 cols)
            var todayRowHtml = '<tr class="dior-newly-added-row" style="background: #f0fdf4; transition: background 1.5s ease;">' +
                '<td><div class="cell-content cell-image-name"><span class="cell-text doctor-name" style="font-weight:700;">' + (appt.doctor || '') + '</span></div></td>' +
                '<td><span class="cell-text">' + (appt.spec || 'Emergency Medicine & Telehealth Urgent Care') + '</span></td>' +
                '<td><div class="cell-content cell-icon-text"><i class="far fa-calendar cell-icon"></i><span class="cell-text">' + (appt.date || '') + '</span></div></td>' +
                '<td><span class="cell-text" style="font-weight:600;">' + (appt.time || '') + '</span></td>' +
                '<td><span class="cell-text" style="color:#d4af37; font-weight:600;">' + (appt.type || 'Video Visit (HD)') + '</span></td>' +
                '<td><span class="cell-text">' + (appt.phone || 'On file') + '</span></td>' +
                '<td><span class="status-cell-text status-confirm" style="background:#d1fae5; color:#047857; font-weight:700; padding:4px 8px; border-radius:6px;">Confirmed</span></td>' +
                '<td class="dior-ic-d5c209e67c">' + actionsHtml + '</td>' +
            '</tr>';

            // 2. Row for Upcoming Table: Checkbox, DOCTOR, DATE, TIME, INJURY, STATUS, NOTES, ACTIONS (8 cols)
            var upcomingRowHtml = '<tr class="dior-newly-added-row" style="background: #f0fdf4; transition: background 1.5s ease;">' +
                '<td class="dior-ic-d5c209e67c"><input type="checkbox" class="master-checkbox upcoming-row-checkbox form-check-input" onclick="diorUpdateUpcomingSelectedCount()"></td>' +
                '<td><span class="cell-text doctor-name" style="font-weight:700;">' + (appt.doctor || '') + '</span></td>' +
                '<td><div class="cell-content cell-icon-text"><i class="far fa-calendar cell-icon"></i><span class="cell-text">' + (appt.date || '') + '</span></div></td>' +
                '<td><span class="cell-text" style="font-weight:600;">' + (appt.time || '') + '</span></td>' +
                '<td><span class="cell-text">' + (appt.type || 'Video Visit (HD)') + '</span></td>' +
                '<td><span class="status-cell-text status-upcoming" style="background:#e0e7ff; color:#3730a3; font-weight:700; padding:4px 8px; border-radius:6px;">Confirmed</span></td>' +
                '<td><span class="cell-text notes-text">' + (appt.notes || '—') + '</span></td>' +
                '<td class="dior-ic-d5c209e67c">' + actionsHtml + '</td>' +
            '</tr>';

            if (isToday && tbodyToday) {
                tbodyToday.insertAdjacentHTML('afterbegin', todayRowHtml);
                var countToday = document.getElementById('dior-today-page-count');
                if (countToday) {
                    var currentTotal = parseInt(countToday.textContent.replace(/[^0-9]/g, ''), 10) || 0;
                    countToday.textContent = '0 selected / ' + (currentTotal + 1) + ' total';
                }
            }

            if (tbodyUpcoming) {
                tbodyUpcoming.insertAdjacentHTML('afterbegin', upcomingRowHtml);
                var countUpcoming = document.getElementById('dior-upcoming-page-count');
                if (countUpcoming) {
                    var currentUpTotal = parseInt(countUpcoming.textContent.replace(/[^0-9]/g, ''), 10) || 0;
                    countUpcoming.textContent = '0 selected / ' + (currentUpTotal + 1) + ' total';
                }
            }

            // Keep in-memory appointment data synced
            var memAppt = {
                id: apptUid,
                appt_uid: apptUid,
                doc: appt.doctor,
                doctor: appt.doctor,
                spec: appt.spec || 'Emergency Medicine & Telehealth Urgent Care',
                date: appt.date,
                ymd: appt.date,
                time: appt.time,
                type: appt.type || 'Video Visit (HD)',
                status: 'Confirmed',
                join_url: joinUrl,
                notes: appt.notes || '—'
            };
            if (Array.isArray(window.dior_patient_appts_data)) window.dior_patient_appts_data.unshift(memAppt);
            if (Array.isArray(window.dior_inline_appts_data)) window.dior_inline_appts_data.unshift(memAppt);
            if (window.dior_patient_dashboard && Array.isArray(window.dior_patient_dashboard.appointments)) {
                window.dior_patient_dashboard.appointments.unshift(memAppt);
            }

            // 2. Increment Overview Counters
            var statCards = document.querySelectorAll('.stat-card .stat-num');
            if (statCards.length >= 2) {
                var totalEl = statCards[0];
                var upEl = statCards[1];
                if (totalEl) {
                    var curT = parseInt(totalEl.textContent, 10) || 0;
                    totalEl.textContent = String(curT + 1).padStart(2, '0');
                }
                if (upEl) {
                    var curU = parseInt(upEl.textContent, 10) || 0;
                    upEl.textContent = String(curU + 1).padStart(2, '0');
                }
            }

            // 3. Update Next Appointment Card in Overview
            var nextDocName = document.querySelector('.hero-appointment-card .doctor-name');
            var nextDocDate = document.querySelector('.hero-appointment-card .appointment-date');
            if (nextDocName) nextDocName.textContent = appt.doctor;
            if (nextDocDate) nextDocDate.textContent = appt.date + ' at ' + appt.time;

        } catch (err) {
            console.error('[Dior Medical] Live DOM update warning:', err);
        }
    };

    /**
     * Called when user clicks "View in Appointments Tab" from success screen
     */
    window.diorOnBookingSuccessDone = function() {
        var dashboardUrl = window.dior_booking_vars && window.dior_booking_vars.patient_dashboard_url;
        if (!dashboardUrl) {
            console.error('[Dior Medical] Patient dashboard URL is not configured.');
            return;
        }
        var todayYmd = new Date().toISOString().split('T')[0];
        var targetSub = (window.diorDocBookerState && window.diorDocBookerState.lastBookedDate === todayYmd) ? 'today' : 'upcoming';
        window.location.assign(dashboardUrl + '#tab=appointments&subtab=' + targetSub);
    };

    var HIPAATIZER_ORIGINS = [
        'https://app.hipaatizer.com',
        'https://www.hipaatizer.com',
        'https://hipaatizer.com'
    ];

    var HIPAATIZER_COMPLETE_EVENTS = [
        'submitSuccess',
        'workflowSubmitted',
        'formCompleted',
        'submissionSuccess'
    ];

    var hipaatizerCompletionEvent = null;

    function isVisibleHipaatizerForm(event) {
        var frame = null;
        var frames = document.querySelectorAll(
            '#dior-hipaa-tab-form iframe, #dior-hipaatizer-form-source-v17 iframe'
        );
        for (var i = 0; i < frames.length; i++) {
            if (frames[i].contentWindow === event.source) {
                frame = frames[i];
                break;
            }
        }

        try {
            if (!frame && event.source && event.source.frameElement) {
                frame = event.source.frameElement;
            }
        } catch (e) {
            return false;
        }
        if (!frame || !frame.closest) return false;

        var appointmentForm = frame.closest('#dior-hipaa-tab-form');
        if (appointmentForm) {
            return !!(appointmentForm.getClientRects && appointmentForm.getClientRects().length);
        }

        var inSource = frame.closest('#dior-hipaatizer-form-source-v17');
        if (!inSource || inSource.hidden) return false;

        var popup = inSource.closest('.swal2-popup');
        return !!(popup && popup.getClientRects && popup.getClientRects().length);
    }

    function isHipaatizerCompletion(data) {
        if (!data) return false;
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (e) {
                return false;
            }
            if (typeof data !== 'object' || data === null) return false;
        }
        if (typeof data !== 'object') return false;

        var name = String(data.type || data.event || data.action || data.name || data.message || '').trim();

        // A payload that identifies a step is a step save, never a completion.
        //
        // This check MUST come before the workflow flags: a step save can carry
        // `step: 1` together with `workflowComplete: true` (or `isWorkflowComplete`,
        // or `completed`) and the flag-first ordering read that as "the whole
        // intake is finished" — which revealed the booking form on the FIRST
        // Continue press, the exact bug reported. A step ordinal is proof that
        // the patient is mid-form, whatever else the payload claims.
        if (/step/i.test(name)) return false;
        if (data.step !== undefined && data.step !== null && data.step !== '') return false;

        // Explicit workflow-level flags, now only reachable for payloads that do
        // not identify any step.
        if (data.workflowComplete === true || data.isWorkflowComplete === true) {
            return true;
        }

        return HIPAATIZER_COMPLETE_EVENTS.indexOf(name) !== -1;
    }

    window.diorHipaatizerCompletion = function(event) {
        if (!event || !event.data) return false;
        if (event === hipaatizerCompletionEvent) return true;

        var isAllowedOrigin = event.origin === window.location.origin || HIPAATIZER_ORIGINS.indexOf(event.origin) !== -1;
        if (!isAllowedOrigin || !isVisibleHipaatizerForm(event) || !isHipaatizerCompletion(event.data)) return false;
        if (hipaatizerCompletionEvent !== null) return false;

        hipaatizerCompletionEvent = event;
        return true;
    };

    function initializeBookingForm() {
        var form = document.getElementById('dior-docbooker-form');
        var dateInput = document.getElementById('dior-db-date-input');
        if (!form || !dateInput) return;

        if (!dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        window.diorDocBookerState.date = dateInput.value;
        window.diorFetchDocBookerAvailability(dateInput.value);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBookingForm);
    } else {
        initializeBookingForm();
    }

})();
