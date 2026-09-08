<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/departments.php';
auth_boot();
require_role('admin');
header('Cache-Control: no-store');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ((string) ($_POST['action'] ?? '') === 'delete') {
        $result = delete_department((int) ($_POST['id'] ?? 0));
        flash_set($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Department deleted.' : $result['error']);
    } else {
        flash_set('error', 'Unknown action.');
    }

    redirect(BASE_URL . '/admin/departments.php');
}

$departments = list_departments_with_counts();

$confirmDelete = null;
if (($_GET['action'] ?? '') === 'delete' && ctype_digit((string) ($_GET['id'] ?? ''))) {
    foreach ($departments as $d) {
        if ((int) $d['id'] === (int) $_GET['id']) {
            $confirmDelete = $d;
            break;
        }
    }
}

$page_title = 'Manage departments';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1>Departments</h1>
        <p>Create and edit departments. A department used by doctors or appointments cannot be deleted.</p>
    </section>
</section>

<div class="container dash">
    <div class="dash-actions">
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/admin/department-form.php">Add a department</a>
        <a class="button button-light" href="<?= e(dashboard_url_for('admin')) ?>">Back to dashboard</a>
    </div>

    <?php if ($confirmDelete !== null): ?>
        <section class="dash-confirm" aria-labelledby="del-heading">
            <h2 id="del-heading">Delete department: <?= e($confirmDelete['name']) ?>?</h2>
            <?php if ((int) $confirmDelete['doctor_count'] > 0 || (int) $confirmDelete['appointment_count'] > 0): ?>
                <p>This department is used by
                   <strong><?= (int) $confirmDelete['doctor_count'] ?></strong> doctor(s) and
                   <strong><?= (int) $confirmDelete['appointment_count'] ?></strong> appointment(s)
                   and cannot be deleted.</p>
                <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/departments.php">Back</a>
            <?php else: ?>
                <p>This department has no doctors or appointments. Deleting it cannot be undone.</p>
                <form action="<?= e(BASE_URL) ?>/admin/departments.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $confirmDelete['id'] ?>">
                    <button type="submit" class="button button-danger">Delete department</button>
                    <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/departments.php">Keep department</a>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="dash-section" aria-labelledby="list-heading">
        <h2 id="list-heading">All departments</h2>
        <?php if (!$departments): ?>
            <p class="dash-empty">No departments yet. <a href="<?= e(BASE_URL) ?>/admin/department-form.php">Add the first department</a>.</p>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <caption><?= count($departments) ?> department<?= count($departments) === 1 ? '' : 's' ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Doctors</th>
                            <th scope="col">Appointments</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <th scope="row"><?= e($d['name']) ?></th>
                                <td><?= e((string) ($d['description'] ?? '')) ?></td>
                                <td><?= (int) $d['doctor_count'] ?></td>
                                <td><?= (int) $d['appointment_count'] ?></td>
                                <td>
                                    <a href="<?= e(BASE_URL) ?>/admin/department-form.php?id=<?= (int) $d['id'] ?>">Edit</a>
                                    &middot;
                                    <a href="<?= e(BASE_URL) ?>/admin/departments.php?action=delete&amp;id=<?= (int) $d['id'] ?>">Delete</a>
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
