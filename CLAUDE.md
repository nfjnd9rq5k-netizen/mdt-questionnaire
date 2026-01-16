# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Marketing research recruitment and screening platform (PHP/MySQL/JavaScript). Participants complete questionnaires with quota management, behavioral tracking, and secure data storage.

## Tech Stack

### Web Platform (Questionnaires)
- **Backend**: PHP 7.4+ (PDO, AES-256-CBC encryption, Argon2ID hashing)
- **Frontend**: Vanilla JavaScript (QuestionnaireEngine class), CSS3 with custom properties
- **Database**: MySQL 5.7+ (UTF-8mb4)
- **No build tools** - Traditional LAMP stack, no npm/webpack

### Mobile App (mdt-application/)
- **Framework**: React 19 + Vite 7
- **Styling**: Tailwind CSS 4
- **Icons**: Lucide React
- **State**: React Context (AuthContext)
- **API**: Custom ApiService class with JWT authentication

## Development Setup

### Web Platform (PHP)
```bash
# Database initialization
mysql -u [user] -p [database] < database/schema.sql

# First admin account: visit /admin/install.php

# Create new study: copy studies/_TEMPLATE_ETUDE/ to studies/NEW_STUDY_NAME/
```

### Mobile App (mdt-application/)
```bash
cd mdt-application
npm install
npm run dev       # Start Vite dev server (http://localhost:5173)
npm run build     # Build for production
```

## Key Entry Points

| URL | Purpose |
|-----|---------|
| `/studies/[NAME]/` | Participant questionnaire |
| `/admin/` | Admin login |
| `/admin/dashboard.php` | Admin panel |
| `/admin/install.php` | First-time setup |

## Architecture

### Frontend (js/engine.js)
`QuestionnaireEngine` class (~2000 lines) handles:
- Question rendering (types: single, multiple, number, text)
- Quota validation with real-time checking
- Conditional logic via `showIf` functions
- Behavioral metrics (paste detection, tab switches, keystroke patterns)
- Session recovery for incomplete questionnaires

### Backend (api/)
- `db.php` - PDO abstraction: `dbQuery()`, `dbQueryOne()`, `dbExecute()`, `dbLastId()`
- `config.php` - Encryption keys, session timeouts (1h), login security (5 attempts, 15min lockout)
- `save.php` - Stores encrypted responses to MySQL
- `admin-data.php` - Dashboard data provider
- `export-xlsx.php` / `export-jsonl*.php` - Data export endpoints

### Study Configuration (studies/[NAME]/questions.js)
Each study exports `STUDY_CONFIG`:
```javascript
const STUDY_CONFIG = {
  studyId: "STUDY_NAME",
  title: "...",
  questions: [...],
  quotas: [...],      // Simple, range (tranche), or combined quotas
  horaires: [...]     // Schedule slots
};
```

### Database Tables
- `responses` - Main participant data (encrypted)
- `signaletiques` - Personal info (encrypted)
- `answer_data` - Individual question answers
- `users` - Admin accounts with roles
- `access_ids` - Valid access codes per study
- `allowed_ips` / `pending_ips` - Admin IP whitelist

## Security Model

- All sensitive data encrypted with AES-256-CBC
- Encryption key: `api/secure_data/.encryption_key` (auto-generated)
- DB credentials: `api/secure_data/db_config.php` (protected by .htaccess)
- Admin passwords: Argon2ID hashing
- IP whitelist enforcement for admin access

## Quota System

Three quota types in `STUDY_CONFIG.quotas`:
1. **Simple**: Direct match `{ critere: "Q1", valeur: "option1", objectif: 10 }`
2. **Range**: Age brackets `{ critere: "Q2", type: "tranche", min: 18, max: 35, objectif: 15 }`
3. **Combined**: Multiple criteria `{ criteres: [{critere: "Q1", valeur: "F"}, {critere: "Q3", valeur: "oui"}], objectif: 5 }`

## Behavioral Metrics

`QuestionnaireEngine` tracks anti-bot/AI signals:
- Paste events, tab switches, focus lost
- Keystroke count and typing intervals
- Mouse movements, scroll events
- Computed `trustScore` stored with responses

## File Structure Highlights

```
api/secure_data/      # Protected directory (credentials, keys)
js/engine.js          # Core questionnaire logic
studies/_TEMPLATE_ETUDE/  # Copy this for new studies
database/schema.sql   # Full MySQL schema
css/style.css         # Questionnaire styling (participants only)
```

## Recent Changes & Issues Fixed

### Security Fixes (Jan 2026)
- **Export endpoints secured**: `api/export-jsonl-hq.php` and `api/export-jsonl-universal.php` had NO authentication - now require admin session
- **CORS headers removed**: Permissive `Access-Control-Allow-Origin: *` removed from export files

### Apostrophe Escaping Fix
- **Problem**: Escaped apostrophes (`\'`) in JS strings displayed as `d\` instead of `d'` in frontend
- **Solution**: Converted single-quoted strings containing `\'` to double-quoted or backtick strings
- **Files affected**: All `studies/*/questions.js` files + `database/schema.sql`
- **Note**: PowerShell regex approach corrupted files - used Node.js parser instead

### CSS Design Guidelines
**IMPORTANT**: The questionnaire CSS must NOT look AI-generated. Follow these rules:

1. **No gradients** - Use solid/flat colors only
2. **Simple shadows** - Single layer, subtle (`0 1px 3px rgba(0,0,0,0.08)`)
3. **No fancy animations** - Basic transitions only (`0.15s ease`)
4. **No custom cubic-bezier** - Use standard `ease` timing
5. **More spacing** - Adequate margins between buttons (12px+)
6. **Simple border-radius** - Consistent 6px, not overly rounded
7. **System fonts** - `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`

The admin dashboard uses **Tailwind CSS** (CDN) - separate from `css/style.css`.

## Local vs Production Database

### Configuration Files
Database credentials are stored in `api/secure_data/db_config.php`:
```php
<?php
// Local development
define('DB_HOST', 'localhost');
define('DB_NAME', 'etudes_local');
define('DB_USER', 'root');
define('DB_PASS', '');

// Production (VPS) - same structure, different values
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'u486755141_etudes');
// define('DB_USER', 'u486755141_admin');
// define('DB_PASS', 'secure_password');
```

### Development Workflow
1. **Local**: Use XAMPP/WAMP with local MySQL - full PHP/MySQL testing
2. **Production**: Same code on VPS, just different `db_config.php`
3. **Schema sync**: Run `database/schema.sql` on both environments

### Benefits of Local Development
- Claude can query/modify database directly
- No risk to production data
- Faster iteration
- Same code works on both (only config differs)

---

## Mobile App Architecture (mdt-application/)

### Overview
The mobile app uses Vite + React with Tailwind CSS. It provides a beautiful native-like UI with:
- Login/Register screens with multi-step registration
- Dashboard with solicitations and studies
- WebView integration for questionnaires (iframe)
- Profile management
- Points system

### File Structure
```
mdt-application/
├── src/
│   ├── App.jsx              # Entry point with AuthProvider
│   ├── MobileApp.jsx        # Main app with all screens (~1000 lines)
│   ├── contexts/
│   │   └── AuthContext.jsx  # Authentication state management
│   ├── services/
│   │   └── api.js           # API service with demo mode
│   └── maquette_mobile.jsx  # Original static mockup (archived)
├── package.json
└── vite.config.js
```

### API Service (src/services/api.js)
```javascript
// Toggle demo mode (set to false when backend is ready)
const DEMO_MODE = true;
const API_BASE_URL = 'https://etudes.lamaisondutest.fr/api/mobile';

// Key methods:
api.login(email, password)     // Returns JWT tokens
api.register(userData)         // Create panelist account
api.getProfile()               // Get panelist profile
api.getSollicitations()        // List available solicitations
api.getEtudes()                // List accepted studies
api.startStudy(id)             // Get WebView URL with JWT token
api.completeTask(etudeId, taskId)  // Mark task complete
```

### Auth Context (src/contexts/AuthContext.jsx)
```javascript
const { user, login, logout, isAuthenticated, loading } = useAuth();
```

### Screen Components (src/MobileApp.jsx)
| Component | Description |
|-----------|-------------|
| `LoginScreen` | Email/password login with validation |
| `RegisterScreen` | 3-step registration (personal, address, professional) |
| `OTPScreen` | 2FA code verification |
| `HomeScreen` | Dashboard with stats, pending solicitations, active studies |
| `SollicitationsScreen` | List of solicitations with filters |
| `SollicitationDetailScreen` | Details + WebView for questionnaire |
| `EtudesScreen` | List of accepted studies |
| `EtudeDetailScreen` | Study tasks with progress |
| `SettingsScreen` | Profile and logout |

### WebView Integration
When user clicks "Acceder au questionnaire":
1. `api.startStudy(id)` returns `{ study_url, webview_token }`
2. URL is opened in iframe with JWT token as query param
3. User completes questionnaire in existing web platform
4. On close, app refreshes data

### Demo Mode
Set `DEMO_MODE = true` in `api.js` to test without backend:
- Login accepts any credentials
- Returns mock data for solicitations, studies, points
- All API calls simulate network delay

---

## Panelist Targeting System

### New Database Tables (to add to schema.sql)

```sql
-- Panelists (mobile app users)
CREATE TABLE IF NOT EXISTS `panelists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unique_id` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `gender` ENUM('M', 'F', 'autre') DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `csp` VARCHAR(50) DEFAULT NULL COMMENT 'Catégorie socio-professionnelle',
    `household_size` INT UNSIGNED DEFAULT NULL,
    `has_children` TINYINT(1) DEFAULT NULL,
    `equipment` JSON DEFAULT NULL COMMENT '["voiture", "iphone", "aspirateur_robot"]',
    `brands_owned` JSON DEFAULT NULL COMMENT '["dyson", "apple", "samsung"]',
    `interests` JSON DEFAULT NULL COMMENT '["tech", "sport", "cuisine"]',
    `push_token` VARCHAR(255) DEFAULT NULL COMMENT 'Firebase FCM token',
    `status` ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    `points_balance` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_active` DATETIME DEFAULT NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitations (études envoyées aux panelistes)
CREATE TABLE IF NOT EXISTS `solicitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `study_url` VARCHAR(500) NOT NULL COMMENT 'URL for WebView',
    `reward_points` INT UNSIGNED DEFAULT 0,
    `criteria` JSON NOT NULL COMMENT 'Targeting criteria',
    `quota_target` INT UNSIGNED DEFAULT NULL,
    `quota_current` INT UNSIGNED DEFAULT 0,
    `priority` INT UNSIGNED DEFAULT 0 COMMENT 'Higher = shown first',
    `starts_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `status` ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`study_id`) REFERENCES `studies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Panelist-Solicitation junction (qui reçoit quoi)
CREATE TABLE IF NOT EXISTS `panelist_solicitations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `panelist_id` INT UNSIGNED NOT NULL,
    `solicitation_id` INT UNSIGNED NOT NULL,
    `status` ENUM('eligible', 'notified', 'viewed', 'started', 'completed', 'screened_out') DEFAULT 'eligible',
    `notified_at` DATETIME DEFAULT NULL,
    `viewed_at` DATETIME DEFAULT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `response_id` INT UNSIGNED DEFAULT NULL COMMENT 'Link to responses table',
    UNIQUE KEY `idx_panelist_solicitation` (`panelist_id`, `solicitation_id`),
    FOREIGN KEY (`panelist_id`) REFERENCES `panelists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`solicitation_id`) REFERENCES `solicitations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`response_id`) REFERENCES `responses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Targeting Criteria Format (JSON)
```json
{
  "gender": ["F"],
  "age_min": 25,
  "age_max": 45,
  "regions": ["Île-de-France", "PACA"],
  "equipment_required": ["voiture"],
  "equipment_excluded": [],
  "brands_owned_any": ["dyson", "shark"],
  "interests_any": ["menage", "tech"],
  "csp": ["cadre", "employe"],
  "has_children": true
}
```

### Matching Algorithm (api/matching.php)
```php
function findMatchingPanelists(int $solicitationId): array {
    $solicitation = dbQueryOne("SELECT * FROM solicitations WHERE id = ?", [$solicitationId]);
    $criteria = json_decode($solicitation['criteria'], true);

    $sql = "SELECT id FROM panelists WHERE status = 'active'";
    $params = [];

    // Gender filter
    if (!empty($criteria['gender'])) {
        $placeholders = implode(',', array_fill(0, count($criteria['gender']), '?'));
        $sql .= " AND gender IN ($placeholders)";
        $params = array_merge($params, $criteria['gender']);
    }

    // Age range
    if (!empty($criteria['age_min'])) {
        $sql .= " AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= ?";
        $params[] = $criteria['age_min'];
    }
    if (!empty($criteria['age_max'])) {
        $sql .= " AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= ?";
        $params[] = $criteria['age_max'];
    }

    // Equipment required (must have ALL)
    if (!empty($criteria['equipment_required'])) {
        foreach ($criteria['equipment_required'] as $equip) {
            $sql .= " AND JSON_CONTAINS(equipment, ?)";
            $params[] = json_encode($equip);
        }
    }

    // Brands owned (must have ANY)
    if (!empty($criteria['brands_owned_any'])) {
        $brandConditions = [];
        foreach ($criteria['brands_owned_any'] as $brand) {
            $brandConditions[] = "JSON_CONTAINS(brands_owned, ?)";
            $params[] = json_encode($brand);
        }
        $sql .= " AND (" . implode(' OR ', $brandConditions) . ")";
    }

    // Region filter
    if (!empty($criteria['regions'])) {
        $placeholders = implode(',', array_fill(0, count($criteria['regions']), '?'));
        $sql .= " AND region IN ($placeholders)";
        $params = array_merge($params, $criteria['regions']);
    }

    return dbQuery($sql, $params);
}
```

### Mobile API Endpoints (Planned)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/mobile/auth/register` | POST | Create panelist account |
| `/api/mobile/auth/login` | POST | Login, returns JWT |
| `/api/mobile/profile` | GET/PUT | Get/update panelist profile |
| `/api/mobile/studies` | GET | List eligible studies for panelist |
| `/api/mobile/studies/{id}/start` | POST | Mark study as started, get WebView URL |
| `/api/mobile/studies/{id}/complete` | POST | Called by WebView on completion |
| `/api/mobile/notifications/token` | POST | Register FCM push token |

### Push Notification Flow
```
1. Admin creates solicitation with criteria
2. Cron job runs matching algorithm
3. Eligible panelists inserted into panelist_solicitations
4. Firebase sends push to each panelist's FCM token
5. App receives notification, shows in dashboard
6. Panelist taps → WebView loads study URL
```

---

## Local Development Notes

- **With PHP (XAMPP/WAMP)**: Full functionality, database access
- **Without PHP**: Use `npx serve -p 3000` for static file testing only
- **API calls will fail** with static server (PHP returns source code)
- **Test questionnaire UI only**: Navigation and styling work, saves/loads require PHP
