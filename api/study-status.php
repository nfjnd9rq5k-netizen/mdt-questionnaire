<?php
/**
 * ============================================================
 * API GESTION STATUT DES ÉTUDES
 * ============================================================
 * Actions: close, reopen, delete
 */

require_once 'config.php';
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Support des deux formats de paramètres (ancien et nouveau)
$studyFolder = $input['folder'] ?? $input['studyFolder'] ?? null;
$status = $input['status'] ?? null;
$action = $input['action'] ?? null;

// Convertir status en action si nécessaire
if ($status && !$action) {
    $action = ($status === 'closed') ? 'close' : 'reopen';
}

if (!$studyFolder || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides', 'received' => $input]);
    exit;
}

// Vérification des permissions pour suppression
$userRole = $_SESSION['user_role'] ?? 'user';
if ($action === 'delete' && $userRole !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Seul un Super Admin peut supprimer une étude']);
    exit;
}

$studyFolder = basename($studyFolder); // Sécurité
$studyPath = __DIR__ . '/../studies/' . $studyFolder;
$statusFile = $studyPath . '/status.json';

// Pour close et reopen, vérifier que le dossier existe
if (in_array($action, ['close', 'reopen', 'open']) && !is_dir($studyPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Étude non trouvée: ' . $studyFolder]);
    exit;
}

// ============================================================
// ACTION: FERMER L'ÉTUDE
// ============================================================
if ($action === 'close') {
    try {
        // 1. Mettre à jour le fichier status.json
        $currentStatus = ['status' => 'active'];
        if (file_exists($statusFile)) {
            $currentStatus = json_decode(file_get_contents($statusFile), true) ?: $currentStatus;
        }
        
        $currentStatus['status'] = 'closed';
        $currentStatus['closedAt'] = date('c');
        $currentStatus['closedBy'] = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'admin';
        
        file_put_contents($statusFile, json_encode($currentStatus, JSON_PRETTY_PRINT));
        
        // 2. Mettre à jour MySQL
        try {
            dbExecute(
                "UPDATE studies SET status = 'closed', closed_at = NOW() WHERE folder_name = ? OR study_id = ?",
                [$studyFolder, $studyFolder]
            );
        } catch (Exception $e) {
            try {
                dbExecute(
                    "UPDATE studies SET status = 'closed' WHERE folder_name = ? OR study_id = ?",
                    [$studyFolder, $studyFolder]
                );
            } catch (Exception $e2) {}
        }
        
        // 3. Logger l'action
        try {
            dbExecute(
                "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, 'close_study', ?, ?, ?)",
                [
                    $_SESSION['user_db_id'] ?? null,
                    $_SESSION['username'] ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    "Fermeture étude: $studyFolder"
                ]
            );
        } catch (Exception $e) {}
        
        echo json_encode([
            'success' => true,
            'status' => 'closed',
            'message' => 'Étude fermée et archivée'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// ACTION: RÉOUVRIR L'ÉTUDE
// ============================================================
if ($action === 'reopen' || $action === 'open') {
    try {
        // 1. Mettre à jour le fichier status.json
        $currentStatus = ['status' => 'closed'];
        if (file_exists($statusFile)) {
            $currentStatus = json_decode(file_get_contents($statusFile), true) ?: $currentStatus;
        }
        
        $currentStatus['status'] = 'active';
        $currentStatus['closedAt'] = null;
        $currentStatus['closedBy'] = null;
        $currentStatus['reopenedAt'] = date('c');
        $currentStatus['reopenedBy'] = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'admin';
        
        file_put_contents($statusFile, json_encode($currentStatus, JSON_PRETTY_PRINT));
        
        // 2. Mettre à jour MySQL
        try {
            dbExecute(
                "UPDATE studies SET status = 'active', closed_at = NULL WHERE folder_name = ? OR study_id = ?",
                [$studyFolder, $studyFolder]
            );
        } catch (Exception $e) {
            try {
                dbExecute(
                    "UPDATE studies SET status = 'active' WHERE folder_name = ? OR study_id = ?",
                    [$studyFolder, $studyFolder]
                );
            } catch (Exception $e2) {}
        }
        
        // 3. Logger l'action
        try {
            dbExecute(
                "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, 'reopen_study', ?, ?, ?)",
                [
                    $_SESSION['user_db_id'] ?? null,
                    $_SESSION['username'] ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    "Réouverture étude: $studyFolder"
                ]
            );
        } catch (Exception $e) {}
        
        echo json_encode([
            'success' => true,
            'status' => 'active',
            'message' => 'Étude réouverte'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// ACTION: SUPPRIMER L'ÉTUDE (Super Admin uniquement)
// ============================================================
if ($action === 'delete') {
    $deletedItems = [];
    $errors = [];
    
    try {
        // 1. Chercher l'étude dans MySQL par folder_name OU study_id
        $study = null;
        try {
            $study = dbQueryOne(
                "SELECT id, study_id, folder_name, title FROM studies WHERE folder_name = ? OR study_id = ?", 
                [$studyFolder, $studyFolder]
            );
        } catch (Exception $e) {
            $errors[] = "Recherche étude: " . $e->getMessage();
        }
        
        $mysqlId = $study['id'] ?? null;
        $studyTitle = $study['title'] ?? $studyFolder;
        
        // 2. Supprimer les données MySQL liées
        if ($mysqlId) {
            // Supprimer les answers liées aux responses
            try {
                dbExecute(
                    "DELETE a FROM answers a 
                     INNER JOIN responses r ON a.response_id = r.id 
                     WHERE r.study_id = ?",
                    [$mysqlId]
                );
                $deletedItems[] = "answers";
            } catch (Exception $e) {
                $errors[] = "Delete answers: " . $e->getMessage();
            }
            
            // Supprimer les signaletiques liées aux responses
            try {
                dbExecute(
                    "DELETE s FROM signaletiques s 
                     INNER JOIN responses r ON s.response_id = r.id 
                     WHERE r.study_id = ?",
                    [$mysqlId]
                );
                $deletedItems[] = "signaletiques";
            } catch (Exception $e) {
                $errors[] = "Delete signaletiques: " . $e->getMessage();
            }
            
            // Supprimer les responses
            try {
                dbExecute("DELETE FROM responses WHERE study_id = ?", [$mysqlId]);
                $deletedItems[] = "responses";
            } catch (Exception $e) {
                $errors[] = "Delete responses: " . $e->getMessage();
            }
            
            // Supprimer les access_ids
            try {
                dbExecute("DELETE FROM access_ids WHERE study_id = ?", [$mysqlId]);
                $deletedItems[] = "access_ids";
            } catch (Exception $e) {
                $errors[] = "Delete access_ids: " . $e->getMessage();
            }
            
            // Supprimer l'étude elle-même
            try {
                dbExecute("DELETE FROM studies WHERE id = ?", [$mysqlId]);
                $deletedItems[] = "study (id=$mysqlId)";
            } catch (Exception $e) {
                $errors[] = "Delete study: " . $e->getMessage();
            }
        }
        
        // 3. Nettoyage supplémentaire : supprimer par folder_name/study_id au cas où
        try {
            dbExecute("DELETE FROM studies WHERE folder_name = ? OR study_id = ?", [$studyFolder, $studyFolder]);
            $deletedItems[] = "studies (by name)";
        } catch (Exception $e) {
            // Ignorer si déjà supprimé ou erreur
        }
        
        // 4. Supprimer le dossier et tous ses fichiers (si existe)
        if (is_dir($studyPath)) {
            deleteDirectory($studyPath);
            $deletedItems[] = "folder";
        }
        
        // 5. Logger l'action
        try {
            dbExecute(
                "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, 'delete_study', ?, ?, ?)",
                [
                    $_SESSION['user_db_id'] ?? null,
                    $_SESSION['username'] ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    "Suppression: $studyTitle ($studyFolder)"
                ]
            );
        } catch (Exception $e) {}
        
        echo json_encode([
            'success' => true,
            'message' => 'Étude supprimée définitivement',
            'deleted' => $deletedItems,
            'errors' => count($errors) > 0 ? $errors : null
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erreur: ' . $e->getMessage(),
            'deleted' => $deletedItems,
            'errors' => $errors
        ]);
    }
    exit;
}

// Action non reconnue
http_response_code(400);
echo json_encode(['error' => 'Action non reconnue: ' . $action]);

// ============================================================
// FONCTION UTILITAIRE
// ============================================================

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}