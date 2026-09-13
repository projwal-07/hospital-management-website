# admin/

Admin-only CRUD screens (Phase 5). Every page calls
`require_role('admin')` server-side; every state change is a
CSRF-protected `POST`.

| File | Purpose |
|------|---------|
| `appointments.php` | Hospital-wide appointment list with status/date filters; update status (same controlled transitions as the doctor view) and notes. No doctor reassignment in Phase 5. |
| `doctors.php` | List doctors (with appointment counts); activate/deactivate; `GET ?action=delete&id=` confirmation then `POST` delete. A doctor with appointments cannot be deleted - deactivate instead. |
| `doctor-form.php` | Add / edit a doctor (rubric form 2). Create writes a `users` row (role literal `'doctor'`) plus a `doctors` row inside a transaction; password is required on create, hashed with `PASSWORD_DEFAULT`. Edit never changes the password. |
| `departments.php` | List departments (with doctor/appointment counts); `GET ?action=delete&id=` confirmation then `POST` delete. A referenced department cannot be deleted. |
| `department-form.php` | Add / edit a department (name, description), server-side validated. |

Foreign-key restricted deletions are caught and shown as friendly
messages; raw database errors are never displayed.
