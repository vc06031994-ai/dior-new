<?php
/**
 * Template Name: Dior Schedule Appointment
 *
 * Full-page Schedule Appointment screen.
 *
 * This replaces the old [dior_book_visit_page] shortcode. A page becomes the
 * schedule page by selecting this template (Page Attributes -> Template), so
 * the page owns its slug and its content is left completely untouched — the
 * plugin never inserts, rewrites or recreates a page for it.
 *
 * It behaves like the dashboard canvas: the theme header/footer are skipped and
 * the page renders inside the portal shell, which is what the booking UI was
 * designed for.
 */

if (!defined('ABSPATH')) {
    exit;
}

$dior_schedule_user_id = get_current_user_id();

// Signed-in only, like the rest of the portal.
if (!$dior_schedule_user_id) {
    wp_safe_redirect(function_exists('dior_portal_login_url') ? dior_portal_login_url() : home_url('/dior-login/'));
    exit;
}

// Tell the rest of the plugin this is a portal screen so the dashboard assets
// that the booking form depends on are enqueued.
$GLOBALS['dior_patient_portal_context'] = true;

if (class_exists('Dior_Medical_Dashboard')) {
    Dior_Medical_Dashboard::enqueue_schedule_appointment_assets();
}

get_header();

?>
<div class="dior-patient-portal-wrap dior-schedule-page-wrap">
    <div id="dior-patient-portal-app" class="dior-schedule-page-app">
        <?php include DIOR_PORTAL_PATH . 'templates/appointment-page.php'; ?>
    </div>
</div>
<?php

get_footer();
