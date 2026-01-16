<?php
/**
 * API GESTION DES IPS - VERSION MYSQL
 */
require_once 'config.php';
require_once 'db.php';
require_once 'security.php';

// Démarrer la session sécurisée (cookie accessible depuis tous les chemins)
secureSessionStart();

header('Content-Type: application/json');

/**
 * Log une action
 */
function logAction($action, $details = null) {
    try {
        dbExecute(
            "INSERT INTO admin_logs (action, ip_address, details) VALUES (?, ?, ?)",
            [$action, $_SERVER['REMOTE_ADDR'] ?? null, $details]
        );
    } catch (Exception $e) {}
}

/**
 * Récupère toutes les IPs autorisées
 */
function getAllowedIps() {
    $ips = dbQuery("SELECT * FROM allowed_ips ORDER BY created_at DESC");
    return array_map(function($ip) {
        return [
            'ip' => $ip['ip_address'],
            'name' => $ip['label'] ?? 'Utilisateur',
            'approved_at' => $ip['created_at'],
            'role' => 'user'
        ];
    }, $ips);
}

/**
 * Récupère les IPs en attente
 */
function getPendingIps() {
    $ips = dbQuery("SELECT * FROM pending_ips ORDER BY created_at DESC");
    return array_map(function($ip) {
        return [
            'ip' => $ip['ip_address'],
            'requested_by' => $ip['requested_by'],
            'first_attempt' => $ip['created_at'],
            'last_attempt' => $ip['created_at'],
            'attempts' => 1
        ];
    }, $ips);
}

/**
 * Vérifie si une IP est autorisée
 */
function isIpAllowed($ip) {
    $result = dbQueryOne("SELECT id FROM allowed_ips WHERE ip_address = ?", [$ip]);
    return $result !== null;
}

/**
 * Approuve une IP
 */
function approveIp($ip, $name = '') {
    if (!isIpAllowed($ip)) {
        dbExecute(
            "INSERT INTO allowed_ips (ip_address, label) VALUES (?, ?)",
            [$ip, $name ?: 'Utilisateur']
        );
    }
    
    // Supprimer des pending
    dbExecute("DELETE FROM pending_ips WHERE ip_address = ?", [$ip]);
    
    logAction('ip_approved', "IP approuvée: $ip (Nom: $name)");
    
    return true;
}

/**
 * Rejette une IP
 */
function rejectIp($ip) {
    dbExecute("DELETE FROM pending_ips WHERE ip_address = ?", [$ip]);
    logAction('ip_rejected', "IP rejetée: $ip");
    return true;
}

/**
 * Révoque une IP
 */
function revokeIp($ip) {
    dbExecute("DELETE FROM allowed_ips WHERE ip_address = ?", [$ip]);
    logAction('ip_revoked', "IP révoquée: $ip");
    return true;
}

/**
 * Renomme une IP
 */
function updateIpName($ip, $newName) {
    dbExecute("UPDATE allowed_ips SET label = ? WHERE ip_address = ?", [$newName, $ip]);
    return true;
}

// Vérification de l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        echo json_encode([
            'success' => true,
            'allowed' => getAllowedIps(),
            'pending' => getPendingIps()
        ]);
        break;
        
    case 'approve':
        $ip = $_POST['ip'] ?? '';
        $name = $_POST['name'] ?? '';
        if (empty($ip)) {
            echo json_encode(['success' => false, 'error' => 'IP manquante']);
            break;
        }
        echo json_encode(['success' => approveIp($ip, $name)]);
        break;
        
    case 'reject':
        $ip = $_POST['ip'] ?? '';
        if (empty($ip)) {
            echo json_encode(['success' => false, 'error' => 'IP manquante']);
            break;
        }
        echo json_encode(['success' => rejectIp($ip)]);
        break;
        
    case 'revoke':
        $ip = $_POST['ip'] ?? '';
        if (empty($ip)) {
            echo json_encode(['success' => false, 'error' => 'IP manquante']);
            break;
        }
        $currentIp = $_SERVER['REMOTE_ADDR'];
        if ($ip === $currentIp) {
            echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas révoquer votre propre IP']);
            break;
        }
        echo json_encode(['success' => revokeIp($ip)]);
        break;
        
    case 'rename':
        $ip = $_POST['ip'] ?? '';
        $name = $_POST['name'] ?? '';
        if (empty($ip) || empty($name)) {
            echo json_encode(['success' => false, 'error' => 'IP ou nom manquant']);
            break;
        }
        echo json_encode(['success' => updateIpName($ip, $name)]);
        break;
        
    case 'current':
        $currentIp = $_SERVER['REMOTE_ADDR'];
        $allowed = isIpAllowed($currentIp);
        echo json_encode([
            'success' => true,
            'ip' => $currentIp,
            'allowed' => $allowed
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}
