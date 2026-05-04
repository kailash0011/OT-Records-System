<?php
/**
 * Database Configuration
 * OT Records Management System
 */

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'ot_management_system');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 * All queries must use prepared statements to prevent SQL injection.
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn     = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Connection failed: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed']));
        }
    }

    return $pdo;
}
