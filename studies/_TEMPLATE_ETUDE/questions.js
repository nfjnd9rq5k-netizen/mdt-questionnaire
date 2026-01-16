
const STUDY_CONFIG = {
    
    
    studyId: 'TEMPLATE_ETUDE_2026',           
    studyTitle: "Titre de l'étude",           
    studyDate: "Date de l'étude",             // Ex: "Mardi 13 Janvier 2026"
    reward: '50€',                             
    duration: '60 min',                        
    horaires: ['9h', '11h', '14h', '16h'],     
    
    requireAccessId: true,
    
    
    objectifs: {
        totalParticipants: 5,
        
        quotas: [
            {
                id: 'exemple_sexe',
                titre: '👤 Répartition par sexe',
                source: 'q_sexe',
                criteres: [
                    { valeur: 'homme', label: 'Hommes', objectif: 2 },
                    { valeur: 'femme', label: 'Femmes', objectif: 3 }
                ]
            }
        ]
    },
    
    
    questions: [
        {
            id: 'q_exemple',
            title: 'Question exemple',
            question: 'Votre question ici ?',
            type: 'single',
            options: [
                { value: 'oui', label: 'Oui', stop: false },
                { value: 'non', label: 'Non', stop: true }
            ]
        },
        
        {
            id: 'q_sexe',
            title: 'Sexe',
            question: 'Vous êtes :',
            type: 'single',
            options: [
                { value: 'homme', label: 'Un homme', stop: false },
                { value: 'femme', label: 'Une femme', stop: false }
            ]
        }
    ]
};
