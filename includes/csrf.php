<?php
declare(strict_types=1);

/**
 * CSRF protection.
 *
 * One random token per session, generated with random_bytes() and
 * compared with hash_equals(). Every state-changing POST in the
 * application (registration, login, logout, and later all CRUD forms)
 * must include csrf_field() and be checked with csrf_verify().
 *
 * Requires an active session - call auth_boot() first.
 */

/** Return the session CSRF token, creating it on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Replace the token. Call on any privilege change (login, logout). */
function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Hidden input carrying the token, value HTML-escaped. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify the submitted token for the current POST request.
 * On failure: send HTTP 400 with a generic message and stop.
 * No internal detail is exposed.
 */
function csrf_verify(): void
{
    $sent  = $_POST['csrf_token'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(400);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
           . '<title>Request could not be processed</title></head><body>'
           . '<h1>Request could not be processed</h1>'
           . '<p>Your session has expired or the form was invalid. '
           . 'Please go back, reload the page and try again.</p>'
           . '</body></html>';
        exit;
    }
}
