# dashboard/

Role-based dashboards shown after login.

| File | Required role | Content |
|------|---------------|---------|
| `patient.php` | `patient` | Status counts, next 5 upcoming appointments, last 5 past/closed appointments. |
| `doctor.php` | `doctor` | Status counts, today's appointments, next 10 upcoming, 5 most recently changed. Friendly message if the account has no `doctors` profile row. |
| `admin.php` | `admin` | Users by role, department count, active/total doctors, appointments by status, today's count, next 10 hospital-wide appointments, latest 5 patient registrations, 5 most recent appointment changes. |

## Rules (Phases 3-4)

- `require_role(...)` enforces the correct role **server-side**, from the
  current database role - not a browser-supplied value. Logged-out
  visitors are redirected to login; a signed-in visitor with the wrong
  role receives HTTP 403.
- Responses are sent with `Cache-Control: no-store`.
- **Read-only.** These pages run `SELECT` queries only (via
  `includes/dashboard.php`). No inserts, updates or deletes.
- Patient data is always scoped to the authenticated patient's
  `users.id`; doctor data to the `doctors.id` derived from the
  authenticated `users.id`. No `patient_id` / `doctor_id` / `user_id`
  is accepted from the query string or a form.
- "Latest patient registrations" and "recent appointment changes" are
  built from the existing `created_at` / `updated_at` columns. They are
  **not** a full audit trail.

## Later phases

Appointment booking, status updates and management pages are Phase 5.
