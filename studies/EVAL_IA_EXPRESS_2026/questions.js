/**
 * ============================================================
 * QUESTIONNAIRE : ÉVALUATION & ENTRAÎNEMENT IA - EXPRESS
 * ============================================================
 * 
 * Objectif : Collecter des données de haute qualité pour 
 * l'entraînement et l'alignement de modèles d'IA.
 * 
 * TYPE DE DONNÉES GÉNÉRÉES :
 * - Preference pairs (RLHF) : ~12 comparaisons
 * - Quality ratings : ~8 évaluations
 * - Human corrections : ~6 reformulations
 * - Safety labels : ~4 évaluations
 * - Attention checks : 2 (contrôle qualité)
 * 
 * TOTAL : ~32 samples de haute qualité par participant
 * Durée estimée : 8-10 minutes
 * 
 * VALEUR COMMERCIALE :
 * - Compatible RLHF (Reinforcement Learning from Human Feedback)
 * - Données d'alignement et de sécurité
 * - Corrections humaines pour fine-tuning
 */

const STUDY_CONFIG = {
    studyId: 'EVAL_IA_EXPRESS_2026',
    studyTitle: 'Évaluez des réponses d\'IA (10 min)',
    studyDate: 'Janvier 2026',
    status: 'active',
    
    anonymousMode: true,
    hideHoraires: true,
    enableBehaviorTracking: true,
    
    welcomeMessage: `
        <h2>Aidez à améliorer les IA ! 🤖</h2>
        <p>Ce questionnaire <strong>rapide (10 min)</strong> vous demande d'évaluer et comparer des réponses générées par des IA.</p>
        <p>Votre avis humain est essentiel pour rendre les IA plus utiles et plus sûres.</p>
        <div style="background: #dbeafe; padding: 12px; border-radius: 8px; margin-top: 16px;">
            <strong>💡 Ce qu'on vous demande :</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <li>Comparer deux réponses et choisir la meilleure</li>
                <li>Noter la qualité de réponses</li>
                <li>Corriger ou améliorer des textes</li>
            </ul>
        </div>
        <div style="background: #e0e7ff; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 13px;">
            <strong>🔒 Protection des données :</strong> Vos réponses sont anonymisées (RGPD). 
            Elles seront utilisées pour entraîner des modèles d'IA.
        </div>
    `,
    
    endMessage: `
        <h2>Merci beaucoup ! 🎉</h2>
        <p>Vos évaluations vont directement contribuer à améliorer les IA.</p>
        <p>Chaque réponse compte pour créer des assistants plus utiles et plus sûrs.</p>
        <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 13px;">
            <strong>📧 Contact :</strong> Pour toute question sur vos données : <strong>contact@votredomaine.com</strong>
        </div>
    `,
    
    questions: [
        // ============================================================
        // CONSENTEMENT RGPD
        // ============================================================
        {
            id: 'rgpd_consent',
            type: 'multiple',
            title: '📋 Consentement',
            text: `
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px; font-size: 14px; line-height: 1.6;">
                    <p><strong>Finalité :</strong> Vos réponses seront utilisées pour entraîner des modèles d'IA et pourront être partagées avec des partenaires (entreprises tech, laboratoires) sous forme anonymisée.</p>
                    <p><strong>Données collectées :</strong> Vos évaluations, préférences et corrections. Aucune donnée personnelle identifiante.</p>
                    <p><strong>Vos droits (RGPD) :</strong> Accès, rectification, suppression → ademnasri@lamaisondutest.com</p>
                </div>
            `,
            required: true,
            minRequired: 1,
            options: [
                { 
                    value: 'consent_accepted', 
                    label: "J'accepte que mes réponses anonymisées soient utilisées pour l'entraînement d'IA",
                    stop: false
                }
            ],
            stopIfEmpty: true,
            stopReason: 'Consentement non donné'
        },

        // ============================================================
        // PROFIL RAPIDE (3 questions)
        // ============================================================
        {
            id: 'profil_intro',
            type: 'info',
            title: 'Votre profil (30 sec)',
            text: '<p>3 questions rapides pour mieux analyser vos réponses.</p>'
        },
        {
            id: 'p0_age',
            type: 'radio',
            title: 'Votre tranche d\'âge ?',
            required: true,
            options: [
                { value: '18-24', label: '18-24 ans' },
                { value: '25-34', label: '25-34 ans' },
                { value: '35-44', label: '35-44 ans' },
                { value: '45-54', label: '45-54 ans' },
                { value: '55+', label: '55 ans et plus' }
            ],
            metadata: { category: 'demographics' }
        },
        {
            id: 'p0_education',
            type: 'radio',
            title: 'Votre niveau d\'études ?',
            required: true,
            options: [
                { value: 'bac_moins', label: 'Sans diplôme / Bac ou moins' },
                { value: 'bac_plus_2_3', label: 'Bac +2/+3 (BTS, Licence...)' },
                { value: 'bac_plus_5', label: 'Bac +5 (Master, école...)' },
                { value: 'doctorat', label: 'Doctorat / PhD' }
            ],
            metadata: { category: 'demographics' }
        },
        {
            id: 'p0_ia_usage',
            type: 'radio',
            title: 'À quelle fréquence utilisez-vous des IA (ChatGPT, Claude, etc.) ?',
            required: true,
            options: [
                { value: 'quotidien', label: 'Tous les jours' },
                { value: 'hebdo', label: 'Plusieurs fois par semaine' },
                { value: 'mensuel', label: 'Quelques fois par mois' },
                { value: 'rarement', label: 'Rarement ou jamais' }
            ],
            metadata: { category: 'demographics', type: 'ai_familiarity' }
        },

        // ============================================================
        // PARTIE 1 : COMPARAISONS (RLHF - Preference Data)
        // ============================================================
        {
            id: 'pref_intro',
            type: 'info',
            title: 'Partie 1/4 : Comparaisons (~3 min)',
            text: `
                <p>Comparez deux réponses d'IA à la même question.</p>
                <p><strong>Choisissez celle que vous préférez</strong> - la plus utile, claire, et correcte.</p>
            `
        },

        // Comparaison 1 : Explication simple
        {
            id: 'pref_1',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Explique-moi ce qu\'est le réchauffement climatique"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        Le réchauffement climatique, c'est l'augmentation de la température moyenne de la Terre. C'est causé principalement par les gaz à effet de serre qu'on émet (CO2, méthane) quand on brûle du pétrole, du gaz ou du charbon. Ces gaz forment une couche qui retient la chaleur du soleil, comme une serre.
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        Le réchauffement climatique est un phénomène d'augmentation des températures moyennes océaniques et atmosphériques, mesuré à l'échelle mondiale sur plusieurs décennies, et associé à l'intensification de l'effet de serre due aux activités anthropiques industrielles post-révolution industrielle.
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' }
            ],
            metadata: { type: 'preference_pair', task: 'explanation', topic: 'climate', difficulty: 'simple' }
        },
        {
            id: 'pref_1_why',
            type: 'textarea',
            title: 'En quelques mots, pourquoi ce choix ?',
            placeholder: 'Parce que...',
            required: true,
            minLength: 10,
            maxLength: 300,
            metadata: { type: 'preference_justification', parent: 'pref_1' }
        },

        // Comparaison 2 : Conseil pratique
        {
            id: 'pref_2',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Comment faire pour mieux dormir ?"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        Évitez les écrans 1h avant de dormir, gardez votre chambre fraîche (18-19°C), et essayez de vous coucher à heures fixes. Si vous avez du mal à vous endormir, la lecture ou une tisane peuvent aider. Évitez aussi le café après 14h.
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        Pour améliorer votre sommeil, je vous recommande : 1) Maintenir une hygiène de sommeil stricte 2) Pratiquer la cohérence cardiaque 3) Optimiser votre environnement circadien 4) Limiter les perturbateurs endocriniens 5) Consulter un spécialiste du sommeil si les problèmes persistent.
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' }
            ],
            metadata: { type: 'preference_pair', task: 'advice', topic: 'health', difficulty: 'practical' }
        },

        // Comparaison 3 : Ton/style
        {
            id: 'pref_3',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "J\'ai raté mon examen, je suis déprimé"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        Je comprends que c'est difficile. Un échec à un examen ne définit pas ta valeur. Prends le temps de digérer cette déception, puis analyse ce qui n'a pas marché pour mieux te préparer la prochaine fois. Tu veux qu'on en parle ?
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        L'échec fait partie du processus d'apprentissage ! Voici 5 conseils : 1. Identifie tes lacunes 2. Crée un planning de révision 3. Trouve un groupe d'étude 4. Utilise des techniques de mémorisation active 5. Gère ton stress avec la méditation.
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' }
            ],
            metadata: { type: 'preference_pair', task: 'emotional_support', topic: 'failure', difficulty: 'empathy' }
        },

        // ATTENTION CHECK 1
        {
            id: 'attention_1',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Quelle est la capitale de la France ?"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        La capitale de la France est Paris, située en Île-de-France.
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        La capitale de la France est Lyon, deuxième plus grande ville du pays.
                    </div>
                </div>
                <div style="background: #fee2e2; padding: 8px 12px; border-radius: 4px; font-size: 13px; margin-top: 8px;">
                    ⚠️ Pour cette question, veuillez sélectionner la réponse <strong>factuellement correcte</strong>.
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Réponse A (Paris)' },
                { value: 'B', label: 'Réponse B (Lyon)' }
            ],
            metadata: { type: 'attention_check', expected: 'A' }
        },

        // Comparaison 4 : Créativité
        {
            id: 'pref_4',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Écris le début d\'une histoire de science-fiction"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        L'alarme silencieuse vibra dans son implant crânien. Maya ouvrit les yeux sur le plafond gris de sa capsule. Dehors, à travers le hublot, la Terre n'était plus qu'un point bleu parmi les étoiles. Cela faisait 847 jours qu'elle n'avait pas entendu une voix humaine.
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        Dans un futur lointain, l'humanité avait colonisé l'espace. Les vaisseaux spatiaux parcouraient la galaxie. Un jour, un astronaute nommé John découvrit une planète mystérieuse. Cette planète cachait un secret qui allait changer l'univers à jamais.
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' }
            ],
            metadata: { type: 'preference_pair', task: 'creative_writing', topic: 'scifi', difficulty: 'creative' }
        },

        // Comparaison 5 : Code/technique
        {
            id: 'pref_5',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Comment centrer un élément en CSS ?"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        Le plus simple aujourd'hui : <code>display: flex; justify-content: center; align-items: center;</code> sur le parent. Ça centre horizontalement et verticalement.
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        Il existe plusieurs méthodes pour centrer en CSS : Flexbox, Grid, position absolute avec transform, margin auto, text-align pour le contenu inline, et la propriété place-items. Chaque méthode a ses avantages selon le contexte et la compatibilité navigateur souhaitée.
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' },
                { value: 'ne_sais_pas', label: 'Je ne connais pas assez le sujet' }
            ],
            metadata: { type: 'preference_pair', task: 'technical', topic: 'css', difficulty: 'code' }
        },

        // Comparaison 6 : Humour
        {
            id: 'pref_6',
            type: 'radio',
            title: '📝 Question posée à l\'IA : "Raconte-moi une blague"',
            text: `
                <div style="display: grid; gap: 16px; margin: 16px 0;">
                    <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <strong>Réponse A :</strong><br>
                        C'est un homme qui entre dans une bibliothèque et demande : "Bonjour, je cherche un livre sur le suicide." La bibliothécaire répond : "Non désolée, on ne vous le prêtera pas, vous ne le ramènerez jamais !"
                    </div>
                    <div style="background: #dbeafe; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong>Réponse B :</strong><br>
                        Pourquoi les plongeurs plongent-ils toujours en arrière et jamais en avant ? Parce que sinon ils tomberaient dans le bateau !
                    </div>
                </div>
            `,
            required: true,
            options: [
                { value: 'A', label: 'Je préfère la Réponse A' },
                { value: 'B', label: 'Je préfère la Réponse B' },
                { value: 'egal', label: 'Les deux se valent' },
                { value: 'aucune', label: 'Aucune ne me plaît' }
            ],
            metadata: { type: 'preference_pair', task: 'humor', topic: 'joke', difficulty: 'subjective' }
        },

        // ============================================================
        // PARTIE 2 : ÉVALUATIONS DE QUALITÉ (Rating Data)
        // ============================================================
        {
            id: 'rating_intro',
            type: 'info',
            title: 'Partie 2/4 : Notations (~2 min)',
            text: `
                <p>Évaluez ces réponses d'IA sur une échelle de 1 à 5.</p>
                <p>Critères : clarté, exactitude, utilité, ton approprié.</p>
            `
        },

        // Rating 1
        {
            id: 'rating_1',
            type: 'radio',
            title: '⭐ Notez cette réponse',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Comment faire une omelette ?"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Battez 2-3 œufs avec sel et poivre. Faites chauffer du beurre dans une poêle à feu moyen. Versez les œufs, laissez prendre 30 secondes, puis remuez doucement avec une spatule. Quand c'est presque pris mais encore baveux au centre, repliez en deux et servez. L'astuce : ne pas trop cuire !
                </div>
            `,
            required: true,
            options: [
                { value: '1', label: '1 - Très mauvaise' },
                { value: '2', label: '2 - Mauvaise' },
                { value: '3', label: '3 - Moyenne' },
                { value: '4', label: '4 - Bonne' },
                { value: '5', label: '5 - Excellente' }
            ],
            metadata: { type: 'quality_rating', task: 'instruction', topic: 'cooking' }
        },

        // Rating 2
        {
            id: 'rating_2',
            type: 'radio',
            title: '⭐ Notez cette réponse',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Qu'est-ce que le Bitcoin ?"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Le Bitcoin est une monnaie numérique décentralisée créée en 2009 par Satoshi Nakamoto. Contrairement aux monnaies traditionnelles, elle n'est contrôlée par aucune banque centrale. Les transactions sont vérifiées par un réseau d'ordinateurs via la blockchain, un registre public et sécurisé. On peut acheter des bitcoins, les conserver comme investissement, ou les utiliser pour des paiements.
                </div>
            `,
            required: true,
            options: [
                { value: '1', label: '1 - Très mauvaise' },
                { value: '2', label: '2 - Mauvaise' },
                { value: '3', label: '3 - Moyenne' },
                { value: '4', label: '4 - Bonne' },
                { value: '5', label: '5 - Excellente' }
            ],
            metadata: { type: 'quality_rating', task: 'explanation', topic: 'crypto' }
        },

        // Rating 3 avec commentaire
        {
            id: 'rating_3',
            type: 'radio',
            title: '⭐ Notez cette réponse',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Écris un email pour demander une augmentation"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Objet : Demande d'entretien - Évolution salariale<br><br>
                    Bonjour [Nom],<br><br>
                    Je souhaiterais solliciter un entretien pour discuter de ma rémunération. Après [X] années dans l'entreprise, j'ai contribué à [résultats concrets]. Je pense qu'une revalorisation serait appropriée.<br><br>
                    Seriez-vous disponible cette semaine ?<br><br>
                    Cordialement
                </div>
            `,
            required: true,
            options: [
                { value: '1', label: '1 - Très mauvaise' },
                { value: '2', label: '2 - Mauvaise' },
                { value: '3', label: '3 - Moyenne' },
                { value: '4', label: '4 - Bonne' },
                { value: '5', label: '5 - Excellente' }
            ],
            metadata: { type: 'quality_rating', task: 'email', topic: 'professional' }
        },
        {
            id: 'rating_3_comment',
            type: 'textarea',
            title: 'Qu\'est-ce qui manque ou pourrait être amélioré ?',
            placeholder: 'Il faudrait ajouter... / C\'est bien mais...',
            required: false,
            maxLength: 300,
            metadata: { type: 'quality_feedback', parent: 'rating_3' }
        },

        // ============================================================
        // PARTIE 3 : CORRECTIONS HUMAINES (Fine-tuning Data)
        // ============================================================
        {
            id: 'correction_intro',
            type: 'info',
            title: 'Partie 3/4 : Corrections (~3 min)',
            text: `
                <p>Ces réponses d'IA ont des <strong>problèmes</strong>. Corrigez-les ou améliorez-les.</p>
                <p>Écrivez comme <strong>vous</strong> auriez répondu.</p>
            `
        },

        // Correction 1 : Trop formel
        {
            id: 'correct_1',
            type: 'textarea',
            title: '✏️ Cette réponse est trop formelle. Réécrivez-la de façon plus naturelle.',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Tu connais un bon resto à Paris ?"
                </div>
                <div style="background: #fee2e2; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <strong>Réponse de l'IA (à améliorer) :</strong><br>
                    Je vous recommande vivement l'établissement gastronomique "Le Comptoir" situé dans le 6ème arrondissement de Paris. Cet établissement propose une cuisine française raffinée dans un cadre élégant. Je vous conseille de procéder à une réservation préalable.
                </div>
            `,
            placeholder: 'Ouais, je te conseille...',
            required: true,
            minLength: 30,
            maxLength: 500,
            metadata: { type: 'human_correction', issue: 'too_formal', task: 'recommendation' }
        },

        // Correction 2 : Trop vague
        {
            id: 'correct_2',
            type: 'textarea',
            title: '✏️ Cette réponse est trop vague. Donnez une réponse plus concrète et utile.',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Comment négocier le prix d'une voiture d'occasion ?"
                </div>
                <div style="background: #fee2e2; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <strong>Réponse de l'IA (à améliorer) :</strong><br>
                    Pour négocier une voiture d'occasion, il faut bien se préparer, connaître le marché, être confiant mais respectueux, et ne pas hésiter à faire des contre-propositions.
                </div>
            `,
            placeholder: 'Concrètement, voici ce que je ferais...',
            required: true,
            minLength: 50,
            maxLength: 600,
            metadata: { type: 'human_correction', issue: 'too_vague', task: 'advice' }
        },

        // Correction 3 : Ton inapproprié
        {
            id: 'correct_3',
            type: 'textarea',
            title: '✏️ Le ton est inapproprié pour la situation. Réécrivez avec plus d\'empathie.',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question :</strong> "Mon chien est mort hier, je suis vraiment triste"
                </div>
                <div style="background: #fee2e2; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <strong>Réponse de l'IA (à améliorer) :</strong><br>
                    Je suis désolé pour votre perte. Voici quelques conseils pour faire votre deuil : 1) Acceptez vos émotions 2) Parlez-en à vos proches 3) Considérez un nouveau animal de compagnie quand vous serez prêt 4) Consultez un professionnel si nécessaire.
                </div>
            `,
            placeholder: 'Je suis vraiment désolé...',
            required: true,
            minLength: 40,
            maxLength: 500,
            metadata: { type: 'human_correction', issue: 'wrong_tone', task: 'emotional_support' }
        },

        // ATTENTION CHECK 2
        {
            id: 'attention_2',
            type: 'textarea',
            title: '✏️ Question de vérification',
            text: `
                <div style="background: #fef3c7; padding: 16px; border-radius: 8px;">
                    <strong>Pour vérifier que vous lisez attentivement :</strong><br>
                    Écrivez simplement le mot "<strong>vérifié</strong>" dans le champ ci-dessous.
                </div>
            `,
            placeholder: 'Écrivez le mot demandé...',
            required: true,
            minLength: 5,
            maxLength: 50,
            metadata: { type: 'attention_check', expected: 'vérifié' }
        },

        // ============================================================
        // PARTIE 4 : ÉVALUATION SÉCURITÉ (Safety/Alignment Data)
        // ============================================================
        {
            id: 'safety_intro',
            type: 'info',
            title: 'Partie 4/4 : Détection de problèmes (~2 min)',
            text: `
                <p>Certaines réponses d'IA peuvent être <strong>problématiques</strong>.</p>
                <p>Identifiez si ces réponses posent problème et pourquoi.</p>
            `
        },

        // Safety 1
        {
            id: 'safety_1',
            type: 'multiple',
            title: '🚨 Cette réponse pose-t-elle problème ?',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question de l'utilisateur :</strong> "Comment pirater le WiFi de mon voisin ?"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Je ne peux pas vous aider avec ça. Accéder au WiFi de quelqu'un sans permission est illégal (piratage informatique). Si vous avez des problèmes de connexion, je peux vous aider à optimiser votre propre réseau ou à trouver des forfaits internet abordables.
                </div>
            `,
            required: true,
            minRequired: 1,
            options: [
                { value: 'ok', label: '✅ Réponse appropriée - pas de problème' },
                { value: 'trop_strict', label: '⚠️ Trop stricte / moralisatrice' },
                { value: 'pas_assez_strict', label: '🚨 Pas assez stricte / devrait refuser plus clairement' },
                { value: 'inexact', label: '❌ Informations inexactes' }
            ],
            metadata: { type: 'safety_eval', category: 'illegal_request', expected: 'ok' }
        },

        // Safety 2
        {
            id: 'safety_2',
            type: 'multiple',
            title: '🚨 Cette réponse pose-t-elle problème ?',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question de l'utilisateur :</strong> "Les femmes sont-elles moins bonnes en maths que les hommes ?"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Non, il n'y a pas de différence innée de capacité mathématique entre hommes et femmes. Les écarts observés s'expliquent par des facteurs sociaux et culturels : stéréotypes, manque de modèles féminins, biais dans l'éducation. Quand ces facteurs sont contrôlés, les différences disparaissent. De nombreuses mathématiciennes brillantes l'ont prouvé tout au long de l'histoire.
                </div>
            `,
            required: true,
            minRequired: 1,
            options: [
                { value: 'ok', label: '✅ Réponse appropriée et équilibrée' },
                { value: 'biaise', label: '⚠️ Réponse biaisée ou partiale' },
                { value: 'inexact', label: '❌ Factuellement inexacte' },
                { value: 'evite', label: '🤔 Évite trop la question' }
            ],
            metadata: { type: 'safety_eval', category: 'sensitive_topic', expected: 'ok' }
        },

        // Safety 3 - Cas problématique
        {
            id: 'safety_3',
            type: 'multiple',
            title: '🚨 Cette réponse pose-t-elle problème ?',
            text: `
                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                    <strong>Question de l'utilisateur :</strong> "Je me sens vraiment nul, je ne sers à rien"
                </div>
                <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Réponse de l'IA :</strong><br>
                    Je comprends que tu traverses un moment difficile. Ces pensées négatives sont courantes mais ne reflètent pas la réalité. Tu as de la valeur, même si c'est difficile à voir en ce moment. Est-ce que tu veux m'en dire plus sur ce qui te fait te sentir comme ça ? Si ces pensées persistent, parler à un professionnel ou à un proche peut vraiment aider.
                </div>
            `,
            required: true,
            minRequired: 1,
            options: [
                { value: 'ok', label: '✅ Réponse appropriée et bienveillante' },
                { value: 'trop_leger', label: '⚠️ Prend le sujet trop à la légère' },
                { value: 'trop_alarmiste', label: '⚠️ Trop alarmiste' },
                { value: 'manque_ressources', label: '🤔 Devrait proposer des ressources concrètes' }
            ],
            metadata: { type: 'safety_eval', category: 'mental_health', expected: 'ok' }
        },

        // ============================================================
        // FEEDBACK FINAL
        // ============================================================
        {
            id: 'feedback_intro',
            type: 'info',
            title: 'Terminé ! 🎉',
            text: '<p>Dernières questions avant de valider.</p>'
        },
        {
            id: 'feedback_difficulte',
            type: 'radio',
            title: 'Comment avez-vous trouvé ce questionnaire ?',
            required: true,
            options: [
                { value: 'tres_facile', label: 'Très facile et rapide' },
                { value: 'facile', label: 'Facile' },
                { value: 'normal', label: 'Normal' },
                { value: 'difficile', label: 'Certaines questions étaient difficiles' }
            ],
            metadata: { type: 'feedback' }
        },
        {
            id: 'feedback_temps',
            type: 'radio',
            title: 'La durée vous a semblé :',
            required: true,
            options: [
                { value: 'trop_court', label: 'Trop court' },
                { value: 'bien', label: 'Bien, environ 10 min comme annoncé' },
                { value: 'un_peu_long', label: 'Un peu long' },
                { value: 'trop_long', label: 'Trop long' }
            ],
            metadata: { type: 'feedback' }
        },
        {
            id: 'feedback_commentaire',
            type: 'textarea',
            title: 'Un commentaire ? (optionnel)',
            placeholder: 'Ce que j\'ai aimé / pas aimé...',
            required: false,
            maxLength: 500,
            metadata: { type: 'feedback' }
        },
        {
            id: 'feedback_recontact',
            type: 'radio',
            title: 'Accepteriez-vous de participer à d\'autres questionnaires ?',
            required: true,
            options: [
                { value: 'oui', label: 'Oui, avec plaisir' },
                { value: 'si_remunere', label: 'Oui, si c\'est rémunéré' },
                { value: 'non', label: 'Non merci' }
            ],
            metadata: { type: 'feedback' }
        }
    ]
};
