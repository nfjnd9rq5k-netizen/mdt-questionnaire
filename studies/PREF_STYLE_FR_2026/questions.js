/**
 * ============================================================
 * QUESTIONNAIRE : PRÉFÉRENCES DE STYLE & TON
 * ============================================================
 * 
 * Objectif : Collecter des données de préférence pour entraîner
 * des modèles IA à adapter leur style au contexte français.
 * 
 * Samples générés par participant : ~40
 * Durée estimée : 20-25 minutes
 * 
 * Structure :
 * - P0 : Démographie & profil
 * - P1 : Comparaisons A/B (ton formel vs casual)
 * - P2 : Comparaisons A/B (longueur & structure)
 * - P3 : Évaluation de réponses IA
 * - P4 : Reformulations naturelles
 * - P5 : Préférences explicites
 * - P6 : Feedback
 */

const STUDY_CONFIG = {
    studyId: 'PREF_STYLE_FR_2026',
    studyTitle: 'Comment préférez-vous qu\'une IA vous parle ?',
    studyDate: 'Janvier 2026',
    status: 'active',
    
    // Mode anonyme pour maximiser la participation
    anonymousMode: true,
    hideHoraires: true,
    
    // Tracking comportemental
    enableBehaviorTracking: true,
    
    // Messages
    welcomeMessage: `
        <h2>Bienvenue !</h2>
        <p>Ce questionnaire nous aide à comprendre <strong>comment les Français préfèrent interagir avec une IA</strong>.</p>
        <p>Vos réponses contribueront à rendre les assistants IA plus naturels et adaptés à la culture française.</p>
        <p><strong>Durée :</strong> environ 20-25 minutes</p>
        <div style="background: #fef3c7; padding: 12px; border-radius: 8px; margin-top: 16px;">
            <strong>🎯 Important :</strong> Il n'y a pas de bonnes ou mauvaises réponses. 
            Nous voulons connaître VOS vraies préférences.
        </div>
        <div style="background: #e0e7ff; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 13px;">
            <strong>🔒 Protection des données :</strong> Vos réponses sont anonymisées et traitées conformément au RGPD. 
            Vous pouvez exercer vos droits à tout moment (voir conditions sur la page suivante).
        </div>
    `,
    
    endMessage: `
        <h2>Merci beaucoup ! 🙏</h2>
        <p>Vos réponses sont précieuses pour améliorer les IA francophones.</p>
        <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 13px;">
            <strong>📧 Exercer vos droits :</strong> Pour toute demande concernant vos données (accès, rectification, suppression), 
            contactez-nous à : <strong>ademnasri@lamaisondutest.com</strong> en indiquant votre identifiant participant.
        </div>
    `,
    
    questions: [
        // ============================================================
        // CONSENTEMENT RGPD - OBLIGATOIRE
        // ============================================================
        {
            id: 'rgpd_consent',
            type: 'multiple',
            title: '📋 Consentement et protection des données',
            text: `
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; line-height: 1.6;">
                    <p><strong>Responsable du traitement :</strong> La Maison du Test</p>
                    <p><strong>Finalité :</strong> Vos réponses seront utilisées pour entraîner et améliorer des modèles d'intelligence artificielle. Les données collectées pourront être partagées avec des partenaires tiers (entreprises tech, laboratoires de recherche) sous forme anonymisée.</p>
                    <p><strong>Données collectées :</strong> Réponses aux questions, données démographiques (âge, région), métriques de qualité (temps de réponse, cohérence). <strong>Aucune donnée personnelle identifiante</strong> (nom, email, téléphone) n'est collectée.</p>
                    <p><strong>Conservation :</strong> Vos données anonymisées sont conservées sans limite de durée à des fins de recherche et d'amélioration des IA.</p>
                    <p><strong>Vos droits (RGPD) :</strong> Vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Vous pouvez également vous opposer au traitement ou demander sa limitation. Pour exercer ces droits, contactez : <strong>ademnasri@lamaisondutest.com</strong></p>
                    <p><strong>Retrait du consentement :</strong> Vous pouvez retirer votre consentement à tout moment en nous contactant avec votre identifiant participant (affiché en fin de questionnaire). Les données déjà intégrées dans des modèles entraînés ne pourront pas être supprimées.</p>
                </div>
            `,
            required: true,
            minRequired: 1,
            options: [
                { 
                    value: 'consent_accepted', 
                    label: "J'accepte que mes réponses anonymisées soient utilisées pour l'entraînement d'IA et potentiellement partagées avec des tiers",
                    stop: false
                }
            ],
            stopIfEmpty: true,
            stopReason: 'Consentement non donné'
        },

        // ============================================================
        // PARTIE 0 : DÉMOGRAPHIE & PROFIL
        // ============================================================
        {
            id: 'p0_intro',
            type: 'info',
            title: 'Partie 1/6 : Votre profil',
            text: `
                <p>Quelques questions pour mieux vous connaître.</p>
                <p>Ces informations nous aident à analyser les préférences selon les profils.</p>
            `
        },
        {
            id: 'p0_age',
            type: 'radio',
            title: 'Quelle est votre tranche d\'âge ?',
            required: true,
            options: [
                { value: '18-24', label: '18-24 ans' },
                { value: '25-34', label: '25-34 ans' },
                { value: '35-44', label: '35-44 ans' },
                { value: '45-54', label: '45-54 ans' },
                { value: '55-64', label: '55-64 ans' },
                { value: '65+', label: '65 ans et plus' }
            ]
        },
        {
            id: 'p0_genre',
            type: 'radio',
            title: 'Quel est votre genre ?',
            required: true,
            options: [
                { value: 'homme', label: 'Homme' },
                { value: 'femme', label: 'Femme' },
                { value: 'autre', label: 'Autre / Ne souhaite pas répondre' }
            ]
        },
        {
            id: 'p0_region',
            type: 'radio',
            title: 'Dans quelle région habitez-vous ?',
            required: true,
            options: [
                { value: 'idf', label: 'Île-de-France' },
                { value: 'nord', label: 'Nord (Hauts-de-France)' },
                { value: 'est', label: 'Est (Grand Est)' },
                { value: 'ouest', label: 'Ouest (Bretagne, Pays de la Loire)' },
                { value: 'sud_ouest', label: 'Sud-Ouest (Nouvelle-Aquitaine, Occitanie ouest)' },
                { value: 'sud_est', label: 'Sud-Est (PACA, Occitanie est, Auvergne-Rhône-Alpes)' },
                { value: 'centre', label: 'Centre (Centre-Val de Loire, Bourgogne-Franche-Comté)' },
                { value: 'normandie', label: 'Normandie' },
                { value: 'outre_mer', label: 'Outre-mer' },
                { value: 'etranger', label: 'Étranger francophone' }
            ]
        },
        {
            id: 'p0_education',
            type: 'radio',
            title: 'Quel est votre niveau d\'études ?',
            required: true,
            options: [
                { value: 'brevet', label: 'Brevet des collèges ou moins' },
                { value: 'cap_bep', label: 'CAP / BEP' },
                { value: 'bac', label: 'Baccalauréat' },
                { value: 'bac+2', label: 'Bac+2 (BTS, DUT, DEUG)' },
                { value: 'bac+3', label: 'Bac+3 (Licence)' },
                { value: 'bac+5', label: 'Bac+5 (Master, École d\'ingénieur, École de commerce)' },
                { value: 'bac+8', label: 'Bac+8 (Doctorat)' }
            ]
        },
        {
            id: 'p0_profession',
            type: 'radio',
            title: 'Quelle est votre situation professionnelle ?',
            required: true,
            options: [
                { value: 'etudiant', label: 'Étudiant(e)' },
                { value: 'employe', label: 'Employé(e) / Ouvrier(ère)' },
                { value: 'cadre', label: 'Cadre / Profession intellectuelle' },
                { value: 'independant', label: 'Indépendant(e) / Freelance' },
                { value: 'chef_entreprise', label: 'Chef d\'entreprise' },
                { value: 'fonctionnaire', label: 'Fonctionnaire' },
                { value: 'retraite', label: 'Retraité(e)' },
                { value: 'recherche_emploi', label: 'En recherche d\'emploi' },
                { value: 'autre', label: 'Autre' }
            ]
        },
        {
            id: 'p0_ia_usage',
            type: 'radio',
            title: 'À quelle fréquence utilisez-vous des assistants IA (ChatGPT, Claude, etc.) ?',
            required: true,
            options: [
                { value: 'quotidien', label: 'Tous les jours' },
                { value: 'hebdo', label: 'Plusieurs fois par semaine' },
                { value: 'mensuel', label: 'Plusieurs fois par mois' },
                { value: 'rarement', label: 'Rarement (quelques fois par an)' },
                { value: 'jamais', label: 'Jamais utilisé' }
            ]
        },
        
        // ============================================================
        // PARTIE 1 : COMPARAISONS A/B - TON (Formel vs Casual)
        // ============================================================
        {
            id: 'p1_intro',
            type: 'info',
            title: 'Partie 2/6 : Comparaisons de style',
            text: `
                <p>Vous allez voir des <strong>paires de réponses</strong> à une même question.</p>
                <p>Choisissez celle que vous préférez et expliquez pourquoi.</p>
                <div style="background: #dbeafe; padding: 12px; border-radius: 8px; margin-top: 12px;">
                    💡 <strong>Conseil :</strong> Imaginez-vous vraiment dans la situation décrite.
                </div>
            `
        },
        
        // Comparaison 1 : Email au manager (formel vs casual)
        {
            id: 'p1_comp1_context',
            type: 'info',
            title: 'Situation 1 : Demande de télétravail',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous demandez à une IA de vous aider à rédiger un email 
                    à votre manager pour demander à faire du télétravail le vendredi.</p>
                </div>
            `
        },
        {
            id: 'p1_comp1_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p>Objet : Demande de télétravail le vendredi</p>
                        <p>Bonjour [Prénom du manager],</p>
                        <p>Je me permets de vous solliciter concernant la possibilité d'effectuer mes missions en télétravail le vendredi. Cette organisation me permettrait d'optimiser ma productivité tout en réduisant mon temps de transport.</p>
                        <p>Je reste bien entendu disponible pour en discuter à votre convenance et m'adapter aux besoins de l'équipe.</p>
                        <p>Cordialement,<br>[Votre nom]</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Objet : Télétravail le vendredi ?</p>
                        <p>Salut [Prénom],</p>
                        <p>Je voulais te demander si ce serait possible de bosser de chez moi le vendredi ? Ça m'arrangerait bien pour éviter les bouchons et franchement je suis souvent plus efficace au calme chez moi.</p>
                        <p>Dis-moi ce que t'en penses, on peut en parler quand tu veux !</p>
                        <p>Merci d'avance</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'tone',
                variable_tested: 'formal_vs_casual',
                domain: 'professional',
                response_a_style: 'formal',
                response_b_style: 'casual'
            }
        },
        {
            id: 'p1_comp1_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ? (plusieurs choix possibles)',
            required: true,
            options: [
                { value: 'ton_adapte', label: 'Le ton est plus adapté à la situation' },
                { value: 'plus_naturel', label: 'Ça semble plus naturel' },
                { value: 'plus_efficace', label: 'Ça sera plus efficace pour obtenir ce que je veux' },
                { value: 'plus_respectueux', label: 'C\'est plus respectueux / professionnel' },
                { value: 'me_ressemble', label: 'Ça me ressemble plus' },
                { value: 'relation_manager', label: 'Ça correspond à ma relation avec mon manager' }
            ]
        },
        {
            id: 'p1_comp1_relation',
            type: 'radio',
            title: 'Dans VOTRE travail, comment vous adressez-vous généralement à votre supérieur ?',
            required: true,
            options: [
                { value: 'tres_formel', label: 'Très formellement (vouvoiement, formules de politesse)' },
                { value: 'formel', label: 'Formellement mais cordial' },
                { value: 'mixte', label: 'Ça dépend du sujet et du contexte' },
                { value: 'decontracte', label: 'De manière décontractée (tutoiement)' },
                { value: 'na', label: 'Non applicable (pas de supérieur)' }
            ]
        },
        
        // Comparaison 2 : Conseil médical (empathique vs factuel)
        {
            id: 'p1_comp2_context',
            type: 'info',
            title: 'Situation 2 : Inquiétude santé',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous dites à une IA que vous avez des maux de tête fréquents 
                    depuis une semaine et vous demandez ce que ça pourrait être.</p>
                </div>
            `
        },
        {
            id: 'p1_comp2_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p>Je comprends que ça doit être désagréable d'avoir ces maux de tête depuis une semaine. C'est normal de s'inquiéter.</p>
                        <p>Les causes les plus courantes sont souvent bénignes : stress, fatigue, déshydratation, trop de temps devant les écrans, ou tensions musculaires au niveau du cou.</p>
                        <p>Quelques questions pour y voir plus clair : Est-ce que tu dors suffisamment ? Tu bois assez d'eau ? Tu as été particulièrement stressé(e) ces derniers temps ?</p>
                        <p>Si ça persiste ou si tu as d'autres symptômes (fièvre, troubles de la vision), je te conseille vraiment de consulter un médecin pour être rassuré(e).</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Les maux de tête fréquents peuvent avoir plusieurs causes :</p>
                        <p><strong>Causes courantes :</strong></p>
                        <ul>
                            <li>Céphalées de tension (stress, posture)</li>
                            <li>Migraines</li>
                            <li>Déshydratation</li>
                            <li>Fatigue visuelle (écrans)</li>
                            <li>Manque de sommeil</li>
                        </ul>
                        <p><strong>Signaux d'alerte nécessitant une consultation :</strong></p>
                        <ul>
                            <li>Maux de tête soudains et intenses</li>
                            <li>Fièvre associée</li>
                            <li>Troubles neurologiques (vision, équilibre)</li>
                        </ul>
                        <p>Recommandation : consulter un médecin si les symptômes persistent au-delà de 2 semaines.</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'tone',
                variable_tested: 'empathetic_vs_factual',
                domain: 'health',
                response_a_style: 'empathetic_conversational',
                response_b_style: 'factual_structured'
            }
        },
        {
            id: 'p1_comp2_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'rassurant', label: 'C\'est plus rassurant' },
                { value: 'complet', label: 'C\'est plus complet' },
                { value: 'clair', label: 'C\'est plus clair et organisé' },
                { value: 'humain', label: 'Ça semble plus humain' },
                { value: 'utile', label: 'C\'est plus utile concrètement' },
                { value: 'pas_condescendant', label: 'Ce n\'est pas condescendant' }
            ]
        },

        // Comparaison 3 : Réclamation commerce (direct vs diplomatique)
        {
            id: 'p1_comp3_context',
            type: 'info',
            title: 'Situation 3 : Réclamation colis',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Votre colis est arrivé abîmé. Vous demandez à une IA 
                    de vous aider à rédiger un message au service client.</p>
                </div>
            `
        },
        {
            id: 'p1_comp3_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p>Bonjour,</p>
                        <p>J'ai reçu ma commande n°[XXX] ce jour et malheureusement le colis est arrivé très abîmé, ce qui a endommagé le produit à l'intérieur.</p>
                        <p>Je souhaite obtenir un remboursement ou un renvoi du produit en bon état.</p>
                        <p>Vous trouverez ci-joint les photos du colis et du produit endommagé.</p>
                        <p>Merci de traiter ma demande rapidement.</p>
                        <p>Cordialement</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Bonjour,</p>
                        <p>Je me permets de vous contacter suite à la réception de ma commande n°[XXX].</p>
                        <p>Malheureusement, j'ai eu la mauvaise surprise de constater que le colis était arrivé dans un état déplorable, et que le produit commandé était par conséquent endommagé. Je suis vraiment déçu(e) car j'attendais cette commande avec impatience.</p>
                        <p>Je me doute que ce n'est pas de votre faute directement et que ces choses arrivent pendant le transport, mais je souhaiterais savoir s'il serait possible d'obtenir soit un remboursement, soit un nouvel envoi ?</p>
                        <p>Je joins les photos pour que vous puissiez constater les dégâts.</p>
                        <p>En vous remerciant par avance pour votre compréhension et votre aide.</p>
                        <p>Bien cordialement</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'tone',
                variable_tested: 'direct_vs_diplomatic',
                domain: 'customer_service',
                response_a_style: 'direct_assertive',
                response_b_style: 'diplomatic_understanding'
            }
        },
        {
            id: 'p1_comp3_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'efficace', label: 'Plus efficace pour obtenir satisfaction' },
                { value: 'poli', label: 'Plus poli et respectueux' },
                { value: 'direct', label: 'Va droit au but' },
                { value: 'comprehensif', label: 'Montre de la compréhension' },
                { value: 'professionnel', label: 'Plus professionnel' },
                { value: 'naturel', label: 'Plus naturel / me ressemble' }
            ]
        },

        // Comparaison 4 : Conseil parental (expert vs pair)
        {
            id: 'p1_comp4_context',
            type: 'info',
            title: 'Situation 4 : Problème avec un ado',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Votre ado de 14 ans passe trop de temps sur son téléphone 
                    et ça crée des tensions. Vous demandez conseil à une IA.</p>
                </div>
            `
        },
        {
            id: 'p1_comp4_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p>La gestion du temps d'écran chez les adolescents est effectivement un défi fréquent. Voici quelques recommandations basées sur les études en psychologie de l'adolescent :</p>
                        <p><strong>1. Établir un cadre clair :</strong> Définissez des règles précises (pas de téléphone à table, couvre-feu numérique à 21h, etc.)</p>
                        <p><strong>2. Impliquer l'adolescent :</strong> Les règles imposées sans discussion sont généralement contre-productives à cet âge. Négociez ensemble.</p>
                        <p><strong>3. Montrer l'exemple :</strong> Votre propre usage du téléphone influence son comportement.</p>
                        <p><strong>4. Proposer des alternatives :</strong> Activités sportives, sorties en famille, etc.</p>
                        <p>Note : Un temps d'écran excessif peut cacher un mal-être. Restez attentif à son état émotionnel général.</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Ah, le téléphone et les ados... un grand classique ! Tu n'es vraiment pas seul(e) dans cette situation.</p>
                        <p>Ce qui marche souvent, c'est d'éviter le rapport de force frontal. Genre lui confisquer le tel sans prévenir, c'est la guerre assurée 😅</p>
                        <p>Essaie plutôt d'en discuter avec lui/elle dans un moment calme : "Je vois que t'es beaucoup sur ton tel, qu'est-ce qui t'intéresse autant dessus ?" Parfois on découvre des trucs (il discute avec ses potes, il regarde des vidéos qui le passionnent...).</p>
                        <p>Après, poser des limites c'est normal et nécessaire. Mais c'est mieux si c'est négocié ensemble. "OK pour le tel jusqu'à 21h en semaine, mais tu lâches pendant les repas, deal ?"</p>
                        <p>Et honnêtement, check aussi ton propre usage du tel devant lui... ils nous observent ces petits malins 😉</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'tone',
                variable_tested: 'expert_vs_peer',
                domain: 'parenting',
                response_a_style: 'expert_structured',
                response_b_style: 'peer_conversational'
            }
        },
        {
            id: 'p1_comp4_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'pratique', label: 'Plus pratique et applicable' },
                { value: 'comprehensif', label: 'Ça montre qu\'on me comprend' },
                { value: 'serieux', label: 'Plus sérieux et crédible' },
                { value: 'chaleureux', label: 'Plus chaleureux et humain' },
                { value: 'complet', label: 'Plus complet' },
                { value: 'realiste', label: 'Plus réaliste' }
            ]
        },

        // Attention check 1
        {
            id: 'p1_attention_check',
            type: 'radio',
            title: '⚠️ Question de vérification : Pour montrer que vous lisez attentivement, sélectionnez "Orange"',
            required: true,
            options: [
                { value: 'bleu', label: 'Bleu' },
                { value: 'orange', label: 'Orange' },
                { value: 'vert', label: 'Vert' },
                { value: 'rouge', label: 'Rouge' }
            ],
            metadata: { is_attention_check: true, correct_answer: 'orange' }
        },

        // Comparaison 5 : Explication technique (vulgarisé vs technique)
        {
            id: 'p1_comp5_context',
            type: 'info',
            title: 'Situation 5 : Comprendre un terme technique',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous demandez à une IA d'expliquer ce qu'est le "cloud computing".</p>
                </div>
            `
        },
        {
            id: 'p1_comp5_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p>Le cloud computing, c'est un peu comme louer un espace de stockage ou de la puissance informatique sur internet, plutôt que d'acheter ton propre équipement.</p>
                        <p><strong>Une analogie simple :</strong> Imagine que tes fichiers et tes programmes sont dans un coffre-fort géant accessible depuis n'importe où, plutôt que dans un coffre chez toi.</p>
                        <p><strong>Exemples concrets que tu utilises peut-être déjà :</strong></p>
                        <ul>
                            <li>Google Drive / iCloud pour tes photos</li>
                            <li>Netflix (les films sont sur leurs serveurs, pas ton ordi)</li>
                            <li>Gmail (tes emails sont stockés chez Google)</li>
                        </ul>
                        <p>L'avantage ? Tu peux y accéder de ton téléphone, ton ordi, n'importe où. Et si ton ordi plante, tes données sont en sécurité.</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Le cloud computing désigne la mise à disposition de ressources informatiques (serveurs, stockage, bases de données, réseau, logiciels) via Internet, selon un modèle de paiement à l'usage.</p>
                        <p><strong>Les trois principaux modèles de service :</strong></p>
                        <ul>
                            <li><strong>IaaS</strong> (Infrastructure as a Service) : infrastructure virtualisée (ex: AWS EC2, Azure VM)</li>
                            <li><strong>PaaS</strong> (Platform as a Service) : environnement de développement (ex: Heroku, Google App Engine)</li>
                            <li><strong>SaaS</strong> (Software as a Service) : applications accessibles via navigateur (ex: Office 365, Salesforce)</li>
                        </ul>
                        <p><strong>Caractéristiques clés :</strong> élasticité, scalabilité, mutualisation des ressources, facturation à l'usage.</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'complexity',
                variable_tested: 'simplified_vs_technical',
                domain: 'technology',
                response_a_style: 'simplified_analogies',
                response_b_style: 'technical_comprehensive'
            }
        },
        {
            id: 'p1_comp5_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'comprehensible', label: 'Plus facile à comprendre' },
                { value: 'complet', label: 'Plus complet et précis' },
                { value: 'exemples', label: 'Les exemples m\'aident' },
                { value: 'niveau_adapte', label: 'Adapté à mon niveau' },
                { value: 'pas_infantilisant', label: 'Ne me prend pas pour un idiot' },
                { value: 'actionnable', label: 'Je sais quoi faire avec cette info' }
            ]
        },
        {
            id: 'p1_comp5_tech_level',
            type: 'radio',
            title: 'Comment évaluez-vous votre niveau en informatique / technologie ?',
            required: true,
            options: [
                { value: 'debutant', label: 'Débutant (j\'utilise les bases)' },
                { value: 'intermediaire', label: 'Intermédiaire (à l\'aise avec la plupart des outils)' },
                { value: 'avance', label: 'Avancé (je comprends les concepts techniques)' },
                { value: 'expert', label: 'Expert (je travaille dans le domaine)' }
            ]
        },

        // ============================================================
        // PARTIE 2 : COMPARAISONS A/B - LONGUEUR & STRUCTURE
        // ============================================================
        {
            id: 'p2_intro',
            type: 'info',
            title: 'Partie 3/6 : Longueur et format',
            text: `
                <p>Maintenant, nous allons nous intéresser à la <strong>longueur</strong> et au <strong>format</strong> des réponses.</p>
            `
        },

        // Comparaison 6 : Recette (concis vs détaillé)
        {
            id: 'p2_comp6_context',
            type: 'info',
            title: 'Situation 6 : Recette de cuisine',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous demandez une recette simple de pâtes à la carbonara.</p>
                </div>
            `
        },
        {
            id: 'p2_comp6_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A (concise)',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p><strong>Carbonara express (2 pers.)</strong></p>
                        <p>200g spaghetti, 150g lardons, 2 jaunes d'œuf, 50g parmesan, poivre.</p>
                        <p>Cuire les pâtes. Faire revenir les lardons. Mélanger jaunes + parmesan. Hors du feu, tout mélanger. Poivrer. C'est prêt !</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B (détaillée)',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p><strong>Vraie carbonara italienne (2 personnes)</strong></p>
                        <p><em>Temps : 20 min | Difficulté : Facile</em></p>
                        <p><strong>Ingrédients :</strong></p>
                        <ul>
                            <li>200g de spaghetti (ou rigatoni)</li>
                            <li>150g de guanciale (ou à défaut, pancetta ou lardons)</li>
                            <li>2 jaunes d'œuf + 1 œuf entier</li>
                            <li>50g de pecorino romano râpé (ou parmesan)</li>
                            <li>Poivre noir fraîchement moulu</li>
                        </ul>
                        <p><strong>Étapes :</strong></p>
                        <ol>
                            <li>Mettre l'eau des pâtes à bouillir (saler généreusement).</li>
                            <li>Pendant ce temps, couper le guanciale en petits morceaux et le faire revenir à la poêle sans matière grasse jusqu'à ce qu'il soit doré et croustillant (5-7 min).</li>
                            <li>Dans un bol, mélanger les jaunes + œuf entier avec le pecorino. Poivrer généreusement.</li>
                            <li>Cuire les pâtes al dente (1 min de moins que le temps indiqué).</li>
                            <li>⚠️ <strong>Point crucial :</strong> Égoutter les pâtes en gardant un peu d'eau de cuisson.</li>
                            <li>Mettre les pâtes dans la poêle avec les lardons HORS DU FEU.</li>
                            <li>Verser le mélange œuf-fromage et remuer vigoureusement. La chaleur résiduelle va créer la sauce crémeuse. Si c'est trop épais, ajouter un peu d'eau de cuisson.</li>
                        </ol>
                        <p><strong>💡 Astuce :</strong> Ne JAMAIS mettre la sauce sur le feu direct, sinon vous aurez des œufs brouillés !</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'length',
                variable_tested: 'concise_vs_detailed',
                domain: 'cooking',
                response_a_style: 'minimal',
                response_b_style: 'comprehensive'
            }
        },
        {
            id: 'p2_comp6_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'rapide', label: 'Je veux l\'info rapidement' },
                { value: 'details', label: 'J\'aime avoir tous les détails' },
                { value: 'astuces', label: 'Les astuces m\'aident' },
                { value: 'niveau', label: 'Adapté à mon niveau en cuisine' },
                { value: 'pratique', label: 'Plus pratique à suivre en cuisinant' },
                { value: 'confiance', label: 'Je me sens plus en confiance' }
            ]
        },
        {
            id: 'p2_comp6_cuisine_level',
            type: 'radio',
            title: 'Comment évaluez-vous votre niveau en cuisine ?',
            required: true,
            options: [
                { value: 'debutant', label: 'Débutant (je suis les recettes à la lettre)' },
                { value: 'intermediaire', label: 'Intermédiaire (je me débrouille bien)' },
                { value: 'avance', label: 'Avancé (je cuisine souvent et j\'improvise)' },
                { value: 'expert', label: 'Expert / Passionné' }
            ]
        },

        // Comparaison 7 : Conseil voyage (liste vs prose)
        {
            id: 'p2_comp7_context',
            type: 'info',
            title: 'Situation 7 : Conseils de voyage',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous partez 4 jours à Lisbonne et demandez des conseils sur quoi voir.</p>
                </div>
            `
        },
        {
            id: 'p2_comp7_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A (liste structurée)',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p><strong>Lisbonne en 4 jours - Les incontournables :</strong></p>
                        <p><strong>Quartiers :</strong></p>
                        <ul>
                            <li>Alfama (vieux quartier, fado)</li>
                            <li>Belém (monuments, pastéis)</li>
                            <li>Bairro Alto (vie nocturne)</li>
                            <li>LX Factory (brunch, street art)</li>
                        </ul>
                        <p><strong>À voir absolument :</strong></p>
                        <ul>
                            <li>Tour de Belém</li>
                            <li>Monastère des Hiéronymites</li>
                            <li>Tram 28</li>
                            <li>Miradouro da Senhora do Monte (coucher de soleil)</li>
                        </ul>
                        <p><strong>À manger :</strong></p>
                        <ul>
                            <li>Pastéis de Belém (LA référence)</li>
                            <li>Bifana (sandwich porc)</li>
                            <li>Bacalhau (morue)</li>
                        </ul>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B (récit fluide)',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Lisbonne en 4 jours, tu vas te régaler ! C'est une ville qui se découvre surtout en se baladant.</p>
                        <p>Commence par te perdre dans l'Alfama, le vieux quartier aux ruelles escarpées où tu entendras peut-être du fado s'échapper des fenêtres. Prends le mythique tram 28 au moins une fois (tôt le matin pour éviter la foule !).</p>
                        <p>Consacre une matinée à Belém pour les monuments - le monastère des Hiéronymites est vraiment impressionnant - et surtout pour goûter les fameux pastéis de nata à la pâtisserie originale. Arrive avant 10h sinon c'est la queue !</p>
                        <p>Pour les couchers de soleil, monte au Miradouro da Senhora do Monte avec une bière, c'est magique. Et le soir, direction Bairro Alto pour l'ambiance - les gens boivent dans la rue, c'est très convivial.</p>
                        <p>Mon conseil bonus : va bruncher à LX Factory le dimanche, une ancienne usine transformée en lieu branché. Vraiment cool !</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'structure',
                variable_tested: 'list_vs_prose',
                domain: 'travel',
                response_a_style: 'structured_list',
                response_b_style: 'narrative_prose'
            }
        },
        {
            id: 'p2_comp7_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'facile_reference', label: 'Plus facile à consulter sur place' },
                { value: 'agreable_lire', label: 'Plus agréable à lire' },
                { value: 'inspire', label: 'Ça m\'inspire plus / me donne envie' },
                { value: 'pratique', label: 'Plus pratique pour planifier' },
                { value: 'conseils_perso', label: 'Les conseils personnels m\'aident' },
                { value: 'complet', label: 'Plus complet' }
            ]
        },

        // Comparaison 8 : Explication administrative (étapes vs global)
        {
            id: 'p2_comp8_context',
            type: 'info',
            title: 'Situation 8 : Démarche administrative',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Contexte :</strong> Vous demandez comment faire pour renouveler votre carte d'identité.</p>
                </div>
            `
        },
        {
            id: 'p2_comp8_choix',
            type: 'radio',
            title: 'Quelle réponse préférez-vous ?',
            required: true,
            options: [
                { 
                    value: 'A', 
                    label: 'Réponse A (étapes numérotées)',
                    description: `<div class="response-option response-a">
                        <h4>Réponse A</h4>
                        <p><strong>Renouveler sa carte d'identité - Étape par étape :</strong></p>
                        <ol>
                            <li><strong>Faire la pré-demande en ligne</strong> sur ants.gouv.fr (créer un compte France Connect si pas déjà fait) - Durée : 10 min</li>
                            <li><strong>Préparer les documents :</strong>
                                <ul>
                                    <li>Ancienne carte d'identité</li>
                                    <li>Photo d'identité récente (- de 6 mois)</li>
                                    <li>Justificatif de domicile (- de 1 an)</li>
                                    <li>Numéro de pré-demande</li>
                                </ul>
                            </li>
                            <li><strong>Prendre RDV en mairie</strong> équipée d'une station biométrique (pas toutes !)</li>
                            <li><strong>Se déplacer au RDV</strong> avec tous les documents</li>
                            <li><strong>Récupérer la carte</strong> en mairie (délai : 2 à 4 semaines selon période)</li>
                        </ol>
                        <p><strong>⚠️ À savoir :</strong> C'est gratuit si vous présentez l'ancienne carte. Sinon 25€ de timbre fiscal.</p>
                    </div>`
                },
                { 
                    value: 'B', 
                    label: 'Réponse B (explication globale)',
                    description: `<div class="response-option response-b">
                        <h4>Réponse B</h4>
                        <p>Pour renouveler ta carte d'identité, tout se passe maintenant en ligne d'abord, puis en mairie.</p>
                        <p>Concrètement, tu fais ta pré-demande sur le site de l'ANTS (c'est le site officiel des titres sécurisés). Tu crées un compte via France Connect si t'en as pas, et tu remplis le formulaire. Garde bien le numéro de pré-demande qu'on te donne à la fin.</p>
                        <p>Ensuite tu prends rendez-vous dans une mairie qui a le matériel biométrique (attention, c'est pas toutes les mairies, vérifie sur le site). Le jour J, tu y vas avec ton ancienne carte, une photo récente, un justificatif de domicile et ton numéro de pré-demande.</p>
                        <p>Après, il faut compter entre 2 et 4 semaines pour recevoir la nouvelle carte, parfois plus en période chargée (avant les vacances d'été par exemple). Tu retournes la chercher en mairie avec un SMS ou mail de confirmation.</p>
                        <p>Bonne nouvelle : c'est gratuit si tu présentes ton ancienne carte, même périmée !</p>
                    </div>`
                }
            ],
            metadata: {
                comparison_type: 'structure',
                variable_tested: 'steps_vs_explanation',
                domain: 'administrative',
                response_a_style: 'numbered_steps',
                response_b_style: 'flowing_explanation'
            }
        },
        {
            id: 'p2_comp8_raison',
            type: 'multiple',
            title: 'Pourquoi avez-vous choisi cette réponse ?',
            required: true,
            options: [
                { value: 'clair', label: 'Plus clair et facile à suivre' },
                { value: 'checklist', label: 'Je peux l\'utiliser comme checklist' },
                { value: 'contexte', label: 'Le contexte m\'aide à comprendre' },
                { value: 'rassure', label: 'Ça me rassure sur la démarche' },
                { value: 'complet', label: 'Toutes les infos importantes sont là' },
                { value: 'lisible', label: 'Plus agréable à lire' }
            ]
        },

        // ============================================================
        // PARTIE 3 : ÉVALUATION DE RÉPONSES IA
        // ============================================================
        {
            id: 'p3_intro',
            type: 'info',
            title: 'Partie 4/6 : Évaluation de réponses',
            text: `
                <p>Vous allez maintenant <strong>noter des réponses</strong> d'IA sur plusieurs critères.</p>
                <p>Imaginez que vous avez posé la question et que l'IA vous répond.</p>
            `
        },

        // Évaluation 1 : Réponse à "Comment gérer mon stress ?"
        {
            id: 'p3_eval1_context',
            type: 'info',
            title: 'Réponse à évaluer #1',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Question posée :</strong> "Je suis stressé en ce moment, comment je peux me détendre ?"</p>
                    <blockquote>
                        <p><strong>Réponse de l'IA :</strong></p>
                        <p>"Je comprends, le stress c'est vraiment pas agréable. Quelques trucs qui marchent bien :</p>
                        <ul>
                            <li><strong>Respiration :</strong> Inspire 4 secondes, bloque 4 secondes, expire 4 secondes. Répète 5 fois, ça calme vraiment.</li>
                            <li><strong>Bouger :</strong> Même 10 min de marche ça aide. L'exercice évacue les tensions.</li>
                            <li><strong>Déconnecter :</strong> 30 min sans téléphone/écrans, ça repose le cerveau.</li>
                            <li><strong>En parler :</strong> Parfois juste exprimer ce qui nous stresse soulage.</li>
                        </ul>
                        <p>C'est passager ou tu te sens stressé depuis longtemps ? Je peux te donner des conseils plus adaptés si tu veux."</p>
                    </blockquote>
                </div>
            `
        },
        {
            id: 'p3_eval1_clarte',
            type: 'radio',
            title: 'Clarté : La réponse est-elle claire et facile à comprendre ?',
            required: true,
            options: [
                { value: '1', label: '1 - Pas du tout claire' },
                { value: '2', label: '2 - Peu claire' },
                { value: '3', label: '3 - Moyennement claire' },
                { value: '4', label: '4 - Claire' },
                { value: '5', label: '5 - Très claire' }
            ]
        },
        {
            id: 'p3_eval1_utilite',
            type: 'radio',
            title: 'Utilité : Les conseils sont-ils utiles et applicables ?',
            required: true,
            options: [
                { value: '1', label: '1 - Pas du tout utiles' },
                { value: '2', label: '2 - Peu utiles' },
                { value: '3', label: '3 - Moyennement utiles' },
                { value: '4', label: '4 - Utiles' },
                { value: '5', label: '5 - Très utiles' }
            ]
        },
        {
            id: 'p3_eval1_ton',
            type: 'radio',
            title: 'Ton : Le ton est-il adapté à la situation ?',
            required: true,
            options: [
                { value: '1', label: '1 - Pas du tout adapté' },
                { value: '2', label: '2 - Peu adapté' },
                { value: '3', label: '3 - Moyennement adapté' },
                { value: '4', label: '4 - Adapté' },
                { value: '5', label: '5 - Parfaitement adapté' }
            ]
        },
        {
            id: 'p3_eval1_longueur',
            type: 'radio',
            title: 'Longueur : La réponse a-t-elle la bonne longueur ?',
            required: true,
            options: [
                { value: 'trop_court', label: 'Trop courte, j\'aurais voulu plus de détails' },
                { value: 'juste', label: 'Longueur parfaite' },
                { value: 'trop_long', label: 'Trop longue, j\'aurais préféré plus concis' }
            ]
        },
        {
            id: 'p3_eval1_global',
            type: 'radio',
            title: 'Note globale : Êtes-vous satisfait(e) de cette réponse ?',
            required: true,
            options: [
                { value: '1', label: '1 - Très insatisfait' },
                { value: '2', label: '2 - Insatisfait' },
                { value: '3', label: '3 - Neutre' },
                { value: '4', label: '4 - Satisfait' },
                { value: '5', label: '5 - Très satisfait' }
            ]
        },

        // Attention check 2
        {
            id: 'p3_attention_check',
            type: 'radio',
            title: '⚠️ Vérification : Quelle était la question posée à l\'IA dans l\'évaluation précédente ?',
            required: true,
            options: [
                { value: 'recette', label: 'Une recette de cuisine' },
                { value: 'stress', label: 'Comment gérer le stress' },
                { value: 'voyage', label: 'Des conseils de voyage' },
                { value: 'admin', label: 'Une démarche administrative' }
            ],
            metadata: { is_attention_check: true, correct_answer: 'stress' }
        },

        // Évaluation 2 : Réponse trop formelle
        {
            id: 'p3_eval2_context',
            type: 'info',
            title: 'Réponse à évaluer #2',
            text: `
                <div class="comparison-scenario">
                    <p><strong>Question posée :</strong> "C'est quoi un bon resto pas cher à Paris ?"</p>
                    <blockquote>
                        <p><strong>Réponse de l'IA :</strong></p>
                        <p>"Je vous remercie pour votre question concernant les établissements de restauration à prix modéré dans la capitale française.</p>
                        <p>Voici quelques recommandations :</p>
                        <ol>
                            <li>Bouillon Chartier (9e arrondissement) : établissement historique proposant une cuisine traditionnelle française à des tarifs accessibles.</li>
                            <li>Bouillon Julien (10e) : cadre Art Nouveau remarquable, formules économiques.</li>
                            <li>Les chaînes de restauration rapide qualitative : Big Mamma, PNY, etc.</li>
                        </ol>
                        <p>Je reste à votre disposition pour tout complément d'information."</p>
                    </blockquote>
                </div>
            `
        },
        {
            id: 'p3_eval2_ton_adapte',
            type: 'radio',
            title: 'Le ton de cette réponse est-il adapté à la question (casual) ?',
            required: true,
            options: [
                { value: 'trop_formel', label: 'Trop formel par rapport à la question' },
                { value: 'bien', label: 'Le ton est bien' },
                { value: 'trop_casual', label: 'Trop casual par rapport à la question' }
            ]
        },
        {
            id: 'p3_eval2_amelioration',
            type: 'multiple',
            title: 'Qu\'est-ce qui pourrait améliorer cette réponse ? (plusieurs choix)',
            required: true,
            options: [
                { value: 'moins_formel', label: 'Ton moins formel, plus décontracté' },
                { value: 'tutoiement', label: 'Utiliser le tutoiement' },
                { value: 'avis_perso', label: 'Donner un avis personnel / recommandation' },
                { value: 'prix', label: 'Indiquer les prix approximatifs' },
                { value: 'plus_options', label: 'Plus d\'options' },
                { value: 'quartier', label: 'Demander le quartier recherché' },
                { value: 'rien', label: 'La réponse est très bien comme ça' }
            ]
        },

        // ============================================================
        // PARTIE 4 : REFORMULATIONS NATURELLES
        // ============================================================
        {
            id: 'p4_intro',
            type: 'info',
            title: 'Partie 5/6 : Reformulations',
            text: `
                <p>Vous allez voir des phrases "correctes mais artificielles".</p>
                <p><strong>Réécrivez-les comme VOUS le diriez naturellement.</strong></p>
                <div style="background: #dbeafe; padding: 12px; border-radius: 8px; margin-top: 12px;">
                    💡 Il n'y a pas de bonne réponse, c'est VOTRE façon de parler qui nous intéresse !
                </div>
            `
        },
        {
            id: 'p4_reformulation1',
            type: 'textarea',
            title: 'Reformulez naturellement :',
            text: '"Je suis dans l\'impossibilité de vous fournir une assistance sur ce sujet."',
            placeholder: 'Écrivez comme vous diriez ça naturellement...',
            required: true,
            minLength: 10,
            maxLength: 500,
            metadata: { original_sentence: 'Je suis dans l\'impossibilité de vous fournir une assistance sur ce sujet.', register: 'formal_robotic' }
        },
        {
            id: 'p4_reformulation2',
            type: 'textarea',
            title: 'Reformulez naturellement :',
            text: '"Votre demande a bien été prise en compte et sera traitée dans les meilleurs délais."',
            placeholder: 'Écrivez comme vous diriez ça naturellement...',
            required: true,
            minLength: 10,
            maxLength: 500,
            metadata: { original_sentence: 'Votre demande a bien été prise en compte et sera traitée dans les meilleurs délais.', register: 'administrative' }
        },
        {
            id: 'p4_reformulation3',
            type: 'textarea',
            title: 'Reformulez naturellement :',
            text: '"Je comprends que cette situation puisse être source de frustration pour vous."',
            placeholder: 'Écrivez comme vous diriez ça naturellement...',
            required: true,
            minLength: 10,
            maxLength: 500,
            metadata: { original_sentence: 'Je comprends que cette situation puisse être source de frustration pour vous.', register: 'corporate_empathy' }
        },
        {
            id: 'p4_reformulation4',
            type: 'textarea',
            title: 'Reformulez naturellement :',
            text: '"Il serait pertinent de considérer les différentes alternatives qui s\'offrent à vous."',
            placeholder: 'Écrivez comme vous diriez ça naturellement...',
            required: true,
            minLength: 10,
            maxLength: 500,
            metadata: { original_sentence: 'Il serait pertinent de considérer les différentes alternatives qui s\'offrent à vous.', register: 'formal_suggestion' }
        },
        {
            id: 'p4_reformulation5',
            type: 'textarea',
            title: 'Reformulez naturellement :',
            text: '"N\'hésitez pas à revenir vers moi si vous avez des questions supplémentaires."',
            placeholder: 'Écrivez comme vous diriez ça naturellement...',
            required: true,
            minLength: 10,
            maxLength: 500,
            metadata: { original_sentence: 'N\'hésitez pas à revenir vers moi si vous avez des questions supplémentaires.', register: 'closing_formula' }
        },

        // ============================================================
        // PARTIE 5 : PRÉFÉRENCES EXPLICITES
        // ============================================================
        {
            id: 'p5_intro',
            type: 'info',
            title: 'Partie 6/6 : Vos préférences',
            text: `
                <p>Dernières questions sur vos <strong>préférences générales</strong> quand vous parlez à une IA.</p>
            `
        },
        {
            id: 'p5_tutoiement',
            type: 'radio',
            title: 'Préférez-vous qu\'une IA vous tutoie ou vous vouvoie ?',
            required: true,
            options: [
                { value: 'tutoiement', label: 'Tutoiement - c\'est plus naturel et sympa' },
                { value: 'vouvoiement', label: 'Vouvoiement - c\'est plus respectueux' },
                { value: 'depends_context', label: 'Ça dépend du contexte' },
                { value: 'indifferent', label: 'Ça m\'est égal' }
            ]
        },
        {
            id: 'p5_tutoiement_context',
            type: 'multiple',
            title: 'Dans quels contextes préféreriez-vous le VOUVOIEMENT ? (plusieurs choix)',
            required: true,
            showIf: { questionId: 'p5_tutoiement', value: 'depends_context' },
            options: [
                { value: 'pro', label: 'Questions professionnelles' },
                { value: 'admin', label: 'Démarches administratives' },
                { value: 'sante', label: 'Questions de santé' },
                { value: 'finance', label: 'Questions financières / juridiques' },
                { value: 'serieux', label: 'Sujets sérieux en général' },
                { value: 'premiere_fois', label: 'Première interaction avec l\'IA' }
            ]
        },
        {
            id: 'p5_emojis',
            type: 'radio',
            title: 'Que pensez-vous de l\'utilisation d\'emojis par une IA ?',
            required: true,
            options: [
                { value: 'aime', label: 'J\'aime bien, ça rend la conversation plus sympa 😊' },
                { value: 'modere', label: 'Avec modération, 1-2 emojis max c\'est OK' },
                { value: 'prefere_pas', label: 'Je préfère sans, ça fait pas sérieux' },
                { value: 'depends', label: 'Ça dépend du contexte' }
            ]
        },
        {
            id: 'p5_longueur_ideale',
            type: 'radio',
            title: 'En général, quelle longueur de réponse préférez-vous ?',
            required: true,
            options: [
                { value: 'tres_court', label: 'Très court - juste l\'essentiel, quelques phrases' },
                { value: 'court', label: 'Court - un paragraphe bien résumé' },
                { value: 'moyen', label: 'Moyen - assez de détails mais pas trop' },
                { value: 'long', label: 'Long - j\'aime avoir tous les détails' },
                { value: 'depends', label: 'Ça dépend de la question' }
            ]
        },
        {
            id: 'p5_format_prefere',
            type: 'radio',
            title: 'Quel format de réponse préférez-vous généralement ?',
            required: true,
            options: [
                { value: 'listes', label: 'Listes à puces - facile à scanner' },
                { value: 'prose', label: 'Texte fluide - plus agréable à lire' },
                { value: 'mixte', label: 'Mélange des deux' },
                { value: 'depends', label: 'Ça dépend du sujet' }
            ]
        },
        {
            id: 'p5_personnalite',
            type: 'multiple',
            title: 'Quelles qualités appréciez-vous chez une IA ? (3 choix maximum)',
            required: true,
            maxSelections: 3,
            options: [
                { value: 'precise', label: 'Précise et factuelle' },
                { value: 'chaleureuse', label: 'Chaleureuse et empathique' },
                { value: 'directe', label: 'Directe et efficace' },
                { value: 'patiente', label: 'Patiente et pédagogue' },
                { value: 'drole', label: 'Avec un peu d\'humour' },
                { value: 'humble', label: 'Humble (reconnaît ses limites)' },
                { value: 'proactive', label: 'Proactive (anticipe mes besoins)' },
                { value: 'neutre', label: 'Neutre et objective' }
            ]
        },
        {
            id: 'p5_agacement',
            type: 'multiple',
            title: 'Qu\'est-ce qui vous agace le plus chez une IA ? (3 choix maximum)',
            required: true,
            maxSelections: 3,
            options: [
                { value: 'trop_long', label: 'Réponses trop longues' },
                { value: 'repetitions', label: 'Répétitions inutiles' },
                { value: 'trop_formel', label: 'Ton trop formel / robotique' },
                { value: 'condescendant', label: 'Ton condescendant' },
                { value: 'pas_repond', label: 'Ne répond pas vraiment à ma question' },
                { value: 'trop_prudent', label: 'Trop de précautions / avertissements' },
                { value: 'manque_personnalite', label: 'Manque de personnalité' },
                { value: 'faux_enthousiasme', label: 'Faux enthousiasme ("Excellent question !")' }
            ]
        },
        {
            id: 'p5_commentaire_libre',
            type: 'textarea',
            title: 'Avez-vous d\'autres remarques sur la façon dont une IA devrait communiquer ?',
            placeholder: 'Partagez vos idées librement... (optionnel)',
            required: false,
            maxLength: 1000
        },

        // ============================================================
        // FEEDBACK FINAL
        // ============================================================
        {
            id: 'p6_difficulte',
            type: 'radio',
            title: 'Comment avez-vous trouvé ce questionnaire ?',
            required: true,
            options: [
                { value: 'tres_facile', label: 'Très facile' },
                { value: 'facile', label: 'Facile' },
                { value: 'normal', label: 'Normal' },
                { value: 'difficile', label: 'Difficile (questions compliquées)' },
                { value: 'long', label: 'Trop long' }
            ]
        },
        {
            id: 'p6_honnetete',
            type: 'radio',
            title: 'Avez-vous répondu honnêtement à toutes les questions ?',
            required: true,
            options: [
                { value: 'oui', label: 'Oui, à toutes' },
                { value: 'presque', label: 'Oui, presque toutes' },
                { value: 'pas_toujours', label: 'Pas toujours (j\'ai répondu vite sur certaines)' }
            ]
        },
        {
            id: 'p6_recontact',
            type: 'radio',
            title: 'Accepteriez-vous de participer à d\'autres questionnaires similaires ?',
            required: true,
            options: [
                { value: 'oui', label: 'Oui, avec plaisir' },
                { value: 'si_remunere', label: 'Oui, si c\'est rémunéré' },
                { value: 'non', label: 'Non merci' }
            ]
        }
    ]
};
