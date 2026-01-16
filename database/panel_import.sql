-- ============================================================
-- TABLE: panel_imported (Données panélistes importées Excel)
-- ============================================================
-- Ces données servent à pré-remplir le profil lors de l'inscription
-- Si l'email + panel_id correspondent, l'utilisateur peut récupérer ses données
-- Les données non utilisées sont supprimées après 1 mois
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `panel_imported` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panel_id` INT UNSIGNED NOT NULL COMMENT 'ID original du fichier Excel',
    `email` VARCHAR(255) NOT NULL,

    -- Identité
    `region` VARCHAR(50) DEFAULT NULL,
    `civilite` VARCHAR(20) DEFAULT NULL,
    `nom` VARCHAR(100) DEFAULT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,

    -- Contact
    `adresse` TEXT DEFAULT NULL,
    `code_postal` VARCHAR(10) DEFAULT NULL,
    `departement` VARCHAR(10) DEFAULT NULL,
    `ville` VARCHAR(100) DEFAULT NULL,
    `tel_domicile` VARCHAR(20) DEFAULT NULL,
    `tel_portable` VARCHAR(20) DEFAULT NULL,
    `tel_bureau` VARCHAR(20) DEFAULT NULL,

    -- Démographie
    `date_naissance` DATE DEFAULT NULL,
    `age` INT UNSIGNED DEFAULT NULL,
    `situation_familiale` VARCHAR(100) DEFAULT NULL,
    `situation_professionnelle` VARCHAR(100) DEFAULT NULL,

    -- Enfants
    `enfants_au_foyer` INT UNSIGNED DEFAULT 0,
    `enfants_plus_18ans` INT UNSIGNED DEFAULT 0,
    `enfants_moins_18ans` INT UNSIGNED DEFAULT 0,
    `enfants_data` JSON DEFAULT NULL COMMENT 'Détails des enfants',

    -- Professionnel
    `diplome` VARCHAR(100) DEFAULT NULL,
    `profession` VARCHAR(100) DEFAULT NULL,
    `secteur_activite` VARCHAR(100) DEFAULT NULL,
    `revenu_mensuel` VARCHAR(100) DEFAULT NULL,

    -- Banque
    `banque_principale` VARCHAR(100) DEFAULT NULL,
    `autres_banques` VARCHAR(255) DEFAULT NULL,

    -- Conjoint
    `conjoint_data` JSON DEFAULT NULL COMMENT 'Toutes les infos du conjoint',

    -- Habitat
    `type_habitation` VARCHAR(100) DEFAULT NULL,
    `situation_habitation` VARCHAR(100) DEFAULT NULL,

    -- Véhicules
    `possede_voiture` TINYINT(1) DEFAULT NULL,
    `voitures_data` JSON DEFAULT NULL COMMENT 'Détails des voitures',
    `possede_moto` TINYINT(1) DEFAULT NULL,
    `motos_data` JSON DEFAULT NULL COMMENT 'Détails des motos',

    -- Équipements
    `equipements` JSON DEFAULT NULL COMMENT 'TV, vidéoprojecteur, PC, etc.',

    -- Métadonnées
    `claimed` TINYINT(1) DEFAULT 0 COMMENT '1 si récupéré par un utilisateur',
    `claimed_by` INT UNSIGNED DEFAULT NULL COMMENT 'ID du panelist qui a réclamé',
    `claimed_at` DATETIME DEFAULT NULL,
    `imported_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL COMMENT 'Date de suppression automatique',

    -- Index
    UNIQUE KEY `idx_panel_email` (`panel_id`, `email`),
    INDEX `idx_email` (`email`),
    INDEX `idx_panel_id` (`panel_id`),
    INDEX `idx_claimed` (`claimed`),
    INDEX `idx_expires_at` (`expires_at`),

    FOREIGN KEY (`claimed_by`) REFERENCES `panelists`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- EVENT: Nettoyage automatique après expiration
-- ============================================================
-- Note: Nécessite que le scheduler d'événements MySQL soit activé
-- SET GLOBAL event_scheduler = ON;

DELIMITER //

CREATE EVENT IF NOT EXISTS `cleanup_expired_panel_imports`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM `panel_imported`
    WHERE `claimed` = 0
    AND `expires_at` IS NOT NULL
    AND `expires_at` < NOW();
END//

DELIMITER ;

-- ============================================================
-- Procédure alternative pour le nettoyage (si events non dispo)
-- ============================================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `cleanup_panel_imports`()
BEGIN
    DECLARE deleted_count INT DEFAULT 0;

    DELETE FROM `panel_imported`
    WHERE `claimed` = 0
    AND `expires_at` IS NOT NULL
    AND `expires_at` < NOW();

    SET deleted_count = ROW_COUNT();

    SELECT deleted_count AS 'Enregistrements supprimés';
END//

DELIMITER ;
