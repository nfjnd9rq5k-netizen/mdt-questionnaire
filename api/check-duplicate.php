<?php
/**
 * ============================================================
 * VÉRIFICATION ANTI-DOUBLONS
 * ============================================================
 * Vérifie si un email a déjà participé à une étude
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['studyId'])) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$email = strtolower(trim($input['email']));
$studyId = $input['studyId'];

try {
    // Récupérer l'ID interne de l'étude
    $study = dbQueryOne(
        "SELECT id FROM studies WHERE study_id = ?",
        [$studyId]
    );
    
    if (!$study) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    // Chercher si cet email a déjà participé
    $existing = dbQueryOne(
        "SELECT r.id, r.status, r.completed_at 
         FROM responses r 
         JOIN signaletiques s ON r.id = s.response_id 
         WHERE r.study_id = ? AND LOWER(s.email) = ?",
        [$study['id'], $email]
    );
    
    if ($existing) {
        echo json_encode([
            'exists' => true,
            'status' => $existing['status'],
            'message' => 'Cet email a déjà participé à cette étude.'
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
} catch (Exception $e) {
    error_log("Erreur check-duplicate: " . $e->getMessage());
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
