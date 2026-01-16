<?php
/**
 * ============================================================
 * EXPORT JSONL STRUCTURÉ - Version 2.0
 * La Maison du Test - Data IA Collection
 * ============================================================
 * 
 * Format de sortie structuré par catégorie de données :
 * - demographics
 * - preferences (comparaisons A/B)
 * - generations (textes générés)
 * - evaluations (safety)
 * - cultural (données culturelles)
 * - quality_metrics (métriques anti-bot)
 * 
 * UTILISATION :
 * GET /api/export-jsonl.php?study=DATA_IA_JAN2026
 * GET /api/export-jsonl.php?study=DATA_IA_JAN2026&format=csv
 * GET /api/export-jsonl.php?study=DATA_IA_JAN2026&include_failed_checks=0
 * ============================================================
 */

session_start();
require_once 'db.php';

// Vérification authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$studyId = $_GET['study'] ?? '';
$format = $_GET['format'] ?? 'jsonl';
$includeFailedChecks = ($_GET['include_failed_checks'] ?? '1') === '1';

if (empty($studyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre study requis', 'usage' => '/api/export-jsonl.php?study=STUDY_ID']);
    exit;
}

// Récupérer l'étude
$study = dbQueryOne("SELECT * FROM studies WHERE study_id = ? OR folder_name = ?", [$studyId, $studyId]);

if (!$study) {
    http_response_code(404);
    echo json_encode(['error' => 'Étude non trouvée']);
    exit;
}

// Récupérer toutes les réponses complétées
$responses = dbQuery(
    "SELECT r.* FROM responses r 
     WHERE r.study_id = ? AND r.status IN ('QUALIFIE', 'REFUSE')
     ORDER BY r.completed_at ASC",
    [$study['id']]
);

if (empty($responses)) {
    http_response_code(404);
    echo json_encode(['error' => 'Aucune réponse trouvée', 'study' => $studyId]);
    exit;
}

// Charger la config des questions pour connaître les catégories
$questionsFile = __DIR__ . '/../studies/' . $study['folder_name'] . '/questions.js';
$questionMeta = [];
if (file_exists($questionsFile)) {
    $jsContent = file_get_contents($questionsFile);
    
    // Méthode 1 : Parser les métadonnées explicites (DATA_IA style)
    preg_match_all('/{\s*id:\s*[\'"]([^\'"]+)[\'"][^}]*category:\s*[\'"]([^\'"]+)[\'"][^}]*data_type:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $questionMeta[$match[1]] = [
            'category' => $match[2],
            'data_type' => $match[3]
        ];
    }
    
    // Méthode 2 : Parser toutes les questions et deviner la catégorie depuis l'ID
    preg_match_all('/{\s*id:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $allIds);
    foreach ($allIds[1] as $qId) {
        if (!isset($questionMeta[$qId])) {
            // Deviner la catégorie basée sur l'ID
            $category = 'other';
            $dataType = 'unknown';
            
            if (preg_match('/^p0_/', $qId) || preg_match('/age|region|education|profession|genre|milieu|langue/', $qId)) {
                $category = 'demographics';
                $dataType = 'demographic';
            } elseif (preg_match('/attention/', $qId)) {
                $category = 'quality_control';
                $dataType = 'attention_check';
            } elseif (preg_match('/comp\d+|pref_|choix/', $qId)) {
                $category = 'preference';
                $dataType = 'comparison';
            } elseif (preg_match('/expr_|argot_|culture_|regional|greve|repas|raler/', $qId)) {
                $category = 'cultural';
                $dataType = 'cultural_text';
            } elseif (preg_match('/eval_|safety_/', $qId)) {
                $category = 'evaluation';
                $dataType = 'evaluation';
            } elseif (preg_match('/feedback|difficulte|commentaire|recontact/', $qId)) {
                $category = 'quality_control';
                $dataType = 'feedback';
            } elseif (preg_match('/_intro$/', $qId)) {
                $category = 'info';
                $dataType = 'info';
            }
            
            $questionMeta[$qId] = [
                'category' => $category,
                'data_type' => $dataType
            ];
        }
    }
    
    // Parser aussi les attention checks depuis metadata
    preg_match_all('/id:\s*[\'"]([^\'"]+)[\'"][^}]*metadata:\s*{\s*is_attention_check:\s*true[^}]*correct_answer:\s*[\'"]([^\'"]+)[\'"]/', $jsContent, $attChecks, PREG_SET_ORDER);
    foreach ($attChecks as $check) {
        if (!isset($questionMeta[$check[1]])) {
            $questionMeta[$check[1]] = ['category' => 'quality_control', 'data_type' => 'attention_check'];
        }
        $questionMeta[$check[1]]['is_attention_check'] = true;
        $questionMeta[$check[1]]['correct_answer'] = $check[2];
    }
}

// Préparer les données structurées
$exportData = [];
$participantCounter = 1;

foreach ($responses as $response) {
    // Récupérer les réponses aux questions
    $answers = dbQuery(
        "SELECT question_id, answer_value, answer_values, answer_text, answer_data 
         FROM answers WHERE response_id = ?",
        [$response['id']]
    );
    
    // Créer un ID anonyme
    $anonymousId = 'P' . str_pad($participantCounter++, 4, '0', STR_PAD_LEFT);
    
    // Organiser les réponses par catégorie
    $structured = [
        'id' => $anonymousId,
        'study_id' => $study['study_id'],
        'completed_at' => $response['completed_at'],
        'status' => $response['status'],
        'demographics' => [],
        'preferences' => [],
        'generations' => [],
        'evaluations' => [],
        'cultural' => [],
        'quality_control' => [
            'attention_checks_passed' => 0,
            'attention_checks_total' => 0
        ]
    ];
    
    // Parser les réponses
    foreach ($answers as $answer) {
        $qId = $answer['question_id'];
        $meta = $questionMeta[$qId] ?? ['category' => 'other', 'data_type' => 'unknown'];
        
        // Extraire la valeur
        $value = null;
        $extraData = [];
        
        if ($answer['answer_value']) {
            $value = $answer['answer_value'];
        } elseif ($answer['answer_values']) {
            $value = json_decode($answer['answer_values'], true);
        } elseif ($answer['answer_text']) {
            $value = $answer['answer_text'];
        } elseif ($answer['answer_data']) {
            $data = json_decode($answer['answer_data'], true);
            if (isset($data['value'])) $value = $data['value'];
            if (isset($data['values'])) $value = $data['values'];
            if (isset($data['metrics'])) $extraData['metrics'] = $data['metrics'];
            if (isset($data['extraText'])) $extraData['extra_text'] = $data['extraText'];
            if (isset($data['extraTexts'])) $extraData['extra_texts'] = $data['extraTexts'];
        }
        
        // Vérifier les attention checks
        if (isset($meta['is_attention_check']) && $meta['is_attention_check']) {
            $structured['quality_control']['attention_checks_total']++;
            if ($value === $meta['correct_answer']) {
                $structured['quality_control']['attention_checks_passed']++;
            }
            continue; // Ne pas inclure dans les données exportées
        }
        
        // Ignorer les écrans info
        if ($meta['category'] === 'info') continue;
        
        // Construire l'entrée
        $entry = [
            'question_id' => $qId,
            'data_type' => $meta['data_type'] ?? 'unknown',
            'value' => $value
        ];
        if (!empty($extraData)) {
            $entry = array_merge($entry, $extraData);
        }
        
        // Ranger dans la bonne catégorie
        switch ($meta['category']) {
            case 'demographics':
                $structured['demographics'][$qId] = $value;
                break;
            case 'preference':
                $structured['preferences'][] = $entry;
                break;
            case 'generation':
                $structured['generations'][] = $entry;
                break;
            case 'evaluation':
                $structured['evaluations'][] = $entry;
                break;
            case 'cultural':
                $structured['cultural'][] = $entry;
                break;
            case 'quality_control':
                if ($meta['data_type'] !== 'attention_check') {
                    $structured['quality_control'][$qId] = $value;
                }
                break;
        }
    }
    
    // Récupérer les métriques comportementales
    $behaviorData = dbQueryOne(
        "SELECT answer_data FROM answers WHERE response_id = ? AND answer_data LIKE '%behaviorMetrics%' LIMIT 1",
        [$response['id']]
    );
    
    if ($behaviorData && $behaviorData['answer_data']) {
        $data = json_decode($behaviorData['answer_data'], true);
        if (isset($data['behaviorMetrics'])) {
            $bm = $data['behaviorMetrics'];
            $structured['quality_control']['behavior_metrics'] = [
                'session_duration_sec' => $bm['sessionDuration'] ?? null,
                'avg_time_per_question_sec' => $bm['avgTimePerQuestion'] ?? null,
                'paste_events' => $bm['pasteEvents'] ?? 0,
                'tab_switches' => $bm['tabSwitches'] ?? 0,
                'backspace_ratio' => $bm['backspaceRatio'] ?? null,
                'trust_score' => $bm['trustScore'] ?? null
            ];
        }
    }
    
    // Calculer un score de qualité global
    $qualityScore = 100;
    $checks = $structured['quality_control'];
    
    // Pénalités attention checks
    if ($checks['attention_checks_total'] > 0) {
        $checkRatio = $checks['attention_checks_passed'] / $checks['attention_checks_total'];
        if ($checkRatio < 1) $qualityScore -= 30 * (1 - $checkRatio);
    }
    
    // Pénalités comportementales
    if (isset($checks['behavior_metrics'])) {
        $bm = $checks['behavior_metrics'];
        if ($bm['trust_score'] !== null && $bm['trust_score'] < 70) {
            $qualityScore -= (70 - $bm['trust_score']) / 2;
        }
        if ($bm['paste_events'] > 5) $qualityScore -= 10;
        if ($bm['session_duration_sec'] !== null && $bm['session_duration_sec'] < 300) $qualityScore -= 15;
    }
    
    $structured['quality_control']['overall_quality_score'] = max(0, min(100, round($qualityScore)));
    
    // Filtrer si attention checks échoués
    if (!$includeFailedChecks) {
        $checkRatio = $checks['attention_checks_total'] > 0 
            ? $checks['attention_checks_passed'] / $checks['attention_checks_total']
            : 1;
        if ($checkRatio < 0.5) continue; // Exclure si moins de 50% de réussite
    }
    
    $exportData[] = $structured;
}

// ============================================================
// EXPORT
// ============================================================

if ($format === 'csv') {
    // Export CSV simplifié
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $studyId . '_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers simplifiés
    $headers = ['participant_id', 'completed_at', 'quality_score', 'attention_checks', 'trust_score'];
    fputcsv($output, $headers, ';');
    
    foreach ($exportData as $row) {
        $qc = $row['quality_control'];
        fputcsv($output, [
            $row['id'],
            $row['completed_at'],
            $qc['overall_quality_score'],
            $qc['attention_checks_passed'] . '/' . $qc['attention_checks_total'],
            $qc['behavior_metrics']['trust_score'] ?? 'N/A'
        ], ';');
    }
    
    fclose($output);
    exit;
}

// Export JSONL (par défaut)
header('Content-Type: application/x-ndjson; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $studyId . '_structured_' . date('Y-m-d') . '.jsonl"');

foreach ($exportData as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
