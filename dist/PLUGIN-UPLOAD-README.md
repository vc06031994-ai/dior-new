# Dior Medical Dashboard — full plugin zip

File: `dior-medical-dashboard-full.zip` (1.4 MB, 120 files)
Built from commit `55e4f22` on branch `arena/4c4c1ca1-dior-new`.

## Contents

The zip contains ONE top-level folder:

    dior-medical-dashboard/
        dior-medical-dashboard.php
        dior-doctor-dashboard.php
        assets/
        includes/
        rest_api/
        templates/

That folder name already matches the live plugin folder, so the paths line up
without any renaming.

## How to upload

### Option A — FTP (recommended: per-file, keeps the plugin live)

Upload the contents of `dior-medical-dashboard/` into
`wp-content/plugins/dior-medical-dashboard/`, overwriting existing files.
Deleting and replacing the whole folder on a live site can leave the plugin
half-missing for a few seconds, so per-file overwrite is safer.

### Option B — WordPress admin (fastest for a first attempt)

1. Plugins -> Add New -> Upload Plugin
2. Choose the zip
3. WordPress will say "Destination folder already exists"
4. Choose **Replace current with uploaded**

Everything writable by the web server must stay writable afterwards.

## After upload

1. Purge WP Rocket cache
2. Purge Cloudflare cache (if enabled)
3. Hard refresh: Ctrl+Shift+R (Cmd+Shift+R on Mac)

## Verify the fix is live

Patient dashboard -> F12 -> Console:

    typeof window.diorHipaatizerCompletion

Must print `"function"`.
If it prints `"undefined"`, the browser is still running the old
dior-docbooker-modal.js and the cache purge did not take.

## What is NOT in the zip (on purpose)

- `docs/` — internal screenshots and analysis notes, not part of the plugin
- `dist/` — the upload bundles themselves
- `.harness*` — local test harness pages
- `.git/` — repository history

## What this build contains

- Patient Documents: category filter bar and its code removed; one mixed list
- Patient Book Appointment: Telehealth Appointment Scheduler card removed
- Patient Documents: filter pills made functional (superseded by the removal above)
- HIPAAtizer intake: booking modal opens only on a verified final submission,
  never on an intermediate step, duplicate renderer message or foreign origin
- Cached-asset fix: dior-hipaatizer-popup.js now versioned by filemtime
