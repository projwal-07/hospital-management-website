-- =====================================================================
-- Evergreen Community Hospital - Hospital Management System
-- ICT726 Web Development, Assignment 4
-- Phase 1: seed data
--
-- ALL DATA BELOW IS FICTIONAL. It exists only for local demonstration
-- and testing on XAMPP. No real personal information is used. Email
-- addresses deliberately use non-routable ".local" style domains.
--
-- Passwords are stored ONLY as bcrypt hashes produced by PHP
-- password_hash(..., PASSWORD_DEFAULT). The matching plaintext values
-- below are FICTIONAL LOCAL DEVELOPMENT CREDENTIALS, documented only so
-- the database can be tested. Remove or change these accounts before
-- any non-local deployment.
--
--   Role     | Email                        | Fictional local dev password
--   ---------|------------------------------|-----------------------------
--   admin    | admin@hms.local              | Admin123!
--   doctor   | alan.reyes@hms.local         | Doctor123!   (all seed doctors)
--   doctor   | bianca.osei@hms.local        | Doctor123!
--   doctor   | charles.ng@hms.local         | Doctor123!
--   patient  | jordan.miller@example.local  | Patient123!  (all seed patients)
--   patient  | priya.nair@example.local     | Patient123!
--
-- Import schema.sql BEFORE this file.
-- This script clears the four tables and resets their AUTO_INCREMENT
-- counters, so the row ids below are deterministic and it is safe to
-- re-run.
-- =====================================================================

USE `hms`;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `appointments`;
DELETE FROM `doctors`;
DELETE FROM `departments`;
DELETE FROM `users`;
ALTER TABLE `appointments` AUTO_INCREMENT = 1;
ALTER TABLE `doctors`      AUTO_INCREMENT = 1;
ALTER TABLE `departments`  AUTO_INCREMENT = 1;
ALTER TABLE `users`        AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- departments  ->  ids 1..6 in this exact order
-- ---------------------------------------------------------------------
INSERT INTO `departments` (`name`, `description`) VALUES
  ('General Medicine', 'Diagnosis and treatment of common adult medical conditions.'),
  ('Cardiology',       'Heart health consultations, monitoring and diagnostic testing.'),
  ('Paediatrics',      'Family-centred care for babies, children and teenagers.'),
  ('Orthopaedics',     'Treatment and rehabilitation for bone, joint and mobility conditions.'),
  ('Medical Imaging',  'X-ray, ultrasound and other imaging services supporting diagnosis.'),
  ('Physiotherapy',    'Individual recovery plans supporting movement, strength and independence.');

-- ---------------------------------------------------------------------
-- users  ->  id 1 = admin, ids 2..4 = doctors, ids 5..6 = patients
-- password_hash values are bcrypt; plaintext is documented in the
-- header above (fictional local development credentials only).
-- ---------------------------------------------------------------------
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `phone`) VALUES
  ('System Administrator', 'admin@hms.local',
     '$2y$10$F/VAx9oATcE4Qhy/tiSCOuZwy0iemU/QE/eGW0dwZSzFW7PwY3N3C', 'admin',   '(02) 9000 0000'),
  ('Alan Reyes',           'alan.reyes@hms.local',
     '$2y$10$AlE4Rrytwl7PbSKednIYr.olW151Clx5DLyAG502eOviVPImQWC4G', 'doctor',  '(02) 9000 0011'),
  ('Bianca Osei',          'bianca.osei@hms.local',
     '$2y$10$AlE4Rrytwl7PbSKednIYr.olW151Clx5DLyAG502eOviVPImQWC4G', 'doctor',  '(02) 9000 0012'),
  ('Charles Ng',           'charles.ng@hms.local',
     '$2y$10$AlE4Rrytwl7PbSKednIYr.olW151Clx5DLyAG502eOviVPImQWC4G', 'doctor',  '(02) 9000 0013'),
  ('Jordan Miller',        'jordan.miller@example.local',
     '$2y$10$EJQ4vLBn9MKPUhVBEHpJse938TKoABuOoIryKu/U0.bkU25K6Oy06', 'patient', '(02) 9000 0101'),
  ('Priya Nair',           'priya.nair@example.local',
     '$2y$10$EJQ4vLBn9MKPUhVBEHpJse938TKoABuOoIryKu/U0.bkU25K6Oy06', 'patient', '(02) 9000 0102');

-- ---------------------------------------------------------------------
-- doctors  ->  one profile per doctor user
--   doctor id 1 -> user 2 (Alan Reyes)  -> department 2 (Cardiology),   active
--   doctor id 2 -> user 3 (Bianca Osei) -> department 3 (Paediatrics),  active
--   doctor id 3 -> user 4 (Charles Ng)  -> department 4 (Orthopaedics), INACTIVE (demo)
-- ---------------------------------------------------------------------
INSERT INTO `doctors` (`user_id`, `department_id`, `specialisation`, `bio`, `is_active`) VALUES
  (2, 2, 'Adult cardiology',
      'Consultations for blood pressure, palpitations and general heart-health reviews.', 1),
  (3, 3, 'General paediatrics',
      'Care for infants, children and teenagers, including routine follow-ups.', 1),
  (4, 4, 'Sports injury rehabilitation',
      'Assessment and rehabilitation of joint and mobility injuries.', 0);

-- ---------------------------------------------------------------------
-- appointments  ->  covers every status value, with past and future dates
--   patients: 5 = Jordan Miller, 6 = Priya Nair
--   doctors : 1 = Alan Reyes (dept 2), 2 = Bianca Osei (dept 3), 3 = Charles Ng (dept 4)
--   status counts: pending 2, confirmed 2, completed 3, cancelled 1
-- ---------------------------------------------------------------------
INSERT INTO `appointments`
  (`patient_id`,`doctor_id`,`department_id`,`appointment_date`,`appointment_time`,`reason`,`status`,`notes`) VALUES
  (5, 1, 2, '2026-09-20', '09:30:00', 'Chest tightness during exercise, requesting a review.', 'pending',   NULL),
  (6, 1, 2, '2026-10-05', '11:00:00', 'Palpitations noted by GP, referred for assessment.',    'pending',   NULL),
  (6, 2, 3, '2026-09-15', '14:00:00', 'Routine follow-up after a childhood ear infection.',     'confirmed', 'Confirmed by reception.'),
  (6, 1, 2, '2026-09-25', '16:00:00', 'Follow-up ECG after a medication change.',               'confirmed', 'Confirmed.'),
  (5, 1, 2, '2026-08-10', '09:00:00', 'Blood pressure review.',                                 'completed', 'Reviewed; medication unchanged. Follow-up in three months.'),
  (6, 2, 3, '2026-07-22', '15:30:00', 'Discussion of the childhood vaccination schedule.',      'completed', 'Completed; next visit to be booked separately.'),
  (5, 3, 4, '2026-06-15', '08:30:00', 'Knee pain assessment.',                                  'completed', 'Discharged with a physiotherapy referral.'),
  (5, 2, 3, '2026-08-28', '13:00:00', 'Requested to reschedule; unable to attend.',             'cancelled', 'Cancelled by patient by phone.');

-- End of seed data.
