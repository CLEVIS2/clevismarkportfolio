SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `registration_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_registration_email` (`email`),
    KEY `idx_registration_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `email` VARCHAR(190) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_email` (`email`),
    KEY `idx_login_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `request_code` VARCHAR(80) NOT NULL,
    `request_status` VARCHAR(40) NOT NULL DEFAULT 'New',
    `client_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `service_type` VARCHAR(120) NOT NULL,
    `budget_range` VARCHAR(80) NULL,
    `preferred_contact` VARCHAR(40) NOT NULL DEFAULT 'Email',
    `project_details` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_by_admin_id` INT UNSIGNED NULL,
    `brief_file_path` VARCHAR(255) NULL,
    `logo_file_path` VARCHAR(255) NULL,
    `reference_file_path` VARCHAR(255) NULL,
    `brief_file_name` VARCHAR(255) NULL,
    `logo_file_name` VARCHAR(255) NULL,
    `reference_file_name` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_service_requests_request_code` (`request_code`),
    KEY `idx_service_requests_status` (`request_status`),
    KEY `idx_service_requests_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `feedback_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` INT UNSIGNED NULL,
    `recipient_user_id` INT UNSIGNED NULL,
    `recipient_email` VARCHAR(190) NOT NULL,
    `admin_user_id` INT UNSIGNED NOT NULL,
    `admin_name` VARCHAR(120) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_feedback_recipient_user_id` (`recipient_user_id`),
    KEY `idx_feedback_recipient_email` (`recipient_email`),
    KEY `idx_feedback_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` INT UNSIGNED NULL,
    `request_code` VARCHAR(80) NULL,
    `admin_user_id` INT UNSIGNED NOT NULL,
    `admin_name` VARCHAR(120) NOT NULL,
    `action_type` VARCHAR(40) NOT NULL,
    `old_value` VARCHAR(120) NULL,
    `new_value` VARCHAR(120) NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_request_id` (`request_id`),
    KEY `idx_audit_logs_request_code` (`request_code`),
    KEY `idx_audit_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `internal_notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` INT UNSIGNED NOT NULL,
    `admin_user_id` INT UNSIGNED NULL,
    `admin_name` VARCHAR(120) NOT NULL,
    `note_text` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_internal_notes_request_id` (`request_id`),
    KEY `idx_internal_notes_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (name, email, password_hash, role)
VALUES (
    'Clevis Admin',
    'nyongesaclevis76@gmail.com',
    '$2y$10$Cd4iV9QwsqPpXUkIJqMThe46zYU1JLbrXT0v4JwuYe9Lr.3vfj6.i',
    'admin'
);
