<?php
/**
 * TEST EXPORT JSONL - Simulation avec données fictives
 *
 * Ce script simule l'export JSONL pour vérifier la structure de sortie
 * USAGE: php test-export-jsonl.php
 */

// Simuler les données d'un participant fictif pour EVAL_IA_EXPRESS_2026
$mockAnswers = [
    // Consentement
    'rgpd_consent' => ['consent_accepted'],

    // Démographiques
    'p0_age' => '25-34',
    'p0_education' => 'bac_plus_5',
    'p0_ia_usage' => 'quotidien',

    // Préférences avec justifications et confiance
    'pref_1' => 'A',
    'pref_1_why' => 'La réponse A est plus accessible et utilise des analogies concrètes comme la serre, ce qui rend le concept facile à comprendre.',
    'pref_1_confidence' => '4',

    'pref_2' => 'A',
    'pref_2_why' => 'Des conseils pratiques et directement applicables, sans jargon médical.',
    'pref_2_confidence' => '5',

    'pref_3' => 'A',
    'pref_3_why' => 'Plus empathique, elle reconnaît les émotions avant de proposer des solutions.',
    'pref_3_confidence' => '4',

    'pref_4' => 'A',
    'pref_4_why' => 'Le style est immersif avec des détails sensoriels, on entre directement dans l\'histoire.',
    'pref_4_confidence' => '5',

    'pref_5' => 'A',
    'pref_5_why' => 'Solution directe et code prêt à l\'emploi, exactement ce qu\'on cherche.',
    'pref_5_confidence' => '5',

    'pref_6' => 'B',
    'pref_6_why' => 'Blague plus légère et universelle, moins risquée.',
    'pref_6_confidence' => '3',

    'pref_7' => 'A',
    'pref_7_why' => 'Synthèse concise qui va droit au but sans numérotation excessive.',
    'pref_7_confidence' => '4',

    'pref_8' => 'A',
    'pref_8_why' => 'Ton motivant et accessible, donne envie de commencer petit.',
    'pref_8_confidence' => '5',

    'pref_9' => 'A',
    'pref_9_why' => 'Instructions numérotées et précises, facile à suivre étape par étape.',
    'pref_9_confidence' => '5',

    'pref_10' => 'A',
    'pref_10_why' => 'Plus nuancée et exacte historiquement, mentionne les autres inventeurs.',
    'pref_10_confidence' => '4',

    // Gold standard questions
    'gold_1' => 'A',
    'gold_2' => 'A',

    // Attention checks
    'attention_1' => 'A',
    'attention_2' => 'vérifié',

    // Ratings
    'rating_1' => '5',
    'rating_2' => '4',
    'rating_3' => '4',
    'rating_3_comment' => 'L\'email est bien structuré mais manque de personnalisation, les crochets devraient être remplacés par des exemples.',

    // Corrections enrichies
    'correct_1_problem' => ['trop_formel', 'trop_long'],
    'correct_1' => 'Ouais, je te conseille Le Comptoir dans le 6ème, c\'est vraiment bon ! Par contre réserve avant, c\'est souvent plein.',
    'correct_1_confidence' => '5',
    'correct_1_use_original' => 'non',

    'correct_2_problem' => ['trop_vague', 'manque_exemples'],
    'correct_2' => 'Pour négocier une occaz, commence par checker l\'Argus et les annonces similaires pour connaître le vrai prix. Repère les défauts (rayures, kilométrage, contrôle technique) pour avoir des arguments. Propose 10-15% en dessous du prix affiché et sois prêt à partir si ça colle pas. Demande aussi ce qui est inclus (pneus hiver, entretien récent...).',
    'correct_2_confidence' => '4',
    'correct_2_use_original' => 'non',

    'correct_3_problem' => ['manque_empathie', 'trop_prescriptif', 'suggestion_deplacee'],
    'correct_3' => 'Oh non, je suis vraiment désolé pour ton chien... Perdre un compagnon comme ça, c\'est vraiment dur. Prends le temps qu\'il te faut pour le pleurer, c\'est normal d\'être triste. Si tu veux en parler ou partager des souvenirs de lui, je suis là.',
    'correct_3_confidence' => '5',
    'correct_3_use_original' => 'non',

    // Safety evaluations
    'safety_1' => ['ok'],
    'safety_2' => ['ok'],
    'safety_3' => ['ok', 'manque_ressources'],

    // Feedback
    'feedback_difficulte' => 'facile',
    'feedback_temps' => 'bien',
    'feedback_commentaire' => 'Questionnaire intéressant, les comparaisons font réfléchir sur ce qu\'on attend vraiment d\'une IA.',
    'feedback_recontact' => 'oui'
];

// Simuler les réponses attendues pour les attention checks
$attentionAnswers = [
    'attention_1' => 'A',
    'attention_2' => 'vérifié'
];

// Simuler les réponses attendues pour les gold standards
$goldExpected = [
    'gold_1' => 'A',
    'gold_2' => 'A'
];

// Créer la structure de sortie
$output = [
    'participant_id' => 'P0001',
    'metadata' => [
        'study_id' => 'EVAL_IA_EXPRESS_2026',
        'completed_at' => date('Y-m-d H:i:s'),
        'status' => 'QUALIFIE',
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
            'trust_score' => 92,
            'session_duration_seconds' => 780,
            'paste_events' => 0,
            'tab_switches' => 1
        ]
    ],
    'demographics' => [],
    'preference_comparisons' => [],
    'quality_ratings' => [],
    'human_corrections' => [],
    'safety_evaluations' => [],
    'survey_feedback' => []
];

// Traiter les gold standards
foreach ($mockAnswers as $qId => $value) {
    if (preg_match('/^gold_(\d+)$/', $qId)) {
        $output['metadata']['gold_standard_checks']['total']++;
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

// Traiter les attention checks
foreach ($mockAnswers as $qId => $value) {
    if (strpos($qId, 'attention') !== false) {
        $output['metadata']['attention_checks']['total']++;
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

// Traiter les préférences
$processedPrefs = [];
foreach ($mockAnswers as $qId => $value) {
    if (preg_match('/^pref_(\d+)$/', $qId, $m)) {
        $prefNum = $m[1];
        if (isset($processedPrefs[$prefNum])) continue;

        $prefEntry = [
            'preference_id' => $qId,
            'choice' => $value
        ];

        // Ajouter la justification
        $whyKey = "pref_{$prefNum}_why";
        if (isset($mockAnswers[$whyKey])) {
            $justification = $mockAnswers[$whyKey];
            $prefEntry['justification'] = $justification;
            $prefEntry['justification_metrics'] = [
                'char_count' => mb_strlen($justification),
                'word_count' => str_word_count($justification)
            ];
        }

        // Ajouter la confiance
        $confKey = "pref_{$prefNum}_confidence";
        if (isset($mockAnswers[$confKey])) {
            $prefEntry['confidence'] = intval($mockAnswers[$confKey]);
        }

        $output['preference_comparisons'][] = $prefEntry;
        $processedPrefs[$prefNum] = true;
    }
}

// Traiter les corrections
$processedCorrections = [];
foreach ($mockAnswers as $qId => $value) {
    if (preg_match('/^correct_(\d+)$/', $qId, $m)) {
        $corrNum = $m[1];
        if (isset($processedCorrections[$corrNum])) continue;

        $corrEntry = [
            'correction_id' => $qId,
            'corrected_text' => $value
        ];

        if (is_string($value) && strlen($value) > 10) {
            $corrEntry['text_metrics'] = [
                'char_count' => mb_strlen($value),
                'word_count' => str_word_count($value)
            ];
        }

        // Problèmes identifiés
        $problemKey = "correct_{$corrNum}_problem";
        if (isset($mockAnswers[$problemKey])) {
            $corrEntry['identified_problems'] = $mockAnswers[$problemKey];
        }

        // Confiance
        $confKey = "correct_{$corrNum}_confidence";
        if (isset($mockAnswers[$confKey])) {
            $corrEntry['confidence'] = intval($mockAnswers[$confKey]);
        }

        // Use original
        $useOrigKey = "correct_{$corrNum}_use_original";
        if (isset($mockAnswers[$useOrigKey])) {
            $corrEntry['would_use_original'] = ($mockAnswers[$useOrigKey] === 'oui');
        }

        $output['human_corrections'][] = $corrEntry;
        $processedCorrections[$corrNum] = true;
    }
}

// Traiter les ratings
$processedRatings = [];
foreach ($mockAnswers as $qId => $value) {
    if (preg_match('/^rating_(\d+)$/', $qId, $m)) {
        $ratingNum = $m[1];
        if (isset($processedRatings[$ratingNum])) continue;

        $ratingEntry = [
            'rating_id' => $qId,
            'score' => intval($value)
        ];

        $commentKey = "rating_{$ratingNum}_comment";
        if (isset($mockAnswers[$commentKey])) {
            $ratingEntry['comment'] = $mockAnswers[$commentKey];
        }

        $output['quality_ratings'][] = $ratingEntry;
        $processedRatings[$ratingNum] = true;
    }
}

// Traiter les autres réponses
foreach ($mockAnswers as $qId => $value) {
    // Ignorer les questions déjà traitées
    if (preg_match('/^(pref_\d+|correct_\d+|rating_\d+|gold_\d+)(_why|_confidence|_problem|_use_original|_comment)?$/', $qId)) continue;
    if (strpos($qId, 'attention') !== false) continue;
    if (preg_match('/_intro$|^rgpd_consent$/', $qId)) continue;

    // Demographics
    if (preg_match('/^p0_/', $qId)) {
        $output['demographics'][$qId] = $value;
    }
    // Safety
    elseif (preg_match('/^safety_/', $qId)) {
        $output['safety_evaluations'][] = [
            'question_id' => $qId,
            'value' => $value
        ];
    }
    // Feedback
    elseif (preg_match('/^feedback_/', $qId)) {
        $output['survey_feedback'][$qId] = $value;
    }
}

// Calculer le score de qualité
$qualityScore = 100;
$checks = $output['metadata']['attention_checks'];
$goldChecks = $output['metadata']['gold_standard_checks'];

if ($checks['total'] > 0) {
    $ratio = $checks['passed'] / $checks['total'];
    if ($ratio < 1) {
        $qualityScore -= (1 - $ratio) * 30;
    }
}

if ($goldChecks['total'] > 0) {
    $goldRatio = $goldChecks['passed'] / $goldChecks['total'];
    if ($goldRatio < 1) {
        $qualityScore -= (1 - $goldRatio) * 20;
    }
    $output['metadata']['gold_standard_checks']['agreement_rate'] = round(($goldChecks['passed'] / $goldChecks['total']) * 100, 1);
}

$bm = $output['metadata']['behavior'];
if ($bm['trust_score'] !== null && $bm['trust_score'] < 70) {
    $qualityScore -= (70 - $bm['trust_score']) / 2;
}

$output['metadata']['quality_score'] = max(0, min(100, round($qualityScore)));

// Afficher le résultat
echo "=== TEST EXPORT JSONL - EVAL_IA_EXPRESS_2026 ===\n\n";
echo "Format JSONL (une ligne par participant):\n";
echo "==========================================\n\n";
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n\n";

// Statistiques
echo "=== STATISTIQUES ===\n";
echo "Préférences exportées: " . count($output['preference_comparisons']) . "\n";
echo "  - Avec justification: " . count(array_filter($output['preference_comparisons'], fn($p) => isset($p['justification']))) . "\n";
echo "  - Avec confiance: " . count(array_filter($output['preference_comparisons'], fn($p) => isset($p['confidence']))) . "\n";
echo "Corrections exportées: " . count($output['human_corrections']) . "\n";
echo "  - Avec problèmes identifiés: " . count(array_filter($output['human_corrections'], fn($c) => isset($c['identified_problems']))) . "\n";
echo "  - Avec confiance: " . count(array_filter($output['human_corrections'], fn($c) => isset($c['confidence']))) . "\n";
echo "Ratings exportés: " . count($output['quality_ratings']) . "\n";
echo "Safety evaluations: " . count($output['safety_evaluations']) . "\n";
echo "Gold standard checks: {$goldChecks['passed']}/{$goldChecks['total']} (IAA: {$output['metadata']['gold_standard_checks']['agreement_rate']}%)\n";
echo "Attention checks: {$checks['passed']}/{$checks['total']}\n";
echo "Score qualité: {$output['metadata']['quality_score']}/100\n";
