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
    `ip_hash` VARCHAR(64) NOT NULL COMMENT 'Hash de l''IP pour anonymisation',
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
    `access_id` VARCHAR(100) DEFAULT NULL COMMENT 'Code d''accès utilisé',
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

-- ============================================================
-- TABLE: panelists (utilisateurs de l'app mobile)
-- ============================================================
CREATE TABLE IF NOT EXISTS `panelists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unique_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'ID public du paneliste',
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `gender` ENUM('M', 'F', 'autre') DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `csp` VARCHAR(50) DEFAULT NULL COMMENT 'Catégorie socio-professionnelle',
    `household_size` INT UNSIGNED DEFAULT NULL,
    `has_children` TINYINT(1) DEFAULT NULL,
    `children_ages` JSON DEFAULT NULL COMMENT '[3, 7, 12]',
    `equipment` JSON DEFAULT NULL COMMENT '["voiture", "iphone", "aspirateur_robot"]',
    `brands_owned` JSON DEFAULT NULL COMMENT '["dyson", "apple", "samsung"]',
    `interests` JSON DEFAULT NULL COMMENT '["tech", "sport", "cuisine"]',
    `push_token` VARCHAR(255) DEFAULT NULL COMMENT 'Firebase FCM token',
    `push_enabled` TINYINT(1) DEFAULT 1,
    `status` ENUM('active', 'inactive', 'pending_verification', 'blacklisted') DEFAULT 'pending_verification',
    `email_verified` TINYINT(1) DEFAULT 0,
    `email_verification_token` VARCHAR(100) DEFAULT NULL,
    `points_balance` INT UNSIGNED DEFAULT 0,
    `points_lifetime` INT UNSIGNED DEFAULT 0 COMMENT 'Total points earned',
    `studies_completed` INT UNSIGNED DEFAULT 0,
    `last_login` DATETIME DEFAULT NULL,
    `last_active` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_region` (`region`),
    INDEX `idx_gender` (`gender`),
    INDEX `idx_birth_date` (`birth_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: panelist_sessions (sessions JWT)
-- ============================================================
CREATE TABLE IF NOT EXISTS `panelist_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panelist_id` INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL COMMENT 'Hash du refresh token',
    `device_info` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_used` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_panelist_id` (`panelist_id`),
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`panelist_id`) REFERENCES `panelists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: solicitations (études envoyées aux panelistes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `solicitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `study_url` VARCHAR(500) NOT NULL COMMENT 'URL for WebView',
    `estimated_duration` VARCHAR(50) DEFAULT NULL COMMENT '10-15 min',
    `reward_points` INT UNSIGNED DEFAULT 0,
    `reward_description` VARCHAR(255) DEFAULT NULL COMMENT 'Ex: 50 points + tirage au sort',
    `criteria` JSON NOT NULL COMMENT 'Targeting criteria',
    `quota_target` INT UNSIGNED DEFAULT NULL,
    `quota_current` INT UNSIGNED DEFAULT 0,
    `priority` INT UNSIGNED DEFAULT 0 COMMENT 'Higher = shown first',
    `image_url` VARCHAR(500) DEFAULT NULL COMMENT 'Image de présentation',
    `starts_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `status` ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_study_id` (`study_id`),
    INDEX `idx_expires` (`expires_at`),
    INDEX `idx_priority` (`priority`),
    FOREIGN KEY (`study_id`) REFERENCES `studies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: panelist_solicitations (qui reçoit quoi)
-- ============================================================
CREATE TABLE IF NOT EXISTS `panelist_solicitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panelist_id` INT UNSIGNED NOT NULL,
    `solicitation_id` INT UNSIGNED NOT NULL,
    `status` ENUM('eligible', 'notified', 'viewed', 'started', 'completed', 'screened_out', 'expired') DEFAULT 'eligible',
    `matched_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Quand le matching a été fait',
    `notified_at` DATETIME DEFAULT NULL,
    `viewed_at` DATETIME DEFAULT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `response_id` INT UNSIGNED DEFAULT NULL COMMENT 'Link to responses table',
    `points_earned` INT UNSIGNED DEFAULT 0,
    UNIQUE KEY `idx_panelist_solicitation` (`panelist_id`, `solicitation_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_matched_at` (`matched_at`),
    FOREIGN KEY (`panelist_id`) REFERENCES `panelists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`solicitation_id`) REFERENCES `solicitations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: panelist_points_history (historique des points)
-- ============================================================
CREATE TABLE IF NOT EXISTS `panelist_points_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panelist_id` INT UNSIGNED NOT NULL,
    `points` INT NOT NULL COMMENT 'Positif = gagné, Négatif = dépensé',
    `type` ENUM('study_completed', 'bonus', 'referral', 'withdrawal', 'adjustment') NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `reference_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID solicitation ou autre',
    `balance_after` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_panelist_id` (`panelist_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`panelist_id`) REFERENCES `panelists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: push_notifications (historique des notifications)
-- ============================================================
CREATE TABLE IF NOT EXISTS `push_notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panelist_id` INT UNSIGNED NOT NULL,
    `solicitation_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `data` JSON DEFAULT NULL COMMENT 'Payload additionnel',
    `status` ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
    `sent_at` DATETIME DEFAULT NULL,
    `read_at` DATETIME DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_panelist_id` (`panelist_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`panelist_id`) REFERENCES `panelists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`solicitation_id`) REFERENCES `solicitations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DU SCHEMA
-- ============================================================
