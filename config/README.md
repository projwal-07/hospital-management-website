# config/

Database settings for the Hospital Management System.

Direct browser access to this folder is blocked by `.htaccess`.

## Files

| File | In Git? | Purpose |
|------|---------|---------|
| `db.example.php` | committed | Template. Returns a settings array with placeholder values. No real password. |
| `db.php` | **git-ignored** | Your local copy of the template with real credentials. Never committed. |

The connection code itself is `includes/database.php`, which exposes a
single function `db()` returning a shared `PDO` object.

## First-time setup

1. Copy `config/db.example.php` to `config/db.php`
   (`db.php` is already listed in `.gitignore`).
2. Open `config/db.php` and set the `'pass'` value to your local
   `hms_app` password, between the quotes:

   ```php
   'pass' => 'your-hms_app-password',
   ```

   Leave the other values as-is for a standard XAMPP setup
   (`host` `127.0.0.1`, `port` `3306`, `name` `hms`, `user` `hms_app`,
   `charset` `utf8mb4`).
3. Import the database first if you have not already - see
   `database/README.md`.

## Application database account

The application connects as **`hms_app`**, a least-privilege MariaDB
account with only `SELECT, INSERT, UPDATE, DELETE` on the `hms`
database. `root` is used only for administration and schema imports in
phpMyAdmin. The `CREATE USER` / `GRANT` statements are in
`database/README.md`.

## PDO configuration (set in `includes/database.php`)

| Attribute | Value | Reason |
|-----------|-------|--------|
| `ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | Errors throw and are handled centrally; no silent failures. |
| `ATTR_EMULATE_PREPARES` | `false` | Real server-side prepared statements. |
| `ATTR_DEFAULT_FETCH_MODE` | `FETCH_ASSOC` | Predictable associative rows. |
| `ATTR_STRINGIFY_FETCHES` | `false` | Keep native column types. |
| `ATTR_PERSISTENT` | `false` | No persistent connections. |

DSN charset is `utf8mb4`, matching the database and tables.

## Error handling

If the connection cannot be made, `db()` sends a generic
`503` "service is temporarily unavailable" response and stops. The
technical reason is written only to the PHP error log
(`C:\xampp\php\logs\php_error_log`). Credentials, the DSN, exception
messages, stack traces, server details and file paths are never shown
to the browser.

## Prepared statements

From the CRUD phases onward, every user-controlled value must be passed
as a bound parameter to a prepared statement and must never be
concatenated into SQL. Prepared statements protect SQL *data values*;
they do not cover identifiers (table/column names) or clauses like
`LIMIT`, which must be checked against a fixed allow-list, and they do
not replace input validation, authorisation checks or output escaping.
