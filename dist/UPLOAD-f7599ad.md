# Dior Medical — upload manifest (commit f7599ad)

Upload each file to the SAME path inside the plugin folder
`wp-content/plugins/dior-medical-dashboard/`. Overwrite existing files.

## Files

| # | Path | SHA256 |
|---|------|--------|
| 1 | assets/js/dior-docbooker-modal.js | 6fa7425ab53b15c9090729a7332928ff794be1ad0023682903145fcd5bb970cf |
| 2 | dior-medical-dashboard.php | b9a3a9118b3c05e8a43762c1a9e44166208fbb88db43fe70bbf2f5790d7a403e |
| 3 | assets/js/dior-dashboard.js | 91be6aee4a75e6b4a1d7261ed0c0c53158b83391f71454e5ba29bb2f0c4e74ed |
| 4 | assets/js/dior-hipaatizer-popup.js | 1e0012c034685d60a299c3c13e1eae4dcfb670d94d0b754525baad997f87af64 |
| 5 | assets/js/dior-patient-dashboard.js | 90f02b515d7c0aa4564d3d226d0330c4f1ad9e652616af5d60cc401b13f4af99 |
| 6 | assets/css/dior-custom.css | 5917ef96fa27083918858e35bc42cd5d04c37a0fc58d6f1b282a3777214d7754 |
| 7 | templates/patient-tabs/documents.php | 6f2a641c90b6c6d5d3cb84708332b662c78512b1094d915ba3d844881a1a04f1 |
| 8 | templates/patient-tabs/appointments.php | f6f256c55c409487d914a6f1cfd2852ef0720e067cca5db7af21819cb5904264 |

## After upload

1. Purge WP Rocket cache
2. Purge Cloudflare cache (if enabled)
3. Hard refresh: Ctrl+Shift+R (Cmd+Shift+R on Mac)

## Verify the fix is live

Open the patient dashboard, press F12, Console, paste:

    typeof window.diorHipaatizerCompletion

Must print "function". If it prints "undefined", the modal JS is still old.
