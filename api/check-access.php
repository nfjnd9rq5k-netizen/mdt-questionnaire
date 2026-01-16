<?php
/**
 * Vérification des IDs d'accès - Version MySQL
 */
header('Content-Type: application/json');

// Sécurité CORS - Charger la configuration
require_once __DIR__ . '/security.php';
applyCorsHeaders();
handleCorsPreflightIfNeeded();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

require_once 'config.php';
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

$studyId = $input['studyId'] ?? '';
$accessId = $input['accessId'] ?? '';

if (empty($studyId) || empty($accessId)) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$studyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyId);
$accessIdClean = trim($accessId);

// Trouver l'étude dans MySQL
$study = dbQueryOne(
    "SELECT id, folder_name FROM studies WHERE study_id = ? OR folder_name = ?",
    [$studyId, $studyId]
);

if (!$study) {
    // Fallback: chercher dans les dossiers et créer l'étude si elle existe
    $studiesDir = __DIR__ . '/../studies';
    $studyFolder = null;
    
    if (is_dir($studiesDir)) {
        $dirs = scandir($studiesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || strpos($dir, '_') === 0) continue;
            
            $questionsFile = $studiesDir . '/' . $dir . '/questions.js';
            if (file_exists($questionsFile)) {
                $content = file_get_contents($questionsFile);
                if (preg_match("/studyId:\s*['\"]" . preg_quote($studyId) . "['\"]/", $content)) {
                    $studyFolder = $dir;
                    
                    // Créer l'étude dans MySQL
                    $title = $studyId;
                    if (preg_match("/studyTitle:\s*['\"]([^'\"]+)['\"]/", $content, $m)) {
                        $title = $m[1];
                    }
                    
                    dbExecute(
                        "INSERT INTO studies (study_id, folder_name, title, status) VALUES (?, ?, ?, 'active')",
                        [$studyId, $dir, $title]
                    );
                    
                    $study = [
                        'id' => dbLastId(),
                        'folder_name' => $dir
                    ];
                    break;
                }
            }
        }
    }
    
    if (!$study) {
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'Étude non trouvée']);
        exit;
    }
}

$studyDbId = $study['id'];
$studyFolder = $study['folder_name'];

// Vérifier si l'ID d'accès est autorisé (dans MySQL)
$accessIdRecord = dbQueryOne(
    "SELECT id FROM access_ids WHERE study_id = ? AND LOWER(access_code) = LOWER(?)",
    [$studyDbId, $accessIdClean]
);

// Fallback: vérifier dans le fichier JSON si pas trouvé dans MySQL
if (!$accessIdRecord) {
    $accessFile = __DIR__ . '/../studies/' . $studyFolder . '/data/access_ids.json';
    $isValidInFile = false;
    
    if (file_exists($accessFile)) {
        $fileData = json_decode(file_get_contents($accessFile), true);
        if (is_array($fileData)) {
            // Parcourir récursivement pour gérer les tableaux imbriqués
            $flattenIds = function($arr) use (&$flattenIds) {
                $result = [];
                foreach ($arr as $item) {
                    if (is_array($item)) {
                        $result = array_merge($result, $flattenIds($item));
                    } elseif (is_string($item)) {
                        $result[] = strtolower(trim($item));
                    }
                }
                return $result;
            };
            
            $allIds = $flattenIds($fileData);
            $isValidInFile = in_array(strtolower($accessIdClean), $allIds);
            
            // Si trouvé dans le fichier, l'ajouter à MySQL pour la prochaine fois
            if ($isValidInFile) {
                $existing = dbQueryOne(
                    "SELECT id FROM access_ids WHERE study_id = ? AND access_code = ?",
                    [$studyDbId, $accessIdClean]
                );
                if (!$existing) {
                    dbExecute(
                        "INSERT INTO access_ids (study_id, access_code) VALUES (?, ?)",
                        [$studyDbId, $accessIdClean]
                    );
                }
            }
        }
    }
    
    if (!$isValidInFile) {
        echo json_encode([
            'success' => true,
            'valid' => false,
            'message' => 'Identifiant non reconnu. Veuillez vérifier votre identifiant.'
        ]);
        exit;
    }
}

// Vérifier si le participant a déjà une progression (dans MySQL)
$existingResponse = dbQueryOne(
    "SELECT r.id, r.unique_id, r.status, r.horaire, r.started_at, r.completed_at,
            s.nom, s.prenom, s.email, s.telephone, s.adresse, s.code_postal, s.ville
     FROM responses r
     LEFT JOIN signaletiques s ON s.response_id = r.id
     WHERE r.study_id = ? AND LOWER(r.access_id) = LOWER(?)",
    [$studyDbId, $accessIdClean]
);

$result = [
    'success' => true,
    'valid' => true,
    'message' => 'Accès autorisé'
];

if ($existingResponse) {
    // Récupérer les réponses
    $answers = dbQuery(
        "SELECT question_id, answer_value, answer_values, answer_text, answer_data 
         FROM answers WHERE response_id = ?",
        [$existingResponse['id']]
    );
    
    $reponses = [];
    foreach ($answers as $a) {
        $data = [];
        if ($a['answer_value'] !== null) {
            $data['value'] = $a['answer_value'];
        }
        if ($a['answer_values'] !== null) {
            $decoded = json_decode($a['answer_values'], true);
            if ($decoded !== null) {
                $data['values'] = $decoded;
            }
        }
        if ($a['answer_text'] !== null) {
            $data['text'] = $a['answer_text'];
        }
        if ($a['answer_data'] !== null) {
            $extraData = json_decode($a['answer_data'], true);
            if ($extraData) {
                $data = array_merge($data, $extraData);
            }
        }
        if (!empty($data)) {
            $reponses[$a['question_id']] = $data;
        }
    }
    
    $isCompleted = in_array($existingResponse['status'], ['QUALIFIE', 'REFUSE']);
    
    $result['hasProgress'] = true;
    $result['isCompleted'] = $isCompleted;
    $result['progress'] = [
        'id' => $existingResponse['unique_id'],
        'signaletique' => [
            'nom' => $existingResponse['nom'] ?? '',
            'prenom' => $existingResponse['prenom'] ?? '',
            'email' => $existingResponse['email'] ?? '',
            'telephone' => $existingResponse['telephone'] ?? '',
            'adresse' => $existingResponse['adresse'] ?? '',
            'codePostal' => $existingResponse['code_postal'] ?? '',
            'ville' => $existingResponse['ville'] ?? ''
        ],
        'horaire' => $existingResponse['horaire'] ?? '',
        'reponses' => $reponses,
        'statut' => $existingResponse['status'] ?? 'EN_COURS',
        'dateDebut' => $existingResponse['started_at'] ?? null
    ];
} else {
    $result['hasProgress'] = false;
    $result['isCompleted'] = false;
}

echo json_encode($result);