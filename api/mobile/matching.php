<?php
/**
 * Algorithme de Matching Panelistes
 * Trouve les panelistes éligibles pour une solicitation
 */

require_once __DIR__ . '/../db.php';

/**
 * Trouve tous les panelistes correspondant aux critères d'une solicitation
 *
 * @param int $solicitationId ID de la solicitation
 * @return array Liste des IDs de panelistes éligibles
 */
function findMatchingPanelists(int $solicitationId): array {
    $solicitation = dbQueryOne(
        "SELECT criteria, quota_target, quota_current FROM solicitations WHERE id = ?",
        [$solicitationId]
    );

    if (!$solicitation) {
        return [];
    }

    $criteria = json_decode($solicitation['criteria'], true);
    if (!$criteria) {
        $criteria = [];
    }

    // Construire la requête SQL dynamiquement
    $sql = "SELECT p.id FROM panelists p WHERE p.status = 'active'";
    $params = [];

    // Exclure les panelistes déjà associés à cette solicitation
    $sql .= " AND p.id NOT IN (
        SELECT panelist_id FROM panelist_solicitations WHERE solicitation_id = ?
    )";
    $params[] = $solicitationId;

    // Filtre: Genre
    if (!empty($criteria['gender'])) {
        $genders = is_array($criteria['gender']) ? $criteria['gender'] : [$criteria['gender']];
        $placeholders = implode(',', array_fill(0, count($genders), '?'));
        $sql .= " AND p.gender IN ($placeholders)";
        $params = array_merge($params, $genders);
    }

    // Filtre: Âge minimum
    if (!empty($criteria['age_min'])) {
        $sql .= " AND p.birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) >= ?";
        $params[] = (int) $criteria['age_min'];
    }

    // Filtre: Âge maximum
    if (!empty($criteria['age_max'])) {
        $sql .= " AND p.birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) <= ?";
        $params[] = (int) $criteria['age_max'];
    }

    // Filtre: Régions
    if (!empty($criteria['regions'])) {
        $regions = is_array($criteria['regions']) ? $criteria['regions'] : [$criteria['regions']];
        $placeholders = implode(',', array_fill(0, count($regions), '?'));
        $sql .= " AND p.region IN ($placeholders)";
        $params = array_merge($params, $regions);
    }

    // Filtre: Code postal (préfixe)
    if (!empty($criteria['postal_prefixes'])) {
        $prefixes = is_array($criteria['postal_prefixes']) ? $criteria['postal_prefixes'] : [$criteria['postal_prefixes']];
        $conditions = [];
        foreach ($prefixes as $prefix) {
            $conditions[] = "p.postal_code LIKE ?";
            $params[] = $prefix . '%';
        }
        $sql .= " AND (" . implode(' OR ', $conditions) . ")";
    }

    // Filtre: CSP
    if (!empty($criteria['csp'])) {
        $csps = is_array($criteria['csp']) ? $criteria['csp'] : [$criteria['csp']];
        $placeholders = implode(',', array_fill(0, count($csps), '?'));
        $sql .= " AND p.csp IN ($placeholders)";
        $params = array_merge($params, $csps);
    }

    // Filtre: A des enfants
    if (isset($criteria['has_children'])) {
        $sql .= " AND p.has_children = ?";
        $params[] = $criteria['has_children'] ? 1 : 0;
    }

    // Filtre: Taille du foyer minimum
    if (!empty($criteria['household_min'])) {
        $sql .= " AND p.household_size >= ?";
        $params[] = (int) $criteria['household_min'];
    }

    // Filtre: Taille du foyer maximum
    if (!empty($criteria['household_max'])) {
        $sql .= " AND p.household_size <= ?";
        $params[] = (int) $criteria['household_max'];
    }

    // Filtre: Équipement requis (TOUS doivent être présents)
    if (!empty($criteria['equipment_required'])) {
        $equipment = is_array($criteria['equipment_required']) ? $criteria['equipment_required'] : [$criteria['equipment_required']];
        foreach ($equipment as $item) {
            $sql .= " AND JSON_CONTAINS(p.equipment, ?)";
            $params[] = json_encode($item);
        }
    }

    // Filtre: Équipement exclu (AUCUN ne doit être présent)
    if (!empty($criteria['equipment_excluded'])) {
        $equipment = is_array($criteria['equipment_excluded']) ? $criteria['equipment_excluded'] : [$criteria['equipment_excluded']];
        foreach ($equipment as $item) {
            $sql .= " AND (p.equipment IS NULL OR NOT JSON_CONTAINS(p.equipment, ?))";
            $params[] = json_encode($item);
        }
    }

    // Filtre: Marques possédées (AU MOINS UNE)
    if (!empty($criteria['brands_owned_any'])) {
        $brands = is_array($criteria['brands_owned_any']) ? $criteria['brands_owned_any'] : [$criteria['brands_owned_any']];
        $brandConditions = [];
        foreach ($brands as $brand) {
            $brandConditions[] = "JSON_CONTAINS(p.brands_owned, ?)";
            $params[] = json_encode($brand);
        }
        $sql .= " AND (" . implode(' OR ', $brandConditions) . ")";
    }

    // Filtre: Centres d'intérêt (AU MOINS UN)
    if (!empty($criteria['interests_any'])) {
        $interests = is_array($criteria['interests_any']) ? $criteria['interests_any'] : [$criteria['interests_any']];
        $interestConditions = [];
        foreach ($interests as $interest) {
            $interestConditions[] = "JSON_CONTAINS(p.interests, ?)";
            $params[] = json_encode($interest);
        }
        $sql .= " AND (" . implode(' OR ', $interestConditions) . ")";
    }

    // Filtre: Push notifications activées
    if (!empty($criteria['push_enabled'])) {
        $sql .= " AND p.push_enabled = 1 AND p.push_token IS NOT NULL";
    }

    // Filtre: Études complétées minimum
    if (!empty($criteria['min_studies_completed'])) {
        $sql .= " AND p.studies_completed >= ?";
        $params[] = (int) $criteria['min_studies_completed'];
    }

    // Filtre: Études complétées maximum (nouveaux panelistes)
    if (isset($criteria['max_studies_completed'])) {
        $sql .= " AND p.studies_completed <= ?";
        $params[] = (int) $criteria['max_studies_completed'];
    }

    // Limiter le nombre de résultats si quota défini
    $limit = null;
    if ($solicitation['quota_target']) {
        $remaining = $solicitation['quota_target'] - $solicitation['quota_current'];
        if ($remaining > 0) {
            // Prendre 3x le quota restant pour avoir de la marge
            $limit = $remaining * 3;
        }
    }

    if ($limit) {
        $sql .= " ORDER BY p.last_active DESC LIMIT " . (int) $limit;
    } else {
        $sql .= " ORDER BY p.last_active DESC LIMIT 1000";
    }

    $results = dbQuery($sql, $params);

    return array_column($results, 'id');
}

/**
 * Trouve toutes les solicitations éligibles pour un paneliste
 *
 * @param int $panelistId ID du paneliste
 * @return array Liste des solicitations avec détails
 */
function findEligibleSolicitations(int $panelistId): array {
    // Récupérer le profil du paneliste
    $panelist = dbQueryOne(
        "SELECT * FROM panelists WHERE id = ? AND status = 'active'",
        [$panelistId]
    );

    if (!$panelist) {
        return [];
    }

    // Récupérer toutes les solicitations actives
    $solicitations = dbQuery(
        "SELECT s.*, st.folder_name as study_folder
         FROM solicitations s
         JOIN studies st ON s.study_id = st.id
         WHERE s.status = 'active'
           AND (s.starts_at IS NULL OR s.starts_at <= NOW())
           AND (s.expires_at IS NULL OR s.expires_at > NOW())
           AND (s.quota_target IS NULL OR s.quota_current < s.quota_target)
         ORDER BY s.priority DESC, s.created_at DESC"
    );

    $eligible = [];

    foreach ($solicitations as $sol) {
        // Vérifier si déjà associé
        $existing = dbQueryOne(
            "SELECT status FROM panelist_solicitations
             WHERE panelist_id = ? AND solicitation_id = ?",
            [$panelistId, $sol['id']]
        );

        if ($existing) {
            // Déjà associé - inclure avec son statut actuel
            if (!in_array($existing['status'], ['completed', 'screened_out', 'expired'])) {
                $sol['panelist_status'] = $existing['status'];
                $eligible[] = $sol;
            }
            continue;
        }

        // Vérifier les critères
        $criteria = json_decode($sol['criteria'], true) ?? [];

        if (matchesCriteria($panelist, $criteria)) {
            $sol['panelist_status'] = 'new'; // Nouvelle étude éligible
            $eligible[] = $sol;
        }
    }

    return $eligible;
}

/**
 * Vérifie si un paneliste correspond aux critères
 */
function matchesCriteria(array $panelist, array $criteria): bool {
    // Genre
    if (!empty($criteria['gender'])) {
        $genders = is_array($criteria['gender']) ? $criteria['gender'] : [$criteria['gender']];
        if (!in_array($panelist['gender'], $genders)) {
            return false;
        }
    }

    // Âge
    if ($panelist['birth_date']) {
        $age = (int) date_diff(
            date_create($panelist['birth_date']),
            date_create('today')
        )->y;

        if (!empty($criteria['age_min']) && $age < $criteria['age_min']) {
            return false;
        }
        if (!empty($criteria['age_max']) && $age > $criteria['age_max']) {
            return false;
        }
    } elseif (!empty($criteria['age_min']) || !empty($criteria['age_max'])) {
        // Âge requis mais non renseigné
        return false;
    }

    // Région
    if (!empty($criteria['regions'])) {
        $regions = is_array($criteria['regions']) ? $criteria['regions'] : [$criteria['regions']];
        if (!in_array($panelist['region'], $regions)) {
            return false;
        }
    }

    // CSP
    if (!empty($criteria['csp'])) {
        $csps = is_array($criteria['csp']) ? $criteria['csp'] : [$criteria['csp']];
        if (!in_array($panelist['csp'], $csps)) {
            return false;
        }
    }

    // Enfants
    if (isset($criteria['has_children'])) {
        if ((bool) $panelist['has_children'] !== (bool) $criteria['has_children']) {
            return false;
        }
    }

    // Taille du foyer
    if (!empty($criteria['household_min']) && $panelist['household_size'] < $criteria['household_min']) {
        return false;
    }
    if (!empty($criteria['household_max']) && $panelist['household_size'] > $criteria['household_max']) {
        return false;
    }

    // Équipement requis
    if (!empty($criteria['equipment_required'])) {
        $equipment = json_decode($panelist['equipment'] ?? '[]', true) ?? [];
        foreach ($criteria['equipment_required'] as $required) {
            if (!in_array($required, $equipment)) {
                return false;
            }
        }
    }

    // Équipement exclu
    if (!empty($criteria['equipment_excluded'])) {
        $equipment = json_decode($panelist['equipment'] ?? '[]', true) ?? [];
        foreach ($criteria['equipment_excluded'] as $excluded) {
            if (in_array($excluded, $equipment)) {
                return false;
            }
        }
    }

    // Marques (au moins une)
    if (!empty($criteria['brands_owned_any'])) {
        $brands = json_decode($panelist['brands_owned'] ?? '[]', true) ?? [];
        $found = false;
        foreach ($criteria['brands_owned_any'] as $brand) {
            if (in_array($brand, $brands)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return false;
        }
    }

    // Centres d'intérêt (au moins un)
    if (!empty($criteria['interests_any'])) {
        $interests = json_decode($panelist['interests'] ?? '[]', true) ?? [];
        $found = false;
        foreach ($criteria['interests_any'] as $interest) {
            if (in_array($interest, $interests)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return false;
        }
    }

    return true;
}

/**
 * Associe les panelistes éligibles à une solicitation
 *
 * @param int $solicitationId ID de la solicitation
 * @return int Nombre de panelistes associés
 */
function matchPanelistsToSolicitation(int $solicitationId): int {
    $panelistIds = findMatchingPanelists($solicitationId);

    $count = 0;
    foreach ($panelistIds as $panelistId) {
        try {
            dbExecute(
                "INSERT IGNORE INTO panelist_solicitations (panelist_id, solicitation_id, status)
                 VALUES (?, ?, 'eligible')",
                [$panelistId, $solicitationId]
            );
            $count++;
        } catch (Exception $e) {
            // Ignorer les doublons
        }
    }

    return $count;
}
