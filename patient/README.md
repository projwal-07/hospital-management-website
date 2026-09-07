# patient/

Patient CRUD pages (Phase 5). Web-facing; every page calls
`require_role('patient')` server-side.

| File | Purpose |
|------|---------|
| `book.php` | Appointment booking form (rubric form 1). Department -> active doctor in that department -> weekday date (tomorrow … +60 days) -> fixed 30-minute slot (09:00-16:30) -> reason (10-500 chars). All rules validated server-side. New appointments are inserted with `status` literal `'pending'` inside a transaction that re-checks the slot. `patient_id` comes from `current_user()`, never the request. |
| `appointments.php` | Lists all of the patient's appointments and lets them cancel an own, pending/confirmed, non-past appointment. `GET ?action=cancel&id=` shows a confirmation panel; the cancel itself is a CSRF-protected `POST`. Ownership is enforced in SQL (`AND patient_id = :uid`). |

Cancelled appointments free their slot for re-booking (the conflict
check only looks at `pending` / `confirmed`).
