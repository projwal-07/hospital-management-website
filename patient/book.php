<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/appointments.php';
auth_boot();
require_role('patient');
header('Cache-Control: no-store');

$user = current_user();

$departments = all_departments();
$doctors     = active_doctors();
$slots       = booking_slots();
$bounds      = booking_date_bounds();

$validDeptIds   = array_map('intval', array_column($departments, 'id'));
$validDoctorIds = array_map('intval', array_column($doctors, 'id'));

$errors = [];
$values = ['department_id' => '', 'doctor_id' => '', 'appointment_date' => '', 'appointment_time' => '', 'reason' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['department_id']    = trim((string) ($_POST['department_id'] ?? ''));
    $values['doctor_id']        = trim((string) ($_POST['doctor_id'] ?? ''));
    $values['appointment_date'] = trim((string) ($_POST['appointment_date'] ?? ''));
    $values['appointment_time'] = trim((string) ($_POST['appointment_time'] ?? ''));
    $values['reason']           = trim((string) ($_POST['reason'] ?? ''));

    $deptId   = (int) $values['department_id'];
    $doctorId = (int) $values['doctor_id'];

    if (v_required($errors, 'department_id', $values['department_id'], 'Department')) {
        v_in_set($errors, 'department_id', $deptId, $validDeptIds, 'department');
    }
    if (v_required($errors, 'doctor_id', $values['doctor_id'], 'Doctor')) {
        v_in_set($errors, 'doctor_id', $doctorId, $validDoctorIds, 'doctor');
    }

    // Cross-field rule: the doctor must be active and in the chosen department.
    if (!isset($errors['department_id']) && !isset($errors['doctor_id'])
        && !doctor_active_in_department($doctorId, $deptId)) {
        $errors['doctor_id'] = 'The selected doctor does not work in the selected department.';
    }

    if (v_required($errors, 'appointment_date', $values['appointment_date'], 'Date')
        && !is_valid_booking_date($values['appointment_date'])) {
        $errors['appointment_date'] = 'Choose a weekday between ' . fmt_date($bounds['min'])
            . ' and ' . fmt_date($bounds['max']) . '.';
    }

    if (v_required($errors, 'appointment_time', $values['appointment_time'], 'Time')
        && !is_valid_slot($values['appointment_time'])) {
        $errors['appointment_time'] = 'Choose one of the listed appointment times.';
    }

    if (v_required($errors, 'reason', $values['reason'], 'Reason for the appointment')) {
        v_length($errors, 'reason', $values['reason'], 10, 500, 'Reason for the appointment');
    }

    // Slot availability (only worth checking once the rest is valid).
    if (!$errors && !slot_is_free($doctorId, $values['appointment_date'], $values['appointment_time'])) {
        $errors['appointment_time'] = 'That time is no longer available. Please choose another slot.';
    }

    if (!$errors) {
        try {
            book_appointment(
                $user['id'],
                $doctorId,
                $deptId,
                $values['appointment_date'],
                $values['appointment_time'],
                $values['reason']
            );
            flash_set('success', 'Your appointment request has been submitted and is now pending.');
            redirect(BASE_URL . '/patient/appointments.php');
        } catch (DomainException $ex) {
            $errors['appointment_time'] = $ex->getMessage();
        }
    }
}

$page_title = 'Book an appointment';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Patient area</p>
        <h1>Book an appointment</h1>
        <p>Choose a department and an available doctor, then a weekday time slot.
           Fields marked * are required.</p>
    </section>
</section>

<div class="container dash">
    <?php if ($errors): ?>
        <div class="form-feedback error" role="alert" tabindex="-1" id="form-errors">
            <p>Please fix the following before continuing:</p>
            <ul>
                <?php foreach ($errors as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$doctors): ?>
        <p class="dash-empty">There are no active doctors available for booking at the moment.
           Please try again later or contact reception.</p>
    <?php else: ?>
    <form action="<?= e(BASE_URL) ?>/patient/book.php" method="post" class="form-panel dash-form" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="department_id">Department *</label>
                <select id="department_id" name="department_id" required
                        <?= isset($errors['department_id']) ? 'aria-invalid="true" aria-describedby="department_id-error"' : '' ?>>
                    <option value="">Please choose</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (string) $d['id'] === $values['department_id'] ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['department_id'])): ?>
                    <p class="form-feedback error" id="department_id-error"><?= e($errors['department_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="doctor_id">Doctor *</label>
                <select id="doctor_id" name="doctor_id" required
                        <?= isset($errors['doctor_id']) ? 'aria-invalid="true" aria-describedby="doctor_id-error"' : 'aria-describedby="doctor_id-hint"' ?>>
                    <option value="">Please choose</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int) $doc['id'] ?>"
                                data-department="<?= (int) $doc['department_id'] ?>"
                                <?= (string) $doc['id'] === $values['doctor_id'] ? 'selected' : '' ?>>
                            <?= e($doc['full_name']) ?> &mdash; <?= e($doc['department_name']) ?> (<?= e($doc['specialisation']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['doctor_id'])): ?>
                    <p class="form-feedback error" id="doctor_id-error"><?= e($errors['doctor_id']) ?></p>
                <?php else: ?>
                    <p id="doctor_id-hint" class="dash-note">The doctor must work in the department you selected.</p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="appointment_date">Date * (weekday)</label>
                <input type="date" id="appointment_date" name="appointment_date" required
                       min="<?= e($bounds['min']) ?>" max="<?= e($bounds['max']) ?>"
                       value="<?= e($values['appointment_date']) ?>"
                       <?= isset($errors['appointment_date']) ? 'aria-invalid="true" aria-describedby="appointment_date-error"' : '' ?>>
                <?php if (isset($errors['appointment_date'])): ?>
                    <p class="form-feedback error" id="appointment_date-error"><?= e($errors['appointment_date']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="appointment_time">Time *</label>
                <select id="appointment_time" name="appointment_time" required
                        <?= isset($errors['appointment_time']) ? 'aria-invalid="true" aria-describedby="appointment_time-error"' : '' ?>>
                    <option value="">Please choose</option>
                    <?php foreach ($slots as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $value === $values['appointment_time'] ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['appointment_time'])): ?>
                    <p class="form-feedback error" id="appointment_time-error"><?= e($errors['appointment_time']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field full-width">
                <label for="reason">Reason for the appointment *</label>
                <textarea id="reason" name="reason" required minlength="10" maxlength="500"
                          aria-describedby="<?= isset($errors['reason']) ? 'reason-error' : 'reason-hint' ?>"
                          <?= isset($errors['reason']) ? 'aria-invalid="true"' : '' ?>><?= e($values['reason']) ?></textarea>
                <?php if (isset($errors['reason'])): ?>
                    <p class="form-feedback error" id="reason-error"><?= e($errors['reason']) ?></p>
                <?php else: ?>
                    <p id="reason-hint" class="dash-note">Between 10 and 500 characters.</p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="button button-primary">Request appointment</button>
        <a class="button button-light" href="<?= e(BASE_URL) ?>/patient/appointments.php">Cancel</a>
    </form>
    <?php endif; ?>
</div>

<script>
    var summary = document.getElementById('form-errors');
    if (summary) { summary.focus(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
