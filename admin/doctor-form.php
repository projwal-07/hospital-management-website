<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/doctors.php';
require_once __DIR__ . '/../includes/appointments.php'; // all_departments(), department_exists()
auth_boot();
require_role('admin');
header('Cache-Control: no-store');

$user = current_user();

$departments   = all_departments();
$validDeptIds  = array_map('intval', array_column($departments, 'id'));

// Edit mode if ?id=N resolves to an existing doctor.
$editId = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$doctor = $editId > 0 ? get_doctor($editId) : null;
if ($editId > 0 && $doctor === null) {
    flash_set('error', 'Doctor not found.');
    redirect(BASE_URL . '/admin/doctors.php');
}
$isEdit = $doctor !== null;

$errors = [];
$values = [
    'full_name'      => $doctor['full_name']     ?? '',
    'email'          => $doctor['email']         ?? '',
    'phone'          => $doctor['phone']         ?? '',
    'department_id'  => (string) ($doctor['department_id'] ?? ''),
    'specialisation' => $doctor['specialisation'] ?? '',
    'bio'            => $doctor['bio']           ?? '',
    'is_active'      => (string) ($doctor['is_active'] ?? '1'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['full_name']      = trim((string) ($_POST['full_name'] ?? ''));
    $values['email']          = strtolower(trim((string) ($_POST['email'] ?? '')));
    $values['phone']          = trim((string) ($_POST['phone'] ?? ''));
    $values['department_id']  = trim((string) ($_POST['department_id'] ?? ''));
    $values['specialisation'] = trim((string) ($_POST['specialisation'] ?? ''));
    $values['bio']            = trim((string) ($_POST['bio'] ?? ''));
    $values['is_active']      = ((string) ($_POST['is_active'] ?? '0')) === '1' ? '1' : '0';
    $password                 = (string) ($_POST['password'] ?? '');

    $deptId = (int) $values['department_id'];

    if (v_required($errors, 'full_name', $values['full_name'], 'Full name')) {
        v_length($errors, 'full_name', $values['full_name'], 2, 100, 'Full name');
    }
    if (v_required($errors, 'email', $values['email'], 'Email address')) {
        if (v_email($errors, 'email', $values['email'], 'email address')) {
            v_length($errors, 'email', $values['email'], 3, 150, 'Email address');
            $exceptUserId = $isEdit ? (int) $doctor['user_id'] : 0;
            if (!isset($errors['email']) && email_in_use($values['email'], $exceptUserId)) {
                $errors['email'] = 'An account with that email address already exists.';
            }
        }
    }
    if (!$isEdit) {
        if (v_required($errors, 'password', $password, 'Password')) {
            v_password($errors, 'password', $password);
        }
    }
    if (v_required($errors, 'department_id', $values['department_id'], 'Department')) {
        v_in_set($errors, 'department_id', $deptId, $validDeptIds, 'department');
    }
    if (v_required($errors, 'specialisation', $values['specialisation'], 'Specialisation')) {
        v_length($errors, 'specialisation', $values['specialisation'], 2, 100, 'Specialisation');
    }
    v_note_optional($errors, 'bio', $values['bio'], 500, 'Biography');
    v_phone_optional($errors, 'phone', $values['phone']);

    if (!$errors) {
        $data = [
            'full_name'      => $values['full_name'],
            'email'          => $values['email'],
            'phone'          => $values['phone'],
            'department_id'  => $deptId,
            'specialisation' => $values['specialisation'],
            'bio'            => $values['bio'],
            'is_active'      => $values['is_active'] === '1',
        ];

        if ($isEdit) {
            $result = update_doctor((int) $doctor['id'], $data);
        } else {
            $data['password'] = $password;
            $result = create_doctor($data);
        }

        if ($result['ok']) {
            flash_set('success', $isEdit ? 'Doctor updated.' : 'Doctor created.');
            redirect(BASE_URL . '/admin/doctors.php');
        }
        $errors['form'] = $result['error'];
    }

    $password = '';
}

$page_title = $isEdit ? 'Edit doctor' : 'Add a doctor';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1><?= $isEdit ? 'Edit doctor' : 'Add a doctor' ?></h1>
        <p><?= $isEdit ? 'Update this doctor\'s details.' : 'Create a doctor account and profile.' ?>
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

    <form action="<?= e(BASE_URL) ?><?= $isEdit ? '/admin/doctor-form.php?id=' . (int) $doctor['id'] : '/admin/doctor-form.php' ?>"
          method="post" class="crud-form" novalidate>
        <?= csrf_field() ?>
        <div class="crud-form-head">
            <h2><?= $isEdit ? 'Edit doctor' : 'Add a doctor' ?></h2>
            <p>Fields marked * are required.</p>
        </div>
        <div class="form-grid">
            <div class="form-field">
                <label for="full_name">Full name *</label>
                <input type="text" id="full_name" name="full_name" required minlength="2" maxlength="100"
                       autocomplete="name" value="<?= e($values['full_name']) ?>"
                       <?= isset($errors['full_name']) ? 'aria-invalid="true" aria-describedby="full_name-error"' : '' ?>>
                <?php if (isset($errors['full_name'])): ?>
                    <p class="form-feedback error" id="full_name-error"><?= e($errors['full_name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="email">Email address *</label>
                <input type="email" id="email" name="email" required maxlength="150"
                       autocomplete="email" value="<?= e($values['email']) ?>"
                       <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                <?php if (isset($errors['email'])): ?>
                    <p class="form-feedback error" id="email-error"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (!$isEdit): ?>
            <div class="form-field">
                <label for="password">Initial password *</label>
                <input type="password" id="password" name="password" required minlength="8" maxlength="72"
                       autocomplete="new-password"
                       aria-describedby="<?= isset($errors['password']) ? 'password-error' : 'password-hint' ?>"
                       <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['password'])): ?>
                    <p class="form-feedback error" id="password-error"><?= e($errors['password']) ?></p>
                <?php else: ?>
                    <p id="password-hint" class="dash-note">At least 8 characters, not only numbers. The doctor can be given this to log in.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="form-field">
                <p class="dash-note">Password changes are not handled here.</p>
            </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="phone">Telephone (optional)</label>
                <input type="tel" id="phone" name="phone" maxlength="20" autocomplete="tel"
                       value="<?= e($values['phone']) ?>"
                       <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                <?php if (isset($errors['phone'])): ?>
                    <p class="form-feedback error" id="phone-error"><?= e($errors['phone']) ?></p>
                <?php endif; ?>
            </div>

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
                <label for="specialisation">Specialisation *</label>
                <input type="text" id="specialisation" name="specialisation" required minlength="2" maxlength="100"
                       value="<?= e($values['specialisation']) ?>"
                       <?= isset($errors['specialisation']) ? 'aria-invalid="true" aria-describedby="specialisation-error"' : '' ?>>
                <?php if (isset($errors['specialisation'])): ?>
                    <p class="form-feedback error" id="specialisation-error"><?= e($errors['specialisation']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field full-width">
                <label for="bio">Biography (optional, up to 500 characters)</label>
                <textarea id="bio" name="bio" maxlength="500"
                          <?= isset($errors['bio']) ? 'aria-invalid="true" aria-describedby="bio-error"' : '' ?>><?= e($values['bio']) ?></textarea>
                <?php if (isset($errors['bio'])): ?>
                    <p class="form-feedback error" id="bio-error"><?= e($errors['bio']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           <?= $values['is_active'] === '1' ? 'checked' : '' ?>>
                    Active (available for new bookings)
                </label>
            </div>
        </div>

        <div class="crud-form-foot">
            <button type="submit" class="button button-primary"><?= $isEdit ? 'Save changes' : 'Create doctor' ?></button>
            <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/doctors.php">Cancel</a>
        </div>
    </form>
</div>

<script>
    var summary = document.getElementById('form-errors');
    if (summary) { summary.focus(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
