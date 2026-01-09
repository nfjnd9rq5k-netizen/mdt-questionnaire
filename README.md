# Maison du Test - Plateforme d'Etudes

Plateforme de recrutement et de screening pour etudes marketing. Permet de creer des questionnaires de qualification, gerer les quotas automatiquement, et administrer les participants.

## Table des matieres

- [Fonctionnalites](#fonctionnalites)
- [Structure du projet](#structure-du-projet)
- [Creer une nouvelle etude](#creer-une-nouvelle-etude)
- [Configuration de questions.js](#configuration-de-questionsjs)
- [Interface d'administration](#interface-dadministration)
- [Fichiers importants](#fichiers-importants)
- [Securite](#securite)

---

## Fonctionnalites

- **Questionnaires de recrutement** - Questions de screening avec logique de disqualification
- **Gestion des quotas** - Suivi automatique des objectifs par critere (sexe, age, etc.)
- **Chiffrement des donnees** - AES-256-CBC pour toutes les reponses
- **Interface d'administration** - Dashboard pour gerer les etudes et participants
- **Export Excel** - Telechargement des resultats en .xlsx
- **Reprise de session** - Les participants peuvent reprendre leur questionnaire
- **IDs d'acces** - Controle d'acces par identifiant unique

---

## Structure du projet

```
etudes/
├── admin/                      # Interface administrateur
│   ├── index.php               # Page de connexion admin
│   ├── dashboard.php           # Dashboard de gestion
│   └── change-password.php     # Changement de mot de passe
│
├── api/                        # Backend PHP
│   ├── config.php              # Configuration centrale
│   ├── save.php                # Sauvegarde des reponses
│   ├── check-access.php        # Validation des IDs d'acces
│   ├── admin-data.php          # API administration
│   ├── export-xlsx.php         # Export Excel
│   ├── photo.php               # Service des photos
│   ├── study-status.php        # Gestion statut etude
│   └── secure_data/            # Donnees protegees
│
├── css/
│   └── style.css               # Styles de la plateforme
│
├── js/
│   └── engine.js               # Moteur de questionnaire
│
├── studies/                    # TOUTES LES ETUDES
│   ├── _TEMPLATE_ETUDE/        # >>> TEMPLATE A COPIER <<<
│   ├── closed.html             # Page etude fermee
│   └── [NOM_ETUDE]/            # Dossiers des etudes
│       ├── index.html
│       ├── questions.js
│       ├── status.json
│       └── data/
│           ├── access_ids.json
│           ├── responses.enc
│           └── refused.enc
│
└── index.html                  # Point d'entree
```

---

## Creer une nouvelle etude

### Etape 1 : Copier le template

```
1. Aller dans le dossier : studies/
2. Copier le dossier : _TEMPLATE_ETUDE
3. Renommer la copie : NOM_ETUDE_DATE (ex: SHAMPOOING_MDT_FEV2026)
```

### Etape 2 : Modifier questions.js

Ouvrir `studies/[VOTRE_ETUDE]/questions.js` et configurer :

```javascript
const STUDY_CONFIG = {
    // === INFORMATIONS DE L'ETUDE ===
    studyId: 'NOM_UNIQUE_ETUDE',        // ID unique (pas d'espaces)
    studyTitle: 'Titre affiche',         // Titre visible par les participants
    studyDate: 'Mardi 13 Janvier 2026',  // Date de l'etude
    reward: '50€',                       // Remuneration
    duration: '60 min',                  // Duree prevue
    horaires: ['9h', '11h', '14h'],      // Creneaux disponibles

    requireAccessId: true,               // true = ID obligatoire

    // === QUOTAS ===
    objectifs: {
        totalParticipants: 10,           // Nombre total vise
        quotas: [
            // Voir section Quotas ci-dessous
        ]
    },

    // === QUESTIONS ===
    questions: [
        // Voir section Questions ci-dessous
    ]
};
```

### Etape 3 : Ajouter les IDs d'acces

Modifier `studies/[VOTRE_ETUDE]/data/access_ids.json` :

```json
[
    "ABC123",
    "DEF456",
    "GHI789"
]
```

### Etape 4 : Tester

Acceder a : `http://[votre-serveur]/studies/[VOTRE_ETUDE]/`

---

## Configuration de questions.js

### Types de questions

| Type | Description | Exemple |
|------|-------------|---------|
| `single` | Choix unique (boutons radio) | Sexe, Oui/Non |
| `multiple` | Choix multiples (cases a cocher) | Marques utilisees |
| `number` | Champ numerique avec min/max | Age |
| `text` | Champ texte libre | Commentaires |

### Exemple de question - Choix unique

```javascript
{
    id: 'q_sexe',
    title: 'Sexe',
    question: 'Vous etes :',
    type: 'single',
    options: [
        { value: 'homme', label: 'Un homme', stop: false },
        { value: 'femme', label: 'Une femme', stop: false }
    ]
}
```

### Exemple de question - Choix multiples

```javascript
{
    id: 'q_marques',
    title: 'Marques',
    question: 'Quelles marques connaissez-vous ?',
    type: 'multiple',
    options: [
        { value: 'marque_a', label: 'Marque A', stop: false },
        { value: 'marque_b', label: 'Marque B', stop: false },
        { value: 'aucune', label: 'Aucune', stop: true, exclusive: true }
    ]
}
```

### Exemple de question - Nombre

```javascript
{
    id: 'q_age',
    title: 'Age',
    question: 'Quel est votre age ?',
    type: 'number',
    min: 18,
    max: 99
}
```

### Logique de disqualification (stop)

Ajouter `stop: true` pour disqualifier si cette option est selectionnee :

```javascript
{
    id: 'q_emploi',
    title: 'Secteur',
    question: 'Travaillez-vous dans l\'un de ces secteurs ?',
    type: 'multiple',
    options: [
        { value: 'marketing', label: 'Marketing/Publicite', stop: true },
        { value: 'etudes', label: 'Etudes de marche', stop: true },
        { value: 'aucun', label: 'Aucun de ces secteurs', stop: false }
    ]
}
```

### Option exclusive

Pour empecher la selection avec d'autres options :

```javascript
{ value: 'aucune', label: 'Aucune de ces reponses', stop: false, exclusive: true }
```

### Questions conditionnelles (showIf)

Afficher une question selon une reponse precedente :

```javascript
{
    id: 'q_precision',
    title: 'Precision',
    question: 'Precisez votre reponse :',
    type: 'text',
    showIf: (reponses) => reponses.q_precedente === 'autre'
}
```

### Configuration des quotas

#### Quota simple (une valeur)

```javascript
{
    id: 'quota_sexe',
    titre: 'Repartition par sexe',
    source: 'q_sexe',                    // ID de la question
    criteres: [
        { valeur: 'homme', label: 'Hommes', objectif: 5 },
        { valeur: 'femme', label: 'Femmes', objectif: 5 }
    ]
}
```

#### Quota par tranche (age)

```javascript
{
    id: 'quota_age',
    titre: 'Repartition par age',
    source: 'q_age',
    type: 'tranche',
    criteres: [
        { min: 18, max: 34, label: '18-34 ans', objectif: 5 },
        { min: 35, max: 50, label: '35-50 ans', objectif: 5 }
    ]
}
```

#### Quota combine (plusieurs criteres)

```javascript
{
    id: 'quota_combine',
    titre: 'Femmes avec enfants',
    type: 'combine',
    sources: ['q_sexe', 'q_enfants'],
    criteres: [
        {
            valeurs: { q_sexe: 'femme', q_enfants: 'oui' },
            label: 'Femmes avec enfants',
            objectif: 3
        }
    ]
}
```

---

## Interface d'administration

### Acces

URL : `http://[votre-serveur]/admin/`

### Fonctionnalites

| Fonction | Description |
|----------|-------------|
| **Liste des etudes** | Voir toutes les etudes actives |
| **Participants** | Liste des participants par etude |
| **Supprimer** | Supprimer un participant |
| **Export Excel** | Telecharger les donnees en .xlsx |
| **Fermer etude** | Empecher nouvelles inscriptions |
| **Archiver** | Deplacer vers les archives |

### Onglets du dashboard

1. **Etudes en cours** - Etudes actives avec progression des quotas
2. **Participants** - Gestion individuelle des participants
3. **Archives** - Etudes terminees

---

## Fichiers importants

### Configuration centrale

| Fichier | Role |
|---------|------|
| `api/config.php` | Identifiants admin, cle de chiffrement, timeouts |

### Moteur de questionnaire

| Fichier | Role |
|---------|------|
| `js/engine.js` | Toute la logique du questionnaire (rendu, validation, quotas) |

### Par etude

| Fichier | Role |
|---------|------|
| `questions.js` | Configuration complete de l'etude |
| `index.html` | Page HTML de l'etude |
| `status.json` | Statut ouvert/ferme |
| `data/access_ids.json` | Liste des IDs autorises |
| `data/responses.enc` | Reponses chiffrees (qualifies) |
| `data/refused.enc` | Reponses chiffrees (disqualifies) |

---

## Securite

### Chiffrement

- **Algorithme** : AES-256-CBC
- **Stockage** : Toutes les reponses sont chiffrees avant sauvegarde
- **Format** : Base64 avec IV integre

### Protection admin

- **Mot de passe** : Hash Argon2
- **Brute force** : 5 tentatives max, blocage 15 min
- **Session** : Timeout 1 heure, validation IP

### Acces aux donnees

- **IDs d'acces** : Requis pour participer (si active)
- **.htaccess** : Protection des dossiers sensibles

---

## Flux participant

```
1. Acces a l'etude
   └─> Verification de l'ID d'acces

2. Questionnaire
   └─> Questions de screening
   └─> Verification des quotas
   └─> Disqualification si "stop"

3. Signaletique
   └─> Nom, prenom, email, telephone
   └─> Adresse complete

4. Choix du creneau
   └─> Selection de l'horaire

5. Confirmation
   └─> Sauvegarde chiffree
   └─> Message de confirmation
```

---

## Exemples d'etudes existantes

| Etude | Description | Particularites |
|-------|-------------|----------------|
| `FM_EXPLO_TP_JAN2025` | Etude alimentaire Fleury Michon | Quotas sexe/enfants/marque |
| `SECHE_CHEVEUX_MDT_JAN2026` | Etude seche-cheveux | Detection de marques avec fuzzy matching |
| `SHARK_INHOME_JAN2026` | Test aspirateur Shark | Quotas combines enfants+animaux |

---

## Support

En cas de probleme :
1. Verifier que le dossier `data/` a les permissions d'ecriture
2. Verifier que `status.json` contient `{"status": "active"}`
3. Verifier les IDs dans `access_ids.json`
4. Consulter les logs PHP du serveur
