<?php
session_start();
require_once '../api/config.php';

$error = '';
$lockout = false;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$lockFile = DATA_DIR . 'login_attempts.json';
$attempts = [];
if (file_exists($lockFile)) {
    $attempts = json_decode(file_get_contents($lockFile), true) ?: [];
}

$ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] . 'salt_login_2026');
$currentTime = time();

if (isset($attempts[$ipHash])) {
    $attempts[$ipHash] = array_filter($attempts[$ipHash], function($time) use ($currentTime) {
        return ($currentTime - $time) < LOCKOUT_TIME;
    });
    $attempts[$ipHash] = array_values($attempts[$ipHash]);
}

if (isset($attempts[$ipHash]) && count($attempts[$ipHash]) >= MAX_LOGIN_ATTEMPTS) {
    $lockout = true;
    $oldestAttempt = min($attempts[$ipHash]);
    $remainingTime = LOCKOUT_TIME - ($currentTime - $oldestAttempt);
    $error = "🔒 Trop de tentatives. Réessayez dans " . ceil($remainingTime / 60) . " minute(s).";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockout) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $validUsername = hash_equals(ADMIN_USERNAME, $username);
    $validPassword = password_verify($password, ADMIN_PASSWORD_HASH);
    
    if ($validUsername && $validPassword) {
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_hash'] = $ipHash;
        
        unset($attempts[$ipHash]);
        file_put_contents($lockFile, json_encode($attempts));
        
        $logFile = DATA_DIR . 'admin_access.log';
        $logEntry = date('Y-m-d H:i:s') . " - Connexion réussie\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        header('Location: dashboard.php');
        exit;
    } else {
        if (!isset($attempts[$ipHash])) {
            $attempts[$ipHash] = [];
        }
        $attempts[$ipHash][] = $currentTime;
        file_put_contents($lockFile, json_encode($attempts));
        
        $remaining = MAX_LOGIN_ATTEMPTS - count($attempts[$ipHash]);
        
        if ($remaining > 0) {
            $error = "❌ Identifiants incorrects. $remaining tentative(s) restante(s).";
        } else {
            $error = "🔒 Compte verrouillé pendant 15 minutes.";
            $lockout = true;
        }
        
        $logFile = DATA_DIR . 'admin_access.log';
        $logEntry = date('Y-m-d H:i:s') . " - Tentative échouée (IP hashée: " . substr($ipHash, 0, 8) . "...)\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

$hashNotConfigured = (ADMIN_PASSWORD_HASH === '$argon2id$v=19$m=65536,t=4,p=1$REMPLACE_PAR_TON_HASH');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Connexion sécurisée</title>
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
        
        .logo h1 {
            font-size: 1.5rem;
            color: #1e293b;
        }
        
        .logo p {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        
        .security-note {
            margin-top: 24px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #64748b;
            text-align: center;
        }
        
        .security-features {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        .security-feature {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <h1>Administration</h1>
            <p>Tableau de bord sécurisé</p>
        </div>
        
        <?php if ($hashNotConfigured): ?>
            <div class="warning">
                ⚠️ <strong>Configuration requise !</strong><br>
                Va sur <a href="../generate-password.php">/generate-password.php</a> pour configurer ton mot de passe sécurisé.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Identifiant</label>
                <input type="text" name="username" class="form-input" required 
                       <?= $lockout ? 'disabled' : '' ?> autocomplete="username">
            </div>
            
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-input" required 
                       <?= $lockout ? 'disabled' : '' ?> autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn" <?= $lockout ? 'disabled' : '' ?>>
                Se connecter
            </button>
        </form>
        
        <div class="security-note">
            🔒 Connexion sécurisée
            <div class="security-features">
                <span class="security-feature">✓ Chiffrement AES-256</span>
                <span class="security-feature">✓ Hash Argon2</span>
                <span class="security-feature">✓ Anti-bruteforce</span>
            </div>
        </div>
    </div>
</body>
</html>
