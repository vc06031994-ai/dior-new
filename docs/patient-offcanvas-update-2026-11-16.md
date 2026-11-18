# Patient Dashboard — Mobile Navigation (Off-Canvas) Update

**Date:** 16 November 2026
**Branch:** `arena/4c4c1ca1-dior-new`
**Commits:** `be834c3`, `9157be3`, `4436ce9`

---

## Summary

The patient dashboard's mobile navigation panel (the slide-out drawer behind the
hamburger button) has been rebuilt to match the Doctor dashboard exactly. The two
patient and doctor panels were visibly different components; they are now the
same component, so switching between the two dashboards on a phone no longer
feels like changing products.

The work is finished, verified on real rendered pages, and pushed. Nothing else
on the dashboard was affected.

---

## What was wrong

The patient drawer had drifted from the doctor drawer it is meant to mirror:

| Property | Patient drawer (before) | Doctor drawer |
|---|---|---|
| Panel width | 304px | 280px |
| Nav row height | 46px, square corners | 32px, rounded |
| Icon box | 18px | 20px |
| Sub-menu text | 15px | 13px |
| Brand underline | Hairline rule | None |
| "PATIENT MENU" heading | Missing | Present |
| Close (×) control | Missing | Present |

Two underlying defects were found while fixing this:

1. An Elementor link style was forcing `font-size: inherit` onto the Appointments
   sub-items, so they rendered at body-text size instead of 13px.
2. A late `.dior-side-close { display: flex }` rule combined with a generic
   `.btn` style made the close button appear on the **desktop** sidebar, where it
   does not belong.

---

## What changed

**1. The drawer itself**

- Panel is now **280px** on the navy brand colour, with the drawer shadow.
- Rows are **32px** tall with a **10px** radius and **15px** icons inside a
  **20px** box — the doctor's exact metrics.
- A **"PATIENT MENU"** group heading appears above the list, matching
  "PROVIDER MENU" on the doctor side.
- Sub-items sit inset at **42px** and read at **13px**.
- A **34px round ghost close button** appears only while the drawer is open. It
  is hidden on desktop.
- Desktop layout is completely unchanged.

**2. The Appointments dropdown**

The patient's Appointments row was a separate nested panel with `›` chevrons.
It now uses the doctor's own markup and styling: a flat indented list at the
same 12.5px text, an 8px radius, a `+ / −` toggle, and the same white hover row.

Two visual faults were corrected afterwards:

- The row kept a translucent white **background fill** because the tab logic was
  marking it "active". The doctor's equivalent row never gets that fill, so the
  fill has been removed — the row is now transparent open or closed.
- **Hover** on that row is now identical to every other navigation row: white
  background, black text, and the same 3px slide.

**3. The collapsed (icon-only) rail**

- The `+` affordance was still drawing next to the calendar icon — it is now
  hidden, so each row shows a single glyph as on the doctor side.
- Rows were stretching to **223px** inside a 72px rail — they are now **56px**
  in an 8px gutter.
- Hover flashed a solid white block — it now uses the doctor's translucent pill.

---

## Verification

Every value below was measured on the rendered page, not estimated.

**Drawer at 390px and 1024px** — identical at both widths:

| Item | Result |
|---|---|
| Panel width / colour | 280px / `#00346C` |
| Nav row | 32px tall, `7px 16px`, 8px radius, 13px |
| Sub-item | 42px tall, 25px indent, 13px |
| Close button | 34×34, visible only when open, exactly one in the DOM |

**Appointments row vs. the doctor's (1440px)** — exact match:

| | Doctor | Patient |
|---|---|---|
| Parent row | 235×35, `9px 13px`, r8, 12.5px | identical |
| Sub-menu | `2px 0 4px 25px` | identical |
| Sub-item | 210×36, `9px 13px`, r8 | identical |
| `+ / −` icon | 20px / 13px at x214 | identical |
| Hover | `#FFF` / `#000` / 3px | identical |
| Collapsed flyout | 222px at `left:64px`, navy | identical |

**Behaviour tests** — all pass with **zero page errors**:

- Drawer opens (panel at x:0, backdrop active, page scroll locked) and closes.
- Tapping a nav item closes the drawer automatically.
- The Appointments toggle opens and closes correctly with a real click, and the
  icon swaps between `+` and `−`.
- A duplicate close button was found and removed (the template ships one and the
  script was injecting a second, stacking two × marks).
- Notification modals and the reschedule dialog are unaffected.

Screenshots: `docs/screenshots/patient-drawer-390.png`,
`doctor-drawer-390.png`, `drawer-parity-compare.png`,
`dropdown-parity-desktop.png`, `dropdown-parity-mobile.png`,
`dropdown-no-fill-desktop.png`, `dropdown-collapsed-rail.png`.

---

## Deployment

Upload these four files (no new files, no database changes, no PHP settings):

- `assets/css/dior-custom.css`
- `assets/js/dior-patient-dashboard.js`
- `assets/js/dior-patient-live.js`
- `assets/js/dior-patient-navigation.js`

Then clear any page/asset cache (Elementor and any CDN) so the new CSS is served.
The drawer is CSS-only, so no other templates need to be re-uploaded for this
change.

---

## Notes

- The Doctor dashboard was **not** modified — it is the reference, and the
  patient side was brought to it. If the doctor drawer changes in future, the
  patient drawer should be re-checked against it.
- The collapsed rail is a desktop-only state. The mobile drawer is a separate
  layout and was verified separately at 390px and 1024px.
