<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointments.php';
auth_boot();
require_role('doctor');
header('Cache-Control: no-store');

$user     = current_user();
$doctorId = doctor_id_for_user($user['id']);   // derived from THIS user's id only

if ($doctorId !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id        = (int) ($_POST['id'] ?? 0);
    $newStatus = (string) ($_POST['status'] ?? '');
    $notes     = (string) ($_POST['notes'] ?? '');

    $allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        flash_set('error', 'That status change is not allowed.');
        redirect(BASE_URL . '/doctor/appointments.php');
    }

    // Ownership is enforced inside the helper (WHERE doctor_id = :did).
    $result = update_appointment_status($id, $newStatus, $notes, $doctorId);
    flash_set($result['ok'] ? 'success' : 'error',
        $result['ok'] ? 'The appointment has been updated.' : $result['error']);

    redirect(BASE_URL . '/doctor/appointments.php');
}

$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $statusFilter = 'all';
}

$appointments = $doctorId === null ? [] : list_doctor_appointments($doctorId, $statusFilter);

// GET ?action=edit&id=N -> show the update form for one owned appointment.
$editing = null;
if ($doctorId !== null && ($_GET['action'] ?? '') === 'edit' && ctype_digit((string) ($_GET['id'] ?? ''))) {
    $editing = appointment_for_doctor((int) $_GET['id'], $doctorId);
}

$page_title = 'My appointments';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Doctor area</p>
        <h1>My appointments</h1>
        <p>Signed in as <strong><?= e($user['full_name']) ?></strong> &middot; role: doctor</p>
    </section>
</section>

<div class="container dash">
<?php if ($doctorId === null): ?>
    <p class="dash-empty">Your doctor profile has not been set up yet.
       Please contact an administrator.</p>
<?php else: ?>

    <div class="dash-actions">
        <a class="button button-light" href="<?= e(dashboard_url_for('doctor')) ?>">Back to dashboard</a>
    </div>

    <?php if ($editing !== null): ?>
        <?php $nextChoices = allowed_transitions($editing['status']); ?>
        <section class="dash-confirm" aria-labelledby="edit-heading">
            <h2 id="edit-heading">Update appointment</h2>
            <p>
                <strong><?= e(fmt_date($editing['appointment_date'])) ?></strong>
                at <strong><?= e(fmt_time($editing['appointment_time'])) ?></strong>
                &middot; patient <?= e($editing['patient_name']) ?>
                &middot; <?= e($editing['department_name']) ?>
                &middot; currently <?= e(status_label($editing['status'])) ?>
            </p>
            <form action="<?= e(BASE_URL) ?>/doctor/appointments.php" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="<?= e($editing['status']) ?>">
                            Keep as <?= e(status_label($editing['status'])) ?>
                        </option>
                        <?php foreach ($nextChoices as $choice): ?>
                            <option value="<?= e($choice) ?>">Change to <?= e(status_label($choice)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$nextChoices): ?>
                        <p class="dash-note">This appointment is <?= e(status_label($editing['status'])) ?> and its status cannot change further.</p>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="notes">Notes (optional, up to 500 characters)</label>
                    <textarea id="notes" name="notes" maxlength="500"><?= e((string) ($editing['notes'] ?? '')) ?></textarea>
                </div>
                <button type="submit" class="button button-primary">Save changes</button>
                <a class="button button-light" href="<?= e(BASE_URL) ?>/doctor/appointments.php">Cancel</a>
            </form>
        </section>
    <?php endif; ?>

    <section class="dash-section" aria-labelledby="list-heading">
        <h2 id="list-heading">Assigned appointments</h2>

        <form action="<?= e(BASE_URL) ?>/doctor/appointments.php" method="get" class="dash-filter">
            <div class="form-field">
                <label for="status-filter">Filter by status</label>
                <select id="status-filter" name="status">
                    <?php foreach (['all', 'pending', 'confirmed', 'completed', 'cancelled'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $opt === $statusFilter ? 'selected' : '' ?>>
                            <?= e(ucfirst($opt)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button button-light">Apply filter</button>
        </form>

        <?php if (!$appointments): ?>
            <p class="dash-empty">No appointments match this filter.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption><?= count($appointments) ?> appointment<?= count($appointments) === 1 ? '' : 's' ?> (<?= e($statusFilter) ?>)</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <th scope="row"><?= e(fmt_date($a['appointment_date'])) ?></th>
                                <td><?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td><?= e($a['patient_name']) ?></td>
                                <td><?= e($a['department_name']) ?></td>
                                <td><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                                <td><a href="<?= e(BASE_URL) ?>/doctor/appointments.php?action=edit&amp;id=<?= (int) $a['id'] ?>">Update</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
