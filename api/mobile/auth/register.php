<?php
/**
 * API Mobile - Inscription paneliste
 * POST /api/mobile/auth/register.php
 *
 * Body: { email, password, phone? }
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
$phone = isset($data['phone']) ? trim($data['phone']) : null;

// Validation email
if (!validateEmail($email)) {
    jsonError('Invalid email format', 400, 'INVALID_EMAIL');
}

// Validation password (minimum 8 caractères)
if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters', 400, 'WEAK_PASSWORD');
}

// Vérifier si email déjà utilisé
$existing = dbQueryOne("SELECT id FROM panelists WHERE email = ?", [$email]);
if ($existing) {
    jsonError('Email already registered', 409, 'EMAIL_EXISTS');
}

// Créer le paneliste
$uniqueId = generatePanelistId();
$passwordHash = password_hash($password, PASSWORD_ARGON2ID);
$verificationToken = generateToken(32);

try {
    dbExecute(
        "INSERT INTO panelists (unique_id, email, password_hash, phone, email_verification_token, status)
         VALUES (?, ?, ?, ?, ?, 'pending_verification')",
        [$uniqueId, $email, $passwordHash, $phone, $verificationToken]
    );

    $panelistId = dbLastId();

    // Récupérer le paneliste créé
    $panelist = dbQueryOne(
        "SELECT id, unique_id, email, status FROM panelists WHERE id = ?",
        [$panelistId]
    );

    // Générer les tokens
    $tokens = generateTokenPair($panelist);

    // TODO: Envoyer email de vérification
    // sendVerificationEmail($email, $verificationToken);

    jsonSuccess([
        'panelist' => [
            'id' => $panelist['unique_id'],
            'email' => $panelist['email'],
            'status' => $panelist['status'],
            'email_verified' => false
        ],
        'tokens' => $tokens,
        'message' => 'Account created. Please verify your email.'
    ], 'Registration successful');

} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    jsonError('Registration failed', 500, 'SERVER_ERROR');
}
