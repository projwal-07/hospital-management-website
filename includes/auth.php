<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';

/**
 * Authentication, session and authorisation helpers.
 *
 * At the very top of every dynamic page, before any output:
 *
 *     require_once __DIR__ . '/../includes/auth.php';
 *     auth_boot();
 *
 * Authorisation (require_role) is decided from the CURRENT database
 * role, looked up by user id - never from a value supplied by the
 * browser. Hidden navigation is a convenience only; every protected
 * page calls a guard.
 */

if (!defined('BASE_URL')) {
    // The application is served from http://localhost/hms/.
    define('BASE_URL', '/hms');
}

const AUTH_IDLE_TIMEOUT = 1800;                 // 30 minutes, in seconds
const AUTH_ROLES        = ['admin', 'doctor', 'patient'];

/**
 * Start the session with hardened settings and apply the idle timeout.
 * Idempotent - safe to call more than once per request.
 */
function auth_boot(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) AUTH_IDLE_TIMEOUT);

        $https = (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,            // session cookie - cleared when the browser closes
            'path'     => '/hms/',
            'domain'   => '',
            'secure'   => $https,       // true only when HTTPS is actually in use
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('HMSSESSID');
        session_start();
    }

    // Baseline security headers for dynamic pages.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');

    // Idle timeout: clear auth data but keep a working session for the flash.
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > AUTH_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash_set('info', 'Your session timed out. Please log in again.');
    }
    $_SESSION['last_activity'] = $now;
}

/**
 * The authenticated user as ['id','full_name','email','role'] or null.
 *
 * Performs one indexed lookup by id and caches it for the rest of the
 * request. If the account has since been removed, the session is
 * cleared and null is returned.
 */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }

    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
        return $cache = null;
    }

    $stmt = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id = :id');
    $stmt->execute([':id' => (int) $id]);
    $row = $stmt->fetch();

    if (!$row) {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['full_name']);
        return $cache = null;
    }

    $row['id'] = (int) $row['id'];
    return $cache = $row;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Establish an authenticated session for a user row (id, role,
 * full_name). Regenerates the session id to defeat fixation and issues
 * a fresh CSRF token.
 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = (int) $user['id'];
    $_SESSION['role']          = (string) $user['role']; // convenience; authorisation re-reads the DB
    $_SESSION['full_name']     = (string) $user['full_name'];
    $_SESSION['last_activity'] = time();
    csrf_regenerate();
}

/** Clear and destroy the session and expire its cookie. */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Send a Location redirect to a path inside this application and stop.
 * Anything that is not a local path is replaced with the site root.
 */
function redirect(string $localPath): never
{
    if (
        $localPath === ''
        || $localPath[0] !== '/'
        || str_starts_with($localPath, '//')
        || str_contains($localPath, '://')
        || str_contains($localPath, "\n")
        || str_contains($localPath, "\r")
    ) {
        $localPath = BASE_URL . '/';
    }
    header('Location: ' . $localPath);
    exit;
}

/**
 * Return $candidate only if it is a safe local path under /hms/,
 * otherwise null. Used for "return to the page you wanted" redirects.
 */
function safe_return_to(?string $candidate): ?string
{
    if (!is_string($candidate) || $candidate === '') {
        return null;
    }
    if (
        $candidate[0] !== '/'
        || str_starts_with($candidate, '//')
        || str_contains($candidate, '://')
        || str_contains($candidate, "\n")
        || str_contains($candidate, "\r")
    ) {
        return null;
    }
    if (!str_starts_with($candidate, BASE_URL . '/')) {
        return null;
    }
    return $candidate;
}

/** Map a role to its dashboard URL. Unknown roles fall back to patient. */
function dashboard_url_for(string $role): string
{
    return match ($role) {
        'admin'  => BASE_URL . '/dashboard/admin.php',
        'doctor' => BASE_URL . '/dashboard/doctor.php',
        default  => BASE_URL . '/dashboard/patient.php',
    };
}

/** Require any authenticated user, else redirect to login with a return-to. */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $safe = safe_return_to($_SERVER['REQUEST_URI'] ?? null);
    if ($safe !== null) {
        $_SESSION['return_to'] = $safe;
    }
    flash_set('info', 'Please log in to continue.');
    redirect(BASE_URL . '/auth/login.php');
}

/**
 * Require the current (database) role to be one of $roles.
 * Authenticated but wrong role -> HTTP 403 and stop.
 * Not authenticated -> redirect to login (via require_login()).
 */
function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();

    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
           . '<title>Access denied</title></head><body>'
           . '<h1>403 - Access denied</h1>'
           . '<p>You do not have permission to view this page.</p>'
           . '<p><a href="' . htmlspecialchars(BASE_URL . '/', ENT_QUOTES, 'UTF-8') . '">Return to the site</a></p>'
           . '</body></html>';
        exit;
    }
}

/** For login/register pages: send an already-authenticated user to their dashboard. */
function require_guest(): void
{
    $user = current_user();
    if ($user !== null) {
        redirect(dashboard_url_for($user['role']));
    }
}

/* ---------- one-time flash messages ---------- */

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $msg = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

/** HTML-escape helper for templates. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
