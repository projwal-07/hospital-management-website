<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/dashboard.php'; // fmt_* and status_* helpers

/**
 * Appointment booking rules and CRUD (Phase 5).
 *
 * - Every query uses the shared db() PDO connection with bound
 *   parameters. Nothing user-controlled is concatenated into SQL.
 * - Ownership (patient users.id, doctors.id) is passed in by the caller
 *   from the authenticated session, never from the request.
 * - GET pages call the list_* / *_for_* readers; only the book_* /
 *   cancel_* / update_* writers change state, and pages call those only
 *   from a CSRF-verified POST.
 */

/* ---------------------------------------------------------------------
 * Booking rules
 * ------------------------------------------------------------------- */

/** Fixed 30-minute clinic slots, 09:00 - 16:30 (stored as HH:MM:00). */
const CLINIC_SLOTS = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30',
    '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
];
const BOOKING_MIN_DAYS = 1;   // earliest bookable day = tomorrow
const BOOKING_MAX_DAYS = 60;  // latest bookable day = today + 60

/** Slot value => display label, e.g. '09:00' => '9:00 am'. */
function booking_slots(): array
{
    $out = [];
    foreach (CLINIC_SLOTS as $s) {
        $out[$s] = fmt_time($s . ':00');
    }
    return $out;
}

/** ['min' => Y-m-d (tomorrow), 'max' => Y-m-d (today + 60)] for the date input. */
function booking_date_bounds(): array
{
    $today = new DateTimeImmutable('today');
    return [
        'min' => $today->modify('+' . BOOKING_MIN_DAYS . ' day')->format('Y-m-d'),
        'max' => $today->modify('+' . BOOKING_MAX_DAYS . ' day')->format('Y-m-d'),
    ];
}

/** True if $ymd is a real weekday date within the booking window. */
function is_valid_booking_date(string $ymd): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    if (!$d || $d->format('Y-m-d') !== $ymd) {
        return false;
    }
    if ((int) $d->format('N') >= 6) {          // 6 = Sat, 7 = Sun
        return false;
    }
    $bounds = booking_date_bounds();
    return $ymd >= $bounds['min'] && $ymd <= $bounds['max'];
}

/** True if $hm ('HH:MM') is one of the fixed slots. */
function is_valid_slot(string $hm): bool
{
    return in_array($hm, CLINIC_SLOTS, true);
}

/** True if the doctor exists, is active, and belongs to the department. */
function doctor_active_in_department(int $doctorId, int $departmentId): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM doctors WHERE id = :d AND department_id = :dep AND is_active = 1'
    );
    $stmt->bindValue(':d', $doctorId, PDO::PARAM_INT);
    $stmt->bindValue(':dep', $departmentId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

function department_exists(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM departments WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

/**
 * True if the slot has no pending/confirmed appointment. Cancelled and
 * completed appointments do NOT block the slot.
 */
function slot_is_free(int $doctorId, string $date, string $hm, ?int $excludeId = null): bool
{
    $sql = "SELECT 1 FROM appointments
            WHERE doctor_id = :d AND appointment_date = :dt AND appointment_time = :tm
              AND status IN ('pending', 'confirmed')";
    if ($excludeId !== null) {
        $sql .= ' AND id <> :ex';
    }
    $sql .= ' LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':d', $doctorId, PDO::PARAM_INT);
    $stmt->bindValue(':dt', $date);
    $stmt->bindValue(':tm', $hm . ':00');
    if ($excludeId !== null) {
        $stmt->bindValue(':ex', $excludeId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchColumn() === false;
}

/* ---------------------------------------------------------------------
 * Selection lists for the booking form
 * ------------------------------------------------------------------- */

function all_departments(): array
{
    return db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
}

/** Active doctors with their department, for the booking form select. */
function active_doctors(): array
{
    return db()->query(
        'SELECT d.id, d.department_id, d.specialisation,
                u.full_name, dep.name AS department_name
         FROM doctors d
         JOIN users u        ON u.id = d.user_id
         JOIN departments dep ON dep.id = d.department_id
         WHERE d.is_active = 1
         ORDER BY dep.name, u.full_name'
    )->fetchAll();
}

/* ---------------------------------------------------------------------
 * Create (patient booking) - transaction with a final conflict re-check
 * ------------------------------------------------------------------- */

/**
 * Insert a new appointment (status literal 'pending') for $patientId.
 * Re-checks the slot inside the transaction immediately before INSERT.
 *
 * Returns the new appointment id. Throws DomainException with a
 * user-safe message if the slot is taken or the write fails.
 *
 * Concurrency note: there is deliberately no UNIQUE(doctor_id, date,
 * time) constraint (so cancelled slots can be reused). The transaction
 * plus SELECT ... FOR UPDATE makes a double-booking very unlikely but
 * not database-guaranteed.
 */
function book_appointment(
    int $patientId,
    int $doctorId,
    int $departmentId,
    string $date,
    string $hm,
    string $reason
): int {
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $check = $pdo->prepare(
            "SELECT 1 FROM appointments
             WHERE doctor_id = :d AND appointment_date = :dt AND appointment_time = :tm
               AND status IN ('pending', 'confirmed')
             LIMIT 1 FOR UPDATE"
        );
        $check->bindValue(':d', $doctorId, PDO::PARAM_INT);
        $check->bindValue(':dt', $date);
        $check->bindValue(':tm', $hm . ':00');
        $check->execute();

        if ($check->fetchColumn() !== false) {
            $pdo->rollBack();
            throw new DomainException('That time is no longer available. Please choose another slot.');
        }

        $insert = $pdo->prepare(
            "INSERT INTO appointments
                (patient_id, doctor_id, department_id, appointment_date, appointment_time, reason, status)
             VALUES (:p, :d, :dep, :dt, :tm, :reason, 'pending')"
        );
        $insert->bindValue(':p', $patientId, PDO::PARAM_INT);
        $insert->bindValue(':d', $doctorId, PDO::PARAM_INT);
        $insert->bindValue(':dep', $departmentId, PDO::PARAM_INT);
        $insert->bindValue(':dt', $date);
        $insert->bindValue(':tm', $hm . ':00');
        $insert->bindValue(':reason', $reason);
        $insert->execute();

        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (DomainException $e) {
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('HMS book_appointment: ' . $e->getMessage());
        throw new DomainException('Sorry, the booking could not be completed. Please try again.');
    }
}

/* ---------------------------------------------------------------------
 * Read
 * ------------------------------------------------------------------- */

function list_patient_appointments(int $patientId): array
{
    $stmt = db()->prepare(APPT_SELECT . '
        WHERE a.patient_id = :uid
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ');
    $stmt->bindValue(':uid', $patientId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function appointment_for_patient(int $id, int $patientId): ?array
{
    $stmt = db()->prepare(APPT_SELECT . ' WHERE a.id = :id AND a.patient_id = :uid');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $patientId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

function list_doctor_appointments(int $doctorId, string $statusFilter = 'all'): array
{
    $sql = APPT_SELECT . ' WHERE a.doctor_id = :did';
    if (in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $sql .= ' AND a.status = :st';
    }
    $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    if (in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $stmt->bindValue(':st', $statusFilter);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

function appointment_for_doctor(int $id, int $doctorId): ?array
{
    $stmt = db()->prepare(APPT_SELECT . ' WHERE a.id = :id AND a.doctor_id = :did');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

function list_all_appointments(string $statusFilter = 'all', string $from = '', string $to = ''): array
{
    $sql = APPT_SELECT . ' WHERE 1 = 1';
    $params = [];

    if (in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $sql .= ' AND a.status = :st';
        $params[':st'] = $statusFilter;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
        $sql .= ' AND a.appointment_date >= :from';
        $params[':from'] = $from;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
        $sql .= ' AND a.appointment_date <= :to';
        $params[':to'] = $to;
    }
    $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 200';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function appointment_any(int $id): ?array
{
    $stmt = db()->prepare(APPT_SELECT . ' WHERE a.id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ---------------------------------------------------------------------
 * Status transitions
 * ------------------------------------------------------------------- */

/** Allowed next statuses from a given status (same rules for doctor and admin). */
function allowed_transitions(string $current): array
{
    return match ($current) {
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        default     => [], // completed / cancelled are terminal
    };
}

function transition_allowed(string $from, string $to): bool
{
    return $from === $to || in_array($to, allowed_transitions($from), true);
}

/* ---------------------------------------------------------------------
 * Update / cancel (writers - call only from a CSRF-verified POST)
 * ------------------------------------------------------------------- */

/** Patient cancels their own pending/confirmed, non-past appointment. */
function cancel_appointment_as_patient(int $id, int $patientId, string $reason = ''): array
{
    $appt = appointment_for_patient($id, $patientId);
    if ($appt === null) {
        return ['ok' => false, 'error' => 'Appointment not found.'];
    }
    if (!in_array($appt['status'], ['pending', 'confirmed'], true)) {
        return ['ok' => false, 'error' => 'This appointment can no longer be cancelled.'];
    }
    if ($appt['appointment_date'] < date('Y-m-d')) {
        return ['ok' => false, 'error' => 'Past appointments cannot be cancelled.'];
    }

    $notes = trim($reason) !== '' ? trim($reason) : $appt['notes'];

    $stmt = db()->prepare(
        "UPDATE appointments SET status = 'cancelled', notes = :notes
         WHERE id = :id AND patient_id = :uid AND status IN ('pending', 'confirmed')"
    );
    $stmt->bindValue(':notes', ($notes === null || $notes === '') ? null : $notes);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $patientId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount() === 1
        ? ['ok' => true]
        : ['ok' => false, 'error' => 'This appointment can no longer be cancelled.'];
}

/** Shared status/notes update with an ownership-guarded WHERE. */
function update_appointment_status(int $id, string $newStatus, string $notes, ?int $doctorId): array
{
    $appt = $doctorId === null ? appointment_any($id) : appointment_for_doctor($id, $doctorId);
    if ($appt === null) {
        return ['ok' => false, 'error' => 'Appointment not found.'];
    }
    if (!transition_allowed($appt['status'], $newStatus)) {
        return ['ok' => false, 'error' => 'That status change is not allowed.'];
    }

    // The edit form pre-fills the notes box, so take it as submitted:
    // an empty box clears the notes.
    $notesVal = trim($notes);

    $sql = 'UPDATE appointments SET status = :st, notes = :notes WHERE id = :id';
    if ($doctorId !== null) {
        $sql .= ' AND doctor_id = :did';
    }

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':st', $newStatus);
    $stmt->bindValue(':notes', ($notesVal === null || $notesVal === '') ? null : $notesVal);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if ($doctorId !== null) {
        $stmt->bindValue(':did', $doctorId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return ['ok' => true];
}
