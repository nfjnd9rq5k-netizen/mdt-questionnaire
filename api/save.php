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

// Sécurité CORS - Charger la configuration
require_once __DIR__ . '/security.php';
applyCorsHeaders();
handleCorsPreflightIfNeeded();

// Connexion à la base de données
require_once 'db.php';

// ============================================================
// SUPPORT JWT MOBILE (optionnel)
// ============================================================

/**
 * Vérifie si un token JWT mobile est présent et valide
 * Retourne les infos du paneliste ou null
 */
function checkMobileToken(): ?array {
    // Vérifier le header Authorization
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    $token = null;
    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }

    // Fallback: query parameter
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }

    if (!$token) {
        return null;
    }

    // Charger la librairie JWT si disponible
    $jwtFile = __DIR__ . '/mobile/jwt.php';
    if (!file_exists($jwtFile)) {
        return null;
    }

    require_once $jwtFile;

    $payload = verifyToken($token);
    if (!$payload) {
        return null;
    }

    // Vérifier que c'est un token valide (access ou webview)
    $type = $payload['type'] ?? '';
    if (!in_array($type, ['access', 'webview'])) {
        return null;
    }

    return [
        'panelist_id' => $payload['sub'] ?? null,
        'panelist_unique_id' => $payload['panelist_unique_id'] ?? null,
        'solicitation_id' => $payload['solicitation_id'] ?? null,
        'email' => $payload['email'] ?? null
    ];
}

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

            // Vérifier si c'est une réponse depuis l'app mobile
            $mobileInfo = checkMobileToken();
            $panelistId = $mobileInfo['panelist_id'] ?? null;
            $solicitationId = $mobileInfo['solicitation_id'] ?? ($input['solicitation_id'] ?? null);
            
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
            
            // Si c'est une réponse mobile, lier au panelist_solicitation
            if ($panelistId && $solicitationId) {
                try {
                    dbExecute(
                        "UPDATE panelist_solicitations
                         SET response_id = ?, status = 'started', started_at = COALESCE(started_at, NOW())
                         WHERE panelist_id = ? AND solicitation_id = ?",
                        [$responseInternalId, $panelistId, $solicitationId]
                    );
                } catch (Exception $e) {
                    // Ignorer si la table n'existe pas encore
                    error_log("Mobile link warning: " . $e->getMessage());
                }
            }

            // Tout s'est bien passé, on valide la transaction
            dbCommit();

            $response = [
                'success' => true,
                'id' => $responseId,
                'message' => 'Réponse enregistrée'
            ];

            // Ajouter des infos pour l'app mobile si applicable
            if ($panelistId) {
                $response['panelist_linked'] = true;
                $response['solicitation_id'] = $solicitationId;
            }

            echo json_encode($response);
            
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

            // Vérifier si c'est une mise à jour depuis l'app mobile
            $mobileInfo = checkMobileToken();
            $panelistId = $mobileInfo['panelist_id'] ?? null;
            $solicitationId = $mobileInfo['solicitation_id'] ?? ($input['solicitation_id'] ?? null);
            
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
            
            // Si c'est une mise à jour mobile avec statut terminal, mettre à jour le lien
            if ($panelistId && $solicitationId && in_array($newStatus, ['QUALIFIE', 'REFUSE'])) {
                try {
                    $finalStatus = ($newStatus === 'QUALIFIE') ? 'completed' : 'screened_out';

                    // Récupérer les points de la solicitation si complété
                    if ($finalStatus === 'completed') {
                        $solInfo = dbQueryOne(
                            "SELECT reward_points, title FROM solicitations WHERE id = ?",
                            [$solicitationId]
                        );

                        if ($solInfo) {
                            $pointsEarned = (int) $solInfo['reward_points'];

                            // Mettre à jour panelist_solicitations
                            dbExecute(
                                "UPDATE panelist_solicitations
                                 SET status = ?, completed_at = NOW(), points_earned = ?
                                 WHERE panelist_id = ? AND solicitation_id = ?",
                                [$finalStatus, $pointsEarned, $panelistId, $solicitationId]
                            );

                            // Ajouter les points au paneliste
                            if ($pointsEarned > 0) {
                                $panelist = dbQueryOne("SELECT points_balance FROM panelists WHERE id = ?", [$panelistId]);
                                $newBalance = (int) ($panelist['points_balance'] ?? 0) + $pointsEarned;

                                dbExecute(
                                    "UPDATE panelists
                                     SET points_balance = points_balance + ?,
                                         points_lifetime = points_lifetime + ?,
                                         studies_completed = studies_completed + 1,
                                         last_active = NOW()
                                     WHERE id = ?",
                                    [$pointsEarned, $pointsEarned, $panelistId]
                                );

                                // Historique des points
                                dbExecute(
                                    "INSERT INTO panelist_points_history
                                     (panelist_id, points, type, description, reference_id, balance_after)
                                     VALUES (?, ?, 'study_completed', ?, ?, ?)",
                                    [$panelistId, $pointsEarned, "Étude: " . $solInfo['title'], $solicitationId, $newBalance]
                                );

                                // Incrémenter le quota
                                dbExecute(
                                    "UPDATE solicitations SET quota_current = quota_current + 1 WHERE id = ?",
                                    [$solicitationId]
                                );
                            }
                        }
                    } else {
                        // Screened out - juste mettre à jour le statut
                        dbExecute(
                            "UPDATE panelist_solicitations
                             SET status = ?, completed_at = NOW()
                             WHERE panelist_id = ? AND solicitation_id = ?",
                            [$finalStatus, $panelistId, $solicitationId]
                        );
                    }
                } catch (Exception $e) {
                    error_log("Mobile update link warning: " . $e->getMessage());
                }
            }

            dbCommit();

            $response = ['success' => true, 'message' => 'Réponse mise à jour'];

            // Ajouter des infos pour l'app mobile
            if ($panelistId && in_array($newStatus, ['QUALIFIE', 'REFUSE'])) {
                $response['study_completed'] = ($newStatus === 'QUALIFIE');
                $response['points_earned'] = $pointsEarned ?? 0;
            }

            echo json_encode($response);

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
