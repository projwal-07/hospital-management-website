<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
auth_boot();
require_role('patient');           // server-side authorisation, from the database role

header('Cache-Control: no-store');

$user = current_user();

// All data below is scoped to THIS patient's database user id.
$counts   = patient_status_counts($user['id']);
$upcoming = patient_upcoming($user['id'], 5);
$history  = patient_history($user['id'], 5);
$total    = array_sum($counts);

$page_title = 'Patient dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Patient area</p>
        <h1>Welcome, <?= e($user['full_name']) ?></h1>
        <p>Manage your appointments and healthcare activity.</p>
    </section>
</section>

<div class="container dash">

    <div class="dash-quick">
        <a class="dash-action-card dash-action-card--primary" href="<?= e(BASE_URL) ?>/patient/book.php">
            <span class="dash-action-icon" aria-hidden="true"><?= svg_icon('calendar-plus') ?></span>Book an appointment
        </a>
        <a class="dash-action-card" href="<?= e(BASE_URL) ?>/patient/appointments.php">
            <span class="dash-action-icon" aria-hidden="true"><?= svg_icon('list') ?></span>View all my appointments
        </a>
    </div>

    <section class="dash-section" aria-labelledby="ov-heading">
        <h2 id="ov-heading"><?= svg_icon('activity') ?>Overview</h2>
        <dl class="dash-stat-grid">
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('calendar') ?></span><dt>Total appointments</dt><dd><?= (int) $total ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('clock') ?></span><dt>Pending</dt><dd><?= (int) $counts['pending'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('calendar-check') ?></span><dt>Confirmed</dt><dd><?= (int) $counts['confirmed'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('check-circle') ?></span><dt>Completed</dt><dd><?= (int) $counts['completed'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('x-circle') ?></span><dt>Cancelled</dt><dd><?= (int) $counts['cancelled'] ?></dd></div>
        </dl>
    </section>

    <section class="dash-section" aria-labelledby="up-heading">
        <h2 id="up-heading"><?= svg_icon('calendar-clock') ?>Upcoming appointments</h2>
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
                            <th scope="col">Doctor</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $a): ?>
                            <tr>
                                <th scope="row" data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></th>
                                <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td data-label="Doctor"><?= e($a['doctor_name']) ?></td>
                                <td data-label="Department"><?= e($a['department_name']) ?></td>
                                <td data-label="Status"><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="dash-section" aria-labelledby="hi-heading">
        <h2 id="hi-heading"><?= svg_icon('history') ?>Appointment history</h2>
        <?php if (!$history): ?>
            <p class="dash-empty">No past or closed appointments yet.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Your most recent <?= count($history) ?> past or closed appointment<?= count($history) === 1 ? '' : 's' ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $a): ?>
                            <tr>
                                <th scope="row" data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></th>
                                <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td data-label="Doctor"><?= e($a['doctor_name']) ?></td>
                                <td data-label="Department"><?= e($a['department_name']) ?></td>
                                <td data-label="Status"><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
