# includes/

Shared PHP building blocks reused across the dynamic pages.

Direct browser access to this folder is blocked by `.htaccess`.

## Helpers (Phase 3)

| File | Contents |
|------|----------|
| `database.php` | `db()` - the shared PDO connection (Phase 2). |
| `csrf.php` | `csrf_token()`, `csrf_field()`, `csrf_regenerate()`, `csrf_verify()`. One random token per session, compared with `hash_equals()`. |
| `validation.php` | `v_required`, `v_length`, `v_email`, `v_match`, `v_password`, `v_phone_optional`, `v_in_set`, `v_note_optional` - server-side field checks that fill an `$errors` array. |
| `auth.php` | Session bootstrap and authorisation: `auth_boot()`, `current_user()`, `is_logged_in()`, `login_user()`, `logout_user()`, `require_login()`, `require_role()`, `require_guest()`, `dashboard_url_for()`, `redirect()`, `safe_return_to()`, `flash_set()/flash_get()`, `e()`. |
| `dashboard.php` (Phase 4) | Read-only dashboard queries as named functions (`patient_*`, `doctor_*`, `admin_*`) plus formatting helpers (`fmt_date`, `fmt_time`, `fmt_datetime`, `status_label`, `status_class`). All use `db()` with bound integer ids/limits; no writes. |
| `appointments.php` (Phase 5) | Booking rules (`booking_slots`, `booking_date_bounds`, `is_valid_booking_date`, `is_valid_slot`, `doctor_active_in_department`, `slot_is_free`) and appointment CRUD (`book_appointment` — transaction; `list_*`, `appointment_for_patient/doctor/any`, `cancel_appointment_as_patient`, `update_appointment_status`, `allowed_transitions`). |
| `doctors.php` (Phase 5) | Doctor CRUD for admin: `list_doctors`, `get_doctor`, `email_in_use`, `create_doctor`/`update_doctor` (transactions), `set_doctor_active`, `delete_doctor` (blocked when referenced). |
| `departments.php` (Phase 5) | Department CRUD for admin: `list_departments_with_counts`, `get_department`, `create_department`, `update_department`, `delete_department` (blocked when referenced). |

`auth.php` pulls in `database.php`, `csrf.php` and `validation.php`, so a
page only needs:

```php
require_once __DIR__ . '/../includes/auth.php';
auth_boot();
```

## Shared page partials (Phase 3; extended in Phase 7)

| File | Purpose |
|------|---------|
| `header.php` | Opens the document, links `css/style.css`, `css/dashboard.css` and `js/script.js`, renders the site header, and prints any one-time flash message. Expects `$page_title`. |
| `nav.php` | Primary navigation. Signed out: Log in / Register. Signed in: Dashboard plus the destinations for that role (patient: My appointments, Book appointment; doctor: My appointments; admin: Appointments, Doctors, Departments), then Log out. Visibility is a convenience only. |
| `footer.php` | Closes `<main>` and the document. |

These are used **only** by the pages in `auth/`, `dashboard/`, `patient/`,
`doctor/` and `admin/`. The six existing public `.html` pages are
untouched until Phase 7.

## Authorisation model

`require_role()` decides access from the **current database role**, looked
up by user id on each request (`current_user()`), not from any value in a
cookie or form. Navigation visibility is a convenience only.
