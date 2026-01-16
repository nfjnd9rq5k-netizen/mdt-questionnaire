<?php
/**
 * API Mobile - Vérifier si un email existe dans les données importées
 * POST /api/mobile/check-imported.php
 *
 * Body: { email, panel_id? }
 *
 * Retourne les données pré-remplies si trouvé
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$data = getJsonBody();
validateRequired($data, ['email']);

$email = strtolower(trim($data['email']));
$panelId = isset($data['panel_id']) ? intval($data['panel_id']) : null;

// Validation email
if (!validateEmail($email)) {
    jsonError('Invalid email format', 400, 'INVALID_EMAIL');
}

// Recherche dans les données importées
$query = "SELECT * FROM panel_imported WHERE email = ? AND claimed = 0";
$params = [$email];

// Si panel_id fourni, recherche plus précise
if ($panelId) {
    $query .= " AND panel_id = ?";
    $params[] = $panelId;
}

$query .= " AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1";

$imported = dbQueryOne($query, $params);

if (!$imported) {
    jsonSuccess([
        'found' => false,
        'message' => 'Aucune donnée existante trouvée'
    ]);
}

// Prépare les données pour l'affichage (masque certaines infos sensibles)
$prefilledData = [
    'panel_id' => $imported['panel_id'],
    'civilite' => $imported['civilite'],
    'nom' => $imported['nom'],
    'prenom' => $imported['prenom'],
    'email' => $imported['email'],
    'region' => $imported['region'],
    'ville' => $imported['ville'],
    'code_postal' => $imported['code_postal'],
    'departement' => $imported['departement'],
    // Masque partiellement l'adresse
    'adresse_preview' => $imported['adresse'] ? substr($imported['adresse'], 0, 20) . '...' : null,
    // Masque partiellement le téléphone
    'tel_portable_preview' => $imported['tel_portable'] ? '******' . substr($imported['tel_portable'], -4) : null,
    'date_naissance' => $imported['date_naissance'],
    'age' => $imported['age'],
    'situation_familiale' => $imported['situation_familiale'],
    'situation_professionnelle' => $imported['situation_professionnelle'],
    'enfants_au_foyer' => $imported['enfants_au_foyer'],
    'profession' => $imported['profession'],
    'secteur_activite' => $imported['secteur_activite'],
    // Données complètes disponibles
    'has_conjoint' => !empty($imported['conjoint_data']),
    'has_children' => $imported['enfants_au_foyer'] > 0,
    'has_vehicle' => $imported['possede_voiture'] || $imported['possede_moto']
];

jsonSuccess([
    'found' => true,
    'data' => $prefilledData,
    'message' => 'Données existantes trouvées. Vous pouvez les récupérer lors de l\'inscription.'
]);
