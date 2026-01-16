<?php
/**
 * API Mobile - Documentation
 * GET /api/mobile/
 */

header('Content-Type: application/json; charset=utf-8');

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
           "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);

echo json_encode([
    'name' => 'La Maison du Test - API Mobile',
    'version' => '1.0.0',
    'description' => 'API pour l\'application mobile de gestion des panelistes',
    'base_url' => $baseUrl,
    'authentication' => [
        'type' => 'Bearer Token (JWT)',
        'header' => 'Authorization: Bearer <access_token>',
        'note' => 'Access tokens expire after 1 hour. Use refresh token to get new tokens.'
    ],
    'endpoints' => [
        'auth' => [
            [
                'method' => 'POST',
                'path' => '/auth/register.php',
                'description' => 'Create new panelist account',
                'body' => ['email' => 'required', 'password' => 'required (min 8 chars)', 'phone' => 'optional'],
                'auth' => false
            ],
            [
                'method' => 'POST',
                'path' => '/auth/login.php',
                'description' => 'Login and get tokens',
                'body' => ['email' => 'required', 'password' => 'required', 'device_info' => 'optional'],
                'auth' => false
            ],
            [
                'method' => 'POST',
                'path' => '/auth/refresh.php',
                'description' => 'Refresh access token',
                'body' => ['refresh_token' => 'required'],
                'auth' => false
            ],
            [
                'method' => 'POST',
                'path' => '/auth/logout.php',
                'description' => 'Logout (invalidate session)',
                'body' => ['refresh_token' => 'optional (logout from specific device)'],
                'auth' => true
            ]
        ],
        'profile' => [
            [
                'method' => 'GET',
                'path' => '/profile.php',
                'description' => 'Get panelist profile',
                'auth' => true
            ],
            [
                'method' => 'PUT',
                'path' => '/profile.php',
                'description' => 'Update panelist profile',
                'body' => [
                    'phone' => 'string',
                    'gender' => 'M|F|autre',
                    'birth_date' => 'YYYY-MM-DD',
                    'region' => 'string',
                    'city' => 'string',
                    'postal_code' => 'string',
                    'csp' => 'string',
                    'household_size' => 'int (1-20)',
                    'has_children' => 'boolean',
                    'children_ages' => 'array of ints',
                    'equipment' => 'array of strings',
                    'brands_owned' => 'array of strings',
                    'interests' => 'array of strings',
                    'push_enabled' => 'boolean'
                ],
                'auth' => true
            ]
        ],
        'studies' => [
            [
                'method' => 'GET',
                'path' => '/studies.php',
                'description' => 'List eligible studies for panelist',
                'auth' => true
            ],
            [
                'method' => 'POST',
                'path' => '/study-start.php',
                'description' => 'Start a study (get WebView URL)',
                'body' => ['solicitation_id' => 'required (int)'],
                'auth' => true
            ],
            [
                'method' => 'POST',
                'path' => '/study-complete.php',
                'description' => 'Mark study as completed',
                'body' => [
                    'solicitation_id' => 'required (int)',
                    'status' => 'completed|screened_out',
                    'response_unique_id' => 'optional'
                ],
                'auth' => true
            ]
        ],
        'points' => [
            [
                'method' => 'GET',
                'path' => '/points-history.php',
                'description' => 'Get points history',
                'query' => ['limit' => 'int (default 20, max 100)', 'offset' => 'int (default 0)'],
                'auth' => true
            ]
        ],
        'notifications' => [
            [
                'method' => 'GET',
                'path' => '/notifications.php',
                'description' => 'Get notifications',
                'query' => ['limit' => 'int (default 20)', 'unread_only' => 'true|false'],
                'auth' => true
            ],
            [
                'method' => 'POST',
                'path' => '/notifications.php',
                'description' => 'Mark notifications as read',
                'body' => [
                    'notification_id' => 'int (specific notification)',
                    'mark_all_read' => 'boolean (all notifications)'
                ],
                'auth' => true
            ]
        ],
        'push' => [
            [
                'method' => 'POST',
                'path' => '/push-token.php',
                'description' => 'Register Firebase push token',
                'body' => ['token' => 'required'],
                'auth' => true
            ]
        ]
    ],
    'response_format' => [
        'success' => [
            'success' => true,
            'message' => 'OK',
            '...' => 'additional data'
        ],
        'error' => [
            'success' => false,
            'error' => 'Error message',
            'code' => 'ERROR_CODE'
        ]
    ],
    'error_codes' => [
        'AUTH_REQUIRED' => 'Authentication token required',
        'INVALID_TOKEN' => 'Token is invalid or expired',
        'INVALID_CREDENTIALS' => 'Wrong email or password',
        'EMAIL_EXISTS' => 'Email already registered',
        'WEAK_PASSWORD' => 'Password too weak',
        'ACCOUNT_SUSPENDED' => 'Account has been suspended',
        'MISSING_FIELDS' => 'Required fields are missing',
        'INVALID_FIELD' => 'Field value is invalid',
        'NOT_FOUND' => 'Resource not found',
        'QUOTA_REACHED' => 'Study quota has been reached',
        'SERVER_ERROR' => 'Internal server error'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
