<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($currentPassword, ADMIN_PASSWORD_HASH)) {
        $error = "❌ Le mot de passe actuel est incorrect.";
    }
    elseif ($newPassword !== $confirmPassword) {
        $error = "❌ Les nouveaux mots de passe ne correspondent pas.";
    }
    elseif (strlen($newPassword) < 8) {
        $error = "❌ Le nouveau mot de passe doit faire au moins 8 caractères.";
    }
    else {
        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        $configFile = __DIR__ . '/../api/config.php';
        $configContent = file_get_contents($configFile);
        
        $pattern = "/define\('ADMIN_PASSWORD_HASH',\s*'[^']*'\);/";
        $replacement = "define('ADMIN_PASSWORD_HASH', '$newHash');";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);
        
        if (file_put_contents($configFile, $newConfigContent)) {
            $success = "✅ Mot de passe modifié avec succès ! Vous allez être déconnecté...";
            
            $logFile = DATA_DIR . 'admin_access.log';
            $logEntry = date('Y-m-d H:i:s') . " - Mot de passe modifié\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            header("Refresh: 3; url=index.php");
            session_unset();
            session_destroy();
        } else {
            $error = "❌ Erreur lors de la sauvegarde. Vérifiez les permissions du fichier config.php";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 24px;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: #1e40af;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .card-icon {
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
        
        .card-header h1 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .card-header p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 24px;
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
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .password-requirements {
            margin-top: 8px;
            font-size: 0.8rem;
            color: #94a3b8;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .alert {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .divider {
            border-top: 2px solid #f1f5f9;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            ← Retour au tableau de bord
        </a>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🔑</div>
                <h1>Changer le mot de passe</h1>
                <p>Modifiez votre mot de passe administrateur</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Mot de passe actuel</label>
                    <input type="password" name="current_password" class="form-input" required 
                           autocomplete="current-password">
                </div>
                
                <div class="divider"></div>
                
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-input" required 
                           autocomplete="new-password" minlength="8">
                    <p class="password-requirements">Minimum 8 caractères</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" class="form-input" required 
                           autocomplete="new-password" minlength="8">
                </div>
                
                <button type="submit" class="btn">
                    🔐 Changer le mot de passe
                </button>
            </form>
        </div>
    </div>
</body>
</html>
