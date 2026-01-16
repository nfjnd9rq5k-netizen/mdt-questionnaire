/**
 * ============================================================
 * QUESTIONNAIRE : CULTURE & LANGUE FRANÇAISE
 * ============================================================
 * 
 * Objectif : Collecter des données culturelles authentiques
 * pour améliorer la compréhension du français par les IA.
 * 
 * VALEUR UNIQUE : Données que les IA américaines ne peuvent pas
 * générer elles-mêmes (expressions, implicites, références).
 * 
 * Samples générés par participant : ~45-50
 * Durée estimée : 25-30 minutes
 * 
 * Structure :
 * - P0 : Profil linguistique
 * - P1 : Expressions idiomatiques (explication + usage)
 * - P2 : Argot et langage courant
 * - P3 : Références culturelles partagées
 * - P4 : Codes sociaux implicites
 * - P5 : Questions d'étrangers sur la France
 * - P6 : Régionalismes
 * - P7 : Feedback
 */

const STUDY_CONFIG = {
    studyId: 'CULTURE_FR_2026',
    studyTitle: 'Parlez-nous de la culture française',
    studyDate: 'Janvier 2026',
    status: 'active',
    
    anonymousMode: true,
    hideHoraires: true,
    enableBehaviorTracking: true,
    
    welcomeMessage: `
        <h2>Bienvenue ! 🇫🇷</h2>
        <p>Ce questionnaire explore la <strong>culture et la langue française</strong> sous toutes ses formes.</p>
        <p>Vos réponses aideront les IA à mieux comprendre les nuances, expressions et références que tout Français connaît.</p>
        <p><strong>Durée :</strong> environ 25-30 minutes</p>
        <div style="background: #fef3c7; padding: 12px; border-radius: 8px; margin-top: 16px;">
            <strong>💡 Conseil :</strong> Répondez naturellement, comme si vous expliquiez à un ami étranger. 
            C'est votre vécu et votre façon de parler qui nous intéressent !
        </div>
        <div style="background: #e0e7ff; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 13px;">
            <strong>🔒 Protection des données :</strong> Vos réponses sont anonymisées et traitées conformément au RGPD. 
            Vous pouvez exercer vos droits à tout moment (voir conditions sur la page suivante).
        </div>
    `,
    
    endMessage: `
        <h2>Merci infiniment ! 🙏</h2>
        <p>Vos réponses sont précieuses pour créer des IA qui comprennent vraiment la culture française.</p>
        <p>Grâce à vous, les IA pourront mieux saisir les nuances de notre langue et notre culture.</p>
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
        // PARTIE 0 : PROFIL LINGUISTIQUE & CULTUREL
        // ============================================================
        {
            id: 'p0_intro',
            type: 'info',
            title: 'Partie 1/7 : Votre profil',
            text: `
                <p>Quelques questions pour mieux comprendre votre rapport à la langue et à la culture française.</p>
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
            id: 'p0_region',
            type: 'radio',
            title: 'Dans quelle région avez-vous grandi ?',
            required: true,
            options: [
                { value: 'idf', label: 'Île-de-France / Paris' },
                { value: 'nord', label: 'Nord (Hauts-de-France, Nord-Pas-de-Calais)' },
                { value: 'est', label: 'Est (Alsace, Lorraine, Champagne)' },
                { value: 'ouest', label: 'Ouest (Bretagne, Pays de la Loire, Normandie)' },
                { value: 'sud_ouest', label: 'Sud-Ouest (Bordeaux, Toulouse, Pays Basque)' },
                { value: 'sud_est', label: 'Sud-Est (PACA, Marseille, Nice, Côte d\'Azur)' },
                { value: 'rhone_alpes', label: 'Rhône-Alpes (Lyon, Grenoble, Savoie)' },
                { value: 'centre', label: 'Centre (Auvergne, Bourgogne, Centre)' },
                { value: 'outre_mer', label: 'Outre-mer (DOM-TOM)' },
                { value: 'etranger', label: 'À l\'étranger (francophone)' }
            ]
        },
        {
            id: 'p0_langue_maternelle',
            type: 'radio',
            title: 'Le français est-il votre langue maternelle ?',
            required: true,
            options: [
                { value: 'oui_unique', label: 'Oui, ma seule langue maternelle' },
                { value: 'oui_bilingue', label: 'Oui, avec une autre langue (bilingue)' },
                { value: 'non_tres_jeune', label: 'Non, mais je l\'ai appris très jeune (avant 6 ans)' },
                { value: 'non_plus_tard', label: 'Non, je l\'ai appris plus tard' }
            ]
        },
        {
            id: 'p0_niveau_langue',
            type: 'radio',
            title: 'Comment décririez-vous votre maîtrise du français courant (argot, expressions) ?',
            required: true,
            options: [
                { value: 'natif', label: 'Natif - je connais toutes les expressions' },
                { value: 'tres_bon', label: 'Très bon - je comprends presque tout' },
                { value: 'bon', label: 'Bon - je comprends la plupart des expressions' },
                { value: 'moyen', label: 'Moyen - certaines expressions m\'échappent' }
            ]
        },
        {
            id: 'p0_milieu',
            type: 'radio',
            title: 'Dans quel milieu évoluez-vous principalement ?',
            required: true,
            options: [
                { value: 'urbain_grande', label: 'Grande ville (Paris, Lyon, Marseille...)' },
                { value: 'urbain_moyenne', label: 'Ville moyenne' },
                { value: 'periurbain', label: 'Périurbain / Banlieue' },
                { value: 'rural', label: 'Rural / Campagne' }
            ]
        },

        // ============================================================
        // PARTIE 1 : EXPRESSIONS IDIOMATIQUES
        // ============================================================
        {
            id: 'p1_intro',
            type: 'info',
            title: 'Partie 2/7 : Expressions françaises',
            text: `
                <p>Les expressions idiomatiques sont souvent mal comprises par les IA.</p>
                <p><strong>Expliquez ces expressions comme si vous parliez à un ami étranger</strong> qui apprend le français.</p>
            `
        },

        // Expression 1 : Avoir le cafard
        {
            id: 'p1_expr1_sens',
            type: 'textarea',
            title: 'Que signifie "avoir le cafard" ?',
            text: 'Expliquez le sens de cette expression avec vos propres mots.',
            placeholder: 'Ça veut dire...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'avoir le cafard', type: 'explanation' }
        },
        {
            id: 'p1_expr1_exemple',
            type: 'textarea',
            title: 'Donnez un exemple d\'utilisation de "avoir le cafard" dans une phrase.',
            placeholder: 'Par exemple : "..."',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'avoir le cafard', type: 'example' }
        },
        {
            id: 'p1_expr1_registre',
            type: 'radio',
            title: 'Dans quel contexte utiliseriez-vous "avoir le cafard" ?',
            required: true,
            options: [
                { value: 'tous', label: 'N\'importe quel contexte (famille, amis, travail)' },
                { value: 'informel', label: 'Seulement en contexte informel (amis, famille)' },
                { value: 'oral', label: 'Surtout à l\'oral' },
                { value: 'vieilli', label: 'Expression un peu vieillie / moins utilisée' }
            ],
            metadata: { expression: 'avoir le cafard', type: 'register' }
        },

        // Expression 2 : Poser un lapin
        {
            id: 'p1_expr2_sens',
            type: 'textarea',
            title: 'Que signifie "poser un lapin" ?',
            placeholder: 'Ça veut dire...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'poser un lapin', type: 'explanation' }
        },
        {
            id: 'p1_expr2_exemple',
            type: 'textarea',
            title: 'Donnez un exemple d\'utilisation dans une phrase.',
            placeholder: 'Par exemple : "..."',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'poser un lapin', type: 'example' }
        },
        {
            id: 'p1_expr2_variante',
            type: 'textarea',
            title: 'Connaissez-vous d\'autres façons de dire la même chose ? (synonymes ou variantes)',
            placeholder: 'On peut aussi dire...',
            required: false,
            maxLength: 300,
            metadata: { expression: 'poser un lapin', type: 'variants' }
        },

        // Expression 3 : Se prendre la tête
        {
            id: 'p1_expr3_sens',
            type: 'textarea',
            title: 'Que signifie "se prendre la tête" ?',
            text: 'Attention, cette expression a plusieurs sens !',
            placeholder: 'Ça peut vouloir dire...',
            required: true,
            minLength: 30,
            maxLength: 600,
            metadata: { expression: 'se prendre la tête', type: 'explanation' }
        },
        {
            id: 'p1_expr3_exemple1',
            type: 'textarea',
            title: 'Donnez un exemple où "se prendre la tête" = se disputer',
            placeholder: 'Par exemple : "..."',
            required: true,
            minLength: 20,
            maxLength: 400,
            metadata: { expression: 'se prendre la tête', type: 'example_dispute' }
        },
        {
            id: 'p1_expr3_exemple2',
            type: 'textarea',
            title: 'Donnez un exemple où "se prendre la tête" = se compliquer la vie',
            placeholder: 'Par exemple : "..."',
            required: true,
            minLength: 20,
            maxLength: 400,
            metadata: { expression: 'se prendre la tête', type: 'example_overthink' }
        },

        // Expression 4 : C'est pas la mer à boire
        {
            id: 'p1_expr4_sens',
            type: 'textarea',
            title: 'Que signifie "c\'est pas la mer à boire" ?',
            placeholder: 'Ça veut dire...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'c\'est pas la mer à boire', type: 'explanation' }
        },
        {
            id: 'p1_expr4_contexte',
            type: 'textarea',
            title: 'Dans quelle situation utiliseriez-vous cette expression ?',
            placeholder: 'Par exemple quand quelqu\'un...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { expression: 'c\'est pas la mer à boire', type: 'context' }
        },

        // Attention check
        {
            id: 'p1_attention_check',
            type: 'radio',
            title: '⚠️ Vérification : Que signifie "poser un lapin" ?',
            required: true,
            options: [
                { value: 'cuisiner', label: 'Cuisiner un lapin' },
                { value: 'rdv', label: 'Ne pas venir à un rendez-vous' },
                { value: 'cadeau', label: 'Offrir un cadeau' },
                { value: 'question', label: 'Poser une question difficile' }
            ],
            metadata: { is_attention_check: true, correct_answer: 'rdv' }
        },

        // Expression libre
        {
            id: 'p1_expr_libre',
            type: 'textarea',
            title: 'Quelle est votre expression française préférée ?',
            text: 'Une expression que vous utilisez souvent ou que vous trouvez particulièrement colorée.',
            placeholder: 'Mon expression préférée c\'est...',
            required: true,
            minLength: 10,
            maxLength: 200,
            metadata: { type: 'user_favorite_expression' }
        },
        {
            id: 'p1_expr_libre_explication',
            type: 'textarea',
            title: 'Expliquez cette expression et donnez un exemple.',
            placeholder: 'Ça veut dire... Par exemple...',
            required: true,
            minLength: 40,
            maxLength: 600,
            metadata: { type: 'user_favorite_explanation' }
        },

        // ============================================================
        // PARTIE 2 : ARGOT ET LANGAGE COURANT
        // ============================================================
        {
            id: 'p2_intro',
            type: 'info',
            title: 'Partie 3/7 : Langage courant',
            text: `
                <p>Le français parlé au quotidien est très différent du français "académique".</p>
                <p><strong>Aidez-nous à comprendre comment les Français parlent vraiment !</strong></p>
            `
        },

        // Terme 1 : Traduire du formel vers l'oral
        {
            id: 'p2_oral1',
            type: 'textarea',
            title: 'Comment diriez-vous "Je ne sais pas" à l\'oral, entre amis ?',
            text: 'Écrivez comme vous le DITES vraiment, pas comme vous l\'écrivez.',
            placeholder: 'Moi je dis plutôt...',
            required: true,
            minLength: 3,
            maxLength: 200,
            metadata: { formal: 'Je ne sais pas', type: 'oral_variant' }
        },
        {
            id: 'p2_oral2',
            type: 'textarea',
            title: 'Comment diriez-vous "Il n\'y a pas de problème" à l\'oral ?',
            placeholder: 'Moi je dis...',
            required: true,
            minLength: 3,
            maxLength: 200,
            metadata: { formal: 'Il n\'y a pas de problème', type: 'oral_variant' }
        },
        {
            id: 'p2_oral3',
            type: 'textarea',
            title: 'Comment diriez-vous "C\'est très bien" à l\'oral ? (donnez plusieurs options si vous en avez)',
            placeholder: 'On peut dire...',
            required: true,
            minLength: 3,
            maxLength: 300,
            metadata: { formal: 'C\'est très bien', type: 'oral_variant' }
        },

        // Argot actuel
        {
            id: 'p2_argot_intro',
            type: 'info',
            text: `
                <p><strong>L'argot actuel :</strong> Ces mots/expressions que vous entendez ou utilisez au quotidien.</p>
            `
        },
        {
            id: 'p2_argot1_connait',
            type: 'radio',
            title: 'Connaissez-vous le mot "chanmé" ?',
            required: true,
            options: [
                { value: 'utilise', label: 'Oui, je l\'utilise' },
                { value: 'connait', label: 'Oui, je connais mais j\'utilise pas' },
                { value: 'entendu', label: 'J\'ai déjà entendu mais pas sûr du sens' },
                { value: 'non', label: 'Non, je ne connais pas' }
            ]
        },
        {
            id: 'p2_argot1_def',
            type: 'textarea',
            title: 'Si vous connaissez "chanmé", expliquez ce que ça veut dire.',
            placeholder: 'Ça veut dire...',
            required: false,
            maxLength: 300,
            showIf: { questionId: 'p2_argot1_connait', values: ['utilise', 'connait'] },
            metadata: { term: 'chanmé', type: 'definition' }
        },
        {
            id: 'p2_argot2_connait',
            type: 'radio',
            title: 'Connaissez-vous le mot "osef" ?',
            required: true,
            options: [
                { value: 'utilise', label: 'Oui, je l\'utilise' },
                { value: 'connait', label: 'Oui, je connais mais j\'utilise pas' },
                { value: 'entendu', label: 'J\'ai déjà entendu mais pas sûr du sens' },
                { value: 'non', label: 'Non, je ne connais pas' }
            ]
        },
        {
            id: 'p2_argot2_def',
            type: 'textarea',
            title: 'Si vous connaissez "osef", expliquez ce que ça veut dire et d\'où ça vient.',
            placeholder: 'Ça veut dire... Ça vient de...',
            required: false,
            maxLength: 300,
            showIf: { questionId: 'p2_argot2_connait', values: ['utilise', 'connait'] },
            metadata: { term: 'osef', type: 'definition' }
        },
        {
            id: 'p2_argot3_connait',
            type: 'radio',
            title: 'Connaissez-vous l\'expression "c\'est le seum" ?',
            required: true,
            options: [
                { value: 'utilise', label: 'Oui, je l\'utilise' },
                { value: 'connait', label: 'Oui, je connais mais j\'utilise pas' },
                { value: 'entendu', label: 'J\'ai déjà entendu mais pas sûr du sens' },
                { value: 'non', label: 'Non, je ne connais pas' }
            ]
        },
        {
            id: 'p2_argot3_exemple',
            type: 'textarea',
            title: 'Si vous connaissez "c\'est le seum", donnez un exemple de situation où on l\'utilise.',
            placeholder: 'On dit ça quand...',
            required: false,
            maxLength: 400,
            showIf: { questionId: 'p2_argot3_connait', values: ['utilise', 'connait'] },
            metadata: { term: 'c\'est le seum', type: 'example' }
        },

        // Mots/expressions que le participant utilise
        {
            id: 'p2_argot_perso',
            type: 'textarea',
            title: 'Quels mots ou expressions d\'argot utilisez-vous régulièrement ?',
            text: 'Listez 3 à 5 mots/expressions avec leur signification.',
            placeholder: '- "..." = ...\n- "..." = ...\n- "..." = ...',
            required: true,
            minLength: 50,
            maxLength: 800,
            metadata: { type: 'user_slang_vocabulary' }
        },

        // ============================================================
        // PARTIE 3 : RÉFÉRENCES CULTURELLES
        // ============================================================
        {
            id: 'p3_intro',
            type: 'info',
            title: 'Partie 4/7 : Références culturelles',
            text: `
                <p>Les Français partagent des <strong>références communes</strong> (films, émissions, personnalités) que les IA étrangères ne comprennent souvent pas.</p>
            `
        },

        // Kaamelott
        {
            id: 'p3_kaamelott',
            type: 'radio',
            title: 'Connaissez-vous la série "Kaamelott" ?',
            required: true,
            options: [
                { value: 'fan', label: 'Oui, je suis fan !' },
                { value: 'connait', label: 'Oui, j\'ai vu quelques épisodes' },
                { value: 'nom', label: 'Oui, de nom, mais jamais vu' },
                { value: 'non', label: 'Non, je ne connais pas' }
            ]
        },
        {
            id: 'p3_kaamelott_replique',
            type: 'textarea',
            title: 'Citez une réplique culte de Kaamelott (si vous en connaissez).',
            placeholder: '« ... »',
            required: false,
            maxLength: 300,
            showIf: { questionId: 'p3_kaamelott', values: ['fan', 'connait'] },
            metadata: { reference: 'kaamelott', type: 'quote' }
        },
        {
            id: 'p3_kaamelott_contexte',
            type: 'textarea',
            title: 'Dans quelle situation réutiliseriez-vous cette réplique dans la vie ?',
            placeholder: 'On peut la sortir quand...',
            required: false,
            maxLength: 400,
            showIf: { questionId: 'p3_kaamelott', values: ['fan', 'connait'] },
            metadata: { reference: 'kaamelott', type: 'usage_context' }
        },

        // OSS 117
        {
            id: 'p3_oss117',
            type: 'radio',
            title: 'Connaissez-vous les films "OSS 117" ?',
            required: true,
            options: [
                { value: 'fan', label: 'Oui, je les adore !' },
                { value: 'vu', label: 'Oui, j\'ai vu au moins un' },
                { value: 'nom', label: 'De nom seulement' },
                { value: 'non', label: 'Non' }
            ]
        },
        {
            id: 'p3_oss117_replique',
            type: 'textarea',
            title: 'Citez une réplique culte d\'OSS 117 (si vous en connaissez).',
            placeholder: '« ... »',
            required: false,
            maxLength: 300,
            showIf: { questionId: 'p3_oss117', values: ['fan', 'vu'] },
            metadata: { reference: 'oss117', type: 'quote' }
        },

        // Les Inconnus / Les Nuls
        {
            id: 'p3_inconnus',
            type: 'radio',
            title: 'Connaissez-vous les sketchs des Inconnus ou des Nuls ?',
            required: true,
            options: [
                { value: 'fan', label: 'Oui, ce sont des classiques !' },
                { value: 'quelques', label: 'Oui, j\'en connais quelques-uns' },
                { value: 'nom', label: 'De nom seulement' },
                { value: 'non', label: 'Non, je ne connais pas' }
            ]
        },
        {
            id: 'p3_inconnus_reference',
            type: 'textarea',
            title: 'Citez un sketch ou une réplique que vous trouvez culte.',
            placeholder: 'Le sketch de... ou la phrase...',
            required: false,
            maxLength: 400,
            showIf: { questionId: 'p3_inconnus', values: ['fan', 'quelques'] },
            metadata: { reference: 'les_inconnus_nuls', type: 'quote' }
        },

        // Référence libre
        {
            id: 'p3_ref_libre',
            type: 'textarea',
            title: 'Quelle référence culturelle française (film, série, émission, chanson, personnalité) pensez-vous que TOUT Français connaît ?',
            placeholder: 'Je pense que tous les Français connaissent...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { type: 'user_cultural_reference' }
        },
        {
            id: 'p3_ref_libre_explication',
            type: 'textarea',
            title: 'Expliquez pourquoi c\'est une référence si connue et comment elle est utilisée.',
            placeholder: 'C\'est connu parce que... On l\'utilise pour...',
            required: true,
            minLength: 40,
            maxLength: 600,
            metadata: { type: 'user_reference_explanation' }
        },

        // ============================================================
        // PARTIE 4 : CODES SOCIAUX IMPLICITES
        // ============================================================
        {
            id: 'p4_intro',
            type: 'info',
            title: 'Partie 5/7 : Codes sociaux',
            text: `
                <p>Les Français ont des <strong>"règles non écrites"</strong> que les étrangers et les IA ne comprennent pas toujours.</p>
                <p>Aidez-nous à les expliciter !</p>
            `
        },

        // La bise
        {
            id: 'p4_bise',
            type: 'textarea',
            title: 'Expliquez les règles de la bise à un étranger.',
            text: 'Qui fait la bise à qui ? Combien ? Quand est-ce approprié ou non ?',
            placeholder: 'En France, on fait la bise...',
            required: true,
            minLength: 80,
            maxLength: 800,
            metadata: { social_code: 'la_bise', type: 'explanation' }
        },
        {
            id: 'p4_bise_nombre',
            type: 'radio',
            title: 'Combien de bises faites-vous habituellement ?',
            required: true,
            options: [
                { value: '1', label: '1 bise' },
                { value: '2', label: '2 bises' },
                { value: '3', label: '3 bises' },
                { value: '4', label: '4 bises' },
                { value: 'variable', label: 'Ça dépend (région, personne)' }
            ],
            metadata: { social_code: 'la_bise', type: 'regional_variant' }
        },

        // Le vouvoiement
        {
            id: 'p4_vouvoiement',
            type: 'textarea',
            title: 'Expliquez les règles du tutoiement/vouvoiement à un étranger.',
            text: 'Quand vouvoie-t-on ? Quand tutoie-t-on ? Comment passe-t-on du vous au tu ?',
            placeholder: 'En France, on vouvoie...',
            required: true,
            minLength: 80,
            maxLength: 800,
            metadata: { social_code: 'vouvoiement', type: 'explanation' }
        },
        {
            id: 'p4_vouvoiement_situation',
            type: 'textarea',
            title: 'Racontez une situation où le choix entre tu/vous était délicat.',
            placeholder: 'Une fois, je ne savais pas si...',
            required: false,
            maxLength: 500,
            metadata: { social_code: 'vouvoiement', type: 'anecdote' }
        },

        // À table
        {
            id: 'p4_table',
            type: 'textarea',
            title: 'Quelles sont les règles de politesse à table en France ?',
            text: 'Les choses à faire et à ne pas faire.',
            placeholder: 'À table, en France, on doit...',
            required: true,
            minLength: 60,
            maxLength: 700,
            metadata: { social_code: 'a_table', type: 'rules' }
        },

        // L'apéro
        {
            id: 'p4_apero',
            type: 'textarea',
            title: 'Expliquez le concept de "l\'apéro" à un étranger.',
            text: 'C\'est quoi ? Ça se passe comment ? Qu\'est-ce qu\'on sert ?',
            placeholder: 'L\'apéro, c\'est...',
            required: true,
            minLength: 60,
            maxLength: 700,
            metadata: { social_code: 'apero', type: 'explanation' }
        },

        // Attention check
        {
            id: 'p4_attention_check',
            type: 'radio',
            title: '⚠️ Vérification : Sélectionnez "Trois"',
            required: true,
            options: [
                { value: 'un', label: 'Un' },
                { value: 'deux', label: 'Deux' },
                { value: 'trois', label: 'Trois' },
                { value: 'quatre', label: 'Quatre' }
            ],
            metadata: { is_attention_check: true, correct_answer: 'trois' }
        },

        // ============================================================
        // PARTIE 5 : QUESTIONS D'ÉTRANGERS
        // ============================================================
        {
            id: 'p5_intro',
            type: 'info',
            title: 'Partie 6/7 : Expliquer la France',
            text: `
                <p>Imaginez qu'un ami étranger vous pose ces questions sur la France.</p>
                <p><strong>Répondez naturellement, comme vous le feriez vraiment.</strong></p>
            `
        },

        // Question 1 : Grèves
        {
            id: 'p5_greves',
            type: 'textarea',
            title: '❓ "Pourquoi il y a autant de grèves en France ?"',
            text: 'Un ami américain vous pose cette question. Que lui répondez-vous ?',
            placeholder: 'Alors, en France, les grèves c\'est...',
            required: true,
            minLength: 80,
            maxLength: 1000,
            metadata: { question_type: 'foreigner_question', topic: 'strikes' }
        },

        // Question 2 : Repas
        {
            id: 'p5_repas',
            type: 'textarea',
            title: '❓ "Pourquoi les Français passent autant de temps à table ?"',
            placeholder: 'C\'est parce que pour nous...',
            required: true,
            minLength: 60,
            maxLength: 800,
            metadata: { question_type: 'foreigner_question', topic: 'meals' }
        },

        // Question 3 : Râler
        {
            id: 'p5_raler',
            type: 'textarea',
            title: '❓ "On dit que les Français râlent tout le temps, c\'est vrai ?"',
            placeholder: 'Alors oui et non... En fait...',
            required: true,
            minLength: 60,
            maxLength: 800,
            metadata: { question_type: 'foreigner_question', topic: 'complaining' }
        },

        // Question 4 : Au choix
        {
            id: 'p5_question_libre',
            type: 'textarea',
            title: 'Quelle question sur la France un étranger vous a-t-il déjà posée (ou pourrait vous poser) ?',
            placeholder: 'On m\'a déjà demandé...',
            required: true,
            minLength: 20,
            maxLength: 400,
            metadata: { question_type: 'user_foreigner_question' }
        },
        {
            id: 'p5_question_libre_reponse',
            type: 'textarea',
            title: 'Et qu\'avez-vous répondu (ou que répondriez-vous) ?',
            placeholder: 'Je lui ai expliqué que...',
            required: true,
            minLength: 60,
            maxLength: 800,
            metadata: { question_type: 'user_foreigner_answer' }
        },

        // ============================================================
        // PARTIE 6 : RÉGIONALISMES
        // ============================================================
        {
            id: 'p6_intro',
            type: 'info',
            title: 'Partie 7/7 : Votre région',
            text: `
                <p>Le français varie selon les régions !</p>
                <p>Partagez les expressions ou mots typiques de votre région.</p>
            `
        },

        {
            id: 'p6_chocolatine',
            type: 'radio',
            title: 'Comment appelez-vous la viennoiserie au chocolat ?',
            required: true,
            options: [
                { value: 'pain_chocolat', label: 'Pain au chocolat' },
                { value: 'chocolatine', label: 'Chocolatine' },
                { value: 'croissant_chocolat', label: 'Croissant au chocolat' },
                { value: 'petit_pain', label: 'Petit pain au chocolat' },
                { value: 'autre', label: 'Autre' }
            ],
            metadata: { type: 'regional_lexical_variant' }
        },
        {
            id: 'p6_serpillere',
            type: 'radio',
            title: 'Comment appelez-vous le tissu pour laver le sol ?',
            required: true,
            options: [
                { value: 'serpillere', label: 'Serpillère' },
                { value: 'wassingue', label: 'Wassingue' },
                { value: 'panosse', label: 'Panosse' },
                { value: 'toile', label: 'Toile / Torchon' },
                { value: 'cinse', label: 'Cinse' },
                { value: 'autre', label: 'Autre' }
            ],
            metadata: { type: 'regional_lexical_variant' }
        },
        {
            id: 'p6_poche_sac',
            type: 'radio',
            title: 'Comment appelez-vous le sac en plastique du supermarché ?',
            required: true,
            options: [
                { value: 'sac_plastique', label: 'Sac plastique' },
                { value: 'poche', label: 'Poche' },
                { value: 'sachet', label: 'Sachet' },
                { value: 'cornet', label: 'Cornet' },
                { value: 'autre', label: 'Autre' }
            ],
            metadata: { type: 'regional_lexical_variant' }
        },

        {
            id: 'p6_expression_regionale',
            type: 'textarea',
            title: 'Quelle expression ou mot typique de votre région utilisez-vous ?',
            text: 'Une expression que les gens d\'autres régions ne connaissent pas forcément.',
            placeholder: 'Chez nous on dit...',
            required: true,
            minLength: 20,
            maxLength: 500,
            metadata: { type: 'user_regional_expression' }
        },
        {
            id: 'p6_expression_regionale_sens',
            type: 'textarea',
            title: 'Que signifie cette expression et dans quel contexte l\'utilisez-vous ?',
            placeholder: 'Ça veut dire... On l\'utilise quand...',
            required: true,
            minLength: 30,
            maxLength: 500,
            metadata: { type: 'user_regional_expression_meaning' }
        },

        {
            id: 'p6_accent',
            type: 'textarea',
            title: 'Comment décririez-vous l\'accent de votre région à quelqu\'un qui ne le connaît pas ?',
            placeholder: 'L\'accent de chez nous, c\'est...',
            required: false,
            maxLength: 500,
            metadata: { type: 'accent_description' }
        },

        // ============================================================
        // FEEDBACK
        // ============================================================
        {
            id: 'p7_difficulte',
            type: 'radio',
            title: 'Comment avez-vous trouvé ce questionnaire ?',
            required: true,
            options: [
                { value: 'tres_facile', label: 'Très facile et agréable' },
                { value: 'facile', label: 'Facile' },
                { value: 'normal', label: 'Normal' },
                { value: 'difficile', label: 'Certaines questions étaient difficiles' },
                { value: 'long', label: 'Trop long' }
            ]
        },
        {
            id: 'p7_commentaire',
            type: 'textarea',
            title: 'Un dernier commentaire ? Quelque chose qu\'on aurait dû demander ?',
            placeholder: 'Optionnel...',
            required: false,
            maxLength: 800
        },
        {
            id: 'p7_recontact',
            type: 'radio',
            title: 'Accepteriez-vous de participer à d\'autres questionnaires ?',
            required: true,
            options: [
                { value: 'oui', label: 'Oui, avec plaisir' },
                { value: 'si_remunere', label: 'Oui, si c\'est rémunéré' },
                { value: 'non', label: 'Non merci' }
            ]
        }
    ]
};
