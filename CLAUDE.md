# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## Project Overview

**La Maison du Test** - Marketing research platform with:
1. **Web Platform**: Questionnaires for participant recruitment (PHP/MySQL/JS)
2. **Mobile App**: React app for panelists to receive and complete studies
3. **Admin Dashboard**: Manage studies, participants, and data exports

## Tech Stack

| Component | Technology |
|-----------|------------|
| Web Backend | PHP 7.4+ (PDO, AES-256-CBC, Argon2ID) |
| Web Frontend | Vanilla JS (QuestionnaireEngine), CSS3 |
| Mobile App | React 19 + Vite 7 + Tailwind CSS 4 |
| Database | MySQL 5.7+ (UTF-8mb4) |
| Hosting | Hostinger (current: mediumvioletred-elephant-640253.hostingersite.com) |

## Project Structure

```
etudes/
├── admin/                  # Admin dashboard (PHP + Tailwind CDN)
├── api/
│   ├── mobile/             # Mobile API (JWT auth, 15 endpoints)
│   │   ├── auth/           # register, login, refresh, logout
│   │   ├── config.php      # JWT config, CORS helpers
│   │   ├── jwt.php         # Token generation/verification
│   │   ├── profile.php     # GET/PUT panelist profile
│   │   ├── studies.php     # List eligible studies
│   │   ├── study-start.php # Start study, get WebView URL
│   │   └── ...
│   ├── secure_data/        # DB credentials, encryption keys
│   ├── db.php              # PDO abstraction
│   ├── config.php          # App config
│   └── save.php            # Save questionnaire responses
├── css/                    # Questionnaire styles
├── database/
│   └── schema.sql          # Full MySQL schema
├── js/
│   └── engine.js           # QuestionnaireEngine (~2000 lines)
├── mdt-application/        # Mobile App (React)
│   └── src/
│       ├── App.jsx         # Entry point with AuthProvider
│       ├── MobileApp.jsx   # All screens (~1000 lines)
│       ├── contexts/
│       │   └── AuthContext.jsx
│       └── services/
│           └── api.js      # API service (JWT, all endpoints)
└── studies/                # Study configurations
    ├── _TEMPLATE_ETUDE/    # Template for new studies
    └── [STUDY_NAME]/       # Each study folder
        └── questions.js    # STUDY_CONFIG
```

## Development Setup

### Web Platform (PHP)
```bash
# Database
mysql -u root -p [database] < database/schema.sql

# First admin: visit /admin/install.php

# New study: copy studies/_TEMPLATE_ETUDE/ to studies/NEW_STUDY/
```

### Mobile App
```bash
cd mdt-application
npm install
npm run dev       # http://localhost:5173
npm run build     # Production build
```

## Database Tables

### Core (Questionnaires)
- `studies` - Study configurations
- `responses` - Participant responses (encrypted)
- `signaletiques` - Personal info (encrypted)
- `answers` - Individual question answers
- `users` - Admin accounts

### Mobile App
- `panelists` - Mobile app users
- `panelist_sessions` - JWT sessions
- `solicitations` - Studies sent to panelists
- `panelist_solicitations` - Who receives what
- `panelist_points_history` - Points transactions
- `push_notifications` - Notification history

## Mobile API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/mobile/` | GET | No | API info |
| `/auth/register.php` | POST | No | Create account |
| `/auth/login.php` | POST | No | Login, get JWT |
| `/auth/refresh.php` | POST | No | Refresh token |
| `/auth/logout.php` | POST | Yes | Invalidate session |
| `/profile.php` | GET/PUT | Yes | Get/update profile |
| `/studies.php` | GET | Yes | List eligible studies |
| `/study-start.php` | POST | Yes | Start study |
| `/study-complete.php` | POST | Yes | Complete study |
| `/points-history.php` | GET | Yes | Points history |
| `/notifications.php` | GET/POST | Yes | Notifications |
| `/push-token.php` | POST | Yes | Register FCM token |

## Mobile App Screens

| Screen | Description |
|--------|-------------|
| `LoginScreen` | Email/password login |
| `RegisterScreen` | 3-step registration |
| `OTPScreen` | 2FA verification |
| `HomeScreen` | Dashboard with stats |
| `SollicitationsScreen` | List of pending solicitations |
| `SollicitationDetailScreen` | Details + WebView for questionnaire |
| `EtudesScreen` | Accepted studies with tasks |
| `EtudeDetailScreen` | Task progress |
| `SettingsScreen` | Profile and logout |

## Security

- **Encryption**: AES-256-CBC for sensitive data
- **Passwords**: Argon2ID hashing
- **Auth**: JWT (1h access, 30d refresh tokens)
- **Admin**: IP whitelist enforcement
- **API**: CORS configured for mobile app

## Key Files

| File | Purpose |
|------|---------|
| `api/secure_data/db_config.php` | Database credentials |
| `api/secure_data/.encryption_key` | Auto-generated encryption key |
| `api/mobile/config.php` | JWT secret, CORS config |
| `mdt-application/src/services/api.js` | Mobile API client |
| `js/engine.js` | Questionnaire rendering engine |

## Quota System

Three types in `STUDY_CONFIG.quotas`:
```javascript
// Simple
{ critere: "Q1", valeur: "option1", objectif: 10 }

// Range (age)
{ critere: "Q2", type: "tranche", min: 18, max: 35, objectif: 15 }

// Combined
{ criteres: [{critere: "Q1", valeur: "F"}, {critere: "Q3", valeur: "oui"}], objectif: 5 }
```

## URLs

| Environment | URL |
|-------------|-----|
| Hostinger (temp) | https://mediumvioletred-elephant-640253.hostingersite.com |
| Mobile API | /api/mobile/ |
| Admin | /admin/ |
| Studies | /studies/[NAME]/ |

## Git Workflow

```bash
# Commit changes
git add .
git commit -m "Description"
git push origin main

# Auto-deploys to Hostinger via Git integration
```

## Notes

- Mobile app uses `DEMO_MODE` flag in `api.js` for local testing
- WebView in app loads questionnaires from web platform with JWT token
- Points are awarded when panelist completes a study
- Behavioral metrics (trustScore) tracked for anti-bot detection
