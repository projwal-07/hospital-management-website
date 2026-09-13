<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard.php';
auth_boot();
require_role('admin');             // server-side authorisation, from the database role

header('Cache-Control: no-store');

$user = current_user();

$roleCounts    = admin_user_role_counts();
$deptCount     = admin_department_count();
$doctorCounts  = admin_doctor_counts();
$statusCounts  = admin_appointment_status_counts();
$todayCount    = admin_today_appointment_count();
$upcoming      = admin_upcoming(10);
$registrations = admin_latest_patient_registrations(5);
$changes       = admin_recent_appointment_changes(5);
$appointmentsTotal = array_sum($statusCounts);

$page_title = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1>Welcome, <?= e($user['full_name']) ?></h1>
        <p>Manage appointments, doctors and departments across the hospital.</p>
    </section>
</section>

<div class="container dash">

    <div class="dash-quick">
        <a class="dash-action-card dash-action-card--primary" href="<?= e(BASE_URL) ?>/admin/appointments.php">
            <span class="dash-action-icon" aria-hidden="true"><?= svg_icon('calendar') ?></span>Manage appointments
        </a>
        <a class="dash-action-card" href="<?= e(BASE_URL) ?>/admin/doctors.php">
            <span class="dash-action-icon" aria-hidden="true"><?= svg_icon('stethoscope') ?></span>Manage doctors
        </a>
        <a class="dash-action-card" href="<?= e(BASE_URL) ?>/admin/departments.php">
            <span class="dash-action-icon" aria-hidden="true"><?= svg_icon('building') ?></span>Manage departments
        </a>
    </div>

    <section class="dash-section" aria-labelledby="people-heading">
        <h2 id="people-heading"><?= svg_icon('users') ?>People and departments</h2>
        <dl class="dash-stat-grid">
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('users') ?></span><dt>Patients</dt><dd><?= (int) $roleCounts['patient'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('stethoscope') ?></span><dt>Doctors</dt><dd><?= (int) $roleCounts['doctor'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('shield') ?></span><dt>Administrators</dt><dd><?= (int) $roleCounts['admin'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('building') ?></span><dt>Departments</dt><dd><?= (int) $deptCount ?></dd></div>
            <div class="dash-stat">
                <span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('check-circle') ?></span>
                <dt>Active doctors</dt>
                <dd><?= (int) $doctorCounts['active'] ?> <small>of <?= (int) $doctorCounts['total'] ?></small></dd>
            </div>
        </dl>
    </section>

    <section class="dash-section" aria-labelledby="appt-heading">
        <h2 id="appt-heading"><?= svg_icon('calendar') ?>Appointments</h2>
        <dl class="dash-stat-grid">
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('calendar') ?></span><dt>Total</dt><dd><?= (int) $appointmentsTotal ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('clock') ?></span><dt>Pending</dt><dd><?= (int) $statusCounts['pending'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('calendar-check') ?></span><dt>Confirmed</dt><dd><?= (int) $statusCounts['confirmed'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('check-circle') ?></span><dt>Completed</dt><dd><?= (int) $statusCounts['completed'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('x-circle') ?></span><dt>Cancelled</dt><dd><?= (int) $statusCounts['cancelled'] ?></dd></div>
            <div class="dash-stat"><span class="dash-stat-glyph" aria-hidden="true"><?= svg_icon('calendar-clock') ?></span><dt>Scheduled today</dt><dd><?= (int) $todayCount ?></dd></div>
        </dl>
    </section>

    <section class="dash-section" aria-labelledby="up-heading">
        <h2 id="up-heading"><?= svg_icon('calendar-clock') ?>Upcoming appointments</h2>
        <?php if (!$upcoming): ?>
            <p class="dash-empty">There are no upcoming active appointments.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Next <?= count($upcoming) ?> active appointment<?= count($upcoming) === 1 ? '' : 's' ?> across the hospital</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Patient</th>
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
                                <td data-label="Patient"><?= e($a['patient_name']) ?></td>
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

    <div class="dash-subgrid dash-subgrid--2">
        <section class="dash-section" aria-labelledby="reg-heading">
            <h2 id="reg-heading"><?= svg_icon('user-plus') ?>Latest patient registrations</h2>
            <?php if (!$registrations): ?>
                <p class="dash-empty">No patients have registered yet.</p>
            <?php else: ?>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <caption>The <?= count($registrations) ?> most recently registered patient<?= count($registrations) === 1 ? '' : 's' ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Registered</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $r): ?>
                                <tr>
                                    <th scope="row" data-label="Registered"><?= e(fmt_datetime($r['created_at'])) ?></th>
                                    <td data-label="Name"><?= e($r['full_name']) ?></td>
                                    <td data-label="Email"><?= e($r['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dash-section" aria-labelledby="chg-heading">
            <h2 id="chg-heading"><?= svg_icon('history') ?>Recent appointment changes</h2>
            <p class="dash-note">The most recently created or updated appointment records. This is not a full audit trail.</p>
            <?php if (!$changes): ?>
                <p class="dash-empty">No appointments have been recorded yet.</p>
            <?php else: ?>
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <caption>The <?= count($changes) ?> most recently changed appointment<?= count($changes) === 1 ? '' : 's' ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">Updated</th>
                                <th scope="col">Patient</th>
                                <th scope="col">Doctor</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($changes as $a): ?>
                                <tr>
                                    <th scope="row" data-label="Updated"><?= e(fmt_datetime($a['updated_at'])) ?></th>
                                    <td data-label="Patient"><?= e($a['patient_name']) ?></td>
                                    <td data-label="Doctor"><?= e($a['doctor_name']) ?></td>
                                    <td data-label="Status"><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
