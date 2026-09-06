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
4. Database import instructions will be added in a later phase — no database is
   required to run the site yet.

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
- **Next:** Phase 1 — MySQL database schema (not yet started).

The Assignment 3 static website remains fully functional and visually unchanged.
