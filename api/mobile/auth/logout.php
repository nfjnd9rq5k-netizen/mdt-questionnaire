<?php
/**
 * API Mobile - Déconnexion paneliste
 * POST /api/mobile/auth/logout.php
 *
 * Body: { refresh_token? } - Si fourni, supprime uniquement cette session
 * Header: Authorization: Bearer <access_token>
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$auth = requireAuth();
$panelistId = $auth['sub'];

$data = getJsonBody();
$refreshToken = $data['refresh_token'] ?? null;

try {
    if ($refreshToken) {
        // Supprimer uniquement cette session
        $tokenHash = hash('sha256', $refreshToken);
        dbExecute(
            "DELETE FROM panelist_sessions WHERE panelist_id = ? AND token_hash = ?",
            [$panelistId, $tokenHash]
        );
    } else {
        // Supprimer toutes les sessions (logout de tous les appareils)
        dbExecute(
            "DELETE FROM panelist_sessions WHERE panelist_id = ?",
            [$panelistId]
        );
    }

    jsonSuccess([], 'Logged out successfully');

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    jsonError('Logout failed', 500, 'SERVER_ERROR');
}
