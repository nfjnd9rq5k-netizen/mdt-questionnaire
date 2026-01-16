

const BRAND_PATTERNS = {
    dyson: ['dyson', 'dison', 'dayson', 'dysson', 'diyson', 'dyzon', 'disone', 'daison'],
    airwrap: ['airwrap', 'air wrap', 'airwarp', 'airwarpe', 'air-wrap', 'airwrape', 'airwrapp', 'air warp'],
    
    shark: ['shark', 'sharc', 'sharck', 'chark', 'sharq', 'schark'],
    flexstyle: ['flexstyle', 'flex style', 'flex-style', 'flexstile', 'flextyle', 'flexestyle', 'flex stile'],
    
    ghd: ['ghd', 'g.h.d', 'g h d', 'gdhd', 'ghdd'],
    t3: ['t3', 't 3', 't-3'],
    babyliss: ['babyliss', 'baby liss', 'babiliss', 'babylis', 'babylliss', 'baby-liss', 'babilis'],
    remington: ['remington', 'remmington', 'remingthon', 'remingtonn', 'remminton'],
    philips: ['philips', 'phillips', 'philps', 'phillip', 'philipes', 'filips'],
    rowenta: ['rowenta', 'roventa', 'rowanta', 'rowentha'],
    drybar: ['drybar', 'dry bar', 'dry-bar', 'dribar']
};

function matchesBrand(text, brand) {
    if (!text || !brand) return false;
    
    const normalizedText = text.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') 
        .replace(/[^a-z0-9\s]/g, ' ') 
        .trim();
    
    const patterns = BRAND_PATTERNS[brand] || [brand.toLowerCase()];
    
    return patterns.some(pattern => {
        return normalizedText.includes(pattern) || 
               pattern.includes(normalizedText) ||
               levenshteinDistance(normalizedText, pattern) <= 2; 
    });
}

function identifyBrand(text) {
    if (!text) return null;
    
    const normalizedText = text.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .trim();
    
    if ((matchesBrand(text, 'dyson') && matchesBrand(text, 'airwrap')) ||
        normalizedText.includes('airwrap')) {
        return 'dyson_airwrap';
    }
    
    if ((matchesBrand(text, 'shark') && matchesBrand(text, 'flexstyle')) ||
        normalizedText.includes('flexstyle') || normalizedText.includes('flex style')) {
        return 'shark_flexstyle';
    }
    
    if (matchesBrand(text, 'dyson')) {
        return 'dyson';
    }
    
    if (matchesBrand(text, 'shark')) {
        return 'shark';
    }
    
    if (matchesBrand(text, 'ghd')) return 'ghd';
    if (matchesBrand(text, 't3')) return 't3';
    if (matchesBrand(text, 'babyliss')) return 'babyliss';
    if (matchesBrand(text, 'remington')) return 'remington';
    if (matchesBrand(text, 'philips')) return 'philips';
    if (matchesBrand(text, 'rowenta')) return 'rowenta';
    if (matchesBrand(text, 'drybar')) return 'drybar';
    
    return 'autre';
}

function levenshteinDistance(str1, str2) {
    const m = str1.length;
    const n = str2.length;
    const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));
    
    for (let i = 0; i <= m; i++) dp[i][0] = i;
    for (let j = 0; j <= n; j++) dp[0][j] = j;
    
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            if (str1[i - 1] === str2[j - 1]) {
                dp[i][j] = dp[i - 1][j - 1];
            } else {
                dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
            }
        }
    }
    return dp[m][n];
}

window.matchesBrand = matchesBrand;
window.identifyBrand = identifyBrand;

const STUDY_CONFIG = {
    
    
    studyId: 'SECHE_CHEVEUX_MDT_JAN2026',
    studyTitle: 'Sèche-cheveux - Test In-Home',
    studyDate: 'Mi-janvier 2026',
    reward: 'À définir',
    duration: '1 mois de test',
    
    hideHoraires: true,
    horaireMessage: 'Si vous êtes sélectionné(e), nous vous recontacterons fin janvier.',
    
    requireAccessId: true,
    
    
    objectifs: {
        totalParticipants: 50,
        
        quotas: [
            {
                id: 'type_cheveux',
                titre: '💇 Type de cheveux',
                source: 'q12',
                criteres: [
                    { valeur: 'lisses', label: 'Lisses (Type 1)', objectif: 10 },
                    { valeur: 'ondules', label: 'Ondulés (Type 2)', objectif: 15 },
                    { valeur: 'boucles', label: 'Bouclés (Type 3)', objectif: 15 },
                    { valeur: 'frises', label: 'Frisés (Type 4a)', objectif: 5 },
                    { valeur: 'crepus', label: 'Crépus (Type 4b/4c)', objectif: 5 }
                ]
            },
            
            {
                id: 'marque_multistyler',
                titre: '🔌 Marque Multistyler possédé',
                source: 'q10b',
                type: 'custom',
                extractValue: (answer) => {
                    if (!answer || !answer.values) return null;
                    const fullText = ((answer.values.marque || '') + ' ' + (answer.values.modele || '')).trim();
                    const brand = identifyBrand(fullText);
                    return 'autre_premium';
                },
                criteres: [
                    { valeur: 'dyson_airwrap', label: 'Dyson AirWrap', objectif: 12 },
                    { valeur: 'shark_flexstyle', label: 'Shark FlexStyle', objectif: 13 },
                    { valeur: 'autre_premium', label: 'Autre marque premium', objectif: 25 }
                ]
            },
            
            {
                id: 'frequence_sechage',
                titre: '🌬️ Fréquence séchage',
                source: 'q15',
                type: 'groupe',
                criteres: [
                    { 
                        valeurs: ['tous_les_jours', '5_6_fois', '3_4_fois'], 
                        label: 'Utilisatrices intensives (3-7x/sem)', 
                        objectif: 50 
                    }
                ]
            },
            
            {
                id: 'frequence_bouclage',
                titre: '🌀 Fréquence bouclage',
                source: 'q16',
                type: 'groupe',
                criteres: [
                    { 
                        valeurs: ['tous_les_jours', '5_6_fois', '3_4_fois', '1_2_fois'], 
                        label: 'Au moins 1x/semaine', 
                        objectif: 50 
                    }
                ]
            },
            
            {
                id: 'age',
                titre: "🎂 Tranche d'âge",
                source: 'q4',
                type: 'tranche',
                criteres: [
                    { min: 18, max: 30, label: '18-30 ans', objectif: null },
                    { min: 31, max: 40, label: '31-40 ans', objectif: null },
                    { min: 41, max: 55, label: '41-55 ans', objectif: null }
                ]
            },
            
            {
                id: 'longueur_cheveux',
                titre: '📏 Longueur des cheveux',
                source: 'q14',
                criteres: [
                    { valeur: 'epaules', label: "Jusqu'aux épaules", objectif: null },
                    { valeur: 'aisselles', label: "Jusqu'aux aisselles", objectif: null },
                    { valeur: 'apres_aisselles', label: 'Après les aisselles', objectif: null }
                ]
            },
            
            {
                id: 'epaisseur_cheveux',
                titre: '🧵 Épaisseur des cheveux',
                source: 'q13',
                criteres: [
                    { valeur: 'epais', label: 'Épais', objectif: null },
                    { valeur: 'moyen', label: 'Moyen', objectif: null },
                    { valeur: 'fins', label: 'Fins', objectif: null }
                ]
            }
        ]
    },
    
    
    questions: [
        
        
        {
            id: 'q1',
            title: "Secteurs d'activité",
            question: "Vous-même ou quelqu'un de votre entourage proche travaillez-vous dans l'un des secteurs suivants ?",
            type: 'multiple',
            note: `STOP si l'un des secteurs est coché (sauf "Aucun")`,
            options: [
                { value: 'publicite', label: 'Publicité', stop: true },
                { value: 'relations_publiques', label: 'Relations publiques', stop: true },
                { value: 'journalisme', label: 'Journalisme', stop: true },
                { value: 'electromenager', label: "Fabrication ou distribution d'appareils électroménagers", stop: true },
                { value: 'etudes_marche', label: 'Études de marché ou sondages', stop: true },
                { value: 'marketing', label: 'Marketing', stop: true },
                { value: 'coiffure', label: 'Salon de coiffure / École de coiffure / Esthétisme', stop: true },
                { value: 'aucun', label: 'Aucun de ces secteurs', stop: false, exclusive: true }
            ]
        },
        
        
        {
            id: 'q2',
            title: 'Participation études récentes',
            question: "Avez-vous participé à un entretien ou un groupe de discussion au cours des 12 derniers mois sur l'un des thèmes suivants ?",
            type: 'multiple',
            note: 'STOP si sèche-cheveux ou fer à boucler/brosse chauffante',
            options: [
                { value: 'seche_cheveux', label: 'Sèche-cheveux', stop: true },
                { value: 'fer_boucler', label: 'Fer à boucler / Brosse chauffante', stop: true },
                { value: 'smartphone', label: 'Smartphone', stop: false },
                { value: 'aucun', label: 'Aucun de ces thèmes', stop: false, exclusive: true }
            ]
        },
        
        
        {
            id: 'q3',
            title: 'Sexe',
            question: 'Vous êtes :',
            type: 'single',
            note: '100% FEMMES - STOP si homme ou autre',
            options: [
                { value: 'homme', label: 'Un homme', stop: true },
                { value: 'femme', label: 'Une femme', stop: false },
                { value: 'autre', label: 'Autre', stop: true }
            ]
        },
        
        {
            id: 'q4',
            title: 'Âge',
            question: 'Quel âge avez-vous ?',
            type: 'number',
            min: 18,
            max: 55,
            suffix: 'ans',
            note: 'STOP si moins de 18 ans ou plus de 55 ans',
            validation: (value) => {
                if (value < 18) {
                    return { stop: true, reason: 'Âge: moins de 18 ans' };
                }
                if (value > 55) {
                    return { stop: true, reason: 'Âge: plus de 55 ans' };
                }
                return { stop: false };
            }
        },
        
        {
            id: 'q5',
            title: 'Enfants au foyer',
            question: 'Avez-vous des enfants vivant avec vous au foyer ?',
            type: 'single',
            note: 'Information collectée - pas de quota',
            options: [
                { value: 'oui', label: 'Oui', stop: false },
                { value: 'non_partis', label: 'Non, mes enfants ont quitté le nid', stop: false },
                { value: 'non_pas_enfants', label: "Je n'ai pas d'enfants", stop: false }
            ]
        },
        
        {
            id: 'q6',
            title: 'Situation professionnelle',
            question: 'Quelle est votre situation professionnelle ?',
            type: 'single',
            note: "STOP si retraité ou recherche d'emploi",
            options: [
                { value: 'temps_plein', label: 'Travaille à temps plein', stop: false },
                { value: 'temps_partiel', label: 'Travaille à temps partiel', stop: false },
                { value: 'foyer', label: 'Au foyer', stop: false },
                { value: 'etudiant', label: 'Étudiant(e)', stop: false },
                { value: 'retraite', label: 'Retraité(e)', stop: true },
                { value: 'recherche_emploi', label: "En recherche d'emploi", stop: true }
            ]
        },
        
        {
            id: 'q6a',
            title: 'Profession',
            question: "Quelle est votre profession et dans quel secteur d'activités ?",
            type: 'double_text',
            note: 'Information collectée',
            fields: [
                { key: 'profession', label: 'Profession' },
                { key: 'secteur', label: "Secteur d'activités" }
            ]
        },
        
        {
            id: 'q6b',
            title: 'Profession du conjoint',
            question: "Quelle est la profession de votre conjoint et dans quel secteur d'activités ?",
            type: 'double_text',
            optional: true,
            note: 'Optionnel',
            fields: [
                { key: 'profession_conjoint', label: 'Profession' },
                { key: 'secteur_conjoint', label: "Secteur d'activités" }
            ]
        },
        
        {
            id: 'q7',
            title: 'Revenus annuels',
            question: 'Parmi les tranches de revenus annuels bruts suivantes, quelle est celle qui correspond à votre foyer ? (Tous salaires confondus)',
            type: 'single',
            note: 'STOP si moins de 45 000€ (objectif 60k+)',
            options: [
                { value: 'moins_25k', label: 'Moins de 25 000€ par an', stop: true },
                { value: '25k_35k', label: 'Entre 25 000 et 35 000€ par an', stop: true },
                { value: '35k_45k', label: 'Entre 35 000 et 45 000€ par an', stop: true },
                { value: '45k_50k', label: 'Entre 45 000 et 50 000€ par an', stop: false },
                { value: '50k_55k', label: 'Entre 50 000 et 55 000€ par an', stop: false },
                { value: '55k_60k', label: 'Entre 55 000 et 60 000€ par an', stop: false },
                { value: 'plus_60k', label: 'Plus de 60 000€ par an', stop: false }
            ]
        },
        
        
        {
            id: 'q8',
            title: 'Responsable achat',
            question: "Qui est responsable du choix et de l'achat de sèche-cheveux dans votre foyer ?",
            type: 'single',
            note: 'STOP si aucune décision',
            options: [
                { value: 'entierement', label: "Je suis entièrement responsable de la décision d'achat", stop: false },
                { value: 'partage', label: 'Je partage la décision avec une autre personne', stop: false },
                { value: 'aucune', label: 'Je ne prends aucune décision', stop: true }
            ]
        },
        
        
        {
            id: 'q9',
            title: 'Appareils possédés',
            question: 'Parmi les appareils électriques pour cheveux suivants, quels sont ceux que vous possédez et utilisez régulièrement à la maison ?',
            type: 'multiple',
            note: 'Critères: Multistyler Dyson AirWrap/Shark FlexStyle OK seul | Autre multistyler = besoin sèche-cheveux | Sèche-cheveux + fer à friser/boucleur = OK',
            options: [
                { value: 'seche_cheveux', label: 'Sèche-cheveux / Diffuseur sèche-cheveux', stop: false },
                { value: 'fer_lisser', label: 'Fer à lisser', stop: false },
                { value: 'fer_friser', label: 'Fer à friser', stop: false },
                { value: 'boucleur_conique', label: 'Boucleur conique', stop: false },
                { value: 'brosse_2en1', label: 'Brosse chauffante 2 en 1', stop: false },
                { value: 'multistyler', label: 'Multistyler (ex. Dyson AirWrap ou FlexStyle)', stop: false },
                { value: 'brosse_chauffante', label: 'Brosse chauffante', stop: false },
                { value: 'brosse_lissante', label: 'Brosse lissante', stop: false },
                { value: 'autre', label: 'Autre', stop: false, needsText: true, textLabel: 'Précisez' }
            ]
        },
        
        
        {
            id: 'q10a',
            title: 'Marque sèche-cheveux',
            question: 'Quelle est la marque et le modèle de votre sèche-cheveux ?',
            type: 'double_text',
            note: 'Information collectée',
            showIf: (answers) => answers.q9 && answers.q9.values && answers.q9.values.includes('seche_cheveux'),
            fields: [
                { key: 'marque', label: 'Marque', placeholder: 'Ex: Dyson, Philips, Babyliss...' },
                { key: 'modele', label: 'Modèle', placeholder: 'Ex: Supersonic, DryCare...' }
            ]
        },
        
        {
            id: 'q10b',
            title: 'Marque multistyler',
            question: 'Quelle est la marque et le modèle de votre multistyler ?',
            type: 'double_text',
            note: 'STOP si pas Dyson AirWrap ou Shark FlexStyle ET pas de sèche-cheveux.',
            showIf: (answers) => answers.q9 && answers.q9.values && answers.q9.values.includes('multistyler'),
            fields: [
                { key: 'marque', label: 'Marque', placeholder: 'Ex: Dyson, Shark, Babyliss...' },
                { key: 'modele', label: 'Modèle', placeholder: 'Ex: AirWrap, FlexStyle...' }
            ],
            customValidation: (answer, allAnswers) => {
                const hasSecheCheveux = allAnswers.q9 && allAnswers.q9.values && allAnswers.q9.values.includes('seche_cheveux');
                
                const fullText = ((answer.values?.marque || '') + ' ' + (answer.values?.modele || '')).trim();
                const identifiedBrand = window.identifyBrand ? window.identifyBrand(fullText) : identifyBrand(fullText);
                
                
                if (!isDysonAirwrap && !isSharkFlexstyle && !hasSecheCheveux) {
                    return { stop: true, reason: 'Multistyler non Dyson/Shark sans sèche-cheveux' };
                }
                return { stop: false };
            }
        },
        
        {
            id: 'q10c',
            title: 'Marque fer à friser / boucleur',
            question: 'Quelle est la marque et le modèle de votre boucleur conique / fer à friser ?',
            type: 'double_text',
            note: 'Information collectée',
            showIf: (answers) => answers.q9 && answers.q9.values && 
                (answers.q9.values.includes('fer_friser') || answers.q9.values.includes('boucleur_conique')),
            fields: [
                { key: 'marque', label: 'Marque', placeholder: 'Ex: Dyson, GHD, Babyliss...' },
                { key: 'modele', label: 'Modèle', placeholder: 'Ex: Corrale, Curve...' }
            ]
        },
        
        
        {
            id: 'q11',
            title: 'Budget sèche-cheveux',
            question: "Combien seriez-vous prête à dépenser pour votre prochain achat d'un sèche-cheveux ?",
            type: 'number',
            min: 0,
            max: 2000,
            suffix: '€',
            note: 'STOP si moins de 250€ — EXCEPTION: propriétaires Dyson AirWrap ou Shark FlexStyle (géré manuellement)',
            validation: (value) => {
                if (value < 250) {
                    return { stop: true, reason: 'Budget: moins de 250€' };
                }
                return { stop: false };
            }
        },
        
        
        {
            id: 'q12',
            title: 'Type de cheveux',
            question: 'Quel adjectif décrit le mieux vos cheveux naturels ?',
            type: 'single',
            note: 'QUOTAS: 10 lisses | 15 ondulés | 15 bouclés | 5 frisés | 5 crépus',
            image: 'types_cheveux.png',
            imageAlt: 'Guide des types de cheveux : 1 (lisse), 2a-2c (ondulé), 3a-3c (bouclé), 4a-4c (frisé/crépu)',
            options: [
                { value: 'lisses', label: 'Raides, Lisses (Type 1)', stop: false },
                { value: 'ondules', label: 'Souples, Ondulés (Type 2a, 2b, 2c)', stop: false },
                { value: 'boucles', label: 'Bouclés (Type 3a, 3b, 3c)', stop: false },
                { value: 'frises', label: 'Frisés (Type 4a)', stop: false },
                { value: 'crepus', label: 'Crépus (Type 4b, 4c)', stop: false }
            ]
        },
        
        {
            id: 'q13',
            title: 'Épaisseur des cheveux',
            question: "Qu'est-ce qui décrit le mieux l'épaisseur de vos cheveux ?",
            type: 'single',
            note: 'Répartition équilibrée souhaitée',
            options: [
                { value: 'epais', label: 'Épais', stop: false },
                { value: 'moyen', label: 'Moyen', stop: false },
                { value: 'fins', label: 'Fins', stop: false }
            ]
        },
        
        {
            id: 'q14',
            title: 'Longueur des cheveux',
            question: 'Quelle est la longueur de vos cheveux ?',
            type: 'single',
            note: 'STOP si courts/très courts. Types 3/4: au moins épaules quand étirés',
            options: [
                { value: 'courts', label: "Courts/très courts (jusqu'au menton)", stop: true },
                { value: 'epaules', label: "Jusqu'aux épaules", stop: false },
                { value: 'aisselles', label: "Jusqu'aux aisselles", stop: false },
                { value: 'apres_aisselles', label: 'Après les aisselles', stop: false }
            ]
        },
        
        
        {
            id: 'q15',
            title: 'Fréquence séchage',
            question: 'À quelle fréquence séchez-vous vos cheveux au sèche-cheveux ?',
            type: 'single',
            note: 'STOP si moins de 3x/semaine. Objectif: 50 utilisatrices intensives (3-7x/sem)',
            options: [
                { value: 'tous_les_jours', label: 'Tous les jours (7x/semaine)', stop: false },
                { value: '5_6_fois', label: '5-6 fois par semaine', stop: false },
                { value: '3_4_fois', label: '3-4 fois par semaine', stop: false },
                { value: '1_2_fois', label: '1-2 fois par semaine', stop: true },
                { value: 'moins_1_fois', label: "Moins d'1 fois par semaine", stop: true }
            ]
        },
        
        {
            id: 'q16',
            title: 'Fréquence bouclage',
            question: 'À quelle fréquence utilisez-vous votre fer à friser ou le boucleur conique ?',
            type: 'single',
            note: 'STOP si moins de 1x/semaine. Objectif: 50 au moins 1x/semaine',
            options: [
                { value: 'tous_les_jours', label: 'Tous les jours (7x/semaine)', stop: false },
                { value: '5_6_fois', label: '5-6 fois par semaine', stop: false },
                { value: '3_4_fois', label: '3-4 fois par semaine', stop: false },
                { value: '1_2_fois', label: '1-2 fois par semaine', stop: false },
                { value: 'moins_1_fois', label: "Moins d'1 fois par semaine", stop: true }
            ]
        },
        
        {
            id: 'q17',
            title: 'Utilisation pour coiffage',
            question: 'À quelle fréquence utilisez-vous votre sèche-cheveux / multistyler pour vous coiffer ?',
            type: 'single',
            note: 'Information collectée',
            options: [
                { value: 'chaque_fois', label: 'À chaque fois', stop: false },
                { value: 'parfois', label: 'Parfois', stop: false },
                { value: 'rarement', label: 'Rarement', stop: false },
                { value: 'jamais', label: 'Jamais', stop: false }
            ]
        },
        
        
        {
            id: 'q18',
            title: 'Intérêt multistyler',
            question: "Êtes-vous intéressée par l'achat d'un multistyler (ex. Dyson AirWrap ou FlexStyle) ?",
            type: 'single',
            note: 'STOP si non intéressée',
            showIf: (answers) => !answers.q9 || !answers.q9.values || !answers.q9.values.includes('multistyler'),
            options: [
                { value: 'oui', label: 'Oui, je suis intéressée', stop: false },
                { value: 'non', label: 'Non, pas intéressée', stop: true }
            ]
        },
        
        
        {
            id: 'q19',
            title: 'Utilisateurs sèche-cheveux au foyer',
            question: 'Y compris vous-même, combien de personnes au sein de votre foyer utilisent le sèche-cheveux ?',
            type: 'single',
            note: "Objectif: maximum de foyers avec plus d'1 utilisateur",
            options: [
                { value: '1_personne', label: '1 personne', stop: false },
                { value: '2_personnes', label: '2 personnes', stop: false },
                { value: '3_personnes', label: '3 personnes', stop: false },
                { value: '4_plus', label: '4 personnes et +', stop: false }
            ]
        },
        
        
        {
            id: 'q20',
            title: 'Photo de vos appareils',
            question: 'Merci de prendre une photo de vos appareils (sèche-cheveux, multistyler, fer à friser...)',
            type: 'file',
            accept: 'image/*',
            optional: false,
            note: 'Photo obligatoire pour vérification'
        },
        
        {
            id: 'q21',
            title: 'Photo de vos cheveux',
            question: 'Merci de prendre une photo de vos cheveux (pour vérifier le type et la longueur)',
            type: 'file',
            accept: 'image/*',
            optional: false,
            note: 'Photo obligatoire pour vérification du type de cheveux'
        }
    ]
};
