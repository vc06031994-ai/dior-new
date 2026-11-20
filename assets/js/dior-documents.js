/**
 * DIOR Medical — Documents & Consent
 *
 * Handles the actions on the Upload Documents screen: "Add new record",
 * export, refresh, bulk delete, row edit (title/category) and single-row
 * selection sync.
 *
 * The Consent Templates and Signed Consent screens were removed together with
 * their toolbars, panels, sidebar rows and JS. Documents uploaded here still
 * reach the patient portal as before.
 *
 * Helpers keep this screen consistent with the pharmacy module (same toast
 * language, same AJAX/nonce handling, no silent fake saves).
 */
(function () {
    'use strict';

    var UPLOAD_TAB = 'doc-documents-upload';

    function vars() {
        return window.dior_doctor_vars || {};
    }

    function ajaxUrl() {
        return vars().ajax_url || '';
    }

    function nonce() {
        return vars().nonce || '';
    }

    function hasBackend() {
        return !!(ajaxUrl() && nonce());
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toast(title, icon) {
        if (!window.Swal) return;
        window.Swal.fire({
            title: title,
            icon: icon || 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: icon === 'error' ? 4200 : 2600,
            timerProgressBar: true
        });
    }

    function request(action, payload) {
        if (!hasBackend()) {
            return Promise.reject(new Error('This dashboard page is not connected to the server. Please reload.'));
        }
        var body = new FormData();
        body.append('action', 'dior_doc_' + action);
        body.append('nonce', nonce());
        Object.keys(payload || {}).forEach(function (key) {
            var value = payload[key];
            if (Array.isArray(value)) {
                value.forEach(function (item) { body.append(key + '[]', item); });
            } else if (value !== undefined && value !== null) {
                body.append(key, value);
            }
        });
        return fetch(ajaxUrl(), { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('The server returned an unexpected response.');
                });
            })
            .then(function (json) {
                if (!json || !json.success) {
                    throw new Error((json && json.data && json.data.message) || 'The request could not be completed.');
                }
                return json.data || {};
            });
    }

    function panel(tabId) {
        return document.getElementById('tab-' + tabId);
    }

    // The upload modal is rendered at the end of documents.php, outside the
    // tab panels. If a cached/older template still nests it inside a panel
    // that is display:none, Bootstrap shows the backdrop but the dialog is
    // 0x0 (invisible). Hoisting it to <body> before showing fixes that.
    function ensureUploadModalAtBodyLevel() {
        var modalEl = document.getElementById('diorDoctorDocUploadModal');
        if (!modalEl) return null;
        if (modalEl.closest('.dior-tab-panel')) {
            document.body.appendChild(modalEl);
        }
        return modalEl;
    }

    function rowsOf(tbody) {
        if (!tbody) return [];
        return Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (tr) {
            return !tr.classList.contains('dior-docs-empty-row') && tr.querySelectorAll('td').length > 1;
        });
    }

    function checkedOf(tbody) {
        return rowsOf(tbody).filter(function (tr) {
            var cb = tr.querySelector('input[type="checkbox"]');
            return cb && cb.checked;
        });
    }

    function selectAllIn(scope, checked) {
        scope.querySelectorAll('tbody input[type="checkbox"]').forEach(function (cb) {
            cb.checked = checked;
        });
    }

    function refreshChrome(table) {
        if (table && window.diorRefreshStaticTable) {
            window.diorRefreshStaticTable(table);
        } else if (window.location && table) {
            // Fallback for tables the generic layer does not manage.
            var counter = table.closest('.docs-card');
            if (counter) {
                var info = counter.querySelector('.docs-pagination-text, .va-pagination-text');
                if (info) {
                    var total = rowsOf(table.querySelector('tbody')).length;
                    info.innerHTML = total + ' record' + (total === 1 ? '' : 's') + ' total';
                }
            }
        }
    }

    function ensureEmptyRow(tbody, html, colspan) {
        if (!tbody) return;
        var rows = rowsOf(tbody);
        var existing = tbody.querySelector('.dior-docs-empty-row');
        if (rows.length) {
            if (existing) existing.remove();
            return;
        }
        if (existing) return;
        var tr = document.createElement('tr');
        tr.className = 'dior-docs-empty-row';
        tr.innerHTML = '<td colspan="' + colspan + '" class="dior-docs-empty">' + html + '</td>';
        tbody.appendChild(tr);
    }

    // Categories that make a document show up under Signed Consent Forms
    // (documents.php filters that query on category/title LIKE %Consent%).
    // Builds a Signed Consent row that matches the PHP-rendered markup
    // (10 cells incl. the checkbox, same data-labels for the card layout).
    // ── Export helper (shared by the three screens) ──────────────────────────
    function exportTable(opts) {
        var tbody = document.querySelector(opts.tbody);
        if (!tbody) return;
        var rows = rowsOf(tbody);
        if (!rows.length) {
            toast('There is nothing to export yet.', 'error');
            return;
        }

        var csv = [opts.headers.map(quote).join(',')];
        rows.forEach(function (tr) {
            if (tr.style.display === 'none') return;
            var cells = tr.querySelectorAll('td');
            var line = [];
            for (var i = opts.skipFrom; i <= opts.skipTo; i++) {
                if (!cells[i]) continue;
                var value = (opts.value ? opts.value(tr, i) : cells[i].textContent) || '';
                value = String(value).replace(/\s+/g, ' ').trim();
                line.push(quote(value === '—' ? '' : value));
            }
            if (line.length) csv.push(line.join(','));
        });

        var blob = new Blob(['\ufeff' + csv.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = opts.filename + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        toast(opts.doneMessage || 'Exported.');
    }

    function quote(value) {
        return '"' + String(value === null || value === undefined ? '' : value).replace(/"/g, '""') + '"';
    }

    // ── Refresh: re-pull the panel markup from the server ────────────────────
    function reloadPanel(tabId, trigger) {
        var icon = trigger ? trigger.querySelector('i') : null;
        if (trigger) trigger.disabled = true;
        if (icon) icon.classList.add('fa-spin');

        var finish = function () {
            if (trigger) trigger.disabled = false;
            if (icon) icon.classList.remove('fa-spin');
        };

        return fetch(window.location.href, { credentials: 'same-origin' })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById('tab-' + tabId);
                var current = panel(tabId);
                if (!fresh || !current) {
                    window.location.reload();
                    return;
                }
                // Swap only the two table bodies + footer counters so the swap
                // never re-binds (or loses) the modals and inline handlers.
                fresh.querySelectorAll('tbody').forEach(function (freshBody) {
                    var id = freshBody.id;
                    var target = id ? current.querySelector('tbody#' + id) : null;
                    if (!target) {
                        var index = Array.prototype.indexOf.call(fresh.querySelectorAll('tbody'), freshBody);
                        target = current.querySelectorAll('tbody')[index];
                    }
                    if (target) target.innerHTML = freshBody.innerHTML;
                });
                current.querySelectorAll('.docs-pagination-text, .va-pagination-text').forEach(function (info, i) {
                    var freshInfo = fresh.querySelectorAll('.docs-pagination-text, .va-pagination-text')[i];
                    if (freshInfo) info.innerHTML = freshInfo.innerHTML;
                });
                bindPanel(tabId);
                syncCounters();
                toast('List refreshed.');
            })
            .catch(function () {
                window.location.reload();
            })
            .then(finish, finish);
    }

    // ── Shared overlay (same visual language as the pharmacy edit sheet) ─────
    var OVERLAY_ID = 'dior-docs-overlay';

    function openOverlay(title, bodyHtml, onSubmit, submitLabel) {
        closeOverlay();

        var wrap = document.createElement('div');
        wrap.className = 'dior-docs-overlay';
        wrap.id = OVERLAY_ID;
        wrap.setAttribute('role', 'dialog');
        wrap.setAttribute('aria-modal', 'true');
        wrap.innerHTML =
            '<div class="dior-docs-dialog" role="document">' +
            '<div class="dior-docs-dialog-head">' +
            '<h3 class="dior-docs-dialog-title">' + escapeHtml(title) + '</h3>' +
            '<button type="button" class="dior-docs-dialog-close" aria-label="Close"><i class="fas fa-xmark"></i></button>' +
            '</div>' +
            '<form class="dior-docs-form" novalidate>' + bodyHtml +
            '<div class="dior-docs-actions">' +
            '<button type="submit" class="dior-docs-btn dior-docs-btn-primary">' + escapeHtml(submitLabel || 'Save') + '</button>' +
            '<button type="button" class="dior-docs-btn dior-docs-btn-ghost" data-docs-cancel>Cancel</button>' +
            '</div></form></div>';

        document.body.appendChild(wrap);
        document.body.classList.add('dior-docs-lock');

        function close() {
            closeOverlay();
        }
        function onKey(event) {
            if (event.key === 'Escape') close();
        }
        wrap.querySelector('.dior-docs-dialog-close').addEventListener('click', close);
        wrap.querySelector('[data-docs-cancel]').addEventListener('click', close);
        wrap.addEventListener('mousedown', function (event) {
            if (event.target === wrap) close();
        });
        document.addEventListener('keydown', onKey);
        wrap._diorOnKey = onKey;

        wrap.querySelector('.dior-docs-form').addEventListener('submit', function (event) {
            event.preventDefault();
            onSubmit(event.currentTarget, close);
        });

        var first = wrap.querySelector('input, select, textarea');
        if (first) first.focus();
        return wrap;
    }

    function closeOverlay() {
        var wrap = document.getElementById(OVERLAY_ID);
        if (!wrap) return;
        if (wrap._diorOnKey) document.removeEventListener('keydown', wrap._diorOnKey);
        wrap.remove();
        document.body.classList.remove('dior-docs-lock');
    }

    function fieldHtml(id, label, inputHtml, required) {
        return '<div class="dior-docs-field">' +
            '<label class="dior-docs-label" for="' + id + '">' + escapeHtml(label) +
            (required ? ' <span class="dior-docs-req">*</span>' : '') + '</label>' +
            inputHtml + '</div>';
    }

    function optionsHtml(values, current) {
        var html = '';
        values.forEach(function (value) {
            html += '<option value="' + escapeHtml(value) + '"' +
                (value === current ? ' selected' : '') + '>' + escapeHtml(value) + '</option>';
        });
        if (current && values.indexOf(current) === -1) {
            html += '<option value="' + escapeHtml(current) + '" selected>' + escapeHtml(current) + '</option>';
        }
        return html;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UPLOAD DOCUMENTS
    // ═══════════════════════════════════════════════════════════════════════
    function bindUpload() {
        var root = panel(UPLOAD_TAB);
        if (!root) return;
        var tbody = root.querySelector('#doc-uploaded-tbody');
        var table = tbody ? tbody.closest('table') : null;

        // "Add new record" used to call an undefined global.
        root.querySelectorAll('.docs-actions-group .docs-btn-primary').forEach(function (btn) {
            if (btn.dataset.diorBound || btn.getAttribute('onclick')) return;
            btn.dataset.diorBound = '1';
            btn.setAttribute('title', 'Upload Patient Document');
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                ensureUploadModalAtBodyLevel();
                if (typeof window.diorOpenDoctorDocUploadModal === 'function') {
                    window.diorOpenDoctorDocUploadModal();
                } else {
                    var modalEl = document.getElementById('diorDoctorDocUploadModal');
                    if (modalEl && window.bootstrap) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                }
            });
        });

        // Export
        root.querySelectorAll('.docs-actions-group .docs-btn-success').forEach(function (btn) {
            if (btn.dataset.diorBound || btn.getAttribute('onclick')) return;
            btn.dataset.diorBound = '1';
            btn.setAttribute('title', 'Export to CSV');
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                exportTable({
                    tbody: '#doc-uploaded-tbody',
                    headers: ['Document ID', 'Patient', 'Document Type', 'Document Name', 'Upload Date', 'Uploaded By', 'Status'],
                    skipFrom: 1,
                    skipTo: 7,
                    value: function (tr, index) {
                        if (index === 1) {
                            var name = tr.querySelector('.docs-user-name');
                            return name ? name.textContent : '';
                        }
                        return tr.querySelectorAll('td')[index].textContent;
                    },
                    filename: 'uploaded-documents',
                    doneMessage: 'Documents exported.'
                });
            });
        });

        // Refresh
        root.querySelectorAll('.docs-actions-group .docs-btn-info').forEach(function (btn) {
            if (btn.dataset.diorBound || btn.getAttribute('onclick')) return;
            btn.dataset.diorBound = '1';
            btn.setAttribute('title', 'Refresh');
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                reloadPanel(UPLOAD_TAB, btn);
            });
        });

        // Row selection: enable the toolbar trash once something is picked.
        if (tbody && !tbody.dataset.diorSelBound) {
            tbody.dataset.diorSelBound = '1';
            tbody.addEventListener('change', function (event) {
                if (!event.target.matches('input[type="checkbox"]')) return;
                var all = tbody.querySelectorAll('tbody input[type="checkbox"], input[type="checkbox"]');
                var master = root.querySelector('thead input[type="checkbox"]');
                var boxes = rowsOf(tbody).map(function (tr) { return tr.querySelector('input[type="checkbox"]'); }).filter(Boolean);
                var checked = boxes.filter(function (cb) { return cb.checked; }).length;
                if (master) {
                    master.checked = boxes.length > 0 && checked === boxes.length;
                    master.indeterminate = checked > 0 && checked < boxes.length;
                }
            });
        }

        // Bulk delete goes through the secure-files endpoint so the record is
        // soft-deleted in the DB (not just hidden from the DOM).
        root.querySelectorAll('.docs-actions-group .docs-btn-danger').forEach(function (btn) {
            if (btn.dataset.diorBound) return;
            btn.dataset.diorBound = '1';
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var picked = checkedOf(tbody);
                if (!picked.length) {
                    toast('Please select at least one document to delete.', 'error');
                    return;
                }
                if (!window.Swal) return;
                window.Swal.fire({
                    title: 'Delete ' + picked.length + ' document(s)?',
                    text: 'These documents will be removed from clinic and patient records.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: 'Yes, delete'
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    var deletes = picked.map(function (tr) {
                        var body = new FormData();
                        body.append('action', 'dior_delete_document');
                        body.append('nonce', nonce());
                        body.append('doc_id', tr.getAttribute('data-doc-uid') || '');
                        body.append('patient_id', tr.getAttribute('data-patient-id') || '0');
                        return fetch(ajaxUrl(), { method: 'POST', credentials: 'same-origin', body: body })
                            .then(function (response) { return response.json(); })
                            .then(function (json) {
                                if (json && json.success) {
                                    tr.remove();
                                    return true;
                                }
                                return false;
                            })
                            .catch(function () { return false; });
                    });
                    Promise.all(deletes).then(function (results) {
                        var ok = results.filter(Boolean).length;
                        refreshChrome(table);
                        ensureEmptyRow(tbody,
                            '<i class="fas fa-folder-open"></i><strong>No documents yet</strong>' +
                            '<span>Use the <em>+</em> button above to upload the first patient document.</span>',
                            root.querySelectorAll('thead th').length || 9);
                        if (ok === results.length) {
                            toast(ok + ' document(s) deleted.');
                        } else {
                            toast(ok + ' deleted, ' + (results.length - ok) + ' could not be removed.', 'error');
                        }
                    });
                });
            });
        });

        // Row edit was opening the generic text-only modal. Documents need a
        // title + category editor instead.
        if (tbody && !tbody.dataset.diorEditBound) {
            tbody.dataset.diorEditBound = '1';
            tbody.addEventListener('click', function (event) {
                var btn = event.target.closest('.docs-btn-edit');
                if (!btn || btn.tagName === 'A') return; // download links keep working
                var tr = btn.closest('tr');
                if (!tr) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                openDocumentEditor(tr);
            }, true);
        }
    }

    function openDocumentEditor(tr) {
        var cells = tr.querySelectorAll('td');
        if (cells.length < 8) return;
        var titleEl = cells[4].querySelector('.fw-semibold');
        var title = titleEl ? titleEl.textContent.trim() : '';
        var category = cells[3].textContent.trim();
        var docUid = tr.getAttribute('data-doc-uid') || '';
        var patientId = tr.getAttribute('data-patient-id') || '0';
        var categories = ['Lab Report', 'Clinical Record', 'Diagnostic & Scans', 'Prescription', 'Procedure', 'ID Proof', 'Consent Form', 'Other'];

        var body =
            '<p class="dior-docs-hint"><i class="fas fa-circle-info"></i>' +
            '<span>Document <strong>' + escapeHtml(docUid) + '</strong> — the file itself is never modified.</span></p>' +
            '<div class="dior-docs-grid">' +
            fieldHtml('dior-doc-edit-title', 'Document Title',
                '<input class="dior-docs-input" id="dior-doc-edit-title" name="title" type="text" value="' + escapeHtml(title) + '" required>', true) +
            fieldHtml('dior-doc-edit-category', 'Document Category',
                '<select class="dior-docs-input" id="dior-doc-edit-category" name="category">' +
                optionsHtml(categories, category) + '</select>', true) +
            '</div>';

        openOverlay('Edit Document', body, function (form, close) {
            var submit = form.querySelector('[type="submit"]');
            var payload = {
                doc_uid: docUid,
                patient_id: patientId,
                title: form.title.value.trim(),
                category: form.category.value
            };
            if (!payload.title) {
                toast('Document title is required.', 'error');
                form.title.focus();
                return;
            }
            submit.disabled = true;
            submit.textContent = 'Saving...';

            request('document_update_meta', payload).then(function () {
                cells[4].innerHTML = '<div class="fw-semibold">' + escapeHtml(payload.title) + '</div>' +
                    '<small class="text-muted">' + escapeHtml(cells[4].querySelector('small') ? cells[4].querySelector('small').textContent : '') + '</small>';
                cells[3].textContent = payload.category;
                close();
                toast('Document updated.');
            }).catch(function (error) {
                submit.disabled = false;
                submit.textContent = 'Save Changes';
                toast(error.message, 'error');
            });
        }, 'Save Changes');
    }

    // ── Counters + boot ──────────────────────────────────────────────────────
    function syncCounters() {
        [
            { tab: UPLOAD_TAB, tbody: '#doc-uploaded-tbody', label: 'document' }
        ].forEach(function (item) {
            var root = panel(item.tab);
            if (!root) return;
            // The shared static-table layer re-renders the footer and drops the
            // .docs-pagination-text class, so match its counter element too.
            var info = root.querySelector(
                '.docs-pagination-text, .dior-pagination-count, .va-pagination-text'
            );
            if (!info) return;
            var total = rowsOf(document.querySelector(item.tbody)).length;
            info.innerHTML = '<strong>' + total + '</strong> ' + item.label + (total === 1 ? '' : 's') + ' total';
        });
    }

    function bindPanel(tabId) {
        if (tabId === UPLOAD_TAB) bindUpload();
    }

    function boot() {
        bindUpload();
        syncCounters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(function () {
            bindUpload();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Search is a plain text filter so it works before any AJAX round-trip.
    window.diorFilterConsentRows = function (value, tbodyId) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var query = String(value || '').toLowerCase().trim();
        rowsOf(tbody).forEach(function (tr) {
            tr.style.display = !query || tr.textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
        });
    };

    // Public hooks — used by the upload handler in documents.php so a freshly
    // uploaded document/consent shows up without a page reload. Keep this the
    // single assignment: a second one would silently drop earlier methods.
    window.DiorDocuments = {
        reloadPanel: reloadPanel,
        closeOverlay: closeOverlay,
        syncCounters: syncCounters
    };
})();
