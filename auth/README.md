# auth/

User authentication (Phase 3).

| File | Method(s) | Purpose |
|------|-----------|---------|
| `register.php` | GET, POST | Patient self-registration. Always creates `role = 'patient'`. |
| `login.php` | GET, POST | Verify credentials, start a session, redirect by role. |
| `logout.php` | GET, POST | GET shows a confirmation page; POST (CSRF-protected) ends the session. |

## Key rules

- **Registration always creates a patient.** The `role` column is written
  as the SQL literal `'patient'`; no role, privilege, id or authorisation
  value is read from the form. Admin and doctor accounts are never created
  here.
- Passwords are hashed with `password_hash(..., PASSWORD_DEFAULT)`, checked
  with `password_verify()`, and upgraded with `password_needs_rehash()` on
  login. The code does not assume the default algorithm.
- Every state-changing POST (register, login, logout) requires a valid
  CSRF token.
- All validation runs server-side; HTML5 attributes are only a usability
  aid. Password fields are never re-populated after a failed submission.
- Login failures always return the single message
  "Invalid email or password." and never reveal whether the email exists.
- Duplicate email: a prepared `SELECT` gives a friendly message; the
  `users.email` UNIQUE index is the race-condition backstop. Raw database
  errors are never shown.
- Return-to redirects are accepted only as local paths under `/hms/`.
