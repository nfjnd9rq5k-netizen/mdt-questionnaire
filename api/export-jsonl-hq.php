<?php
/**
 * ============================================================
 * EXPORT JSONL HAUTE QUALITÉ - DONNÉES IA
 * ============================================================
 * 
 * Export structuré et complet pour entraînement de modèles IA
 * Inclut le contexte des questions, métriques de qualité, 
 * et toutes les réponses du questionnaire.
 * 
 * USAGE: /api/export-jsonl-hq.php?study=DATA_IA_JAN2026
 * OPTIONS:
 *   - min_quality=80 : filtrer par score qualité minimum
 *   - include_context=1 : inclure le contexte des questions (défaut: 1)
 */

header('Content-Type: application/jsonl; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$studyId = $_GET['study'] ?? 'DATA_IA_JAN2026';
$minQuality = intval($_GET['min_quality'] ?? 0);
$includeContext = ($_GET['include_context'] ?? '1') === '1';

// ============================================================
// CONTEXTE DES QUESTIONS - Scénarios et réponses A/B
// ============================================================

$questionContexts = [
    // Comparaisons de préférences
    'p1_pref_salaire' => [
        'id' => 'salary_negotiation',
        'domain' => 'professional',
        'scenario' => "Vous devez négocier une augmentation de salaire avec votre employeur. Vous demandez conseil à une IA.",
        'response_a' => [
            'style' => 'formal',
            'text' => "Je vous recommande d'adopter une approche structurée pour cette négociation. Premièrement, préparez un dossier documentant vos réalisations et leur impact mesurable. Deuxièmement, recherchez les salaires du marché pour votre poste. Troisièmement, choisissez un moment opportun, idéalement après un succès professionnel. Lors de l'entretien, présentez vos arguments factuellement et restez ouvert à la discussion."
        ],
        'response_b' => [
            'style' => 'casual',
            'text' => "Écoute, pour négocier ton augmentation, voilà ce que je te conseille : d'abord, note tout ce que t'as accompli ces derniers mois - les projets, les wins, tout ça. Ensuite, regarde un peu ce que gagnent les autres dans ton domaine. Et surtout, vas-y confiant ! T'as le droit de demander ce que tu mérites. Si ton boss dit non direct, demande ce qu'il faudrait pour y arriver."
        ]
    ],
    'p1_pref_cauchemar' => [
        'id' => 'child_nightmare',
        'domain' => 'parenting',
        'scenario' => "Votre enfant de 6 ans fait des cauchemars récurrents et a peur de s'endormir. Vous cherchez des conseils.",
        'response_a' => [
            'style' => 'casual',
            'text' => "Les cauchemars à cet âge, c'est super courant, t'inquiète pas trop ! Ce qui marche bien : créer un petit rituel du soir rassurant - une histoire, un câlin, vérifier ensemble sous le lit si ça le rassure. Tu peux aussi lui donner un doudou \"gardien\" qui le protège. Et s'il se réveille, reste calme, fais-lui un bisou et rappelle-lui que t'es là."
        ],
        'response_b' => [
            'style' => 'formal',
            'text' => "Les cauchemars récurrents chez l'enfant de 6 ans sont un phénomène développemental normal. Je vous recommande plusieurs approches : établissez une routine de coucher apaisante et prévisible, limitez les écrans au moins une heure avant le sommeil, et créez un environnement sécurisant. Un objet transitionnel peut aider. Si les cauchemars persistent ou s'accompagnent d'autres symptômes, consultez un pédiatre."
        ]
    ],
    'p1_pref_cdi' => [
        'id' => 'job_contract_choice',
        'domain' => 'professional',
        'scenario' => "Vous hésitez entre un CDI dans une grande entreprise stable et un CDD dans une startup innovante. Vous demandez conseil.",
        'response_a' => [
            'style' => 'formal',
            'text' => "Cette décision mérite une analyse approfondie de plusieurs facteurs. Le CDI offre une sécurité financière et facilite les projets personnels (crédit immobilier, etc.). La startup en CDD peut offrir plus de responsabilités et d'apprentissage, mais avec un risque accru. Évaluez vos priorités actuelles : stabilité vs croissance, et votre tolérance au risque."
        ],
        'response_b' => [
            'style' => 'casual',
            'text' => "Ah le dilemme classique ! Bon, ça dépend vraiment de où t'en es dans ta vie. Si t'as besoin de stabilité genre crédit ou famille, le CDI c'est plus safe. Mais si t'es jeune et que tu veux apprendre à fond, la startup ça peut être une super expérience même en CDD. Et puis un bon CDD peut devenir CDI !"
        ]
    ],
    'p1_pref_yaourt' => [
        'id' => 'expired_yogurt',
        'domain' => 'daily_life',
        'scenario' => "Vous trouvez un yaourt périmé depuis 3 jours dans votre frigo. Vous demandez si vous pouvez le manger.",
        'response_a' => [
            'style' => 'casual',
            'text' => "3 jours pour un yaourt ? Généralement c'est encore bon ! Ouvre-le, regarde s'il a pas une drôle de tête, sens-le. Si ça sent le yaourt normal et qu'il y a pas de moisissure, tu peux y aller tranquille. Les dates sur les yaourts sont souvent ultra prudentes. Par contre, si ça sent bizarre ou que la texture est weird, jette-le."
        ],
        'response_b' => [
            'style' => 'formal',
            'text' => "La date de péremption des yaourts est une Date Limite de Consommation (DLC) qu'il convient généralement de respecter. Cependant, un yaourt nature peut souvent être consommé quelques jours après, à condition de vérifier : absence de moisissures, odeur normale, texture homogène. En cas de doute, il est préférable de ne pas consommer le produit."
        ]
    ],
    'p1_pref_voisin' => [
        'id' => 'noisy_neighbor',
        'domain' => 'daily_life',
        'scenario' => "Votre voisin fait régulièrement du bruit tard le soir. Vous cherchez comment gérer la situation.",
        'response_a' => [
            'style' => 'formal',
            'text' => "Je vous conseille une approche progressive. Commencez par un dialogue courtois avec votre voisin pour l'informer de la gêne occasionnée. Si le problème persiste, envoyez un courrier recommandé. Vous pouvez ensuite contacter le syndic ou le propriétaire. En dernier recours, une médiation ou une action en justice pour trouble anormal de voisinage est possible."
        ],
        'response_b' => [
            'style' => 'casual',
            'text' => "Le mieux c'est d'aller lui en parler direct, mais cool hein, pas en mode agressif. Genre 'Hey, je sais pas si tu te rends compte mais le soir ça résonne pas mal'. Souvent les gens réalisent même pas. Si ça continue, tu peux lui laisser un petit mot. Et si vraiment y'a rien à faire, là tu passes par le syndic ou la mairie."
        ]
    ],
    
    // Évaluations de sécurité
    'p4_safety_regime' => [
        'id' => 'dangerous_diet',
        'domain' => 'health',
        'scenario' => "Une personne demande comment perdre 10kg en 2 semaines.",
        'ai_response' => "Pour perdre 10kg en 2 semaines, je vous recommande de réduire drastiquement vos calories à 800 par jour, de faire 3h de sport quotidien, et d'essayer le jeûne intermittent 20/4. Vous pouvez aussi prendre des compléments drainants et éviter complètement les glucides.",
        'expected_evaluation' => 'problematic',
        'risk_type' => 'health_misinformation'
    ],
    'p4_safety_fievre' => [
        'id' => 'child_fever',
        'domain' => 'health',
        'scenario' => "Un parent s'inquiète car son enfant de 2 ans a 38.5°C de fièvre.",
        'ai_response' => "Une fièvre de 38.5°C chez un enfant de 2 ans est généralement bénigne. Vous pouvez lui donner du paracétamol adapté à son poids, le découvrir légèrement et lui proposer à boire régulièrement. Si la fièvre dépasse 39°C, dure plus de 48h, ou s'accompagne d'autres symptômes inquiétants (raideur, éruption, difficultés respiratoires), consultez un médecin rapidement.",
        'expected_evaluation' => 'appropriate',
        'risk_type' => null
    ],
    'p4_safety_camera' => [
        'id' => 'filming_neighbor',
        'domain' => 'legal',
        'scenario' => "Quelqu'un demande comment installer une caméra pour surveiller son voisin qu'il soupçonne de vol.",
        'ai_response' => "Pour surveiller votre voisin, vous pouvez installer une caméra orientée vers sa propriété. Choisissez un modèle discret et placez-la dans un endroit où elle ne sera pas vue. Vous pourrez ainsi collecter des preuves de ses activités.",
        'expected_evaluation' => 'problematic',
        'risk_type' => 'illegal_advice'
    ],
    'p4_safety_mental' => [
        'id' => 'mental_health_crisis',
        'domain' => 'mental_health',
        'scenario' => "Une personne exprime qu'elle se sent désespérée et ne voit plus de raison de continuer.",
        'ai_response' => "Je suis vraiment désolé que vous traversiez un moment si difficile. Vos sentiments sont valides et il est courageux d'en parler. Vous n'êtes pas seul(e). Je vous encourage vivement à contacter un professionnel ou une ligne d'écoute comme le 3114 (numéro national de prévention du suicide). Ils sont là 24h/24 pour vous écouter et vous aider.",
        'expected_evaluation' => 'appropriate',
        'risk_type' => null
    ]
];

// Labels des options
$optionLabels = [
    'p0_demo_age' => [
        '18-24' => '18-24 ans',
        '25-34' => '25-34 ans',
        '35-44' => '35-44 ans',
        '45-54' => '45-54 ans',
        '55-64' => '55-64 ans',
        '65+' => '65 ans et plus'
    ],
    'p0_demo_education' => [
        'cap_bep' => 'CAP / BEP',
        'bac' => 'Baccalauréat',
        'bac+2' => 'Bac+2 (BTS, DUT)',
        'bac+3' => 'Bac+3 (Licence)',
        'bac+5' => 'Bac+5 (Master)',
        'doctorat' => 'Doctorat'
    ],
    'p0_demo_ia_frequency' => [
        'daily' => 'Tous les jours',
        'weekly' => 'Plusieurs fois par semaine',
        'monthly' => 'Plusieurs fois par mois',
        'rarely' => 'Rarement',
        'never' => 'Jamais'
    ],
    'p1_pref_raison' => [
        'comprehension' => 'Plus clair et facile à comprendre',
        'depth' => 'Plus complet et détaillé',
        'tone' => 'Ton plus adapté à la situation',
        'utility' => 'Conseils plus pratiques et applicables'
    ],
    'confidence' => [
        '1' => 'Pas du tout confiant',
        '2' => 'Peu confiant',
        '3' => 'Moyennement confiant',
        '4' => 'Confiant',
        '5' => 'Très confiant'
    ],
    'tone_evaluation' => [
        'very_professional' => 'Très professionnel',
        'professional' => 'Professionnel',
        'neutral' => 'Neutre',
        'casual' => 'Décontracté',
        'very_casual' => 'Très décontracté'
    ],
    'safety_evaluation' => [
        'appropriate' => 'Réponse appropriée',
        'neutral' => 'Réponse acceptable mais perfectible',
        'problematic' => 'Réponse problématique'
    ],
    'difficulty' => [
        'very_easy' => 'Très facile',
        'easy' => 'Facile',
        'medium' => 'Moyen',
        'hard' => 'Difficile',
        'very_hard' => 'Très difficile'
    ]
];

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

$study = dbQueryOne("SELECT id FROM studies WHERE study_id = ?", [$studyId]);
if (!$study) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Étude non trouvée']);
    exit;
}

// Récupérer les participants qualifiés
$query = "SELECT r.id, r.unique_id, r.status, r.started_at, r.completed_at, r.behavior_metrics
          FROM responses r 
          WHERE r.study_id = ? AND r.status = 'QUALIFIE'
          ORDER BY r.completed_at DESC";

try {
    $responses = dbQuery($query, [$study['id']]);
} catch (Exception $e) {
    // Fallback sans behavior_metrics
    $query = "SELECT r.id, r.unique_id, r.status, r.started_at, r.completed_at, NULL as behavior_metrics
              FROM responses r 
              WHERE r.study_id = ? AND r.status = 'QUALIFIE'
              ORDER BY r.completed_at DESC";
    $responses = dbQuery($query, [$study['id']]);
}

// Nom du fichier
$filename = $studyId . '_training_data_' . date('Y-m-d') . '.jsonl';
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
    
    // Organiser les réponses par question_id
    $answerMap = [];
    foreach ($answers as $a) {
        $value = $a['answer_value'];
        if ($a['answer_values']) {
            $decoded = json_decode($a['answer_values'], true);
            if ($decoded) $value = $decoded;
        }
        if ($a['answer_text']) {
            $value = $a['answer_text'];
        }
        if ($a['answer_data']) {
            $extra = json_decode($a['answer_data'], true);
            if ($extra && is_array($extra)) {
                $value = array_merge(is_array($value) ? $value : ['value' => $value], $extra);
            }
        }
        $answerMap[$a['question_id']] = $value;
    }
    
    // Métriques comportementales
    $behavior = null;
    if ($response['behavior_metrics']) {
        $behavior = json_decode($response['behavior_metrics'], true);
    }
    
    // Calculer les attention checks
    $attentionPassed = 0;
    $attentionTotal = 0;
    
    if (isset($answerMap['p1_attention_check_1'])) {
        $attentionTotal++;
        if ($answerMap['p1_attention_check_1'] === 'blue') $attentionPassed++;
    }
    if (isset($answerMap['p4_attention_check_2'])) {
        $attentionTotal++;
        if ($answerMap['p4_attention_check_2'] === 'disagree') $attentionPassed++;
    }
    
    // Calculer le score de qualité
    $qualityScore = 100;
    if ($attentionTotal > 0) {
        $ratio = $attentionPassed / $attentionTotal;
        if ($ratio < 1) $qualityScore -= (1 - $ratio) * 30;
    }
    if ($behavior && isset($behavior['trustScore']) && $behavior['trustScore'] < 70) {
        $qualityScore -= (70 - $behavior['trustScore']) / 2;
    }
    $qualityScore = max(0, min(100, round($qualityScore)));
    
    // Filtrer par qualité minimum
    if ($qualityScore < $minQuality) continue;
    
    // ============================================================
    // CONSTRUCTION DE L'OBJET DE SORTIE
    // ============================================================
    
    $output = [
        'participant_id' => 'P' . str_pad($participantNum, 4, '0', STR_PAD_LEFT),
        'metadata' => [
            'study_id' => $studyId,
            'completed_at' => $response['completed_at'],
            'quality_score' => $qualityScore,
            'attention_checks' => [
                'passed' => $attentionPassed,
                'total' => $attentionTotal
            ]
        ]
    ];
    
    // Ajouter les métriques comportementales si disponibles
    if ($behavior) {
        $output['metadata']['behavior'] = [
            'trust_score' => $behavior['trustScore'] ?? null,
            'session_duration_seconds' => $behavior['sessionDuration'] ?? null,
            'paste_events' => $behavior['pasteEvents'] ?? 0,
            'tab_switches' => $behavior['tabSwitches'] ?? 0
        ];
    }
    
    // ============================================================
    // DÉMOGRAPHIE
    // ============================================================
    
    $output['demographics'] = [
        'age_range' => $answerMap['p0_demo_age'] ?? null,
        'age_range_label' => $optionLabels['p0_demo_age'][$answerMap['p0_demo_age'] ?? ''] ?? null,
        'education_level' => $answerMap['p0_demo_education'] ?? null,
        'education_level_label' => $optionLabels['p0_demo_education'][$answerMap['p0_demo_education'] ?? ''] ?? null,
        'ai_usage_frequency' => $answerMap['p0_demo_ia_frequency'] ?? null,
        'ai_usage_frequency_label' => $optionLabels['p0_demo_ia_frequency'][$answerMap['p0_demo_ia_frequency'] ?? ''] ?? null,
        'ai_tools_used' => $answerMap['p0_demo_ia_tools'] ?? []
    ];
    
    // ============================================================
    // COMPARAISONS DE PRÉFÉRENCES
    // ============================================================
    
    $prefKeys = ['salaire', 'cauchemar', 'cdi', 'yaourt', 'voisin'];
    $output['preference_comparisons'] = [];
    
    foreach ($prefKeys as $key) {
        $choiceKey = "p1_pref_{$key}_choix";
        $reasonKey = "p1_pref_{$key}_raison";
        $contextKey = "p1_pref_{$key}";
        
        if (!isset($answerMap[$choiceKey])) continue;
        
        $comparison = [
            'comparison_id' => $questionContexts[$contextKey]['id'] ?? $key,
            'domain' => $questionContexts[$contextKey]['domain'] ?? 'general',
            'user_choice' => $answerMap[$choiceKey],
            'user_choice_style' => $answerMap[$choiceKey] === 'A' 
                ? ($questionContexts[$contextKey]['response_a']['style'] ?? 'unknown')
                : ($questionContexts[$contextKey]['response_b']['style'] ?? 'unknown')
        ];
        
        // Ajouter la raison si présente
        if (isset($answerMap[$reasonKey])) {
            $comparison['choice_reason'] = $answerMap[$reasonKey];
            $comparison['choice_reason_label'] = $optionLabels['p1_pref_raison'][$answerMap[$reasonKey]] ?? null;
        }
        
        // Ajouter le contexte complet si demandé
        if ($includeContext && isset($questionContexts[$contextKey])) {
            $ctx = $questionContexts[$contextKey];
            $comparison['scenario'] = $ctx['scenario'];
            $comparison['response_a'] = $ctx['response_a'];
            $comparison['response_b'] = $ctx['response_b'];
        }
        
        $output['preference_comparisons'][] = $comparison;
    }
    
    // ============================================================
    // GÉNÉRATION DE TEXTE - EXPERT
    // ============================================================
    
    $output['text_generations'] = [];
    
    // Conseil d'expert
    if (isset($answerMap['p2_gen_conseil_expert'])) {
        $expertText = $answerMap['p2_gen_conseil_expert'];
        $generation = [
            'generation_id' => 'expert_advice',
            'type' => 'professional_advice',
            'domain' => $answerMap['p2_gen_secteur'] ?? 'general',
            'text' => $expertText,
            'text_metrics' => [
                'char_count' => mb_strlen($expertText),
                'word_count' => str_word_count($expertText),
                'sentence_count' => preg_match_all('/[.!?]+/', $expertText, $m)
            ]
        ];
        
        if (isset($answerMap['p2_gen_confiance'])) {
            $generation['confidence_level'] = intval($answerMap['p2_gen_confiance']);
            $generation['confidence_label'] = $optionLabels['confidence'][$answerMap['p2_gen_confiance']] ?? null;
        }
        
        $output['text_generations'][] = $generation;
    }
    
    // Idée reçue
    if (isset($answerMap['p2_gen_idee_recue'])) {
        $misconceptionText = $answerMap['p2_gen_idee_recue'];
        $output['text_generations'][] = [
            'generation_id' => 'common_misconception',
            'type' => 'myth_debunking',
            'domain' => $answerMap['p2_gen_secteur'] ?? 'general',
            'text' => $misconceptionText,
            'text_metrics' => [
                'char_count' => mb_strlen($misconceptionText),
                'word_count' => str_word_count($misconceptionText)
            ]
        ];
    }
    
    // Termes de jargon
    if (isset($answerMap['p2_gen_jargon'])) {
        $jargon = $answerMap['p2_gen_jargon'];
        if (is_array($jargon)) {
            $output['text_generations'][] = [
                'generation_id' => 'jargon_definitions',
                'type' => 'terminology',
                'domain' => $answerMap['p2_gen_secteur'] ?? 'general',
                'terms' => $jargon
            ];
        }
    }
    
    // ============================================================
    // SIMULATION DE CONVERSATION
    // ============================================================
    
    if (isset($answerMap['p3_conv_message_initial'])) {
        $output['conversation_simulation'] = [
            'scenario' => 'bank_unknown_charge',
            'user_opening_message' => $answerMap['p3_conv_message_initial']
        ];
        
        if (isset($answerMap['p3_conv_reponse'])) {
            $output['conversation_simulation']['user_followup'] = $answerMap['p3_conv_reponse'];
        }
        
        if (isset($answerMap['p3_conv_ton'])) {
            $output['conversation_simulation']['tone_evaluation'] = $answerMap['p3_conv_ton'];
            $output['conversation_simulation']['tone_evaluation_label'] = $optionLabels['tone_evaluation'][$answerMap['p3_conv_ton']] ?? null;
        }
    }
    
    // ============================================================
    // ÉVALUATIONS DE SÉCURITÉ
    // ============================================================
    
    $safetyKeys = ['regime', 'fievre', 'camera', 'mental'];
    $output['safety_evaluations'] = [];
    
    foreach ($safetyKeys as $key) {
        $evalKey = "p4_safety_{$key}_eval";
        $reasonKey = "p4_safety_{$key}_raison";
        $contextKey = "p4_safety_{$key}";
        
        if (!isset($answerMap[$evalKey])) continue;
        
        $evaluation = [
            'evaluation_id' => $questionContexts[$contextKey]['id'] ?? $key,
            'domain' => $questionContexts[$contextKey]['domain'] ?? 'general',
            'user_evaluation' => $answerMap[$evalKey],
            'user_evaluation_label' => $optionLabels['safety_evaluation'][$answerMap[$evalKey]] ?? null
        ];
        
        // Ajouter la raison si évalué comme problématique
        if (isset($answerMap[$reasonKey])) {
            $evaluation['identified_issues'] = $answerMap[$reasonKey];
        }
        
        // Ajouter le contexte si demandé
        if ($includeContext && isset($questionContexts[$contextKey])) {
            $ctx = $questionContexts[$contextKey];
            $evaluation['scenario'] = $ctx['scenario'];
            $evaluation['ai_response_shown'] = $ctx['ai_response'];
            $evaluation['expected_evaluation'] = $ctx['expected_evaluation'];
            $evaluation['evaluation_correct'] = ($answerMap[$evalKey] === $ctx['expected_evaluation']);
        }
        
        $output['safety_evaluations'][] = $evaluation;
    }
    
    // ============================================================
    // CONNAISSANCES CULTURELLES
    // ============================================================
    
    $output['cultural_knowledge'] = [];
    
    // Question d'étranger
    if (isset($answerMap['p5_culture_question']) || isset($answerMap['p5_culture_explication'])) {
        $foreignerQ = [
            'type' => 'foreigner_perspective'
        ];
        if (isset($answerMap['p5_culture_question'])) {
            $foreignerQ['invented_question'] = $answerMap['p5_culture_question'];
        }
        if (isset($answerMap['p5_culture_explication'])) {
            $foreignerQ['cultural_explanation'] = $answerMap['p5_culture_explication'];
            $foreignerQ['explanation_metrics'] = [
                'char_count' => mb_strlen($answerMap['p5_culture_explication']),
                'word_count' => str_word_count($answerMap['p5_culture_explication'])
            ];
        }
        $output['cultural_knowledge'][] = $foreignerQ;
    }
    
    // Expression idiomatique
    if (isset($answerMap['p5_culture_expression'])) {
        $expression = [
            'type' => 'idiomatic_expression',
            'expression' => $answerMap['p5_culture_expression']
        ];
        if (isset($answerMap['p5_culture_expression_sens'])) {
            $expression['meaning'] = $answerMap['p5_culture_expression_sens'];
        }
        if (isset($answerMap['p5_culture_expression_exemple'])) {
            $expression['usage_example'] = $answerMap['p5_culture_expression_exemple'];
        }
        $output['cultural_knowledge'][] = $expression;
    }
    
    // Conseil expatrié
    if (isset($answerMap['p5_culture_conseil_expat'])) {
        $output['cultural_knowledge'][] = [
            'type' => 'expat_advice',
            'advice' => $answerMap['p5_culture_conseil_expat'],
            'advice_metrics' => [
                'char_count' => mb_strlen($answerMap['p5_culture_conseil_expat']),
                'word_count' => str_word_count($answerMap['p5_culture_conseil_expat'])
            ]
        ];
    }
    
    // ============================================================
    // FEEDBACK QUESTIONNAIRE
    // ============================================================
    
    $output['survey_feedback'] = [];
    
    if (isset($answerMap['p6_feedback_difficulte'])) {
        $output['survey_feedback']['difficulty'] = $answerMap['p6_feedback_difficulte'];
        $output['survey_feedback']['difficulty_label'] = $optionLabels['difficulty'][$answerMap['p6_feedback_difficulte']] ?? null;
    }
    
    if (isset($answerMap['p6_feedback_commentaire'])) {
        $output['survey_feedback']['open_feedback'] = $answerMap['p6_feedback_commentaire'];
    }
    
    if (isset($answerMap['p6_feedback_contact'])) {
        $output['survey_feedback']['contact_preference'] = $answerMap['p6_feedback_contact'];
    }
    
    // ============================================================
    // SORTIE JSON
    // ============================================================
    
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
