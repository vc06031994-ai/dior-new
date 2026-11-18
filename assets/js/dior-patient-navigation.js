/**
 * Dior Medical — Patient Dashboard navigation safety controller.
 * Keeps sidebar navigation independent from other dashboard widgets.
 */
(function () {
    'use strict';

    // Hash captured before any other controller runs, so the tab the user
    // refreshed on is still the one that gets restored.
    var diorInitialHash = window.location.hash || '';

    function boot() {
        var app = document.getElementById('dior-patient-portal-app');
        if (!app || app.__diorSafeNavigationReady) return;

        app.__diorSafeNavigationReady = true;

        // Explicit close control inside the mobile drawer — same control the
        // Doctor Dashboard injects (icon, size, position and behaviour).
        function ensureMobileCloseButton() {
            var sidebar = document.getElementById('dior-sidebar');
            if (!sidebar || sidebar.querySelector('.dior-mobile-drawer-close')) return;
            // The template already ships its own close control
            // (#dior-side-close). Injecting a second one stacked two ×
            // buttons in the drawer, so only add one when none exists.
            if (sidebar.querySelector('.dior-side-close')) return;

            var header = sidebar.querySelector('.dior-side-header') || sidebar;
            var closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'dior-mobile-drawer-close';
            closeButton.setAttribute('aria-label', 'Close navigation menu');
            closeButton.setAttribute('title', 'Close menu');
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof window.diorCloseMobileDrawer === 'function') {
                    window.diorCloseMobileDrawer();
                }
            });
            header.appendChild(closeButton);
        }
        ensureMobileCloseButton();

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            var sidebar = document.getElementById('dior-sidebar');
            if (sidebar && sidebar.classList.contains('mobile-open') &&
                typeof window.diorCloseMobileDrawer === 'function') {
                window.diorCloseMobileDrawer();
            }
        });

        var titles = {
            overview: 'Patient Overview',
            appointments: 'Telehealth Appointments',
            docs_meds: 'Medical Docs & Prescriptions',
            telemedicine: 'Telemedicine',
            medical_record: 'Medical Record',
            payments: 'Billing & Payment Statements',
            insurance: 'Insurance Claim',
            documents: 'Documents & Reports',
            emergency: 'Emergency Support',
            feedback: 'Feedback & Support',
            notifications: 'Notification Inbox',
            consultation: 'Consultation Room',
            settings: 'Settings'
        };

        function activate(tabId, updateHash) {
            if (!tabId) return false;

            var panel = document.getElementById('tab-' + tabId);
            if (!panel || !app.contains(panel)) return false;

            app.querySelectorAll('.dior-nav-btn[data-tab]').forEach(function (button) {
                var isActive = button.getAttribute('data-tab') === tabId && !diorIsSubmenuParentBtn(button);
                button.classList.toggle('active', isActive);
            });

            app.querySelectorAll('.dior-tab-panel').forEach(function (item) {
                item.classList.toggle('active', item === panel);
            });

            var title = document.getElementById('dior-current-page-title');
            if (title && titles[tabId]) title.textContent = titles[tabId];

            if (tabId !== 'appointments') {
                var w = document.getElementById('dior-appt-dropdown-wrap');
                if (w) {
                    w.classList.remove('open');
                    diorSetApptToggleIcon(w, false);
                }
            }

            if (updateHash !== false && window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#tab=' + encodeURIComponent(tabId));
            }

            return true;
        }

        // A row that owns a submenu (the patient's "Appointments") behaves like the
        // Doctor Dashboard's parent row: it only toggles the list and never takes
        // the .active fill. Its label is the thing that stays highlighted, and the
        // fill was the exact background the user asked to remove.
            function diorIsSubmenuParentBtn(btn) {
            if (!btn) return false;
            if (btn.classList.contains('submenu-btn')) return false;
            return !!(btn.closest('.dior-nav-item-has-children') ||
                      btn.closest('.dior-nav-item.has-submenu'));
        }

        // Appointments toggle affordance mirrors the Doctor Dashboard: +/−.
        function diorSetApptToggleIcon(wrap, isOpen) {
            if (!wrap) return;
            var icon = wrap.querySelector('.submenu-icon');
            if (!icon) return;
            icon.classList.toggle('fa-minus', !!isOpen);
            icon.classList.toggle('fa-plus', !isOpen);
        }

        // Public API used by existing dashboard controls.
        window.diorSafeSwitchPatientTab = activate;

        // Capture before other handlers so a broken widget cannot block navigation.
        app.addEventListener('click', function (event) {
            var button = event.target.closest('.dior-nav-btn[data-tab]');
            if (!button || !app.contains(button)) return;

            var tabId = button.getAttribute('data-tab');
            if (!tabId) return;

            event.preventDefault();
            event.stopPropagation();

            if (tabId === 'appointments') {
                if (typeof window.diorToggleApptDropdown === 'function') {
                    window.diorToggleApptDropdown(event);
                } else {
                    var w = document.getElementById('dior-appt-dropdown-wrap');
                    if (w) {
                        var isOpen = w.classList.contains('open');
                        if (isOpen) {
                            w.classList.remove('open');
                            diorSetApptToggleIcon(w, false);
                        } else {
                            w.classList.add('open');
                            diorSetApptToggleIcon(w, true);
                            activate('appointments', true);
                            if (typeof window.diorSwitchApptSubTab === 'function') {
                                window.diorSwitchApptSubTab('book');
                            }
                        }
                    }
                }
            } else {
                activate(tabId, true);
            }

            // Close the mobile drawer after a successful selection.
            if (window.innerWidth <= 1024 && typeof window.diorCloseMobileDrawer === 'function') {
                window.diorCloseMobileDrawer();
            }
        }, true);

        // Restore a valid tab and subtab from the URL, otherwise keep Overview active.
        var hash = diorInitialHash || window.location.hash || '';
        var requestedTab = 'overview';
        var requestedSubTab = null;

        if (hash.indexOf('#tab=') === 0) {
            var raw = hash.substring(5);
            if (raw.indexOf('&subtab=') !== -1) {
                var parts = raw.split('&subtab=');
                requestedTab = decodeURIComponent(parts[0]);
                requestedSubTab = decodeURIComponent(parts[1]);
            } else {
                requestedTab = decodeURIComponent(raw);
            }
        } else {
            var active = app.querySelector('.dior-nav-btn.active[data-tab]');
            requestedTab = active ? active.getAttribute('data-tab') : 'overview';
        }

        if (requestedTab === 'appointments') {
            activate('appointments', false);
            var sub = requestedSubTab || (function(){
                try { return sessionStorage.getItem('dior_active_appt_subtab'); } catch(e){ return null; }
            })() || 'today';
            if (typeof window.diorSwitchApptSubTab === 'function') {
                window.diorSwitchApptSubTab(sub);
            }
        } else {
            if (!activate(requestedTab, false)) activate('overview', false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();

/* ==========================================================================
   Dead-tab guard (patient portal)

   Several older buttons still point at tab ids that are not rendered in
   this dashboard (for example `consultation` — templates/patient-tabs/
   consultation.php is never included by patient-dashboard.php). Clicking
   those used to switch to a panel that does not exist, so nothing visibly
   happened and the button looked broken.

   Instead of failing silently we forward such ids to the tab that actually
   owns that content. Anything still unresolved falls back to the current
   tab, so a stale attribute can never leave the user on a blank view.
   ========================================================================== */
(function () {
    'use strict';

    var ALIASES = {
        consultation: 'telemedicine',   // consultation.php is an orphan template
        profile: 'settings',            // profile form lives inside Settings
        docs: 'documents',
        meds: 'docs_meds'
    };

    function resolve(tabId) {
        if (!tabId) return null;
        var id = String(tabId).split('&subtab=')[0].replace(/^#tab=/, '');
        if (id && document.getElementById('tab-' + id)) return null;   // real tab, leave alone
        var mapped = ALIASES[id];
        if (mapped && document.getElementById('tab-' + mapped)) return mapped;
        return null;
    }

    function patch() {
        if (typeof window.diorSwitchTab !== 'function' || window.diorSwitchTab.__diorAliased) return;

        var original = window.diorSwitchTab;
        var wrapped = function (tabId) {
            var mapped = resolve(tabId);
            if (mapped) {
                try {
                    console.info('[Dior] tab "' + tabId + '" is not rendered; opening "' + mapped + '"');
                } catch (e) { }
                tabId = mapped;
            }
            return original.apply(this, arguments.length ? [tabId].concat([].slice.call(arguments, 1)) : [tabId]);
        };
        wrapped.__diorAliased = true;
        window.diorSwitchTab = wrapped;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', patch);
    } else {
        patch();
    }
    // dior-dashboard.js may finish after this file; retry once the page is idle.
    window.addEventListener('load', patch);
})();
