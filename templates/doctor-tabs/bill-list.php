<section class="dior-tab-panel" id="tab-doc-accounts-bill-list" style="display: none;">
    <!-- Breadcrumb Header -->
    <div class="mb-4" style="display: flex; justify-content: space-between; align-items: center; padding: 0 5px;">
        <div>
            <h4 class="mb-0 text-dark" style="font-size: 20px; font-weight: 700;">Bill List</h4>
        </div>
        <div>
            <ul class="va-breadcrumb-list">
                <li><a href="#"><i class="fas fa-house"></i></a></li>
                <li>/</li>
                <li><a href="javascript:void(0)">Accounts</a></li>
                <li>/</li>
                <li class="active"><span>Bill List</span></li>
            </ul>
        </div>
    </div>

    <!-- Subtab Navigation Bar -->
    <div class="dior-subtab-nav-bar px-1 mb-4">
        <button type="button" class="dior-subtab-pill btn active" data-doc-tab="doc-accounts-bill-list" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-bill-list');">
            <i class="fas fa-receipt"></i> Bill List
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-add-bill" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-add-bill');">
            <i class="fas fa-file-invoice-dollar"></i> Add Bill
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-income" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income');">
            <i class="fas fa-arrow-trend-up"></i> Income
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-expenses" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-expenses');">
            <i class="fas fa-arrow-trend-down"></i> Expenses
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-income-report" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-income-report');">
            <i class="fas fa-chart-pie"></i> Income Report
        </button>
        <button type="button" class="dior-subtab-pill btn" data-doc-tab="doc-accounts-invoice" onclick="if(typeof diorDocSwitchTab==='function') diorDocSwitchTab('doc-accounts-invoice');">
            <i class="fas fa-file-invoice"></i> Invoice
        </button>
    </div>

    <div class="view-appointment-card">
        <div class="va-header-container">
            <div class="va-title-box">
                <h2><i class="fas fa-receipt text-primary me-2"></i>Bill List</h2>
                <div class="va-title-line"></div>
            </div>

            <div class="va-actions-wrapper">
                <div class="input-group input-group-sm dior-bs-search-group" style="width: 240px;">

                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Search records...">
                </div>

                <div class="va-actions-group">
                    <button class="va-icon-btn va-btn-primary btn" title="Add New Bill"><i class="fas fa-plus"></i></button>
                    <button class="va-icon-btn va-btn-success btn" title="Export to Excel"><i class="fas fa-file-arrow-down"></i></button>
                    <button class="va-icon-btn va-btn-info btn" title="Refresh" onclick="window.location.reload()"><i class="fas fa-rotate-right"></i></button>
                </div>
            </div>
        </div>

        <div class="va-table-wrapper">
            <table class="va-table table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">
                            <input type="checkbox" class="va-checkbox form-check-input">
                        </th>
                        <th>Admission ID <i class="fas fa-sort"></i></th>
                        <th>Patient Name <i class="fas fa-sort"></i></th>
                        <th>Doctor <i class="fas fa-sort"></i></th>
                        <th>Status <i class="fas fa-sort"></i></th>
                        <th>Date <i class="fas fa-sort"></i></th>
                        <th>Tax <i class="fas fa-sort"></i></th>
                        <th>Discount <i class="fas fa-sort"></i></th>
                        <th>Total <i class="fas fa-sort"></i></th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Row 1 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU89</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">SS</span>
                                <span class="va-user-name">Sarah Smith</span>
                            </div>
                        </td>
                        <td>DR. Megha Trivedi</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Feb 12, 2018</td>
                        <td>8%</td>
                        <td>5%</td>
                        <td>$142</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 2 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU99</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">VK</span>
                                <span class="va-user-name">Vishal Kumar</span>
                            </div>
                        </td>
                        <td>DR. John Deo</td>
                        <td><span class="va-badge va-badge-completed">Paid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Mar 22, 2018</td>
                        <td>10%</td>
                        <td>5%</td>
                        <td>$126</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 3 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU29</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">RM</span>
                                <span class="va-user-name">Rahul Malhotra</span>
                            </div>
                        </td>
                        <td>DR. Sarah Smith</td>
                        <td><span class="va-badge va-badge-completed">Paid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Feb 2, 2018</td>
                        <td>9%</td>
                        <td>5%</td>
                        <td>$115</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 4 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU36</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">PJ</span>
                                <span class="va-user-name">Priya Jain</span>
                            </div>
                        </td>
                        <td>DR. Stive</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Mar 19, 2018</td>
                        <td>9%</td>
                        <td>5%</td>
                        <td>$142</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 5 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU27</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">RP</span>
                                <span class="va-user-name">Radhika Patel</span>
                            </div>
                        </td>
                        <td>DR. Megha Trivedi</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> May 22, 2018</td>
                        <td>10%</td>
                        <td>5%</td>
                        <td>$126</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 6 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU12</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">SS</span>
                                <span class="va-user-name">Sarah Smith</span>
                            </div>
                        </td>
                        <td>DR. Megha Trivedi</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> May 7, 2018</td>
                        <td>8%</td>
                        <td>5%</td>
                        <td>$115</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 7 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU14</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">VK</span>
                                <span class="va-user-name">Vishal Kumar</span>
                            </div>
                        </td>
                        <td>DR. John Deo</td>
                        <td><span class="va-badge va-badge-completed">Paid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Feb 20, 2018</td>
                        <td>10%</td>
                        <td>5%</td>
                        <td>$126</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 8 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU09</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">RM</span>
                                <span class="va-user-name">Rahul Malhotra</span>
                            </div>
                        </td>
                        <td>DR. Sarah Smith</td>
                        <td><span class="va-badge va-badge-completed">Paid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Mar 21, 2018</td>
                        <td>9%</td>
                        <td>5%</td>
                        <td>$142</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 9 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU80</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">PJ</span>
                                <span class="va-user-name">Priya Jain</span>
                            </div>
                        </td>
                        <td>DR. Stive</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Feb 11, 2018</td>
                        <td>9%</td>
                        <td>5%</td>
                        <td>$142</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <!-- Row 10 -->
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" class="va-checkbox form-check-input">
                        </td>
                        <td>HGYU38</td>
                        <td>
                            <div class="va-user-profile">
                                <span class="va-user-avatar va-user-initials" aria-hidden="true">RP</span>
                                <span class="va-user-name">Radhika Patel</span>
                            </div>
                        </td>
                        <td>DR. Megha Trivedi</td>
                        <td><span class="va-badge va-badge-cancelled">Unpaid</span></td>
                        <td><i class="far fa-calendar va-icon-blue"></i> Mar 18, 2018</td>
                        <td>10%</td>
                        <td>5%</td>
                        <td>$126</td>
                        <td>
                            <div class="va-row-actions" style="justify-content: center;">
                                <button class="va-action-btn-sm va-btn-edit btn"><i class="fas fa-pen"></i></button>
                                <button class="va-action-btn-sm va-btn-delete btn"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


    </div>
</section>
<style id="dior-bill-list-patient-initials">

/* Patient name initials fallback for bill-list demo rows */
#tab-doc-accounts-bill-list .va-user-initials{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    flex:0 0 36px!important;
    width:36px!important;
    height:36px!important;
    min-width:36px!important;
    border-radius:50%!important;
    background:#1A528E!important;
    color:#fff!important;
    font-size:12px!important;
    font-weight:700!important;
    line-height:1!important;
    letter-spacing:.02em!important;
    text-transform:uppercase!important;
}
</style>
<style id="dior-accounts-mobile-bill-list">
/* Accounts mobile responsive layout, aligned with Patient Records */

@media(max-width:767.98px){
#dior-doctor-app #dior-doc-content #tab-doc-accounts-bill-list{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:14px 0 24px!important;overflow-x:clip;box-sizing:border-box!important}
#tab-doc-accounts-bill-list>.mb-4:first-child{align-items:flex-start!important}
#tab-doc-accounts-bill-list>.mb-4:first-child .va-breadcrumb-list{display:none!important}
#tab-doc-accounts-bill-list .dior-subtab-nav-bar{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important;padding:0!important;margin:0 0 16px!important}
#tab-doc-accounts-bill-list .dior-subtab-pill{width:100%!important;min-width:0!important;min-height:40px!important;margin:0!important;padding:8px 5px!important;font-size:12px!important;white-space:normal!important}
#tab-doc-accounts-bill-list .view-appointment-card{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow:hidden!important;box-sizing:border-box!important}
#tab-doc-accounts-bill-list .va-header-container{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:12px!important;padding:12px!important}
#tab-doc-accounts-bill-list .va-actions-wrapper,#tab-doc-accounts-bill-list .dior-bs-search-group{width:100%!important;max-width:100%!important}
#tab-doc-accounts-bill-list .va-actions-wrapper{display:flex!important;flex-wrap:wrap!important}
#tab-doc-accounts-bill-list .va-actions-group{width:100%!important;display:flex!important;gap:8px!important}
#tab-doc-accounts-bill-list .va-actions-group .va-icon-btn{flex:1!important}
#tab-doc-accounts-bill-list .va-table-wrapper{width:100%!important;max-width:100%!important;overflow:hidden!important;padding:0 10px 12px!important;box-sizing:border-box!important}
#tab-doc-accounts-bill-list .va-table{display:block!important;width:100%!important;min-width:0!important;table-layout:fixed!important}
#tab-doc-accounts-bill-list .va-table thead{display:none!important}
#tab-doc-accounts-bill-list .va-table tbody{display:grid!important;gap:10px!important;width:100%!important}
#tab-doc-accounts-bill-list .va-table tbody tr{position:relative!important;display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px 12px!important;width:100%!important;min-width:0!important;margin:0!important;padding:14px 12px 12px 42px!important;border:1px solid #E2E8F0!important;border-radius:14px!important;background:#fff!important;box-shadow:0 1px 3px rgba(15,23,42,.06)!important;box-sizing:border-box!important}
#tab-doc-accounts-bill-list .va-table tbody td{display:flex!important;flex-direction:column!important;align-items:flex-start!important;justify-content:flex-start!important;gap:3px!important;min-width:0!important;padding:0!important;border:0!important;background:transparent!important;white-space:normal!important;overflow-wrap:anywhere!important;font-size:12px!important}
#tab-doc-accounts-bill-list .va-table tbody td:before{content:"";font-size:9px!important;font-weight:700!important;letter-spacing:.04em!important;text-transform:uppercase!important;color:#8493A7!important}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(1){position:absolute!important;left:12px!important;top:15px!important;display:block!important}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(2):before{content:"Admission ID"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(3):before{content:"Patient"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(4):before{content:"Doctor"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(5):before{content:"Status"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(6):before{content:"Date"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(7):before{content:"Tax"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(8):before{content:"Discount"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(9):before{content:"Total"}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(3){font-weight:700!important;color:#12345A!important}
#tab-doc-accounts-bill-list .va-table tbody td:nth-child(10){grid-column:1/-1;align-items:flex-end!important;padding-top:8px!important;border-top:1px solid #EDF1F6!important}
#tab-doc-accounts-bill-list .va-row-actions{display:flex!important;gap:7px!important}
}

</style>
