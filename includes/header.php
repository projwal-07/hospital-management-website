<?php
declare(strict_types=1);

/**
 * Shared page header for the DYNAMIC (PHP) pages only - authentication
 * and dashboard screens. The six existing public .html pages are not
 * affected; their migration to these partials is Phase 7.
 *
 * Set $page_title before including this file. Requires auth.php.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/auth.php';
}

require_once __DIR__ . '/icons.php';   // presentation-only inline SVG helper

$page_title = $page_title ?? 'Hospital Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($page_title) ?> - Evergreen Community Hospital</title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/style.css?v=17">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/dashboard.css?v=18">
    <script src="<?= e(BASE_URL) ?>/js/script.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <aside class="utility-bar" aria-label="Hospital contact information">
        <section class="container">
            <p class="utility-alert">
                <?= svg_icon('lock', ['class' => 'utility-ico']) ?>
                <strong>Emergency Department: Open 24 hours</strong>
                <span class="utility-note">For urgent medical care, please call or visit our emergency department.</span>
            </p>
            <p class="utility-phone">
                <?= svg_icon('phone', ['class' => 'utility-ico']) ?>
                <a href="tel:+61291234567">(02) 9123 4567</a>
            </p>
        </section>
    </aside>

    <header class="site-header">
        <section class="container header-row">
            <a class="brand" href="<?= e(BASE_URL) ?>/index.html" aria-label="Evergreen Community Hospital home page">
                <span class="brand-mark" aria-hidden="true">+</span>
                <span>Evergreen Community Hospital
                    <small>Hospital Management System</small>
                </span>
            </a>
            <button class="menu-button" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu"><span></span></button>
            <?php require __DIR__ . '/nav.php'; ?>
        </section>
    </header>

    <main id="main-content">
<?php
foreach (['success', 'info', 'error'] as $flashKey):
    $flashMsg = flash_get($flashKey);
    if ($flashMsg === null) {
        continue;
    }
    // $flashKey is always one of success | info | error, each of which
    // has a matching .form-feedback style. Presentation only.
    $cssClass = $flashKey;
?>
        <section class="container">
            <p class="form-feedback <?= $cssClass ?>" role="status"><?= e($flashMsg) ?></p>
        </section>
<?php endforeach; ?>
