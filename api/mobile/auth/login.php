<?php
/**
 * API Mobile - Connexion paneliste
 * POST /api/mobile/auth/login.php
 *
 * Body: { email, password, device_info? }
 */

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$data = getJsonBody();
validateRequired($data, ['email', 'password']);

$email = strtolower(trim($data['email']));
$password = $data['password'];
$deviceInfo = isset($data['device_info']) ? trim($data['device_info']) : null;

// Récupérer le paneliste
$panelist = dbQueryOne(
    "SELECT id, unique_id, email, password_hash, status, email_verified,
            points_balance, studies_completed
     FROM panelists WHERE email = ?",
    [$email]
);

if (!$panelist) {
    // Délai pour éviter timing attack
    password_verify($password, '$argon2id$v=19$m=65536,t=4,p=1$dummy$dummy');
    jsonError('Invalid credentials', 401, 'INVALID_CREDENTIALS');
}

// Vérifier le statut du compte
if ($panelist['status'] === 'blacklisted') {
    jsonError('Account suspended', 403, 'ACCOUNT_SUSPENDED');
}

if ($panelist['status'] === 'inactive') {
    jsonError('Account inactive', 403, 'ACCOUNT_INACTIVE');
}

// Vérifier le mot de passe
if (!password_verify($password, $panelist['password_hash'])) {
    jsonError('Invalid credentials', 401, 'INVALID_CREDENTIALS');
}

// Générer les tokens
$tokens = generateTokenPair($panelist);

// Enregistrer la session (refresh token)
$tokenHash = hash('sha256', $tokens['refresh_token']);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$expiresAt = date('Y-m-d H:i:s', time() + JWT_REFRESH_TOKEN_EXPIRE);

try {
    // Nettoyer les anciennes sessions expirées
    dbExecute(
        "DELETE FROM panelist_sessions WHERE panelist_id = ? AND expires_at < NOW()",
        [$panelist['id']]
    );

    // Limiter le nombre de sessions actives
    $sessionCount = dbQueryOne(
        "SELECT COUNT(*) as count FROM panelist_sessions WHERE panelist_id = ?",
        [$panelist['id']]
    );

    if ($sessionCount && $sessionCount['count'] >= MAX_SESSIONS_PER_USER) {
        // Supprimer la session la plus ancienne
        dbExecute(
            "DELETE FROM panelist_sessions
             WHERE panelist_id = ?
             ORDER BY last_used ASC LIMIT 1",
            [$panelist['id']]
        );
    }

    // Créer la nouvelle session
    dbExecute(
        "INSERT INTO panelist_sessions (panelist_id, token_hash, device_info, ip_address, expires_at)
         VALUES (?, ?, ?, ?, ?)",
        [$panelist['id'], $tokenHash, $deviceInfo, $ipAddress, $expiresAt]
    );

    // Mettre à jour last_login
    dbExecute(
        "UPDATE panelists SET last_login = NOW(), last_active = NOW() WHERE id = ?",
        [$panelist['id']]
    );

    jsonSuccess([
        'panelist' => [
            'id' => $panelist['unique_id'],
            'email' => $panelist['email'],
            'status' => $panelist['status'],
            'email_verified' => (bool) $panelist['email_verified'],
            'points_balance' => (int) $panelist['points_balance'],
            'studies_completed' => (int) $panelist['studies_completed']
        ],
        'tokens' => $tokens
    ], 'Login successful');

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    jsonError('Login failed', 500, 'SERVER_ERROR');
}
