<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Doctor management CRUD for the admin area (Phase 5).
 *
 * A "doctor" is a users row (role literal 'doctor') plus a doctors row.
 * Creating and editing touch both tables and run inside a transaction.
 * All values are bound; nothing user-controlled is concatenated.
 */

/** List doctors with department and a reference count of their appointments. */
function list_doctors(): array
{
    return db()->query(
        "SELECT d.id, d.user_id, d.department_id, d.specialisation, d.bio, d.is_active,
                u.full_name, u.email, u.phone,
                dep.name AS department_name,
                (SELECT COUNT(*) FROM appointments ap WHERE ap.doctor_id = d.id) AS appointment_count
         FROM doctors d
         JOIN users u        ON u.id = d.user_id
         JOIN departments dep ON dep.id = d.department_id
         ORDER BY u.full_name"
    )->fetchAll();
}

function get_doctor(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT d.id, d.user_id, d.department_id, d.specialisation, d.bio, d.is_active,
                u.full_name, u.email, u.phone
         FROM doctors d
         JOIN users u ON u.id = d.user_id
         WHERE d.id = :id'
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

/** True if $email belongs to a user other than $exceptUserId (0 = none). */
function email_in_use(string $email, int $exceptUserId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = :e AND id <> :x');
    $stmt->bindValue(':e', $email);
    $stmt->bindValue(':x', $exceptUserId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

/**
 * Create a doctor: users row (role literal 'doctor') + doctors row, in a
 * transaction. $data: full_name, email, password (plain), phone,
 * department_id, specialisation, bio, is_active.
 */
function create_doctor(array $data): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $u = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, phone)
             VALUES (:n, :e, :h, 'doctor', :p)"
        );
        $u->bindValue(':n', $data['full_name']);
        $u->bindValue(':e', $data['email']);
        $u->bindValue(':h', password_hash($data['password'], PASSWORD_DEFAULT));
        $u->bindValue(':p', $data['phone'] === '' ? null : $data['phone']);
        $u->execute();

        $userId = (int) $pdo->lastInsertId();

        $d = $pdo->prepare(
            'INSERT INTO doctors (user_id, department_id, specialisation, bio, is_active)
             VALUES (:uid, :dep, :spec, :bio, :active)'
        );
        $d->bindValue(':uid', $userId, PDO::PARAM_INT);
        $d->bindValue(':dep', (int) $data['department_id'], PDO::PARAM_INT);
        $d->bindValue(':spec', $data['specialisation']);
        $d->bindValue(':bio', $data['bio'] === '' ? null : $data['bio']);
        $d->bindValue(':active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $d->execute();

        $pdo->commit();
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'An account with that email address already exists.'];
        }
        error_log('HMS create_doctor: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the doctor could not be created. Please try again.'];
    }
}

/**
 * Update a doctor: users row (name, email, phone) + doctors row
 * (department, specialisation, bio, is_active), in a transaction.
 * Password is never changed here.
 */
function update_doctor(int $doctorId, array $data): array
{
    $doctor = get_doctor($doctorId);
    if ($doctor === null) {
        return ['ok' => false, 'error' => 'Doctor not found.'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $u = $pdo->prepare(
            'UPDATE users SET full_name = :n, email = :e, phone = :p WHERE id = :uid AND role = \'doctor\''
        );
        $u->bindValue(':n', $data['full_name']);
        $u->bindValue(':e', $data['email']);
        $u->bindValue(':p', $data['phone'] === '' ? null : $data['phone']);
        $u->bindValue(':uid', (int) $doctor['user_id'], PDO::PARAM_INT);
        $u->execute();

        $d = $pdo->prepare(
            'UPDATE doctors SET department_id = :dep, specialisation = :spec, bio = :bio, is_active = :active
             WHERE id = :id'
        );
        $d->bindValue(':dep', (int) $data['department_id'], PDO::PARAM_INT);
        $d->bindValue(':spec', $data['specialisation']);
        $d->bindValue(':bio', $data['bio'] === '' ? null : $data['bio']);
        $d->bindValue(':active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $d->bindValue(':id', $doctorId, PDO::PARAM_INT);
        $d->execute();

        $pdo->commit();
        return ['ok' => true];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'An account with that email address already exists.'];
        }
        error_log('HMS update_doctor: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the changes could not be saved. Please try again.'];
    }
}

function set_doctor_active(int $doctorId, bool $active): bool
{
    $stmt = db()->prepare('UPDATE doctors SET is_active = :a WHERE id = :id');
    $stmt->bindValue(':a', $active ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':id', $doctorId, PDO::PARAM_INT);
    $stmt->execute();
    return true;
}

/**
 * Delete a doctor and its user row, only when no appointment references
 * the doctor. Blocked gracefully otherwise.
 */
function delete_doctor(int $doctorId): array
{
    $doctor = get_doctor($doctorId);
    if ($doctor === null) {
        return ['ok' => false, 'error' => 'Doctor not found.'];
    }

    $count = db()->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :id');
    $count->bindValue(':id', $doctorId, PDO::PARAM_INT);
    $count->execute();
    if ((int) $count->fetchColumn() > 0) {
        return [
            'ok' => false,
            'error' => 'This doctor has appointments and cannot be deleted. Deactivate the doctor instead.',
        ];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM doctors WHERE id = :id')
            ->execute([':id' => $doctorId]);
        $pdo->prepare('DELETE FROM users WHERE id = :uid AND role = \'doctor\'')
            ->execute([':uid' => (int) $doctor['user_id']]);
        $pdo->commit();
        return ['ok' => true];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
            return [
                'ok' => false,
                'error' => 'This doctor is still referenced by other records and cannot be deleted. Deactivate the doctor instead.',
            ];
        }
        error_log('HMS delete_doctor: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the doctor could not be deleted.'];
    }
}
