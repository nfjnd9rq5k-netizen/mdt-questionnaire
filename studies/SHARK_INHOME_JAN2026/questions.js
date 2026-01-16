
const STUDY_CONFIG = {
    
    
    studyId: 'SHARK_INHOME_JAN2026',
    studyTitle: 'SHARK InHome - Aspirateurs',
    studyDate: 'Mardi 13 Janvier 2026',
    reward: '40€',
    duration: '60 min',
    horaires: ['9h', '11h00', '13h', '15h', '16h45'],
    
    requireAccessId: true,
    
    
    objectifs: {
        totalParticipants: 5,
        
        quotas: [
            {
                id: 'sexe',
                titre: '👤 Répartition par sexe',
                source: 'q4', 
                criteres: [
                    { valeur: 'homme', label: 'Hommes', objectif: 2 },
                    { valeur: 'femme', label: 'Femmes', objectif: 3 }
                ]
            },
            {
                id: 'age',
                titre: '🎂 Répartition par âge',
                source: 'q5',
                type: 'tranche', 
                criteres: [
                    { min: 25, max: 35, label: '25-35 ans', objectif: 1 },
                    { min: 36, max: 45, label: '36-45 ans', objectif: 1 },
                    { min: 46, max: 55, label: '46-55 ans', objectif: 1 },
                    { min: 56, max: 65, label: '56-65 ans', objectif: 1 }
                ]
            },
            {
                id: 'enfants_animaux',
                titre: '🏠 Foyer (enfants & animaux)',
                type: 'combine', 
                sources: ['q6', 'q7'],
                criteres: [
                    { 
                        id: 'animaux_et_enfants',
                        label: 'Avec animaux ET enfants -18 ans', 
                        objectif: 2,
                        condition: (reponses) => {
                            return aEnfants && aAnimaux;
                        }
                    },
                    { 
                        id: 'animaux_ou_enfants',
                        label: 'Animaux OU enfants (pas les deux)', 
                        objectif: 2,
                        condition: (reponses) => {
                            return (aEnfants || aAnimaux) && !(aEnfants && aAnimaux);
                        }
                    },
                    { 
                        id: 'ni_animaux_ni_enfants',
                        label: 'Ni animaux ni enfants', 
                        objectif: 1,
                        condition: (reponses) => {
                            return !aEnfants && !aAnimaux;
                        }
                    }
                ]
            },
            {
                id: 'possesseur_2en1',
                titre: '🧹 Possesseur aspirateur 2en1',
                source: 'q10',
                type: 'contains', 
                criteres: [
                    { valeur: 'aspirateur_2en1', label: 'Possesseurs', objectif: 2, present: true },
                    { valeur: 'aspirateur_2en1', label: 'Non possesseurs', objectif: 3, present: false }
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
            note: "AUCUN NE TRAVAILLE DANS L'UN DES SECTEURS CI-DESSUS SINON STOP",
            options: [
                { value: 'publicite', label: 'Publicité', stop: true },
                { value: 'relations_publiques', label: 'Relations publiques', stop: true },
                { value: 'journalisme', label: 'Journalisme', stop: true },
                { value: 'electromenager', label: 'Fabrication ou vente de produits électroménager ou ménager', stop: true },
                { value: 'etudes_marche', label: 'Études de marché', stop: true },
                { value: 'marketing', label: 'Marketing', stop: true },
                { value: 'supermarche', label: 'Hypermarché/Supermarché', stop: true },
                { value: 'aucun', label: 'Aucun de ces secteurs', stop: false, exclusive: true }
            ]
        },

        {
            id: 'q2',
            title: 'Participation récente',
            question: 'Avez-vous participé à un entretien ou une réunion de consommateurs au cours des 6 derniers mois ?',
            type: 'single',
            note: 'STOP si participation au cours des 6 derniers mois',
            options: [
                { value: 'oui', label: 'Oui', stop: true },
                { value: 'non', label: 'Non', stop: false }
            ]
        },

        {
            id: 'q3',
            title: 'Études aspirateurs',
            question: 'Avez-vous déjà participé à un entretien ou une étude portant sur les aspirateurs au cours des 12 derniers mois ?',
            type: 'single',
            note: "Aucun n'a déjà participé à ce type d'études",
            options: [
                { value: 'oui', label: 'Oui', stop: true },
                { value: 'non', label: 'Non', stop: false }
            ]
        },

        {
            id: 'q4',
            title: 'Sexe',
            question: 'Vous êtes :',
            type: 'single',
            note: "Pas de quotas – essayer d'avoir 2 hommes",
            options: [
                { value: 'homme', label: 'Un homme', stop: false },
                { value: 'femme', label: 'Une femme', stop: false },
                { value: 'autre', label: 'Autre', stop: false },
                { value: 'non_precise', label: 'Je ne souhaite pas préciser', stop: false }
            ]
        },

        {
            id: 'q5',
            title: 'Âge',
            question: 'Quel âge avez-vous ?',
            type: 'number',
            min: 18,
            max: 100,
            suffix: 'ans',
            note: 'STOP si moins de 25 ans ou plus de 65 ans',
            validation: (val) => {
                if (val < 25 || val > 65) {
                    return { stop: true, reason: 'Âge hors critères (25-65 ans requis)' };
                }
                return { stop: false };
            }
        },

        {
            id: 'q6',
            title: 'Enfants au foyer',
            question: 'Avez-vous des enfants de moins de 18 ans au foyer ?',
            type: 'single_with_text',
            note: 'VOIR QUOTAS ENFANTS/ANIMAUX',
            options: [
                { value: 'oui_moins_18', label: 'Oui', stop: false, needsText: true, textLabel: 'Préciser les âges' },
                { value: 'oui_plus_ages', label: "Non, j'ai des enfants plus âgés", stop: false, needsText: true, textLabel: 'Préciser les âges' },
                { value: 'partis', label: 'Non, les enfants sont partis du foyer', stop: false },
                { value: 'aucun', label: 'Non, aucun enfant', stop: false }
            ]
        },

        {
            id: 'q7',
            title: 'Animaux domestiques',
            question: 'Avez-vous des chiens ou chats à la maison ?',
            type: 'multiple_with_text',
            note: 'VOIR QUOTAS ENFANTS/ANIMAUX',
            options: [
                { value: 'chats', label: "Oui, j'ai un ou plusieurs chats", stop: false, needsText: true, textLabel: 'Combien ?' },
                { value: 'chiens', label: "Oui, j'ai un ou plusieurs chiens", stop: false, needsText: true, textLabel: 'Combien ?' },
                { value: 'aucun', label: "Non, je n'ai ni chien ni chat", stop: false, exclusive: true }
            ]
        },

        {
            id: 'q8',
            title: 'Rôle décisionnel',
            question: 'Quel est votre rôle dans le choix des appareils ménagers (aspirateurs, balais, nettoyeurs...) pour votre foyer ?',
            type: 'single',
            note: "Tous au moins conjointement responsable du choix et de l'achat",
            options: [
                { value: 'principal', label: 'Je suis le/la principal(e) décisionnaire pour ces achats', stop: false },
                { value: 'influence', label: "J'ai une certaine influence, mais c'est quelqu'un d'autre qui décide", stop: false },
                { value: 'non_participe', label: 'Je ne participe généralement pas à ces décisions', stop: true }
            ]
        },

        {
            id: 'q9',
            title: 'Utilisation appareils',
            question: "Quelle affirmation décrit le mieux l'utilisation des appareils de nettoyage des sols dans votre foyer ?",
            type: 'single',
            note: 'Tous au moins conjointement responsable sinon STOP',
            options: [
                { value: 'principal', label: "Je suis l'utilisateur principal des appareils de nettoyage des sols", stop: false },
                { value: 'partage', label: 'Je partage cette tâche à part égale avec une autre personne', stop: false },
                { value: 'non_utilise', label: "Je n'utilise pas les appareils de nettoyage des sols", stop: true }
            ]
        },

        {
            id: 'q10',
            title: 'Outils nettoyage sols',
            question: 'Quels outils utilisez-vous actuellement pour nettoyer vos sols durs ? (Plusieurs réponses possibles)',
            type: 'multiple_with_brands',
            note: 'QUOTAS: 2 possesseurs aspirateur 2en1 | 3 non possesseurs',
            options: [
                { value: 'balai', label: 'Balai', stop: false },
                { value: 'balai_vapeur', label: 'Balai vapeur', stop: false },
                { value: 'balai_electrique', label: 'Balai électrique (sans vapeur)', stop: false },
                { value: 'aspirateur_2en1', label: 'Aspirateur balai 2 en 1 [eau et poussière]', stop: false, needsBrand: true },
                { value: 'aspirateur_traineau', label: 'Aspirateur traineau', stop: false },
                { value: 'aspirateur_balai', label: 'Aspirateur balai avec ou sans fil', stop: false },
                { value: 'balai_brosse', label: 'Balai brosse standard avec seau et serpillère', stop: false },
                { value: 'balai_depoussiérant', label: 'Balai dépoussiérant avec lingettes sèches', stop: false },
                { value: 'balai_lingettes_humides', label: 'Balai avec lingettes humides ou serpillère microfibre', stop: false },
                { value: 'balai_pulverisateur', label: 'Balai avec pulvérisateur et serpillère microfibre', stop: false },
                { value: 'robot_lavant', label: 'Aspirateur robot lavant', stop: false },
                { value: 'autre', label: 'Autre', stop: false, needsText: true, textLabel: 'Préciser' },
                { value: 'rien', label: "Je n'utilise rien pour nettoyer mes sols durs", stop: true, exclusive: true }
            ]
        },

        {
            id: 'q11',
            title: "Intention d'achat",
            question: "Concernant l'aspirateur balai 2 en 1 pour sols durs (humides/secs), quelle est votre situation ?",
            type: 'single',
            note: 'QUOTAS: 2 possesseurs envisageant de changer | 3 non possesseurs intéressés',
            options: [
                { value: 'possede_remplacer', label: 'Je possède ce produit ET je souhaite ou envisage de le remplacer', stop: false },
                { value: 'possede_garder', label: 'Je possède ce produit et je ne souhaite PAS le remplacer', stop: true },
                { value: 'pas_possede_interesse', label: "Je ne possède pas ce produit, mais je pense qu'il serait utile chez moi", stop: false },
                { value: 'pas_interesse', label: "Je ne possède pas ce produit et il ne m'intéresse pas", stop: true }
            ]
        },

        {
            id: 'q12',
            title: 'Intérêt produit',
            question: "En ce qui concerne ce type de produit, quel serait votre intérêt pour l'acheter si son prix était à 449€ ?",
            type: 'single',
            note: 'TERMINER si neutre ou pas intéressé',
            options: [
                { value: 'tres_interesse', label: 'Très intéressé', stop: false },
                { value: 'plutot_interesse', label: 'Plutôt intéressé', stop: false },
                { value: 'neutre', label: 'Ni intéressé ni désintéressé', stop: true },
                { value: 'plutot_pas', label: 'Plutôt pas intéressé', stop: true },
                { value: 'pas_du_tout', label: 'Pas du tout intéressé', stop: true }
            ]
        },

        {
            id: 'q13',
            title: 'Sols durs',
            question: 'Quel pourcentage de votre foyer est recouvert de sols durs (parquet, carrelage, lino...) ?',
            type: 'single',
            note: 'STOP si moins de 50%',
            options: [
                { value: '1-39', label: '1% - 39%', stop: true },
                { value: '40-49', label: '40% - 49%', stop: true },
                { value: '50-59', label: '50% - 59%', stop: false },
                { value: '60-69', label: '60% - 69%', stop: false },
                { value: '70-100', label: '70% - 100%', stop: false }
            ]
        },

        {
            id: 'q14',
            title: 'Type de logement',
            question: 'Dans quel type de logement vivez-vous ?',
            type: 'multiple',
            options: [
                { value: 'maison_plain_pied', label: 'Maison de plain-pied', stop: false },
                { value: 'maison_etage', label: 'Maison avec au moins 1 étage', stop: false },
                { value: 'appartement', label: 'Appartement (plain-pied)', stop: false },
                { value: 'appartement_duplex', label: 'Appartement duplex/triplex', stop: false }
            ]
        },

        {
            id: 'q15',
            title: 'Superficie',
            question: 'Quelle est la superficie de votre foyer ?',
            type: 'single_with_text',
            needsExactValue: true,
            exactValueLabel: 'Superficie exacte en m² (optionnel)',
            note: 'STOP si moins de 140m²',
            options: [
                { value: 'moins_100', label: 'Moins de 100m²', stop: true },
                { value: '100-139', label: 'De 100 à 139 m²', stop: true },
                { value: '140-189', label: 'De 140 à 189 m²', stop: false },
                { value: '190-239', label: 'De 190 à 239 m²', stop: false },
                { value: '240-289', label: 'De 240 à 289 m²', stop: false },
                { value: '290-369', label: 'De 290 à 369 m²', stop: false },
                { value: '370_plus', label: '370 m² et plus', stop: false }
            ]
        },

        {
            id: 'q16',
            title: 'Statut logement',
            question: 'Êtes-vous propriétaire ou locataire de ce foyer ?',
            type: 'single',
            note: 'PAS DE QUOTAS',
            options: [
                { value: 'proprietaire', label: 'Je suis propriétaire', stop: false },
                { value: 'locataire', label: 'Je suis locataire', stop: false }
            ]
        },

        {
            id: 'q17',
            title: 'Marques',
            question: "Parmi ces marques, y en a-t-il une que vous n'achèteriez en aucun cas ?",
            type: 'multiple',
            note: '⚠️ STOP SI SHARK CITÉ',
            options: [
                { value: 'bosch', label: 'Bosch', stop: false },
                { value: 'rowenta', label: 'Rowenta', stop: false },
                { value: 'dyson', label: 'Dyson', stop: false },
                { value: 'miele', label: 'Miele', stop: false },
                { value: 'shark', label: 'Shark', stop: true },
                { value: 'electrolux', label: 'Electrolux', stop: false },
                { value: 'aucune', label: 'Je ne rejette aucune marque', stop: false, exclusive: true }
            ]
        },

        {
            id: 'q18',
            title: 'Profession',
            question: "Quelle est votre profession et dans quel secteur d'activités ?",
            type: 'double_text',
            fields: [
                { key: 'profession', label: 'Profession' },
                { key: 'secteur', label: "Secteur d'activités" }
            ]
        },

        {
            id: 'q19',
            title: 'Diplôme',
            question: 'Quel est votre dernier diplôme obtenu ?',
            type: 'single',
            note: 'STOP si pas de diplôme ou CAP/BEP',
            options: [
                { value: 'aucun', label: "Je n'ai pas de diplôme", stop: true },
                { value: 'cap_bep', label: "CAP – BEP – Brevet – Certificat d'études", stop: true },
                { value: 'bac', label: 'Baccalauréat', stop: false },
                { value: 'bac2', label: 'Bac +2 (BTS/DUT/DEUST)', stop: false },
                { value: 'bac34', label: 'Bac +3/+4', stop: false },
                { value: 'bac5', label: 'Bac +5 et plus', stop: false },
                { value: 'doctorat', label: 'Doctorat', stop: false }
            ]
        },

        {
            id: 'q20',
            title: 'Profession partenaire',
            question: "Quelle est la profession de votre partenaire et dans quel secteur d'activités ?",
            type: 'double_text',
            optional: true,
            fields: [
                { key: 'profession_partenaire', label: 'Profession' },
                { key: 'secteur_partenaire', label: "Secteur d'activités" }
            ]
        },

        {
            id: 'q21',
            title: 'Revenus annuels',
            question: "Quelle tranche de revenus annuels s'applique à votre foyer (tous revenus confondus) ?",
            type: 'single',
            note: 'STOP si moins de 50 000€',
            options: [
                { value: 'moins_30k', label: 'Moins de 30 000€ par an', stop: true },
                { value: '30k-50k', label: 'De 30 000 à 49 999€ par an', stop: true },
                { value: '50k-60k', label: 'De 50 000 à 59 999€ par an', stop: false },
                { value: '60k-75k', label: 'De 60 000 à 74 999€ par an', stop: false },
                { value: '75k-100k', label: 'De 75 000 à 99 999€ par an', stop: false },
                { value: '100k_plus', label: '100 000€ ou plus par an', stop: false }
            ]
        }
    ]
};
