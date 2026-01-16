<?php
/**
 * API Mobile - Réclamer les données importées après inscription
 * POST /api/mobile/claim-imported.php
 *
 * Body: { panel_id }
 *
 * Authentification requise (JWT)
 * Récupère les données importées et les copie dans le profil du panéliste
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Vérification JWT
$panelist = requireAuth();

$data = getJsonBody();
validateRequired($data, ['panel_id']);

$panelId = intval($data['panel_id']);

// Récupère les données importées correspondant à l'email du panéliste
$imported = dbQueryOne(
    "SELECT * FROM panel_imported
     WHERE panel_id = ?
     AND email = ?
     AND claimed = 0
     AND (expires_at IS NULL OR expires_at > NOW())",
    [$panelId, $panelist['email']]
);

if (!$imported) {
    jsonError('Données non trouvées ou déjà réclamées', 404, 'NOT_FOUND');
}

try {
    // Démarre une transaction
    dbExecute("START TRANSACTION");

    // Conversion du genre
    $gender = null;
    if ($imported['civilite']) {
        $civ = strtolower($imported['civilite']);
        if (in_array($civ, ['m.', 'm', 'monsieur', 'mr'])) {
            $gender = 'M';
        } elseif (in_array($civ, ['mme', 'melle', 'madame', 'mademoiselle'])) {
            $gender = 'F';
        }
    }

    // Extraction des enfants pour calculer has_children et children_ages
    $hasChildren = $imported['enfants_au_foyer'] > 0;
    $childrenAges = null;

    if ($hasChildren && $imported['enfants_data']) {
        $enfantsData = json_decode($imported['enfants_data'], true);
        if (is_array($enfantsData)) {
            $ages = [];
            $currentYear = intval(date('Y'));
            foreach ($enfantsData as $enfant) {
                if (!empty($enfant['date_naissance'])) {
                    $birthYear = intval(substr($enfant['date_naissance'], 0, 4));
                    if ($birthYear > 0) {
                        $ages[] = $currentYear - $birthYear;
                    }
                }
            }
            if (!empty($ages)) {
                $childrenAges = json_encode($ages);
            }
        }
    }

    // Équipements
    $equipment = [];
    if ($imported['equipements']) {
        $equip = json_decode($imported['equipements'], true);
        if (is_array($equip)) {
            foreach ($equip as $key => $val) {
                if ($val) {
                    $equipment[] = $key;
                }
            }
        }
    }
    if ($imported['possede_voiture']) {
        $equipment[] = 'voiture';
    }
    if ($imported['possede_moto']) {
        $equipment[] = 'moto';
    }

    // Met à jour le profil du panéliste
    dbExecute(
        "UPDATE panelists SET
            phone = COALESCE(?, phone),
            gender = COALESCE(?, gender),
            birth_date = COALESCE(?, birth_date),
            region = COALESCE(?, region),
            city = COALESCE(?, city),
            postal_code = COALESCE(?, postal_code),
            csp = COALESCE(?, csp),
            household_size = COALESCE(?, household_size),
            has_children = COALESCE(?, has_children),
            children_ages = COALESCE(?, children_ages),
            equipment = COALESCE(?, equipment),
            updated_at = NOW()
         WHERE id = ?",
        [
            $imported['tel_portable'] ?: $imported['tel_domicile'],
            $gender,
            $imported['date_naissance'],
            $imported['region'],
            $imported['ville'],
            $imported['code_postal'],
            $imported['profession'], // CSP
            $imported['enfants_au_foyer'] + 1, // +1 pour le panéliste lui-même
            $hasChildren ? 1 : 0,
            $childrenAges,
            !empty($equipment) ? json_encode($equipment) : null,
            $panelist['id']
        ]
    );

    // Marque les données importées comme réclamées
    dbExecute(
        "UPDATE panel_imported SET
            claimed = 1,
            claimed_by = ?,
            claimed_at = NOW()
         WHERE id = ?",
        [$panelist['id'], $imported['id']]
    );

    dbExecute("COMMIT");

    // Récupère le profil mis à jour
    $updatedPanelist = dbQueryOne(
        "SELECT id, unique_id, email, phone, gender, birth_date, region, city,
                postal_code, csp, household_size, has_children, children_ages, equipment
         FROM panelists WHERE id = ?",
        [$panelist['id']]
    );

    jsonSuccess([
        'claimed' => true,
        'profile' => [
            'phone' => $updatedPanelist['phone'],
            'gender' => $updatedPanelist['gender'],
            'birth_date' => $updatedPanelist['birth_date'],
            'region' => $updatedPanelist['region'],
            'city' => $updatedPanelist['city'],
            'postal_code' => $updatedPanelist['postal_code'],
            'csp' => $updatedPanelist['csp'],
            'household_size' => $updatedPanelist['household_size'],
            'has_children' => (bool)$updatedPanelist['has_children'],
            'children_ages' => $updatedPanelist['children_ages'] ? json_decode($updatedPanelist['children_ages']) : null,
            'equipment' => $updatedPanelist['equipment'] ? json_decode($updatedPanelist['equipment']) : []
        ],
        'imported_data' => [
            'nom' => $imported['nom'],
            'prenom' => $imported['prenom'],
            'adresse' => $imported['adresse'],
            'situation_familiale' => $imported['situation_familiale'],
            'profession' => $imported['profession'],
            'secteur_activite' => $imported['secteur_activite']
        ],
        'message' => 'Données récupérées avec succès. Veuillez vérifier votre profil.'
    ], 'Données importées réclamées');

} catch (Exception $e) {
    dbExecute("ROLLBACK");
    error_log("Claim imported error: " . $e->getMessage());
    jsonError('Erreur lors de la récupération des données', 500, 'SERVER_ERROR');
}
