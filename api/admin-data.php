<?php
/**
 * ============================================================
 * API DONNÉES ADMIN - VERSION MYSQL HYBRIDE
 * ============================================================
 * Combine le parsing des quotas original avec lecture MySQL
 */

require_once 'db.php';
require_once 'security.php';

// Démarrer la session sécurisée (cookie accessible depuis tous les chemins)
secureSessionStart();
header('Content-Type: application/json');

define('SESSION_TIMEOUT', 3600);

// ============================================================
// VÉRIFICATION D'AUTHENTIFICATION
// ============================================================

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

// ============================================================
// TRAITEMENT DES REQUÊTES POST (actions)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // Actions qui modifient des données = vérification CSRF obligatoire
    $actionsRequiringCsrf = [
        'delete_participant',
        'update_participant',
        'update_responses',
        'add_access_ids',
        'delete_access_id',
        'move_to_qualified'
    ];

    if (in_array($action, $actionsRequiringCsrf)) {
        $csrfToken = $input['csrf_token'] ?? null;
        if (!validateCsrfToken($csrfToken)) {
            echo json_encode([
                'success' => false,
                'error' => 'Token CSRF invalide. Veuillez rafraîchir la page.'
            ]);
            exit;
        }
    }
    
    if ($action === 'delete_participant') {
        $participantId = $input['participantId'] ?? '';
        
        if (empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'ID participant manquant']);
            exit;
        }
        
        try {
            $response = dbQueryOne("SELECT id FROM responses WHERE unique_id = ?", [$participantId]);
            
            if ($response) {
                dbExecute("DELETE FROM responses WHERE id = ?", [$response['id']]);
                echo json_encode(['success' => true, 'message' => 'Participant supprimé']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_participant') {
        $participantId = $input['participantId'] ?? '';
        $newSignaletique = $input['signaletique'] ?? [];
        $newHoraire = $input['horaire'] ?? '';
        $newReponses = $input['reponses'] ?? [];
        
        if (empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'ID participant manquant']);
            exit;
        }
        
        try {
            dbBeginTransaction();
            
            $response = dbQueryOne("SELECT id FROM responses WHERE unique_id = ?", [$participantId]);
            
            if (!$response) {
                throw new Exception('Participant non trouvé');
            }
            
            $responseId = $response['id'];
            
            if ($newHoraire !== '') {
                dbExecute(
                    "UPDATE responses SET horaire = ?, modified_at = NOW(), modified_by = 'admin' WHERE id = ?",
                    [$newHoraire, $responseId]
                );
            }
            
            if (!empty($newSignaletique)) {
                $existingSig = dbQueryOne("SELECT id FROM signaletiques WHERE response_id = ?", [$responseId]);
                
                if ($existingSig) {
                    dbExecute(
                        "UPDATE signaletiques SET 
                            nom = COALESCE(?, nom), prenom = COALESCE(?, prenom),
                            email = COALESCE(?, email), telephone = COALESCE(?, telephone),
                            adresse = COALESCE(?, adresse), code_postal = COALESCE(?, code_postal),
                            ville = COALESCE(?, ville)
                         WHERE response_id = ?",
                        [
                            $newSignaletique['nom'] ?? null, $newSignaletique['prenom'] ?? null,
                            $newSignaletique['email'] ?? null, $newSignaletique['telephone'] ?? null,
                            $newSignaletique['adresse'] ?? null, $newSignaletique['codePostal'] ?? null,
                            $newSignaletique['ville'] ?? null, $responseId
                        ]
                    );
                } else {
                    dbExecute(
                        "INSERT INTO signaletiques (response_id, nom, prenom, email, telephone, adresse, code_postal, ville)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $responseId, $newSignaletique['nom'] ?? null, $newSignaletique['prenom'] ?? null,
                            $newSignaletique['email'] ?? null, $newSignaletique['telephone'] ?? null,
                            $newSignaletique['adresse'] ?? null, $newSignaletique['codePostal'] ?? null,
                            $newSignaletique['ville'] ?? null
                        ]
                    );
                }
            }
            
            foreach ($newReponses as $questionId => $answer) {
                $answerValue = $answer['value'] ?? null;
                $answerValues = isset($answer['values']) ? json_encode($answer['values']) : null;
                $answerText = $answer['text'] ?? null;
                $complexData = array_diff_key($answer, array_flip(['value', 'values', 'text']));
                $answerData = !empty($complexData) ? json_encode($complexData) : null;
                
                dbExecute(
                    "INSERT INTO answers (response_id, question_id, answer_value, answer_values, answer_text, answer_data)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                        answer_value = VALUES(answer_value), answer_values = VALUES(answer_values),
                        answer_text = VALUES(answer_text), answer_data = VALUES(answer_data), updated_at = NOW()",
                    [$responseId, $questionId, $answerValue, $answerValues, $answerText, $answerData]
                );
            }
            
            dbCommit();
            echo json_encode(['success' => true, 'message' => 'Participant mis à jour']);
            
        } catch (Exception $e) {
            dbRollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'update_responses') {
        $participantId = $input['participantId'] ?? '';
        $newReponses = $input['reponses'] ?? [];
        
        if (empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'ID participant manquant']);
            exit;
        }
        
        try {
            $response = dbQueryOne("SELECT id FROM responses WHERE unique_id = ?", [$participantId]);
            
            if (!$response) {
                echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
                exit;
            }
            
            dbBeginTransaction();
            
            foreach ($newReponses as $questionId => $answer) {
                $answerValue = $answer['value'] ?? null;
                $answerValues = isset($answer['values']) ? json_encode($answer['values']) : null;
                $answerText = $answer['text'] ?? null;
                $complexData = array_diff_key($answer, array_flip(['value', 'values', 'text']));
                $answerData = !empty($complexData) ? json_encode($complexData) : null;
                
                dbExecute(
                    "INSERT INTO answers (response_id, question_id, answer_value, answer_values, answer_text, answer_data)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                        answer_value = VALUES(answer_value), answer_values = VALUES(answer_values),
                        answer_text = VALUES(answer_text), answer_data = VALUES(answer_data), updated_at = NOW()",
                    [$response['id'], $questionId, $answerValue, $answerValues, $answerText, $answerData]
                );
            }
            
            dbExecute("UPDATE responses SET modified_at = NOW(), modified_by = 'admin' WHERE id = ?", [$response['id']]);
            
            dbCommit();
            echo json_encode(['success' => true, 'message' => 'Réponses mises à jour']);
            
        } catch (Exception $e) {
            dbRollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'add_access_ids') {
        $studyFolder = $input['studyFolder'] ?? '';
        $newIds = $input['ids'] ?? [];
        
        if (empty($studyFolder) || empty($newIds)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        try {
            $study = dbQueryOne("SELECT id FROM studies WHERE folder_name = ? OR study_id = ?", [$studyFolder, $studyFolder]);
            
            $added = 0;
            if ($study) {
                foreach ($newIds as $accessCode) {
                    $accessCode = trim($accessCode);
                    if (empty($accessCode)) continue;
                    
                    $existing = dbQueryOne("SELECT id FROM access_ids WHERE study_id = ? AND access_code = ?", [$study['id'], $accessCode]);
                    
                    if (!$existing) {
                        dbExecute("INSERT INTO access_ids (study_id, access_code) VALUES (?, ?)", [$study['id'], $accessCode]);
                        $added++;
                    }
                }
            }
            
            // Mettre à jour aussi le fichier JSON
            $accessIdsFile = __DIR__ . '/../studies/' . $studyFolder . '/data/access_ids.json';
            $existingIds = [];
            if (file_exists($accessIdsFile)) {
                $existingIds = json_decode(file_get_contents($accessIdsFile), true) ?: [];
            }
            $allIds = array_unique(array_merge($existingIds, $newIds));
            sort($allIds);
            
            $dataDir = dirname($accessIdsFile);
            if (!file_exists($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            file_put_contents($accessIdsFile, json_encode(array_values($allIds)));
            
            echo json_encode(['success' => true, 'message' => "$added ID(s) ajouté(s)", 'added' => $added]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    // ============================================================
    // ACTION : Récupérer les IDs d'accès configurés
    // ============================================================
    if ($action === 'get_access_ids') {
        $studyFolder = $input['studyFolder'] ?? '';
        
        if (empty($studyFolder)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        try {
            // Récupérer depuis MySQL
            $study = dbQueryOne("SELECT id FROM studies WHERE folder_name = ? OR study_id = ?", [$studyFolder, $studyFolder]);
            
            $ids = [];
            $usedIds = [];
            
            if ($study) {
                // Récupérer tous les IDs configurés depuis MySQL
                $accessIds = dbQuery("SELECT access_code FROM access_ids WHERE study_id = ? ORDER BY access_code", [$study['id']]);
                foreach ($accessIds as $row) {
                    $ids[] = $row['access_code'];
                }
                
                // Récupérer les IDs déjà utilisés par les participants
                $responses = dbQuery("SELECT access_id FROM responses WHERE study_id = ? AND access_id IS NOT NULL AND access_id != ''", [$study['id']]);
                foreach ($responses as $row) {
                    $usedIds[] = $row['access_id'];
                }
            }
            
            // Fallback: lire depuis le fichier JSON si pas dans MySQL
            if (empty($ids)) {
                $accessIdsFile = __DIR__ . '/../studies/' . $studyFolder . '/data/access_ids.json';
                if (file_exists($accessIdsFile)) {
                    $fileIds = json_decode(file_get_contents($accessIdsFile), true) ?: [];
                    // Nettoyer les IDs (enlever les tableaux imbriqués et valeurs invalides)
                    foreach ($fileIds as $id) {
                        if (is_string($id) && !empty(trim($id)) && !strpos($id, 'T') && strlen($id) < 50) {
                            $ids[] = trim($id);
                        } elseif (is_array($id)) {
                            // Gérer les tableaux imbriqués
                            foreach ($id as $subId) {
                                if (is_string($subId) && !empty(trim($subId))) {
                                    $ids[] = trim($subId);
                                }
                            }
                        }
                    }
                    $ids = array_unique($ids);
                    sort($ids);
                }
            }
            
            echo json_encode(['success' => true, 'ids' => array_values($ids), 'usedIds' => array_values(array_unique($usedIds))]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'delete_access_id') {
        $studyFolder = $input['studyFolder'] ?? '';
        $accessCode = $input['accessCode'] ?? $input['id'] ?? '';
        
        if (empty($studyFolder) || empty($accessCode)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            exit;
        }
        
        try {
            $study = dbQueryOne("SELECT id FROM studies WHERE folder_name = ? OR study_id = ?", [$studyFolder, $studyFolder]);
            
            if ($study) {
                dbExecute("DELETE FROM access_ids WHERE study_id = ? AND access_code = ?", [$study['id'], $accessCode]);
            }
            
            $accessIdsFile = __DIR__ . '/../studies/' . $studyFolder . '/data/access_ids.json';
            if (file_exists($accessIdsFile)) {
                $ids = json_decode(file_get_contents($accessIdsFile), true) ?: [];
                $ids = array_filter($ids, fn($id) => $id !== $accessCode);
                file_put_contents($accessIdsFile, json_encode(array_values($ids)));
            }
            
            echo json_encode(['success' => true, 'message' => 'ID supprimé']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    // Action : Récupérer les logs de connexion
    if ($action === 'get_logs') {
        try {
            $logs = dbQuery(
                "SELECT action, username, ip_address, details, created_at 
                 FROM admin_logs 
                 ORDER BY created_at DESC 
                 LIMIT 100"
            );
            
            // Formater les logs comme du texte
            $logsText = '';
            foreach ($logs as $log) {
                $line = $log['created_at'] . ' - ';
                if ($log['action'] === 'login_success') {
                    $line .= "Connexion réussie: " . ($log['username'] ?? 'inconnu');
                } elseif ($log['action'] === 'login_failed') {
                    $line .= "Tentative échouée pour: " . ($log['username'] ?? 'inconnu');
                } elseif ($log['action'] === 'logout') {
                    $line .= "Déconnexion: " . ($log['username'] ?? 'inconnu');
                } else {
                    $line .= ($log['details'] ?? $log['action']);
                }
                $logsText .= $line . "\n";
            }
            
            echo json_encode(['success' => true, 'logs' => $logsText]);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'logs' => 'Erreur de chargement des logs: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Action : Déplacer un participant refusé vers les qualifiés
    if ($action === 'move_to_qualified') {
        $studyId = $input['studyId'] ?? '';
        $participantId = $input['participantId'] ?? '';
        
        if (empty($participantId)) {
            echo json_encode(['success' => false, 'error' => 'ID participant manquant']);
            exit;
        }
        
        try {
            // Trouver la réponse
            $response = dbQueryOne("SELECT id, status FROM responses WHERE unique_id = ?", [$participantId]);
            
            if (!$response) {
                echo json_encode(['success' => false, 'error' => 'Participant non trouvé']);
                exit;
            }
            
            // Mettre à jour le statut vers QUALIFIE
            dbExecute(
                "UPDATE responses SET status = 'QUALIFIE', stop_reason = NULL, all_stop_reasons = NULL, modified_at = NOW(), modified_by = 'admin' WHERE id = ?",
                [$response['id']]
            );
            
            echo json_encode(['success' => true, 'message' => 'Participant déplacé vers les qualifiés']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// ============================================================
// FONCTIONS DE PARSING DES QUOTAS (depuis l'original)
// ============================================================

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
    
    // Gérer les apostrophes échappées dans le titre
    if (preg_match("/titre:\s*'((?:[^'\\\\]|\\\\.)*)'/", $block, $m)) {
        $quota['titre'] = stripslashes($m[1]);
    } elseif (preg_match('/titre:\s*"((?:[^"\\\\]|\\\\.)*)"/', $block, $m)) {
        $quota['titre'] = stripslashes($m[1]);
    } elseif (preg_match("/titre:\s*['\"]([^'\"]+)['\"]/", $block, $m)) {
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
    
    // Horaires
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

// ============================================================
// FONCTIONS DE DÉTECTION ET CHARGEMENT
// ============================================================

function detectStudies() {
    $studiesDir = __DIR__ . '/../studies';
    $studies = [];
    
    if (is_dir($studiesDir)) {
        $dirs = scandir($studiesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || strpos($dir, '_') === 0 || $dir === 'closed.html') {
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
                    
                    // S'assurer que l'étude existe dans MySQL
                    // Essayer d'abord avec study_type, sinon sans
                    $dbStudy = null;
                    try {
                        $dbStudy = dbQueryOne(
                            "SELECT id, study_type FROM studies WHERE study_id = ? OR folder_name = ?",
                            [$config['studyId'], $dir]
                        );
                    } catch (Exception $e) {
                        // Fallback si study_type n'existe pas
                        $dbStudy = dbQueryOne(
                            "SELECT id FROM studies WHERE study_id = ? OR folder_name = ?",
                            [$config['studyId'], $dir]
                        );
                    }
                    
                    // Détecter le type d'étude depuis le fichier (anonymousMode = data_collection)
                    // IMPORTANT: Lire le contenu du fichier pour la détection
                    $fileContent = file_get_contents($questionFile);
                    $detectedType = 'classic';
                    if (preg_match("/anonymousMode:\s*true/", $fileContent)) {
                        $detectedType = 'data_collection';
                    }
                    
                    if (!$dbStudy) {
                        // Essayer d'insérer avec study_type, sinon sans
                        try {
                            dbExecute(
                                "INSERT INTO studies (study_id, folder_name, title, study_date, status, study_type) VALUES (?, ?, ?, ?, ?, ?)",
                                [$config['studyId'], $dir, $config['studyTitle'] ?? $dir, $config['studyDate'] ?? '', $config['status'], $detectedType]
                            );
                        } catch (Exception $e) {
                            // Fallback sans study_type
                            dbExecute(
                                "INSERT INTO studies (study_id, folder_name, title, study_date, status) VALUES (?, ?, ?, ?, ?)",
                                [$config['studyId'], $dir, $config['studyTitle'] ?? $dir, $config['studyDate'] ?? '', $config['status']]
                            );
                        }
                        $config['dbId'] = dbLastId();
                        $config['studyType'] = $detectedType;
                    } else {
                        $config['dbId'] = $dbStudy['id'];
                        // TOUJOURS utiliser le type détecté depuis le fichier (source de vérité)
                        $config['studyType'] = $detectedType;
                        
                        // Mettre à jour le type en base si différent
                        if (($dbStudy['study_type'] ?? 'classic') !== $detectedType) {
                            try {
                                dbExecute("UPDATE studies SET study_type = ? WHERE id = ?", [$detectedType, $dbStudy['id']]);
                            } catch (Exception $e) {
                                // Ignorer si la colonne n'existe pas
                            }
                        }
                    }
                    
                    // Synchroniser les IDs d'accès depuis le fichier JSON vers MySQL
                    syncAccessIds($config['dbId'], $studiesDir . '/' . $dir);
                    
                    $studies[] = $config;
                }
            }
        }
    }
    
    return $studies;
}

/**
 * Synchronise les IDs d'accès d'un fichier JSON vers MySQL
 */
function syncAccessIds($studyDbId, $studyPath) {
    $accessIdsFile = $studyPath . '/data/access_ids.json';
    if (!file_exists($accessIdsFile)) {
        return;
    }
    
    $fileIds = json_decode(file_get_contents($accessIdsFile), true);
    if (!is_array($fileIds)) {
        return;
    }
    
    // Fonction récursive pour aplatir les tableaux imbriqués
    $flattenIds = function($arr) use (&$flattenIds) {
        $result = [];
        foreach ($arr as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $flattenIds($item));
            } elseif (is_string($item) && !empty(trim($item))) {
                $result[] = trim($item);
            }
        }
        return $result;
    };
    
    $allIds = array_unique($flattenIds($fileIds));
    
    foreach ($allIds as $accessCode) {
        // Vérifier si l'ID existe déjà dans MySQL
        $existing = dbQueryOne(
            "SELECT id FROM access_ids WHERE study_id = ? AND access_code = ?",
            [$studyDbId, $accessCode]
        );
        
        if (!$existing) {
            try {
                dbExecute(
                    "INSERT INTO access_ids (study_id, access_code) VALUES (?, ?)",
                    [$studyDbId, $accessCode]
                );
            } catch (Exception $e) {
                // Ignorer les erreurs de duplication
            }
        }
    }
}

function parseStudyConfig($filePath) {
    $content = file_get_contents($filePath);
    $config = [];
    
    if (preg_match("/studyId:\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
        $config['studyId'] = $matches[1];
    } else {
        return null;
    }
    
    // Regex amélioré pour gérer les apostrophes/guillemets échappés
    // Match avec apostrophe simple (gère \')
    if (preg_match("/studyTitle:\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $matches)) {
        $config['studyTitle'] = stripslashes($matches[1]);
    }
    // Match avec guillemets doubles (gère \")
    elseif (preg_match('/studyTitle:\s*"((?:[^"\\\\]|\\\\.)*)"/', $content, $matches)) {
        $config['studyTitle'] = stripslashes($matches[1]);
    }
    
    // Même correction pour studyDate
    if (preg_match("/studyDate:\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $matches)) {
        $config['studyDate'] = stripslashes($matches[1]);
    } elseif (preg_match('/studyDate:\s*"((?:[^"\\\\]|\\\\.)*)"/', $content, $matches)) {
        $config['studyDate'] = stripslashes($matches[1]);
    }
    
    if (preg_match("/totalParticipants:\s*(\d+)/", $content, $matches)) {
        $config['totalParticipants'] = intval($matches[1]);
    }
    
    $config['quotas'] = parseQuotasFromContent($content);
    
    return $config;
}

function getResponseAnswers($responseInternalId) {
    $answers = dbQuery(
        "SELECT question_id, answer_value, answer_values, answer_text, answer_data FROM answers WHERE response_id = ?",
        [$responseInternalId]
    );
    
    $result = [];
    foreach ($answers as $answer) {
        $data = [];
        
        if ($answer['answer_value'] !== null) {
            $data['value'] = $answer['answer_value'];
        }
        if ($answer['answer_values'] !== null) {
            $decoded = json_decode($answer['answer_values'], true);
            if ($decoded !== null) {
                $data['values'] = $decoded;
            }
        }
        if ($answer['answer_text'] !== null) {
            $data['text'] = $answer['answer_text'];
        }
        if ($answer['answer_data'] !== null) {
            $extraData = json_decode($answer['answer_data'], true);
            if ($extraData) {
                $data = array_merge($data, $extraData);
            }
        }
        
        if (!empty($data)) {
            $result[$answer['question_id']] = $data;
        }
    }
    
    return $result;
}

function getSignaletique($responseInternalId) {
    $sig = dbQueryOne(
        "SELECT nom, prenom, email, telephone, adresse, code_postal, ville FROM signaletiques WHERE response_id = ?",
        [$responseInternalId]
    );
    
    if (!$sig) return [];
    
    return [
        'nom' => $sig['nom'],
        'prenom' => $sig['prenom'],
        'email' => $sig['email'],
        'telephone' => $sig['telephone'],
        'adresse' => $sig['adresse'],
        'codePostal' => $sig['code_postal'],
        'ville' => $sig['ville']
    ];
}

/**
 * Calcule les métriques de qualité pour les études data_collection
 */
function calculateDataQualityMetrics($reponses, $behaviorMetrics, $studyId = '') {
    $metrics = [
        'attentionChecksPassed' => 0,
        'attentionChecksTotal' => 0,
        'trustScore' => $behaviorMetrics['trustScore'] ?? null,
        'pasteEvents' => $behaviorMetrics['pasteEvents'] ?? 0,
        'sessionDuration' => $behaviorMetrics['sessionDuration'] ?? null,
        'tabSwitches' => $behaviorMetrics['tabSwitches'] ?? 0,
        'totalTextResponses' => 0,
        'avgWordCount' => 0,
        'categoriesCompleted' => [],
        'overallQualityScore' => 100
    ];
    
    // Mapping des réponses correctes par étude
    $attentionAnswersByStudy = [
        'DATA_IA_JAN2026' => [
            'p1_attention_check_1' => 'blue',
            'p4_attention_check_2' => 'disagree'
        ],
        'CULTURE_FR_2026' => [
            'p1_attention_check' => 'rdv',
            'p4_attention_check' => 'trois'
        ],
        'PREF_STYLE_FR_2026' => [
            'p1_attention_check' => 'orange',
            'p3_attention_check' => 'stress'
        ],
        'EVAL_IA_EXPRESS_2026' => [
            'attention_1' => 'A',
            'attention_2' => 'vérifié'
        ]
    ];
    
    // Récupérer le mapping pour cette étude
    $attentionAnswers = $attentionAnswersByStudy[$studyId] ?? [];
    
    $wordCounts = [];
    $categories = [];
    
    // Analyser les réponses
    foreach ($reponses as $questionId => $answer) {
        // Détecter les attention checks (toute question contenant "attention")
        if (strpos($questionId, 'attention') !== false) {
            $metrics['attentionChecksTotal']++;
            
            $answerValue = $answer['value'] ?? '';
            
            // Vérifier si la réponse est correcte selon le mapping de l'étude
            if (isset($attentionAnswers[$questionId])) {
                $expected = $attentionAnswers[$questionId];
                $actual = is_string($answerValue) ? mb_strtolower(trim($answerValue)) : $answerValue;
                $expectedLower = is_string($expected) ? mb_strtolower(trim($expected)) : $expected;
                
                // Comparaison flexible pour les textes
                if ($actual === $expectedLower || 
                    (is_string($actual) && strpos($actual, $expectedLower) !== false)) {
                    $metrics['attentionChecksPassed']++;
                }
            }
        }
        
        // Compter les réponses texte
        if (isset($answer['value']) && is_string($answer['value']) && strlen($answer['value']) > 50) {
            $metrics['totalTextResponses']++;
            $wordCount = str_word_count($answer['value']);
            $wordCounts[] = $wordCount;
        }
        
        // Identifier les catégories complétées
        if (preg_match('/^p(\d+)_([a-z]+)_/', $questionId, $m)) {
            $cat = $m[2];
            if (!in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
    }
    
    // Calculer la moyenne de mots
    if (count($wordCounts) > 0) {
        $metrics['avgWordCount'] = round(array_sum($wordCounts) / count($wordCounts));
    }
    
    $metrics['categoriesCompleted'] = $categories;
    
    // Calculer le score de qualité global
    $qualityScore = 100;
    
    // Pénalités attention checks
    if ($metrics['attentionChecksTotal'] > 0) {
        $ratio = $metrics['attentionChecksPassed'] / $metrics['attentionChecksTotal'];
        if ($ratio < 1) {
            $qualityScore -= (1 - $ratio) * 30;
        }
    }
    
    // Pénalités comportementales
    if ($metrics['trustScore'] !== null && $metrics['trustScore'] < 70) {
        $qualityScore -= (70 - $metrics['trustScore']) / 2;
    }
    if ($metrics['pasteEvents'] > 5) {
        $qualityScore -= 10;
    }
    if ($metrics['sessionDuration'] !== null && $metrics['sessionDuration'] < 300) {
        $qualityScore -= 15;
    }
    
    $metrics['overallQualityScore'] = max(0, min(100, round($qualityScore)));
    
    return $metrics;
}

// ============================================================
// REQUÊTE GET : Récupérer toutes les données
// ============================================================

$userRole = $_SESSION['user_role'] ?? 'user';
$allowedStudies = $_SESSION['allowed_studies'] ?? [];

$studies = detectStudies();

// Filtrer selon les permissions
if ($userRole === 'user' && !in_array('*', $allowedStudies)) {
    $studies = array_filter($studies, function($study) use ($allowedStudies) {
        return in_array($study['folder'], $allowedStudies) || in_array($study['studyId'], $allowedStudies);
    });
    $studies = array_values($studies);
}

$studiesData = [];

foreach ($studies as $study) {
    $studyDbId = $study['dbId'];
    $studyFolder = $study['folder'];
    $studyType = $study['studyType'] ?? 'classic';
    
    // Récupérer les participants depuis MySQL
    // Essayer d'abord avec behavior_metrics, sinon sans
    try {
        $allResponses = dbQuery(
            "SELECT r.id as internal_id, r.unique_id, r.access_id, r.status, r.stop_reason, 
                    r.all_stop_reasons, r.horaire, r.started_at, r.completed_at, r.behavior_metrics
             FROM responses r WHERE r.study_id = ? ORDER BY r.started_at DESC",
            [$studyDbId]
        );
    } catch (Exception $e) {
        // Fallback sans behavior_metrics si la colonne n'existe pas
        $allResponses = dbQuery(
            "SELECT r.id as internal_id, r.unique_id, r.access_id, r.status, r.stop_reason, 
                    r.all_stop_reasons, r.horaire, r.started_at, r.completed_at, NULL as behavior_metrics
             FROM responses r WHERE r.study_id = ? ORDER BY r.started_at DESC",
            [$studyDbId]
        );
    }
    
    $qualifies = [];
    $refuses = [];
    $enCours = [];
    
    foreach ($allResponses as $r) {
        $internalId = $r['internal_id'];
        $sig = getSignaletique($internalId);
        $reponses = getResponseAnswers($internalId);
        
        // Parser les métriques comportementales
        $behaviorMetrics = null;
        if (!empty($r['behavior_metrics'])) {
            $behaviorMetrics = json_decode($r['behavior_metrics'], true);
        }
        
        // Calculer les métriques de qualité pour études data_collection
        $qualityData = null;
        if ($studyType === 'data_collection') {
            $qualityData = calculateDataQualityMetrics($reponses, $behaviorMetrics, $studyFolder);
        }
        
        $participant = [
            'id' => $r['unique_id'],
            'accessId' => $r['access_id'] ?? '',
            'nom' => $sig['nom'] ?? 'N/A',
            'prenom' => $sig['prenom'] ?? 'N/A',
            'email' => $sig['email'] ?? 'N/A',
            'telephone' => $sig['telephone'] ?? 'N/A',
            'ville' => $sig['ville'] ?? 'N/A',
            'adresse' => $sig['adresse'] ?? 'N/A',
            'codePostal' => $sig['codePostal'] ?? 'N/A',
            'horaire' => $r['horaire'] ?? 'N/A',
            'dateDebut' => $r['started_at'] ?? 'N/A',
            'dateFin' => $r['completed_at'] ?? $r['started_at'] ?? 'N/A',
            'date' => $r['completed_at'] ?? $r['started_at'] ?? 'N/A',
            'reponses' => $reponses,
            'behaviorMetrics' => $behaviorMetrics,
            'qualityData' => $qualityData
        ];
        
        if ($r['status'] === 'QUALIFIE') {
            $participant['statut'] = 'QUALIFIÉ';
            $qualifies[] = $participant;
        } elseif ($r['status'] === 'REFUSE') {
            $raisons = [];
            if (!empty($r['all_stop_reasons'])) {
                $allReasons = json_decode($r['all_stop_reasons'], true);
                if ($allReasons) {
                    foreach ($allReasons as $stop) {
                        $raisons[] = $stop['raison'] ?? '';
                    }
                }
            } elseif (!empty($r['stop_reason'])) {
                $raisons[] = $r['stop_reason'];
            }
            
            $participant['statut'] = 'REFUSÉ';
            $participant['raisons'] = $raisons;
            $participant['raisonPrincipale'] = $r['stop_reason'] ?? 'Non spécifiée';
            $refuses[] = $participant;
        } else {
            $participant['statut'] = 'EN_COURS';
            $participant['questionsRepondues'] = count($reponses);
            $participant['derniereActivite'] = $r['completed_at'] ?? $r['started_at'] ?? 'N/A';
            $enCours[] = $participant;
        }
    }
    
    $stats = [
        'total' => count($allResponses),
        'qualifies' => count($qualifies),
        'refuses' => count($refuses),
        'en_cours' => count($enCours)
    ];
    
    $quotas = calculateQuotas($qualifies, $study['quotas'] ?? []);
    
    $studiesData[] = [
        'studyId' => $study['studyId'],
        'studyName' => $study['studyTitle'] ?? $study['studyId'],
        'studyDate' => $study['studyDate'] ?? '',
        'studyType' => $studyType,
        'folder' => $study['folder'],
        'status' => $study['status'],
        'closedAt' => $study['closedAt'],
        'targetParticipants' => $study['totalParticipants'] ?? 5,
        'stats' => $stats,
        'quotas' => $quotas,
        'qualifies' => $qualifies,
        'refuses' => $refuses,
        'enCours' => $enCours
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