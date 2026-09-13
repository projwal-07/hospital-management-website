<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
auth_boot();

/**
 * Logout.
 *
 * The actual sign-out is POST-only and CSRF-protected, so it cannot be
 * triggered by a stray GET request (for example an <img> tag). A GET
 * request just shows a confirmation page with a POST button.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    logout_user();

    // Start a fresh, empty session only to carry the confirmation flash.
    session_start();
    session_regenerate_id(true);
    flash_set('success', 'You have been logged out.');

    redirect(BASE_URL . '/auth/login.php');
}

$page_title = 'Log out';
require __DIR__ . '/../includes/header.php';

$currentUser = current_user();
?>
<section class="auth-shell">
    <div class="container">
        <div class="auth-card auth-card--narrow">
            <div class="auth-main">
                <p class="kicker">Account access</p>
                <h1>Log out</h1>
                <?php if ($currentUser !== null): ?>
                    <p class="auth-lead">You are signed in as <strong><?= e($currentUser['full_name']) ?></strong>. Do you want to log out?</p>
                    <form action="<?= e(BASE_URL) ?>/auth/logout.php" method="post">
                        <?= csrf_field() ?>
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Log out</button>
                            <a class="button button-light" href="<?= e(dashboard_url_for($currentUser['role'])) ?>">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="auth-lead">You are not logged in.</p>
                    <p><a class="button button-primary" href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
