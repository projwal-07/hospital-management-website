<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
auth_boot();
require_guest();

/**
 * Login.
 *
 * Every failure - unknown email, wrong password, or a missing field -
 * produces the same generic message and never reveals whether the
 * email exists. For an unknown email a dummy password_verify() still
 * runs so the response time is similar either way.
 */

// A valid but throwaway bcrypt hash, used only to equalise timing.
const LOGIN_DUMMY_HASH = '$2y$10$l2nu5RA01rXN39gDI0o66.73S4I7CC853Kc8vPap8KUu6jQyxoNdm';

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $authenticated = false;
    $user          = null;

    if ($email !== '' && $password !== '') {
        $stmt = db()->prepare(
            'SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, (string) $user['password_hash'])) {
                $authenticated = true;

                // Transparently upgrade the stored hash if the default has moved on.
                if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                    $rehash = db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                    $rehash->execute([
                        ':h'  => password_hash($password, PASSWORD_DEFAULT),
                        ':id' => (int) $user['id'],
                    ]);
                }
            }
        } else {
            password_verify($password, LOGIN_DUMMY_HASH); // timing equalisation
        }
    } else {
        password_verify('placeholder', LOGIN_DUMMY_HASH); // timing equalisation
    }

    if ($authenticated && $user) {
        login_user($user);

        $destination = safe_return_to($_SESSION['return_to'] ?? null)
            ?? dashboard_url_for((string) $user['role']);
        unset($_SESSION['return_to']);

        redirect($destination);
    }

    $errors['form'] = 'Invalid email or password.';
    $password = '';
}

$page_title = 'Log in';

/* Presentation only: take any one-time notice (the "Please log in to
   continue." hint from require_login(), a session-timeout message, or
   the post-registration success line) BEFORE the header renders it, so
   it shows as a small notice inside the card rather than a full-width
   banner above it. The messages are still set in auth.php / register.php. */
$authNoticeOk   = flash_get('success');
$authNoticeInfo = flash_get('info');
$authNotice     = $authNoticeOk ?? $authNoticeInfo;
$authNoticeKind = $authNoticeOk !== null ? 'success' : 'info';

require __DIR__ . '/../includes/header.php';
?>
<section class="auth-shell">
    <div class="container">
        <div class="auth-card">
            <aside class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">+</span>
                <p class="auth-brand-name">Evergreen Community Hospital</p>
                <p class="auth-brand-sub">Hospital Management System</p>
                <p class="auth-brand-copy">Practical, respectful healthcare for Western Sydney families.</p>
            </aside>

            <div class="auth-main">
                <?php if ($authNotice !== null): ?>
                    <p class="auth-notice auth-notice--<?= $authNoticeKind ?>" role="status"><?= e($authNotice) ?></p>
                <?php endif; ?>
                <p class="kicker">Account access</p>
                <h1>Log in</h1>
                <p class="auth-lead">Enter your email and password to reach your dashboard.</p>

                <?php if ($errors): ?>
                    <div class="form-feedback error" role="alert" tabindex="-1" id="form-errors">
                        <?php foreach ($errors as $message): ?>
                            <p><?= e($message) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= e(BASE_URL) ?>/auth/login.php" method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" required maxlength="150"
                                   autocomplete="email" value="<?= e($email) ?>"
                                   <?= $errors ? 'aria-invalid="true" aria-describedby="form-errors"' : '' ?>>
                        </div>
                        <div class="form-field full-width">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required maxlength="72"
                                   autocomplete="current-password"
                                   <?= $errors ? 'aria-invalid="true" aria-describedby="form-errors"' : '' ?>>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Log in</button>
                    </div>
                    <p class="auth-alt">No account yet? <a href="<?= e(BASE_URL) ?>/auth/register.php">Register as a patient</a>.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
    var summary = document.getElementById('form-errors');
    if (summary) { summary.focus(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
