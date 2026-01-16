<?php
/**
 * API Mobile - Historique des points
 * GET /api/mobile/points-history.php - Liste l'historique des points du paneliste
 *
 * Query params: ?limit=20&offset=0
 * Header: Authorization: Bearer <access_token>
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$auth = requireAuth();
$panelistId = $auth['sub'];

$limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
$offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

// Récupérer les stats du paneliste
$stats = dbQueryOne(
    "SELECT points_balance, points_lifetime, studies_completed FROM panelists WHERE id = ?",
    [$panelistId]
);

if (!$stats) {
    jsonError('Panelist not found', 404, 'NOT_FOUND');
}

// Récupérer l'historique
$history = dbQuery(
    "SELECT points, type, description, balance_after, created_at
     FROM panelist_points_history
     WHERE panelist_id = ?
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    [$panelistId, $limit, $offset]
);

// Compter le total
$total = dbQueryOne(
    "SELECT COUNT(*) as count FROM panelist_points_history WHERE panelist_id = ?",
    [$panelistId]
);

// Formater les données
$formattedHistory = array_map(function($row) {
    return [
        'points' => (int) $row['points'],
        'type' => $row['type'],
        'description' => $row['description'],
        'balance_after' => (int) $row['balance_after'],
        'date' => $row['created_at']
    ];
}, $history);

jsonSuccess([
    'stats' => [
        'current_balance' => (int) $stats['points_balance'],
        'lifetime_earned' => (int) $stats['points_lifetime'],
        'studies_completed' => (int) $stats['studies_completed']
    ],
    'history' => $formattedHistory,
    'pagination' => [
        'total' => (int) $total['count'],
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $total['count']
    ]
]);
