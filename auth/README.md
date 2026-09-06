# auth/

User authentication (Phase 3).

- `register.php` — create a patient account
- `login.php` — start an authenticated session
- `logout.php` — end the session

Passwords are stored with `password_hash()` and checked with
`password_verify()`. All form input is validated server-side.
