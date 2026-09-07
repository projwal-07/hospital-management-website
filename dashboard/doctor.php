<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
auth_boot();
require_role('doctor');           // server-side authorisation, from the database role

header('Cache-Control: no-store');

$user = current_user();

$page_title = 'Doctor dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Doctor area</p>
        <h1>Welcome, <?= e($user['full_name']) ?></h1>
        <p>You are signed in as <strong><?= e($user['role']) ?></strong> (<?= e($user['email']) ?>).</p>
    </section>
</section>

<section class="content-section">
    <section class="container">
        <h2>Your dashboard</h2>
        <p>The appointments assigned to you, and status updates for them, will
           appear here in the next phase.</p>
        <p><a class="button button-primary" href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a></p>
    </section>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
