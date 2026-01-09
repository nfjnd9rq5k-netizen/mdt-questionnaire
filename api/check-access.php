<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

$studyId = $input['studyId'] ?? '';
$accessId = $input['accessId'] ?? '';

if (empty($studyId) || empty($accessId)) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$studyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyId);

$studiesDir = __DIR__ . '/../studies';
$studyFolder = null;
$accessFile = null;

if (is_dir($studiesDir)) {
    $dirs = scandir($studiesDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || strpos($dir, '_') === 0) continue;
        
        $questionsFile = $studiesDir . '/' . $dir . '/questions.js';
        if (file_exists($questionsFile)) {
            $content = file_get_contents($questionsFile);
            if (preg_match("/studyId:\s*['\"]" . preg_quote($studyId) . "['\"]/", $content)) {
                $studyFolder = $dir;
                $accessFile = $studiesDir . '/' . $dir . '/data/access_ids.json';
                break;
            }
        }
    }
}

if (!$studyFolder) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Étude non trouvée']);
    exit;
}

$dataDir = dirname($accessFile);
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$authorizedIds = [];
if (file_exists($accessFile)) {
    $data = json_decode(file_get_contents($accessFile), true);
    $authorizedIds = $data['ids'] ?? [];
}

$accessIdLower = strtolower(trim($accessId));
$isValid = false;

foreach ($authorizedIds as $id) {
    if (strtolower(trim($id)) === $accessIdLower) {
        $isValid = true;
        break;
    }
}

if (!$isValid) {
    echo json_encode([
        'success' => true,
        'valid' => false,
        'message' => 'Identifiant non reconnu. Veuillez vérifier votre identifiant.'
    ]);
    exit;
}

$existingProgress = null;
$isCompleted = false;

function decryptData($encryptedData) {
    global $studyFolder;
    $parts = explode('::', base64_decode($encryptedData), 2);
    if (count($parts) !== 2) return null;
    
    list($iv, $encrypted) = $parts;
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return json_decode($decrypted, true);
}

$responsesFile = $studiesDir . '/' . $studyFolder . '/data/responses.enc';
if (file_exists($responsesFile)) {
    $content = file_get_contents($responsesFile);
    $responses = decryptData($content) ?: [];
    
    foreach ($responses as $response) {
        if (isset($response['accessId']) && strtolower(trim($response['accessId'])) === $accessIdLower) {
            $existingProgress = $response;
            $isCompleted = ($response['statut'] === 'QUALIFIE');
            break;
        }
    }
}

if (!$existingProgress) {
    $refusedFile = $studiesDir . '/' . $studyFolder . '/data/refused.enc';
    if (file_exists($refusedFile)) {
        $content = file_get_contents($refusedFile);
        $refused = decryptData($content) ?: [];
        
        foreach ($refused as $response) {
            if (isset($response['accessId']) && strtolower(trim($response['accessId'])) === $accessIdLower) {
                $existingProgress = $response;
                $isCompleted = ($response['statut'] === 'REFUSE');
                break;
            }
        }
    }
}

$result = [
    'success' => true,
    'valid' => true,
    'message' => 'Accès autorisé'
];

if ($existingProgress) {
    $result['hasProgress'] = true;
    $result['isCompleted'] = $isCompleted;
    $result['progress'] = [
        'id' => $existingProgress['id'],
        'signaletique' => $existingProgress['signaletique'] ?? [],
        'horaire' => $existingProgress['horaire'] ?? '',
        'reponses' => $existingProgress['reponses'] ?? [],
        'statut' => $existingProgress['statut'] ?? 'EN_COURS',
        'dateDebut' => $existingProgress['dateDebut'] ?? null
    ];
} else {
    $result['hasProgress'] = false;
    $result['isCompleted'] = false;
}

echo json_encode($result);
