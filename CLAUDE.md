# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Marketing research recruitment and screening platform (PHP/MySQL/JavaScript). Participants complete questionnaires with quota management, behavioral tracking, and secure data storage.

## Tech Stack

- **Backend**: PHP 7.4+ (PDO, AES-256-CBC encryption, Argon2ID hashing)
- **Frontend**: Vanilla JavaScript (QuestionnaireEngine class), CSS3 with custom properties
- **Database**: MySQL 5.7+ (UTF-8mb4)
- **No build tools** - Traditional LAMP stack, no npm/webpack

## Development Setup

```bash
# Database initialization
mysql -u [user] -p [database] < database/schema.sql

# First admin account: visit /admin/install.php

# Create new study: copy studies/_TEMPLATE_ETUDE/ to studies/NEW_STUDY_NAME/
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

## Local Development Notes

- **No PHP locally**: Use `npx serve -p 3000` for static file testing
- **API calls will fail**: PHP endpoints return raw source code with static server
- **Test questionnaire UI only**: Navigation and styling work, but saves/loads don't
