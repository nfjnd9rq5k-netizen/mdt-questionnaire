<?php
/**
 * ============================================================
 * EXPORT JSONL UNIVERSEL - TOUTES LES ÉTUDES IA
 * ============================================================
 * 
 * Export automatique qui fonctionne avec tous les questionnaires.
 * Détecte automatiquement les catégories basées sur les IDs.
 * 
 * USAGE: /api/export-jsonl-universal.php?study=CULTURE_FR_2026
 */

header('Content-Type: application/jsonl; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$studyId = $_GET['study'] ?? '';

if (empty($studyId)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Paramètre study requis']);
    exit;
}

// Récupérer l'étude
$study = dbQueryOne("SELECT id, study_id, folder_name FROM studies WHERE study_id = ? OR folder_name = ?", [$studyId, $studyId]);
if (!$study) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Étude non trouvée']);
    exit;
}

// Mapping des réponses correctes pour les attention checks par étude
$attentionAnswersByStudy = [
    'DATA_IA_JAN2026' => [
        'p1_attention_check_1' => 'blue',
        'p4_attention_check_2' => 'disagree'
    ],
    'CULTURE_FR_2026' => [
        'p1_attention_check' => 'rdv',
        'p4_attention_check' => 'trois'
    ],
    'PREF_STYLE_FR_2026' => [
        'p1_attention_check' => 'orange',
        'p3_attention_check' => 'stress'
    ],
    'EVAL_IA_EXPRESS_2026' => [
        'attention_1' => 'A',
        'attention_2' => 'vérifié'
    ]
];

$attentionAnswers = $attentionAnswersByStudy[$study['folder_name']] ?? [];

// Récupérer les participants qualifiés
$query = "SELECT r.id, r.unique_id, r.status, r.started_at, r.completed_at, r.behavior_metrics
          FROM responses r 
          WHERE r.study_id = ? AND r.status IN ('QUALIFIE', 'REFUSE')
          ORDER BY r.completed_at DESC";

try {
    $responses = dbQuery($query, [$study['id']]);
} catch (Exception $e) {
    $query = "SELECT r.id, r.unique_id, r.status, r.started_at, r.completed_at, NULL as behavior_metrics
              FROM responses r 
              WHERE r.study_id = ? AND r.status IN ('QUALIFIE', 'REFUSE')
              ORDER BY r.completed_at DESC";
    $responses = dbQuery($query, [$study['id']]);
}

if (empty($responses)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Aucune réponse trouvée']);
    exit;
}

// Nom du fichier
$filename = $study['folder_name'] . '_training_data_' . date('Y-m-d') . '.jsonl';
header('Content-Disposition: attachment; filename="' . $filename . '"');

$participantNum = 0;

foreach ($responses as $response) {
    $participantNum++;
    
    // Récupérer toutes les réponses
    $answers = dbQuery(
        "SELECT question_id, answer_value, answer_values, answer_text, answer_data 
         FROM answers WHERE response_id = ?",
        [$response['id']]
    );
    
    // Créer la structure de sortie
    $output = [
        'participant_id' => 'P' . str_pad($participantNum, 4, '0', STR_PAD_LEFT),
        'metadata' => [
            'study_id' => $study['folder_name'],
            'completed_at' => $response['completed_at'],
            'status' => $response['status'],
            'quality_score' => 100,
            'attention_checks' => [
                'passed' => 0,
                'total' => 0
            ],
            'behavior' => [
                'trust_score' => null,
                'session_duration_seconds' => null,
                'paste_events' => 0,
                'tab_switches' => 0
            ]
        ],
        'demographics' => [],
        'preference_comparisons' => [],
        'quality_ratings' => [],
        'text_generations' => [],
        'safety_evaluations' => [],
        'cultural_knowledge' => [],
        'survey_feedback' => []
    ];
    
    // Parser les métriques comportementales
    if ($response['behavior_metrics']) {
        $bm = json_decode($response['behavior_metrics'], true);
        if ($bm) {
            $output['metadata']['behavior'] = [
                'trust_score' => $bm['trustScore'] ?? null,
                'session_duration_seconds' => $bm['sessionDuration'] ?? null,
                'paste_events' => $bm['pasteEvents'] ?? 0,
                'tab_switches' => $bm['tabSwitches'] ?? 0
            ];
        }
    }
    
    // Traiter chaque réponse
    foreach ($answers as $a) {
        $qId = $a['question_id'];
        
        // Extraire la valeur
        $value = null;
        $metrics = null;
        
        if ($a['answer_value']) {
            $value = $a['answer_value'];
        }
        if ($a['answer_values']) {
            $decoded = json_decode($a['answer_values'], true);
            if ($decoded) $value = $decoded;
        }
        if ($a['answer_text']) {
            $value = $a['answer_text'];
        }
        if ($a['answer_data']) {
            $extra = json_decode($a['answer_data'], true);
            if ($extra) {
                if (isset($extra['value'])) $value = $extra['value'];
                if (isset($extra['values'])) $value = $extra['values'];
                if (isset($extra['metrics'])) $metrics = $extra['metrics'];
            }
        }
        
        // Ignorer les valeurs vides
        if ($value === null || $value === '') continue;
        
        // Vérifier les attention checks
        if (strpos($qId, 'attention') !== false) {
            $output['metadata']['attention_checks']['total']++;
            if (isset($attentionAnswers[$qId])) {
                $expected = $attentionAnswers[$qId];
                $actual = is_string($value) ? mb_strtolower(trim($value)) : $value;
                $expectedLower = is_string($expected) ? mb_strtolower(trim($expected)) : $expected;
                
                // Comparaison flexible pour les textes
                if ($actual === $expectedLower || 
                    (is_string($actual) && strpos($actual, $expectedLower) !== false)) {
                    $output['metadata']['attention_checks']['passed']++;
                }
            }
            continue; // Ne pas inclure dans les données
        }
        
        // Ignorer les intros et le consentement
        if (preg_match('/_intro$|^rgpd_consent$/', $qId)) continue;
        
        // Catégoriser automatiquement basé sur l'ID
        $entry = [
            'question_id' => $qId,
            'value' => $value
        ];
        if ($metrics) {
            $entry['metrics'] = $metrics;
        }
        
        // DEMOGRAPHICS (p0_ ou mots-clés démographiques)
        if (preg_match('/^p0_|age|region|education|profession|genre|milieu|langue|niveau|demo_/', $qId)) {
            $output['demographics'][$qId] = $value;
        }
        // RATINGS / NOTATIONS (1-5)
        elseif (preg_match('/^rating_/', $qId)) {
            $output['quality_ratings'][] = $entry;
        }
        // CORRECTIONS HUMAINES
        elseif (preg_match('/^correct_/', $qId)) {
            if (is_string($value) && strlen($value) > 20) {
                $entry['metrics'] = [
                    'char_count' => mb_strlen($value),
                    'word_count' => str_word_count($value)
                ];
            }
            $output['text_generations'][] = $entry;
        }
        // PREFERENCES / COMPARAISONS
        elseif (preg_match('/comp|pref_|choix|preference/', $qId)) {
            $output['preference_comparisons'][] = $entry;
        }
        // SAFETY / ÉVALUATIONS
        elseif (preg_match('/safety|eval_|probleme|appropriate/', $qId)) {
            $output['safety_evaluations'][] = $entry;
        }
        // CULTUREL
        elseif (preg_match('/culture|expr|argot|greve|repas|raler|regional|chocolatine|serpillere|accent/', $qId)) {
            // Calculer les métriques pour les textes longs
            if (is_string($value) && strlen($value) > 30) {
                $entry['metrics'] = [
                    'char_count' => mb_strlen($value),
                    'word_count' => str_word_count($value)
                ];
            }
            $output['cultural_knowledge'][] = $entry;
        }
        // FEEDBACK
        elseif (preg_match('/feedback|difficulte|commentaire|recontact|notification/', $qId)) {
            $output['survey_feedback'][$qId] = $value;
        }
        // TEXTES GÉNÉRÉS (réponses longues)
        elseif (is_string($value) && strlen($value) > 100) {
            $entry['metrics'] = [
                'char_count' => mb_strlen($value),
                'word_count' => str_word_count($value)
            ];
            $output['text_generations'][] = $entry;
        }
        // Autres réponses
        else {
            // Mettre dans la catégorie la plus probable selon le préfixe
            if (preg_match('/^p[1-3]_/', $qId)) {
                $output['preference_comparisons'][] = $entry;
            } elseif (preg_match('/^p[4-5]_/', $qId)) {
                if (is_string($value) && strlen($value) > 50) {
                    $output['cultural_knowledge'][] = $entry;
                } else {
                    $output['safety_evaluations'][] = $entry;
                }
            } elseif (preg_match('/^p[6-7]_/', $qId)) {
                $output['survey_feedback'][$qId] = $value;
            }
        }
    }
    
    // Calculer le score de qualité
    $qualityScore = 100;
    $checks = $output['metadata']['attention_checks'];
    
    if ($checks['total'] > 0) {
        $ratio = $checks['passed'] / $checks['total'];
        if ($ratio < 1) {
            $qualityScore -= (1 - $ratio) * 30;
        }
    }
    
    $bm = $output['metadata']['behavior'];
    if ($bm['trust_score'] !== null && $bm['trust_score'] < 70) {
        $qualityScore -= (70 - $bm['trust_score']) / 2;
    }
    if ($bm['paste_events'] > 5) $qualityScore -= 10;
    if ($bm['session_duration_seconds'] !== null && $bm['session_duration_seconds'] < 300) $qualityScore -= 15;
    
    $output['metadata']['quality_score'] = max(0, min(100, round($qualityScore)));
    
    // Sortie JSON
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}