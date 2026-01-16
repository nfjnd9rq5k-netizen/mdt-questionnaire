<?php
/**
 * API GESTION DES UTILISATEURS - VERSION MYSQL
 */

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher, mais logger

// Gestion globale des erreurs pour toujours renvoyer du JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {

session_start();
require_once 'config.php';
require_once 'db.php';
require_once 'security.php';

header('Content-Type: application/json');

/**
 * Hash un mot de passe de manière sécurisée
 * Utilise ARGON2ID si disponible, sinon bcrypt
 */
function hashPassword($password) {
    // Vérifier si ARGON2ID est disponible
    if (defined('PASSWORD_ARGON2ID')) {
        try {
            $hash = @password_hash($password, PASSWORD_ARGON2ID);
            if ($hash !== false) {
                return $hash;
            }
        } catch (Exception $e) {
            // ARGON2ID a échoué, on continue avec le fallback
        }
    }
    
    // Fallback sur bcrypt (toujours disponible)
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Enregistre un log d'action
 */
function logAction($action, $userId = null, $username = null, $details = null) {
    try {
        dbExecute(
            "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, ?, ?, ?, ?)",
            [$userId, $action, $username, $_SERVER['REMOTE_ADDR'] ?? null, $details]
        );
    } catch (Exception $e) {
        // Ignorer les erreurs de log
    }
}

/**
 * Récupère tous les utilisateurs
 */
function getUsers() {
    return dbQuery("SELECT * FROM users ORDER BY created_at DESC");
}

/**
 * Vérifie si un nom d'utilisateur existe
 */
function usernameExists($username, $excludeId = null) {
    if ($excludeId) {
        $user = dbQueryOne("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?", [$username, $excludeId]);
    } else {
        $user = dbQueryOne("SELECT id FROM users WHERE LOWER(username) = LOWER(?)", [$username]);
    }
    return $user !== null;
}

/**
 * Crée un nouvel utilisateur
 */
function createUser($username, $password, $displayName, $role, $allowedStudies = []) {
    if (usernameExists($username)) {
        return ['success' => false, 'error' => 'Ce nom d\'utilisateur existe déjà'];
    }
    
    $validRoles = ['super_admin', 'admin', 'user'];
    if (!in_array($role, $validRoles)) {
        $role = 'user';
    }
    
    $uniqueId = uniqid('user_');
    $hashedPassword = hashPassword($password);
    
    dbExecute(
        "INSERT INTO users (unique_id, username, password_hash, display_name, role, allowed_studies, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [
            $uniqueId,
            $username,
            $hashedPassword,
            $displayName ?: $username,
            $role,
            json_encode($allowedStudies)
        ]
    );
    
    logAction('user_created', null, $username, "Compte créé: $displayName ($username) - Rôle: $role");
    
    return ['success' => true, 'id' => $uniqueId];
}

/**
 * Met à jour un utilisateur
 */
function updateUser($userId, $data) {
    $user = dbQueryOne("SELECT * FROM users WHERE unique_id = ?", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Utilisateur non trouvé'];
    }
    
    $updates = [];
    $params = [];
    
    if (isset($data['display_name'])) {
        $updates[] = "display_name = ?";
        $params[] = $data['display_name'];
    }
    
    if (isset($data['role']) && $user['role'] !== 'super_admin') {
        $validRoles = ['admin', 'user'];
        if (in_array($data['role'], $validRoles)) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
    }
    
    if (isset($data['allowed_studies'])) {
        $updates[] = "allowed_studies = ?";
        $params[] = json_encode($data['allowed_studies']);
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        $updates[] = "password_hash = ?";
        $params[] = hashPassword($data['password']);
    }
    
    if (empty($updates)) {
        return ['success' => true, 'message' => 'Aucune modification'];
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE unique_id = ?";
    dbExecute($sql, $params);
    
    logAction('user_updated', $user['id'], $user['username'], "Compte modifié: {$user['display_name']}");
    
    return ['success' => true];
}

/**
 * Supprime un utilisateur
 */
function deleteUser($userId, $currentUserId) {
    if ($userId === $currentUserId) {
        return ['success' => false, 'error' => 'Impossible de supprimer votre propre compte'];
    }
    
    $user = dbQueryOne("SELECT * FROM users WHERE unique_id = ?", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Utilisateur non trouvé'];
    }
    
    if ($user['role'] === 'super_admin') {
        // Vérifier qu'il reste au moins un super admin
        $count = dbQueryOne("SELECT COUNT(*) as cnt FROM users WHERE role = 'super_admin'");
        if ($count['cnt'] <= 1) {
            return ['success' => false, 'error' => 'Impossible de supprimer le dernier Super Admin'];
        }
    }
    
    dbExecute("DELETE FROM users WHERE unique_id = ?", [$userId]);
    
    logAction('user_deleted', null, $user['username'], "Compte supprimé: {$user['display_name']} ({$user['username']})");
    
    return ['success' => true];
}

// Vérification de l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$currentRole = $_SESSION['user_role'] ?? 'user';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Actions qui modifient des données = vérification CSRF obligatoire
$actionsRequiringCsrf = ['create', 'update', 'delete'];

if (in_array($action, $actionsRequiringCsrf)) {
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF invalide. Veuillez rafraîchir la page.'
        ]);
        exit;
    }
}

switch ($action) {
    case 'list':
        $users = getUsers();
        $safeUsers = array_map(function($u) {
            $allowedStudies = [];
            if (!empty($u['allowed_studies'])) {
                $allowedStudies = json_decode($u['allowed_studies'], true) ?: [];
            }
            return [
                'id' => $u['unique_id'],
                'username' => $u['username'],
                'display_name' => $u['display_name'],
                'role' => $u['role'],
                'allowed_studies' => $allowedStudies,
                'created_at' => $u['created_at'],
                'last_login' => $u['last_login']
            ];
        }, $users);
        echo json_encode(['success' => true, 'users' => array_values($safeUsers)]);
        break;
        
    case 'create':
        // super_admin peut créer tous types de comptes
        // admin peut créer uniquement des comptes user
        if ($currentRole === 'user') {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            break;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $displayName = trim($_POST['display_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $allowedStudies = json_decode($_POST['allowed_studies'] ?? '[]', true) ?: [];
        
        // Les admins ne peuvent créer que des comptes user
        if ($currentRole === 'admin' && $role !== 'user') {
            echo json_encode(['success' => false, 'error' => 'Vous ne pouvez créer que des comptes utilisateur']);
            break;
        }
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Nom d\'utilisateur et mot de passe requis']);
            break;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Le mot de passe doit faire au moins 6 caractères']);
            break;
        }
        
        echo json_encode(createUser($username, $password, $displayName, $role, $allowedStudies));
        break;
        
    case 'update':
        if ($currentRole !== 'super_admin') {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            break;
        }
        
        $userId = $_POST['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
            break;
        }
        
        $data = [];
        if (isset($_POST['display_name'])) $data['display_name'] = trim($_POST['display_name']);
        if (isset($_POST['role'])) $data['role'] = $_POST['role'];
        if (isset($_POST['allowed_studies'])) {
            $data['allowed_studies'] = json_decode($_POST['allowed_studies'], true) ?: [];
        }
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                echo json_encode(['success' => false, 'error' => 'Le mot de passe doit faire au moins 6 caractères']);
                break;
            }
            $data['password'] = $_POST['password'];
        }
        
        echo json_encode(updateUser($userId, $data));
        break;
        
    case 'delete':
        if ($currentRole !== 'super_admin') {
            echo json_encode(['success' => false, 'error' => 'Permission refusée']);
            break;
        }
        
        $userId = $_POST['user_id'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
            break;
        }
        
        $currentUserId = $_SESSION['user_id'] ?? '';
        echo json_encode(deleteUser($userId, $currentUserId));
        break;
        
    case 'get_studies':
        $studiesDir = __DIR__ . '/../studies/';
        $studies = [];
        
        if (is_dir($studiesDir)) {
            $folders = scandir($studiesDir);
            foreach ($folders as $folder) {
                if ($folder[0] === '.' || $folder[0] === '_' || $folder === 'closed.html') continue;
                $statusFile = $studiesDir . $folder . '/status.json';
                if (file_exists($statusFile)) {
                    $status = json_decode(file_get_contents($statusFile), true);
                    $studies[] = [
                        'id' => $folder,
                        'name' => $status['studyName'] ?? $folder,
                        'status' => $status['status'] ?? 'active'
                    ];
                }
            }
        }
        
        echo json_encode(['success' => true, 'studies' => $studies]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}

} catch (Exception $e) {
    // En cas d'erreur, renvoyer un JSON propre avec le message d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}