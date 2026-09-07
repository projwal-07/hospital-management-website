# doctor/

Doctor CRUD pages (Phase 5). Web-facing; every page calls
`require_role('doctor')` server-side.

| File | Purpose |
|------|---------|
| `appointments.php` | Lists only the appointments assigned to this doctor, with a status filter, and lets the doctor update status and notes. |

The doctor's `doctors.id` is derived from the authenticated `users.id`
via `doctor_id_for_user()`; `doctor_id` is never taken from the
request. Every read and write is scoped with `AND doctor_id = :did`, and
the row is re-fetched with that predicate before any update, so a
doctor cannot touch another doctor's appointment.

Status changes follow the controlled transition map:
`pending -> confirmed | cancelled`, `confirmed -> completed | cancelled`;
`completed` and `cancelled` are terminal. Invalid transitions are
rejected server-side. If the account has no `doctors` profile row a
friendly message is shown and no data is queried.
