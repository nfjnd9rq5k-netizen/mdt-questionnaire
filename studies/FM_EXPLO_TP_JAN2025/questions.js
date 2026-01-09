
const STUDY_CONFIG = {
    
    studyId: 'FM_EXPLO_TP_JAN2025',
    studyTitle: 'Etude Fleury Michon',
    studyDate: 'Janvier 2025',
    reward: '50 euros',
    duration: '2h30',
    horaires: ['Jeudi 15/01 - 14h', 'Mardi 20/01 - 14h'],
    
    requireAccessId: true,
    
    objectifs: {
        totalParticipants: 20,
        
        quotas: [
            {
                id: 'sexe',
                titre: 'Sexe',
                source: 'q3a',
                criteres: [
                    { valeur: 'homme', label: 'Hommes', objectif: 10 },
                    { valeur: 'femme', label: 'Femmes', objectif: 10 }
                ]
            },
            {
                id: 'enfants',
                titre: 'Enfants',
                source: 'q4a',
                criteres: [
                    { valeur: 'avec_enfants', label: 'Avec enfants', objectif: 14 },
                    { valeur: 'sans_enfant', label: 'Sans enfants', objectif: 6 }
                ]
            },
            {
                id: 'marque',
                titre: 'Marque',
                source: 'q9',
                criteres: [
                    { valeur: 'fleury_michon', label: 'Fleury Michon', objectif: 10 },
                    { valeur: 'autre_nationale', label: 'Autres', objectif: 10 }
                ]
            }
        ]
    },
    
    questions: [
        {
            id: 'q1',
            title: 'Secteurs',
            question: 'Vous ou un proche travaillez-vous dans un de ces secteurs ?',
            type: 'multiple',
            options: [
                { value: 'marketing', label: 'Marketing', stop: true },
                { value: 'journalisme', label: 'Journalisme, presse, TV', stop: true },
                { value: 'etudes', label: 'Etudes de marche', stop: true },
                { value: 'rp', label: 'Relations publiques', stop: true },
                { value: 'alimentaire', label: 'Industrie alimentaire', stop: true },
                { value: 'gms', label: 'Grande distribution', stop: true },
                { value: 'medical', label: 'Secteur medical', stop: true },
                { value: 'design', label: 'Design / Packaging', stop: true },
                { value: 'restauration', label: 'Restauration', stop: true },
                { value: 'publicite', label: 'Publicite, communication', stop: true },
                { value: 'aucun', label: 'Aucun de ces secteurs', stop: false, exclusive: true }
            ]
        },
        {
            id: 'q2',
            title: 'Participation',
            question: 'Avez-vous participe a une etude au cours des 6 derniers mois ?',
            type: 'single',
            options: [
                { value: 'oui', label: 'Oui', stop: true },
                { value: 'non', label: 'Non', stop: false }
            ]
        },
        {
            id: 'q3a',
            title: 'Genre',
            question: 'Vous etes :',
            type: 'single',
            options: [
                { value: 'homme', label: 'Un homme', stop: false },
                { value: 'femme', label: 'Une femme', stop: false }
            ]
        },
        {
            id: 'q3b',
            title: 'Residence',
            question: 'Ou habitez-vous ?',
            type: 'single',
            options: [
                { value: 'paris', label: 'Paris intra-muros', stop: false },
                { value: 'petite_couronne', label: 'Petite couronne (92, 93, 94)', stop: false },
                { value: 'grande_couronne', label: 'Grande couronne (77, 78, 91, 95)', stop: false },
                { value: 'autre', label: 'Autre region', stop: true }
            ]
        },
        {
            id: 'q3c',
            title: 'Age',
            question: 'Quel age avez-vous ?',
            type: 'number',
            min: 18,
            max: 99,
            suffix: 'ans'
        },
        {
            id: 'q4a',
            title: 'Enfants',
            question: 'Avez-vous des enfants de 5 a 15 ans au foyer ?',
            type: 'single',
            options: [
                { value: 'avec_enfants', label: 'Oui', stop: false },
                { value: 'sans_enfant', label: 'Non', stop: false }
            ]
        },
        {
            id: 'q5a',
            title: 'Statut',
            question: 'Quel est votre statut professionnel ?',
            type: 'single',
            options: [
                { value: 'plein_temps', label: 'Temps plein', stop: false },
                { value: 'temps_partiel', label: 'Temps partiel', stop: false },
                { value: 'foyer', label: 'Au foyer', stop: false },
                { value: 'etudiant', label: 'Etudiant', stop: true },
                { value: 'retraite', label: 'Retraite', stop: true },
                { value: 'chomage', label: 'Recherche emploi', stop: false }
            ]
        },
        {
            id: 'q5b',
            title: 'Revenus',
            question: 'Tranche de revenus annuels de votre foyer ?',
            type: 'single',
            options: [
                { value: 'moins_20k', label: 'Moins de 20 000 euros', stop: false },
                { value: '20k_35k', label: '20 000 - 35 000 euros', stop: false },
                { value: '35k_50k', label: '35 000 - 50 000 euros', stop: false },
                { value: '50k_70k', label: '50 000 - 70 000 euros', stop: false },
                { value: 'plus_70k', label: 'Plus de 70 000 euros', stop: true },
                { value: 'nsp', label: 'Ne souhaite pas repondre', stop: false }
            ]
        },
        {
            id: 'q6a',
            title: 'Achats',
            question: 'Etes-vous responsable des achats alimentaires ?',
            type: 'single',
            options: [
                { value: 'moi', label: 'Oui, principalement moi', stop: false },
                { value: 'partage', label: 'Oui, partage', stop: false },
                { value: 'autre', label: 'Non', stop: true }
            ]
        },
        {
            id: 'q6b',
            title: 'Lieu achats',
            question: 'Ou faites-vous vos courses le plus souvent ?',
            type: 'single',
            options: [
                { value: 'supermarche', label: 'Supermarches', stop: false },
                { value: 'hypermarche', label: 'Hypermarches', stop: false },
                { value: 'drive', label: 'Drive', stop: false },
                { value: 'hard_discount', label: 'Hard Discount (Lidl, Aldi)', stop: true },
                { value: 'superette', label: 'Superettes', stop: true }
            ]
        },
        {
            id: 'q7',
            title: 'Jambon',
            question: 'Consommez-vous du jambon blanc de porc ?',
            type: 'single',
            options: [
                { value: 'oui', label: 'Oui', stop: false },
                { value: 'non', label: 'Non', stop: true }
            ]
        },
        {
            id: 'q8a',
            title: 'Achat jambon',
            question: 'Ou achetez-vous votre jambon le plus souvent ?',
            type: 'single',
            options: [
                { value: 'boucherie', label: 'Boucherie traditionnelle', stop: true },
                { value: 'rayon_boucherie', label: 'Rayon boucherie supermarche', stop: false },
                { value: 'libre_service', label: 'Rayon libre-service', stop: false }
            ]
        },
        {
            id: 'q8b',
            title: 'Frequence',
            question: 'Frequence d\'achat du jambon ?',
            type: 'single',
            options: [
                { value: 'semaine', label: '1 fois par semaine ou plus', stop: false },
                { value: 'mois', label: '1 fois par mois', stop: false },
                { value: '2_3_mois', label: 'Tous les 2-3 mois', stop: true },
                { value: 'moins', label: 'Moins souvent', stop: true }
            ]
        },
        {
            id: 'q9',
            title: 'Marque',
            question: 'Quelle marque achetez-vous le plus souvent ?',
            type: 'single',
            options: [
                { value: 'fleury_michon', label: 'Fleury Michon', stop: false },
                { value: 'herta', label: 'Herta', stop: false },
                { value: 'madrange', label: 'Madrange', stop: false },
                { value: 'broceliande', label: 'Broceliande', stop: false },
                { value: 'aoste', label: 'Aoste', stop: false },
                { value: 'autre_nationale', label: 'Autre marque nationale', stop: false },
                { value: 'mdd', label: 'Marque distributeur', stop: true }
            ]
        },
        {
            id: 'q10',
            title: 'Gammes',
            question: 'Quelles gammes consommez-vous ?',
            type: 'multiple',
            options: [
                { value: 'classique', label: 'Classique', stop: false },
                { value: 'reduit_sel', label: 'Reduit en sel', stop: false },
                { value: 'braise_fume', label: 'Braise ou fume', stop: false },
                { value: 'bio_lr', label: 'Bio / Label Rouge', stop: false }
            ]
        },
        {
            id: 'q11',
            title: 'Consommation',
            question: 'Comment consommez-vous le jambon ?',
            type: 'single',
            options: [
                { value: 'tel_quel', label: 'Tel quel', stop: false },
                { value: 'preparation', label: 'En preparation', stop: false },
                { value: 'les_deux', label: 'Les deux', stop: false }
            ]
        },
        {
            id: 'q12',
            title: 'Marques refusees',
            question: 'Quelles marques refuseriez-vous d\'acheter ?',
            type: 'multiple',
            options: [
                { value: 'fleury_michon', label: 'Fleury Michon', stop: true },
                { value: 'madrange', label: 'Madrange', stop: false },
                { value: 'herta', label: 'Herta', stop: false },
                { value: 'broceliande', label: 'Broceliande', stop: false },
                { value: 'aucune', label: 'Aucune', stop: false, exclusive: true }
            ]
        },
        {
            id: 'q13',
            title: 'Produits mer',
            question: 'Consommez-vous des produits de la mer ?',
            type: 'single',
            options: [
                { value: 'regulier', label: 'Regulierement', stop: false },
                { value: 'occasionnel', label: 'Occasionnellement', stop: false },
                { value: 'pourrait', label: 'Pas encore mais possible', stop: false },
                { value: 'jamais', label: 'Jamais', stop: true }
            ]
        },
        {
            id: 'q14',
            title: 'Autres produits',
            question: 'Consommez-vous aussi ces produits ?',
            type: 'multiple',
            options: [
                { value: 'jambon_volaille', label: 'Jambon de volaille', stop: false },
                { value: 'tranches_vege', label: 'Tranches vegetales', stop: false },
                { value: 'aucun', label: 'Aucun', stop: false, exclusive: true }
            ]
        }
    ]
};
