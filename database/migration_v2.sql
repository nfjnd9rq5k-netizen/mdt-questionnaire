-- ============================================================
-- MIGRATION v2.0 - La Maison du Test
-- ============================================================
-- Exécuter ce fichier dans phpMyAdmin pour mettre à jour
-- une base existante avec les nouvelles fonctionnalités
-- ============================================================

-- 1. Ajouter la colonne study_type à la table studies
ALTER TABLE `studies` 
ADD COLUMN IF NOT EXISTS `study_type` ENUM('classic', 'data_collection') DEFAULT 'classic' 
COMMENT 'Type: classic=recrutement, data_collection=collecte IA'
AFTER `require_access_id`;

-- 2. Ajouter la colonne behavior_metrics à la table responses
ALTER TABLE `responses` 
ADD COLUMN IF NOT EXISTS `behavior_metrics` JSON DEFAULT NULL 
COMMENT 'Métriques comportementales (trust_score, paste_events, etc.)'
AFTER `user_agent`;

-- 3. Ajouter un index sur study_type pour optimiser les requêtes
ALTER TABLE `studies` ADD INDEX IF NOT EXISTS `idx_study_type` (`study_type`);

-- 4. Mettre à jour l'étude DATA_IA_JAN2026 comme étude de type data_collection
UPDATE `studies` SET `study_type` = 'data_collection' WHERE `study_id` = 'DATA_IA_JAN2026';

-- 5. Vérification
SELECT 'Migration v2.0 terminée avec succès!' AS message;
SELECT study_id, study_type, title FROM studies;
