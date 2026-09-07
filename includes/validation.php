<?php
declare(strict_types=1);

/**
 * Small server-side validation helpers.
 *
 * Each function checks one rule and, if it fails, records a message in
 * $errors keyed by $field (first failure per field wins). $errors is
 * passed by reference. Returns true when the value passes the rule.
 *
 * These run on the server regardless of any HTML5 validation
 * attributes, which are only a usability aid.
 */

function v_required(array &$errors, string $field, mixed $value, string $label): bool
{
    if (!is_string($value) || trim($value) === '') {
        $errors[$field] ??= "$label is required.";
        return false;
    }
    return true;
}

function v_length(array &$errors, string $field, string $value, int $min, int $max, string $label): bool
{
    $len = mb_strlen(trim($value));
    if ($len < $min || $len > $max) {
        $errors[$field] ??= "$label must be between $min and $max characters.";
        return false;
    }
    return true;
}

function v_email(array &$errors, string $field, string $value, string $label): bool
{
    if (filter_var(trim($value), FILTER_VALIDATE_EMAIL) === false) {
        $errors[$field] ??= "Enter a valid $label.";
        return false;
    }
    return true;
}

function v_match(array &$errors, string $field, mixed $a, mixed $b, string $label): bool
{
    if (!is_string($a) || !is_string($b) || $a !== $b) {
        $errors[$field] ??= "$label do not match.";
        return false;
    }
    return true;
}

/**
 * Password policy: 8 to 72 bytes, not entirely numeric.
 *
 * The limit is measured in BYTES because the current PHP hashing
 * default (bcrypt) only uses the first 72 bytes of the input; longer
 * input is silently truncated, so it is rejected rather than accepted.
 * The rule is deliberately simple - no forced character-class mix.
 */
function v_password(array &$errors, string $field, string $value, string $label = 'Password'): bool
{
    $bytes = strlen($value);
    if ($bytes < 8) {
        $errors[$field] ??= "$label must be at least 8 characters.";
        return false;
    }
    if ($bytes > 72) {
        $errors[$field] ??= "$label is too long (limit is 72 bytes).";
        return false;
    }
    if (preg_match('/^\d+$/', $value) === 1) {
        $errors[$field] ??= "$label must not be entirely numbers.";
        return false;
    }
    return true;
}

/** Optional telephone: digits, spaces and + ( ) - only, 8 to 20 chars. */
function v_phone_optional(array &$errors, string $field, mixed $value, string $label = 'Telephone'): bool
{
    $s = trim((string) ($value ?? ''));
    if ($s === '') {
        return true;
    }
    if (preg_match('/^[0-9+()\-\s]{8,20}$/', $s) !== 1) {
        $errors[$field] ??= "Enter a valid $label or leave it blank.";
        return false;
    }
    return true;
}
