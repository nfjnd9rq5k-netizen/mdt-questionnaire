<?php
/**
 * API Mobile - Liste des études disponibles
 * GET /api/mobile/studies.php - Liste les études éligibles pour le paneliste
 *
 * Header: Authorization: Bearer <access_token>
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/matching.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$auth = requireAuth();
$panelistId = $auth['sub'];

// Mettre à jour last_active
dbExecute("UPDATE panelists SET last_active = NOW() WHERE id = ?", [$panelistId]);

// Récupérer les études éligibles
$eligibleSolicitations = findEligibleSolicitations($panelistId);

$studies = [];

foreach ($eligibleSolicitations as $sol) {
    $studies[] = [
        'id' => (int) $sol['id'],
        'title' => $sol['title'],
        'description' => $sol['description'],
        'estimated_duration' => $sol['estimated_duration'],
        'reward_points' => (int) $sol['reward_points'],
        'reward_description' => $sol['reward_description'],
        'image_url' => $sol['image_url'],
        'expires_at' => $sol['expires_at'],
        'status' => $sol['panelist_status'], // new, eligible, notified, viewed, started
        'priority' => (int) $sol['priority']
    ];
}

// Trier par priorité puis par statut (started en premier, puis viewed, etc.)
$statusOrder = ['started' => 0, 'viewed' => 1, 'notified' => 2, 'eligible' => 3, 'new' => 4];
usort($studies, function($a, $b) use ($statusOrder) {
    // D'abord par statut
    $statusA = $statusOrder[$a['status']] ?? 5;
    $statusB = $statusOrder[$b['status']] ?? 5;
    if ($statusA !== $statusB) {
        return $statusA - $statusB;
    }
    // Puis par priorité (décroissant)
    return $b['priority'] - $a['priority'];
});

jsonSuccess([
    'studies' => $studies,
    'count' => count($studies)
]);
