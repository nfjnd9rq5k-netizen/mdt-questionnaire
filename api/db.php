<?php
/**
 * ============================================================
 * CONNEXION À LA BASE DE DONNÉES MYSQL
 * ============================================================
 * 
 * Ce fichier gère la connexion à MySQL.
 * Les identifiants sont stockés dans secure_data/db_config.php
 * (dossier protégé, inaccessible depuis le web)
 */

// Charger les identifiants depuis le fichier protégé
require_once __DIR__ . '/secure_data/db_config.php';

/**
 * Obtenir une connexion à la base de données
 */
function getDatabase(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Erreur de connexion MySQL: " . $e->getMessage());
            die(json_encode([
                'error' => 'Erreur de connexion à la base de données'
            ]));
        }
    }
    
    return $pdo;
}

/**
 * Exécuter une requête SELECT (plusieurs lignes)
 */
function dbQuery(string $sql, array $params = []): array {
    $stmt = getDatabase()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécuter une requête SELECT (une seule ligne)
 */
function dbQueryOne(string $sql, array $params = []): ?array {
    $stmt = getDatabase()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Exécuter INSERT/UPDATE/DELETE
 */
function dbExecute(string $sql, array $params = []): int {
    $stmt = getDatabase()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Récupérer le dernier ID inséré
 */
function dbLastId(): string {
    return getDatabase()->lastInsertId();
}

/**
 * Démarrer une transaction
 */
function dbBeginTransaction(): void {
    getDatabase()->beginTransaction();
}

/**
 * Valider une transaction
 */
function dbCommit(): void {
    getDatabase()->commit();
}

/**
 * Annuler une transaction
 */
function dbRollback(): void {
    getDatabase()->rollBack();
}

// ============================================================
// Test de connexion (accès direct à ce fichier)
// ============================================================

if (basename($_SERVER['SCRIPT_FILENAME']) === 'db.php' && php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    
    try {
        $pdo = getDatabase();
        $version = $pdo->query("SELECT VERSION() as version")->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion à MySQL réussie !',
            'mysql_version' => $version['version'],
            'database' => DB_NAME
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Échec de connexion',
            'details' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
    exit;
}
