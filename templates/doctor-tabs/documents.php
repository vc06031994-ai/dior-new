<?php
/**
 * Doctor Dashboard — Documents & Consent
 * Tab: Upload Documents (#tab-doc-documents-upload)
 */
global $wpdb;
$doc_table = $wpdb->prefix . 'dior_documents';
$patients_table = $wpdb->prefix . 'dior_patients';
$users_table = $wpdb->users;

$doctor_doc_nonce = wp_create_nonce('dior_doctor_nonce');
$current_doctor_user = wp_get_current_user();
$doctor_display_name = $current_doctor_user->display_name ?: 'Doctor';

// 1. Fetch real documents from database
$doc_records = $wpdb->get_results("
    SELECT d.*,
           COALESCE(p.patient_uid, CONCAT('DM-', (10000 + d.patient_id))) AS patient_code,
           COALESCE(NULLIF(CONCAT(p.first_name, ' ', p.last_name), ' '), u.display_name, 'Patient') AS patient_name,
           p.avatar_url AS patient_avatar,
           u.user_email AS patient_email,
           COALESCE(author.display_name, 'Staff') AS author_name
    FROM $doc_table d
    LEFT JOIN $patients_table p ON p.user_id = d.patient_id
    LEFT JOIN $users_table u ON u.ID = d.patient_id
    LEFT JOIN $users_table author ON author.ID = d.author_id
    WHERE d.is_deleted = 0
    ORDER BY d.created_at DESC
    LIMIT 300
", ARRAY_A);

// 2. Fetch all registered patients for the upload modal dropdown
$dp_map = [];
$dp_list = $wpdb->get_results("SELECT user_id, patient_uid, first_name, last_name, email FROM $patients_table WHERE status <> 'deleted'", ARRAY_A);
if (!empty($dp_list)) {
    foreach ($dp_list as $dp) {
        $dp_map[(int)$dp['user_id']] = $dp;
    }
}

$patient_subscribers = get_users([
    'role__in' => ['patient', 'subscriber', 'customer'],
    'orderby'  => 'display_name',
    'order'    => 'ASC',
    'number'   => 500
]);

$doctor_patient_dropdown = [];
$seen_patients = [];
foreach ($patient_subscribers as $ps) {
    $p_meta = $dp_map[$ps->ID] ?? null;
    $p_code = $p_meta['patient_uid'] ?? ('DM-' . (10000 + $ps->ID));
    $p_name = !empty($p_meta['first_name']) ? trim($p_meta['first_name'] . ' ' . ($p_meta['last_name'] ?? '')) : $ps->display_name;
    $doctor_patient_dropdown[] = [
        'user_id' => $ps->ID,
        'code'    => $p_code,
        'name'    => $p_name,
        'email'   => $ps->user_email,
    ];
    $seen_patients[$ps->ID] = true;
}

if (!empty($dp_list)) {
    foreach ($dp_list as $dp) {
        $uid = (int)$dp['user_id'];
        if ($uid > 0 && empty($seen_patients[$uid])) {
            $doctor_patient_dropdown[] = [
                'user_id' => $uid,
                'code'    => $dp['patient_uid'] ?: ('DM-' . (10000 + $uid)),
                'name'    => trim($dp['first_name'] . ' ' . ($dp['last_name'] ?? '')),
                'email'   => $dp['email'] ?? '',
            ];
            $seen_patients[$uid] = true;
        }
    }
}

if (!function_exists('dior_docs_format_date')) {
    function dior_docs_format_date($value)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') return '';
        $stamp = strtotime($value);
        return $stamp ? date_i18n('M j, Y', $stamp) : (string) $value;
    }
}
?>
<section class="dior-tab-panel" id="tab-doc-documents-upload" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Upload Documents</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list"
                style="display: flex; gap: 8px; list-style: none; padding: 0; margin: 0; align-items: center;">
                <li><a href="#"><i class="fas fa-house" style="font-size: 14px; color: #1A528E;"></i></a></li>
                <li><span style="color: #94A3B8;">/</span></li>
                <li><a href="javascript:void(0)"
                        style="color: #64748B; text-decoration: none; font-size: 14px;">Documents & Consent</a></li>
                <li><span style="color: #94A3B8;">/</span></li>
                <li class="active"><span style="color: #1E293B; font-weight: 700; font-size: 14px;">Upload
                        Documents</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-documents-upload" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-documents-upload');">
            <i class="fas fa-file-arrow-up"></i> Upload Documents
        </button>
    </div>

    <div class="docs-card">
        <div class="docs-header-container">
            <div class="docs-title-box">
                <h2>Upload Documents</h2>
                <div class="docs-title-line"></div>
            </div>

            <div class="docs-actions-wrapper">
                <div class="input-group input-group-sm dior-bs-search-group">
                    <input type="text" id="doc-upload-search-input" class="form-control border-start-0 ps-0" placeholder="Search records..." onkeyup="diorFilterDoctorUploadDocs(this.value)">
                </div>
                <div class="docs-actions-group">
                    <button type="button" class="docs-btn-letter btn" id="dior-doc-letter-btn"
                        aria-label="Write a letter for a patient" title="Write Patient Letter"
                        onclick="diorOpenLetterModal()">
                        <i class="fas fa-pen-nib"></i><span>Write Letter</span>
                    </button>
                    <button type="button" class="docs-icon-btn docs-btn-primary btn" id="dior-doc-upload-btn"
                        aria-label="Upload patient document" title="Upload Patient Document"
                        onclick="diorOpenDoctorDocUploadModal()">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="docs-icon-btn docs-btn-success btn" id="dior-doc-upload-export-btn"
                        aria-label="Export documents to CSV" title="Export to CSV"
                        onclick="diorExportDoctorDocsCSV()">
                        <i class="fas fa-file-arrow-down"></i>
                    </button>
                    <button type="button" class="docs-icon-btn docs-btn-info btn" id="dior-doc-upload-refresh-btn"
                        aria-label="Refresh document list" title="Refresh"
                        onclick="window.location.reload()">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="docs-table-wrapper">
            <table class="docs-table table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center"><input type="checkbox"
                                class="docs-checkbox form-check-input" onclick="diorToggleAllDoctorDocs(this)"></th>
                        <th>DOCUMENT ID <i class="fas fa-sort"></i></th>
                        <th>PATIENT <i class="fas fa-sort"></i></th>
                        <th>DOCUMENT TYPE <i class="fas fa-sort"></i></th>
                        <th>DOCUMENT NAME <i class="fas fa-sort"></i></th>
                        <th>UPLOAD DATE <i class="fas fa-sort"></i></th>
                        <th>UPLOADED BY <i class="fas fa-sort"></i></th>
                        <th>STATUS <i class="fas fa-sort"></i></th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="doc-uploaded-tbody">
                    <?php if (!empty($doc_records)): ?>
                        <?php foreach ($doc_records as $doc): ?>
                            <?php
                            $doc_uid = $doc['doc_uid'] ?: ('DOC-' . $doc['id']);
                            $p_id = (int)$doc['patient_id'];
                            $p_name = $doc['patient_name'] ?: 'Patient';
                            $p_avatar = $doc['patient_avatar'] ?: ('https://ui-avatars.com/api/?name=' . urlencode($p_name) . '&background=random');
                            $doc_date = date('M j, Y', strtotime($doc['created_at']));
                            $download_url = add_query_arg([
                                'dior_action' => 'download_doc',
                                'doc_id'      => $doc_uid,
                                'patient_id'  => $p_id
                            ], home_url('/'));
                            ?>
                            <tr id="doc-row-<?php echo esc_attr($doc_uid); ?>" data-patient-id="<?php echo esc_attr($p_id); ?>" data-doc-uid="<?php echo esc_attr($doc_uid); ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="docs-checkbox form-check-input" value="<?php echo esc_attr($doc_uid); ?>">
                                </td>
                                <td data-label="Document ID">
                                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small"><?php echo esc_html($doc_uid); ?></span>
                                </td>
                                <td data-label="Patient">
                                    <div class="docs-user-profile d-flex align-items-center gap-2">
                                        <img src="<?php echo esc_url($p_avatar); ?>" class="docs-user-avatar rounded-circle" style="width: 32px; height: 32px; object-fit: cover;" alt="<?php echo esc_attr($p_name); ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($p_name); ?>&background=random'">
                                        <div>
                                            <span class="docs-user-name fw-semibold text-dark d-block"><?php echo esc_html($p_name); ?></span>
                                            <small class="text-muted"><?php echo esc_html($doc['patient_code'] ?: ('ID: ' . $p_id)); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Document Type"><?php echo esc_html($doc['category'] ?: 'Clinical Record'); ?></td>
                                <td data-label="Document Name">
                                    <div class="fw-semibold text-dark"><?php echo esc_html($doc['title'] ?: 'Medical Document'); ?></div>
                                    <small class="text-muted"><?php echo esc_html(($doc['file_type'] ?: 'PDF') . ' • ' . ($doc['file_size_formatted'] ?: '150 KB')); ?></small>
                                </td>
                                <td data-label="Upload Date"><i class="far fa-calendar docs-icon-blue me-1"></i> <?php echo esc_html($doc_date); ?></td>
                                <td data-label="Uploaded By"><?php echo esc_html($doc['author_name'] ?: 'Doctor'); ?></td>
                                <td data-label="Status"><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small"><i class="fas fa-circle-check me-1"></i> Available</span></td>
                                <td data-label="Actions">
                                    <div class="docs-row-actions d-flex gap-1">
                                        <a href="<?php echo esc_url($download_url); ?>" target="_blank" class="docs-action-btn-sm docs-btn-edit btn" title="Download Document" download>
                                            <i class="fas fa-file-arrow-down"></i>
                                        </a>
                                        <button type="button" class="docs-action-btn-sm docs-btn-delete btn" title="Delete Document" onclick="diorDoctorDeleteDoc(this, '<?php echo esc_attr($doc_uid); ?>', <?php echo $p_id; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="dior-doc-empty-row">
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                No uploaded documents found. Click "+" to upload a patient document.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="va-footer dior-docs-footer">
            <div class="va-pagination-text docs-pagination-text">
                <strong>0</strong> documents total
            </div>
        </div>
    </div>

    <script>
    window.diorOpenDoctorDocUploadModal = function() {
        var modalEl = document.getElementById('diorDoctorDocUploadModal');
        if (!modalEl) return;
        // Templates cached from before this fix still render the modal inside
        // #tab-doc-documents-upload. If that panel is display:none (which is
        // the case on the other two sub-tabs) Bootstrap would only paint the
        // backdrop - hoist the modal to <body> first.
        if (modalEl.closest('.dior-tab-panel')) {
            document.body.appendChild(modalEl);
        }
        var form = document.getElementById('dior-doctor-doc-upload-form');
        if (form) form.reset();
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    };

    /* ── Write Patient Letter ────────────────────────────────────────────────
       Replaces the removed Consent Templates / Signed Consent screens with a
       single action: the doctor writes a letter, and it lands in the patient's
       Documents & Reports tab. Publishing reuses the existing
       dior_doctor_save_letter endpoint (wp_dior_documents, is_letter = 1). */
    window.diorLetterSyncTitle = function () {
        var typeEl = document.getElementById('dior-letter-type');
        var titleEl = document.getElementById('dior-letter-title');
        if (!typeEl || !titleEl) return;
        if (titleEl.dataset.diorTouched === '1' && titleEl.value.trim() !== '') return;
        titleEl.value = typeEl.value;
    };

    window.diorOpenLetterModal = function () {
        var modalEl = document.getElementById('diorDoctorLetterModal');
        if (!modalEl) return;
        if (modalEl.closest('.dior-tab-panel')) {
            document.body.appendChild(modalEl);
        }
        var form = document.getElementById('dior-doctor-letter-form');
        if (form) {
            form.reset();
            // reset() empties the date inputs, so re-apply sensible defaults
            // (today, and three days out) instead of posting empty strings.
            var today = new Date();
            var iso = function (d) { return d.toISOString().slice(0, 10); };
            var plus3 = new Date(today.getTime() + 3 * 86400000);
            var fromEl = document.getElementById('dior-letter-from');
            var retEl = document.getElementById('dior-letter-return');
            if (fromEl) fromEl.value = iso(today);
            if (retEl) retEl.value = iso(plus3);
            var titleEl = document.getElementById('dior-letter-title');
            if (titleEl) {
                titleEl.dataset.diorTouched = '';
                titleEl.value = (document.getElementById('dior-letter-type') || {}).value || 'Work Absence Note';
            }
        }
        var submit = document.getElementById('dior-letter-submit-btn');
        if (submit) {
            submit.disabled = false;
            submit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Publish to Patient';
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        var titleEl = document.getElementById('dior-letter-title');
        if (titleEl) {
            titleEl.addEventListener('input', function () { titleEl.dataset.diorTouched = '1'; });
        }

        var form = document.getElementById('dior-doctor-letter-form');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var patientId = (document.getElementById('dior-letter-patient') || {}).value || '';
            var letterType = (document.getElementById('dior-letter-type') || {}).value || 'Work Absence Note';
            var letterTitle = ((document.getElementById('dior-letter-title') || {}).value || '').trim();
            var body = ((document.getElementById('dior-letter-body') || {}).value || '').trim();

            if (!patientId) {
                if (window.Swal) Swal.fire({icon: 'warning', title: 'Select a patient', text: 'Choose the patient this letter is for.'});
                return;
            }
            if (!letterTitle) {
                if (window.Swal) Swal.fire({icon: 'warning', title: 'Title required', text: 'Give the letter a title.'});
                return;
            }
            if (!body) {
                if (window.Swal) Swal.fire({icon: 'warning', title: 'Letter is empty', text: 'Write the letter body before publishing.'});
                return;
            }

            var submit = document.getElementById('dior-letter-submit-btn');
            if (submit) {
                submit.disabled = true;
                submit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Publishing…';
            }

            var paragraphs = body.split(/\n\s*\n/).map(function (block) {
                var text = block.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return '<p>' + text.split(/\n/).join('<br>') + '</p>';
            }).join('');

            var fd = new FormData();
            fd.append('action', 'dior_doctor_save_letter');
            fd.append('nonce', (window.dior_doctor_vars && window.dior_doctor_vars.nonce) ||
                               (window.dior_vars && window.dior_vars.nonce) || '');
            fd.append('patient_id', patientId);
            fd.append('letter_type', letterType);
            fd.append('letter_title', letterTitle);
            fd.append('content_html', paragraphs);
            fd.append('excused_from', (document.getElementById('dior-letter-from') || {}).value || '');
            fd.append('return_date', (document.getElementById('dior-letter-return') || {}).value || '');
            fd.append('restrictions', '');
            fd.append('remarks', '');

            var ajaxUrl = (window.dior_doctor_vars && window.dior_doctor_vars.ajax_url) ||
                          (window.dior_vars && window.dior_vars.ajax_url) ||
                          '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (!res || !res.success) {
                        throw new Error((res && res.data && res.data.message) || 'The letter could not be published.');
                    }
                    var modalEl = document.getElementById('diorDoctorLetterModal');
                    if (window.bootstrap && bootstrap.Modal && modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    } else if (modalEl) {
                        modalEl.classList.remove('show');
                        modalEl.style.display = 'none';
                    }
                    form.reset();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Letter Published',
                            text: (res.data && res.data.message) || 'The patient can now see this letter in their portal.'
                        });
                    }
                })
                .catch(function (err) {
                    if (window.Swal) {
                        Swal.fire({icon: 'error', title: 'Publish Failed', text: err.message});
                    } else {
                        alert('Publish failed: ' + err.message);
                    }
                })
                .then(function () {
                    if (submit) {
                        submit.disabled = false;
                        submit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Publish to Patient';
                    }
                });
        });
    });

    window.diorFilterDoctorUploadDocs = function(val) {
        var q = (val || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#doc-uploaded-tbody tr');
        rows.forEach(function(r) {
            if (r.id === 'dior-doc-empty-row') return;
            var text = r.textContent.toLowerCase();
            r.style.display = text.indexOf(q) !== -1 ? '' : 'none';
        });
    };

    window.diorToggleAllDoctorDocs = function(masterCb) {
        var cbs = document.querySelectorAll('#doc-uploaded-tbody .docs-checkbox');
        cbs.forEach(function(cb) {
            cb.checked = masterCb.checked;
        });
    };

    window.diorDoctorDeleteDoc = function(btn, docUid, patientId) {
        var row = btn.closest('tr');
        var executeDelete = function() {
            var fd = new FormData();
            fd.append('action', 'dior_delete_document');
            var nonce = (window.dior_doctor_vars && window.dior_doctor_vars.nonce) ||
                        (window.dior_vars && window.dior_vars.nonce) ||
                        '<?php echo esc_js($doctor_doc_nonce); ?>';
            fd.append('nonce', nonce);
            fd.append('doc_id', docUid);
            fd.append('patient_id', patientId);
            
            var ajaxUrl = (window.dior_doctor_vars && window.dior_doctor_vars.ajax_url) ||
                          (window.dior_vars && window.dior_vars.ajax_url) ||
                          '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
                          
            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (!res.success) throw new Error((res.data && res.data.message) || 'Failed to delete');
                if (row) row.remove();
                if (window.Swal) {
                    Swal.fire({position: 'top-end', icon: 'success', title: 'Document Removed', showConfirmButton: false, timer: 1400});
                }
            })
            .catch(function(err) {
                if (window.Swal) {
                    Swal.fire({icon: 'error', title: 'Delete Failed', text: err.message});
                } else {
                    alert('Delete failed: ' + err.message);
                }
            });
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Delete Document?',
                text: 'This document will be removed from clinic and patient records.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Delete'
            }).then(function(r) {
                if (r.isConfirmed) executeDelete();
            });
        } else {
            if (confirm('Are you sure you want to delete this document?')) {
                executeDelete();
            }
        }
    };

    window.diorExportDoctorDocsCSV = function() {
        var rows = document.querySelectorAll('#doc-uploaded-tbody tr');
        var csv = ['Document ID,Patient,Category,Title,Upload Date,Uploaded By,Status'];
        rows.forEach(function(r) {
            if (r.id === 'dior-doc-empty-row' || r.style.display === 'none') return;
            var cols = r.querySelectorAll('td');
            if (cols.length < 8) return;
            var docId = cols[1].innerText.trim();
            var patient = cols[2].querySelector('.docs-user-name') ? cols[2].querySelector('.docs-user-name').innerText.trim() : cols[2].innerText.trim();
            var category = cols[3].innerText.trim();
            var title = cols[4].querySelector('.fw-semibold') ? cols[4].querySelector('.fw-semibold').innerText.trim() : cols[4].innerText.trim();
            var date = cols[5].innerText.trim();
            var author = cols[6].innerText.trim();
            var status = cols[7].innerText.trim();
            csv.push(['"' + docId + '"', '"' + patient + '"', '"' + category + '"', '"' + title + '"', '"' + date + '"', '"' + author + '"', '"' + status + '"'].join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', 'doctor_uploaded_documents.csv');
        document.body.appendChild(a);
        a.click();
        a.remove();
    };

    (function() {
        function initDocUploadHandler() {
            var form = document.getElementById('dior-doctor-doc-upload-form');
            if (!form || form.dataset.listenerBound) return;
            form.dataset.listenerBound = 'true';
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('doctor-doc-upload-submit-btn');
                var patientSelect = document.getElementById('doctor-doc-upload-patient');
                var patientId = patientSelect ? patientSelect.value : '';
                if (!patientId) {
                    if (window.Swal) Swal.fire({icon: 'warning', title: 'Please select a patient'});
                    return;
                }
                var patientOptionText = patientSelect.options[patientSelect.selectedIndex].text;
                var patientName = patientOptionText.split('(')[0].trim();
                
                var fd = new FormData(form);
                fd.append('action', 'dior_upload_document');
                var nonce = (window.dior_doctor_vars && window.dior_doctor_vars.nonce) ||
                            (window.dior_vars && window.dior_vars.nonce) ||
                            '<?php echo esc_js($doctor_doc_nonce); ?>';
                fd.append('nonce', nonce);
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Uploading...';
                
                var ajaxUrl = (window.dior_doctor_vars && window.dior_doctor_vars.ajax_url) ||
                              (window.dior_vars && window.dior_vars.ajax_url) ||
                              '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
                              
                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                })
                .then(function(res) { return res.json(); })
                .then(function(result) {
                    if (!result.success) {
                        throw new Error((result.data && result.data.message) || 'Upload failed.');
                    }
                    
                    var modalEl = document.getElementById('diorDoctorDocUploadModal');
                    if (modalEl && window.bootstrap && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(modalEl);
                        if (inst) inst.hide();
                    }
                    
                    form.reset();
                    
                    if (window.Swal) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: 'Document Uploaded Successfully',
                            text: 'The document is now available in patient records.',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                    
                    var tbody = document.getElementById('doc-uploaded-tbody');
                    var doc = result.data.document || {};
                    var docUid = doc.id || '';
                    var docTitle = doc.title || fd.get('doc_title');
                    var docCat = doc.category || fd.get('doc_category');
                    var pName = doc.patient_name || patientName;
                    var pCode = doc.patient_code || ('DM-' + (10000 + parseInt(patientId, 10)));
                    var pAvatar = doc.patient_avatar ||
                        ('https://ui-avatars.com/api/?name=' + encodeURIComponent(pName) + '&background=random');
                    var docType = doc.file_type || 'PDF';
                    var docSize = doc.size || '';
                    var docDate = doc.date || 'Just now';
                    var downloadUrl = '<?php echo esc_js(home_url('/')); ?>?dior_action=download_doc&doc_id=' +
                        encodeURIComponent(docUid) + '&patient_id=' + encodeURIComponent(patientId);

                    // Build the row with the same cells the PHP template renders,
                    // so the client-side insert matches a server-rendered row
                    // (including the data-labels the mobile card layout needs).
                    var tr = document.createElement('tr');
                    tr.id = 'doc-row-' + docUid;
                    tr.setAttribute('data-patient-id', patientId);
                    tr.setAttribute('data-doc-uid', docUid);
                    tr.innerHTML = '<td class="text-center"><input type="checkbox" class="docs-checkbox form-check-input" value="' + docUid + '"></td>' +
                        '<td data-label="Document ID"><span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">' + docUid + '</span></td>' +
                        '<td data-label="Patient"><div class="docs-user-profile d-flex align-items-center gap-2">' +
                        '<img src="' + pAvatar + '" class="docs-user-avatar rounded-circle" style="width: 32px; height: 32px; object-fit: cover;" alt="' + pName + '">' +
                        '<div><span class="docs-user-name fw-semibold text-dark d-block">' + pName + '</span>' +
                        '<small class="text-muted">' + pCode + '</small></div></div></td>' +
                        '<td data-label="Document Type">' + docCat + '</td>' +
                        '<td data-label="Document Name"><div class="fw-semibold text-dark">' + docTitle + '</div>' +
                        (docType ? '<small class="text-muted">' + docType + (docSize ? ' &bull; ' + docSize : '') + '</small>' : '') + '</td>' +
                        '<td data-label="Upload Date"><i class="far fa-calendar docs-icon-blue me-1"></i> ' + docDate + '</td>' +
                        '<td data-label="Uploaded By"><?php echo esc_js($doctor_display_name); ?></td>' +
                        '<td data-label="Status"><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small"><i class="fas fa-circle-check me-1"></i> Available</span></td>' +
                        '<td data-label="Actions"><div class="docs-row-actions d-flex gap-1">' +
                        '<a href="' + downloadUrl + '" target="_blank" class="docs-action-btn-sm docs-btn-edit btn" title="Download Document" download><i class="fas fa-file-arrow-down"></i></a>' +
                        '<button type="button" class="docs-action-btn-sm docs-btn-delete btn" title="Delete Document"' +
                        ' onclick="diorDoctorDeleteDoc(this, \'' + docUid + '\', ' + parseInt(patientId, 10) + ')"><i class="fas fa-trash"></i></button>' +
                        '</div></td>';

                    // Uploaded documents land on the Upload tab, but the Consent
                    // Forms tab filters the same table by category. A consent
                    // uploaded from that tab has to appear there too (it used to
                    // only show up after a manual page reload).
                    var api = window.DiorDocuments || {};
                    var targetTbody = tbody;
                    if (typeof api.isConsentCategory === 'function' &&
                        typeof api.buildSignedConsentRow === 'function' &&
                        api.isConsentCategory(docCat)) {
                        var signedBody = document.getElementById('doc-signed-consents-tbody');
                        if (signedBody) {
                            var scRow = api.buildSignedConsentRow(doc, patientId, pName, pCode, pAvatar, docCat, docTitle, docDate, downloadUrl);
                            var scEmpty = document.getElementById('dior-sc-empty-row');
                            if (scEmpty) scEmpty.remove();
                            signedBody.insertBefore(scRow, signedBody.firstChild);
                        }
                    }
                    if (targetTbody) {
                        var emptyRow = document.getElementById('dior-doc-empty-row');
                        if (emptyRow) emptyRow.remove();
                        targetTbody.insertBefore(tr, targetTbody.firstChild);
                    }
                    if (window.DiorDocuments && window.DiorDocuments.syncCounters) {
                        window.DiorDocuments.syncCounters();
                    }
                })
                .catch(function(err) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: err.message || 'An error occurred while uploading the document.'
                        });
                    } else {
                        alert('Upload failed: ' + err.message);
                    }
                })
                .finally(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Upload Document';
                });
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDocUploadHandler);
        } else {
            initDocUploadHandler();
        }
    })();
    </script>
</section>

<!-- ==============================================
     2) CONSENT TEMPLATES PANE
     ============================================== -->
<!-- ==============================================
     3) SIGNED CONSENT PANE 
     ============================================== -->
<!-- ══════════════════════════════════════════════════════════════════════════
     Doctor Document Upload Modal

     This modal is rendered OUTSIDE all three .dior-tab-panel sections on
     purpose. It used to live inside #tab-doc-documents-upload, which is
     display:none whenever another sub-tab is open - so clicking "+" from
     Consent Forms / Manage Templates only produced a Bootstrap backdrop
     with no visible dialog (the backdrop is appended to <body>, the dialog
     was still inside the hidden panel). Keeping it here means it works from
     every sub-tab.
     ══════════════════════════════════════════════════════════════════════════ -->
    <!-- Doctor Document Upload Modal (Strict 100% Bootstrap 5 Standard) -->
    <div class="modal fade" id="diorDoctorDocUploadModal" tabindex="-1" aria-labelledby="diorDoctorDocUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                    <h5 class="modal-title fw-bold text-dark fs-5 mb-0" id="diorDoctorDocUploadModalLabel">
                        <i class="fas fa-file-arrow-up text-primary me-2"></i> Upload Patient Document
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="dior-doctor-doc-upload-form" enctype="multipart/form-data">
                    <div class="modal-body p-4 bg-white">
                        <p class="text-secondary small mb-3">
                            Select a patient and attach clinical records, lab results, prescriptions, or imaging scans. The document will immediately be accessible in the selected patient's dashboard.
                        </p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small text-uppercase text-secondary fw-semibold mb-1" for="doctor-doc-upload-patient">
                                    Select Patient <span class="text-danger">*</span>
                                </label>
                                <select class="form-select rounded-3 py-2" id="doctor-doc-upload-patient" name="patient_id" required>
                                    <option value="">-- Choose Patient --</option>
                                    <?php foreach ($doctor_patient_dropdown as $popt): ?>
                                        <option value="<?php echo esc_attr($popt['user_id']); ?>">
                                            <?php echo esc_html($popt['name'] . ' (' . $popt['code'] . (!empty($popt['email']) ? ' - ' . $popt['email'] : '') . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small text-uppercase text-secondary fw-semibold mb-1" for="doctor-doc-upload-title">
                                    Document Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control rounded-3 py-2" id="doctor-doc-upload-title" name="doc_title" placeholder="e.g. Lab Results - Complete Blood Count" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small text-uppercase text-secondary fw-semibold mb-1" for="doctor-doc-upload-category">
                                    Document Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select rounded-3 py-2" id="doctor-doc-upload-category" name="doc_category" required>
                                    <option value="Lab Report">Lab Report</option>
                                    <option value="Clinical Record">Clinical Record</option>
                                    <option value="Diagnostic & Scans">Diagnostic &amp; Scans</option>
                                    <option value="Prescription">Prescription</option>
                                    <option value="Procedure">Procedure</option>
                                    <option value="ID Proof">ID Proof</option>
                                    <option value="Consent Form">Consent Form</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-uppercase text-secondary fw-semibold mb-1" for="doctor-doc-upload-file">
                                    Document File <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control rounded-3 py-2" id="doctor-doc-upload-file" name="doc_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" required>
                                <div class="form-text text-muted small mt-1">
                                    <i class="fas fa-circle-info text-primary"></i> Supported formats: PDF, PNG, JPG, JPEG, DOC, DOCX (Max 25MB)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-3 px-4 bg-white d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm" id="doctor-doc-upload-submit-btn">
                            <i class="fas fa-cloud-arrow-up me-1"></i> Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Write Patient Letter — rendered outside #tab-doc-documents-upload so it is
     never hidden behind an inactive tab panel. -->
<div class="modal fade" id="diorDoctorLetterModal" tabindex="-1" aria-labelledby="diorDoctorLetterModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold text-dark fs-5 mb-0" id="diorDoctorLetterModalLabel">
                    <i class="fas fa-pen-nib text-primary me-2"></i> Write Patient Letter
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dior-doctor-letter-form">
                <div class="modal-body p-4 bg-white">
                    <p class="text-secondary small mb-3">
                        The letter is published to the patient's Documents &amp; Reports tab the moment you submit it.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="dior-letter-patient" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Patient <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3 py-2" id="dior-letter-patient" name="patient_id" required>
                                <option value="">Select a patient…</option>
                                <?php foreach ($doctor_patient_dropdown as $dp): ?>
                                    <option value="<?php echo esc_attr($dp['user_id']); ?>">
                                        <?php echo esc_html($dp['name']); ?> (<?php echo esc_html($dp['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="dior-letter-type" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Letter Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3 py-2" id="dior-letter-type" name="letter_type" required
                                onchange="diorLetterSyncTitle()">
                                <option value="Work Absence Note">Work Absence Note</option>
                                <option value="School Excuse Note">School Excuse Note</option>
                                <option value="Return to Work Clearance">Return to Work Clearance</option>
                                <option value="Fit to Travel Letter">Fit to Travel Letter</option>
                                <option value="Medical Attestation">Medical Attestation</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label for="dior-letter-title" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Letter Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control rounded-3 py-2" id="dior-letter-title" name="letter_title"
                                placeholder="e.g. Work Absence Note - Acute Bronchitis" required>
                        </div>

                        <div class="col-md-6">
                            <label for="dior-letter-from" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Excused From
                            </label>
                            <input type="date" class="form-control rounded-3 py-2" id="dior-letter-from" name="excused_from"
                                value="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="dior-letter-return" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Return Date
                            </label>
                            <input type="date" class="form-control rounded-3 py-2" id="dior-letter-return" name="return_date"
                                value="<?php echo esc_attr(date('Y-m-d', strtotime('+3 days'))); ?>">
                        </div>

                        <div class="col-md-12">
                            <label for="dior-letter-body" class="form-label small text-uppercase text-secondary fw-semibold mb-1">
                                Letter Body <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control rounded-3 py-2" id="dior-letter-body" name="letter_body" rows="8"
                                placeholder="Write the letter the patient will receive…" required></textarea>
                            <div class="form-text text-muted small mt-1">
                                <i class="fas fa-circle-info text-primary"></i>
                                The patient header, clinic letterhead, signature and verification token are added automatically.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-white d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 fw-semibold"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm"
                        id="dior-letter-submit-btn">
                        <i class="fas fa-paper-plane me-1"></i> Publish to Patient
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
