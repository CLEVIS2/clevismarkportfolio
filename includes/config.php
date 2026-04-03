<?php

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Africa/Nairobi');
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_NAME') ?: 'portfolio_one';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

$pdo = null;
$database_error = null;

try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    try {
        $pdo->exec("SET time_zone = '+03:00'");
    } catch (PDOException $timezoneException) {
        // Some hosts restrict session time zone changes; PHP will still render dates in Africa/Nairobi.
    }

    $adminSeedEmail = getenv('ADMIN_EMAIL') ?: null;
    $adminSeedPassword = getenv('ADMIN_PASSWORD') ?: null;

    if ($adminSeedEmail && $adminSeedPassword) {
        $adminHash = password_hash($adminSeedPassword, PASSWORD_DEFAULT);

        try {
            $seedAdmin = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role)
                 VALUES (:name, :email, :password_hash, :role)
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    password_hash = VALUES(password_hash),
                    role = VALUES(role)'
            );
            $seedAdmin->execute([
                ':name' => 'Clevis Admin',
                ':email' => $adminSeedEmail,
                ':password_hash' => $adminHash,
                ':role' => 'admin',
            ]);
        } catch (PDOException $seedException) {
            // Ignore schema bootstrapping issues here; the app will surface database errors where needed.
        }
    }

    try {
        $pdo->exec('ALTER TABLE service_requests ADD COLUMN request_code VARCHAR(80) NULL AFTER user_id');
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN request_status VARCHAR(40) NOT NULL DEFAULT 'New' AFTER request_code");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN deleted_by_admin_id INT UNSIGNED NULL AFTER deleted_at");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN brief_file_path VARCHAR(255) NULL AFTER deleted_by_admin_id");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN logo_file_path VARCHAR(255) NULL AFTER brief_file_path");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN reference_file_path VARCHAR(255) NULL AFTER logo_file_path");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN brief_file_name VARCHAR(255) NULL AFTER reference_file_path");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN logo_file_name VARCHAR(255) NULL AFTER brief_file_name");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec("ALTER TABLE service_requests ADD COLUMN reference_file_name VARCHAR(255) NULL AFTER logo_file_name");
    } catch (PDOException $schemaException) {
        // The column already exists on newer installs.
    }

    try {
        $pdo->exec('CREATE UNIQUE INDEX idx_service_requests_request_code ON service_requests (request_code)');
    } catch (PDOException $schemaException) {
        // The index already exists or the host does not support creating it here.
    }

    try {
        $pdo->exec('CREATE INDEX idx_service_requests_status ON service_requests (request_status)');
    } catch (PDOException $schemaException) {
        // The index already exists or the host does not support creating it here.
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS audit_logs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                request_id INT UNSIGNED NULL,
                request_code VARCHAR(80) NULL,
                admin_user_id INT UNSIGNED NOT NULL,
                admin_name VARCHAR(120) NOT NULL,
                action_type VARCHAR(40) NOT NULL,
                old_value VARCHAR(120) NULL,
                new_value VARCHAR(120) NULL,
                details TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_audit_logs_request_id (request_id),
                KEY idx_audit_logs_request_code (request_code),
                KEY idx_audit_logs_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (PDOException $schemaException) {
        // The table already exists or the host does not allow creation here.
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS internal_notes (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                request_id INT UNSIGNED NOT NULL,
                admin_user_id INT UNSIGNED NULL,
                admin_name VARCHAR(120) NOT NULL,
                note_text TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_internal_notes_request_id (request_id),
                KEY idx_internal_notes_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (PDOException $schemaException) {
        // The table already exists or the host does not allow creation here.
    }
} catch (PDOException $exception) {
    $database_error = 'Database connection is not active. Update includes/config.php with your hosting MySQL host, database name, username, and password, then import database/portfolio_one.sql into that database.';
}
