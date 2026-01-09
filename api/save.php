<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

function getStudyDataPath($studyId) {
    $studyDir = __DIR__ . '/../studies/' . $studyId . '/data';
    if (!file_exists($studyDir)) {
        mkdir($studyDir, 0755, true);
    }
    return $studyDir;
}

function getStudyResponsesFile($studyId) {
    return getStudyDataPath($studyId) . '/responses.enc';
}

function getStudyRefusedFile($studyId) {
    return getStudyDataPath($studyId) . '/refused.enc';
}

function getStudyPhotosDir($studyId) {
    $photosDir = getStudyDataPath($studyId) . '/photos';
    if (!file_exists($photosDir)) {
        mkdir($photosDir, 0755, true);
    }
    return $photosDir;
}

function encryptData($data) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt(
        json_encode($data),
        'AES-256-CBC',
        ENCRYPTION_KEY,
        0,
        $iv
    );
    return base64_encode($iv . '::' . $encrypted);
}

function decryptData($encryptedData) {
    $parts = explode('::', base64_decode($encryptedData), 2);
    if (count($parts) !== 2) return null;
    
    list($iv, $encrypted) = $parts;
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return json_decode($decrypted, true);
}

function loadResponses($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return decryptData($content) ?: [];
}

function saveResponses($responses, $file = DATA_FILE) {
    if (file_exists($file)) {
        $backupFile = BACKUP_DIR . 'backup_' . basename($file, '.enc') . '_' . date('Y-m-d_H-i-s') . '.enc';
        copy($file, $backupFile);
        
        $pattern = BACKUP_DIR . 'backup_' . basename($file, '.enc') . '_*.enc';
        $backups = glob($pattern);
        if (count($backups) > 10) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            for ($i = 0; $i < count($backups) - 10; $i++) {
                unlink($backups[$i]);
            }
        }
    }
    
    $encrypted = encryptData($responses);
    return file_put_contents($file, $encrypted) !== false;
}

function generateUniqueId() {
    return bin2hex(random_bytes(8));
}

function savePhoto($base64Data, $studyId, $responseId, $questionId) {
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
    
    $tempFile = tempnam(sys_get_temp_dir(), 'img');
    file_put_contents($tempFile, $imageData);
    
    $imageInfo = getimagesize($tempFile);
    if ($imageInfo) {
        $mime = $imageInfo['mime'];
        
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
            
            $filename = $studyId . '_' . $responseId . '_' . $questionId . '_' . time() . '.jpg';
            $photosDir = getStudyPhotosDir($studyId);
            $filepath = $photosDir . '/' . $filename;
            imagejpeg($image, $filepath, 75);
            imagedestroy($image);
            
            unlink($tempFile);
            return $filename;
        }
    }
    
    $filename = $studyId . '_' . $responseId . '_' . $questionId . '_' . time() . '.' . $extension;
    $photosDir = getStudyPhotosDir($studyId);
    $filepath = $photosDir . '/' . $filename;
    rename($tempFile, $filepath);
    
    return $filename;
}

function processPhotosInResponses(&$reponses, $studyId, $responseId) {
    foreach ($reponses as $questionId => &$answer) {
        if (isset($answer['file']) && isset($answer['file']['data'])) {
            $filename = savePhoto($answer['file']['data'], $studyId, $responseId, $questionId);
            if ($filename) {
                $answer['file'] = [
                    'filename' => $filename,
                    'originalName' => $answer['file']['name'] ?? 'photo.jpg',
                    'url' => 'api/photo.php?study=' . urlencode($studyId) . '&file=' . urlencode($filename)
                ];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

switch ($input['action']) {
    case 'save':
        $responseId = generateUniqueId();
        $statut = $input['statut'] ?? 'EN_COURS';
        $studyId = $input['studyId'] ?? 'DEFAULT';
        $reponses = $input['reponses'] ?? [];
        
        processPhotosInResponses($reponses, $studyId, $responseId);
        
        $newResponse = [
            'id' => $responseId,
            'studyId' => $studyId,
            'accessId' => $input['accessId'] ?? null,
            'signaletique' => $input['signaletique'] ?? [],
            'horaire' => $input['horaire'] ?? '',
            'reponses' => $reponses,
            'statut' => $statut,
            'raisonStop' => $input['raisonStop'] ?? '',
            'toutesRaisonsStop' => $input['toutesRaisonsStop'] ?? [],
            'dateDebut' => $input['dateDebut'] ?? date('c'),
            'dateFin' => date('c'),
            'ip' => hash('sha256', $_SERVER['REMOTE_ADDR'] . ENCRYPTION_KEY),
            'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
        ];
        
        if ($statut === 'REFUSE') {
            $dataFile = getStudyRefusedFile($studyId);
        } else {
            $dataFile = getStudyResponsesFile($studyId);
        }
        
        $responses = loadResponses($dataFile);
        $responses[] = $newResponse;
        $success = saveResponses($responses, $dataFile);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'id' => $responseId,
                'message' => 'Réponse enregistrée'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de sauvegarde']);
        }
        break;
        
    case 'update':
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant']);
            exit;
        }
        
        $newStatut = $input['statut'] ?? 'EN_COURS';
        $studyId = $input['studyId'] ?? 'DEFAULT';
        $reponses = $input['reponses'] ?? [];
        
        processPhotosInResponses($reponses, $studyId, $input['id']);
        
        $found = false;
        $moved = false;
        
        $responsesFile = getStudyResponsesFile($studyId);
        $refusedFile = getStudyRefusedFile($studyId);
        
        $responses = loadResponses($responsesFile);
        
        foreach ($responses as $key => &$response) {
            if ($response['id'] === $input['id']) {
                foreach ($reponses as $qId => $answer) {
                    $response['reponses'][$qId] = $answer;
                }
                
                $response['statut'] = $newStatut;
                $response['raisonStop'] = $input['raisonStop'] ?? '';
                $response['toutesRaisonsStop'] = $input['toutesRaisonsStop'] ?? [];
                $response['dateFin'] = date('c');
                
                if ($newStatut === 'REFUSE') {
                    $refusedResponses = loadResponses($refusedFile);
                    $refusedResponses[] = $response;
                    saveResponses($refusedResponses, $refusedFile);
                    
                    unset($responses[$key]);
                    $responses = array_values($responses);
                    $moved = true;
                }
                
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $refusedResponses = loadResponses($refusedFile);
            foreach ($refusedResponses as $key => &$response) {
                if ($response['id'] === $input['id']) {
                    foreach ($reponses as $qId => $answer) {
                        $response['reponses'][$qId] = $answer;
                    }
                    $response['statut'] = $newStatut;
                    $response['raisonStop'] = $input['raisonStop'] ?? '';
                    $response['toutesRaisonsStop'] = $input['toutesRaisonsStop'] ?? [];
                    $response['dateFin'] = date('c');
                    
                    if ($newStatut === 'QUALIFIE') {
                        $qualifiedResponses = loadResponses($responsesFile);
                        $qualifiedResponses[] = $response;
                        saveResponses($qualifiedResponses, $responsesFile);
                        
                        unset($refusedResponses[$key]);
                        $refusedResponses = array_values($refusedResponses);
                        $moved = true;
                    }
                    
                    $found = true;
                    break;
                }
            }
            if ($found) {
                saveResponses($refusedResponses, $refusedFile);
            }
        } else if (!$moved) {
            saveResponses($responses, $responsesFile);
        } else {
            saveResponses($responses, $responsesFile);
        }
        
        if ($found) {
            echo json_encode(['success' => true, 'message' => 'Réponse mise à jour']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Réponse non trouvée']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
}
