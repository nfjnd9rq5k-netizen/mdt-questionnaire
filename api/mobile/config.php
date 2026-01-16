<?php
/**
 * Configuration API Mobile
 * Paramètres JWT et sécurité
 */

// Durée de validité des tokens
define('JWT_ACCESS_TOKEN_EXPIRE', 3600);       // 1 heure
define('JWT_REFRESH_TOKEN_EXPIRE', 2592000);   // 30 jours

// Clé secrète JWT (sera générée automatiquement si absente)
define('JWT_SECRET_FILE', __DIR__ . '/../secure_data/.jwt_secret');

// Limites de sécurité
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('MAX_SESSIONS_PER_USER', 5);

// Points par défaut
define('DEFAULT_STUDY_POINTS', 50);
define('REFERRAL_BONUS_POINTS', 100);

// Validation email
define('EMAIL_VERIFICATION_EXPIRE', 86400);    // 24 heures

/**
 * Récupère ou génère la clé secrète JWT
 */
function getJwtSecret(): string {
    if (file_exists(JWT_SECRET_FILE)) {
        return trim(file_get_contents(JWT_SECRET_FILE));
    }

    // Générer une nouvelle clé
    $secret = bin2hex(random_bytes(32));
    $dir = dirname(JWT_SECRET_FILE);

    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    file_put_contents(JWT_SECRET_FILE, $secret);
    chmod(JWT_SECRET_FILE, 0600);

    return $secret;
}

/**
 * Headers CORS pour l'API mobile
 */
function setCorsHeaders(): void {
    // En production, remplacer * par le domaine de l'app
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    // Preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Réponse JSON standardisée
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Réponse d'erreur
 */
function jsonError(string $message, int $statusCode = 400, ?string $code = null): void {
    $response = ['success' => false, 'error' => $message];
    if ($code) {
        $response['code'] = $code;
    }
    jsonResponse($response, $statusCode);
}

/**
 * Réponse de succès
 */
function jsonSuccess(array $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Récupère le body JSON de la requête
 */
function getJsonBody(): array {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Invalid JSON body', 400, 'INVALID_JSON');
    }

    return $data ?? [];
}

/**
 * Valide les champs requis
 */
function validateRequired(array $data, array $fields): void {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        jsonError('Missing required fields: ' . implode(', ', $missing), 400, 'MISSING_FIELDS');
    }
}

/**
 * Valide un email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un ID unique pour les panelistes
 */
function generatePanelistId(): string {
    return 'PAN_' . strtoupper(bin2hex(random_bytes(8)));
}

/**
 * Génère un token aléatoire
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}
