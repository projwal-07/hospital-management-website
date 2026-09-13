<?php
/**
 * TEMPLATE - database settings for the Hospital Management System.
 *
 * Copy this file to config/db.php and enter your local credentials
 * THERE. config/db.php is git-ignored and must never be committed.
 * Do NOT put a real password in this template file.
 *
 * The application connects with the least-privilege "hms_app" account
 * (SELECT, INSERT, UPDATE, DELETE on the hms database only). Schema
 * imports are done separately, as root, in phpMyAdmin.
 */

return [
    'host'    => '127.0.0.1',   // local MariaDB over TCP
    'port'    => 3306,
    'name'    => 'hms',
    'user'    => 'hms_app',     // least-privilege application account
    'pass'    => '',            // placeholder only - set the real password in config/db.php
    'charset' => 'utf8mb4',
];
