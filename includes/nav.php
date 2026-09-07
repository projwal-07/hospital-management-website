<?php
declare(strict_types=1);

/**
 * Shared primary navigation for dynamic pages.
 *
 * Shows Log in / Register when signed out, and Dashboard / Log out when
 * signed in. This visibility is a convenience only - every protected
 * page independently enforces authorisation on the server.
 */

$navUser = function_exists('current_user') ? current_user() : null;
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
            <li><a href="<?= e(dashboard_url_for($navUser['role'])) ?>">Dashboard</a></li>
            <li><a href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a></li>
        <?php endif; ?>
    </ul>
</nav>
