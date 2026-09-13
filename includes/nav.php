<?php
declare(strict_types=1);

/**
 * Shared primary navigation for the dynamic (PHP) pages.
 *
 * Signed out: the same six public links as the static .html pages,
 *             then Log in / Register.
 * Signed in:  a trimmed set of public links plus that user's role
 *             destinations, a compact initials avatar and Log out
 *             (fewer public links so the role links still fit one row).
 *
 * Showing or hiding a link is a convenience only - every protected
 * page independently enforces authorisation with require_role().
 */

$navUser = function_exists('current_user') ? current_user() : null;
$navRole = $navUser['role'] ?? null;

/* Presentation-only "which page am I on" hint for aria-current.
   $_SERVER['SCRIPT_NAME'] is the executing script, not a routing input. */
$navHere = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$navDashHere = in_array($navHere, ['patient.php', 'doctor.php', 'admin.php'], true);
$aria = static fn (bool $on): string => $on ? ' aria-current="page"' : '';
?>
<nav class="main-nav" id="main-navigation" aria-label="Main navigation">
    <ul>
        <li><a href="<?= e(BASE_URL) ?>/index.html">Home</a></li>

        <?php if ($navUser === null): ?>
            <li><a href="<?= e(BASE_URL) ?>/about.html">About</a></li>
            <li><a href="<?= e(BASE_URL) ?>/services.html">Services</a></li>
            <li><a href="<?= e(BASE_URL) ?>/testimonials.html">Patient Stories</a></li>
            <li><a href="<?= e(BASE_URL) ?>/gallery.html">Gallery</a></li>
            <li><a href="<?= e(BASE_URL) ?>/contact.html">Contact</a></li>
            <li><a class="nav-login" href="<?= e(BASE_URL) ?>/auth/login.php"<?= $aria($navHere === 'login.php') ?>>Log in</a></li>
            <li><a class="nav-register" href="<?= e(BASE_URL) ?>/auth/register.php"<?= $aria($navHere === 'register.php') ?>>Register</a></li>

        <?php else: ?>
            <li><a href="<?= e(BASE_URL) ?>/services.html">Services</a></li>
            <li><a href="<?= e(BASE_URL) ?>/contact.html">Contact</a></li>

            <li><a href="<?= e(dashboard_url_for($navRole)) ?>"<?= $aria($navDashHere) ?>>Dashboard</a></li>

            <?php if ($navRole === 'patient'): ?>
                <li><a href="<?= e(BASE_URL) ?>/patient/appointments.php"<?= $aria($navHere === 'appointments.php') ?>>My appointments</a></li>
                <li><a href="<?= e(BASE_URL) ?>/patient/book.php"<?= $aria($navHere === 'book.php') ?>>Book appointment</a></li>

            <?php elseif ($navRole === 'doctor'): ?>
                <li><a href="<?= e(BASE_URL) ?>/doctor/appointments.php"<?= $aria($navHere === 'appointments.php') ?>>My appointments</a></li>

            <?php elseif ($navRole === 'admin'): ?>
                <li><a href="<?= e(BASE_URL) ?>/admin/appointments.php"<?= $aria($navHere === 'appointments.php') ?>>Appointments</a></li>
                <li><a href="<?= e(BASE_URL) ?>/admin/doctors.php"<?= $aria(in_array($navHere, ['doctors.php', 'doctor-form.php'], true)) ?>>Doctors</a></li>
                <li><a href="<?= e(BASE_URL) ?>/admin/departments.php"<?= $aria(in_array($navHere, ['departments.php', 'department-form.php'], true)) ?>>Departments</a></li>
            <?php endif; ?>

            <li class="nav-identity">
                <span class="nav-avatar" title="<?= e($navUser['full_name']) ?>" aria-hidden="true"><?= e(user_initials((string) $navUser['full_name'])) ?></span>
                <span class="nav-identity-name">Signed in as <?= e($navUser['full_name']) ?></span>
            </li>
            <li><a href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a></li>
        <?php endif; ?>
    </ul>
</nav>
