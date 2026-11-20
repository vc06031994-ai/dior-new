(function () {
    'use strict';

    const sourceId = 'dior-hipaatizer-form-source-v17';
    let source = null;
    let originalParent = null;
    let originalNextSibling = null;

    function openPopup() {
        if (!window.Swal) {
            return;
        }

        source = document.getElementById(sourceId);
        if (!source || !source.firstElementChild) {
            window.Swal.fire({
                icon: 'error',
                title: 'Form unavailable',
                text: 'The HIPAAtizer form could not be loaded. Please refresh and try again.'
            });
            return;
        }

        originalParent = source.parentNode;
        originalNextSibling = source.nextSibling;

        window.Swal.fire({
            title: 'Book An Appointment',
            html: '<div id="dior-hipaatizer-popup-slot-v17"></div>',
            width: 'min(1120px, 96vw)',
            showCloseButton: false,
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: false,
            heightAuto: false,
            customClass: {
                popup: 'dior-hipaatizer-popup-v17',
                title: 'dior-hipaatizer-popup-title-v17',
                htmlContainer: 'dior-hipaatizer-popup-content-v17'
            },
            didOpen: function () {
                const slot = document.getElementById('dior-hipaatizer-popup-slot-v17');
                if (slot && source) {
                    source.hidden = false;
                    slot.appendChild(source);
                }
            },
            willClose: function () {
                if (source && originalParent) {
                    source.hidden = true;
                    if (originalNextSibling && originalNextSibling.parentNode === originalParent) {
                        originalParent.insertBefore(source, originalNextSibling);
                    } else {
                        originalParent.appendChild(source);
                    }
                }
            }
        });
    }

    // The popup is opened only by a genuine HIPAAtizer completion, handled in
    // dior-docbooker-modal.js (the single completion handler). The previous
    // capture-phase click listener on any button, link or submit input is gone:
    // it could never have worked as intended because the intake form lives in a
    // cross-origin iframe, so its Next/Submit buttons never reach this document.
    //
    // It is still exposed because the post-registration flow opens it directly:
    // see dior-dashboard.js (diorOpenHipaaPopup on the ?intake_required=1 path).
    window.diorOpenHipaatizerFormPopup = openPopup;
})();
