<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/doctors.php';
require_once __DIR__ . '/../includes/dashboard.php'; // status_* not needed; fmt_* available if wanted
auth_boot();
require_role('admin');
header('Cache-Control: no-store');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_active') {
        $makeActive = ((string) ($_POST['active'] ?? '')) === '1';
        set_doctor_active($id, $makeActive);
        flash_set('success', $makeActive ? 'Doctor activated.' : 'Doctor deactivated.');
    } elseif ($action === 'delete') {
        $result = delete_doctor($id);
        flash_set($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Doctor deleted.' : $result['error']);
    } else {
        flash_set('error', 'Unknown action.');
    }

    redirect(BASE_URL . '/admin/doctors.php');
}

$doctors = list_doctors();

// GET ?action=delete&id=N -> confirmation panel.
$confirmDelete = null;
if (($_GET['action'] ?? '') === 'delete' && ctype_digit((string) ($_GET['id'] ?? ''))) {
    $confirmDelete = get_doctor((int) $_GET['id']);
    if ($confirmDelete !== null) {
        $c = db()->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :id');
        $c->bindValue(':id', (int) $confirmDelete['id'], PDO::PARAM_INT);
        $c->execute();
        $confirmDelete['appointment_count'] = (int) $c->fetchColumn();
    }
}

$page_title = 'Manage doctors';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1>Doctors</h1>
        <p>Create, edit, activate or deactivate doctors. A doctor with appointments cannot be deleted.</p>
    </section>
</section>

<div class="container dash">
    <div class="dash-actions">
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/admin/doctor-form.php">Add a doctor</a>
        <a class="button button-light" href="<?= e(dashboard_url_for('admin')) ?>">Back to dashboard</a>
    </div>

    <?php if ($confirmDelete !== null): ?>
        <section class="dash-confirm" aria-labelledby="del-heading">
            <h2 id="del-heading">Delete doctor: <?= e($confirmDelete['full_name']) ?>?</h2>
            <?php if ($confirmDelete['appointment_count'] > 0): ?>
                <p>This doctor has <strong><?= (int) $confirmDelete['appointment_count'] ?></strong>
                   appointment record<?= $confirmDelete['appointment_count'] === 1 ? '' : 's' ?>
                   and cannot be deleted. You can deactivate the doctor so they are hidden from new bookings.</p>
                <form action="<?= e(BASE_URL) ?>/admin/doctors.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= (int) $confirmDelete['id'] ?>">
                    <input type="hidden" name="active" value="0">
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">Deactivate doctor</button>
                        <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/doctors.php">Back</a>
                    </div>
                </form>
            <?php else: ?>
                <p>This will permanently remove the doctor and their login account. This cannot be undone.</p>
                <form action="<?= e(BASE_URL) ?>/admin/doctors.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $confirmDelete['id'] ?>">
                    <div class="form-actions">
                        <button type="submit" class="button button-danger">Delete doctor</button>
                        <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/doctors.php">Keep doctor</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="dash-section" aria-labelledby="list-heading">
        <h2 id="list-heading"><?= svg_icon('stethoscope') ?>All doctors</h2>
        <?php if (!$doctors): ?>
            <p class="dash-empty">No doctors yet. <a href="<?= e(BASE_URL) ?>/admin/doctor-form.php">Add the first doctor</a>.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption><?= count($doctors) ?> doctor<?= count($doctors) === 1 ? '' : 's' ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Department</th>
                            <th scope="col">Specialisation</th>
                            <th scope="col">Status</th>
                            <th scope="col">Appointments</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $d): ?>
                            <tr>
                                <th scope="row" data-label="Name"><?= e($d['full_name']) ?></th>
                                <td data-label="Email"><?= e($d['email']) ?></td>
                                <td data-label="Department"><?= e($d['department_name']) ?></td>
                                <td data-label="Specialisation"><?= e($d['specialisation']) ?></td>
                                <td data-label="Status">
                                    <span class="dash-status dash-status--<?= $d['is_active'] ? 'confirmed' : 'cancelled' ?>">
                                        <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td data-label="Appointments"><?= (int) $d['appointment_count'] ?></td>
                                <td data-label="Actions">
                                    <span class="dash-row-actions">
                                        <a href="<?= e(BASE_URL) ?>/admin/doctor-form.php?id=<?= (int) $d['id'] ?>">Edit</a>
                                        <form action="<?= e(BASE_URL) ?>/admin/doctors.php" method="post" class="dash-inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                            <input type="hidden" name="active" value="<?= $d['is_active'] ? '0' : '1' ?>">
                                            <button type="submit" class="link-button"><?= $d['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                        <a href="<?= e(BASE_URL) ?>/admin/doctors.php?action=delete&amp;id=<?= (int) $d['id'] ?>">Delete</a>
                                    </span>
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
