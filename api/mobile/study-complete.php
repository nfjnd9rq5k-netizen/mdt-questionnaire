<?php
/**
 * API Mobile - Marquer une étude comme terminée
 * POST /api/mobile/study-complete.php
 *
 * Body: { solicitation_id, response_unique_id?, status: 'completed'|'screened_out' }
 * Header: Authorization: Bearer <access_token>
 *
 * Peut être appelé par:
 * - L'app mobile après réception du postMessage
 * - Le questionnaire directement via callback
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
$status = $data['status'] ?? 'completed';
$responseUniqueId = $data['response_unique_id'] ?? null;

// Valider le statut
if (!in_array($status, ['completed', 'screened_out'])) {
    jsonError('Invalid status', 400, 'INVALID_STATUS');
}

// Vérifier l'association
$association = dbQueryOne(
    "SELECT ps.*, s.reward_points, s.title as study_title
     FROM panelist_solicitations ps
     JOIN solicitations s ON ps.solicitation_id = s.id
     WHERE ps.panelist_id = ? AND ps.solicitation_id = ?",
    [$panelistId, $solicitationId]
);

if (!$association) {
    jsonError('Study association not found', 404, 'NOT_FOUND');
}

if ($association['status'] === 'completed') {
    jsonError('Study already completed', 400, 'ALREADY_COMPLETED');
}

// Récupérer l'ID de response si fourni
$responseId = null;
if ($responseUniqueId) {
    $response = dbQueryOne(
        "SELECT id FROM responses WHERE unique_id = ?",
        [$responseUniqueId]
    );
    if ($response) {
        $responseId = $response['id'];
    }
}

try {
    // Commencer une transaction
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    // Mettre à jour l'association
    $pointsEarned = 0;
    if ($status === 'completed') {
        $pointsEarned = (int) $association['reward_points'];
    }

    dbExecute(
        "UPDATE panelist_solicitations
         SET status = ?, completed_at = NOW(), response_id = ?, points_earned = ?
         WHERE panelist_id = ? AND solicitation_id = ?",
        [$status, $responseId, $pointsEarned, $panelistId, $solicitationId]
    );

    // Si complété, ajouter les points
    if ($status === 'completed' && $pointsEarned > 0) {
        // Récupérer le solde actuel
        $panelist = dbQueryOne("SELECT points_balance FROM panelists WHERE id = ?", [$panelistId]);
        $newBalance = (int) $panelist['points_balance'] + $pointsEarned;

        // Mettre à jour le paneliste
        dbExecute(
            "UPDATE panelists
             SET points_balance = points_balance + ?,
                 points_lifetime = points_lifetime + ?,
                 studies_completed = studies_completed + 1,
                 last_active = NOW()
             WHERE id = ?",
            [$pointsEarned, $pointsEarned, $panelistId]
        );

        // Historique des points
        dbExecute(
            "INSERT INTO panelist_points_history
             (panelist_id, points, type, description, reference_id, balance_after)
             VALUES (?, ?, 'study_completed', ?, ?, ?)",
            [
                $panelistId,
                $pointsEarned,
                "Étude complétée: " . $association['study_title'],
                $solicitationId,
                $newBalance
            ]
        );

        // Incrémenter le quota de la solicitation
        dbExecute(
            "UPDATE solicitations SET quota_current = quota_current + 1 WHERE id = ?",
            [$solicitationId]
        );
    } else {
        // Juste mettre à jour last_active
        dbExecute("UPDATE panelists SET last_active = NOW() WHERE id = ?", [$panelistId]);
    }

    $pdo->commit();

    // Récupérer les nouvelles stats
    $stats = dbQueryOne(
        "SELECT points_balance, studies_completed FROM panelists WHERE id = ?",
        [$panelistId]
    );

    jsonSuccess([
        'status' => $status,
        'points_earned' => $pointsEarned,
        'new_balance' => (int) $stats['points_balance'],
        'studies_completed' => (int) $stats['studies_completed']
    ], $status === 'completed' ? 'Study completed! Points added.' : 'Study marked as screened out');

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Study complete error: " . $e->getMessage());
    jsonError('Failed to complete study', 500, 'SERVER_ERROR');
}

/**
 * Récupère la connexion PDO pour les transactions
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        require_once __DIR__ . '/../secure_data/db_config.php';
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    return $pdo;
}
