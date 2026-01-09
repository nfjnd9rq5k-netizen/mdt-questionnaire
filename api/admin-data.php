<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

define('REFUSED_FILE', __DIR__ . '/secure_data/refused.enc');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expirée']);
    exit;
}
$_SESSION['last_activity'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'delete_participant') {
        $studyId = $input['studyId'] ?? '';
        $participantId = $input['participantId'] ?? '';
        
        if (empty($studyId) || empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyDataDir = __DIR__ . '/../studies/' . $studyId . '/data';
        $responsesFile = $studyDataDir . '/responses.enc';
        $refusedFile = $studyDataDir . '/refused.enc';
        
        $deleted = false;
        
        if (file_exists($responsesFile)) {
            $content = file_get_contents($responsesFile);
            $responses = decryptData($content) ?: [];
            
            $originalCount = count($responses);
            $responses = array_filter($responses, function($r) use ($participantId) {
                return $r['id'] !== $participantId;
            });
            
            if (count($responses) < $originalCount) {
                $deleted = true;
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
                $encrypted = openssl_encrypt(json_encode(array_values($responses)), 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
                file_put_contents($responsesFile, base64_encode($iv . '::' . $encrypted));
            }
        }
        
        if (!$deleted && file_exists($refusedFile)) {
            $content = file_get_contents($refusedFile);
            $refused = decryptData($content) ?: [];
            
            $originalCount = count($refused);
            $refused = array_filter($refused, function($r) use ($participantId) {
                return $r['id'] !== $participantId;
            });
            
            if (count($refused) < $originalCount) {
                $deleted = true;
                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
                $encrypted = openssl_encrypt(json_encode(array_values($refused)), 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
                file_put_contents($refusedFile, base64_encode($iv . '::' . $encrypted));
            }
        }
        
        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Participant supprimé']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
        }
        exit;
    }
    
    if ($action === 'update_responses') {
        $studyFolder = $input['studyFolder'] ?? '';
        $participantId = $input['participantId'] ?? '';
        $newReponses = $input['reponses'] ?? [];
        
        if (empty($studyFolder) || empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyDataDir = __DIR__ . '/../studies/' . $studyFolder . '/data';
        $responsesFile = $studyDataDir . '/responses.enc';
        $refusedFile = $studyDataDir . '/refused.enc';
        
        $updated = false;
        
        $saveEncrypted = function($data, $file) {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
            $encrypted = openssl_encrypt(json_encode($data), 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
            return file_put_contents($file, base64_encode($iv . '::' . $encrypted));
        };
        
        if (file_exists($responsesFile)) {
            $content = file_get_contents($responsesFile);
            $responses = decryptData($content) ?: [];
            
            foreach ($responses as &$response) {
                if ($response['id'] === $participantId) {
                    $response['reponses'] = $newReponses;
                    $response['dateModification'] = date('c');
                    $response['modifiePar'] = 'admin';
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                $saveEncrypted(array_values($responses), $responsesFile);
            }
        }
        
        if (!$updated && file_exists($refusedFile)) {
            $content = file_get_contents($refusedFile);
            $refused = decryptData($content) ?: [];
            
            foreach ($refused as &$response) {
                if ($response['id'] === $participantId) {
                    $response['reponses'] = $newReponses;
                    $response['dateModification'] = date('c');
                    $response['modifiePar'] = 'admin';
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                $saveEncrypted(array_values($refused), $refusedFile);
            }
        }
        
        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Réponses mises à jour']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
        }
        exit;
    }
    
    if ($action === 'update_participant') {
        $studyFolder = $input['studyFolder'] ?? '';
        $participantId = $input['participantId'] ?? '';
        $newSignaletique = $input['signaletique'] ?? [];
        $newHoraire = $input['horaire'] ?? '';
        $newReponses = $input['reponses'] ?? [];
        
        if (empty($studyFolder) || empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyDataDir = __DIR__ . '/../studies/' . $studyFolder . '/data';
        $responsesFile = $studyDataDir . '/responses.enc';
        $refusedFile = $studyDataDir . '/refused.enc';
        
        $updated = false;
        
        $saveEncrypted = function($data, $file) {
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
            $encrypted = openssl_encrypt(json_encode($data), 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
            return file_put_contents($file, base64_encode($iv . '::' . $encrypted));
        };
        
        if (file_exists($responsesFile)) {
            $content = file_get_contents($responsesFile);
            $responses = decryptData($content) ?: [];
            
            foreach ($responses as &$response) {
                if ($response['id'] === $participantId) {
                    if (!isset($response['signaletique'])) $response['signaletique'] = [];
                    foreach ($newSignaletique as $key => $value) {
                        if ($key !== 'horaire') {
                            $response['signaletique'][$key] = $value;
                        }
                    }
                    if (!empty($newHoraire)) {
                        $response['horaire'] = $newHoraire;
                    }
                    $response['reponses'] = $newReponses;
                    $response['dateModification'] = date('c');
                    $response['modifiePar'] = 'admin';
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                $saveEncrypted(array_values($responses), $responsesFile);
            }
        }
        
        if (!$updated && file_exists($refusedFile)) {
            $content = file_get_contents($refusedFile);
            $refused = decryptData($content) ?: [];
            
            foreach ($refused as &$response) {
                if ($response['id'] === $participantId) {
                    if (!isset($response['signaletique'])) $response['signaletique'] = [];
                    foreach ($newSignaletique as $key => $value) {
                        if ($key !== 'horaire') {
                            $response['signaletique'][$key] = $value;
                        }
                    }
                    if (!empty($newHoraire)) {
                        $response['horaire'] = $newHoraire;
                    }
                    $response['reponses'] = $newReponses;
                    $response['dateModification'] = date('c');
                    $response['modifiePar'] = 'admin';
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                $saveEncrypted(array_values($refused), $refusedFile);
            }
        }
        
        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Participant mis à jour']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
        }
        exit;
    }
    
    if ($action === 'get_access_ids') {
        $studyFolder = $input['studyFolder'] ?? '';
        
        if (empty($studyFolder)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyFolder);
        $dataDir = __DIR__ . '/../studies/' . $studyFolder . '/data';
        $accessFile = $dataDir . '/access_ids.json';
        $responsesFile = $dataDir . '/responses.enc';
        $refusedFile = $dataDir . '/refused.enc';
        
        // Créer le dossier data s'il n'existe pas
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $ids = [];
        if (file_exists($accessFile)) {
            $data = json_decode(file_get_contents($accessFile), true);
            $ids = $data['ids'] ?? [];
        }
        
        $usedIds = [];
        if (file_exists($responsesFile)) {
            $content = file_get_contents($responsesFile);
            $responses = decryptData($content) ?: [];
            foreach ($responses as $r) {
                if (!empty($r['accessId'])) {
                    $usedIds[] = $r['accessId'];
                }
            }
        }
        if (file_exists($refusedFile)) {
            $content = file_get_contents($refusedFile);
            $refused = decryptData($content) ?: [];
            foreach ($refused as $r) {
                if (!empty($r['accessId'])) {
                    $usedIds[] = $r['accessId'];
                }
            }
        }
        
        echo json_encode(['success' => true, 'ids' => $ids, 'usedIds' => array_values(array_unique($usedIds))]);
        exit;
    }
    
    if ($action === 'add_access_ids') {
        $studyFolder = $input['studyFolder'] ?? '';
        $newIds = $input['ids'] ?? [];
        
        if (empty($studyFolder) || empty($newIds)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyFolder);
        $dataDir = __DIR__ . '/../studies/' . $studyFolder . '/data';
        $accessFile = $dataDir . '/access_ids.json';
        
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $existingIds = [];
        if (file_exists($accessFile)) {
            $data = json_decode(file_get_contents($accessFile), true);
            $existingIds = $data['ids'] ?? [];
        }
        
        foreach ($newIds as $id) {
            $id = trim($id);
            if (!empty($id) && !in_array($id, $existingIds)) {
                $existingIds[] = $id;
            }
        }
        
        file_put_contents($accessFile, json_encode([
            'ids' => $existingIds,
            'lastModified' => date('c')
        ], JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true, 'message' => 'IDs ajoutés', 'count' => count($existingIds)]);
        exit;
    }
    
    if ($action === 'remove_access_id') {
        $studyFolder = $input['studyFolder'] ?? '';
        $idToRemove = $input['id'] ?? '';
        
        if (empty($studyFolder) || empty($idToRemove)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        $studyFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $studyFolder);
        $accessFile = __DIR__ . '/../studies/' . $studyFolder . '/data/access_ids.json';
        
        if (!file_exists($accessFile)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé']);
            exit;
        }
        
        $data = json_decode(file_get_contents($accessFile), true);
        $ids = $data['ids'] ?? [];
        
        $ids = array_values(array_filter($ids, function($id) use ($idToRemove) {
            return $id !== $idToRemove;
        }));
        
        file_put_contents($accessFile, json_encode([
            'ids' => $ids,
            'lastModified' => date('c')
        ], JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true, 'message' => 'ID supprimé']);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    exit;
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
function detectStudies() {
    $studiesDir = __DIR__ . '/../studies';
    $studies = [];
    
    if (is_dir($studiesDir)) {
        $dirs = scandir($studiesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || strpos($dir, '_') === 0) {
                continue;
            }
            
            $questionFile = $studiesDir . '/' . $dir . '/questions.js';
            if (file_exists($questionFile)) {
                $config = parseStudyConfig($questionFile);
                if ($config) {
                    $config['folder'] = $dir;
                    
                    $statusFile = $studiesDir . '/' . $dir . '/status.json';
                    if (file_exists($statusFile)) {
                        $statusData = json_decode(file_get_contents($statusFile), true);
                        $config['status'] = $statusData['status'] ?? 'active';
                        $config['closedAt'] = $statusData['closedAt'] ?? null;
                    } else {
                        $config['status'] = 'active';
                        $config['closedAt'] = null;
                    }
                    
                    $studies[] = $config;
                }
            }
        }
    }
    
    $legacyFile = __DIR__ . '/../data/questions.js';
    if (file_exists($legacyFile) && empty($studies)) {
        $config = parseStudyConfig($legacyFile);
        if ($config) {
            $config['folder'] = '_legacy';
            $config['status'] = 'active';
            $config['closedAt'] = null;
            $studies[] = $config;
        }
    }
    
    return $studies;
}

function parseStudyConfig($filePath) {
    $content = file_get_contents($filePath);
    $config = [];
    
    if (preg_match("/studyId:\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $config['studyId'] = $matches[1];
    } else {
        return null;
    }
    
    if (preg_match("/studyTitle:\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $config['studyTitle'] = $matches[1];
    }
    if (preg_match("/studyDate:\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $config['studyDate'] = $matches[1];
    }
    if (preg_match("/totalParticipants:\s*(\d+)/", $content, $matches)) {
        $config['totalParticipants'] = intval($matches[1]);
    }
    
    $config['quotas'] = parseQuotasFromContent($content);
    
    return $config;
}
function parseQuotasFromContent($content) {
    $quotas = [];
    
    if (preg_match("/objectifs:\s*\{[\s\S]*?quotas:\s*\[\s*([\s\S]*?)\n\s{8}\]/", $content, $quotasSection)) {
        $quotasContent = $quotasSection[1];
    } 
    elseif (preg_match("/quotas:\s*\[\s*([\s\S]*?)\n\s{4}\]/", $content, $quotasSection)) {
        $quotasContent = $quotasSection[1];
    } else {
        return [];
    }
    
    $depth = 0;
    $currentBlock = '';
    $blocks = [];
    
    for ($i = 0; $i < strlen($quotasContent); $i++) {
        $char = $quotasContent[$i];
        
        if ($char === '{') {
            $depth++;
            $currentBlock .= $char;
        } elseif ($char === '}') {
            $currentBlock .= $char;
            $depth--;
            if ($depth === 0) {
                $blocks[] = trim($currentBlock);
                $currentBlock = '';
            }
        } elseif ($depth > 0) {
            $currentBlock .= $char;
        }
    }
    
    foreach ($blocks as $block) {
        $quota = parseQuotaBlock($block);
        if ($quota && !empty($quota['criteres'])) {
            $quotas[] = $quota;
        }
    }
    
    return $quotas;
}

function parseQuotaBlock($block) {
    $quota = [
        'id' => '',
        'titre' => '',
        'type' => 'simple',
        'source' => '',
        'sources' => [],
        'criteres' => []
    ];
    
    if (preg_match("/id:\s*['\"]([^'\"]+)['\"]/", $block, $m)) {
        $quota['id'] = $m[1];
    }
    if (preg_match("/titre:\s*['\"]([^'\"]+)['\"]/", $block, $m)) {
        $quota['titre'] = $m[1];
    }
    if (preg_match("/type:\s*['\"]([^'\"]+)['\"]/", $block, $m)) {
        $quota['type'] = $m[1];
    }
    if (preg_match("/source:\s*['\"]([^'\"]+)['\"]/", $block, $m)) {
        $quota['source'] = $m[1];
    }
    if (preg_match("/sources:\s*\[([^\]]+)\]/", $block, $m)) {
        preg_match_all("/['\"]([^'\"]+)['\"]/", $m[1], $sourcesList);
        $quota['sources'] = $sourcesList[1] ?? [];
    }
    
    $criteresStart = strpos($block, 'criteres:');
    if ($criteresStart !== false) {
        $afterCriteres = substr($block, $criteresStart + 9);
        $bracketStart = strpos($afterCriteres, '[');
        if ($bracketStart !== false) {
            $depth = 0;
            $criteresContent = '';
            $started = false;
            
            for ($i = $bracketStart; $i < strlen($afterCriteres); $i++) {
                $char = $afterCriteres[$i];
                if ($char === '[') {
                    $depth++;
                    $started = true;
                } elseif ($char === ']') {
                    $depth--;
                    if ($depth === 0 && $started) {
                        break;
                    }
                }
                if ($started && $depth > 0) {
                    $criteresContent .= $char;
                }
            }
            
            if ($quota['type'] === 'tranche') {
                preg_match_all("/\{[^}]*min:\s*(\d+)[^}]*max:\s*(\d+)[^}]*label:\s*['\"]([^'\"]+)['\"][^}]*objectif:\s*(\d+)[^}]*\}/s", $criteresContent, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $quota['criteres'][] = [
                        'type' => 'tranche',
                        'min' => intval($m[1]),
                        'max' => intval($m[2]),
                        'label' => $m[3],
                        'objectif' => intval($m[4])
                    ];
                }
            } elseif ($quota['type'] === 'contains') {
                preg_match_all("/\{[^}]*valeur:\s*['\"]([^'\"]+)['\"][^}]*label:\s*['\"]([^'\"]+)['\"][^}]*objectif:\s*(\d+)[^}]*present:\s*(true|false)[^}]*\}/s", $criteresContent, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $quota['criteres'][] = [
                        'type' => 'contains',
                        'valeur' => $m[1],
                        'label' => $m[2],
                        'objectif' => intval($m[3]),
                        'present' => $m[4] === 'true'
                    ];
                }
            } elseif ($quota['type'] === 'combine') {
                preg_match_all("/\{\s*\n?\s*id:\s*['\"]([^'\"]+)['\"][^}]*label:\s*['\"]([^'\"]+)['\"][^}]*objectif:\s*(\d+)/s", $criteresContent, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $quota['criteres'][] = [
                        'type' => 'combine',
                        'id' => $m[1],
                        'label' => $m[2],
                        'objectif' => intval($m[3])
                    ];
                }
            } else {
                preg_match_all("/\{[^}]*valeur:\s*['\"]([^'\"]+)['\"][^}]*label:\s*['\"]([^'\"]+)['\"][^}]*objectif:\s*(\d+)[^}]*\}/s", $criteresContent, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $quota['criteres'][] = [
                        'type' => 'simple',
                        'valeur' => $m[1],
                        'label' => $m[2],
                        'objectif' => intval($m[3])
                    ];
                }
            }
        }
    }
    
    return $quota;
}
function calculateQuotas($qualifies, $quotasConfig) {
    $results = [];
    
    foreach ($quotasConfig as $quotaConfig) {
        $quota = [
            'id' => $quotaConfig['id'],
            'titre' => $quotaConfig['titre'],
            'criteres' => []
        ];
        
        $type = $quotaConfig['type'] ?? 'simple';
        $source = $quotaConfig['source'] ?? '';
        
        foreach ($quotaConfig['criteres'] as $critere) {
            $count = 0;
            
            foreach ($qualifies as $q) {
                $reponses = $q['reponses'] ?? [];
                
                if ($type === 'tranche') {
                    if (isset($reponses[$source]['value'])) {
                        $value = intval($reponses[$source]['value']);
                        if ($value >= $critere['min'] && $value <= $critere['max']) {
                            $count++;
                        }
                    }
                } elseif ($type === 'contains') {
                    if (isset($reponses[$source]['values'])) {
                        $hasValue = in_array($critere['valeur'], $reponses[$source]['values']);
                        if ($hasValue === $critere['present']) {
                            $count++;
                        }
                    }
                } elseif ($type === 'combine') {
                    $critereId = $critere['id'] ?? '';
                    
                    $aEnfants = isset($reponses['q6']['value']) && $reponses['q6']['value'] === 'oui_moins_18';
                    $aAnimaux = isset($reponses['q7']['values']) && 
                                (in_array('chats', $reponses['q7']['values']) || in_array('chiens', $reponses['q7']['values']));
                    
                    if ($critereId === 'animaux_et_enfants' && $aEnfants && $aAnimaux) {
                        $count++;
                    } elseif ($critereId === 'animaux_ou_enfants' && ($aEnfants || $aAnimaux) && !($aEnfants && $aAnimaux)) {
                        $count++;
                    } elseif ($critereId === 'ni_animaux_ni_enfants' && !$aEnfants && !$aAnimaux) {
                        $count++;
                    }
                } else {
                    if (isset($reponses[$source]['value']) && $reponses[$source]['value'] === $critere['valeur']) {
                        $count++;
                    } elseif (isset($reponses[$source]['values']) && in_array($critere['valeur'], $reponses[$source]['values'])) {
                        $count++;
                    }
                }
            }
            
            $objectif = $critere['objectif'] ?? 0;
            $quota['criteres'][] = [
                'label' => $critere['label'],
                'objectif' => $objectif,
                'actuel' => $count,
                'atteint' => $count >= $objectif
            ];
        }
        
        $results[] = $quota;
    }
    
    $horaires = [];
    foreach ($qualifies as $q) {
        $h = $q['horaire'] ?? 'Non défini';
        if (!isset($horaires[$h])) $horaires[$h] = 0;
        $horaires[$h]++;
    }
    
    if (!empty($horaires)) {
        $horairesQuota = [
            'id' => 'horaires',
            'titre' => '⏰ Répartition horaires',
            'criteres' => []
        ];
        foreach ($horaires as $h => $count) {
            $horairesQuota['criteres'][] = [
                'label' => $h,
                'objectif' => null,
                'actuel' => $count,
                'atteint' => true
            ];
        }
        $results[] = $horairesQuota;
    }
    
    return $results;
}
function getStudyDataFiles($studyFolder) {
    $dataDir = __DIR__ . '/../studies/' . $studyFolder . '/data';
    return [
        'responses' => $dataDir . '/responses.enc',
        'refused' => $dataDir . '/refused.enc'
    ];
}

$studies = detectStudies();

$studiesData = [];

foreach ($studies as $study) {
    $studyId = $study['studyId'];
    $studyFolder = $study['folder'];
    
    $dataFiles = getStudyDataFiles($studyFolder);
    $studyResponses = loadResponses($dataFiles['responses']);
    $studyRefused = loadResponses($dataFiles['refused']);
    
    $stats = [
        'total' => count($studyResponses) + count($studyRefused),
        'qualifies' => 0,
        'refuses' => count($studyRefused),
        'en_cours' => 0
    ];
    
    $qualifies = [];
    $enCours = [];
    
    foreach ($studyResponses as $response) {
        switch ($response['statut']) {
            case 'QUALIFIE':
                $stats['qualifies']++;
                $qualifies[] = $response;
                break;
            default:
                $stats['en_cours']++;
                $enCours[] = $response;
        }
    }
    
    $quotas = calculateQuotas($qualifies, $study['quotas'] ?? []);
    
    $participantsQualifies = array_map(function($q) {
        return [
            'id' => $q['id'] ?? '',
            'accessId' => $q['accessId'] ?? '',
            'nom' => $q['signaletique']['nom'] ?? 'N/A',
            'prenom' => $q['signaletique']['prenom'] ?? 'N/A',
            'email' => $q['signaletique']['email'] ?? 'N/A',
            'telephone' => $q['signaletique']['telephone'] ?? 'N/A',
            'ville' => $q['signaletique']['ville'] ?? 'N/A',
            'adresse' => $q['signaletique']['adresse'] ?? 'N/A',
            'codePostal' => $q['signaletique']['codePostal'] ?? 'N/A',
            'horaire' => $q['horaire'] ?? 'N/A',
            'date' => $q['dateFin'] ?? $q['dateDebut'] ?? 'N/A',
            'statut' => 'QUALIFIÉ',
            'reponses' => $q['reponses'] ?? []
        ];
    }, $qualifies);
    
    $participantsRefuses = array_map(function($q) {
        $raisons = [];
        if (!empty($q['toutesRaisonsStop'])) {
            foreach ($q['toutesRaisonsStop'] as $stop) {
                $raisons[] = $stop['raison'] ?? '';
            }
        } elseif (!empty($q['raisonStop'])) {
            $raisons[] = $q['raisonStop'];
        }
        
        return [
            'id' => $q['id'] ?? '',
            'accessId' => $q['accessId'] ?? '',
            'nom' => $q['signaletique']['nom'] ?? 'N/A',
            'prenom' => $q['signaletique']['prenom'] ?? 'N/A',
            'email' => $q['signaletique']['email'] ?? 'N/A',
            'telephone' => $q['signaletique']['telephone'] ?? 'N/A',
            'raisons' => $raisons,
            'raisonPrincipale' => $q['raisonStop'] ?? 'Non spécifiée',
            'date' => $q['dateFin'] ?? $q['dateDebut'] ?? 'N/A',
            'statut' => 'REFUSÉ',
            'reponses' => $q['reponses'] ?? []
        ];
    }, array_values($studyRefused));
    
    $participantsEnCours = array_map(function($q) {
        $questionsRepondues = count($q['reponses'] ?? []);
        return [
            'id' => $q['id'] ?? '',
            'accessId' => $q['accessId'] ?? '',
            'nom' => $q['signaletique']['nom'] ?? 'N/A',
            'prenom' => $q['signaletique']['prenom'] ?? 'N/A',
            'email' => $q['signaletique']['email'] ?? 'N/A',
            'telephone' => $q['signaletique']['telephone'] ?? 'N/A',
            'ville' => $q['signaletique']['ville'] ?? 'N/A',
            'adresse' => $q['signaletique']['adresse'] ?? 'N/A',
            'codePostal' => $q['signaletique']['codePostal'] ?? 'N/A',
            'dateDebut' => $q['dateDebut'] ?? 'N/A',
            'derniereActivite' => $q['dateFin'] ?? $q['dateDebut'] ?? 'N/A',
            'questionsRepondues' => $questionsRepondues,
            'statut' => 'EN_COURS',
            'reponses' => $q['reponses'] ?? []
        ];
    }, $enCours);
    
    $studiesData[] = [
        'studyId' => $studyId,
        'studyName' => $study['studyTitle'] ?? 'Étude',
        'studyDate' => $study['studyDate'] ?? '',
        'folder' => $study['folder'] ?? '',
        'status' => $study['status'] ?? 'active',
        'closedAt' => $study['closedAt'] ?? null,
        'targetParticipants' => $study['totalParticipants'] ?? 5,
        'stats' => $stats,
        'quotas' => $quotas,
        'qualifies' => $participantsQualifies,
        'refuses' => $participantsRefuses,
        'enCours' => $participantsEnCours
    ];
}

echo json_encode([
    'success' => true,
    'multiStudy' => true,
    'studies' => $studiesData,
    'studyId' => $studiesData[0]['studyId'] ?? 'N/A',
    'studyName' => $studiesData[0]['studyName'] ?? 'Étude',
    'studyDate' => $studiesData[0]['studyDate'] ?? '',
    'targetParticipants' => $studiesData[0]['targetParticipants'] ?? 5,
    'stats' => $studiesData[0]['stats'] ?? ['total' => 0, 'qualifies' => 0, 'refuses' => 0, 'en_cours' => 0],
    'quotas' => $studiesData[0]['quotas'] ?? [],
    'qualifies' => $studiesData[0]['qualifies'] ?? [],
    'refuses' => $studiesData[0]['refuses'] ?? [],
    'disqualifies' => $studiesData[0]['refuses'] ?? []
]);
