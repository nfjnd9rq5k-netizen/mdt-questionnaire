<?php
/**
 * ============================================================
 * API CRÉATION D'ÉTUDE - NO CODE BUILDER
 * ============================================================
 */

require_once 'db.php';
require_once 'security.php';

// Démarrer la session sécurisée (cookie accessible depuis tous les chemins)
secureSessionStart();
header('Content-Type: application/json');

// Vérification authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérification rôle (admin ou super_admin uniquement)
$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ============================================================
// ACTION: Créer une nouvelle étude
// ============================================================
if ($action === 'create_study') {
    $config = $input['config'] ?? [];
    
    // Validation des champs requis
    $required = ['studyId', 'studyTitle', 'questions'];
    foreach ($required as $field) {
        if (empty($config[$field])) {
            echo json_encode(['success' => false, 'error' => "Champ requis manquant: $field"]);
            exit;
        }
    }
    
    // Nettoyer le studyId pour créer un nom de dossier valide
    $studyId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $config['studyId']);
    $folderName = strtoupper($studyId);
    
    // Vérifier que l'étude n'existe pas déjà
    $existingStudy = dbQueryOne(
        "SELECT id FROM studies WHERE study_id = ? OR folder_name = ?",
        [$studyId, $folderName]
    );
    
    if ($existingStudy) {
        echo json_encode(['success' => false, 'error' => 'Une étude avec cet identifiant existe déjà']);
        exit;
    }
    
    $studiesDir = __DIR__ . '/../studies';
    $studyPath = $studiesDir . '/' . $folderName;
    
    if (file_exists($studyPath)) {
        echo json_encode(['success' => false, 'error' => 'Le dossier existe déjà']);
        exit;
    }
    
    try {
        // Créer les dossiers
        mkdir($studyPath, 0755, true);
        mkdir($studyPath . '/data', 0755, true);
        
        // Générer le fichier questions.js
        $questionsJs = generateQuestionsJs($config);
        file_put_contents($studyPath . '/questions.js', $questionsJs);
        
        // Copier le template index.html
        $indexHtml = generateIndexHtml($config);
        file_put_contents($studyPath . '/index.html', $indexHtml);
        
        // Créer le fichier status.json
        $status = ['status' => 'active', 'createdAt' => date('c')];
        file_put_contents($studyPath . '/status.json', json_encode($status, JSON_PRETTY_PRINT));
        
        // Créer le fichier access_ids.json vide
        file_put_contents($studyPath . '/data/access_ids.json', '[]');
        
        // Ajouter à MySQL
        dbExecute(
            "INSERT INTO studies (study_id, folder_name, title, study_date, reward, duration, target_participants, require_access_id, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                $studyId,
                $folderName,
                $config['studyTitle'],
                $config['studyDate'] ?? '',
                $config['reward'] ?? '',
                $config['duration'] ?? '',
                $config['totalParticipants'] ?? 10,
                $config['requireAccessId'] ? 1 : 0
            ]
        );
        
        $newStudyId = dbLastId();
        
        // Logger la création
        dbExecute(
            "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, 'create_study', ?, ?, ?)",
            [
                $_SESSION['user_db_id'] ?? null,
                $_SESSION['username'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                "Création étude: {$config['studyTitle']} ($folderName)"
            ]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Étude créée avec succès',
            'folder' => $folderName,
            'studyId' => $studyId,
            'dbId' => $newStudyId
        ]);
        
    } catch (Exception $e) {
        // Nettoyer en cas d'erreur
        if (file_exists($studyPath)) {
            array_map('unlink', glob("$studyPath/*"));
            if (file_exists($studyPath . '/data')) {
                array_map('unlink', glob("$studyPath/data/*"));
                rmdir($studyPath . '/data');
            }
            rmdir($studyPath);
        }
        
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ============================================================
// ACTION: Valider l'ID d'étude (vérifier unicité)
// ============================================================
if ($action === 'validate_study_id') {
    $studyId = $input['studyId'] ?? '';
    $studyId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studyId);
    $folderName = strtoupper($studyId);
    
    $exists = dbQueryOne(
        "SELECT id FROM studies WHERE study_id = ? OR folder_name = ?",
        [$studyId, $folderName]
    );
    
    $folderExists = file_exists(__DIR__ . '/../studies/' . $folderName);
    
    echo json_encode([
        'success' => true,
        'available' => !$exists && !$folderExists,
        'suggestion' => $folderName
    ]);
    exit;
}

// ============================================================
// FONCTIONS DE GÉNÉRATION
// ============================================================

function generateQuestionsJs($config) {
    $js = "const STUDY_CONFIG = {\n";
    
    // Métadonnées
    $js .= "    studyId: '" . addslashes($config['studyId']) . "',\n";
    $js .= "    studyTitle: '" . addslashes($config['studyTitle']) . "',\n";
    $js .= "    studyDate: '" . addslashes($config['studyDate'] ?? '') . "',\n";
    $js .= "    reward: '" . addslashes($config['reward'] ?? '') . "',\n";
    $js .= "    duration: '" . addslashes($config['duration'] ?? '') . "',\n";
    
    // Horaires
    if (!empty($config['horaires'])) {
        $horaires = array_map(function($h) { return "'" . addslashes($h) . "'"; }, $config['horaires']);
        $js .= "    horaires: [" . implode(', ', $horaires) . "],\n";
    }
    
    if (!empty($config['hideHoraires'])) {
        $js .= "    hideHoraires: true,\n";
    }
    
    // Access ID
    $js .= "    requireAccessId: " . ($config['requireAccessId'] ? 'true' : 'false') . ",\n\n";
    
    // Objectifs et quotas
    $js .= "    objectifs: {\n";
    $js .= "        totalParticipants: " . intval($config['totalParticipants'] ?? 10) . ",\n";
    $js .= "        quotas: [\n";
    
    if (!empty($config['quotas'])) {
        foreach ($config['quotas'] as $quota) {
            $js .= generateQuotaBlock($quota);
        }
    }
    
    $js .= "        ]\n";
    $js .= "    },\n\n";
    
    // Questions
    $js .= "    questions: [\n";
    
    foreach ($config['questions'] as $question) {
        $js .= generateQuestionBlock($question);
    }
    
    $js .= "    ]\n";
    $js .= "};\n";
    
    return $js;
}

function generateQuotaBlock($quota) {
    $js = "            {\n";
    $js .= "                id: '" . addslashes($quota['id']) . "',\n";
    $js .= "                titre: '" . addslashes($quota['titre']) . "',\n";
    $js .= "                source: '" . addslashes($quota['source']) . "',\n";
    
    if (!empty($quota['type']) && $quota['type'] !== 'simple') {
        $js .= "                type: '" . addslashes($quota['type']) . "',\n";
    }
    
    $js .= "                criteres: [\n";
    
    foreach ($quota['criteres'] as $critere) {
        $js .= "                    { ";
        
        if (!empty($critere['valeur'])) {
            $js .= "valeur: '" . addslashes($critere['valeur']) . "', ";
        }
        if (isset($critere['min'])) {
            $js .= "min: " . intval($critere['min']) . ", max: " . intval($critere['max']) . ", ";
        }
        
        $js .= "label: '" . addslashes($critere['label']) . "', ";
        $js .= "objectif: " . (isset($critere['objectif']) ? intval($critere['objectif']) : 'null');
        $js .= " },\n";
    }
    
    $js .= "                ]\n";
    $js .= "            },\n";
    
    return $js;
}

function generateQuestionBlock($q) {
    $js = "        {\n";
    $js .= "            id: '" . addslashes($q['id']) . "',\n";
    $js .= "            title: '" . addslashes($q['title'] ?? '') . "',\n";
    $js .= "            question: '" . addslashes($q['question']) . "',\n";
    $js .= "            type: '" . addslashes($q['type']) . "',\n";
    
    // Note
    if (!empty($q['note'])) {
        $js .= "            note: '" . addslashes($q['note']) . "',\n";
    }
    
    // Optionnel
    if (!empty($q['optional'])) {
        $js .= "            optional: true,\n";
    }
    
    // Pour les types number
    if ($q['type'] === 'number') {
        if (isset($q['min'])) $js .= "            min: " . intval($q['min']) . ",\n";
        if (isset($q['max'])) $js .= "            max: " . intval($q['max']) . ",\n";
        if (!empty($q['suffix'])) $js .= "            suffix: '" . addslashes($q['suffix']) . "',\n";
        
        // Validation pour number
        if (!empty($q['stopMin']) || !empty($q['stopMax'])) {
            $js .= "            validation: (value) => {\n";
            if (!empty($q['stopMin'])) {
                $js .= "                if (value < " . intval($q['stopMin']) . ") return { stop: true, reason: '" . addslashes($q['stopMinReason'] ?? 'Valeur trop basse') . "' };\n";
            }
            if (!empty($q['stopMax'])) {
                $js .= "                if (value > " . intval($q['stopMax']) . ") return { stop: true, reason: '" . addslashes($q['stopMaxReason'] ?? 'Valeur trop haute') . "' };\n";
            }
            $js .= "                return { stop: false };\n";
            $js .= "            },\n";
        }
    }
    
    // Pour les types file
    if ($q['type'] === 'file') {
        $js .= "            accept: '" . addslashes($q['accept'] ?? 'image/*') . "',\n";
    }
    
    // Pour les types double_text
    if ($q['type'] === 'double_text' && !empty($q['fields'])) {
        $js .= "            fields: [\n";
        foreach ($q['fields'] as $field) {
            $js .= "                { key: '" . addslashes($field['key']) . "', label: '" . addslashes($field['label']) . "'";
            if (!empty($field['placeholder'])) {
                $js .= ", placeholder: '" . addslashes($field['placeholder']) . "'";
            }
            $js .= " },\n";
        }
        $js .= "            ],\n";
    }
    
    // Options
    if (!empty($q['options'])) {
        $js .= "            options: [\n";
        foreach ($q['options'] as $opt) {
            $js .= "                { value: '" . addslashes($opt['value']) . "', label: '" . addslashes($opt['label']) . "'";
            
            if (!empty($opt['stop'])) {
                $js .= ", stop: true";
            } else {
                $js .= ", stop: false";
            }
            
            if (!empty($opt['exclusive'])) {
                $js .= ", exclusive: true";
            }
            
            if (!empty($opt['needsText'])) {
                $js .= ", needsText: true";
                if (!empty($opt['textLabel'])) {
                    $js .= ", textLabel: '" . addslashes($opt['textLabel']) . "'";
                }
            }
            
            $js .= " },\n";
        }
        // Virgule après le tableau si d'autres propriétés suivent
        $hasMoreProps = !empty($q['condition']) || !empty($q['showIf']);
        $js .= "            ]" . ($hasMoreProps ? "," : "") . "\n";
    }
    
    // Condition d'affichage
    if (!empty($q['condition'])) {
        $cond = $q['condition'];
        $source = addslashes($cond['source']);
        $operator = $cond['operator'];
        $value = addslashes($cond['value']);
        
        // Générer le code JavaScript selon l'opérateur
        // Note: les réponses sont stockées comme { value: 'xxx' } pour single et { values: ['xxx'] } pour multiple
        switch ($operator) {
            case 'equals':
                $js .= "            showIf: (answers) => answers['$source']?.value === '$value',\n";
                break;
            case 'not_equals':
                $js .= "            showIf: (answers) => answers['$source']?.value !== '$value',\n";
                break;
            case 'contains':
                // Pour multiple: vérifier dans .values, pour single: vérifier .value
                $js .= "            showIf: (answers) => {\n";
                $js .= "                const a = answers['$source'];\n";
                $js .= "                if (!a) return false;\n";
                $js .= "                if (a.values) return a.values.includes('$value');\n";
                $js .= "                return a.value === '$value';\n";
                $js .= "            },\n";
                break;
            case 'not_contains':
                $js .= "            showIf: (answers) => {\n";
                $js .= "                const a = answers['$source'];\n";
                $js .= "                if (!a) return true;\n";
                $js .= "                if (a.values) return !a.values.includes('$value');\n";
                $js .= "                return a.value !== '$value';\n";
                $js .= "            },\n";
                break;
            case 'greater':
                $js .= "            showIf: (answers) => parseInt(answers['$source']?.value) > $value,\n";
                break;
            case 'less':
                $js .= "            showIf: (answers) => parseInt(answers['$source']?.value) < $value,\n";
                break;
        }
    } elseif (!empty($q['showIf'])) {
        // Support legacy pour code brut
        $js .= "            showIf: (answers) => " . $q['showIf'] . ",\n";
    }
    
    $js .= "        },\n";
    
    return $js;
}

function generateIndexHtml($config) {
    $title = htmlspecialchars($config['studyTitle']);
    
    return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
    <link rel="stylesheet" href="../../css/style.css?v=70">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="app">
        <div class="loading">
            <div class="spinner"></div>
            <p>Chargement du questionnaire...</p>
        </div>
    </div>
    
    <script src="../../js/engine.js?v=70"></script>
    <script src="questions.js?v=70"></script>
    
    <script>
        document.addEventListener(\'DOMContentLoaded\', async () => {
            try {
                const response = await fetch(\'status.json?v=\' + Date.now());
                const status = await response.json();
                
                if (status.status === \'closed\') {
                    window.location.href = \'../closed.html\';
                    return;
                }
                
                const questionnaire = new QuestionnaireEngine(STUDY_CONFIG);
                questionnaire.init();
            } catch (error) {
                const questionnaire = new QuestionnaireEngine(STUDY_CONFIG);
                questionnaire.init();
            }
        });
    </script>
</body>
</html>';
}

echo json_encode(['error' => 'Action non reconnue']);