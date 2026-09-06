# database/

Database definition, seed data and documentation for the Hospital
Management System (ICT726 Assignment 4).

Direct browser access to this folder is blocked by `.htaccess`.

## Files

| File | Purpose |
|------|---------|
| `schema.sql` | Creates the `hms` database and the four core tables (structure only). Safe to re-run: it drops and recreates the tables. |
| `seed.sql` | Fictional demonstration data. Clears the tables, resets AUTO_INCREMENT, then inserts departments, users, doctors and appointments. Safe to re-run. Import **after** `schema.sql`. |
| `ER-diagram.mmd` | Entity-relationship diagram source (Mermaid). Render to PNG/SVG with https://mermaid.live or a Mermaid VS Code extension. |

## Database at a glance

- **Name:** `hms`  **Engine:** InnoDB  **Charset/Collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Tables:** `users`, `departments`, `doctors`, `appointments`

| Table | Used for |
|-------|----------|
| `users` | Every account (admin, doctor, patient). Authentication and the role check behind protected pages. |
| `departments` | Hospital departments. Admin CRUD; referenced by doctors and appointments. |
| `doctors` | One-to-one professional profile for a `users` row with `role = 'doctor'`. Admin CRUD. `is_active` (1/0) is a soft on/off switch for booking. |
| `appointments` | Primary CRUD entity. Patients book and cancel their own; doctors update those assigned to them; admin manages all. |

## Relationships

```
departments (1) ---< doctors      (M)     doctors.department_id      -> departments.id
users       (1) ---- doctors      (1)     doctors.user_id           -> users.id   (UNIQUE, 1:1)
users       (1) ---< appointments (M)     appointments.patient_id   -> users.id
doctors     (1) ---< appointments (M)     appointments.doctor_id    -> doctors.id
departments (1) ---< appointments (M)     appointments.department_id -> departments.id
```

All foreign keys use `ON UPDATE CASCADE ON DELETE RESTRICT`. Deletions are
handled deliberately in the application (soft-disable a doctor, block
deletion of a department that is in use, soft-cancel an appointment)
rather than cascading automatically.

## Design decisions recorded in Phase 1

1. **Booking flow is department -> doctor -> date/time -> appointment.**
   `appointments.department_id` is stored explicitly (not derived from the
   doctor) so the department chosen at booking time is preserved even if
   the doctor later changes department. Later PHP must verify that the
   selected doctor actually belongs to the selected department.

2. **Double-booking is not a database `UNIQUE` constraint.**
   A later phase will reject a new booking when the same `doctor_id` +
   `appointment_date` + `appointment_time` already has an appointment with
   status `pending` or `confirmed`. A `cancelled` appointment does **not**
   permanently block the slot. The non-unique index
   `idx_appt_doctor_slot (doctor_id, appointment_date, appointment_time)`
   exists to make that lookup fast. No such PHP logic is implemented yet.

3. **`appointments.patient_id` must reference a user whose `role` is
   `patient`.** A foreign key alone cannot express this condition, so it
   is validated server-side in PHP when a booking is created.

4. **`doctors.is_active`** is `TINYINT(1)`: `1` = active, `0` = inactive.

## Fictional local development credentials

All seed accounts and data are **fictional** and for local
demonstration/testing only. No real personal information is used. Only
bcrypt password hashes are stored in `seed.sql`; the plaintext values
below are documented solely so the database can be tested.

| Role | Email | Fictional local dev password |
|------|-------|------------------------------|
| admin | `admin@hms.local` | `Admin123!` |
| doctor | `alan.reyes@hms.local` | `Doctor123!` |
| doctor | `bianca.osei@hms.local` | `Doctor123!` |
| doctor | `charles.ng@hms.local` | `Doctor123!` (this doctor is inactive) |
| patient | `jordan.miller@example.local` | `Patient123!` |
| patient | `priya.nair@example.local` | `Patient123!` |

Remove or change these accounts before any non-local deployment.

## Importing with phpMyAdmin (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin/`.
3. **Import the schema:**
   - Click the **SQL** tab (top menu), or use **Import**.
   - If using **Import**: click *Choose File*, select
     `c:\xampp\htdocs\hms\database\schema.sql`, leave defaults, click
     **Import** (or **Go**).
   - `schema.sql` creates the `hms` database itself, so you do **not**
     need to create it first.
4. **Import the seed data:**
   - In the left sidebar, click the **`hms`** database to select it.
   - Open the **Import** tab, choose
     `c:\xampp\htdocs\hms\database\seed.sql`, click **Import** (or **Go**).
5. You should see `hms` in the sidebar with four tables: `appointments`,
   `departments`, `doctors`, `users`.

### Command-line alternative (optional)

```
cd c:\xampp\mysql\bin
mysql -u root < c:\xampp\htdocs\hms\database\schema.sql
mysql -u root hms < c:\xampp\htdocs\hms\database\seed.sql
```

## Verification queries

Run these in the phpMyAdmin **SQL** tab (with `hms` selected) after importing.

```sql
-- 1. The four tables exist, all InnoDB / utf8mb4
SELECT table_name, engine, table_collation
FROM information_schema.tables
WHERE table_schema = 'hms'
ORDER BY table_name;

-- 2. Primary keys
SELECT table_name, column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'hms' AND constraint_name = 'PRIMARY'
ORDER BY table_name;

-- 3. Foreign keys and what they reference
SELECT table_name, column_name, constraint_name,
       referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'hms' AND referenced_table_name IS NOT NULL
ORDER BY table_name, constraint_name;

-- 4. Foreign key delete/update rules
SELECT constraint_name, table_name, referenced_table_name,
       update_rule, delete_rule
FROM information_schema.referential_constraints
WHERE constraint_schema = 'hms'
ORDER BY table_name;

-- 5. Unique constraints
SELECT table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns
FROM information_schema.statistics
WHERE table_schema = 'hms' AND non_unique = 0
GROUP BY table_name, index_name
ORDER BY table_name;

-- 6. Seed row counts (expect: departments 6, users 6, doctors 3, appointments 8)
SELECT 'departments' AS table_name, COUNT(*) AS rows_count FROM departments
UNION ALL SELECT 'users',        COUNT(*) FROM users
UNION ALL SELECT 'doctors',      COUNT(*) FROM doctors
UNION ALL SELECT 'appointments', COUNT(*) FROM appointments;

-- 7. Users by role (expect: admin 1, doctor 3, patient 2)
SELECT role, COUNT(*) AS n FROM users GROUP BY role ORDER BY role;

-- 8. Appointments by status (expect: pending 2, confirmed 2, completed 3, cancelled 1)
SELECT status, COUNT(*) AS n FROM appointments GROUP BY status ORDER BY status;

-- 9. Readable join across all four tables
SELECT a.id,
       pu.full_name  AS patient,
       du.full_name  AS doctor,
       d.name        AS department,
       a.appointment_date, a.appointment_time, a.status
FROM appointments a
JOIN users        pu ON pu.id = a.patient_id
JOIN doctors      dr ON dr.id = a.doctor_id
JOIN users        du ON du.id = dr.user_id
JOIN departments  d  ON d.id  = a.department_id
ORDER BY a.appointment_date, a.appointment_time;

-- 10. Data-integrity spot check: every appointment's doctor is really in
--     the appointment's department (expect 0 rows = no mismatch)
SELECT a.id
FROM appointments a
JOIN doctors dr ON dr.id = a.doctor_id
WHERE dr.department_id <> a.department_id;
```

## OPTIONAL: dedicated least-privilege application user

Not required for local XAMPP testing (you may connect as `root` locally).
For a closer-to-production setup, the application should connect with an
account that has only the privileges it actually uses (read/write rows,
no schema changes):

```sql
-- Run once, as root, in phpMyAdmin's SQL tab.
CREATE USER IF NOT EXISTS 'hms_app'@'localhost'
  IDENTIFIED BY 'change-this-local-dev-only';

GRANT SELECT, INSERT, UPDATE, DELETE ON `hms`.* TO 'hms_app'@'localhost';

FLUSH PRIVILEGES;
```

Schema imports (`schema.sql` / `seed.sql`) are still run as `root`. When
Phase 2 adds `config/db.php`, that file can point at either `root` (local
default) or `hms_app`.
