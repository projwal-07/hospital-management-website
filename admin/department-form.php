<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/departments.php';
auth_boot();
require_role('admin');
header('Cache-Control: no-store');

$user = current_user();

$editId     = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$department = $editId > 0 ? get_department($editId) : null;
if ($editId > 0 && $department === null) {
    flash_set('error', 'Department not found.');
    redirect(BASE_URL . '/admin/departments.php');
}
$isEdit = $department !== null;

$errors = [];
$values = [
    'name'        => $department['name']        ?? '',
    'description' => $department['description'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['name']        = trim((string) ($_POST['name'] ?? ''));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));

    if (v_required($errors, 'name', $values['name'], 'Name')) {
        v_length($errors, 'name', $values['name'], 2, 100, 'Name');
    }
    v_note_optional($errors, 'description', $values['description'], 255, 'Description');

    if (!$errors) {
        $result = $isEdit
            ? update_department((int) $department['id'], $values['name'], $values['description'])
            : create_department($values['name'], $values['description']);

        if ($result['ok']) {
            flash_set('success', $isEdit ? 'Department updated.' : 'Department created.');
            redirect(BASE_URL . '/admin/departments.php');
        }
        $errors['form'] = $result['error'];
    }
}

$page_title = $isEdit ? 'Edit department' : 'Add a department';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-banner">
    <section class="container">
        <p class="kicker">Administration</p>
        <h1><?= $isEdit ? 'Edit department' : 'Add a department' ?></h1>
        <p>Fields marked * are required.</p>
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

    <form action="<?= e(BASE_URL) ?><?= $isEdit ? '/admin/department-form.php?id=' . (int) $department['id'] : '/admin/department-form.php' ?>"
          method="post" class="form-panel dash-form" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-field full-width">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required minlength="2" maxlength="100"
                       value="<?= e($values['name']) ?>"
                       <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                <?php if (isset($errors['name'])): ?>
                    <p class="form-feedback error" id="name-error"><?= e($errors['name']) ?></p>
                <?php endif; ?>
            </div>
            <div class="form-field full-width">
                <label for="description">Description (optional, up to 255 characters)</label>
                <textarea id="description" name="description" maxlength="255"
                          <?= isset($errors['description']) ? 'aria-invalid="true" aria-describedby="description-error"' : '' ?>><?= e($values['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <p class="form-feedback error" id="description-error"><?= e($errors['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <button type="submit" class="button button-primary"><?= $isEdit ? 'Save changes' : 'Create department' ?></button>
        <a class="button button-light" href="<?= e(BASE_URL) ?>/admin/departments.php">Cancel</a>
    </form>
</div>

<script>
    var summary = document.getElementById('form-errors');
    if (summary) { summary.focus(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
