<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Read-only queries and formatting helpers for the role dashboards
 * (Phase 4).
 *
 * - Every query uses the shared db() PDO connection.
 * - Any value is passed as a bound parameter; nothing user-controlled
 *   is concatenated into SQL. The only bound values here are integer
 *   ids and row limits.
 * - Ownership scoping (patient users.id, doctors.id) is supplied by the
 *   calling page from the authenticated session - never from a request
 *   parameter.
 * - Nothing in this file writes to the database.
 */

/* =====================================================================
 * Formatting helpers
 * =================================================================== */

function fmt_date(string $ymd): string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $d ? $d->format('D j M Y') : $ymd;
}

function fmt_time(string $his): string
{
    $t = DateTimeImmutable::createFromFormat('!H:i:s', $his)
        ?: DateTimeImmutable::createFromFormat('!H:i', $his);
    return $t ? $t->format('g:i a') : $his;
}

function fmt_datetime(string $dt): string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dt);
    return $d ? $d->format('j M Y, g:i a') : $dt;
}

/** Human label for a status value (values come from the appointments ENUM). */
function status_label(string $status): string
{
    return ucfirst($status);
}

/** CSS classes for a status pill; unknown values fall back to "pending". */
function status_class(string $status): string
{
    $known = ['pending', 'confirmed', 'completed', 'cancelled'];
    return 'dash-status dash-status--' . (in_array($status, $known, true) ? $status : 'pending');
}

/** A zero-filled status count map, so every status always has a number. */
function status_count_template(): array
{
    return ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
}

/**
 * Shared SELECT for one joined appointment row (patient name, doctor
 * name, department name). Append WHERE / ORDER BY / LIMIT per caller.
 */
const APPT_SELECT = '
    SELECT a.id, a.appointment_date, a.appointment_time, a.status,
           a.reason, a.notes, a.created_at, a.updated_at,
           pu.full_name AS patient_name,
           du.full_name AS doctor_name,
           d.name       AS department_name
    FROM appointments a
    JOIN users       pu ON pu.id = a.patient_id
    JOIN doctors     dr ON dr.id = a.doctor_id
    JOIN users       du ON du.id = dr.user_id
    JOIN departments d  ON d.id  = a.department_id
';

/* =====================================================================
 * Patient dashboard - scoped to the authenticated patient's users.id
 * =================================================================== */

function patient_status_counts(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT status, COUNT(*) AS n FROM appointments WHERE patient_id = :uid GROUP BY status'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $counts = status_count_template();
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['n'];
    }
    return $counts;
}

function patient_upcoming(int $userId, int $limit = 5): array
{
    $stmt = db()->prepare(APPT_SELECT . "
        WHERE a.patient_id = :uid
          AND a.appointment_date >= CURDATE()
          AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT :lim
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function patient_history(int $userId, int $limit = 5): array
{
    $stmt = db()->prepare(APPT_SELECT . "
        WHERE a.patient_id = :uid
          AND (a.appointment_date < CURDATE() OR a.status IN ('completed', 'cancelled'))
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* =====================================================================
 * Doctor dashboard - scoped to the doctors.id derived from users.id
 * =================================================================== */

function doctor_id_for_user(int $userId): ?int
{
    $stmt = db()->prepare('SELECT id FROM doctors WHERE user_id = :uid');
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function doctor_status_counts(int $doctorId): array
{
    $stmt = db()->prepare(
        'SELECT status, COUNT(*) AS n FROM appointments WHERE doctor_id = :did GROUP BY status'
    );
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    $stmt->execute();

    $counts = status_count_template();
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['n'];
    }
    return $counts;
}

function doctor_today(int $doctorId): array
{
    $stmt = db()->prepare(APPT_SELECT . '
        WHERE a.doctor_id = :did
          AND a.appointment_date = CURDATE()
        ORDER BY a.appointment_time ASC
    ');
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function doctor_upcoming(int $doctorId, int $limit = 10): array
{
    $stmt = db()->prepare(APPT_SELECT . "
        WHERE a.doctor_id = :did
          AND a.appointment_date >= CURDATE()
          AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT :lim
    ");
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function doctor_recent_changes(int $doctorId, int $limit = 5): array
{
    $stmt = db()->prepare(APPT_SELECT . '
        WHERE a.doctor_id = :did
        ORDER BY a.updated_at DESC, a.id DESC
        LIMIT :lim
    ');
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* =====================================================================
 * Admin dashboard - hospital-wide (admin role enforced by the page)
 * =================================================================== */

function admin_user_role_counts(): array
{
    $counts = ['admin' => 0, 'doctor' => 0, 'patient' => 0];
    foreach (db()->query('SELECT role, COUNT(*) AS n FROM users GROUP BY role')->fetchAll() as $row) {
        $counts[$row['role']] = (int) $row['n'];
    }
    return $counts;
}

function admin_department_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM departments')->fetchColumn();
}

function admin_doctor_counts(): array
{
    $row = db()->query('SELECT COALESCE(SUM(is_active), 0) AS active, COUNT(*) AS total FROM doctors')->fetch();
    return ['active' => (int) $row['active'], 'total' => (int) $row['total']];
}

function admin_appointment_status_counts(): array
{
    $counts = status_count_template();
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM appointments GROUP BY status')->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['n'];
    }
    return $counts;
}

function admin_today_appointment_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()')->fetchColumn();
}

function admin_upcoming(int $limit = 10): array
{
    $stmt = db()->prepare(APPT_SELECT . "
        WHERE a.appointment_date >= CURDATE()
          AND a.status IN ('pending', 'confirmed')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function admin_latest_patient_registrations(int $limit = 5): array
{
    $stmt = db()->prepare("
        SELECT id, full_name, email, created_at
        FROM users
        WHERE role = 'patient'
        ORDER BY created_at DESC, id DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function admin_recent_appointment_changes(int $limit = 5): array
{
    $stmt = db()->prepare(APPT_SELECT . '
        ORDER BY a.updated_at DESC, a.id DESC
        LIMIT :lim
    ');
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
