<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Department management CRUD for the admin area (Phase 5).
 * All values are bound; nothing user-controlled is concatenated.
 */

function list_departments_with_counts(): array
{
    return db()->query(
        'SELECT dep.id, dep.name, dep.description, dep.created_at,
                (SELECT COUNT(*) FROM doctors d      WHERE d.department_id = dep.id) AS doctor_count,
                (SELECT COUNT(*) FROM appointments a WHERE a.department_id = dep.id) AS appointment_count
         FROM departments dep
         ORDER BY dep.name'
    )->fetchAll();
}

function get_department(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name, description FROM departments WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_department(string $name, string $description): array
{
    try {
        $stmt = db()->prepare('INSERT INTO departments (name, description) VALUES (:n, :d)');
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':d', $description === '' ? null : $description);
        $stmt->execute();
        return ['ok' => true, 'id' => (int) db()->lastInsertId()];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'A department with that name already exists.'];
        }
        error_log('HMS create_department: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the department could not be created.'];
    }
}

function update_department(int $id, string $name, string $description): array
{
    try {
        $stmt = db()->prepare('UPDATE departments SET name = :n, description = :d WHERE id = :id');
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':d', $description === '' ? null : $description);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return ['ok' => true];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'A department with that name already exists.'];
        }
        error_log('HMS update_department: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the changes could not be saved.'];
    }
}

/**
 * Delete a department only when no doctor and no appointment reference
 * it. Blocked gracefully otherwise (including a foreign-key error).
 */
function delete_department(int $id): array
{
    $dep = get_department($id);
    if ($dep === null) {
        return ['ok' => false, 'error' => 'Department not found.'];
    }

    $c = db()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM doctors      WHERE department_id = :id1) AS doctors,
            (SELECT COUNT(*) FROM appointments WHERE department_id = :id2) AS appts'
    );
    $c->bindValue(':id1', $id, PDO::PARAM_INT);
    $c->bindValue(':id2', $id, PDO::PARAM_INT);
    $c->execute();
    $refs = $c->fetch();

    if ((int) $refs['doctors'] > 0 || (int) $refs['appts'] > 0) {
        return [
            'ok' => false,
            'error' => 'This department is still used by doctors or appointments and cannot be deleted.',
        ];
    }

    try {
        db()->prepare('DELETE FROM departments WHERE id = :id')->execute([':id' => $id]);
        return ['ok' => true];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1451) {
            return [
                'ok' => false,
                'error' => 'This department is still referenced by other records and cannot be deleted.',
            ];
        }
        error_log('HMS delete_department: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Sorry, the department could not be deleted.'];
    }
}
