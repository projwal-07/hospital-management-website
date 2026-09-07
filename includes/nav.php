<?php
declare(strict_types=1);

/**
 * Shared primary navigation for dynamic pages.
 *
 * Signed out: Log in / Register.
 * Signed in: the destinations for that user's role.
 *
 * Showing or hiding a link is a convenience only - every protected page
 * independently enforces authorisation on the server with require_role().
 */

$navUser = function_exists('current_user') ? current_user() : null;
$navRole = $navUser['role'] ?? null;
?>
<nav class="main-nav" id="main-navigation" aria-label="Main navigation">
    <ul>
        <li><a href="<?= e(BASE_URL) ?>/index.html">Home</a></li>
        <li><a href="<?= e(BASE_URL) ?>/services.html">Services</a></li>
        <li><a href="<?= e(BASE_URL) ?>/contact.html">Contact</a></li>

        <?php if ($navUser === null): ?>
            <li><a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a></li>
            <li><a href="<?= e(BASE_URL) ?>/auth/register.php">Register</a></li>

        <?php else: ?>
            <li><a href="<?= e(dashboard_url_for($navRole)) ?>">Dashboard</a></li>

            <?php if ($navRole === 'patient'): ?>
                <li><a href="<?= e(BASE_URL) ?>/patient/appointments.php">My appointments</a></li>
                <li><a href="<?= e(BASE_URL) ?>/patient/book.php">Book appointment</a></li>

            <?php elseif ($navRole === 'doctor'): ?>
                <li><a href="<?= e(BASE_URL) ?>/doctor/appointments.php">My appointments</a></li>

            <?php elseif ($navRole === 'admin'): ?>
                <li><a href="<?= e(BASE_URL) ?>/admin/appointments.php">Appointments</a></li>
                <li><a href="<?= e(BASE_URL) ?>/admin/doctors.php">Doctors</a></li>
                <li><a href="<?= e(BASE_URL) ?>/admin/departments.php">Departments</a></li>
            <?php endif; ?>

            <li><a href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a></li>
        <?php endif; ?>
    </ul>
</nav>
