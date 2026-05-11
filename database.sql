-- Reference Database Schema for DarkAuth Advanced Features
-- Use these tables to enable MFA, Audit Logging, and Trusted Devices.
-- DarkAuth adapts to your existing 'users' table, so these are supplementary.

-- 1. MFA Secrets Table (Separated from users table for security)
CREATE TABLE IF NOT EXISTS `darkauth_mfa_secrets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `secret` TEXT NOT NULL,
    `recovery_codes` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Trusted Devices Table
CREATE TABLE IF NOT EXISTS `darkauth_trusted_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `device_token` VARCHAR(128) NOT NULL,
    `user_agent` TEXT,
    `last_ip` VARCHAR(45),
    `expires_at` TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`user_id`),
    INDEX (`device_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Audit Logs Table (Tamper-resistant design)
CREATE TABLE IF NOT EXISTS `darkauth_audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(64) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `data` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`event`),
    INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
