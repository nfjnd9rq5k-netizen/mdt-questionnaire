<?php
/**
 * ============================================================
 * EXPORT JSONL UNIVERSEL - TOUTES LES ÉTUDES IA v2.0
 * ============================================================
 *
 * Export automatique qui fonctionne avec tous les questionnaires.
 * Détecte automatiquement les catégories basées sur les IDs.
 *
 * NOUVEAUTÉS v2.0 :
 * - Support des justifications + confiance (pref_X_why, pref_X_confidence)
 * - Support des gold standard questions (gold_X)
 * - Support des corrections enrichies (correct_X_problem, correct_X_confidence, correct_X_use_original)
 * - Regroupement intelligent des données liées
 *
 * USAGE: /api/export-jsonl-universal.php?study=EVAL_IA_EXPRESS_2026
 */

session_start();
require_once 'db.php';

// Vérification authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé - Authentification requise']);
    exit;
}

header('Content-Type: application/jsonl; charset=utf-8');

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
            'gold_standard_checks' => [
                'passed' => 0,
                'total' => 0,
                'details' => []
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
        'human_corrections' => [],
        'text_generations' => [],
        'safety_evaluations' => [],
        'cultural_knowledge' => [],
        'survey_feedback' => []
    ];

    // Stocker temporairement toutes les réponses pour regroupement
    $answerMap = [];
    
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

    // PHASE 1 : Collecter toutes les réponses dans une map
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

        $answerMap[$qId] = [
            'value' => $value,
            'metrics' => $metrics
        ];
    }

    // PHASE 2 : Traiter les questions groupées

    // 2.1 Traiter les GOLD STANDARD (gold_X)
    $goldExpected = [
        'gold_1' => 'A',
        'gold_2' => 'A'
    ];
    foreach ($answerMap as $qId => $data) {
        if (preg_match('/^gold_(\d+)$/', $qId, $m)) {
            $output['metadata']['gold_standard_checks']['total']++;
            $value = $data['value'];
            $expected = $goldExpected[$qId] ?? null;
            $passed = ($expected !== null && $value === $expected);
            if ($passed) {
                $output['metadata']['gold_standard_checks']['passed']++;
            }
            $output['metadata']['gold_standard_checks']['details'][] = [
                'question_id' => $qId,
                'user_answer' => $value,
                'expected' => $expected,
                'correct' => $passed
            ];
        }
    }

    // 2.2 Traiter les ATTENTION CHECKS
    foreach ($answerMap as $qId => $data) {
        if (strpos($qId, 'attention') !== false) {
            $output['metadata']['attention_checks']['total']++;
            $value = $data['value'];
            if (isset($attentionAnswers[$qId])) {
                $expected = $attentionAnswers[$qId];
                $actual = is_string($value) ? mb_strtolower(trim($value)) : $value;
                $expectedLower = is_string($expected) ? mb_strtolower(trim($expected)) : $expected;

                if ($actual === $expectedLower ||
                    (is_string($actual) && strpos($actual, $expectedLower) !== false)) {
                    $output['metadata']['attention_checks']['passed']++;
                }
            }
        }
    }

    // 2.3 Traiter les PRÉFÉRENCES avec justifications et confiance (pref_X, pref_X_why, pref_X_confidence)
    $processedPrefs = [];
    foreach ($answerMap as $qId => $data) {
        // Trouver les questions de préférence principales (pas _why, _confidence)
        if (preg_match('/^pref_(\d+)$/', $qId, $m)) {
            $prefNum = $m[1];
            if (isset($processedPrefs[$prefNum])) continue;

            $prefEntry = [
                'preference_id' => $qId,
                'choice' => $data['value']
            ];

            // Ajouter la justification si présente
            $whyKey = "pref_{$prefNum}_why";
            if (isset($answerMap[$whyKey]) && $answerMap[$whyKey]['value']) {
                $justification = $answerMap[$whyKey]['value'];
                $prefEntry['justification'] = $justification;
                $prefEntry['justification_metrics'] = [
                    'char_count' => mb_strlen($justification),
                    'word_count' => str_word_count($justification)
                ];
            }

            // Ajouter la confiance si présente
            $confKey = "pref_{$prefNum}_confidence";
            if (isset($answerMap[$confKey]) && $answerMap[$confKey]['value']) {
                $prefEntry['confidence'] = intval($answerMap[$confKey]['value']);
            }

            $output['preference_comparisons'][] = $prefEntry;
            $processedPrefs[$prefNum] = true;
        }
    }

    // 2.4 Traiter les CORRECTIONS enrichies (correct_X_problem, correct_X, correct_X_confidence, correct_X_use_original)
    $processedCorrections = [];
    foreach ($answerMap as $qId => $data) {
        // Trouver les questions de correction principales
        if (preg_match('/^correct_(\d+)$/', $qId, $m)) {
            $corrNum = $m[1];
            if (isset($processedCorrections[$corrNum])) continue;

            $corrEntry = [
                'correction_id' => $qId,
                'corrected_text' => $data['value']
            ];

            // Ajouter les métriques du texte
            if (is_string($data['value']) && strlen($data['value']) > 10) {
                $corrEntry['text_metrics'] = [
                    'char_count' => mb_strlen($data['value']),
                    'word_count' => str_word_count($data['value'])
                ];
            }

            // Ajouter l'identification du problème si présente
            $problemKey = "correct_{$corrNum}_problem";
            if (isset($answerMap[$problemKey]) && $answerMap[$problemKey]['value']) {
                $corrEntry['identified_problems'] = $answerMap[$problemKey]['value'];
            }

            // Ajouter la confiance si présente
            $confKey = "correct_{$corrNum}_confidence";
            if (isset($answerMap[$confKey]) && $answerMap[$confKey]['value']) {
                $corrEntry['confidence'] = intval($answerMap[$confKey]['value']);
            }

            // Ajouter use_original si présent
            $useOrigKey = "correct_{$corrNum}_use_original";
            if (isset($answerMap[$useOrigKey]) && $answerMap[$useOrigKey]['value']) {
                $corrEntry['would_use_original'] = ($answerMap[$useOrigKey]['value'] === 'oui');
            }

            $output['human_corrections'][] = $corrEntry;
            $processedCorrections[$corrNum] = true;
        }
    }

    // 2.5 Traiter les RATINGS avec commentaires (rating_X, rating_X_comment)
    $processedRatings = [];
    foreach ($answerMap as $qId => $data) {
        if (preg_match('/^rating_(\d+)$/', $qId, $m)) {
            $ratingNum = $m[1];
            if (isset($processedRatings[$ratingNum])) continue;

            $ratingEntry = [
                'rating_id' => $qId,
                'score' => intval($data['value'])
            ];

            // Ajouter le commentaire si présent
            $commentKey = "rating_{$ratingNum}_comment";
            if (isset($answerMap[$commentKey]) && $answerMap[$commentKey]['value']) {
                $ratingEntry['comment'] = $answerMap[$commentKey]['value'];
            }

            $output['quality_ratings'][] = $ratingEntry;
            $processedRatings[$ratingNum] = true;
        }
    }

    // 2.6 Traiter les autres réponses
    foreach ($answerMap as $qId => $data) {
        $value = $data['value'];
        if ($value === null || $value === '') continue;

        // Ignorer les questions déjà traitées
        if (preg_match('/^(pref_\d+|correct_\d+|rating_\d+|gold_\d+)(_why|_confidence|_problem|_use_original|_comment)?$/', $qId)) continue;
        if (strpos($qId, 'attention') !== false) continue;
        if (preg_match('/_intro$|^rgpd_consent$/', $qId)) continue;

        $entry = [
            'question_id' => $qId,
            'value' => $value
        ];
        if ($data['metrics']) {
            $entry['metrics'] = $data['metrics'];
        }

        // DEMOGRAPHICS
        if (preg_match('/^p0_|age|region|education|profession|genre|milieu|langue|niveau|demo_|ia_usage/', $qId)) {
            $output['demographics'][$qId] = $value;
        }
        // SAFETY / ÉVALUATIONS
        elseif (preg_match('/^safety_|eval_|probleme|appropriate/', $qId)) {
            $output['safety_evaluations'][] = $entry;
        }
        // CULTUREL
        elseif (preg_match('/culture|expr|argot|greve|repas|raler|regional|chocolatine|serpillere|accent/', $qId)) {
            if (is_string($value) && strlen($value) > 30) {
                $entry['metrics'] = [
                    'char_count' => mb_strlen($value),
                    'word_count' => str_word_count($value)
                ];
            }
            $output['cultural_knowledge'][] = $entry;
        }
        // FEEDBACK
        elseif (preg_match('/feedback|difficulte|commentaire|recontact|notification|temps/', $qId)) {
            $output['survey_feedback'][$qId] = $value;
        }
        // TEXTES GÉNÉRÉS (réponses longues non catégorisées)
        elseif (is_string($value) && strlen($value) > 100) {
            $entry['metrics'] = [
                'char_count' => mb_strlen($value),
                'word_count' => str_word_count($value)
            ];
            $output['text_generations'][] = $entry;
        }
        // Autres selon préfixe
        elseif (preg_match('/^p[1-3]_/', $qId)) {
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
    
    // Calculer le score de qualité
    $qualityScore = 100;
    $checks = $output['metadata']['attention_checks'];
    $goldChecks = $output['metadata']['gold_standard_checks'];

    // Pénalité attention checks
    if ($checks['total'] > 0) {
        $ratio = $checks['passed'] / $checks['total'];
        if ($ratio < 1) {
            $qualityScore -= (1 - $ratio) * 30;
        }
    }

    // Pénalité gold standard checks (utilisé pour calculer l'IAA mais aussi la qualité individuelle)
    if ($goldChecks['total'] > 0) {
        $goldRatio = $goldChecks['passed'] / $goldChecks['total'];
        if ($goldRatio < 1) {
            $qualityScore -= (1 - $goldRatio) * 20;
        }
    }

    // Pénalités comportementales
    $bm = $output['metadata']['behavior'];
    if ($bm['trust_score'] !== null && $bm['trust_score'] < 70) {
        $qualityScore -= (70 - $bm['trust_score']) / 2;
    }
    if ($bm['paste_events'] > 5) $qualityScore -= 10;
    if ($bm['session_duration_seconds'] !== null && $bm['session_duration_seconds'] < 300) $qualityScore -= 15;

    $output['metadata']['quality_score'] = max(0, min(100, round($qualityScore)));

    // Calculer l'IAA potentiel (Inter-Annotator Agreement) basé sur les gold standards
    if ($goldChecks['total'] > 0) {
        $output['metadata']['gold_standard_checks']['agreement_rate'] = round(($goldChecks['passed'] / $goldChecks['total']) * 100, 1);
    }

    // Nettoyer les tableaux vides
    if (empty($output['human_corrections'])) unset($output['human_corrections']);
    if (empty($output['quality_ratings'])) unset($output['quality_ratings']);
    if (empty($output['text_generations'])) unset($output['text_generations']);
    if (empty($output['cultural_knowledge'])) unset($output['cultural_knowledge']);
    if (empty($output['survey_feedback'])) unset($output['survey_feedback']);
    if (empty($output['metadata']['gold_standard_checks']['details'])) {
        unset($output['metadata']['gold_standard_checks']);
    }

    // Sortie JSON
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}