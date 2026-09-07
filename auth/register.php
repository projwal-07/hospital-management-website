<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
auth_boot();
require_guest();

/**
 * Patient self-registration.
 *
 * Public registration ALWAYS creates role = 'patient'. No role,
 * privilege, id or authorisation value is read from the form. Admin and
 * doctor accounts are never created here.
 */

$errors = [];
$values = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $values['email']     = strtolower(trim((string) ($_POST['email'] ?? '')));
    $values['phone']     = trim((string) ($_POST['phone'] ?? ''));
    $password            = (string) ($_POST['password'] ?? '');
    $passwordConfirm     = (string) ($_POST['password_confirm'] ?? '');

    if (v_required($errors, 'full_name', $values['full_name'], 'Full name')) {
        v_length($errors, 'full_name', $values['full_name'], 2, 100, 'Full name');
    }
    if (v_required($errors, 'email', $values['email'], 'Email address')) {
        if (v_email($errors, 'email', $values['email'], 'email address')) {
            v_length($errors, 'email', $values['email'], 3, 150, 'Email address');
        }
    }
    if (v_required($errors, 'password', $password, 'Password')) {
        v_password($errors, 'password', $password);
    }
    if (v_required($errors, 'password_confirm', $passwordConfirm, 'Confirm password')) {
        v_match($errors, 'password_confirm', $password, $passwordConfirm, 'Passwords');
    }
    v_phone_optional($errors, 'phone', $values['phone']);

    // Friendly duplicate-email check (the UNIQUE index is the real backstop).
    if (!isset($errors['email'])) {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = :email');
        $check->execute([':email' => $values['email']]);
        if ($check->fetchColumn() !== false) {
            $errors['email'] = 'An account with that email address already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insert = db()->prepare(
                "INSERT INTO users (full_name, email, password_hash, role, phone)
                 VALUES (:full_name, :email, :password_hash, 'patient', :phone)"
            );
            $insert->execute([
                ':full_name'     => $values['full_name'],
                ':email'         => $values['email'],
                ':password_hash' => $hash,
                ':phone'         => $values['phone'] === '' ? null : $values['phone'],
            ]);

            flash_set('success', 'Your account has been created. Please log in.');
            redirect(BASE_URL . '/auth/login.php');
        } catch (PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) === 1062) {
                $errors['email'] = 'An account with that email address already exists.';
            } else {
                error_log('HMS register: ' . $ex->getMessage());
                $errors['form'] = 'Sorry, something went wrong. Please try again.';
            }
        }
    }

    // Never keep password input after a failed submission.
    $password = $passwordConfirm = '';
}

$page_title = 'Register';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Patient registration</p>
        <h1>Create your patient account</h1>
        <p>Register to book and manage appointments. Fields marked * are required.</p>
    </section>
</section>

<section class="content-section">
    <section class="container">
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

        <form action="<?= e(BASE_URL) ?>/auth/register.php" method="post" class="form-panel" novalidate>
            <?= csrf_field() ?>
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

                <div class="form-field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="8" maxlength="72"
                           autocomplete="new-password"
                           <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : 'aria-describedby="password-hint"' ?>>
                    <?php if (isset($errors['password'])): ?>
                        <p class="form-feedback error" id="password-error"><?= e($errors['password']) ?></p>
                    <?php else: ?>
                        <p id="password-hint">At least 8 characters. Must not be only numbers.</p>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="password_confirm">Confirm password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required maxlength="72"
                           autocomplete="new-password"
                           <?= isset($errors['password_confirm']) ? 'aria-invalid="true" aria-describedby="password_confirm-error"' : '' ?>>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <p class="form-feedback error" id="password_confirm-error"><?= e($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-field full-width">
                    <label for="phone">Telephone (optional)</label>
                    <input type="tel" id="phone" name="phone" maxlength="20"
                           autocomplete="tel" value="<?= e($values['phone']) ?>"
                           <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
                    <?php if (isset($errors['phone'])): ?>
                        <p class="form-feedback error" id="phone-error"><?= e($errors['phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="button button-primary">Create account</button>
            <p>Already registered? <a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a>.</p>
        </form>
    </section>
</section>

<script>
    var summary = document.getElementById('form-errors');
    if (summary) { summary.focus(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
