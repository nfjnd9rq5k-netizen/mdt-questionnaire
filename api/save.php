<?php
/**
 * ============================================================
 * SAUVEGARDE DES RÉPONSES - VERSION MYSQL
 * ============================================================
 * 
 * Ce fichier gère l'enregistrement des réponses au questionnaire.
 * Avant : on écrivait dans des fichiers .enc (risque de perte)
 * Maintenant : on écrit dans MySQL (sûr et rapide)
 * 
 * COMMENT ÇA MARCHE :
 * 1. Le questionnaire (engine.js) envoie les données en POST
 * 2. Ce fichier reçoit les données
 * 3. On les insère dans la base de données
 * 4. On renvoie une confirmation au questionnaire
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Requête OPTIONS (pré-vérification CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Connexion à la base de données
require_once 'db.php';

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================

/**
 * Génère un identifiant unique pour chaque participant
 * Ex: "a1b2c3d4e5f6g7h8"
 */
function generateUniqueId(): string {
    return bin2hex(random_bytes(8));
}

/**
 * Récupère l'ID interne d'une étude à partir de son study_id
 * Ex: "SHARK_INHOME_JAN2026" → 1
 */
function getStudyInternalId(string $studyId): ?int {
    $result = dbQueryOne(
        "SELECT id FROM studies WHERE study_id = ?",
        [$studyId]
    );
    return $result ? (int)$result['id'] : null;
}

/**
 * Crée une étude si elle n'existe pas encore dans la base
 * (Utile pour la première réponse à une nouvelle étude)
 */
function ensureStudyExists(string $studyId): int {
    $existing = getStudyInternalId($studyId);
    
    if ($existing) {
        return $existing;
    }
    
    // L'étude n'existe pas, on la crée avec des valeurs par défaut
    dbExecute(
        "INSERT INTO studies (study_id, folder_name, title, status) VALUES (?, ?, ?, 'active')",
        [$studyId, $studyId, $studyId]
    );
    
    return (int)dbLastId();
}

/**
 * Sauvegarde une photo uploadée
 * Redimensionne si trop grande et convertit en JPEG
 */
function savePhoto(string $base64Data, string $studyId, string $responseId, string $questionId): ?array {
    // Extraire le type et les données
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
        $extension = $matches[1];
        $data = substr($base64Data, strpos($base64Data, ',') + 1);
    } else {
        $extension = 'jpg';
        $data = $base64Data;
    }
    
    $imageData = base64_decode($data);
    if ($imageData === false) {
        return null;
    }
    
    // Créer le dossier photos s'il n'existe pas
    $photosDir = __DIR__ . '/../studies/' . $studyId . '/data/photos';
    if (!file_exists($photosDir)) {
        mkdir($photosDir, 0755, true);
    }
    
    // Sauvegarder temporairement pour traitement
    $tempFile = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tempFile, $imageData);
    
    $imageInfo = getimagesize($tempFile);
    $finalFilename = $studyId . '_' . $responseId . '_' . $questionId . '_' . time() . '.jpg';
    $finalPath = $photosDir . '/' . $finalFilename;
    
    if ($imageInfo) {
        $mime = $imageInfo['mime'];
        
        // Charger l'image selon son type
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($tempFile);
                break;
            case 'image/png':
                $image = imagecreatefrompng($tempFile);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($tempFile);
                break;
            default:
                $image = null;
        }
        
        if ($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            $maxSize = 1200;
            
            // Redimensionner si nécessaire
            if ($width > $maxSize || $height > $maxSize) {
                if ($width > $height) {
                    $newWidth = $maxSize;
                    $newHeight = intval($height * ($maxSize / $width));
                } else {
                    $newHeight = $maxSize;
                    $newWidth = intval($width * ($maxSize / $height));
                }
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }
            
            // Sauvegarder en JPEG
            imagejpeg($image, $finalPath, 75);
            imagedestroy($image);
            unlink($tempFile);
            
            return [
                'filename' => $finalFilename,
                'path' => $finalPath,
                'size' => filesize($finalPath),
                'mime' => 'image/jpeg'
            ];
        }
    }
    
    // Si le traitement échoue, copier tel quel
    rename($tempFile, $finalPath);
    
    return [
        'filename' => $finalFilename,
        'path' => $finalPath,
        'size' => filesize($finalPath),
        'mime' => $mime ?? 'image/jpeg'
    ];
}

// ============================================================
// TRAITEMENT DE LA REQUÊTE
// ============================================================

// Vérifier que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données envoyées
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

// ============================================================
// ACTIONS DISPONIBLES
// ============================================================

switch ($input['action']) {
    
    // ----------------------------------------------------------
    // ACTION : save (Nouvelle réponse)
    // ----------------------------------------------------------
    case 'save':
        try {
            // Démarrer une transaction (tout réussit ou tout échoue)
            dbBeginTransaction();
            
            $responseId = generateUniqueId();
            $studyId = $input['studyId'] ?? 'DEFAULT';
            $status = $input['statut'] ?? 'EN_COURS';
            $reponses = $input['reponses'] ?? [];
            $signaletique = $input['signaletique'] ?? [];
            
            // S'assurer que l'étude existe dans la base
            $studyInternalId = ensureStudyExists($studyId);
            
            // 1. Insérer la réponse principale
            dbExecute(
                "INSERT INTO responses (
                    unique_id, study_id, access_id, status, stop_reason, 
                    all_stop_reasons, horaire, ip_hash, user_agent, 
                    started_at, completed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $responseId,
                    $studyInternalId,
                    $input['accessId'] ?? null,
                    $status,
                    $input['raisonStop'] ?? null,
                    !empty($input['toutesRaisonsStop']) ? json_encode($input['toutesRaisonsStop']) : null,
                    $input['horaire'] ?? null,
                    hash('sha256', $_SERVER['REMOTE_ADDR'] . ($input['studyId'] ?? '')),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                    $input['dateDebut'] ?? date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]
            );
            
            $responseInternalId = (int)dbLastId();
            
            // 2. Insérer la signalétique (si présente)
            if (!empty($signaletique)) {
                dbExecute(
                    "INSERT INTO signaletiques (
                        response_id, nom, prenom, email, telephone, 
                        adresse, code_postal, ville
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $responseInternalId,
                        $signaletique['nom'] ?? null,
                        $signaletique['prenom'] ?? null,
                        $signaletique['email'] ?? null,
                        $signaletique['telephone'] ?? null,
                        $signaletique['adresse'] ?? null,
                        $signaletique['codePostal'] ?? null,
                        $signaletique['ville'] ?? null
                    ]
                );
            }
            
            // 3. Insérer chaque réponse aux questions
            foreach ($reponses as $questionId => $answer) {
                $answerValue = null;
                $answerValues = null;
                $answerText = null;
                $answerData = null;
                
                // Déterminer le type de réponse
                if (isset($answer['value'])) {
                    $answerValue = $answer['value'];
                }
                if (isset($answer['values'])) {
                    $answerValues = json_encode($answer['values']);
                }
                if (isset($answer['text'])) {
                    $answerText = $answer['text'];
                }
                
                // Données complexes (double_text, brands, etc.)
                $complexData = array_diff_key($answer, array_flip(['value', 'values', 'text', 'file']));
                if (!empty($complexData)) {
                    $answerData = json_encode($complexData);
                }
                
                // Gérer les photos
                if (isset($answer['file']) && isset($answer['file']['data'])) {
                    $photoInfo = savePhoto($answer['file']['data'], $studyId, $responseId, $questionId);
                    
                    if ($photoInfo) {
                        // Sauvegarder l'info de la photo dans la table photos
                        dbExecute(
                            "INSERT INTO photos (response_id, question_id, filename, original_name, file_path, file_size, mime_type)
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [
                                $responseInternalId,
                                $questionId,
                                $photoInfo['filename'],
                                $answer['file']['name'] ?? 'photo.jpg',
                                $photoInfo['path'],
                                $photoInfo['size'],
                                $photoInfo['mime']
                            ]
                        );
                        
                        // Stocker le nom du fichier dans answer_data
                        $answerData = json_encode([
                            'filename' => $photoInfo['filename'],
                            'originalName' => $answer['file']['name'] ?? 'photo.jpg'
                        ]);
                    }
                }
                
                dbExecute(
                    "INSERT INTO answers (response_id, question_id, answer_value, answer_values, answer_text, answer_data)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$responseInternalId, $questionId, $answerValue, $answerValues, $answerText, $answerData]
                );
            }
            
            // Tout s'est bien passé, on valide la transaction
            dbCommit();
            
            echo json_encode([
                'success' => true,
                'id' => $responseId,
                'message' => 'Réponse enregistrée'
            ]);
            
        } catch (Exception $e) {
            // En cas d'erreur, on annule tout
            dbRollback();
            
            error_log("Erreur save: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de sauvegarde: ' . $e->getMessage()]);
        }
        break;
    
    // ----------------------------------------------------------
    // ACTION : update (Mise à jour d'une réponse existante)
    // ----------------------------------------------------------
    case 'update':
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant']);
            exit;
        }
        
        try {
            dbBeginTransaction();
            
            $uniqueId = $input['id'];
            $newStatus = $input['statut'] ?? 'EN_COURS';
            $studyId = $input['studyId'] ?? 'DEFAULT';
            $reponses = $input['reponses'] ?? [];
            $behaviorMetrics = $input['behaviorMetrics'] ?? null;
            
            // Trouver la réponse existante
            $existing = dbQueryOne(
                "SELECT r.id, r.study_id, s.study_id as study_code 
                 FROM responses r 
                 JOIN studies s ON r.study_id = s.id 
                 WHERE r.unique_id = ?",
                [$uniqueId]
            );
            
            if (!$existing) {
                throw new Exception('Réponse non trouvée');
            }
            
            $responseInternalId = $existing['id'];
            
            // Préparer les métriques comportementales en JSON
            $behaviorJson = null;
            if ($behaviorMetrics) {
                $behaviorJson = json_encode($behaviorMetrics);
            }
            
            // Mettre à jour la réponse principale
            // Essayer d'abord avec behavior_metrics, sinon sans
            try {
                dbExecute(
                    "UPDATE responses SET 
                        status = ?, 
                        stop_reason = ?, 
                        all_stop_reasons = ?,
                        completed_at = ?,
                        modified_at = NOW(),
                        modified_by = 'questionnaire',
                        behavior_metrics = COALESCE(?, behavior_metrics)
                     WHERE id = ?",
                    [
                        $newStatus,
                        $input['raisonStop'] ?? null,
                        !empty($input['toutesRaisonsStop']) ? json_encode($input['toutesRaisonsStop']) : null,
                        date('Y-m-d H:i:s'),
                        $behaviorJson,
                        $responseInternalId
                    ]
                );
            } catch (Exception $e) {
                // Fallback sans behavior_metrics si la colonne n'existe pas
                dbExecute(
                    "UPDATE responses SET 
                        status = ?, 
                        stop_reason = ?, 
                        all_stop_reasons = ?,
                        completed_at = ?,
                        modified_at = NOW(),
                        modified_by = 'questionnaire'
                     WHERE id = ?",
                    [
                        $newStatus,
                        $input['raisonStop'] ?? null,
                        !empty($input['toutesRaisonsStop']) ? json_encode($input['toutesRaisonsStop']) : null,
                        date('Y-m-d H:i:s'),
                        $responseInternalId
                    ]
                );
            }
            
            // Mettre à jour ou insérer chaque réponse
            foreach ($reponses as $questionId => $answer) {
                $answerValue = $answer['value'] ?? null;
                $answerValues = isset($answer['values']) ? json_encode($answer['values']) : null;
                $answerText = $answer['text'] ?? null;
                
                $complexData = array_diff_key($answer, array_flip(['value', 'values', 'text', 'file']));
                $answerData = !empty($complexData) ? json_encode($complexData) : null;
                
                // Gérer les photos
                if (isset($answer['file']) && isset($answer['file']['data'])) {
                    $photoInfo = savePhoto($answer['file']['data'], $studyId, $uniqueId, $questionId);
                    
                    if ($photoInfo) {
                        // Supprimer l'ancienne photo si elle existe
                        dbExecute(
                            "DELETE FROM photos WHERE response_id = ? AND question_id = ?",
                            [$responseInternalId, $questionId]
                        );
                        
                        dbExecute(
                            "INSERT INTO photos (response_id, question_id, filename, original_name, file_path, file_size, mime_type)
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [
                                $responseInternalId,
                                $questionId,
                                $photoInfo['filename'],
                                $answer['file']['name'] ?? 'photo.jpg',
                                $photoInfo['path'],
                                $photoInfo['size'],
                                $photoInfo['mime']
                            ]
                        );
                        
                        $answerData = json_encode([
                            'filename' => $photoInfo['filename'],
                            'originalName' => $answer['file']['name'] ?? 'photo.jpg'
                        ]);
                    }
                }
                
                // Utiliser INSERT ... ON DUPLICATE KEY UPDATE (upsert)
                dbExecute(
                    "INSERT INTO answers (response_id, question_id, answer_value, answer_values, answer_text, answer_data)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                        answer_value = VALUES(answer_value),
                        answer_values = VALUES(answer_values),
                        answer_text = VALUES(answer_text),
                        answer_data = VALUES(answer_data),
                        updated_at = NOW()",
                    [$responseInternalId, $questionId, $answerValue, $answerValues, $answerText, $answerData]
                );
            }
            
            dbCommit();
            
            echo json_encode(['success' => true, 'message' => 'Réponse mise à jour']);
            
        } catch (Exception $e) {
            dbRollback();
            
            error_log("Erreur update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de mise à jour: ' . $e->getMessage()]);
        }
        break;
    
    // ----------------------------------------------------------
    // ACTION INCONNUE
    // ----------------------------------------------------------
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue: ' . $input['action']]);
}
