<?php
/**
 * Admin - Import des panélistes depuis Excel
 * Utilise PhpSpreadsheet si disponible, sinon parsing CSV
 */

require_once __DIR__ . '/../api/db.php';

// Vérification session admin (simplifiée pour l'exemple)
session_start();
// TODO: Ajouter vérification authentification admin

$message = '';
$imported = 0;
$errors = [];

// Durée avant expiration (30 jours)
define('EXPIRATION_DAYS', 30);

/**
 * Parse une date JJ/MM/AAAA ou composants séparés
 */
function parseDate($jour, $mois, $annee) {
    if (!$jour || !$mois || !$annee) return null;

    $jour = intval($jour);
    $mois = intval($mois);
    $annee = intval($annee);

    // Correction année sur 2 chiffres
    if ($annee < 100) {
        $annee = ($annee > 25) ? 1900 + $annee : 2000 + $annee;
    }

    if ($jour < 1 || $jour > 31 || $mois < 1 || $mois > 12) return null;

    return sprintf('%04d-%02d-%02d', $annee, $mois, $jour);
}

/**
 * Nettoie un numéro de téléphone
 */
function cleanPhone($phone) {
    if (!$phone) return null;
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 9) {
        $phone = '0' . $phone;
    }
    return $phone ?: null;
}

/**
 * Convertit Oui/Non en boolean
 */
function parseBool($value) {
    if ($value === null) return null;
    $val = strtolower(trim($value));
    return in_array($val, ['oui', 'yes', '1', 'true']) ? 1 : 0;
}

/**
 * Import depuis un fichier CSV
 */
function importFromCSV($filepath) {
    global $imported, $errors;

    $handle = fopen($filepath, 'r');
    if (!$handle) {
        throw new Exception("Impossible d'ouvrir le fichier");
    }

    // Lecture des en-têtes
    $headers = fgetcsv($handle, 0, ';');
    if (!$headers) {
        throw new Exception("Fichier vide ou format invalide");
    }

    // Normalise les en-têtes
    $headers = array_map('trim', $headers);
    $headers = array_map('strtoupper', $headers);

    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . EXPIRATION_DAYS . ' days'));
    $lineNum = 1;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $lineNum++;

        try {
            // Crée un tableau associatif
            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = isset($row[$i]) ? trim($row[$i]) : null;
            }

            // Champs obligatoires
            $panelId = $data['ID'] ?? null;
            $email = strtolower($data['EMAIL'] ?? '');

            if (!$panelId || !$email || strpos($email, '@') === false) {
                continue; // Skip
            }

            // Extraction des données
            $insertData = [
                'panel_id' => intval($panelId),
                'email' => $email,
                'region' => $data['REGION'] ?? null,
                'civilite' => $data['CIVILITE'] ?? null,
                'nom' => $data['NOM'] ?? null,
                'prenom' => $data['PRENOM'] ?? null,
                'adresse' => $data['ADRESSE_POSTAL'] ?? null,
                'code_postal' => $data['CODE_POSTAL'] ?? null,
                'departement' => $data['DEPARTEMENT'] ?? null,
                'ville' => $data['VILLE'] ?? null,
                'tel_domicile' => cleanPhone($data['TEL_DOMICILE'] ?? null),
                'tel_portable' => cleanPhone($data['TEL_PORTABLE'] ?? null),
                'tel_bureau' => cleanPhone($data['TEL_BUREAU'] ?? null),
                'date_naissance' => parseDate($data['JJ'] ?? null, $data['MM'] ?? null, $data['AA'] ?? null),
                'age' => isset($data['AGE']) ? intval($data['AGE']) : null,
                'situation_familiale' => $data['SITUATION_FAMILIAL'] ?? null,
                'situation_professionnelle' => $data['SITUATION_PROFESSIONNEL'] ?? null,
                'enfants_au_foyer' => intval($data['ENFANTS_AU_FOYER'] ?? 0),
                'enfants_plus_18ans' => intval($data['ENFANTS_PLUS_DE_18ANS'] ?? 0),
                'enfants_moins_18ans' => intval($data['ENFANTS_MOINS_DE_18ANS'] ?? 0),
                'diplome' => $data['DIPLOME_OBTENU'] ?? null,
                'profession' => $data['PROFESSION_ACTUEL'] ?? null,
                'secteur_activite' => $data['SECTEUR_ACTIVITE'] ?? null,
                'revenu_mensuel' => $data['REVENU_NET_MENSUEL_DU_FOYER'] ?? null,
                'banque_principale' => $data['BANQUE_PRINCIPAL'] ?? null,
                'autres_banques' => $data['AUTRES_BANQUES'] ?? null,
                'type_habitation' => $data['TYPE_HABITATION'] ?? null,
                'situation_habitation' => $data['SITUATION_HABITATION'] ?? null,
                'possede_voiture' => parseBool($data['VOITURE'] ?? null),
                'possede_moto' => parseBool($data['MOTO'] ?? null),
                'expires_at' => $expiresAt
            ];

            // Équipements
            $equipements = [];
            $equipCols = [
                'POSSEDE_TELEVISEUR' => 'televiseur',
                'POSSEDE_VIDEO_PROJECTEUR' => 'video_projecteur',
                'POSSEDE_UN_ORDINATEUR_PORTABLE' => 'ordinateur_portable',
                'POSSEDE_APPAREIL_PHOTO_NUMERIQUE' => 'appareil_photo',
                'POSSEDE_WIFI' => 'wifi',
                'POSSEDE_GPS' => 'gps'
            ];
            foreach ($equipCols as $col => $key) {
                if (isset($data[$col]) && parseBool($data[$col])) {
                    $equipements[$key] = true;
                }
            }
            $insertData['equipements'] = !empty($equipements) ? json_encode($equipements) : null;

            // Conjoint
            if (!empty($data['CIVILITE_CONJOINT'])) {
                $conjoint = [
                    'civilite' => $data['CIVILITE_CONJOINT'] ?? null,
                    'nom' => $data['NOM_CONJOINT'] ?? null,
                    'prenom' => $data['PRENOM_CONJOINT'] ?? null,
                    'email' => $data['EMAIL_CONJOINT'] ?? null,
                    'date_naissance' => parseDate($data['JJ_CONJOINT'] ?? null, $data['MM_CONJOINT'] ?? null, $data['AA_CONJOINT'] ?? null),
                    'diplome' => $data['DIPLOME_OBTENU_CONJOINT'] ?? null,
                    'profession' => $data['PROFESSION_ACTUEl_CONJOINT'] ?? null
                ];
                $insertData['conjoint_data'] = json_encode($conjoint);
            } else {
                $insertData['conjoint_data'] = null;
            }

            // Insert ou update
            $sql = "INSERT INTO panel_imported (
                panel_id, email, region, civilite, nom, prenom,
                adresse, code_postal, departement, ville,
                tel_domicile, tel_portable, tel_bureau,
                date_naissance, age, situation_familiale, situation_professionnelle,
                enfants_au_foyer, enfants_plus_18ans, enfants_moins_18ans,
                diplome, profession, secteur_activite, revenu_mensuel,
                banque_principale, autres_banques, conjoint_data,
                type_habitation, situation_habitation,
                possede_voiture, possede_moto, equipements, expires_at
            ) VALUES (
                :panel_id, :email, :region, :civilite, :nom, :prenom,
                :adresse, :code_postal, :departement, :ville,
                :tel_domicile, :tel_portable, :tel_bureau,
                :date_naissance, :age, :situation_familiale, :situation_professionnelle,
                :enfants_au_foyer, :enfants_plus_18ans, :enfants_moins_18ans,
                :diplome, :profession, :secteur_activite, :revenu_mensuel,
                :banque_principale, :autres_banques, :conjoint_data,
                :type_habitation, :situation_habitation,
                :possede_voiture, :possede_moto, :equipements, :expires_at
            ) ON DUPLICATE KEY UPDATE
                nom = VALUES(nom),
                prenom = VALUES(prenom),
                expires_at = VALUES(expires_at)";

            dbExecute($sql, $insertData);
            $imported++;

        } catch (Exception $e) {
            $errors[] = "Ligne $lineNum: " . $e->getMessage();
            if (count($errors) > 50) break; // Limite les erreurs affichées
        }
    }

    fclose($handle);
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['panel_file'])) {
    $file = $_FILES['panel_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Erreur lors de l\'upload du fichier.';
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            try {
                importFromCSV($file['tmp_name']);
                $message = "Import terminé: $imported enregistrements importés.";
                if (!empty($errors)) {
                    $message .= " " . count($errors) . " erreurs.";
                }
            } catch (Exception $e) {
                $message = 'Erreur: ' . $e->getMessage();
            }
        } elseif ($extension === 'xlsx') {
            $message = 'Pour les fichiers Excel, veuillez d\'abord les convertir en CSV (séparateur: point-virgule).';
        } else {
            $message = 'Format non supporté. Utilisez CSV ou XLSX.';
        }
    }
}

// Statistiques
$stats = dbQueryOne("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN claimed = 1 THEN 1 ELSE 0 END) as claimed,
    SUM(CASE WHEN claimed = 0 AND (expires_at IS NULL OR expires_at > NOW()) THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN expires_at < NOW() AND claimed = 0 THEN 1 ELSE 0 END) as expired
FROM panel_imported");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Panel - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Import des Panélistes</h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= strpos($message, 'Erreur') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                <strong>Erreurs détectées:</strong>
                <ul class="list-disc ml-5 mt-2">
                    <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($errors) > 10): ?>
                    <p class="mt-2 italic">... et <?= count($errors) - 10 ?> autres erreurs</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-3xl font-bold text-blue-600"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="text-gray-500 text-sm">Total importés</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-3xl font-bold text-green-600"><?= number_format($stats['available'] ?? 0) ?></div>
                <div class="text-gray-500 text-sm">Disponibles</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-3xl font-bold text-purple-600"><?= number_format($stats['claimed'] ?? 0) ?></div>
                <div class="text-gray-500 text-sm">Réclamés</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-3xl font-bold text-gray-400"><?= number_format($stats['expired'] ?? 0) ?></div>
                <div class="text-gray-500 text-sm">Expirés</div>
            </div>
        </div>

        <!-- Formulaire d'import -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Importer un fichier</h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">Fichier CSV (séparateur: point-virgule)</label>
                    <input type="file" name="panel_file" accept=".csv,.xlsx" required
                           class="block w-full text-gray-500 file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0 file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm text-yellow-700">
                    <strong>Note:</strong> Pour les fichiers Excel (.xlsx), exportez d'abord en CSV avec le séparateur point-virgule.
                    <br>Les données expireront automatiquement après 30 jours si non réclamées.
                </div>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Importer
                </button>
            </form>
        </div>

        <!-- Instructions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Comment ça fonctionne</h2>

            <div class="space-y-3 text-gray-600">
                <p><strong>1.</strong> Importez votre fichier panel (CSV avec colonnes: ID, EMAIL, NOM, PRENOM, etc.)</p>
                <p><strong>2.</strong> Les données sont stockées avec une date d'expiration de 30 jours</p>
                <p><strong>3.</strong> Quand un utilisateur s'inscrit sur l'app, son email est vérifié</p>
                <p><strong>4.</strong> S'il correspond, ses données sont pré-remplies et il n'a qu'à vérifier</p>
                <p><strong>5.</strong> Après 30 jours, les données non réclamées sont automatiquement supprimées</p>
            </div>
        </div>

        <!-- Bouton nettoyage manuel -->
        <div class="mt-6 text-center">
            <a href="?cleanup=1" class="text-red-600 hover:text-red-800 text-sm"
               onclick="return confirm('Supprimer tous les enregistrements expirés ?')">
                Nettoyer les enregistrements expirés
            </a>
        </div>

        <?php if (isset($_GET['cleanup'])): ?>
            <?php
            $deleted = dbExecute("DELETE FROM panel_imported WHERE claimed = 0 AND expires_at < NOW()");
            ?>
            <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-lg text-center">
                Nettoyage effectué.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
