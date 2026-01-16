<?php
/**
 * API Mobile - Démarrer une étude
 * POST /api/mobile/study-start.php
 *
 * Body: { solicitation_id }
 * Header: Authorization: Bearer <access_token>
 *
 * Retourne l'URL à charger dans la WebView avec le token
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$auth = requireAuth();
$panelistId = $auth['sub'];

$data = getJsonBody();
validateRequired($data, ['solicitation_id']);

$solicitationId = (int) $data['solicitation_id'];

// Vérifier que la solicitation existe et est active
$solicitation = dbQueryOne(
    "SELECT s.*, st.folder_name as study_folder
     FROM solicitations s
     JOIN studies st ON s.study_id = st.id
     WHERE s.id = ?
       AND s.status = 'active'
       AND (s.expires_at IS NULL OR s.expires_at > NOW())",
    [$solicitationId]
);

if (!$solicitation) {
    jsonError('Study not found or not available', 404, 'STUDY_NOT_FOUND');
}

// Vérifier le quota
if ($solicitation['quota_target'] && $solicitation['quota_current'] >= $solicitation['quota_target']) {
    jsonError('Study quota reached', 410, 'QUOTA_REACHED');
}

// Vérifier/créer l'association panelist_solicitation
$existing = dbQueryOne(
    "SELECT id, status FROM panelist_solicitations
     WHERE panelist_id = ? AND solicitation_id = ?",
    [$panelistId, $solicitationId]
);

if ($existing) {
    if (in_array($existing['status'], ['completed', 'screened_out'])) {
        jsonError('You have already completed this study', 400, 'ALREADY_COMPLETED');
    }

    // Mettre à jour le statut
    dbExecute(
        "UPDATE panelist_solicitations
         SET status = 'started', started_at = COALESCE(started_at, NOW())
         WHERE id = ?",
        [$existing['id']]
    );
} else {
    // Créer l'association
    dbExecute(
        "INSERT INTO panelist_solicitations (panelist_id, solicitation_id, status, started_at)
         VALUES (?, ?, 'started', NOW())",
        [$panelistId, $solicitationId]
    );
}

// Récupérer le paneliste pour l'email
$panelist = dbQueryOne(
    "SELECT unique_id, email FROM panelists WHERE id = ?",
    [$panelistId]
);

// Générer un token spécifique pour le questionnaire (courte durée)
$webviewPayload = [
    'sub' => $panelistId,
    'panelist_unique_id' => $panelist['unique_id'],
    'email' => $panelist['email'],
    'solicitation_id' => $solicitationId,
    'type' => 'webview'
];

$webviewToken = generateAccessToken($webviewPayload);

// Construire l'URL du questionnaire
$baseUrl = $solicitation['study_url'];

// Ajouter le token et l'ID paneliste à l'URL
$separator = strpos($baseUrl, '?') !== false ? '&' : '?';
$studyUrl = $baseUrl . $separator . http_build_query([
    'token' => $webviewToken,
    'panelist_id' => $panelist['unique_id'],
    'solicitation_id' => $solicitationId
]);

// Mettre à jour last_active
dbExecute("UPDATE panelists SET last_active = NOW() WHERE id = ?", [$panelistId]);

jsonSuccess([
    'study_url' => $studyUrl,
    'webview_token' => $webviewToken,
    'study' => [
        'id' => (int) $solicitation['id'],
        'title' => $solicitation['title'],
        'estimated_duration' => $solicitation['estimated_duration'],
        'reward_points' => (int) $solicitation['reward_points']
    ]
], 'Study started');
