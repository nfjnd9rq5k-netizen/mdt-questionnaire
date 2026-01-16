<?php
/**
 * ============================================================
 * GÉNÉRATION DE DONNÉES DE TEST - DATA_IA_JAN2026
 * ============================================================
 * Crée 20 faux participants avec des réponses COMPLÈTES et réalistes
 * 
 * USAGE: Exécuter une seule fois via le navigateur :
 *        /etudes/api/generate-test-data.php
 * 
 * ATTENTION: Supprimer ce fichier après utilisation !
 */

require_once 'db.php';

// Vérifier que l'étude existe
$study = dbQueryOne("SELECT id FROM studies WHERE study_id = 'DATA_IA_JAN2026'");
if (!$study) {
    die("Erreur: L'étude DATA_IA_JAN2026 n'existe pas. Exécutez d'abord sync-studies.php");
}
$studyId = $study['id'];

// ============================================================
// DONNÉES RÉALISTES POUR LA GÉNÉRATION
// ============================================================

$pseudos = [
    'Marie', 'Lucas', 'Emma', 'Hugo', 'Léa', 'Thomas', 'Chloé', 'Nathan',
    'Camille', 'Maxime', 'Sarah', 'Antoine', 'Julie', 'Quentin', 'Laura',
    'Alexandre', 'Manon', 'Romain', 'Pauline', 'Julien'
];

$domains = ['gmail.com', 'outlook.fr', 'yahoo.fr', 'orange.fr', 'free.fr'];

// Options
$ages = ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
$education = ['cap_bep', 'bac', 'bac+2', 'bac+3', 'bac+5', 'doctorat'];
$iaFrequency = ['daily', 'weekly', 'monthly', 'rarely', 'never'];
$iaTools = ['chatgpt', 'claude', 'gemini', 'copilot', 'mistral', 'other'];
$sectors = ['tech', 'sante', 'finance', 'education', 'commerce', 'industrie'];
$preferenceReasons = ['comprehension', 'depth', 'tone', 'utility'];
$confidenceLevels = ['1', '2', '3', '4', '5'];
$toneEvaluations = ['very_professional', 'professional', 'neutral', 'casual', 'very_casual'];
$safetyEvaluations = ['appropriate', 'neutral', 'problematic'];
$difficultyRatings = ['very_easy', 'easy', 'medium', 'hard', 'very_hard'];

// Conseils d'expert par secteur (variés et réalistes)
$expertAdvices = [
    'tech' => [
        "En tant que développeur avec 8 ans d'expérience, mon conseil principal serait de toujours privilégier la lisibilité du code sur la performance prématurée. Un code bien structuré et commenté sera plus facile à maintenir et à faire évoluer. Investissez du temps dans les tests automatisés dès le début du projet, ça vous sauvera des heures de debug plus tard.",
        "Après 10 ans dans le développement web, je recommande de ne jamais sous-estimer l'importance de la documentation. Un projet bien documenté, c'est un projet qui survit au turnover d'équipe. Prenez le temps d'écrire des README clairs et des commentaires pertinents.",
        "Mon expérience de tech lead m'a appris que la communication est aussi importante que le code. Un développeur qui sait expliquer ses choix techniques à des non-techniciens vaut de l'or. Cultivez cette compétence autant que vos skills techniques."
    ],
    'sante' => [
        "Après 12 ans dans le secteur médical, je recommande vivement de toujours écouter activement vos patients. La communication est la clé d'un bon diagnostic. Prenez le temps d'expliquer clairement les traitements et leurs effets secondaires potentiels, même si vous manquez de temps.",
        "En 15 ans de médecine générale, j'ai compris que le plus important est de créer une relation de confiance avec le patient. Un patient qui se sent écouté sera plus honnête sur ses symptômes et plus observant dans son traitement.",
        "Mon conseil après des années en milieu hospitalier : ne négligez jamais votre propre santé mentale. Le burnout dans notre profession est réel. Apprenez à poser des limites et à demander de l'aide quand nécessaire."
    ],
    'finance' => [
        "Mon expérience de 10 ans en gestion de patrimoine m'a appris que la diversification reste la règle d'or. Ne mettez jamais tous vos œufs dans le même panier. Commencez à épargner tôt, même de petites sommes, et profitez de l'effet des intérêts composés sur le long terme.",
        "Après 8 ans en banque d'investissement, mon conseil est de toujours comprendre ce dans quoi vous investissez. Si vous ne pouvez pas expliquer simplement un produit financier, ne l'achetez pas. La complexité cache souvent des frais ou des risques.",
        "En gestion de patrimoine, j'ai vu trop de gens prendre des décisions émotionnelles. Mon conseil : automatisez vos investissements et ne regardez pas les cours tous les jours. La patience est la qualité numéro un de l'investisseur."
    ],
    'education' => [
        "En 15 ans d'enseignement, j'ai constaté que l'engagement des élèves est primordial. Variez vos méthodes pédagogiques, utilisez des exemples concrets tirés de leur quotidien, et créez un environnement où l'erreur est perçue comme une opportunité d'apprentissage.",
        "Mon expérience de professeur m'a appris que chaque élève apprend différemment. Certains sont visuels, d'autres auditifs ou kinesthésiques. Proposez des supports variés et observez ce qui fonctionne pour chacun.",
        "Après 20 ans dans l'éducation, mon conseil est de ne jamais humilier un élève qui se trompe. Une remarque blessante peut bloquer un enfant pendant des années. Encouragez toujours l'effort, pas seulement le résultat."
    ],
    'commerce' => [
        "La clé du succès commercial réside dans la relation client. Après 9 ans dans la vente, je peux affirmer qu'un client satisfait en amène dix autres. Écoutez vraiment leurs besoins avant de proposer une solution, même si ça prend plus de temps.",
        "Mon expérience en tant que directeur commercial m'a montré que les meilleurs vendeurs ne vendent pas : ils aident leurs clients à acheter. Posez des questions, comprenez le besoin réel, et proposez uniquement ce qui apporte de la valeur.",
        "En 12 ans de commerce B2B, j'ai appris que la relance fait 80% de la vente. La plupart des commerciaux abandonnent après 2 tentatives, alors que les études montrent qu'il en faut souvent 5 ou 6. Soyez persévérant mais jamais harcelant."
    ],
    'industrie' => [
        "Après 15 ans dans l'industrie automobile, mon conseil principal est de ne jamais sacrifier la sécurité pour la productivité. Un accident coûte infiniment plus cher qu'un arrêt de production. Investissez dans la formation continue de vos équipes.",
        "Mon expérience de responsable de production m'a appris que les meilleures idées d'amélioration viennent souvent des opérateurs. Écoutez ceux qui sont sur le terrain tous les jours, ils connaissent les problèmes mieux que quiconque.",
        "En industrie, la maintenance préventive est reine. J'ai vu trop d'usines négliger l'entretien pour gagner du temps, puis perdre des semaines sur une panne majeure. Planifiez, anticipez, prévenez."
    ]
];

// Idées reçues par secteur
$misconceptions = [
    'tech' => [
        "Beaucoup pensent que plus de lignes de code signifie un meilleur programme. C'est faux ! Un code concis et bien pensé est souvent plus efficace et maintenable qu'un code verbeux.",
        "Une idée reçue tenace : 'Il faut être bon en maths pour coder'. En réalité, la programmation demande surtout de la logique et de la créativité. Les maths avancées ne sont utiles que dans certains domaines spécifiques.",
        "On croit souvent qu'un bon développeur travaille seul dans son coin. Faux ! Les meilleurs devs sont ceux qui collaborent, partagent leurs connaissances et font des code reviews constructives."
    ],
    'sante' => [
        "Une idée reçue courante est que les antibiotiques soignent tout. En réalité, ils sont totalement inefficaces contre les virus et leur surutilisation crée des résistances bactériennes dangereuses pour la santé publique.",
        "Beaucoup pensent que 'naturel' signifie 'sans danger'. C'est faux ! L'arsenic et la ciguë sont naturels mais mortels. Un médicament doit être évalué sur ses effets, pas sur son origine.",
        "L'idée que le rhume vient du froid est un mythe. Les rhumes sont causés par des virus, pas par les basses températures. Le froid nous fait simplement passer plus de temps en intérieur, où les virus se transmettent plus facilement."
    ],
    'finance' => [
        "Contrairement à la croyance populaire, investir en bourse n'est pas du gambling. Avec une stratégie long terme et diversifiée, c'est un outil de création de richesse éprouvé, très différent des jeux de hasard.",
        "Une erreur commune : croire qu'il faut être riche pour investir. Avec les ETF et les applications modernes, on peut commencer à investir avec 10€ par mois. Le plus important est de commencer tôt.",
        "L'idée que l'immobilier monte toujours est dangereuse. Les prix peuvent baisser, parfois fortement et durablement. L'immobilier n'est pas un investissement magique sans risque."
    ],
    'education' => [
        "On croit souvent que certains élèves sont 'mauvais en maths'. En réalité, avec les bonnes méthodes, de la patience et un enseignement adapté, tout le monde peut progresser significativement en mathématiques.",
        "Une idée reçue : les écrans rendent les enfants moins intelligents. Les études montrent que c'est l'usage qui compte. Un usage éducatif et encadré peut au contraire développer certaines compétences.",
        "Beaucoup pensent que le redoublement aide les élèves en difficulté. Les recherches montrent que c'est rarement efficace et souvent contre-productif. L'accompagnement personnalisé est bien plus bénéfique."
    ],
    'commerce' => [
        "L'idée que le client a toujours raison est un mythe dangereux. Un bon commercial sait dire non quand c'est dans l'intérêt du client ou de l'entreprise. La relation doit être équilibrée.",
        "On croit que les meilleurs vendeurs sont extravertis et bavards. Faux ! Les études montrent que les vendeurs les plus performants sont souvent des ambiverts qui savent surtout écouter.",
        "Une erreur commune : penser que baisser ses prix est la meilleure façon de vendre plus. Souvent, c'est le contraire ! Un prix trop bas peut signaler une mauvaise qualité et dévaloriser votre offre."
    ],
    'industrie' => [
        "Beaucoup croient que l'automatisation va supprimer tous les emplois industriels. En réalité, elle transforme les emplois : moins de tâches répétitives, plus de supervision et de maintenance de systèmes complexes.",
        "L'idée que 'Made in France' est toujours synonyme de qualité est simpliste. La qualité dépend des processus et des contrôles, pas uniquement du lieu de fabrication.",
        "On pense souvent que la production de masse est plus polluante que l'artisanat. C'est parfois l'inverse : une usine moderne optimise les ressources et traite ses déchets, ce qui n'est pas toujours le cas des petites structures."
    ]
];

// Termes de jargon par secteur
$jargonTerms = [
    'tech' => [
        [['terme1' => 'Refactoring', 'definition1' => 'Restructuration du code existant sans changer son comportement pour améliorer sa lisibilité et maintenabilité']],
        [['terme1' => 'Dette technique', 'definition1' => 'Accumulation de choix de développement rapides mais non optimaux qui devront être corrigés plus tard']],
        [['terme1' => 'CI/CD', 'definition1' => 'Intégration et déploiement continus - pratique automatisant les tests et la mise en production du code']]
    ],
    'sante' => [
        [['terme1' => 'Iatrogène', 'definition1' => 'Se dit d\'un effet indésirable causé par un traitement médical ou une intervention de santé']],
        [['terme1' => 'Posologie', 'definition1' => 'Dosage et fréquence d\'administration d\'un médicament prescrit à un patient']],
        [['terme1' => 'Anamnèse', 'definition1' => 'Recueil des antécédents médicaux du patient par l\'interrogatoire']]
    ],
    'finance' => [
        [['terme1' => 'Hedge', 'definition1' => 'Stratégie de couverture visant à réduire le risque d\'un investissement par une position inverse']],
        [['terme1' => 'Due diligence', 'definition1' => 'Audit approfondi réalisé avant une acquisition ou un investissement']],
        [['terme1' => 'EBITDA', 'definition1' => 'Bénéfice avant intérêts, impôts, dépréciation et amortissement - indicateur de performance opérationnelle']]
    ],
    'education' => [
        [['terme1' => 'Différenciation pédagogique', 'definition1' => 'Adaptation de l\'enseignement aux besoins spécifiques de chaque élève']],
        [['terme1' => 'ZPD', 'definition1' => 'Zone proximale de développement - écart entre ce qu\'un élève peut faire seul et avec aide']],
        [['terme1' => 'Évaluation formative', 'definition1' => 'Évaluation en cours d\'apprentissage pour ajuster l\'enseignement, sans notation']]
    ],
    'commerce' => [
        [['terme1' => 'Lead', 'definition1' => 'Contact commercial qualifié montrant un intérêt pour le produit ou service']],
        [['terme1' => 'Upselling', 'definition1' => 'Technique consistant à proposer un produit supérieur ou plus cher au client']],
        [['terme1' => 'Churn', 'definition1' => 'Taux d\'attrition - pourcentage de clients perdus sur une période donnée']]
    ],
    'industrie' => [
        [['terme1' => 'Lean manufacturing', 'definition1' => 'Méthode de gestion visant à éliminer les gaspillages et optimiser les processus de production']],
        [['terme1' => 'TRS', 'definition1' => 'Taux de Rendement Synthétique - indicateur mesurant l\'efficacité d\'une machine ou ligne de production']],
        [['terme1' => 'AMDEC', 'definition1' => 'Analyse des Modes de Défaillance, de leurs Effets et de leur Criticité']]
    ]
];

// Expressions françaises avec contexte complet
$expressions = [
    ['expr' => 'Avoir le cafard', 'sens' => 'Être triste, mélancolique, avoir le moral en berne', 'exemple' => 'Depuis son départ, j\'ai le cafard tous les dimanches soir. La maison me semble trop vide.'],
    ['expr' => 'Poser un lapin', 'sens' => 'Ne pas venir à un rendez-vous sans prévenir', 'exemple' => 'Il m\'a posé un lapin hier soir ! Je l\'ai attendu une heure au restaurant comme une idiote.'],
    ['expr' => 'Avoir la flemme', 'sens' => 'Ne pas avoir envie de faire quelque chose, être paresseux', 'exemple' => 'J\'ai vraiment la flemme d\'aller courir ce matin, je crois que je vais rester au lit encore un peu.'],
    ['expr' => 'Se prendre un râteau', 'sens' => 'Être rejeté par quelqu\'un qu\'on essaie de séduire', 'exemple' => 'Le pauvre, il s\'est pris un râteau monumental à la soirée de samedi. Elle ne lui a même pas répondu.'],
    ['expr' => 'C\'est la galère', 'sens' => 'C\'est très difficile, compliqué, pénible', 'exemple' => 'Trouver un appartement à Paris en ce moment, c\'est vraiment la galère totale. Les prix sont délirants.'],
    ['expr' => 'Avoir un coup de barre', 'sens' => 'Ressentir une fatigue soudaine et intense', 'exemple' => 'Tous les jours après le déjeuner, j\'ai un coup de barre vers 14h. Impossible de me concentrer.'],
    ['expr' => 'Être dans le coaltar', 'sens' => 'Être dans un état de confusion, de fatigue, ne pas avoir les idées claires', 'exemple' => 'Le matin avant mon premier café, je suis complètement dans le coaltar. Ne me parlez pas avant 9h !'],
    ['expr' => 'Péter les plombs', 'sens' => 'Perdre son sang-froid, s\'énerver violemment', 'exemple' => 'Mon chef a complètement pété les plombs quand il a vu les résultats du trimestre. Il criait dans tout l\'open space.'],
    ['expr' => 'Avoir le beurre et l\'argent du beurre', 'sens' => 'Vouloir tout avoir, tous les avantages sans les inconvénients', 'exemple' => 'Il veut le salaire d\'un manager mais sans les responsabilités. Il veut le beurre et l\'argent du beurre !'],
    ['expr' => 'Mettre son grain de sel', 'sens' => 'Donner son avis sans qu\'on le demande, s\'immiscer', 'exemple' => 'Ma belle-mère met toujours son grain de sel dans notre éducation. Ça devient épuisant.']
];

// Questions d'étrangers avec explications culturelles
$foreignerQuestions = [
    ['question' => 'Pourquoi les Français font-ils la bise pour dire bonjour ?', 'explication' => 'La bise est une tradition sociale française qui marque la proximité et l\'affection. Le nombre de bises varie selon les régions, de 1 à 4. On fait la bise aux amis, à la famille, mais rarement dans un contexte professionnel formel avec des inconnus. C\'est un rituel social qui peut surprendre les étrangers mais qui est très naturel pour nous.'],
    ['question' => 'Pourquoi les Français passent-ils autant de temps à table ?', 'explication' => 'En France, le repas est un moment social important, pas juste un besoin à satisfaire rapidement. On prend le temps de discuter, de partager plusieurs plats, de savourer le vin. Le déjeuner peut durer 1 à 2 heures, surtout le dimanche en famille. C\'est une façon de cultiver les liens sociaux et de profiter des plaisirs de la vie.'],
    ['question' => 'Pourquoi les magasins sont-ils fermés le dimanche ?', 'explication' => 'C\'est une tradition héritée de la culture catholique et du droit du travail français. Le dimanche est considéré comme un jour de repos familial et de vie sociale. Certains commerces alimentaires et touristiques peuvent ouvrir, mais beaucoup restent fermés pour préserver l\'équilibre vie pro/perso. C\'est aussi une question de choix de société.'],
    ['question' => 'Pourquoi les Français se plaignent-ils tout le temps ?', 'explication' => 'Ce stéréotype vient de notre culture de critique et de débat. Les Français expriment facilement leur mécontentement car c\'est vu comme un droit démocratique de contester. C\'est aussi une forme de perfectionnisme : on critique pour améliorer les choses, pas par négativité. Râler ensemble crée aussi du lien social, paradoxalement !'],
    ['question' => 'Pourquoi y a-t-il autant de grèves en France ?', 'explication' => 'La grève fait partie de la culture sociale française depuis la Révolution. C\'est un droit constitutionnel et un moyen d\'expression politique respecté. Les Français considèrent que les acquis sociaux ont été obtenus par la lutte et doivent être défendus de la même manière. C\'est aussi lié à des syndicats historiquement forts et combatifs.'],
    ['question' => 'Pourquoi les Français ne parlent-ils pas anglais ?', 'explication' => 'C\'est un cliché qui évolue ! Les nouvelles générations sont bien meilleures. Historiquement, la France était une puissance culturelle dominante et le français était LA langue diplomatique. Il y a une fierté de la langue. Aussi, notre système éducatif favorisait longtemps l\'écrit sur l\'oral. Mais surtout, beaucoup comprennent l\'anglais mais n\'osent pas le parler par peur de faire des erreurs.']
];

// Conseils pour expatriés
$expatAdvices = [
    'Apprenez quelques mots de français, même basiques comme bonjour, merci, excusez-moi. Les Français apprécient énormément l\'effort et seront beaucoup plus accueillants et patients avec vous.',
    'Ne soyez pas surpris si les gens ne sourient pas dans la rue ou dans le métro. Ce n\'est pas de l\'hostilité, c\'est juste culturel. Les Français réservent leur sourire aux interactions personnelles.',
    'Les apéros du vendredi soir sont sacrés en France, c\'est le meilleur moyen de créer des liens avec vos collègues. Acceptez les invitations même si vous ne buvez pas d\'alcool !',
    'Prenez le temps de déjeuner. En France, le repas du midi est important et souvent pris avec les collègues. C\'est un moment social, évitez de manger un sandwich devant votre écran.',
    'Apprenez à faire la queue patiemment et respectez l\'ordre d\'arrivée. Mais n\'hésitez pas à dire poliment quelque chose si quelqu\'un essaie de passer devant vous, c\'est normal ici.',
    'La ponctualité pour les rendez-vous professionnels est importante, mais un petit retard est toléré entre amis. 15 minutes de retard à un dîner, c\'est presque poli - ça laisse le temps à l\'hôte de finir de préparer.',
    'Faites attention au vouvoiement ! Utilisez "vous" par défaut avec les inconnus, les aînés et dans le contexte professionnel. Le passage au "tu" est un moment significatif dans une relation.',
    'Ne soyez pas surpris par les discussions politiques passionnées. Les Français adorent débattre et ce n\'est pas personnel. Ça fait partie de la culture du café et de la vie sociale.'
];

// Messages de conversation bancaire
$bankMessages = [
    "Bonjour, j'ai remarqué un prélèvement de %d€ sur mon compte que je ne reconnais pas. La référence indique '%s'. Pouvez-vous m'aider à identifier cette transaction ?",
    "Bonjour, je viens de voir un débit de %d€ sur mon relevé avec la mention '%s'. Je n'ai aucun souvenir d'avoir fait cet achat. C'est peut-être une erreur ?",
    "Bonjour, un prélèvement de %d€ apparaît sur mon compte avec la référence '%s'. Je ne sais pas à quoi ça correspond. Pouvez-vous me renseigner ?",
    "Bonjour, je suis inquiet car je vois un mouvement de %d€ sortant de mon compte, référencé '%s'. Je n'ai pas autorisé ce paiement. Que dois-je faire ?"
];

$bankReferences = ['AMZN MKTP', 'PAYPAL *MERCHANT', 'SEPA-DD UNKNOWN', 'CB PARIS COMMERCE', 'VIR INST INCONNU'];

// Réponses de suivi conversation
$bankFollowups = [
    "D'accord, je comprends mieux maintenant. C'est peut-être l'abonnement que j'avais oublié. Je vais vérifier mes emails de confirmation. Merci pour l'explication !",
    "Ah oui, maintenant que vous le dites, ça pourrait être le renouvellement automatique de mon antivirus. Je vais vérifier et vous recontacter si le problème persiste.",
    "Merci pour ces informations. Je vais faire le point sur mes achats récents. Si je ne trouve rien, je reviendrai vers vous pour contester la transaction.",
    "Je vois, c'est possible que ce soit le paiement fractionné de ma commande du mois dernier. Je vérifie dans mes mails et je vous tiens au courant.",
    "Merci pour votre réponse rapide. Effectivement, après vérification, c'est bien un achat que j'avais fait. Désolé pour le dérangement !"
];

// Raisons si réponse problématique
$safetyIssueReasons = [
    'regime' => ['Conseils dangereux pour la santé', 'Objectif irréaliste médicalement', 'Risque de carences graves', 'Peut encourager des TCA'],
    'camera' => ['Atteinte à la vie privée', 'Potentiellement illégal', 'Encourage la surveillance', 'Pas de mention des lois'],
    'mental' => ['Minimise la détresse', 'Pas de ressources d\'aide', 'Conseils inadaptés', 'Risque d\'aggravation']
];

echo "<h1>Génération de 20 participants de test (données complètes)</h1>";
echo "<pre>";

$inserted = 0;

for ($i = 0; $i < 20; $i++) {
    $pseudo = $pseudos[$i];
    $email = strtolower($pseudo) . rand(10, 99) . '@' . $domains[array_rand($domains)];
    $uniqueId = bin2hex(random_bytes(8));
    
    // Déterminer le secteur pour ce participant
    $sector = $sectors[array_rand($sectors)];
    
    // Choix démographiques
    $age = $ages[array_rand($ages)];
    $edu = $education[array_rand($education)];
    $iaFreq = $iaFrequency[array_rand($iaFrequency)];
    $selectedTools = array_rand(array_flip($iaTools), rand(1, 3));
    if (!is_array($selectedTools)) $selectedTools = [$selectedTools];
    
    // Préférences A/B avec raisons
    $preferences = [];
    $prefReasons = [];
    for ($p = 1; $p <= 5; $p++) {
        $preferences[$p] = rand(0, 1) ? 'A' : 'B';
        $prefReasons[$p] = $preferenceReasons[array_rand($preferenceReasons)];
    }
    
    // Attention checks - 85% de bonnes réponses
    $attention1 = rand(1, 100) <= 85 ? 'blue' : ['red', 'green', 'yellow'][array_rand(['red', 'green', 'yellow'])];
    $attention2 = rand(1, 100) <= 85 ? 'disagree' : ['agree', 'neutral'][array_rand(['agree', 'neutral'])];
    
    // Sélection des contenus
    $expertAdvice = $expertAdvices[$sector][array_rand($expertAdvices[$sector])];
    $misconception = $misconceptions[$sector][array_rand($misconceptions[$sector])];
    $jargon = $jargonTerms[$sector][array_rand($jargonTerms[$sector])];
    $expr = $expressions[array_rand($expressions)];
    $foreignQ = $foreignerQuestions[array_rand($foreignerQuestions)];
    $expatAdvice = $expatAdvices[array_rand($expatAdvices)];
    
    // Message banque
    $bankAmount = rand(15, 250);
    $bankRef = $bankReferences[array_rand($bankReferences)];
    $bankMessage = sprintf($bankMessages[array_rand($bankMessages)], $bankAmount, $bankRef);
    $bankFollowup = $bankFollowups[array_rand($bankFollowups)];
    
    // Évaluations sécurité
    $safetyRegime = rand(0, 100) < 80 ? 'problematic' : 'neutral';
    $safetyFievre = rand(0, 100) < 80 ? 'appropriate' : 'neutral';
    $safetyCamera = rand(0, 100) < 80 ? 'problematic' : 'neutral';
    $safetyMental = rand(0, 100) < 80 ? 'appropriate' : 'neutral';
    
    // Métriques comportementales réalistes
    $sessionDuration = rand(600, 1800);
    $trustScore = rand(65, 98);
    $behaviorMetrics = json_encode([
        'sessionDuration' => $sessionDuration,
        'pasteEvents' => rand(0, 4),
        'tabSwitches' => rand(0, 8),
        'backspaceRatio' => round(rand(5, 18) / 100, 2),
        'trustScore' => $trustScore
    ]);
    
    try {
        dbBeginTransaction();
        
        // 1. Insérer la réponse principale
        $hoursAgo = rand(1, 168);
        dbExecute(
            "INSERT INTO responses (unique_id, study_id, status, started_at, completed_at, behavior_metrics) 
             VALUES (?, ?, 'QUALIFIE', DATE_SUB(NOW(), INTERVAL ? HOUR), DATE_SUB(NOW(), INTERVAL ? HOUR), ?)",
            [$uniqueId, $studyId, $hoursAgo, max(0, $hoursAgo - 1), $behaviorMetrics]
        );
        $responseId = dbLastId();
        
        // 2. Insérer la signalétique
        dbExecute(
            "INSERT INTO signaletiques (response_id, prenom, email) VALUES (?, ?, ?)",
            [$responseId, $pseudo, $email]
        );
        
        // 3. Insérer TOUTES les réponses
        $answers = [
            // ===== PARTIE 0 : DÉMOGRAPHIE =====
            ['p0_demo_age', $age, null, null],
            ['p0_demo_education', $edu, null, null],
            ['p0_demo_ia_frequency', $iaFreq, null, null],
            ['p0_demo_ia_tools', null, json_encode($selectedTools), null],
            
            // ===== PARTIE 1 : PRÉFÉRENCES =====
            ['p1_pref_salaire_choix', $preferences[1], null, null],
            ['p1_pref_salaire_raison', $prefReasons[1], null, null],
            ['p1_pref_cauchemar_choix', $preferences[2], null, null],
            ['p1_pref_cauchemar_raison', $prefReasons[2], null, null],
            ['p1_pref_cdi_choix', $preferences[3], null, null],
            ['p1_pref_cdi_raison', $prefReasons[3], null, null],
            ['p1_pref_yaourt_choix', $preferences[4], null, null],
            ['p1_pref_yaourt_raison', $prefReasons[4], null, null],
            ['p1_pref_voisin_choix', $preferences[5], null, null],
            ['p1_pref_voisin_raison', $prefReasons[5], null, null],
            ['p1_attention_check_1', $attention1, null, null],
            
            // ===== PARTIE 2 : GÉNÉRATION EXPERT =====
            ['p2_gen_secteur', $sector, null, null],
            ['p2_gen_conseil_expert', $expertAdvice, null, null],
            ['p2_gen_confiance', $confidenceLevels[array_rand($confidenceLevels)], null, null],
            ['p2_gen_idee_recue', $misconception, null, null],
            ['p2_gen_jargon', null, null, json_encode($jargon[0])],
            
            // ===== PARTIE 3 : CONVERSATION =====
            ['p3_conv_message_initial', $bankMessage, null, null],
            ['p3_conv_reponse', $bankFollowup, null, null],
            ['p3_conv_ton', $toneEvaluations[array_rand($toneEvaluations)], null, null],
            
            // ===== PARTIE 4 : ÉVALUATIONS SÉCURITÉ =====
            ['p4_safety_regime_eval', $safetyRegime, null, null],
            ['p4_safety_fievre_eval', $safetyFievre, null, null],
            ['p4_safety_camera_eval', $safetyCamera, null, null],
            ['p4_safety_mental_eval', $safetyMental, null, null],
            ['p4_attention_check_2', $attention2, null, null],
            
            // ===== PARTIE 5 : CULTURE =====
            ['p5_culture_question', $foreignQ['question'], null, null],
            ['p5_culture_explication', $foreignQ['explication'], null, null],
            ['p5_culture_expression', $expr['expr'], null, null],
            ['p5_culture_expression_sens', $expr['sens'], null, null],
            ['p5_culture_expression_exemple', $expr['exemple'], null, null],
            ['p5_culture_conseil_expat', $expatAdvice, null, null],
            
            // ===== PARTIE 6 : FEEDBACK =====
            ['p6_feedback_difficulte', $difficultyRatings[array_rand($difficultyRatings)], null, null],
            ['p6_feedback_contact', rand(0, 1) ? 'yes' : 'no', null, null]
        ];
        
        // Ajouter les raisons si évaluation problématique
        if ($safetyRegime === 'problematic') {
            $reasons = array_rand(array_flip($safetyIssueReasons['regime']), 2);
            $answers[] = ['p4_safety_regime_raison', null, json_encode($reasons), null];
        }
        if ($safetyCamera === 'problematic') {
            $reasons = array_rand(array_flip($safetyIssueReasons['camera']), 2);
            $answers[] = ['p4_safety_camera_raison', null, json_encode($reasons), null];
        }
        
        foreach ($answers as $ans) {
            dbExecute(
                "INSERT INTO answers (response_id, question_id, answer_value, answer_values, answer_data) VALUES (?, ?, ?, ?, ?)",
                [$responseId, $ans[0], $ans[1], $ans[2], $ans[3]]
            );
        }
        
        dbCommit();
        $inserted++;
        echo "✅ Participant $inserted: $pseudo ($email) - Secteur: $sector\n";
        echo "   📊 Trust: $trustScore | Attention: " . ($attention1 === 'blue' ? '✓' : '✗') . ($attention2 === 'disagree' ? '✓' : '✗') . "\n";
        
    } catch (Exception $e) {
        dbRollback();
        echo "❌ Erreur pour $pseudo: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ $inserted participants créés avec succès !\n";
echo str_repeat("=", 60) . "\n";
echo "\n📁 Export disponible: /api/export-jsonl-hq.php?study=DATA_IA_JAN2026\n";
echo "⚠️  IMPORTANT: Supprimez ce fichier après utilisation !\n";
echo "</pre>";

echo "<p><a href='../admin/dashboard.php'>Retour au dashboard</a></p>";
echo "<p><a href='export-jsonl-hq.php?study=DATA_IA_JAN2026' target='_blank'>Télécharger l'export JSONL haute qualité</a></p>";
