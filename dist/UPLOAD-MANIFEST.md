# Dior Medical - upload manifest (commit after merging main)

Upload each file to the SAME path inside
`wp-content/plugins/dior-medical-dashboard/`. Overwrite existing files.

The whole plugin is also available as `dior-medical-dashboard-full.zip`
(120 files) - prefer that unless you want a per-file FTP upload.

## Renamed in main - delete the old names on the server

These two files were renamed upstream. The old names must be REMOVED from the
server, or a stale copy will linger next to the new one:

| Old name (delete) | New name (upload) |
|---|---|
| assets/js/dior-docbooker-modal.js | assets/js/dior-docbooker-booking.js |
| templates/modal-docbooker-booking.php | templates/docbooker-booking-form.php |

## Files changed in this branch

| Path |
|------|
| assets/js/dior-docbooker-booking.js |
| assets/js/dior-dashboard.js |
| assets/js/dior-hipaatizer-popup.js |
| assets/js/dior-patient-dashboard.js |
| assets/css/dior-custom.css |
| dior-medical-dashboard.php |
| dior-doctor-dashboard.php |
| templates/patient-tabs/appointments.php |
| templates/patient-tabs/documents.php |
| templates/patient-tabs/questionnaire.php |
| templates/patient-tabs/telemedicine.php |
| templates/docbooker-booking-form.php |
| templates/doctor-tabs/appointments.php |
| templates/doctor-tabs/view-appointment.php |

## After upload

1. Purge WP Rocket cache
2. Purge Cloudflare cache (if enabled)
3. Hard refresh: Ctrl+Shift+R (Cmd+Shift+R on Mac)

## Verify the fix is live

Patient dashboard -> F12 -> Console:

    typeof window.diorHipaatizerCompletion

Must print `"function"`. If it prints `"undefined"`, the old booking JS is
still cached - the rename to dior-docbooker-booking.js means the old file may
still be enqueued by a stale copy of dior-medical-dashboard.php.
