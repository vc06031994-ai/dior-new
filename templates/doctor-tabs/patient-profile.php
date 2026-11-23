<?php
/**
 * Dior Medical - Doctor Dashboard: Professional Patient Profile & Chart
 * 100% Native Bootstrap 5 Component & Utility Layout (Strictly No Custom CSS & No Inline Styles)
 */
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$pp_doctor_id = get_current_user_id();

// Retrieve accessible patient list for the doctor selector
if (current_user_can('manage_options')) {
    $pp_patients = $wpdb->get_results(
        "SELECT user_id, first_name, last_name, patient_uid 
         FROM {$wpdb->prefix}dior_patients 
         WHERE status <> 'deleted' 
         ORDER BY first_name ASC LIMIT 500",
        ARRAY_A
    );
} else {
    $pp_patients = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT p.user_id, p.first_name, p.last_name, p.patient_uid 
         FROM {$wpdb->prefix}dior_patients p 
         LEFT JOIN {$wpdb->prefix}dior_appointments a ON a.patient_id = p.user_id 
         WHERE p.status <> 'deleted' 
           AND (a.doctor_id = %d OR p.user_id IN (SELECT DISTINCT patient_id FROM {$wpdb->prefix}dior_prescriptions WHERE doctor_id = %d))
         ORDER BY p.first_name ASC LIMIT 500",
        $pp_doctor_id,
        $pp_doctor_id
    ), ARRAY_A);
}

$pp_nonce = wp_create_nonce('dior_doctor_nonce');
$pp_ajax_url = admin_url('admin-ajax.php');
?>

<section class="dior-tab-panel" id="tab-doc-patients-profile" style="display:none;" aria-labelledby="pp-page-title">

    <!-- Top Action & Selection Header -->
    <div class="row g-3 align-items-center justify-content-between mb-4">
        <div class="col-12 col-lg-auto">
            <div id="pp-page-title" class="h6 mb-1 fw-bold text-dark">
                <i class="fas fa-id-card text-primary me-2"></i>Patient Profile &amp; Clinical Chart
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small text-muted">
                    <li class="breadcrumb-item">Patients</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Chart &amp; Medical Record</li>
                </ol>
            </nav>
        </div>

        <!-- Patient Selector Dropdown -->
        <div class="col-12 col-lg-auto">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group shadow-sm rounded-3 w-auto flex-grow-1">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fas fa-hospital-user"></i>
                    </span>
                    <select class="form-select border-start-0 ps-1" id="pp-patient-dropdown" aria-label="Select patient to view chart">
                        <option value="">Select Patient Record…</option>
                        <?php foreach ((array)$pp_patients as $pp): 
                            $pp_name = trim(($pp['first_name'] ?? '') . ' ' . ($pp['last_name'] ?? ''));
                        ?>
                            <option value="<?php echo (int)$pp['user_id']; ?>">
                                <?php echo esc_html($pp_name ?: 'Patient #' . $pp['user_id']); ?> 
                                (<?php echo esc_html($pp['patient_uid'] ?: 'ID: ' . $pp['user_id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-primary px-3 fw-semibold shadow-sm" id="pp-load-patient-btn">
                        <i class="fas fa-folder-open me-1"></i> Open Chart
                    </button>
                </div>
                <button type="button" class="btn btn-outline-secondary rounded-3 shadow-sm px-3" id="pp-refresh-btn" title="Refresh Current Record">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-all" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-all');">
            <i class="fas fa-users"></i> All Patients
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-add" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-add');">
            <i class="fas fa-user-plus"></i> Add Patient
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-edit" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-edit');">
            <i class="fas fa-user-pen"></i> Edit Patient
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-patients-records" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-records');">
            <i class="fas fa-clipboard-user"></i> Patient Records
        </button>
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-patients-profile" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-patients-profile');">
            <i class="fas fa-id-card"></i> Patient Profile
        </button>
    </div>

    <!-- Empty State (When no patient is loaded) -->
    <div class="card border-0 shadow-sm rounded-4 text-center py-5 mb-4 bg-white" id="pp-empty-state">
        <div class="card-body py-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3 p-4 fs-1 lh-1">
                <i class="fas fa-user-doctor"></i>
            </div>
            <div class="h5 fw-bold text-dark mb-2">Select a Patient to Open Chart</div>
            <p class="text-secondary mx-auto mb-4 col-12 col-md-8 col-lg-6">
                Choose any patient from the dropdown above to view complete personal details, uploaded medical documents, previous prescriptions, medical history, and clinical questionnaire responses.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 fw-semibold" onclick="document.getElementById('pp-patient-dropdown').focus();">
                    <i class="fas fa-arrow-up me-1"></i> Choose from Patient List
                </button>
            </div>
        </div>
    </div>

    <!-- Active Patient Chart Workspace -->
    <div id="pp-chart" hidden>

        <!-- Patient Hero Summary Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
            <div class="p-3 p-md-4 bg-white border-bottom d-flex flex-column flex-xxl-row align-items-start align-items-xxl-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative flex-shrink-0">
                        <img data-pp="avatar" src="" alt="Patient photo" width="72" height="72" class="rounded-circle border border-2 border-white shadow-sm object-fit-cover" style="width: 72px; height: 72px;">
                        <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Active Record"></span>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <div class="fs-5 fw-bold text-dark mb-0 text-nowrap" data-pp="name" style="font-size: 1.25rem !important;">Patient Name</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 small fw-semibold" data-pp="status">Active</span>
                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small fw-bold font-monospace" data-pp="uid">UID: —</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-sm-3 text-secondary small">
                            <span><i class="fas fa-venus-mars text-primary me-1"></i><span data-pp="gender">—</span></span>
                            <span class="text-muted">&bull;</span>
                            <span><i class="fas fa-cake-candles text-primary me-1"></i><span data-pp="age">—</span></span>
                            <span class="text-muted">&bull;</span>
                            <span><i class="fas fa-droplet text-danger me-1"></i>Blood Group: <strong class="text-dark" data-pp="blood">—</strong></span>
                            <span class="text-muted">&bull;</span>
                            <span class="d-inline-flex align-items-center"><i class="fas fa-phone text-success me-1"></i><a data-pp="phone-link" href="#" class="text-decoration-none text-secondary fw-semibold small" style="font-size: 13px !important; line-height: 1.3 !important;"><span data-pp="phone">—</span></a></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="d-flex flex-wrap gap-2 w-100 w-xxl-auto justify-content-start justify-content-xxl-end">
                    <button type="button" class="btn btn-primary rounded-3 fw-semibold shadow-sm px-3 py-2 d-inline-flex align-items-center" onclick="ppSwitchTab('prescriptions')">
                        <i class="fas fa-prescription me-2"></i> New Prescription
                    </button>
                    <button type="button" class="btn btn-outline-primary rounded-3 fw-semibold px-3 py-2 d-inline-flex align-items-center" onclick="ppSwitchTab('consultation')">
                        <i class="fas fa-notes-medical me-2"></i> Clinical Note
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold px-3 py-2 d-inline-flex align-items-center" onclick="ppSwitchTab('documents')">
                        <i class="fas fa-cloud-arrow-up me-2"></i> Upload Document
                    </button>
                </div>
            </div>

            <!-- 4 Quick Stats Ribbon -->
            <div class="row g-0 text-center border-top bg-white">
                <div class="col-6 col-md-3 p-3 border-end">
                    <div class="text-secondary small fw-semibold text-uppercase">Appointments</div>
                    <div class="fs-4 fw-bold text-primary mt-1" data-pp="visit-count">0</div>
                </div>
                <div class="col-6 col-md-3 p-3 border-end">
                    <div class="text-secondary small fw-semibold text-uppercase">Prescriptions</div>
                    <div class="fs-4 fw-bold text-success mt-1" data-pp="prescription-count">0</div>
                </div>
                <div class="col-6 col-md-3 p-3 border-end">
                    <div class="text-secondary small fw-semibold text-uppercase">Documents</div>
                    <div class="fs-4 fw-bold text-info mt-1" data-pp="document-count">0</div>
                </div>
                <div class="col-6 col-md-3 p-3">
                    <div class="text-secondary small fw-semibold text-uppercase">Consultation Notes</div>
                    <div class="fs-4 fw-bold text-dark mt-1" data-pp="consultation-count">0</div>
                </div>
            </div>
        </div>

        <!-- Main Chart Card with Tab Navigation -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            
            <!-- Tab Navigation Header -->
            <div class="card-header bg-white border-bottom p-3">
                <ul class="nav nav-pills flex-nowrap overflow-auto gap-2" id="pp-nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="overview">
                            <i class="fas fa-user-circle me-2"></i> Personal Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="prescriptions">
                            <i class="fas fa-prescription-bottle-medical me-2 text-danger"></i> Prescriptions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="questionnaire">
                            <i class="fas fa-clipboard-question me-2 text-warning"></i> Medical Questionnaire
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="clinical">
                            <i class="fas fa-heart-pulse me-2 text-primary"></i> Medical History
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="documents">
                            <i class="fas fa-folder-open me-2 text-info"></i> Uploaded Documents
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="consultation">
                            <i class="fas fa-notes-medical me-2 text-success"></i> Clinical Notes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link rounded-3 fw-semibold px-3 py-2 text-nowrap" data-pp-tab="visits">
                            <i class="fas fa-calendar-days me-2 text-secondary"></i> Visit History
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Panes Container -->
            <div class="card-body p-3 p-md-4">

                <!-- ============================================================== -->
                <!-- TAB 1: PERSONAL INFORMATION & DEMOGRAPHICS -->
                <!-- ============================================================== -->
                <div class="tab-pane fade show active" data-pp-pane="overview">
                    <div class="row g-4">
                        <!-- Demographics List -->
                        <div class="col-12 col-xl-7">
                            <div class="card border rounded-4 p-3 p-md-4 h-100 bg-white">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                    <i class="fas fa-address-card text-primary me-2"></i>Patient Demographics
                                </h5>
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Full Legal Name</span>
                                            <strong class="text-dark fs-6" data-pp="name">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Patient UID</span>
                                            <strong class="text-primary fs-6 font-monospace" data-pp="uid">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Email Address</span>
                                            <strong class="text-dark" data-pp="email">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Mobile Phone</span>
                                            <strong class="text-dark" data-pp="phone">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Date of Birth &amp; Age</span>
                                            <strong class="text-dark"><span data-pp="dob">—</span> (<span data-pp="age">—</span>)</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Gender &amp; Blood Group</span>
                                            <strong class="text-dark"><span data-pp="gender">—</span> &bull; <span data-pp="blood">—</span></strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Marital Status</span>
                                            <strong class="text-dark" data-pp="marital">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">National ID / SSN</span>
                                            <strong class="text-dark" data-pp="national-id">—</strong>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3">
                                            <span class="small text-uppercase text-secondary fw-semibold d-block mb-1">Full Residential Address</span>
                                            <strong class="text-dark" data-pp="address">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Safety & Emergency Card -->
                        <div class="col-12 col-xl-5">
                            <div class="d-flex flex-column gap-3 h-100">
                                <!-- Emergency Contact -->
                                <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none">
                                    <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                        <i class="fas fa-user-shield text-danger me-2"></i>Emergency Contact
                                    </h5>
                                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light mb-3">
                                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0 p-3 fs-5 lh-1">
                                            <i class="fas fa-phone-volume"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <strong class="d-block text-dark fs-6" data-pp="emergency-name">—</strong>
                                            <span class="small text-secondary" data-pp="emergency-relation">Emergency Contact</span>
                                        </div>
                                        <a data-pp="emergency-phone" href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold">
                                            <i class="fas fa-phone me-1"></i> Call
                                        </a>
                                    </div>
                                </div>

                                <!-- Critical Allergies Notice -->
                                <div class="card border rounded-4 p-3 p-md-4 bg-warning-subtle border-warning-subtle shadow-none">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="text-warning fs-3 flex-shrink-0">
                                            <i class="fas fa-triangle-exclamation"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Allergy &amp; Safety Summary</h6>
                                            <p class="mb-0 text-dark small" data-pp="allergy-summary">
                                                No allergy information recorded.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preferred Pharmacy -->
                                <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none flex-grow-1">
                                    <h5 class="fw-bold text-dark mb-2 d-flex align-items-center">
                                        <i class="fas fa-prescription-bottle text-success me-2"></i>Preferred Pharmacy
                                    </h5>
                                    <div class="p-3 bg-light rounded-3">
                                        <strong class="text-dark d-block fs-6" data-pp-q="pharmacy_name">Preferred Pharmacy on file</strong>
                                        <span class="text-secondary small d-block mt-1" data-pp-q="pharmacy_location">Standard clinical fulfillment route</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 2: DEDICATED PRESCRIPTION MANAGEMENT TAB -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="prescriptions" hidden>
                    <div class="row g-4 align-items-start">
                        
                        <!-- Left Column: Create New Prescription Form -->
                        <div class="col-12 col-xl-5">
                            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-light border">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <div>
                                        <span class="text-primary small fw-bold text-uppercase">Direct Physician Order</span>
                                        <h5 class="fw-bold text-dark mb-0 mt-1">
                                            <i class="fas fa-prescription text-primary me-2"></i>Write Prescription
                                        </h5>
                                    </div>
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 small">E-Rx Ready</span>
                                </div>

                                <form id="pp-prescription-form">
                                    <input type="hidden" name="patient_id" id="pp-rx-patient-id" value="">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-rx-medication">
                                            Medication Name &amp; Formulation <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control rounded-3" id="pp-rx-medication" required placeholder="e.g. Amoxicillin 500mg Capsule, Albuterol Inhaler">
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-dosage">
                                                Dosage <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control rounded-3" id="pp-rx-dosage" required placeholder="e.g. 500 mg, 1 puff">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-quantity">
                                                Quantity / Dispense
                                            </label>
                                            <input type="text" class="form-control rounded-3" id="pp-rx-quantity" value="30 capsules" placeholder="e.g. 30 capsules, 1 bottle">
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-frequency">
                                                Frequency <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select rounded-3" id="pp-rx-frequency" required>
                                                <option value="Once daily">Once daily (QD)</option>
                                                <option value="Twice daily" selected>Twice daily (BID)</option>
                                                <option value="Three times daily">Three times daily (TID)</option>
                                                <option value="Four times daily">Four times daily (QID)</option>
                                                <option value="Every 8 hours">Every 8 hours</option>
                                                <option value="Every 12 hours">Every 12 hours</option>
                                                <option value="At bedtime">At bedtime (QHS)</option>
                                                <option value="As needed">As needed (PRN)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-duration">
                                                Duration <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select rounded-3" id="pp-rx-duration" required>
                                                <option value="3 days">3 days</option>
                                                <option value="5 days">5 days</option>
                                                <option value="7 days" selected>7 days</option>
                                                <option value="10 days">10 days</option>
                                                <option value="14 days">14 days</option>
                                                <option value="30 days">30 days</option>
                                                <option value="90 days">90 days</option>
                                                <option value="Ongoing">Ongoing / Maintenance</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-refills">
                                                Authorized Refills
                                            </label>
                                            <input type="number" class="form-control rounded-3" id="pp-rx-refills" min="0" max="12" value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-dark" for="pp-rx-status">
                                                Prescription Status
                                            </label>
                                            <select class="form-select rounded-3" id="pp-rx-status">
                                                <option value="Active" selected>Active</option>
                                                <option value="Completed">Completed</option>
                                                <option value="Cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-rx-instructions">
                                            Directions / Patient Instructions (SIG)
                                        </label>
                                        <textarea class="form-control rounded-3" id="pp-rx-instructions" rows="3" placeholder="Take 1 capsule by mouth every 12 hours with meals and plenty of water until finished."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm" id="pp-submit-rx-btn">
                                        <i class="fas fa-paper-plane me-2"></i> Issue &amp; Send Prescription
                                    </button>
                                    <div class="form-text text-center text-muted small mt-2">
                                        Prescription is routed to patient chart and designated fulfillment pharmacy.
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Right Column: Previous Prescriptions & Records List -->
                        <div class="col-12 col-xl-7">
                            <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                                    <div>
                                        <span class="text-secondary small fw-bold text-uppercase">Patient Medication Record</span>
                                        <h5 class="fw-bold text-dark mb-0 mt-1">
                                            <i class="fas fa-history text-primary me-2"></i>Previous Prescriptions
                                        </h5>
                                    </div>
                                    <!-- Search filter for prescriptions -->
                                    <div class="input-group input-group-sm w-auto">
                                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control border-start-0" id="pp-rx-search" placeholder="Filter medicines..." onkeyup="ppFilterRxTable()">
                                    </div>
                                </div>

                                <div class="table-responsive mb-0">
                                    <table class="table table-hover align-middle mb-0" id="pp-rx-table">
                                        <thead class="bg-light text-secondary small text-uppercase">
                                            <tr>
                                                <th>Medication &amp; Dosage</th>
                                                <th>Frequency / Duration</th>
                                                <th>Directions (SIG)</th>
                                                <th>Status</th>
                                                <th>Date Prescribed</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody data-pp-rows="prescriptions">
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-5">
                                                    <i class="fas fa-pills fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                                    Select a patient to load prescription history.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 3: MEDICAL QUESTIONNAIRE & INTAKE (HIPAAtizer) -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="questionnaire" hidden>
                    
                    <!-- Questionnaire Empty State -->
                    <div class="text-center py-5" id="pp-questionnaire-empty">
                        <div class="text-secondary fs-1 mb-3"><i class="fas fa-clipboard-question"></i></div>
                        <h4 class="h5 fw-bold text-dark">No Medical Questionnaire Submitted</h4>
                        <p class="text-secondary small mb-0">When this patient submits their HIPAAtizer clinical questionnaire, complete answers and modules will display here.</p>
                    </div>

                    <!-- Questionnaire Content (Rendered dynamically) -->
                    <div id="pp-questionnaire-content" hidden>
                        
                        <!-- Metadata Header & PDF Actions -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom gap-2 bg-light p-3 rounded-4">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold small mb-1">
                                    <i class="fas fa-check-circle me-1"></i> Verified Clinical Intake
                                </span>
                                <div class="text-secondary small mt-1">
                                    Submitted on: <strong class="text-dark" data-pp-q="submitted_at">—</strong>
                                    &bull; Submission ID: <code class="text-dark fw-bold" data-pp-q="submission_id">—</code>
                                </div>
                            </div>
                            <div class="d-flex gap-2" id="pp-q-pdf-container">
                                <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-3" data-pp-q-link="pdf">
                                    <i class="fas fa-file-pdf me-1"></i> View Signed PDF
                                </a>
                                <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3" data-pp-q-link="submission">
                                    <i class="fas fa-external-link-alt me-1"></i> Original Form
                                </a>
                            </div>
                        </div>

                        <!-- Row 1: Visit Reason & Preferences -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Selected Condition / Service</div>
                                    <div class="fw-bold text-dark fs-6" data-pp-q="selected_service">—</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Preferred Consultation Type</div>
                                    <div class="fw-bold text-dark fs-6" data-pp-q="consultation_type">—</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Referral Source</div>
                                    <div class="fw-bold text-dark fs-6" data-pp-q="referral_source">—</div>
                                </div>
                            </div>
                        </div>

                        <!-- Symptoms & Complaint Card -->
                        <div class="card border rounded-4 p-3 p-md-4 mb-4 bg-white">
                            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                <i class="fas fa-stethoscope text-primary me-2"></i>Symptoms &amp; Clinical Reason for Visit
                            </h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Main Reason for Visit</div>
                                    <div class="fw-bold text-dark fs-6" data-pp-q="reason_for_visit">—</div>
                                </div>
                                <div class="col-12">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Detailed Symptoms Description</div>
                                    <div class="text-dark bg-light p-3 rounded-3 border" data-pp-q="symptom_description">—</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Duration</div>
                                    <div class="fw-bold text-dark" data-pp-q="symptom_duration">—</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Reported Severity</div>
                                    <div><span data-pp-q-badge="symptom_severity">—</span></div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Fever Reported</div>
                                    <div class="fw-bold text-dark" data-pp-q="has_fever">—</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Treatment Tried Previously</div>
                                    <div class="fw-bold text-dark" data-pp-q="tried_treatment">—</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="small text-uppercase text-secondary fw-semibold mb-1">Prior Treatment Details</div>
                                    <div class="text-dark" data-pp-q="treatment_tried_details">—</div>
                                </div>
                                <!-- Red Flags Alert Banner -->
                                <div class="col-12" id="pp-q-redflag-box" hidden>
                                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 rounded-3">
                                        <i class="fas fa-triangle-exclamation fs-4 text-danger"></i>
                                        <div>
                                            <strong>Red Flag Symptoms Reported:</strong>
                                            <span data-pp-q="red_flags">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specialized Clinical Modules (UTI, Allergy, Asthma, Refill) -->
                        <h5 class="fw-bold text-dark mb-3" id="pp-q-modules-header">
                            <i class="fas fa-layer-group text-primary me-2"></i>Condition-Specific Assessments
                        </h5>
                        <div class="row g-3 mb-4" id="pp-q-modules-row">
                            <!-- UTI Assessment -->
                            <div class="col-12 col-md-6" data-pp-module="uti">
                                <div class="card border rounded-3 h-100 p-3">
                                    <h6 class="fw-bold text-danger text-uppercase small border-bottom pb-2 mb-2">
                                        <i class="fas fa-bacteria me-1"></i> UTI Assessment
                                    </h6>
                                    <div class="row g-1 small">
                                        <div class="col-6 text-secondary">Burning / Dysuria:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="uti_burning">—</div>
                                        <div class="col-6 text-secondary">Urgency / Frequency:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="uti_frequency">—</div>
                                        <div class="col-6 text-secondary">Blood in Urine:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="uti_blood">—</div>
                                        <div class="col-6 text-secondary">Flank / Back Pain:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="uti_flank_pain">—</div>
                                        <div class="col-6 text-secondary">Prior UTI History:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="uti_history">—</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Allergy Assessment -->
                            <div class="col-12 col-md-6" data-pp-module="allergy">
                                <div class="card border rounded-3 h-100 p-3">
                                    <h6 class="fw-bold text-warning text-uppercase small border-bottom pb-2 mb-2">
                                        <i class="fas fa-shield-virus me-1"></i> Allergy Assessment
                                    </h6>
                                    <div class="row g-1 small">
                                        <div class="col-6 text-secondary">Main Symptoms:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="allergy_main_symptom">—</div>
                                        <div class="col-6 text-secondary">Known Triggers:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="allergy_triggers">—</div>
                                        <div class="col-6 text-secondary">Anaphylaxis History:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="allergy_anaphylaxis">—</div>
                                        <div class="col-6 text-secondary">Carries EpiPen:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="allergy_epipen">—</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Asthma Assessment -->
                            <div class="col-12 col-md-6" data-pp-module="asthma">
                                <div class="card border rounded-3 h-100 p-3">
                                    <h6 class="fw-bold text-info text-uppercase small border-bottom pb-2 mb-2">
                                        <i class="fas fa-lungs me-1"></i> Asthma &amp; Respiratory
                                    </h6>
                                    <div class="row g-1 small">
                                        <div class="col-6 text-secondary">Rescue Inhaler Use:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="asthma_rescue_use">—</div>
                                        <div class="col-6 text-secondary">Nighttime Waking:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="asthma_night">—</div>
                                        <div class="col-6 text-secondary">ER Visit (12 mos):</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="asthma_er_visit">—</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Refill Request -->
                            <div class="col-12 col-md-6" data-pp-module="refill">
                                <div class="card border rounded-3 h-100 p-3">
                                    <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-2">
                                        <i class="fas fa-prescription-bottle-medical me-1"></i> Refill Request
                                    </h6>
                                    <div class="row g-1 small">
                                        <div class="col-6 text-secondary">Requested Med:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="refill_med_name">—</div>
                                        <div class="col-6 text-secondary">Dose &amp; Frequency:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="refill_med_dose">—</div>
                                        <div class="col-6 text-secondary">Last Refill Date:</div>
                                        <div class="col-6 fw-semibold text-dark text-end" data-pp-q="refill_last_date">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Past Medical History Cards -->
                        <div class="card border rounded-4 p-3 p-md-4 mb-4 bg-white">
                            <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                <i class="fas fa-heart-pulse text-danger me-2"></i>Medical History &amp; Chronic Conditions
                            </h5>
                            <div class="row g-2 text-center mb-3">
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Hypertension</small>
                                        <strong class="text-dark small" data-pp-q="hx_hypertension">—</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Diabetes</small>
                                        <strong class="text-dark small" data-pp-q="hx_diabetes">—</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Heart Disease</small>
                                        <strong class="text-dark small" data-pp-q="hx_heart">—</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Lung / Asthma</small>
                                        <strong class="text-dark small" data-pp-q="hx_lung">—</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Kidney / Liver</small>
                                        <strong class="text-dark small" data-pp-q="hx_kidney_liver">—</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <small class="text-secondary d-block">Cancer / Immune</small>
                                        <strong class="text-dark small" data-pp-q="hx_cancer_immune">—</strong>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <small class="text-uppercase text-secondary fw-semibold d-block mb-1">Surgeries &amp; Other Conditions</small>
                                <div class="p-3 bg-light rounded-3 text-dark border" data-pp-q="hx_other">—</div>
                            </div>
                        </div>

                        <!-- Current Medications & Drug Allergies -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-4 p-3 h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2">
                                        <i class="fas fa-pills text-primary me-2"></i>Patient-Reported Medications
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 text-dark" data-pp-q="medication_list">—</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-4 p-3 h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2">
                                        <i class="fas fa-triangle-exclamation text-danger me-2"></i>Patient-Reported Drug Allergies
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 text-dark" data-pp-q="drug_allergy_list">—</div>
                                </div>
                            </div>
                        </div>

                        <!-- Legal Signature & Consent -->
                        <div class="card border rounded-4 p-3 bg-light">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold small mb-1">
                                        <i class="fas fa-file-signature me-1"></i> E-Signature Verified
                                    </span>
                                    <div class="text-dark small mt-1">
                                        Signer: <strong data-pp-q="e_signature">—</strong> &bull; Signed Date: <span data-pp-q="signature_date">—</span>
                                    </div>
                                </div>
                                <span class="text-secondary small font-monospace">Legal Consent Stored</span>
                            </div>
                        </div>

                        <!-- Complete Dynamic Questions & Answers from Intake Form -->
                        <div class="card border rounded-4 p-3 p-md-4 mt-4 bg-white shadow-sm" id="pp-all-questions-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">
                                        <i class="fas fa-clipboard-list text-primary me-2"></i>All HIPAAtizer Form Questions &amp; Patient Responses
                                    </h5>
                                    <span class="text-secondary small">Comprehensive responses submitted by the patient during their clinical questionnaire intake.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="max-width: 260px;">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-secondary"></i></span>
                                        <input type="text" id="pp-qa-search" class="form-control border-start-0" placeholder="Filter questions..." oninput="ppFilterQuestions()">
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold small" id="pp-qa-count">0 Questions</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 dior-static-skip dior-no-table-interactions" id="pp-qa-table">
                                    <thead class="table-light small text-uppercase text-secondary">
                                        <tr>
                                            <th style="width: 50px;" class="text-center">#</th>
                                            <th style="width: 45%;">Question Prompt</th>
                                            <th style="width: 50%;">Patient's Answer</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pp-qa-tbody">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fas fa-clipboard-question d-block mb-1 fs-4 opacity-50"></i>
                                                Loading intake responses...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 4: MEDICAL HISTORY & CLINICAL CHARTS -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="clinical" hidden>
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3 p-md-4 h-100">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                    <i class="fas fa-triangle-exclamation text-danger me-2"></i>Documented Allergies
                                </h5>
                                <div id="pp-allergies-chips" class="d-flex flex-wrap gap-2" data-pp-list="allergies">
                                    <span class="text-muted small">No allergy information recorded.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3 p-md-4 h-100">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                    <i class="fas fa-heart-pulse text-warning me-2"></i>Chronic Health Conditions
                                </h5>
                                <div id="pp-conditions-chips" class="d-flex flex-wrap gap-2" data-pp-list="conditions">
                                    <span class="text-muted small">No conditions recorded.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3 p-md-4 h-100">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                    <i class="fas fa-pills text-success me-2"></i>Reported Current Medications
                                </h5>
                                <div id="pp-meds-chips" class="d-flex flex-wrap gap-2" data-pp-list="medications">
                                    <span class="text-muted small">No medication information recorded.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3 p-md-4 h-100">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom d-flex align-items-center">
                                    <i class="fas fa-notes-medical text-primary me-2"></i>Medical &amp; Surgical History
                                </h5>
                                <div class="p-3 bg-light rounded-3 text-dark small" data-pp="medical-history">
                                    No medical history recorded.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 5: UPLOADED DOCUMENTS -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="documents" hidden>
                    <div class="row g-4">
                        <!-- Upload New Document Form -->
                        <div class="col-12 col-xl-4">
                            <div class="card border rounded-4 p-3 p-md-4 bg-light">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                    <i class="fas fa-cloud-arrow-up text-primary me-2"></i>Upload Patient Document
                                </h5>
                                <form id="pp-document-form" enctype="multipart/form-data">
                                    <input type="hidden" name="patient_id" id="pp-doc-patient-id" value="">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-doc-title">Document Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control rounded-3" id="pp-doc-title" required placeholder="e.g. CBC Blood Panel Results">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-doc-category">Document Type</label>
                                        <select class="form-select rounded-3" id="pp-doc-category">
                                            <option value="Lab Report">Lab Report</option>
                                            <option value="Imaging">Imaging / X-Ray</option>
                                            <option value="Clinical Record" selected>Clinical Record</option>
                                            <option value="Insurance">Insurance</option>
                                            <option value="Consent">Consent Agreement</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-doc-file">Select File <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control rounded-3" id="pp-doc-file" required accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">
                                        <div class="form-text small">Accepted: PDF, PNG, JPG, DOC, DOCX up to 25MB.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm" id="pp-doc-upload-btn">
                                        <i class="fas fa-upload me-1"></i> Upload File Securely
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Documents List Table -->
                        <div class="col-12 col-xl-8">
                            <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                    <i class="fas fa-folder-open text-primary me-2"></i>Uploaded Documents &amp; Records
                                </h5>
                                <div class="table-responsive mb-0">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light text-secondary small text-uppercase">
                                            <tr>
                                                <th>Document Title</th>
                                                <th>Category</th>
                                                <th>Date Uploaded</th>
                                                <th>Author</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody data-pp-rows="documents">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No documents recorded.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 6: CLINICAL CONSULTATION NOTES -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="consultation" hidden>
                    <div class="row g-4">
                        <!-- Add Note Form -->
                        <div class="col-12 col-xl-5">
                            <div class="card border rounded-4 p-3 p-md-4 bg-light">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                    <i class="fas fa-notes-medical text-primary me-2"></i>New Clinical Encounter Note
                                </h5>
                                <form id="pp-consultation-form">
                                    <input type="hidden" name="patient_id" id="pp-note-patient-id" value="">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-chief-complaint">Subjective / Chief Complaint <span class="text-danger">*</span></label>
                                        <textarea class="form-control rounded-3" id="pp-chief-complaint" rows="3" required placeholder="Patient presents with..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-diagnosis">Diagnosis (Working or Confirmed)</label>
                                        <input type="text" class="form-control rounded-3" id="pp-diagnosis" placeholder="e.g. Acute uncomplicated cystitis">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-assessment">Objective &amp; Clinical Assessment</label>
                                        <textarea class="form-control rounded-3" id="pp-assessment" rows="3" placeholder="Clinical evaluation findings..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-plan">Treatment &amp; Follow-up Plan</label>
                                        <textarea class="form-control rounded-3" id="pp-plan" rows="3" placeholder="Prescribed regimen and patient education..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark" for="pp-follow-up">Follow-up Recommendation</label>
                                        <input type="text" class="form-control rounded-3" id="pp-follow-up" placeholder="e.g. Return in 7 days if symptoms persist">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm" id="pp-save-note-btn">
                                        <i class="fas fa-floppy-disk me-1"></i> Save Consultation Note
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Previous Notes List -->
                        <div class="col-12 col-xl-7">
                            <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none">
                                <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                                    <i class="fas fa-file-waveform text-primary me-2"></i>Consultation History
                                </h5>
                                <div id="pp-consultation-list" data-pp-rows="consultations" class="d-flex flex-column gap-3">
                                    <div class="text-center text-muted py-4">No consultation notes recorded.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TAB 7: VISIT & APPOINTMENT HISTORY -->
                <!-- ============================================================== -->
                <div class="tab-pane fade" data-pp-pane="visits" hidden>
                    <div class="card border rounded-4 p-3 p-md-4 bg-white shadow-none">
                        <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                            <i class="fas fa-calendar-check text-primary me-2"></i>Complete Appointment History
                        </h5>
                        <div class="table-responsive mb-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary small text-uppercase">
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>Provider</th>
                                        <th>Visit Reason</th>
                                        <th>Format</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody data-pp-rows="visits">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No appointments recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</section>

<!-- Document Edit Metadata Modal -->
<div class="modal fade" id="pp-edit-document-modal" tabindex="-1" aria-labelledby="pp-edit-document-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form id="pp-edit-document-form">
                <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                    <h5 class="modal-title fw-bold text-dark fs-5 mb-0" id="pp-edit-document-title">
                        <i class="fas fa-file-pen text-primary me-2"></i>Update Document Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <input type="hidden" id="pp-edit-doc-uid">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark" for="pp-edit-doc-name">Document Title</label>
                        <input type="text" class="form-control rounded-3" id="pp-edit-doc-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark" for="pp-edit-doc-category">Document Type</label>
                        <select class="form-select rounded-3" id="pp-edit-doc-category">
                            <option value="Lab Report">Lab Report</option>
                            <option value="Imaging">Imaging / X-Ray</option>
                            <option value="Clinical Record">Clinical Record</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Consent">Consent Agreement</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-white">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Prescription Details Modal -->
<div class="modal fade" id="pp-view-rx-modal" tabindex="-1" aria-labelledby="pp-view-rx-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold text-dark fs-5 mb-0" id="pp-view-rx-title">
                    <i class="fas fa-prescription text-primary me-2"></i>Prescription Directive Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="pp-view-rx-body">
                <!-- Dynamically populated via JS -->
            </div>
            <div class="modal-footer border-top py-3 px-4 bg-white d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-3 px-3 fw-semibold" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Prescription
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var ajaxUrl = <?php echo wp_json_encode($pp_ajax_url); ?>;
    var nonce = <?php echo wp_json_encode($pp_nonce); ?>;
    var selectedPatient = null;
    var currentRxList = [];
    var chart = document.getElementById('pp-chart');
    var emptyState = document.getElementById('pp-empty-state');
    var picker = document.getElementById('pp-patient-dropdown');

    function notify(icon, title, text) {
        if (window.Swal) {
            return Swal.fire({ icon: icon, title: title, text: text, timer: 3000, showConfirmButton: false });
        }
        window.alert(title + ': ' + text);
    }

    function field(key, value) {
        document.querySelectorAll('[data-pp="' + key + '"]').forEach(function(el) {
            el.textContent = value || '—';
        });
    }

    function request(action, payload, fileForm) {
        var data = fileForm || new FormData();
        data.append('action', action);
        data.append('nonce', nonce);
        Object.keys(payload || {}).forEach(function(key) {
            data.append(key, payload[key] == null ? '' : payload[key]);
        });
        return fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) {
                    throw new Error(res.data && res.data.message ? res.data.message : 'Request could not be completed.');
                }
                return res.data;
            });
    }

    // Tab switching function with auto-scroll
    window.ppSwitchTab = function(tabKey) {
        var tabBtn = document.querySelector('[data-pp-tab="' + tabKey + '"]');
        if (tabBtn) {
            tabBtn.click();
            var navTabs = document.getElementById('pp-nav-tabs');
            if (navTabs) {
                navTabs.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    };

    document.querySelectorAll('[data-pp-tab]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var key = this.getAttribute('data-pp-tab');
            document.querySelectorAll('[data-pp-tab]').forEach(function(b) {
                b.classList.toggle('active', b === btn);
            });
            document.querySelectorAll('[data-pp-pane]').forEach(function(pane) {
                var isTarget = pane.getAttribute('data-pp-pane') === key;
                pane.hidden = !isTarget;
                pane.classList.toggle('active', isTarget);
                pane.classList.toggle('show', isTarget);
            });
        });
    });

    // Render Prescription Table
    function renderPrescriptions(items) {
        currentRxList = items || [];
        var body = document.querySelector('[data-pp-rows="prescriptions"]');
        if (!body) return;
        body.innerHTML = '';

        if (!items || !items.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-pills d-block mb-1 fs-4 opacity-50"></i>No prescriptions recorded for this patient.</td></tr>';
            return;
        }

        items.forEach(function(item, idx) {
            var tr = document.createElement('tr');
            var medName = item.medication || item.medications || item.name || 'Prescription';
            var dose = item.dosage || '—';
            var freq = item.frequency || '—';
            var dur = item.duration || '—';
            var sig = item.instructions || item.notes || 'As directed';
            var status = (item.status || 'Active').toLowerCase();
            var date = item.prescription_date || item.created_at || item.date || '—';
            if (date.indexOf('T') !== -1) date = date.split('T')[0];
            if (date.indexOf(' ') !== -1) date = date.split(' ')[0];

            var badgeClass = 'bg-success-subtle text-success border border-success-subtle';
            if (status === 'completed') badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            if (status === 'cancelled') badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';

            tr.innerHTML = 
                '<td>' +
                    '<strong class="text-dark d-block">' + escapeHtml(medName) + '</strong>' +
                    '<span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">' + escapeHtml(dose) + '</span>' +
                '</td>' +
                '<td>' +
                    '<span class="d-block text-dark small fw-semibold">' + escapeHtml(freq) + '</span>' +
                    '<span class="text-muted small">' + escapeHtml(dur) + '</span>' +
                '</td>' +
                '<td><span class="text-secondary small d-inline-block text-truncate mw-100" title="' + escapeHtml(sig) + '">' + escapeHtml(sig) + '</span></td>' +
                '<td><span class="badge rounded-pill px-3 py-1 small fw-semibold ' + badgeClass + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>' +
                '<td><span class="small text-secondary">' + escapeHtml(date) + '</span></td>' +
                '<td class="text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary rounded-3 pp-view-rx-btn" data-index="' + idx + '" title="View Prescription Details">' +
                        '<i class="fas fa-eye me-1"></i> Details' +
                    '</button>' +
                '</td>';
            body.appendChild(tr);
        });

        // Bind View Rx Details
        body.querySelectorAll('.pp-view-rx-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var rx = currentRxList[parseInt(this.getAttribute('data-index'), 10)];
                if (!rx) return;
                showRxDetailsModal(rx);
            });
        });
    }

    function showRxDetailsModal(rx) {
        var modalEl = document.getElementById('pp-view-rx-modal');
        var body = document.getElementById('pp-view-rx-body');
        if (!modalEl || !body) return;

        var med = rx.medication || rx.medications || rx.name || 'Prescription';
        var dose = rx.dosage || '—';
        var freq = rx.frequency || '—';
        var dur = rx.duration || '—';
        var qty = rx.quantity || 'As prescribed';
        var sig = rx.instructions || rx.notes || 'Take as directed.';
        var refills = rx.refills || 0;
        var date = rx.prescription_date || rx.created_at || '—';
        var doc = rx.doctor_name || 'Attending Physician';
        var uid = rx.prescription_uid || rx.prescription_id || rx.id || 'RX-DIRECT';

        body.innerHTML = 
            '<div class="p-3 bg-light rounded-3 mb-3 border">' +
                '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<span class="badge bg-primary text-white rounded-pill px-3 py-1 font-monospace">' + escapeHtml(uid) + '</span>' +
                    '<span class="small text-muted">' + escapeHtml(date) + '</span>' +
                '</div>' +
                '<h4 class="fw-bold text-dark mb-1">' + escapeHtml(med) + '</h4>' +
                '<div class="text-secondary small">Prescribed by: <strong class="text-dark">' + escapeHtml(doc) + '</strong></div>' +
            '</div>' +
            '<div class="row g-2 mb-3 small">' +
                '<div class="col-6"><span class="text-secondary">Dosage:</span> <strong class="text-dark">' + escapeHtml(dose) + '</strong></div>' +
                '<div class="col-6"><span class="text-secondary">Quantity:</span> <strong class="text-dark">' + escapeHtml(qty) + '</strong></div>' +
                '<div class="col-6"><span class="text-secondary">Frequency:</span> <strong class="text-dark">' + escapeHtml(freq) + '</strong></div>' +
                '<div class="col-6"><span class="text-secondary">Duration:</span> <strong class="text-dark">' + escapeHtml(dur) + '</strong></div>' +
                '<div class="col-6"><span class="text-secondary">Refills:</span> <strong class="text-dark">' + escapeHtml(refills) + '</strong></div>' +
                '<div class="col-6"><span class="text-secondary">Status:</span> <strong class="text-success">' + escapeHtml(rx.status || 'Active') + '</strong></div>' +
            '</div>' +
            '<div class="p-3 bg-white rounded-3 border">' +
                '<span class="text-secondary small fw-bold text-uppercase d-block mb-1">Directions (SIG):</span>' +
                '<p class="mb-0 text-dark">' + escapeHtml(sig) + '</p>' +
            '</div>';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // Filter prescriptions table
    window.ppFilterRxTable = function() {
        var query = (document.getElementById('pp-rx-search').value || '').toLowerCase();
        var rows = document.querySelectorAll('#pp-rx-table tbody tr');
        rows.forEach(function(row) {
            row.classList.toggle('d-none', row.textContent.toLowerCase().indexOf(query) === -1);
        });
    };

    // Render Clinical Notes
    function renderNotes(items) {
        var list = document.querySelector('[data-pp-rows="consultations"]');
        if (!list) return;
        list.innerHTML = '';

        if (!items || !items.length) {
            list.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-file-medical d-block mb-1 fs-4 opacity-50"></i>No clinical consultation notes recorded.</div>';
            return;
        }

        items.forEach(function(note) {
            var card = document.createElement('div');
            card.className = 'p-3 bg-light rounded-4 border';
            var diag = note.diagnosis || 'Clinical Consultation';
            var date = note.created_at || '';
            var subj = note.subjective || note.assessment || 'No consultation text.';
            var plan = note.plan ? '<div class="mt-2 pt-2 border-top small text-secondary"><strong>Plan:</strong> ' + escapeHtml(note.plan) + '</div>' : '';
            var follow = note.follow_up ? '<div class="mt-1 small text-primary"><i class="fas fa-clock me-1"></i>Follow-up: ' + escapeHtml(note.follow_up) + '</div>' : '';

            card.innerHTML = 
                '<div class="d-flex justify-content-between align-items-center mb-1">' +
                    '<h6 class="fw-bold text-dark mb-0">' + escapeHtml(diag) + '</h6>' +
                    '<span class="small text-muted font-monospace">' + escapeHtml(date) + '</span>' +
                '</div>' +
                '<p class="mb-0 text-secondary small mt-1">' + escapeHtml(subj) + '</p>' +
                plan + follow;

            list.appendChild(card);
        });
    }

    // Render Documents Table
    function renderDocuments(items) {
        var body = document.querySelector('[data-pp-rows="documents"]');
        if (!body) return;
        body.innerHTML = '';

        if (!items || !items.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-folder-open d-block mb-1 fs-4 opacity-50"></i>No documents uploaded for this patient.</td></tr>';
            return;
        }

        items.forEach(function(doc) {
            var tr = document.createElement('tr');
            tr.innerHTML = 
                '<td><strong class="text-dark"><i class="fas fa-file-lines text-primary me-2"></i>' + escapeHtml(doc.title || 'Document') + '</strong></td>' +
                '<td><span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small">' + escapeHtml(doc.category || 'Clinical Record') + '</span></td>' +
                '<td><span class="small text-secondary">' + escapeHtml(doc.date || '—') + '</span></td>' +
                '<td><span class="small text-muted">' + escapeHtml(doc.author || 'Clinical Staff') + '</span></td>' +
                '<td class="text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary rounded-3 pp-edit-doc-btn" data-uid="' + escapeHtml(doc.id || doc.doc_uid || '') + '" data-title="' + escapeHtml(doc.title || '') + '" data-category="' + escapeHtml(doc.category || 'Clinical Record') + '">' +
                        '<i class="fas fa-pen me-1"></i> Edit' +
                    '</button>' +
                '</td>';
            body.appendChild(tr);
        });

        body.querySelectorAll('.pp-edit-doc-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('pp-edit-doc-uid').value = this.dataset.uid;
                document.getElementById('pp-edit-doc-name').value = this.dataset.title;
                document.getElementById('pp-edit-doc-category').value = this.dataset.category;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('pp-edit-document-modal')).show();
            });
        });
    }

    // Render Visit History
    function renderVisits(items) {
        var body = document.querySelector('[data-pp-rows="visits"]');
        if (!body) return;
        body.innerHTML = '';

        if (!items || !items.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-calendar-xmark d-block mb-1 fs-4 opacity-50"></i>No appointment visits recorded.</td></tr>';
            return;
        }

        items.forEach(function(v) {
            var tr = document.createElement('tr');
            var status = (v.status || 'Scheduled').toLowerCase();
            var bCls = 'bg-primary-subtle text-primary border border-primary-subtle';
            if (status === 'completed') bCls = 'bg-success-subtle text-success border border-success-subtle';
            if (status === 'cancelled' || status === 'canceled') bCls = 'bg-danger-subtle text-danger border border-danger-subtle';

            tr.innerHTML = 
                '<td><strong class="text-dark">' + escapeHtml(v.appt_date || '—') + '</strong></td>' +
                '<td><span class="small text-secondary">' + escapeHtml(v.doctor_name || 'Dr. Marcus Sterling') + '</span></td>' +
                '<td><span class="small text-dark fw-semibold">' + escapeHtml(v.condition_name || 'Telehealth Visit') + '</span></td>' +
                '<td><span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">' + escapeHtml(v.visit_type || 'Video HD') + '</span></td>' +
                '<td><span class="badge rounded-pill px-3 py-1 small fw-semibold ' + bCls + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>';
            body.appendChild(tr);
        });
    }

    // Render Chip Badges
    function renderChips(key, raw) {
        var node = document.querySelector('[data-pp-list="' + key + '"]');
        if (!node) return;
        node.innerHTML = '';
        var items = Array.isArray(raw) ? raw : String(raw || '').split(/[\n,;]+/).map(function(x){ return x.trim(); }).filter(Boolean);
        if (!items.length) {
            node.innerHTML = '<span class="text-muted small">No ' + (key === 'allergies' ? 'allergies' : key) + ' recorded.</span>';
            return;
        }
        var color = key === 'allergies' ? 'bg-danger-subtle text-danger border-danger-subtle' : (key === 'conditions' ? 'bg-warning-subtle text-warning border-warning-subtle' : 'bg-primary-subtle text-primary border-primary-subtle');
        items.forEach(function(text) {
            var badge = document.createElement('span');
            badge.className = 'badge rounded-pill px-3 py-1 small border ' + color;
            badge.textContent = text;
            node.appendChild(badge);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    // Main Patient Render
    function renderPatient(p) {
        selectedPatient = p;
        emptyState.hidden = true;
        chart.hidden = false;

        var name = p.full_name || [p.first_name, p.last_name].filter(Boolean).join(' ') || 'Patient';
        field('name', name);
        field('uid', p.patient_uid || p.patient_id || ('DM-' + p.user_id));

        var avatar = document.querySelector('[data-pp="avatar"]');
        if (avatar) {
            avatar.src = p.avatar_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=00346c&color=ffffff&bold=true');
            avatar.alt = name;
        }

        var status = p.status || 'active';
        var statusBadge = document.querySelector('[data-pp="status"]');
        if (statusBadge) {
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusBadge.className = 'badge rounded-pill px-3 py-1 small fw-semibold ' + (status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle');
        }

        var birth = p.dob ? new Date(p.dob + 'T00:00:00') : null;
        var age = '—';
        if (birth && !isNaN(birth)) {
            var now = new Date();
            var years = now.getFullYear() - birth.getFullYear();
            if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) years--;
            age = years + ' yrs';
        }

        field('age', age);
        field('dob', p.dob || '—');
        field('gender', p.gender ? p.gender.charAt(0).toUpperCase() + p.gender.slice(1) : '—');
        field('blood', p.blood_group || '—');
        field('email', p.email || '—');
        field('phone', p.phone || '—');
        field('marital', p.marital_status ? p.marital_status.charAt(0).toUpperCase() + p.marital_status.slice(1) : '—');
        field('national-id', p.national_id || '—');
        field('address', [p.address, p.city, p.state, p.zip, p.country].filter(Boolean).join(', ') || '—');

        var phoneLink = document.querySelector('[data-pp="phone-link"]');
        if (phoneLink) {
            phoneLink.href = p.phone ? 'tel:' + String(p.phone).replace(/[^\d+]/g, '') : '#';
        }

        field('emergency-name', p.emergency_name || 'None on file');
        field('emergency-relation', p.emergency_relation || 'Emergency Contact');
        var emergencyPhone = document.querySelector('[data-pp="emergency-phone"]');
        if (emergencyPhone) {
            emergencyPhone.href = p.emergency_phone ? 'tel:' + String(p.emergency_phone).replace(/[^\d+]/g, '') : '#';
        }

        var allergies = p.allergies || '';
        field('allergy-summary', allergies || 'No critical allergies recorded.');
        field('medical-history', p.medical_history || 'No prior medical history notes recorded.');

        renderChips('allergies', p.allergies);
        renderChips('conditions', p.conditions);
        renderChips('medications', p.medications);

        renderPrescriptions(p.prescriptions || []);
        renderNotes(p.consultations || []);
        renderDocuments(p.documents || []);
        renderVisits(p.visits || []);
        renderQuestionnaire(p.questionnaire || {});

        // Sync hidden patient IDs in forms
        document.querySelectorAll('#pp-rx-patient-id, #pp-doc-patient-id, #pp-note-patient-id').forEach(function(inp) {
            inp.value = p.user_id;
        });

        // Set KPIs
        field('visit-count', String((p.visits || []).length));
        field('prescription-count', String((p.prescriptions || []).length));
        field('document-count', String((p.documents || []).length));
        field('consultation-count', String((p.consultations || []).length));
    }

    // Format boolean / radio values cleanly
    function formatBool(val, type) {
        if (val === undefined || val === null || val === '' || val === '—') return '<span class="text-muted">—</span>';
        var s = String(val).trim().toLowerCase();
        if (s === '1' || s === 'yes' || s === 'true') {
            if (type === 'danger') {
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-semibold"><i class="fas fa-circle-exclamation me-1"></i>Yes</span>';
            }
            if (type === 'warning') {
                return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fw-bold">Yes</span>';
            }
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-semibold">Yes</span>';
        }
        if (s === '2' || s === 'no' || s === 'false' || s === '0' || s === 'none') {
            return '<span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">No</span>';
        }
        return escapeHtml(val);
    }

    // Format Q&A Answers
    function formatQuestionAnswer(val, qText) {
        if (val === undefined || val === null || String(val).trim() === '' || val === '—') {
            return '<span class="text-muted">—</span>';
        }
        var s = String(val).trim();
        var sLow = s.toLowerCase();

        // For list questions, treat 2/none/no as "None reported"
        if (/list|surgeries|treatment details|triggers/i.test(qText || '') && (s === '2' || sLow === 'none' || sLow === 'no' || sLow === 'n/a')) {
            return '<span class="text-secondary small fst-italic"><i class="fas fa-minus-circle me-1 opacity-50"></i>None reported</span>';
        }

        if (s === '1' || sLow === 'yes' || sLow === 'true') {
            var isAlert = /fever|red_flag|trouble|chest|confusion|bleed|emergency|anaphylaxis|shortness|er visit/i.test(qText || '');
            var badgeCls = isAlert ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle';
            return '<span class="badge rounded-pill px-3 py-1 fw-semibold ' + badgeCls + '"><i class="fas fa-circle-check me-1"></i>Yes</span>';
        }
        if (s === '2' || sLow === 'no' || sLow === 'false') {
            return '<span class="badge rounded-pill bg-light text-secondary border px-3 py-1 fw-semibold"><i class="fas fa-circle-xmark me-1"></i>No</span>';
        }

        if (/severity/i.test(qText || '')) {
            if (/severe|high/i.test(sLow)) return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 fw-semibold">' + escapeHtml(s) + '</span>';
            if (/moderate|medium/i.test(sLow)) return '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 fw-semibold">' + escapeHtml(s) + '</span>';
            if (/mild|low/i.test(sLow)) return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-semibold">' + escapeHtml(s) + '</span>';
        }

        return '<span class="text-dark fw-medium">' + escapeHtml(s) + '</span>';
    }

    // Filter dynamic questions table
    window.ppFilterQuestions = function() {
        var query = (document.getElementById('pp-qa-search').value || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#pp-qa-tbody tr');
        var matched = 0;
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            var isMatch = !query || text.indexOf(query) !== -1;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) matched++;
        });
        var countBadge = document.getElementById('pp-qa-count');
        if (countBadge && query) {
            countBadge.textContent = matched + ' Matched';
        }
    };

    // Render Full List of All Questions & Patient Responses
    function renderAllQuestions(q) {
        var table = document.getElementById('pp-qa-table');
        var tbody = document.getElementById('pp-qa-tbody');
        var countBadge = document.getElementById('pp-qa-count');
        if (!table || !tbody) return;

        // Ensure table header strictly has the 3 correct columns without any injected checkboxes or rogue th
        var theadTr = table.querySelector('thead tr');
        if (theadTr) {
            theadTr.innerHTML = 
                '<th style="width: 50px;" class="text-center">#</th>' +
                '<th style="width: 45%;">Question Prompt</th>' +
                '<th style="width: 50%;">Patient\'s Answer</th>';
        }

        tbody.innerHTML = '';

        var raw = q.raw || {};
        var qaList = [];
        var seenTitles = {}; // Track by normalized question prompt to prevent ANY duplicate

        // Dictionary to map ANY raw key or variable to human-readable clinical question title
        var QUESTION_LABELS = {
            // Patient Demographics
            'first_name': 'First Name',
            'last_name': 'Last Name',
            'full_name': 'Full Legal Name',
            'name': 'Full Legal Name',
            'patient_name': 'Full Legal Name',
            'date_of_birth': 'Date of Birth',
            'dob': 'Date of Birth',
            'birth_date': 'Date of Birth',
            'gender': 'Sex Assigned at Birth',
            'sex': 'Sex Assigned at Birth',
            'sex_at_birth': 'Sex Assigned at Birth',
            'sex_assigned_at_birth': 'Sex Assigned at Birth',
            'email': 'Email Address',
            'email_address': 'Email Address',
            'patient_email': 'Email Address',
            'phone': 'Mobile Phone Number',
            'phone_number': 'Mobile Phone Number',
            'mobile': 'Mobile Phone Number',
            'mobile_phone': 'Mobile Phone Number',
            'state_of_residence': 'State of Residence',
            'state': 'State of Residence',
            'primary_problem': 'Primary Reason for Visit',
            'preferred_language': 'Preferred Language',
            'language': 'Preferred Language',
            'is_adult': 'Are you 18 years of age or older?',
            'is_18_plus': 'Are you 18 years of age or older?',
            'is_pregnant': 'Are you currently pregnant?',

            // Visit Details & Symptoms
            'selected_service': 'Selected Service / Condition',
            'service_condition': 'Selected Service / Condition',
            'condition': 'Selected Service / Condition',
            'consultation_type': 'Preferred Consultation Type',
            'appointment_type': 'Preferred Consultation Type',
            'referral_source': 'How did you hear about us? (Referral Source)',
            'reason_for_visit': 'Main Reason for Visit',
            'chief_complaint': 'Main Reason for Visit',
            'symptom_description': 'Detailed Symptoms Description',
            'symptoms_description': 'Detailed Symptoms Description',
            'symptoms': 'Detailed Symptoms Description',
            'symptom_duration': 'How long have you had these symptoms?',
            'symptoms_duration': 'How long have you had these symptoms?',
            'symptom_severity': 'Symptom Severity',
            'has_fever': 'Do you currently have a fever?',
            'fever': 'Do you currently have a fever?',
            'tried_treatment': 'Have you tried any treatment for these symptoms?',
            'treatment_tried': 'Prior Treatment Details',
            'treatment_tried_details': 'Prior Treatment Details',
            'treatment_details': 'Prior Treatment Details',
            'red_flags': 'Trouble breathing, chest pain, or confusion (Red Flags)',

            // Condition: Asthma
            'asthma_rescue_use': 'Asthma - Rescue inhaler frequency',
            'asthma_night': 'Asthma - Nighttime waking symptoms?',
            'asthma_night_waking': 'Asthma - Nighttime waking symptoms?',
            'asthma_er_visit': 'Asthma - ER / Hospital visits in past year?',
            'asthma_wheeze': 'Asthma - Current wheezing or shortness of breath?',

            // Condition: UTI
            'uti_burning': 'UTI - Burning or pain when urinating?',
            'uti_burning_pain': 'UTI - Burning or pain when urinating?',
            'uti_frequency': 'UTI - Frequent or urgent urination?',
            'uti_blood': 'UTI - Blood in urine?',
            'uti_flank_pain': 'UTI - Flank or lower back pain?',
            'uti_history': 'UTI - Prior history of UTI?',
            'uti_previous': 'UTI - Prior history of UTI?',
            'uti_discharge': 'UTI - Vaginal discharge or irritation?',

            // Condition: Allergy
            'allergy_main_symptom': 'Allergy - Main Symptoms',
            'allergy_symptoms': 'Allergy - Main Symptoms',
            'allergy_triggers': 'Allergy - Known Triggers',
            'allergy_anaphylaxis': 'Allergy - Severe reaction (anaphylaxis) history?',
            'allergy_epipen': 'Allergy - Carries epinephrine auto-injector (EpiPen)?',

            // Condition: Refill
            'refill_med_name': 'Prescription Refill - Medication Requested',
            'refill_medication': 'Prescription Refill - Medication Requested',
            'refill_med_dose': 'Prescription Refill - Dose & Frequency',
            'refill_doctor': 'Prescribing Doctor',
            'refill_last_date': 'Prescription Refill - Date of Last Refill',

            // Condition: Birth Control
            'bc_current_method': 'Current Birth Control Method',
            'bc_lmp': 'First Day of Last Menstrual Period',
            'bc_smoker_35': 'Do you smoke and are you over 35?',
            'bc_clot_history': 'History of blood clots, stroke, or migraines with aura?',

            // Condition: COVID-19
            'covid19': 'Tested positive or diagnosed with COVID-19?',
            'suspected_covid': 'Suspected COVID-19 Symptoms?',
            'covid_contact': 'Close contact with a confirmed COVID-19 case?',
            'covid_test_result': 'Recent COVID-19 Test Result',
            'covid_taste_smell': 'Loss of taste or smell?',
            'covid_vaccinated': 'COVID-19 vaccination or booster in past year?',

            // Condition: Rash
            'rash_location': 'Rash - Location on body',
            'rash_itchy': 'Rash - Is it itchy?',
            'rash_new_exposure': 'Rash - New exposure to soap, medicine, plant, or food?',
            'rash_spreading': 'Rash - Spreading quickly or blistering?',

            // Medical History: Chronic Conditions
            'hx_hypertension': 'High Blood Pressure (Hypertension)',
            'hypertension': 'High Blood Pressure (Hypertension)',
            'hx_diabetes': 'Diabetes',
            'diabetes': 'Diabetes',
            'hx_heart': 'Heart Disease',
            'heart_disease': 'Heart Disease',
            'hx_asthma': 'Asthma or Lung Disease',
            'hx_lung': 'Asthma or Lung Disease',
            'lung_disease': 'Asthma or Lung Disease',
            'hx_kidney_liver': 'Kidney or Liver Disease',
            'kidney_liver': 'Kidney or Liver Disease',
            'hx_cancer_immune': 'Cancer or Immune System Disorder',
            'cancer_immune': 'Cancer or Immune System Disorder',
            'surgeries': 'Other Medical Conditions / Surgeries',
            'hx_other': 'Other Medical Conditions / Surgeries',
            'other_conditions': 'Other Medical Conditions / Surgeries',

            // Medications & Drug Allergies
            'current_medications': 'Currently taking any medications, vitamins, or supplements?',
            'takes_medications': 'Currently taking any medications, vitamins, or supplements?',
            'taking_medications': 'Currently taking any medications, vitamins, or supplements?',
            'medication_list': 'Medications List & Dosages',
            'medications_list': 'Medications List & Dosages',
            'medications': 'Medications List & Dosages',
            'known_drug_allergies': 'Known Drug Allergies?',
            'has_drug_allergy': 'Known Drug Allergies?',
            'drug_allergies': 'Known Drug Allergies?',
            'drug_allergies_list': 'Drug Allergies List & Reactions',
            'drug_allergy_list': 'Drug Allergies List & Reactions',

            // Social & Pharmacy
            'tobacco_use': 'Tobacco / Vaping Use',
            'alcohol_use': 'Alcohol Use',
            'pharmacy_name': 'Preferred Pharmacy Name',
            'pharmacy_location': 'Preferred Pharmacy Location (City / ZIP Code)',

            // Consent & Signature
            'consent_all': 'Consent & Acknowledgements',
            'consent': 'Consent & Acknowledgements',
            'e_signature': 'Full Legal Name (E-Signature)',
            'signature': 'Full Legal Name (E-Signature)',
            'full_legal_name': 'Full Legal Name (E-Signature)',
            'signature_date': 'Date of Signature'
        };

        // Keys to ignore completely (technical tokens, system metadata, HTML)
        var skipKeys = [
            'user_id', 'patient_id', 'submission_id', 'submissionId', 'Submission Id',
            'form_id', 'Form Id', 'workflowId', 'raw_data', 'viewSubmissionLink',
            'downloadPdfLink', 'View Submission Link', 'Download PDF Link',
            'token', 'action', 'nonce', '_wpnonce', 'status', 'id'
        ];

        function normalizeKey(str) {
            return String(str || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        }

        function addQA(title, answer) {
            if (answer === undefined || answer === null) return;
            var aStr = String(answer).trim();
            if (aStr === '' || aStr === '—') return;
            
            // Filter out HTML strings, instructional widgets, or disclaimer notices
            if (/<[a-z][\s\S]*>/i.test(aStr) || /please answer the questions|fields marked with|do not continue with this form|before completing this form/i.test(aStr)) {
                return;
            }

            // Filter out UUID or raw technical hex tokens as question titles
            if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(title) || /^[0-9a-f-]{30,}$/i.test(title)) {
                return;
            }

            var normTitle = normalizeKey(title);
            if (!normTitle || seenTitles[normTitle]) return;
            seenTitles[normTitle] = true;

            qaList.push({
                question: title,
                answer: aStr
            });
        }

        // 1. Patient Demographics & Profile
        if (raw.first_name) {
            addQA('First Name', raw.first_name);
        }
        if (raw.last_name) {
            addQA('Last Name', raw.last_name);
        }
        var fullNameVal = q.full_name || raw.full_name || raw.name;
        if (fullNameVal && fullNameVal !== raw.first_name) {
            addQA('Full Legal Name', fullNameVal);
        }
        addQA('Date of Birth', q.dob || raw.date_of_birth || raw.dob || raw['Date of Birth']);
        addQA('Sex Assigned at Birth', q.gender || raw['Sex Assigned at Birth (Labels)'] || raw['Sex Assigned at Birth'] || raw.gender || raw.sex || raw.sex_at_birth);
        addQA('Email Address', q.email || raw.email || raw.email_address || raw['Email Address']);
        addQA('Mobile Phone Number', q.phone || raw.phone_number || raw.phone || raw['Mobile Phone Number']);
        addQA('State of Residence', raw.state_of_residence || raw.state || raw['State of Residence']);
        addQA('Preferred Language', q.preferred_language || raw.preferred_language || raw.language || raw['Preferred Language']);
        addQA('Primary Reason for Visit', raw.primary_problem || raw['Primary Reason for Visit']);
        addQA('Are you 18 years of age or older?', raw['Are you 18 years of age or older? (Labels)'] || raw['Are you 18 years of age or older?'] || raw.is_adult || raw.is_18_plus || q.is_adult);
        addQA('Are you currently pregnant?', raw['Are you currently pregnant? (Labels)'] || raw['Are you currently pregnant?'] || raw.is_pregnant || q.is_pregnant);
        addQA('Tested positive or diagnosed with COVID-19?', raw.covid19 || raw['Tested positive or diagnosed with COVID-19? (Labels)']);
        addQA('Suspected COVID-19 Symptoms?', raw.suspected_covid || raw['Suspected COVID-19 Symptoms? (Labels)']);

        // 2. Visit Details & Symptoms
        addQA('Selected Service / Condition', q.selected_service || raw['Selected Service / Condition (Labels)'] || raw['Selected Service / Condition'] || raw.selected_service);
        addQA('Preferred Consultation Type', q.consultation_type || raw['Preferred Consultation Type (Labels)'] || raw['Preferred Consultation Type'] || raw.consultation_type);
        addQA('How did you hear about us? (Referral Source)', q.referral_source || raw['How did you hear about us? (Labels)'] || raw['How did you hear about us?'] || raw.referral_source);
        addQA('Main Reason for Visit', q.reason_for_visit || raw['Main Reason for Visit'] || raw.reason_for_visit || raw.chief_complaint);
        addQA('Detailed Symptoms Description', q.symptom_description || raw['Describe Your Symptoms'] || raw.symptom_description || raw.symptoms);
        addQA('How long have you had these symptoms?', q.symptom_duration || raw['How long have you had these symptoms? (Labels)'] || raw['How long have you had these symptoms?'] || raw.symptoms_duration || raw.symptom_duration);
        addQA('Symptom Severity', q.symptom_severity || raw['Symptom Severity (Labels)'] || raw['Symptom Severity'] || raw.symptom_severity);
        addQA('Do you currently have a fever?', q.has_fever || raw['Do you currently have a fever? (Labels)'] || raw['Do you currently have a fever?'] || raw.has_fever || raw.fever);
        addQA('Have you tried any treatment for these symptoms?', q.tried_treatment || raw['Have you tried any treatment for these symptoms? (Labels)'] || raw['Have you tried any treatment for these symptoms?'] || raw.tried_treatment);
        addQA('Prior Treatment Details', q.treatment_tried_details || raw['Treatment Tried'] || raw.treatment_tried || raw.treatment_tried_details || raw.treatment_details);
        addQA('Trouble breathing, chest pain, or confusion (Red Flags)', q.red_flags || raw['Do you have trouble breathing, chest pain, or confusion? (Labels)'] || raw['Do you have trouble breathing, chest pain, or confusion?'] || raw.red_flags);

        // 3. Condition-Specific Assessments (only added if answer was provided)
        // Asthma
        addQA('Asthma - Rescue inhaler frequency', (q.asthma || {}).rescue_use || raw['How often do you use a rescue inhaler? (Labels)'] || raw['How often do you use a rescue inhaler?'] || raw.asthma_rescue_use);
        addQA('Asthma - Nighttime waking symptoms?', (q.asthma || {}).night_symptoms || raw['Do symptoms wake you at night? (Labels)'] || raw['Do symptoms wake you at night?'] || raw.asthma_night_waking || raw.asthma_night);
        addQA('Asthma - ER / Hospital visits in past year?', (q.asthma || {}).er_visit || raw['ER or hospital visit for asthma in the past year? (Labels)'] || raw['ER or hospital visit for asthma in the past year?'] || raw.asthma_er_visit);
        addQA('Asthma - Current wheezing or shortness of breath?', (q.asthma || {}).wheeze || raw['Do you currently have a wheeze or shortness of breath? (Labels)'] || raw.asthma_wheeze);

        // UTI
        addQA('UTI - Burning or pain when urinating?', (q.uti || {}).burning || raw['Burning or pain when urinating? (Labels)'] || raw['Burning or pain when urinating?'] || raw.uti_burning);
        addQA('UTI - Frequent or urgent urination?', (q.uti || {}).frequency || raw['Frequent or urgent need to urinate? (Labels)'] || raw['Frequent or urgent need to urinate?'] || raw.uti_frequency);
        addQA('UTI - Blood in urine?', (q.uti || {}).blood || raw['Blood in your urine? (Labels)'] || raw['Blood in your urine?'] || raw.uti_blood);
        addQA('UTI - Flank or lower back pain?', (q.uti || {}).flank_pain || raw['Pain in your side or lower back? (Labels)'] || raw['Pain in your side or lower back?'] || raw.uti_flank_pain);
        addQA('UTI - Prior history of UTI?', (q.uti || {}).history || raw['Have you had a UTI before? (Labels)'] || raw['Have you had a UTI before?'] || raw.uti_history);
        addQA('UTI - Vaginal discharge or irritation?', (q.uti || {}).discharge || raw['Any vaginal discharge or irritation? (Labels)'] || raw.uti_discharge);

        // Allergy
        addQA('Allergy - Main Symptoms', (q.allergy || {}).main_symptom || raw['Main Symptom (Labels)'] || raw['Main Symptom'] || raw.allergy_main_symptom || raw.allergy_symptoms);
        addQA('Allergy - Known Triggers', (q.allergy || {}).triggers || raw['Known Triggers'] || raw.allergy_triggers);
        addQA('Allergy - Severe reaction (anaphylaxis) history?', (q.allergy || {}).anaphylaxis || raw['Have you ever had a severe allergic reaction (anaphylaxis)? (Labels)'] || raw['Have you ever had a severe allergic reaction (anaphylaxis)?'] || raw.allergy_anaphylaxis);
        addQA('Allergy - Carries epinephrine auto-injector (EpiPen)?', (q.allergy || {}).epipen || raw['Do you carry an epinephrine auto-injector? (Labels)'] || raw['Do you carry an epinephrine auto-injector?'] || raw.allergy_epipen);

        // Refill
        addQA('Prescription Refill - Medication Requested', (q.refill || {}).med_name || raw['Medication Name'] || raw.refill_medication || raw.refill_med_name);
        addQA('Prescription Refill - Dose & Frequency', (q.refill || {}).med_dose || raw['Dose and How Often'] || raw.refill_med_dose);
        addQA('Prescribing Doctor', (q.refill || {}).doctor || raw['Prescribing Doctor'] || raw.refill_doctor);
        addQA('Prescription Refill - Date of Last Refill', (q.refill || {}).last_date || raw['Approximate Date of Last Refill'] || raw.refill_last_date);

        // Birth Control
        addQA('Current Birth Control Method', (q.birth_control || {}).method || raw['Current Birth Control Method (Labels)'] || raw['Current Birth Control Method'] || raw.bc_current_method);
        addQA('First Day of Last Menstrual Period', (q.birth_control || {}).lmp || raw['First Day of Last Menstrual Period'] || raw.bc_lmp);
        addQA('Do you smoke and are you over 35?', (q.birth_control || {}).smoker_35 || raw['Do you smoke and are you over 35? (Labels)'] || raw.bc_smoker_35);
        addQA('History of blood clots, stroke, or migraine with aura?', (q.birth_control || {}).clot_history || raw['History of blood clots, stroke or migraines with aura? (Labels)'] || raw.bc_clot_history);

        // COVID Details
        addQA('Close contact with a confirmed COVID-19 case?', (q.covid || {}).contact || raw['Close contact with a confirmed case in the past 10 days? (Labels)'] || raw.covid_contact);
        addQA('Recent COVID-19 Test Result', (q.covid || {}).test_result || raw['Recent Test Result (Labels)'] || raw.covid_test_result);
        addQA('Loss of taste or smell?', (q.covid || {}).taste_smell || raw['Loss of taste or smell? (Labels)'] || raw.covid_taste_smell);
        addQA('COVID-19 vaccination or booster in past year?', (q.covid || {}).vaccinated || raw['Vaccinated or boosted in the past year? (Labels)'] || raw.covid_vaccinated);

        // Rash
        addQA('Rash - Location on body', (q.rash || {}).location || raw['Where on your body is the rash?'] || raw.rash_location);
        addQA('Rash - Is it itchy?', (q.rash || {}).itchy || raw['Is it itchy? (Labels)'] || raw.rash_itchy);
        addQA('Rash - New exposure to soap, medicine, plant, or food?', (q.rash || {}).new_exposure || raw['Any new soap, medicine, plant or food exposure? (Labels)'] || raw.rash_new_exposure);
        addQA('Rash - Spreading quickly or blistering?', (q.rash || {}).spreading || raw['Is the rash spreading quickly or blistering? (Labels)'] || raw.rash_spreading);

        // 4. Past Medical History & Chronic Conditions
        addQA('High Blood Pressure (Hypertension)', (q.medical_history || {}).hypertension || raw['High blood pressure? (Labels)'] || raw['High blood pressure?'] || raw.hx_hypertension || raw.hypertension);
        addQA('Diabetes', (q.medical_history || {}).diabetes || raw['Diabetes? (Labels)'] || raw['Diabetes?'] || raw.hx_diabetes || raw.diabetes);
        addQA('Heart Disease', (q.medical_history || {}).heart_disease || raw['Heart disease? (Labels)'] || raw['Heart disease?'] || raw.hx_heart || raw.heart_disease);
        addQA('Asthma or Lung Disease', (q.medical_history || {}).lung_disease || raw['Asthma or lung disease? (Labels)'] || raw['Asthma or lung disease?'] || raw.hx_asthma || raw.hx_lung || raw.lung_disease);
        addQA('Kidney or Liver Disease', (q.medical_history || {}).kidney_liver || raw['Kidney or liver disease? (Labels)'] || raw['Kidney or liver disease?'] || raw.hx_kidney_liver || raw.kidney_liver);
        addQA('Cancer or Immune System Disorder', (q.medical_history || {}).cancer_immune || raw['Cancer or immune system disorder? (Labels)'] || raw['Cancer or immune system disorder?'] || raw.hx_cancer_immune || raw.cancer_immune);
        addQA('Other Medical Conditions / Surgeries', (q.medical_history || {}).other || raw['Other Medical Conditions or Past Surgeries'] || raw.surgeries || raw.hx_other || raw.other_conditions);

        // 5. Patient-Reported Medications & Drug Allergies
        addQA('Currently taking any medications, vitamins, or supplements?', (q.medications || {}).takes_medications || raw['Are you currently taking any medications, vitamins or supplements? (Labels)'] || raw['Are you currently taking any medications, vitamins or supplements?'] || raw.current_medications || raw.takes_medications);
        addQA('Medications List & Dosages', (q.medications || {}).list || raw['Please List Your Medications'] || raw.medication_list || raw.medications_list);
        addQA('Known Drug Allergies?', (q.allergies || {}).has_drug_allergy || raw['Do you have any known drug allergies? (Labels)'] || raw['Do you have any known drug allergies?'] || raw.known_drug_allergies || raw.has_drug_allergy);
        addQA('Drug Allergies List & Reactions', (q.allergies || {}).list || raw['Please List Allergies and Reactions'] || raw.drug_allergies_list || raw.drug_allergy_list);

        // 6. Social History & Preferred Pharmacy
        addQA('Tobacco / Vaping Use', (q.social_history || {}).tobacco || raw['Tobacco / Vaping Use (Labels)'] || raw['Tobacco / Vaping Use'] || raw.tobacco_use);
        addQA('Alcohol Use', (q.social_history || {}).alcohol || raw['Alcohol Use (Labels)'] || raw['Alcohol Use'] || raw.alcohol_use);
        addQA('Preferred Pharmacy Name', (q.pharmacy || {}).name || raw['Pharmacy Name'] || raw.pharmacy_name);
        addQA('Preferred Pharmacy Location (City / ZIP Code)', (q.pharmacy || {}).location || raw['Pharmacy City / ZIP Code'] || raw.pharmacy_location);

        // 7. Electronic Consent & Signature
        addQA('Consent & Acknowledgements', q.consent || raw['Consent & Acknowledgements (Labels)'] || raw['Consent & Acknowledgements'] || raw.consent_all);
        addQA('Full Legal Name (E-Signature)', q.signature || raw['Type Your Full Legal Name'] || raw.e_signature || raw.signature);
        addQA('Date of Signature', q.signature_date || raw['Date'] || raw.signature_date);

        // 8. Dynamic Fallback: Process any remaining raw fields not captured in the canonical sequence
        Object.keys(raw).forEach(function(key) {
            if (skipKeys.indexOf(key) !== -1) return;
            if (key.indexOf('(Values)') !== -1) return;
            // Ignore UUIDs (HIPAAtizer static widget IDs) or token keys
            if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(key) || /^[0-9a-f-]{30,}$/i.test(key)) return;
            // Ignore any HTML tag or disclaimer notice keys
            if (key.indexOf('<') !== -1 || /^<[a-z!]/i.test(key) || /before completing|disclaimer|notice|agreement|please answer/i.test(key)) return;

            var cleanKey = key.replace(/\s*\(Labels\)\s*$/, '').trim();
            if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(cleanKey) || /^[0-9a-f-]{30,}$/i.test(cleanKey)) return;

            var mappedTitle = QUESTION_LABELS[cleanKey];

            // If mapped title was already rendered in canonical sequence, skip!
            if (mappedTitle && seenTitles[normalizeKey(mappedTitle)]) return;
            if (seenTitles[normalizeKey(cleanKey)]) return;

            var val = raw[cleanKey + ' (Labels)'] !== undefined ? raw[cleanKey + ' (Labels)'] : raw[key];
            if (val === undefined || val === null) return;
            var valStr = String(val).trim();
            // Ignore if value is HTML markup, static instruction, or disclaimer
            if (/<[a-z][\s\S]*>/i.test(valStr) || /please answer the questions|fields marked with|do not continue with this form/i.test(valStr)) return;

            // Determine prompt label
            var finalTitle = mappedTitle;
            if (!finalTitle) {
                // If it looks like a variable with underscores, convert to Title Case words
                if (cleanKey.indexOf('_') !== -1) {
                    finalTitle = cleanKey.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                } else {
                    finalTitle = cleanKey;
                }
            }

            if (seenTitles[normalizeKey(finalTitle)]) return;
            addQA(finalTitle, val);
        });

        if (countBadge) {
            countBadge.textContent = qaList.length + ' Questions Answered';
        }

        if (!qaList.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4"><i class="fas fa-clipboard-question d-block mb-1 fs-4 opacity-50"></i>No individual question responses on record.</td></tr>';
            return;
        }

        qaList.forEach(function(item, idx) {
            var tr = document.createElement('tr');
            var ansFormatted = formatQuestionAnswer(item.answer, item.question);
            tr.innerHTML = 
                '<td class="text-secondary small font-monospace text-center">' + (idx + 1) + '</td>' +
                '<td class="fw-semibold text-dark">' + escapeHtml(item.question) + '</td>' +
                '<td>' + ansFormatted + '</td>';
            tbody.appendChild(tr);
        });
    }

    // Questionnaire rendering
    function renderQuestionnaire(q) {
        var emptyBox = document.getElementById('pp-questionnaire-empty');
        var contentBox = document.getElementById('pp-questionnaire-content');
        if (!emptyBox || !contentBox) return;

        if (!q || (!q.submitted_at && !q.reason_for_visit && !q.symptom_description && !q.submission_id)) {
            emptyBox.hidden = false;
            contentBox.hidden = true;
            return;
        }

        emptyBox.hidden = true;
        contentBox.hidden = false;

        function qField(key, val, def, isHtml) {
            document.querySelectorAll('[data-pp-q="' + key + '"]').forEach(function(el) {
                if (isHtml) {
                    el.innerHTML = val || def || '—';
                } else {
                    el.textContent = val || def || '—';
                }
            });
        }

        qField('submitted_at', q.submitted_at);
        qField('submission_id', q.submission_id);
        qField('selected_service', q.selected_service);
        qField('consultation_type', q.consultation_type);
        qField('referral_source', q.referral_source);
        qField('reason_for_visit', q.reason_for_visit);
        qField('symptom_description', q.symptom_description);
        qField('symptom_duration', q.symptom_duration);

        // Clean boolean formatting for symptoms
        qField('has_fever', formatBool(q.has_fever, 'danger'), '—', true);
        qField('tried_treatment', formatBool(q.tried_treatment), '—', true);
        qField('treatment_tried_details', q.treatment_tried_details || '—');

        // Red Flags Alert Banner: ONLY show if true red flags reported
        var redFlagBox = document.getElementById('pp-q-redflag-box');
        if (redFlagBox) {
            var rf = (q.red_flags || '').toString().toLowerCase().trim();
            var hasRf = rf && rf !== 'no' && rf !== 'none' && rf !== 'false' && rf !== '0' && rf !== '2' && rf !== '—';
            redFlagBox.hidden = !hasRf;
            if (hasRf) {
                qField('red_flags', formatBool(q.red_flags, 'danger'), '—', true);
            }
        }

        // Severity badge
        var sevEl = document.querySelector('[data-pp-q-badge="symptom_severity"]');
        if (sevEl) {
            var sev = q.symptom_severity || '—';
            var bClass = 'bg-light text-dark border';
            var sLow = sev.toLowerCase();
            if (sLow.indexOf('severe') !== -1 || sLow.indexOf('high') !== -1 || sLow.indexOf('8') !== -1 || sLow.indexOf('9') !== -1 || sLow.indexOf('10') !== -1) {
                bClass = 'bg-danger-subtle text-danger border border-danger-subtle';
            } else if (sLow.indexOf('moderate') !== -1 || sLow.indexOf('medium') !== -1 || sLow.indexOf('4') !== -1 || sLow.indexOf('5') !== -1 || sLow.indexOf('6') !== -1 || sLow.indexOf('7') !== -1) {
                bClass = 'bg-warning-subtle text-warning border border-warning-subtle';
            } else if (sLow.indexOf('mild') !== -1 || sLow.indexOf('low') !== -1 || sLow.indexOf('1') !== -1 || sLow.indexOf('2') !== -1 || sLow.indexOf('3') !== -1) {
                bClass = 'bg-success-subtle text-success border border-success-subtle';
            }
            sevEl.className = 'badge rounded-pill px-3 py-1 small fw-semibold ' + bClass;
            sevEl.textContent = sev;
        }

        // Action links
        var pdfLink = document.querySelector('[data-pp-q-link="pdf"]');
        if (pdfLink) {
            var pUrl = q.view_pdf_link || q.download_pdf_link;
            if (pUrl && pUrl !== '—') {
                pdfLink.href = pUrl;
                pdfLink.classList.remove('d-none');
            } else {
                pdfLink.classList.add('d-none');
            }
        }

        var subLink = document.querySelector('[data-pp-q-link="submission"]');
        if (subLink) {
            var sUrl = q.view_submission_link;
            if (sUrl && sUrl !== '—') {
                subLink.href = sUrl;
                subLink.classList.remove('d-none');
            } else {
                subLink.classList.add('d-none');
            }
        }

        // Condition-Specific Assessments: Dynamically show only modules with actual data
        var activeModulesCount = 0;
        var serviceLower = String(q.selected_service || '').toLowerCase();
        var conditionModules = [
            { id: 'uti', data: q.uti || {}, label: 'uti' },
            { id: 'allergy', data: q.allergy || {}, label: 'allergy' },
            { id: 'asthma', data: q.asthma || {}, label: 'asthma' },
            { id: 'refill', data: q.refill || {}, label: 'refill' }
        ];

        // Format each module's fields
        var uti = q.uti || {};
        qField('uti_burning', formatBool(uti.burning), '—', true);
        qField('uti_frequency', formatBool(uti.frequency), '—', true);
        qField('uti_blood', formatBool(uti.blood, 'danger'), '—', true);
        qField('uti_flank_pain', formatBool(uti.flank_pain, 'warning'), '—', true);
        qField('uti_history', formatBool(uti.history), '—', true);

        var allergy = q.allergy || {};
        qField('allergy_main_symptom', allergy.main_symptom);
        qField('allergy_triggers', allergy.triggers);
        qField('allergy_anaphylaxis', formatBool(allergy.anaphylaxis, 'danger'), '—', true);
        qField('allergy_epipen', formatBool(allergy.epipen), '—', true);

        var asthma = q.asthma || {};
        qField('asthma_rescue_use', asthma.rescue_use);
        qField('asthma_night', formatBool(asthma.night_symptoms), '—', true);
        qField('asthma_er_visit', formatBool(asthma.er_visit, 'danger'), '—', true);

        var refill = q.refill || {};
        qField('refill_med_name', refill.med_name);
        qField('refill_med_dose', refill.med_dose);
        qField('refill_last_date', refill.last_date);

        // Hide empty modules so it doesn't look like dummy UI with empty dashes
        conditionModules.forEach(function(m) {
            var el = document.querySelector('[data-pp-module="' + m.id + '"]');
            if (!el) return;
            var hasData = false;
            if (serviceLower.indexOf(m.label) !== -1) {
                hasData = true;
            }
            Object.keys(m.data).forEach(function(k) {
                var v = String(m.data[k] || '').trim();
                if (v && v !== '—' && v !== '2' && v !== 'no' && v !== 'none' && v !== 'false') {
                    hasData = true;
                }
            });
            if (hasData) {
                el.style.display = '';
                activeModulesCount++;
            } else {
                el.style.display = 'none';
            }
        });

        var modulesHeader = document.getElementById('pp-q-modules-header');
        var modulesRow = document.getElementById('pp-q-modules-row');
        if (modulesHeader) {
            modulesHeader.style.display = activeModulesCount > 0 ? '' : 'none';
        }
        if (modulesRow) {
            modulesRow.style.display = activeModulesCount > 0 ? '' : 'none';
        }

        // Medical History & Chronic Conditions: Clean badges
        var hx = q.medical_history || {};
        qField('hx_hypertension', formatBool(hx.hypertension, 'warning'), '—', true);
        qField('hx_diabetes', formatBool(hx.diabetes, 'warning'), '—', true);
        qField('hx_heart', formatBool(hx.heart_disease, 'warning'), '—', true);
        qField('hx_lung', formatBool(hx.lung_disease, 'warning'), '—', true);
        qField('hx_kidney_liver', formatBool(hx.kidney_liver, 'warning'), '—', true);
        qField('hx_cancer_immune', formatBool(hx.cancer_immune, 'warning'), '—', true);
        qField('hx_other', hx.other || 'No other chronic conditions or past surgeries reported.');

        // Patient-Reported Medications
        var meds = q.medications || {};
        var medList = String(meds.list || '').trim();
        var takesMeds = String(meds.takes_medications || '').trim().toLowerCase();
        var medBox = document.querySelector('[data-pp-q="medication_list"]');
        if (medBox) {
            if (medList && medList !== '2' && medList !== 'no' && medList !== 'none') {
                medBox.innerHTML = '<div class="text-dark fw-semibold"><i class="fas fa-pills text-primary me-2"></i>' + escapeHtml(medList) + '</div>';
            } else if (takesMeds === '2' || takesMeds === 'no' || takesMeds === 'none' || takesMeds === 'false' || !takesMeds) {
                medBox.innerHTML = '<span class="text-secondary small"><i class="fas fa-check-circle text-success me-1"></i> Patient reported taking no daily medications, vitamins, or supplements.</span>';
            } else {
                medBox.textContent = 'None reported.';
            }
        }

        // Patient-Reported Drug Allergies
        var algs = q.allergies || {};
        var algList = String(algs.list || '').trim();
        var hasAlg = String(algs.has_drug_allergy || '').trim().toLowerCase();
        var algBox = document.querySelector('[data-pp-q="drug_allergy_list"]');
        if (algBox) {
            if (algList && algList !== '2' && algList !== 'no' && algList !== 'none') {
                algBox.innerHTML = '<div class="text-danger fw-semibold"><i class="fas fa-triangle-exclamation text-danger me-2"></i>' + escapeHtml(algList) + '</div>';
            } else if (hasAlg === '2' || hasAlg === 'no' || hasAlg === 'none' || hasAlg === 'false' || !hasAlg) {
                algBox.innerHTML = '<span class="text-secondary small"><i class="fas fa-shield-halved text-success me-1"></i> No known drug allergies (NKDA) reported by patient.</span>';
            } else {
                algBox.textContent = 'None reported.';
            }
        }

        // Legal Consent & Signatures
        var pharm = q.pharmacy || {};
        qField('pharmacy_name', pharm.name);
        qField('pharmacy_location', pharm.location);
        qField('e_signature', q.signature);
        qField('signature_date', q.signature_date);

        // Dynamically populate all Questions & Patient Answers
        renderAllQuestions(q);
    }

    // Load Patient API Request
    function loadPatient(id) {
        if (!id) {
            notify('warning', 'Select a Patient', 'Please choose a patient from the dropdown list first.');
            return;
        }
        if (selectedPatient && String(selectedPatient.user_id) === String(id) && chart && !chart.hidden) {
            if (picker) picker.value = id;
            return;
        }
        var btn = document.getElementById('pp-load-patient-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> Opening…';
        }
        request('dior_doc_get_patient_profile', { patient_id: id })
            .then(function(data) {
                renderPatient(data.patient);
                if (picker) {
                    var opt = picker.querySelector('option[value="' + id + '"]');
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = id;
                        var patName = (data && data.patient && (data.patient.full_name || (data.patient.first_name + ' ' + data.patient.last_name))) ? (data.patient.full_name || (data.patient.first_name + ' ' + data.patient.last_name)).trim() : ('Patient #' + id);
                        var patUid = (data && data.patient && data.patient.patient_uid) ? (' (' + data.patient.patient_uid + ')') : '';
                        opt.textContent = patName + patUid;
                        picker.appendChild(opt);
                    }
                    picker.value = id;
                }
            })
            .catch(function(err) {
                notify('error', 'Chart Loading Failed', err.message);
            })
            .finally(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-folder-open me-1"></i> Open Chart';
                }
            });
    }

    // Expose Global Loader for integration from other dashboard tabs
    window.diorLoadPatientProfile = function(patientId) {
        if (!patientId) return;
        if (picker) {
            var opt = picker.querySelector('option[value="' + patientId + '"]');
            if (opt) picker.value = patientId;
        }
        loadPatient(patientId);
    };

    // Event Listeners
    document.getElementById('pp-load-patient-btn').addEventListener('click', function() {
        loadPatient(picker.value);
    });

    document.getElementById('pp-refresh-btn').addEventListener('click', function() {
        if (selectedPatient) {
            loadPatient(selectedPatient.user_id);
        } else if (picker.value) {
            loadPatient(picker.value);
        }
    });

    picker.addEventListener('change', function() {
        if (this.value) {
            loadPatient(this.value);
        }
    });

    // 1. Submit Prescription Form (Dedicated Tab)
    document.getElementById('pp-prescription-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!selectedPatient) {
            notify('warning', 'No Patient Selected', 'Please load a patient chart first.');
            return;
        }
        var submitBtn = document.getElementById('pp-submit-rx-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Generating &amp; Routing...';

        var formData = new FormData();
        formData.append('action', 'dior_doctor_send_multi_prescription');
        formData.append('nonce', nonce);
        formData.append('patient_id', selectedPatient.user_id);
        formData.append('title', 'Clinical Prescription - ' + document.getElementById('pp-rx-medication').value.trim());
        formData.append('medications', document.getElementById('pp-rx-medication').value.trim());
        formData.append('dosage', document.getElementById('pp-rx-dosage').value.trim());
        formData.append('quantity', document.getElementById('pp-rx-quantity').value.trim());
        formData.append('frequency', document.getElementById('pp-rx-frequency').value);
        formData.append('duration', document.getElementById('pp-rx-duration').value);
        formData.append('refills', document.getElementById('pp-rx-refills').value);
        formData.append('status', document.getElementById('pp-rx-status').value);
        formData.append('instructions', document.getElementById('pp-rx-instructions').value.trim());
        formData.append('prescription_date', new Date().toISOString().split('T')[0]);

        fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Issue &amp; Send Prescription';
                if (!res.success) {
                    throw new Error(res.data && res.data.message ? res.data.message : 'Prescription failed.');
                }
                notify('success', 'Prescription Issued', 'Prescription has been added to the patient record successfully.');
                document.getElementById('pp-rx-medication').value = '';
                document.getElementById('pp-rx-dosage').value = '';
                document.getElementById('pp-rx-instructions').value = '';
                loadPatient(selectedPatient.user_id);
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Issue &amp; Send Prescription';
                notify('error', 'Prescription Error', err.message);
            });
    });

    // 2. Submit Consultation Note Form
    document.getElementById('pp-consultation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!selectedPatient) return;
        var btn = document.getElementById('pp-save-note-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> Saving...';

        request('dior_doc_save_consultation', {
            patient_id: selectedPatient.user_id,
            note: document.getElementById('pp-chief-complaint').value,
            diagnosis: document.getElementById('pp-diagnosis').value,
            assessment: document.getElementById('pp-assessment').value,
            plan: document.getElementById('pp-plan').value,
            follow_up: document.getElementById('pp-follow-up').value
        })
        .then(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-floppy-disk me-1"></i> Save Consultation Note';
            document.getElementById('pp-consultation-form').reset();
            loadPatient(selectedPatient.user_id);
            notify('success', 'Note Saved', 'Clinical consultation note added to patient record.');
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-floppy-disk me-1"></i> Save Consultation Note';
            notify('error', 'Save Failed', err.message);
        });
    });

    // 3. Submit Document Upload Form
    document.getElementById('pp-document-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!selectedPatient) return;
        var btn = document.getElementById('pp-doc-upload-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i> Uploading...';

        var fd = new FormData();
        fd.append('patient_id', selectedPatient.user_id);
        fd.append('doc_title', document.getElementById('pp-doc-title').value);
        fd.append('doc_category', document.getElementById('pp-doc-category').value);
        fd.append('doc_file', document.getElementById('pp-doc-file').files[0]);

        request('dior_upload_document', {}, fd)
            .then(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload File Securely';
                document.getElementById('pp-document-form').reset();
                loadPatient(selectedPatient.user_id);
                notify('success', 'Document Uploaded', 'Document successfully stored in patient chart.');
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload File Securely';
                notify('error', 'Upload Error', err.message);
            });
    });

    // 4. Update Document Metadata
    document.getElementById('pp-edit-document-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!selectedPatient) return;
        request('dior_doc_document_update_meta', {
            patient_id: selectedPatient.user_id,
            doc_uid: document.getElementById('pp-edit-doc-uid').value,
            title: document.getElementById('pp-edit-doc-name').value,
            category: document.getElementById('pp-edit-doc-category').value
        })
        .then(function() {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('pp-edit-document-modal')).hide();
            loadPatient(selectedPatient.user_id);
            notify('success', 'Updated', 'Document details updated.');
        })
        .catch(function(err) {
            notify('error', 'Update Failed', err.message);
        });
    });

    // Check initial patient from window or URL hash
    function checkInitialPatient() {
        var pid = window.diorSelectedPatientId;
        if (!pid) {
            var hashMatch = window.location.hash.match(/[?&#]patient_id=([0-9]+)/);
            if (hashMatch && hashMatch[1]) pid = hashMatch[1];
        }
        if (pid) {
            window.diorSelectedPatientId = null;
            loadPatient(pid);
        } else if (picker && picker.value && !selectedPatient) {
            loadPatient(picker.value);
        }
    }

    // Visibility Observer to auto-load patient when navigating from View Appointments, All Patients or URL
    var profileSection = document.getElementById('tab-doc-patients-profile');
    if (profileSection) {
        var observer = new MutationObserver(function() {
            if (profileSection.style.display !== 'none') {
                checkInitialPatient();
            }
        });
        observer.observe(profileSection, { attributes: true, attributeFilter: ['style', 'class'] });
        if (profileSection.style.display !== 'none') {
            checkInitialPatient();
        }
    }

})();
</script>


<style id="dior-patient-tabs-mobile-fix">
@media (max-width: 767.98px) {
  #dior-doctor-app #dior-doc-content #tab-doc-patients-all .dior-subtab-nav-bar,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-add .dior-subtab-nav-bar,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-edit .dior-subtab-nav-bar,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-profile .dior-subtab-nav-bar {
    display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;
    grid-auto-flow:row!important;align-items:stretch!important;gap:8px!important;
    width:100%!important;max-width:100%!important;padding:0!important;margin:0 0 16px!important;
  }
  #dior-doctor-app #dior-doc-content #tab-doc-patients-all .dior-subtab-nav-bar > .dior-subtab-pill,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-add .dior-subtab-nav-bar > .dior-subtab-pill,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-edit .dior-subtab-nav-bar > .dior-subtab-pill,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-profile .dior-subtab-nav-bar > .dior-subtab-pill {
    display:flex!important;align-items:center!important;justify-content:center!important;
    width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;
    padding:9px 7px!important;white-space:normal!important;text-align:center!important;
    line-height:1.25!important;box-sizing:border-box!important;
  }
  #dior-doctor-app #dior-doc-content #tab-doc-patients-all .dior-subtab-nav-bar > .dior-subtab-pill:last-child,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-add .dior-subtab-nav-bar > .dior-subtab-pill:last-child,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-edit .dior-subtab-nav-bar > .dior-subtab-pill:last-child,
  #dior-doctor-app #dior-doc-content #tab-doc-patients-profile .dior-subtab-nav-bar > .dior-subtab-pill:last-child {
    grid-column:1/-1!important;
  }
}
</style>
