<?php
/**
 * Database connection layer for the Hospital Management System.
 *
 * Include with require_once, then call db() wherever a connection is
 * needed:
 *
 *     require_once __DIR__ . '/../includes/database.php';
 *     $pdo = db();
 *
 * db() creates one PDO connection on first call and returns that same
 * connection for the rest of the request.
 *
 * Security notes
 * --------------
 * - Real credentials live only in config/db.php, which is git-ignored.
 * - On any connection failure the browser receives a short, generic
 *   message only. The technical reason (including the PDO exception
 *   message) is written to the PHP error log via error_log() and is
 *   never sent to the client. No DSN, credentials, stack trace, server
 *   details or file paths are exposed.
 * - PDO is configured for real (non-emulated) prepared statements.
 *   From later phases on, every user-controlled value MUST be passed as
 *   a bound parameter to a prepared statement and MUST NOT be
 *   concatenated into SQL text.
 * - Prepared statements are the primary defence for SQL data values.
 *   They do NOT, on their own, make everything safe: identifiers
 *   (table/column names), keywords, and clauses such as LIMIT cannot be
 *   bound and must be checked against a fixed allow-list; and prepared
 *   statements are not a substitute for input validation, authorisation
 *   checks or output escaping.
 */

declare(strict_types=1);

/**
 * Return the shared PDO connection for this request, creating it on the
 * first call. Stops with a generic 503 response if the connection
 * cannot be established.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config/db.php';

    if (!is_file($configFile)) {
        error_log('HMS: config/db.php is missing. Copy config/db.example.php '
            . 'to config/db.php and set the local hms_app password.');
        db_connection_failed();
    }

    $config = require $configFile;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host']    ?? '127.0.0.1',
        $config['port']    ?? 3306,
        $config['name']    ?? 'hms',
        $config['charset'] ?? 'utf8mb4'
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
    ];

    try {
        $pdo = new PDO(
            $dsn,
            $config['user'] ?? '',
            $config['pass'] ?? '',
            $options
        );
    } catch (PDOException $e) {
        // Developer detail to the error log only - never to the browser.
        error_log('HMS: database connection failed: ' . $e->getMessage());
        db_connection_failed();
    }

    return $pdo;
}

/**
 * Emit a generic "temporarily unavailable" response and stop.
 * Exposes no credentials, DSN, exception text, stack trace, server
 * details or file paths.
 */
function db_connection_failed(): never
{
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        header('Retry-After: 60');
    }

    exit('The service is temporarily unavailable. Please try again later.');
}
