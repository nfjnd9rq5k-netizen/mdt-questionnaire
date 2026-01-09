<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['studyFolder']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$studyFolder = basename($input['studyFolder']);
$action = $input['action'];

$studyPath = __DIR__ . '/../studies/' . $studyFolder;
$statusFile = $studyPath . '/status.json';

if (!is_dir($studyPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Étude non trouvée']);
    exit;
}

$currentStatus = ['status' => 'active', 'closedAt' => null, 'closedBy' => null];
if (file_exists($statusFile)) {
    $currentStatus = json_decode(file_get_contents($statusFile), true) ?: $currentStatus;
}

switch ($action) {
    case 'close':
        $currentStatus['status'] = 'closed';
        $currentStatus['closedAt'] = date('c');
        $currentStatus['closedBy'] = $_SESSION['admin_user'] ?? 'admin';
        break;
        
    case 'open':
    case 'reopen':
        $currentStatus['status'] = 'active';
        $currentStatus['closedAt'] = null;
        $currentStatus['closedBy'] = null;
        $currentStatus['reopenedAt'] = date('c');
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
        exit;
}

if (file_put_contents($statusFile, json_encode($currentStatus, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'status' => $currentStatus['status'],
        'message' => $action === 'close' ? 'Étude fermée' : 'Étude réouverte'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de sauvegarde']);
}
