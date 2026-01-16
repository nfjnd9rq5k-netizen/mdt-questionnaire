<?php
/**
 * PAGE DE CONNEXION ADMIN - VERSION MYSQL
 */
// Configure session cookie to be accessible from all paths (fix for cross-directory AJAX)
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
session_start();
require_once '../api/config.php';
require_once '../api/db.php';

$error = '';
$lockout = false;

// Déjà connecté ? Rediriger vers le dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

/**
 * Vérifie si un super admin existe dans la base
 */
function hasSuperAdmin() {
    $user = dbQueryOne("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
    return $user !== null;
}

/**
 * Récupère un utilisateur par son nom d'utilisateur
 */
function getUserByUsername($username) {
    return dbQueryOne(
        "SELECT * FROM users WHERE LOWER(username) = LOWER(?)",
        [$username]
    );
}

/**
 * Met à jour la date de dernière connexion
 */
function updateLastLogin($userId) {
    dbExecute(
        "UPDATE users SET last_login = NOW() WHERE id = ?",
        [$userId]
    );
}

/**
 * Enregistre un log de connexion
 */
function logAction($action, $userId = null, $username = null, $details = null) {
    dbExecute(
        "INSERT INTO admin_logs (user_id, action, username, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)",
        [
            $userId,
            $action,
            $username,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $details
        ]
    );
}

/**
 * Vérifie si l'IP est bloquée (anti-bruteforce)
 */
function checkBruteforce($ipHash) {
    // Nettoyer les anciennes tentatives (plus de 15 min)
    dbExecute(
        "DELETE FROM login_attempts WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [LOCKOUT_TIME]
    );
    
    // Vérifier les tentatives actuelles
    $attempt = dbQueryOne(
        "SELECT attempts, blocked_until FROM login_attempts WHERE ip_hash = ?",
        [$ipHash]
    );
    
    if ($attempt) {
        // Vérifier si encore bloqué
        if ($attempt['blocked_until'] && strtotime($attempt['blocked_until']) > time()) {
            $remaining = strtotime($attempt['blocked_until']) - time();
            return ['blocked' => true, 'remaining' => ceil($remaining / 60)];
        }
        
        // Vérifier le nombre de tentatives
        if ($attempt['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            return ['blocked' => true, 'remaining' => ceil(LOCKOUT_TIME / 60)];
        }
        
        return ['blocked' => false, 'attempts' => $attempt['attempts']];
    }
    
    return ['blocked' => false, 'attempts' => 0];
}

/**
 * Enregistre une tentative de connexion échouée
 */
function recordFailedAttempt($ipHash, $username) {
    $existing = dbQueryOne("SELECT id, attempts FROM login_attempts WHERE ip_hash = ?", [$ipHash]);
    
    if ($existing) {
        $newAttempts = $existing['attempts'] + 1;
        $blockedUntil = $newAttempts >= MAX_LOGIN_ATTEMPTS 
            ? date('Y-m-d H:i:s', time() + LOCKOUT_TIME) 
            : null;
        
        dbExecute(
            "UPDATE login_attempts SET attempts = ?, username = ?, last_attempt = NOW(), blocked_until = ? WHERE id = ?",
            [$newAttempts, $username, $blockedUntil, $existing['id']]
        );
        
        return MAX_LOGIN_ATTEMPTS - $newAttempts;
    } else {
        dbExecute(
            "INSERT INTO login_attempts (ip_hash, username, attempts) VALUES (?, ?, 1)",
            [$ipHash, $username]
        );
        return MAX_LOGIN_ATTEMPTS - 1;
    }
}

/**
 * Efface les tentatives après une connexion réussie
 */
function clearAttempts($ipHash) {
    dbExecute("DELETE FROM login_attempts WHERE ip_hash = ?", [$ipHash]);
}

// Vérifier si l'installation est nécessaire
if (!hasSuperAdmin()) {
    header('Location: install.php');
    exit;
}

// Vérifier le bruteforce
$ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] . 'salt_login_2026');
$bruteCheck = checkBruteforce($ipHash);

if ($bruteCheck['blocked']) {
    $lockout = true;
    $error = "🔒 Trop de tentatives. Réessayez dans " . $bruteCheck['remaining'] . " minute(s).";
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockout) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $user = getUserByUsername($username);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Connexion réussie
        session_regenerate_id(true);
        
        // Décoder les études autorisées
        $allowedStudies = [];
        if (!empty($user['allowed_studies'])) {
            $allowedStudies = json_decode($user['allowed_studies'], true) ?: [];
        }
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $user['unique_id'];
        $_SESSION['user_db_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['allowed_studies'] = $allowedStudies;
        $_SESSION['last_activity'] = time();
        
        // Effacer les tentatives
        clearAttempts($ipHash);
        
        // Mettre à jour la dernière connexion
        updateLastLogin($user['id']);
        
        // Logger la connexion
        logAction('login_success', $user['id'], $user['username'], "Connexion réussie: {$user['display_name']}");
        
        header('Location: dashboard.php');
        exit;
        
    } else {
        // Connexion échouée
        $remaining = recordFailedAttempt($ipHash, $username);
        
        // Logger l'échec
        logAction('login_failed', null, $username, "Tentative échouée pour: $username");
        
        if ($remaining > 0) {
            $error = "❌ Identifiants incorrects. $remaining tentative(s) restante(s).";
        } else {
            $error = "🔒 Compte verrouillé pendant 15 minutes.";
            $lockout = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
        
        .logo h1 { font-size: 1.5rem; color: #1e293b; }
        .logo p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn { width: 100%; padding: 14px 24px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border: none; border-radius: 10px; color: white; font-family: inherit; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .security-note { margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; font-size: 0.8rem; color: #64748b; text-align: center; }
        .security-features { display: flex; justify-content: center; gap: 16px; margin-top: 12px; flex-wrap: wrap; }
        .security-feature { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #3b82f6; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <h1>Administration</h1>
            <p>Maison du Test</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-input" required <?= $lockout ? 'disabled' : '' ?> autocomplete="username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-input" required <?= $lockout ? 'disabled' : '' ?> autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn" <?= $lockout ? 'disabled' : '' ?>>Se connecter</button>
        </form>
        
        <div class="security-note">
            🔒 Connexion sécurisée
            <div class="security-features">
                <span class="security-feature">✓ MySQL</span>
                <span class="security-feature">✓ Hash Argon2</span>
                <span class="security-feature">✓ Anti-bruteforce</span>
            </div>
        </div>
    </div>
</body>
</html>
