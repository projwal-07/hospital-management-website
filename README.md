# Evergreen Community Hospital — Hospital Management System

## Assignment context

ICT726 Web Development, Assignment 4. This project builds on the Assignment 3
static website (preserved unchanged on the `main` branch) by adding user
authentication, a MySQL database, CRUD functionality, and server-side security.
Development for Assignment 4 happens on the `assignment-4` branch.

## Technology stack

- PHP 8.2 — plain PHP with small reusable include/helper files, no framework
- MySQL — accessed through PDO using prepared statements
- HTML5, CSS3, JavaScript
- Apache and MySQL provided by XAMPP

## User roles

| Role    | Responsibilities |
|---------|------------------|
| admin   | Manage doctors and departments; view and manage all appointments; access the admin dashboard. |
| doctor  | Access the doctor dashboard; view appointments assigned to them; update the status and details of those appointments. No access to admin functionality. |
| patient | Register and log in; access the patient dashboard; book appointments; view their own appointments; cancel their own eligible appointments. Cannot access another patient's records. |

All authorisation is enforced server-side, not only by hiding navigation links.

## Local setup (XAMPP)

1. Install XAMPP and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Place this project folder at `c:\xampp\htdocs\hms`.
3. Open `http://localhost/hms/` in a browser.
4. Import the database: see `database/README.md` (import `database/schema.sql`
   then `database/seed.sql` in phpMyAdmin as `root`).
5. Create the local database config: copy `config/db.example.php` to
   `config/db.php` and enter your `hms_app` password. See `config/README.md`.
   `config/db.php` is git-ignored.

The existing static pages still work without steps 4-5; those steps are
required for the dynamic (PHP/MySQL) features being added in Assignment 4.

## Project structure

    hms/
    |-- *.html            Assignment 3 pages (converted to PHP in a later phase)
    |-- css/              Shared stylesheet
    |-- js/               Shared JavaScript
    |-- images/           Site images (free-licence stock; see images/IMAGE-SOURCES.txt)
    |-- config/           Database connection and application configuration
    |-- includes/         Shared header, navigation, footer and helper files
    |-- auth/             Registration, login and logout
    |-- dashboard/        Role-based dashboards (admin, doctor, patient)
    |-- admin/            Admin CRUD screens (doctors, departments, appointments)
    |-- database/         SQL schema, seed data and ER diagram

## Current development status

- **Phase 0 (complete):** development branch, folder structure, `.gitignore`,
  Apache folder protection, and this documentation.
- **Phase 1 (complete):** MySQL schema (`users`, `departments`, `doctors`,
  `appointments`), seed data and ER diagram — see `database/`.
- **Phase 2 (complete):** secure PDO connection layer — `config/db.example.php`,
  local git-ignored `config/db.php`, and `db()` in `includes/database.php`.
- **Phase 3 (complete):** authentication — patient self-registration, login,
  logout, hardened sessions, CSRF protection, server-side validation, and
  server-enforced role-based access. Helpers in `includes/auth.php`,
  `includes/csrf.php`, `includes/validation.php`; shared page partials in
  `includes/header.php`, `includes/nav.php`, `includes/footer.php`; pages in
  `auth/` and role dashboard stubs in `dashboard/`.
- **Phase 4 (complete):** read-only role dashboards — patient, doctor and admin
  views with status counts, upcoming appointments and recent activity. Queries
  in `includes/dashboard.php`; styling in `css/dashboard.css` (added to, not
  replacing, `css/style.css`).
- **Phase 5 (complete):** CRUD and validated forms — patient appointment
  booking/list/cancel (`patient/`), doctor assigned-appointments and status
  updates (`doctor/`), admin management of appointments, doctors and
  departments (`admin/`). Data helpers in `includes/appointments.php`,
  `includes/doctors.php`, `includes/departments.php`. Booking is Mon–Fri,
  fixed 30-minute slots 09:00–16:30, tomorrow … +60 days, with a
  transaction-guarded slot-conflict check (cancelled slots are reusable).
- **Next:** Phase 6 — validation/error-handling hardening and login rate
  limiting (not yet started).

The two rubric demonstration forms are **patient appointment booking**
(`patient/book.php`) and **admin add/edit doctor** (`admin/doctor-form.php`).

### Known remaining security-hardening items

- Login throttling / rate limiting is deferred to Phase 6.

The Assignment 3 static website remains fully functional and visually unchanged.
