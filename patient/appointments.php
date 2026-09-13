<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointments.php';
auth_boot();
require_role('patient');
header('Cache-Control: no-store');

$user = current_user();

/** A patient may cancel a pending/confirmed appointment that is not in the past. */
function patient_can_cancel(array $appt): bool
{
    return in_array($appt['status'], ['pending', 'confirmed'], true)
        && $appt['appointment_date'] >= date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id     = (int) ($_POST['id'] ?? 0);
    $reason = trim((string) ($_POST['reason'] ?? ''));

    $result = cancel_appointment_as_patient($id, $user['id'], $reason);
    flash_set($result['ok'] ? 'success' : 'error',
        $result['ok'] ? 'Your appointment has been cancelled.' : $result['error']);

    redirect(BASE_URL . '/patient/appointments.php');
}

$appointments = list_patient_appointments($user['id']);

// GET ?action=cancel&id=N -> show a confirmation panel for that appointment.
$confirm = null;
if (($_GET['action'] ?? '') === 'cancel' && ctype_digit((string) ($_GET['id'] ?? ''))) {
    $candidate = appointment_for_patient((int) $_GET['id'], $user['id']);
    if ($candidate !== null && patient_can_cancel($candidate)) {
        $confirm = $candidate;
    }
}

$page_title = 'My appointments';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Patient area</p>
        <h1>My appointments</h1>
        <p>Review, book and cancel your appointments.</p>
    </section>
</section>

<div class="container dash">

    <div class="dash-actions">
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/patient/book.php">Book an appointment</a>
        <a class="button button-light" href="<?= e(dashboard_url_for('patient')) ?>">Back to dashboard</a>
    </div>

    <?php if ($confirm !== null): ?>
        <section class="dash-confirm" aria-labelledby="confirm-heading">
            <h2 id="confirm-heading">Cancel this appointment?</h2>
            <p>
                <strong><?= e(fmt_date($confirm['appointment_date'])) ?></strong>
                at <strong><?= e(fmt_time($confirm['appointment_time'])) ?></strong>
                with <?= e($confirm['doctor_name']) ?> (<?= e($confirm['department_name']) ?>),
                currently <?= e(status_label($confirm['status'])) ?>.
            </p>
            <p>This cannot be undone. The time slot will become available for others to book.</p>
            <form action="<?= e(BASE_URL) ?>/patient/appointments.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $confirm['id'] ?>">
                <div class="form-field">
                    <label for="reason">Reason (optional)</label>
                    <input type="text" id="reason" name="reason" maxlength="500"
                           placeholder="e.g. no longer needed">
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-danger">Cancel appointment</button>
                    <a class="button button-light" href="<?= e(BASE_URL) ?>/patient/appointments.php">Keep appointment</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="dash-section" aria-labelledby="list-heading">
        <h2 id="list-heading"><?= svg_icon('list') ?>All appointments</h2>
        <?php if (!$appointments): ?>
            <p class="dash-empty">You have no appointments yet.
               <a href="<?= e(BASE_URL) ?>/patient/book.php">Book your first appointment</a>.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption>Your <?= count($appointments) ?> appointment<?= count($appointments) === 1 ? '' : 's' ?>, newest first</caption>
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
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
                                <td data-label="Doctor"><?= e($a['doctor_name']) ?></td>
                                <td data-label="Department"><?= e($a['department_name']) ?></td>
                                <td data-label="Status"><span class="<?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td>
                                <td data-label="Action">
                                    <?php if (patient_can_cancel($a)): ?>
                                        <a href="<?= e(BASE_URL) ?>/patient/appointments.php?action=cancel&amp;id=<?= (int) $a['id'] ?>">Cancel</a>
                                    <?php else: ?>
                                        <span class="dash-note">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
