<?php
/**
 * ============================================================
 * SECURITE - CSRF & CORS
 * ============================================================
 *
 * Ce fichier centralise la gestion de la sécurité :
 * - Tokens CSRF pour protéger les actions admin
 * - Configuration CORS pour contrôler les origines autorisées
 */

// ============================================================
// CONFIGURATION CORS
// ============================================================

/**
 * Domaines autorisés à appeler l'API
 * Ajoute ici les domaines de ton application
 */
function getAllowedOrigins(): array {
    return [
        // Développement local
        'http://localhost',
        'http://localhost:8080',
        'http://127.0.0.1',

        // Ajoute ici ton domaine de production
        // 'https://ton-domaine.com',
        // 'https://www.ton-domaine.com',
    ];
}

/**
 * Applique les headers CORS sécurisés
 * Autorise uniquement les origines définies dans getAllowedOrigins()
 */
function applyCorsHeaders(): void {
    $allowedOrigins = getAllowedOrigins();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Autoriser aussi les requêtes sans origin (même domaine)
    if (empty($origin)) {
        // Requête du même domaine, pas besoin de CORS
        return;
    }

    // Vérifier si l'origine est autorisée
    $isAllowed = false;
    foreach ($allowedOrigins as $allowed) {
        if (strpos($origin, $allowed) === 0) {
            $isAllowed = true;
            break;
        }
    }

    if ($isAllowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
    // Si non autorisé, on n'envoie pas le header = le navigateur bloquera

    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400'); // Cache preflight 24h
}

/**
 * Gère les requêtes OPTIONS (preflight CORS)
 */
function handleCorsPreflightIfNeeded(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        applyCorsHeaders();
        http_response_code(200);
        exit;
    }
}

// ============================================================
// PROTECTION CSRF
// ============================================================

/**
 * Génère un token CSRF unique pour la session
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Récupère le token CSRF actuel
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

/**
 * Vérifie si le token CSRF fourni est valide
 *
 * @param string|null $token Le token à vérifier
 * @return bool True si valide, False sinon
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token)) {
        return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionToken)) {
        return false;
    }

    // Comparaison en temps constant pour éviter les timing attacks
    return hash_equals($sessionToken, $token);
}

/**
 * Extrait le token CSRF de la requête
 * Cherche dans : header X-CSRF-Token, POST csrf_token, JSON csrf_token
 */
function getCsrfTokenFromRequest(): ?string {
    // 1. Header HTTP (recommandé pour les appels AJAX)
    $headers = getallheaders();
    if (isset($headers['X-CSRF-Token'])) {
        return $headers['X-CSRF-Token'];
    }
    if (isset($headers['X-Csrf-Token'])) {
        return $headers['X-Csrf-Token'];
    }

    // 2. Paramètre POST
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }

    // 3. Corps JSON
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $json = json_decode($input, true);
        if (isset($json['csrf_token'])) {
            return $json['csrf_token'];
        }
    }

    return null;
}

/**
 * Vérifie la protection CSRF et arrête si invalide
 * À appeler au début des endpoints qui modifient des données
 */
function requireCsrfToken(): void {
    $token = getCsrfTokenFromRequest();

    if (!validateCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF invalide ou manquant. Veuillez rafraîchir la page.'
        ]);
        exit;
    }
}

/**
 * Génère un champ input hidden avec le token CSRF
 * À utiliser dans les formulaires HTML
 */
function csrfField(): string {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Génère une balise meta avec le token CSRF
 * À utiliser dans le head pour les appels AJAX
 */
function csrfMeta(): string {
    $token = getCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}
