<?php
/**
 * API Mobile - Enregistrer le token push Firebase
 * POST /api/mobile/push-token.php
 *
 * Body: { token, device_type? }
 * Header: Authorization: Bearer <access_token>
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
validateRequired($data, ['token']);

$pushToken = trim($data['token']);

if (empty($pushToken)) {
    jsonError('Token cannot be empty', 400, 'INVALID_TOKEN');
}

try {
    // Vérifier si le token est utilisé par un autre paneliste
    $existing = dbQueryOne(
        "SELECT id FROM panelists WHERE push_token = ? AND id != ?",
        [$pushToken, $panelistId]
    );

    if ($existing) {
        // Supprimer l'ancien token (changement d'appareil)
        dbExecute(
            "UPDATE panelists SET push_token = NULL WHERE id = ?",
            [$existing['id']]
        );
    }

    // Mettre à jour le token
    dbExecute(
        "UPDATE panelists SET push_token = ?, last_active = NOW() WHERE id = ?",
        [$pushToken, $panelistId]
    );

    jsonSuccess([], 'Push token registered');

} catch (Exception $e) {
    error_log("Push token error: " . $e->getMessage());
    jsonError('Failed to register token', 500, 'SERVER_ERROR');
}
