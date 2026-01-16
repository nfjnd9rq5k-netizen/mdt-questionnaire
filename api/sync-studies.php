<?php
/**
 * ============================================================
 * SYNCHRONISATION DES ÉTUDES - La Maison du Test
 * ============================================================
 * Scanne /studies et ajoute les nouvelles études en BDD
 * 
 * UTILISATION : /api/sync-studies.php
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

$studiesDir = __DIR__ . '/../studies';
$results = [
    'scanned' => 0,
    'added' => [],
    'already_exists' => [],
    'errors' => [],
    'skipped' => []
];

$ignoredFolders = ['_TEMPLATE_ETUDE', '.', '..'];
$folders = scandir($studiesDir);

foreach ($folders as $folder) {
    if (in_array($folder, $ignoredFolders) || !is_dir($studiesDir . '/' . $folder)) {
        continue;
    }
    
    if (is_file($studiesDir . '/' . $folder)) {
        continue;
    }
    
    $results['scanned']++;
    $studyPath = $studiesDir . '/' . $folder;
    $questionsFile = $studyPath . '/questions.js';
    
    if (!file_exists($questionsFile)) {
        $results['skipped'][] = ['folder' => $folder, 'reason' => 'questions.js manquant'];
        continue;
    }
    
    $existingStudy = dbQueryOne(
        "SELECT id, study_id FROM studies WHERE folder_name = ? OR study_id = ?",
        [$folder, $folder]
    );
    
    if ($existingStudy) {
        $results['already_exists'][] = ['folder' => $folder, 'db_id' => $existingStudy['id']];
        continue;
    }
    
    $jsContent = file_get_contents($questionsFile);
    $studyInfo = extractStudyInfo($jsContent, $folder);
    
    if (!$studyInfo) {
        $results['errors'][] = ['folder' => $folder, 'reason' => 'Parse error'];
        continue;
    }
    
    try {
        dbExecute(
            "INSERT INTO studies (study_id, folder_name, title, study_date, reward, duration, target_participants, require_access_id, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                $studyInfo['studyId'],
                $folder,
                $studyInfo['title'],
                $studyInfo['date'],
                $studyInfo['reward'],
                $studyInfo['duration'],
                $studyInfo['totalParticipants'],
                $studyInfo['requireAccessId'] ? 1 : 0
            ]
        );
        
        $newId = dbLastId();
        $results['added'][] = ['folder' => $folder, 'db_id' => $newId, 'title' => $studyInfo['title']];
        
        // Créer fichiers manquants
        if (!file_exists($studyPath . '/status.json')) {
            file_put_contents($studyPath . '/status.json', json_encode(['status' => 'active', 'createdAt' => date('c')], JSON_PRETTY_PRINT));
        }
        if (!is_dir($studyPath . '/data')) {
            mkdir($studyPath . '/data', 0755, true);
        }
        if (!file_exists($studyPath . '/data/access_ids.json')) {
            file_put_contents($studyPath . '/data/access_ids.json', '[]');
        }
        
    } catch (Exception $e) {
        $results['errors'][] = ['folder' => $folder, 'reason' => $e->getMessage()];
    }
}

$results['summary'] = [
    'total_scanned' => $results['scanned'],
    'total_added' => count($results['added']),
    'total_existing' => count($results['already_exists']),
    'total_errors' => count($results['errors'])
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function extractStudyInfo($jsContent, $folderName) {
    $info = [
        'studyId' => $folderName,
        'title' => $folderName,
        'date' => '',
        'reward' => '',
        'duration' => '',
        'totalParticipants' => 10,
        'requireAccessId' => false
    ];
    
    if (preg_match('/studyId\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['studyId'] = $m[1];
    if (preg_match('/studyTitle\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['title'] = $m[1];
    elseif (preg_match('/nom\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['title'] = $m[1];
    if (preg_match('/studyDate\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['date'] = $m[1];
    if (preg_match('/reward\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['reward'] = $m[1];
    elseif (preg_match('/compensation\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['reward'] = $m[1];
    if (preg_match('/duration\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['duration'] = $m[1];
    elseif (preg_match('/dureeEstimee\s*:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $m)) $info['duration'] = $m[1];
    if (preg_match('/totalParticipants\s*:\s*(\d+)/', $jsContent, $m)) $info['totalParticipants'] = intval($m[1]);
    if (preg_match('/requireAccessId\s*:\s*(true|false)/', $jsContent, $m)) $info['requireAccessId'] = $m[1] === 'true';
    
    return $info;
}
