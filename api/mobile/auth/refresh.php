<?php
/**
 * API Mobile - Rafraîchir les tokens
 * POST /api/mobile/auth/refresh.php
 *
 * Body: { refresh_token }
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$data = getJsonBody();
validateRequired($data, ['refresh_token']);

$refreshToken = $data['refresh_token'];

// Vérifier le token
$payload = verifyToken($refreshToken);

if (!$payload) {
    jsonError('Invalid or expired refresh token', 401, 'INVALID_REFRESH_TOKEN');
}

if (($payload['type'] ?? '') !== 'refresh') {
    jsonError('Invalid token type', 401, 'INVALID_TOKEN_TYPE');
}

$panelistId = $payload['sub'] ?? null;
if (!$panelistId) {
    jsonError('Invalid token payload', 401, 'INVALID_TOKEN');
}

// Vérifier que la session existe en base
$tokenHash = hash('sha256', $refreshToken);
$session = dbQueryOne(
    "SELECT id FROM panelist_sessions
     WHERE panelist_id = ? AND token_hash = ? AND expires_at > NOW()",
    [$panelistId, $tokenHash]
);

if (!$session) {
    jsonError('Session not found or expired', 401, 'SESSION_EXPIRED');
}

// Récupérer le paneliste
$panelist = dbQueryOne(
    "SELECT id, unique_id, email, status FROM panelists WHERE id = ? AND status = 'active'",
    [$panelistId]
);

if (!$panelist) {
    // Supprimer la session invalide
    dbExecute("DELETE FROM panelist_sessions WHERE id = ?", [$session['id']]);
    jsonError('Account not found or inactive', 401, 'ACCOUNT_INACTIVE');
}

// Générer de nouveaux tokens
$newTokens = generateTokenPair($panelist);

// Mettre à jour la session avec le nouveau refresh token
$newTokenHash = hash('sha256', $newTokens['refresh_token']);
$newExpiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_TOKEN_EXPIRE);

try {
    dbExecute(
        "UPDATE panelist_sessions
         SET token_hash = ?, expires_at = ?, last_used = NOW()
         WHERE id = ?",
        [$newTokenHash, $newExpiresAt, $session['id']]
    );

    // Mettre à jour last_active
    dbExecute(
        "UPDATE panelists SET last_active = NOW() WHERE id = ?",
        [$panelist['id']]
    );

    jsonSuccess([
        'tokens' => $newTokens
    ], 'Tokens refreshed');

} catch (Exception $e) {
    error_log("Refresh error: " . $e->getMessage());
    jsonError('Token refresh failed', 500, 'SERVER_ERROR');
}
