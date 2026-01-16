<?php
/**
 * API Mobile - Profil paneliste
 * GET  /api/mobile/profile.php - Récupérer le profil
 * PUT  /api/mobile/profile.php - Mettre à jour le profil
 *
 * Header: Authorization: Bearer <access_token>
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

setCorsHeaders();

$auth = requireAuth();
$panelistId = $auth['sub'];

// GET - Récupérer le profil
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $panelist = dbQueryOne(
        "SELECT unique_id, email, phone, gender, birth_date, region, city, postal_code,
                csp, household_size, has_children, children_ages, equipment, brands_owned,
                interests, push_enabled, status, email_verified, points_balance,
                points_lifetime, studies_completed, created_at, last_active
         FROM panelists WHERE id = ?",
        [$panelistId]
    );

    if (!$panelist) {
        jsonError('Panelist not found', 404, 'NOT_FOUND');
    }

    // Décoder les champs JSON
    $jsonFields = ['children_ages', 'equipment', 'brands_owned', 'interests'];
    foreach ($jsonFields as $field) {
        if ($panelist[$field]) {
            $panelist[$field] = json_decode($panelist[$field], true);
        } else {
            $panelist[$field] = [];
        }
    }

    // Convertir les booléens
    $panelist['has_children'] = $panelist['has_children'] !== null ? (bool) $panelist['has_children'] : null;
    $panelist['push_enabled'] = (bool) $panelist['push_enabled'];
    $panelist['email_verified'] = (bool) $panelist['email_verified'];
    $panelist['points_balance'] = (int) $panelist['points_balance'];
    $panelist['points_lifetime'] = (int) $panelist['points_lifetime'];
    $panelist['studies_completed'] = (int) $panelist['studies_completed'];
    $panelist['household_size'] = $panelist['household_size'] !== null ? (int) $panelist['household_size'] : null;

    // Renommer unique_id en id pour l'API
    $panelist['id'] = $panelist['unique_id'];
    unset($panelist['unique_id']);

    jsonSuccess(['profile' => $panelist]);
}

// PUT - Mettre à jour le profil
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = getJsonBody();

    // Champs modifiables
    $allowedFields = [
        'phone', 'gender', 'birth_date', 'region', 'city', 'postal_code',
        'csp', 'household_size', 'has_children', 'children_ages',
        'equipment', 'brands_owned', 'interests', 'push_enabled'
    ];

    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $value = $data[$field];

            // Valider et convertir selon le type
            switch ($field) {
                case 'gender':
                    if ($value !== null && !in_array($value, ['M', 'F', 'autre'])) {
                        jsonError("Invalid value for gender", 400, 'INVALID_FIELD');
                    }
                    break;

                case 'birth_date':
                    if ($value !== null) {
                        $date = DateTime::createFromFormat('Y-m-d', $value);
                        if (!$date) {
                            jsonError("Invalid date format for birth_date (use YYYY-MM-DD)", 400, 'INVALID_FIELD');
                        }
                    }
                    break;

                case 'household_size':
                    if ($value !== null) {
                        $value = (int) $value;
                        if ($value < 1 || $value > 20) {
                            jsonError("Invalid household_size (1-20)", 400, 'INVALID_FIELD');
                        }
                    }
                    break;

                case 'has_children':
                case 'push_enabled':
                    $value = $value !== null ? ($value ? 1 : 0) : null;
                    break;

                case 'children_ages':
                case 'equipment':
                case 'brands_owned':
                case 'interests':
                    // Doit être un array
                    if ($value !== null && !is_array($value)) {
                        jsonError("$field must be an array", 400, 'INVALID_FIELD');
                    }
                    $value = $value !== null ? json_encode($value) : null;
                    break;
            }

            $updates[] = "`$field` = ?";
            $params[] = $value;
        }
    }

    if (empty($updates)) {
        jsonError('No valid fields to update', 400, 'NO_UPDATES');
    }

    $params[] = $panelistId;
    $sql = "UPDATE panelists SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";

    try {
        dbExecute($sql, $params);

        // Mettre à jour last_active
        dbExecute("UPDATE panelists SET last_active = NOW() WHERE id = ?", [$panelistId]);

        jsonSuccess([], 'Profile updated successfully');

    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        jsonError('Update failed', 500, 'SERVER_ERROR');
    }
}

jsonError('Method not allowed', 405);
