<?php
/**
 * PAGE D'INSTALLATION - VERSION MYSQL
 * Crée le premier compte Super Admin
 */
session_start();
require_once '../api/config.php';
require_once '../api/db.php';

$error = '';
$success = false;

/**
 * Vérifie si un super admin existe
 */
function hasSuperAdmin() {
    try {
        $user = dbQueryOne("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
        return $user !== null;
    } catch (Exception $e) {
        // Table n'existe peut-être pas encore
        return false;
    }
}

/**
 * Enregistre un log
 */
function logAction($action, $details = null) {
    try {
        dbExecute(
            "INSERT INTO admin_logs (action, ip_address, user_agent, details) VALUES (?, ?, ?, ?)",
            [$action, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $details]
        );
    } catch (Exception $e) {
        // Ignorer si la table n'existe pas
    }
}

// Si un super admin existe déjà, rediriger
if (hasSuperAdmin()) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim($_POST['display_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validations
    if (empty($displayName) || empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (strlen($username) < 3) {
        $error = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    } elseif (strlen($password) < 12) {
        $error = "Le mot de passe doit contenir au moins 12 caractères.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une majuscule.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une minuscule.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } else {
        try {
            // Fonction de hash intelligente
            $hashedPassword = null;
            if (defined('PASSWORD_ARGON2ID')) {
                $hashedPassword = @password_hash($password, PASSWORD_ARGON2ID);
            }
            if (!$hashedPassword) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            }
            
            // Créer le super admin en base
            dbExecute(
                "INSERT INTO users (unique_id, username, password_hash, display_name, role, allowed_studies, created_at) 
                 VALUES (?, ?, ?, ?, 'super_admin', ?, NOW())",
                [
                    uniqid('user_'),
                    $username,
                    $hashedPassword,
                    $displayName,
                    json_encode(['*'])
                ]
            );
            
            // Logger l'installation
            logAction('install', "Compte Super Admin créé: $username depuis " . $_SERVER['REMOTE_ADDR']);
            
            $success = true;
            
        } catch (Exception $e) {
            $error = "Erreur lors de la création du compte: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Créer le compte administrateur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-container { background: white; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); padding: 40px; width: 100%; max-width: 450px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px; }
        .logo h1 { font-size: 1.5rem; color: #1e293b; }
        .logo p { color: #64748b; font-size: 0.9rem; margin-top: 4px; }
        .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 16px; margin-bottom: 24px; }
        .info-box h3 { color: #166534; font-size: 0.95rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .info-box p { color: #15803d; font-size: 0.85rem; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        .password-requirements { margin-top: 8px; font-size: 0.8rem; color: #64748b; }
        .password-requirements ul { margin-top: 4px; padding-left: 20px; }
        .password-requirements li { margin: 2px 0; }
        .btn { width: 100%; padding: 14px 24px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; border-radius: 10px; color: white; font-family: inherit; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .success-box { text-align: center; padding: 30px 20px; }
        .success-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; }
        .success-box h2 { color: #166534; margin-bottom: 12px; }
        .success-box p { color: #64748b; margin-bottom: 24px; }
        .btn-login { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border-radius: 10px; color: white; text-decoration: none; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body>
    <div class="install-container">
        <?php if ($success): ?>
            <div class="success-box">
                <div class="success-icon">✓</div>
                <h2>Installation terminée !</h2>
                <p>Votre compte administrateur a été créé avec succès.<br>Vous pouvez maintenant vous connecter.</p>
                <a href="index.php" class="btn-login">Se connecter</a>
            </div>
        <?php else: ?>
            <div class="logo">
                <div class="logo-icon">🚀</div>
                <h1>Installation</h1>
                <p>Maison du Test - Configuration initiale</p>
            </div>
            
            <div class="info-box">
                <h3>🔐 Première utilisation</h3>
                <p>Bienvenue ! Créez votre compte Super Administrateur pour commencer à utiliser la plateforme.<br><strong>Les données seront stockées en MySQL.</strong></p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Votre nom complet</label>
                    <input type="text" name="display_name" class="form-input" required placeholder="Ex: Jean Dupont" value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-input" required placeholder="Ex: jean.dupont" minlength="3" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-input" required placeholder="Minimum 12 caractères" minlength="12">
                    <div class="password-requirements">
                        <strong>Le mot de passe doit contenir :</strong>
                        <ul>
                            <li>Au moins 12 caractères</li>
                            <li>Au moins une majuscule (A-Z)</li>
                            <li>Au moins une minuscule (a-z)</li>
                            <li>Au moins un chiffre (0-9)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-input" required placeholder="Retapez votre mot de passe" minlength="12">
                </div>
                
                <button type="submit" class="btn">Créer le compte administrateur</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
