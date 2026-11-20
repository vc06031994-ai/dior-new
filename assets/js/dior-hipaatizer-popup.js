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

    // This popup remains available to the post-registration intake flow.
    // Booking completion reveals the DocBooker form in the Book Appointment panel.
    window.diorOpenHipaatizerFormPopup = openPopup;
})();
