<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointments.php';
auth_boot();
require_role('admin');
header('Cache-Control: no-store');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id        = (int) ($_POST['id'] ?? 0);
    $newStatus = (string) ($_POST['status'] ?? '');
    $notes     = (string) ($_POST['notes'] ?? '');

    if (!in_array($newStatus, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        flash_set('error', 'That status change is not allowed.');
        redirect(BASE_URL . '/admin/appointments.php');
    }

    // Admin is hospital-wide: no doctor_id ownership filter, but the same
    // controlled transition rules apply.
    $result = update_appointment_status($id, $newStatus, $notes, null);
    flash_set($result['ok'] ? 'success' : 'error',
        $result['ok'] ? 'The appointment has been updated.' : $result['error']);

    redirect(BASE_URL . '/admin/appointments.php');
}

$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $statusFilter = 'all';
}
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) === 1 ? $_GET['from'] : '';
$to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) === 1 ? $_GET['to'] : '';

$appointments = list_all_appointments($statusFilter, $from, $to);

$editing = null;
if (($_GET['action'] ?? '') === 'edit' && ctype_digit((string) ($_GET['id'] ?? ''))) {
    $editing = appointment_any((int) $_GET['id']);
}

$page_title = 'Manage appointments';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1>Appointments</h1>
        <p>Hospital-wide view. Status changes follow the same controlled transitions as the doctor view.</p>
    </section>
</section>

<div class="container dash">
    <div class="dash-actions">
        <a class="button button-light" href="<?= e(dashboard_url_for('admin')) ?>">Back to dashboard</a>
    </div>

    <?php if ($editing !== null): ?>
        <?php $nextChoices = allowed_transitions($editing['status']); ?>
        <section class="dash-confirm" aria-labelledby="edit-heading">
            <h2 id="edit-heading">Update appointment</h2>
            <p>
                <strong><?= e(fmt_date($editing['appointment_date'])) ?></strong>
                at <strong><?= e(fmt_time($editing['appointment_time'])) ?></strong>
                &middot; patient <?= e($editing['patient_name']) ?>
                &middot; doctor <?= e($editing['doctor_name']) ?>
                &middot; <?= e($editing['department_name']) ?>
                &middot; currently <?= e(status_label($editing['status'])) ?>
            </p>
            <form action="<?= e(BASE_URL) ?>/admin/appointments.php" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="<?= e($editing['status']) ?>">Keep as <?= e(status_label($editing['status'])) ?></option>
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
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save changes</button>
                    <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/appointments.php">Cancel</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="dash-section" aria-labelledby="list-heading">
        <h2 id="list-heading"><?= svg_icon('list') ?>All appointments</h2>

        <form action="<?= e(BASE_URL) ?>/admin/appointments.php" method="get" class="dash-filter">
            <div class="form-field">
                <label for="status-filter">Status</label>
                <select id="status-filter" name="status">
                    <?php foreach (['all', 'pending', 'confirmed', 'completed', 'cancelled'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $opt === $statusFilter ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="from">From date</label>
                <input type="date" id="from" name="from" value="<?= e($from) ?>">
            </div>
            <div class="form-field">
                <label for="to">To date</label>
                <input type="date" id="to" name="to" value="<?= e($to) ?>">
            </div>
            <button type="submit" class="button button-light">Apply filters</button>
        </form>

        <?php if (!$appointments): ?>
            <p class="dash-empty">No appointments match these filters.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption><?= count($appointments) ?> appointment<?= count($appointments) === 1 ? '' : 's' ?> shown (newest first, max 200)</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <th scope="row" data-label="Date"><?= e(fmt_date($a['appointment_date'])) ?></th>
                                <td data-label="Time"><?= e(fmt_time($a['appointment_time'])) ?></td>
                                <td data-label="Patient"><?= e($a['patient_name']) ?></td>
                                <td data-label="Doctor"><?= e($a['doctor_name']) ?></td>
                                <td data-label="Department"><?= e($a['department_name']) ?></td>
                                <td data-label="Status"><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                                <td data-label="Action"><a href="<?= e(BASE_URL) ?>/admin/appointments.php?action=edit&amp;id=<?= (int) $a['id'] ?>">Update</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
