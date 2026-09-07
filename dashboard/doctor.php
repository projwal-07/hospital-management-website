<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
auth_boot();
require_role('doctor');            // server-side authorisation, from the database role

header('Cache-Control: no-store');

$user     = current_user();
$doctorId = doctor_id_for_user($user['id']);   // derived from THIS user's id only

if ($doctorId !== null) {
    $counts   = doctor_status_counts($doctorId);
    $today    = doctor_today($doctorId);
    $upcoming = doctor_upcoming($doctorId, 10);
    $recent   = doctor_recent_changes($doctorId, 5);
    $total    = array_sum($counts);
}

$page_title = 'Doctor dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Doctor area</p>
        <h1>Your dashboard</h1>
        <p>Signed in as <strong><?= e($user['full_name']) ?></strong>
           (<?= e($user['email']) ?>) &middot; role: doctor</p>
    </section>
</section>

<div class="container dash">
<?php if ($doctorId === null): ?>
    <section class="dash-section" aria-labelledby="np-heading">
        <h2 id="np-heading">Profile not set up</h2>
        <p class="dash-empty">Your doctor profile has not been set up yet.
           Please contact an administrator.</p>
    </section>
<?php else: ?>

    <div class="dash-actions">
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/doctor/appointments.php">Manage my appointments</a>
    </div>

    <section class="dash-section" aria-labelledby="ov-heading">
        <h2 id="ov-heading">Overview</h2>
        <dl class="dash-stat-grid">
            <div class="dash-stat"><dt>Total appointments</dt><dd><?= (int) $total ?></dd></div>
            <div class="dash-stat"><dt>Pending</dt><dd><?= (int) $counts['pending'] ?></dd></div>
            <div class="dash-stat"><dt>Confirmed</dt><dd><?= (int) $counts['confirmed'] ?></dd></div>
            <div class="dash-stat"><dt>Completed</dt><dd><?= (int) $counts['completed'] ?></dd></div>
            <div class="dash-stat"><dt>Cancelled</dt><dd><?= (int) $counts['cancelled'] ?></dd></div>
        </dl>
    </section>

    <section class="dash-section" aria-labelledby="td-heading">
        <h2 id="td-heading">Today's appointments</h2>
        <?php if (!$today): ?>
            <p class="dash-empty">You have no appointments scheduled for today.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Your appointments for today (<?= count($today) ?>)</caption>
                    <thead>
                        <tr>
                            <th scope="col">Time</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today as $a): ?>
                            <tr>
                                <th scope="row"><?= e(fmt_time($a['appointment_time'])) ?></th>
                                <td><?= e($a['patient_name']) ?></td>
                                <td><?= e($a['department_name']) ?></td>
                                <td><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="dash-section" aria-labelledby="up-heading">
        <h2 id="up-heading">Upcoming appointments</h2>
        <?php if (!$upcoming): ?>
            <p class="dash-empty">You have no upcoming appointments.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Your next <?= count($upcoming) ?> active appointment<?= count($upcoming) === 1 ? '' : 's' ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $a): ?>
                            <tr>
                                <th scope="row"><?= e(fmt_date($a['appointment_date'])) ?></th>
                                <td><?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td><?= e($a['patient_name']) ?></td>
                                <td><?= e($a['department_name']) ?></td>
                                <td><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="dash-section" aria-labelledby="rc-heading">
        <h2 id="rc-heading">Recent appointment changes</h2>
        <p class="dash-note">The most recently created or updated appointments assigned to you.</p>
        <?php if (!$recent): ?>
            <p class="dash-empty">No appointments have been recorded for you yet.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Your <?= count($recent) ?> most recently changed appointment<?= count($recent) === 1 ? '' : 's' ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Updated</th>
                            <th scope="col">Date</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $a): ?>
                            <tr>
                                <th scope="row"><?= e(fmt_datetime($a['updated_at'])) ?></th>
                                <td><?= e(fmt_date($a['appointment_date'])) ?> <?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td><?= e($a['patient_name']) ?></td>
                                <td><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

    <p><a class="button button-primary" href="<?= e(BASE_URL) ?>/auth/logout.php">Log out</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
