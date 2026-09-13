-- =====================================================================
-- Evergreen Community Hospital - Hospital Management System
-- ICT726 Web Development, Assignment 4
-- Phase 1: database schema (structure only; seed data is in seed.sql)
--
-- Target      : MySQL / MariaDB (as shipped with XAMPP)
-- Engine      : InnoDB   (required for foreign keys and transactions)
-- Charset     : utf8mb4  Collation: utf8mb4_unicode_ci
--
-- Core tables : users, departments, doctors, appointments
--
-- Running this script DROPS and RECREATES the four HMS tables, so it is
-- safe to re-run during development. Import schema.sql first, then
-- seed.sql.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `hms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hms`;

-- Drop child tables before parents so foreign keys do not block the drop.
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
-- users
-- One row per account. Covers all three roles (admin, doctor, patient).
-- Used for authentication and for the role check behind every
-- protected page. Patients self-register; admin and doctor accounts
-- are created by an administrator.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(100)  NOT NULL,
  `email`         VARCHAR(150)  NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,               -- output of PHP password_hash()
  `role`          ENUM('admin','doctor','patient') NOT NULL DEFAULT 'patient',
  `phone`         VARCHAR(20)   DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- departments
-- Hospital departments. Managed by admin (CRUD). Referenced by doctors
-- and by appointments, and used to populate the booking form dropdown
-- and (later) the Services page.
-- ---------------------------------------------------------------------
CREATE TABLE `departments` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- doctors
-- Professional profile for a users row whose role is 'doctor'
-- (one-to-one, enforced by UNIQUE(user_id)). Managed by admin (CRUD).
-- is_active is a soft on/off switch for booking:
--   1 = active (bookable), 0 = inactive (hidden from new bookings,
--   existing appointments are kept).
-- ---------------------------------------------------------------------
CREATE TABLE `doctors` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `department_id`  INT UNSIGNED NOT NULL,
  `specialisation` VARCHAR(100) NOT NULL,
  `bio`            VARCHAR(500) DEFAULT NULL,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,      -- 1 = active, 0 = inactive
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctors_user_id` (`user_id`),
  KEY `idx_doctors_department_id` (`department_id`),
  KEY `idx_doctors_is_active` (`is_active`),
  CONSTRAINT `fk_doctors_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_doctors_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- appointments
-- The primary CRUD entity.
-- Booking flow: department -> doctor -> date/time -> appointment.
-- department_id records the department chosen at booking time and is
-- kept even if the doctor later moves department.
--
-- Double-booking is intentionally NOT enforced by a UNIQUE constraint
-- here. The application (a later phase) rejects a new booking when the
-- same doctor + date + time already has an appointment with status
-- 'pending' or 'confirmed'. A 'cancelled' appointment does not block
-- the slot. See database/README.md for this design decision.
-- ---------------------------------------------------------------------
CREATE TABLE `appointments` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id`       INT UNSIGNED NOT NULL,             -- users.id, role must be 'patient' (checked in PHP)
  `doctor_id`        INT UNSIGNED NOT NULL,
  `department_id`    INT UNSIGNED NOT NULL,
  `appointment_date` DATE         NOT NULL,
  `appointment_time` TIME         NOT NULL,
  `reason`           VARCHAR(500) NOT NULL,
  `status`           ENUM('pending','confirmed','completed','cancelled')
                                  NOT NULL DEFAULT 'pending',
  `notes`            VARCHAR(500) DEFAULT NULL,          -- staff note / reason for a status change
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appt_patient_id` (`patient_id`),
  KEY `idx_appt_doctor_id` (`doctor_id`),
  KEY `idx_appt_department_id` (`department_id`),
  KEY `idx_appt_date` (`appointment_date`),
  KEY `idx_appt_status` (`status`),
  -- Supports the application's double-booking lookup
  -- (doctor + date + time, then filter by active status in PHP).
  KEY `idx_appt_doctor_slot` (`doctor_id`,`appointment_date`,`appointment_time`),
  CONSTRAINT `fk_appt_patient`
    FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_appt_doctor`
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_appt_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of schema.
