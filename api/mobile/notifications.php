<?php
/**
 * API Mobile - Notifications
 * GET /api/mobile/notifications.php - Liste les notifications du paneliste
 *
 * Query params: ?limit=20&unread_only=false
 * Header: Authorization: Bearer <access_token>
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setCorsHeaders();

$auth = requireAuth();
$panelistId = $auth['sub'];

// GET - Liste des notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 20;
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

    $sql = "SELECT n.id, n.title, n.body, n.data, n.status, n.created_at, n.read_at,
                   s.id as solicitation_id, s.title as study_title
            FROM push_notifications n
            LEFT JOIN solicitations s ON n.solicitation_id = s.id
            WHERE n.panelist_id = ?";

    $params = [$panelistId];

    if ($unreadOnly) {
        $sql .= " AND n.read_at IS NULL";
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT ?";
    $params[] = $limit;

    $notifications = dbQuery($sql, $params);

    // Compter les non lues
    $unreadCount = dbQueryOne(
        "SELECT COUNT(*) as count FROM push_notifications WHERE panelist_id = ? AND read_at IS NULL",
        [$panelistId]
    );

    $formattedNotifications = array_map(function($n) {
        return [
            'id' => (int) $n['id'],
            'title' => $n['title'],
            'body' => $n['body'],
            'data' => $n['data'] ? json_decode($n['data'], true) : null,
            'study_title' => $n['study_title'],
            'solicitation_id' => $n['solicitation_id'] ? (int) $n['solicitation_id'] : null,
            'is_read' => $n['read_at'] !== null,
            'created_at' => $n['created_at'],
            'read_at' => $n['read_at']
        ];
    }, $notifications);

    jsonSuccess([
        'notifications' => $formattedNotifications,
        'unread_count' => (int) $unreadCount['count']
    ]);
}

// POST - Marquer comme lue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getJsonBody();

    // Marquer une notification spécifique
    if (isset($data['notification_id'])) {
        $notifId = (int) $data['notification_id'];

        dbExecute(
            "UPDATE push_notifications SET read_at = NOW(), status = 'read'
             WHERE id = ? AND panelist_id = ? AND read_at IS NULL",
            [$notifId, $panelistId]
        );

        jsonSuccess([], 'Notification marked as read');
    }

    // Marquer toutes comme lues
    if (isset($data['mark_all_read']) && $data['mark_all_read']) {
        dbExecute(
            "UPDATE push_notifications SET read_at = NOW(), status = 'read'
             WHERE panelist_id = ? AND read_at IS NULL",
            [$panelistId]
        );

        jsonSuccess([], 'All notifications marked as read');
    }

    jsonError('Invalid request', 400, 'INVALID_REQUEST');
}

jsonError('Method not allowed', 405);
