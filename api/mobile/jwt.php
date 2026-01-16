<?php
/**
 * JWT Authentication Library
 * Implémentation simple sans dépendances externes
 */

require_once __DIR__ . '/config.php';

/**
 * Encode en base64 URL-safe
 */
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decode base64 URL-safe
 */
function base64UrlDecode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Génère un Access Token JWT
 */
function generateAccessToken(array $payload): string {
    $secret = getJwtSecret();

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_ACCESS_TOKEN_EXPIRE;
    $payload['type'] = 'access';

    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64UrlEncode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Génère un Refresh Token JWT
 */
function generateRefreshToken(int $panelistId): string {
    $secret = getJwtSecret();

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $payload = [
        'sub' => $panelistId,
        'iat' => time(),
        'exp' => time() + JWT_REFRESH_TOKEN_EXPIRE,
        'type' => 'refresh',
        'jti' => bin2hex(random_bytes(16)) // Unique ID for this token
    ];

    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64UrlEncode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Vérifie et décode un token JWT
 */
function verifyToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

    $secret = getJwtSecret();
    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true)
    );

    // Vérification signature (timing-safe)
    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        return null;
    }

    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if (!$payload) {
        return null;
    }

    // Vérification expiration
    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

/**
 * Extrait le token du header Authorization
 */
function extractBearerToken(): ?string {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    // Fallback: query parameter (pour WebView)
    if (isset($_GET['token'])) {
        return $_GET['token'];
    }

    return null;
}

/**
 * Middleware d'authentification - récupère le paneliste connecté
 */
function requireAuth(): array {
    $token = extractBearerToken();

    if (!$token) {
        jsonError('Authentication required', 401, 'AUTH_REQUIRED');
    }

    $payload = verifyToken($token);

    if (!$payload) {
        jsonError('Invalid or expired token', 401, 'INVALID_TOKEN');
    }

    if (($payload['type'] ?? '') !== 'access') {
        jsonError('Invalid token type', 401, 'INVALID_TOKEN_TYPE');
    }

    return $payload;
}

/**
 * Middleware optionnel - récupère le paneliste si connecté
 */
function optionalAuth(): ?array {
    $token = extractBearerToken();

    if (!$token) {
        return null;
    }

    $payload = verifyToken($token);

    if (!$payload || ($payload['type'] ?? '') !== 'access') {
        return null;
    }

    return $payload;
}

/**
 * Génère les deux tokens (access + refresh)
 */
function generateTokenPair(array $panelist): array {
    $accessPayload = [
        'sub' => $panelist['id'],
        'unique_id' => $panelist['unique_id'],
        'email' => $panelist['email']
    ];

    return [
        'access_token' => generateAccessToken($accessPayload),
        'refresh_token' => generateRefreshToken($panelist['id']),
        'expires_in' => JWT_ACCESS_TOKEN_EXPIRE,
        'token_type' => 'Bearer'
    ];
}
