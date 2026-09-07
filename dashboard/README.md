# dashboard/

Role-based dashboards shown after login.

| File | Required role |
|------|---------------|
| `patient.php` | `patient` |
| `doctor.php` | `doctor` |
| `admin.php` | `admin` |

## Phase 3 (current)

Each page is a **stub** that only proves the authentication and
authorisation chain:

- `require_role(...)` enforces the correct role server-side, using the
  current database role (not a browser-supplied value);
- a logged-out visitor is redirected to login;
- a logged-in visitor with the wrong role gets HTTP 403;
- the page shows the current user's name and role and a log-out link;
- responses are sent with `Cache-Control: no-store`.

## Phase 4 (next)

Full dashboard functionality (appointment lists, status updates,
management links) will be added on top of these stubs.
