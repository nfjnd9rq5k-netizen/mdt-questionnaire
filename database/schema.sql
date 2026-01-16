-- ============================================================
-- SCHEMA COMPLET - La Maison du Test
-- ============================================================
-- Exécuter ce fichier dans phpMyAdmin pour créer toutes les tables
-- Base de données: u486755141_etudes
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE: users (comptes administrateurs)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unique_id` VARCHAR(50) NOT NULL UNIQUE,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'user') DEFAULT 'user',
    `allowed_studies` JSON DEFAULT NULL COMMENT 'Liste des études autorisées (null ou ["*"] = toutes)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME DEFAULT NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: admin_logs (journal des connexions)
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'login_success, login_failed, logout, etc.',
    `username` VARCHAR(100) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: login_attempts (protection anti-bruteforce)
-- ============================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_hash` VARCHAR(64) NOT NULL COMMENT 'Hash de l\'IP pour anonymisation',
    `username` VARCHAR(100) DEFAULT NULL,
    `attempts` INT UNSIGNED DEFAULT 1,
    `first_attempt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_attempt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `blocked_until` DATETIME DEFAULT NULL,
    UNIQUE KEY `idx_ip_hash` (`ip_hash`),
    INDEX `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: allowed_ips (IPs autorisées pour l'admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS `allowed_ips` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL UNIQUE,
    `label` VARCHAR(100) DEFAULT NULL COMMENT 'Description (ex: Bureau Paris)',
    `added_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`added_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: pending_ips (IPs en attente de validation)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pending_ips` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL UNIQUE,
    `requested_by` VARCHAR(100) DEFAULT NULL COMMENT 'Username qui a demandé',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: studies (études/questionnaires)
-- ============================================================
CREATE TABLE IF NOT EXISTS `studies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_id` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Identifiant technique (ex: SHARK_INHOME_JAN2026)',
    `folder_name` VARCHAR(100) NOT NULL COMMENT 'Nom du dossier',
    `title` VARCHAR(255) DEFAULT NULL,
    `study_date` VARCHAR(100) DEFAULT NULL,
    `reward` VARCHAR(50) DEFAULT NULL,
    `duration` VARCHAR(50) DEFAULT NULL,
    `target_participants` INT UNSIGNED DEFAULT 5,
    `require_access_id` TINYINT(1) DEFAULT 0,
    `study_type` ENUM('classic', 'data_collection') DEFAULT 'classic' COMMENT 'Type: classic=recrutement, data_collection=collecte IA',
    `status` ENUM('active', 'closed', 'draft') DEFAULT 'active',
    `closed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_folder_name` (`folder_name`),
    INDEX `idx_study_type` (`study_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: access_ids (codes d'accès pour les études)
-- ============================================================
CREATE TABLE IF NOT EXISTS `access_ids` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_id` INT UNSIGNED NOT NULL,
    `access_code` VARCHAR(100) NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_study_code` (`study_id`, `access_code`),
    FOREIGN KEY (`study_id`) REFERENCES `studies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: responses (réponses des participants)
-- ============================================================
CREATE TABLE IF NOT EXISTS `responses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unique_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'ID public du participant',
    `study_id` INT UNSIGNED NOT NULL,
    `access_id` VARCHAR(100) DEFAULT NULL COMMENT 'Code d\'accès utilisé',
    `status` ENUM('EN_COURS', 'QUALIFIE', 'REFUSE') DEFAULT 'EN_COURS',
    `stop_reason` TEXT DEFAULT NULL,
    `all_stop_reasons` JSON DEFAULT NULL,
    `horaire` VARCHAR(100) DEFAULT NULL,
    `ip_hash` VARCHAR(64) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `behavior_metrics` JSON DEFAULT NULL COMMENT 'Métriques comportementales (trust_score, paste_events, etc.)',
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    `modified_at` DATETIME DEFAULT NULL,
    `modified_by` VARCHAR(50) DEFAULT NULL,
    INDEX `idx_study_id` (`study_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_access_id` (`access_id`),
    FOREIGN KEY (`study_id`) REFERENCES `studies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: signaletiques (informations personnelles des participants)
-- ============================================================
CREATE TABLE IF NOT EXISTS `signaletiques` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `response_id` INT UNSIGNED NOT NULL UNIQUE,
    `nom` VARCHAR(100) DEFAULT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `telephone` VARCHAR(50) DEFAULT NULL,
    `adresse` TEXT DEFAULT NULL,
    `code_postal` VARCHAR(10) DEFAULT NULL,
    `ville` VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: answers (réponses aux questions)
-- ============================================================
CREATE TABLE IF NOT EXISTS `answers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `response_id` INT UNSIGNED NOT NULL,
    `question_id` VARCHAR(50) NOT NULL,
    `answer_value` TEXT DEFAULT NULL COMMENT 'Réponse simple (radio, number)',
    `answer_values` JSON DEFAULT NULL COMMENT 'Réponses multiples (checkbox)',
    `answer_text` TEXT DEFAULT NULL COMMENT 'Texte libre',
    `answer_data` JSON DEFAULT NULL COMMENT 'Données complexes (matrix, files, etc.)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_response_question` (`response_id`, `question_id`),
    INDEX `idx_question_id` (`question_id`),
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: photos (photos uploadées)
-- ============================================================
CREATE TABLE IF NOT EXISTS `photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `response_id` INT UNSIGNED NOT NULL,
    `question_id` VARCHAR(50) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `file_path` TEXT DEFAULT NULL,
    `file_size` INT UNSIGNED DEFAULT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_response_id` (`response_id`),
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DU SCHEMA
-- ============================================================
