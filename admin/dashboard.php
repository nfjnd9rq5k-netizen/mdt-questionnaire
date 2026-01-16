<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
// Configure session cookie to be accessible from all paths (fix for cross-directory AJAX)
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
session_start();
require_once '../api/config.php';
require_once '../api/db.php';
require_once '../api/security.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$adminDisplayName = $_SESSION['display_name'] ?? 'Admin';
$adminRole = $_SESSION['user_role'] ?? 'user';
$allowedStudies = $_SESSION['allowed_studies'] ?? [];
$userId = $_SESSION['user_id'] ?? '';
$userDbId = $_SESSION['user_db_id'] ?? null;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();
if (isset($_GET['logout'])) {
    // Logger la déconnexion en MySQL
    try {
        dbExecute(
            "INSERT INTO admin_logs (user_id, action, username, ip_address, details) VALUES (?, 'logout', ?, ?, ?)",
            [$userDbId, $_SESSION['username'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, "Déconnexion: $adminDisplayName"]
        );
    } catch (Exception $e) {
        // Ignorer les erreurs de log
    }
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$canAccessAllStudies = ($adminRole === 'super_admin' || $adminRole === 'admin' || in_array('*', $allowedStudies));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrfMeta() ?>
    <title>Administration MDT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        sidebar: '#0f172a',
                        'sidebar-hover': '#1e3a5f',
                        'sidebar-active': '#3b82f6',
                        'mdt-blue': '#0F243E',
                        'mdt-light': '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        tr.hidden-by-filter { display: none; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .filter-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 32px;
        }
        /* Multi-select dropdown styles */
        .multi-select-container {
            position: relative;
            display: inline-block;
            min-width: 140px;
        }
        .multi-select-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
        }
        .multi-select-btn:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .multi-select-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-color: #2563eb;
        }
        .multi-select-btn .arrow {
            width: 16px;
            height: 16px;
            transition: transform 0.2s ease;
        }
        .multi-select-btn.open .arrow {
            transform: rotate(180deg);
        }
        .multi-select-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            min-width: 200px;
            max-width: 280px;
            max-height: 250px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            padding: 6px;
        }
        .multi-select-dropdown.show {
            display: block;
            animation: dropdownFadeIn 0.15s ease;
        }
        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .multi-select-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s ease;
            font-size: 13px;
            color: #334155;
        }
        .multi-select-option:hover {
            background: #f1f5f9;
        }
        .multi-select-option input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid #cbd5e1;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        .multi-select-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            background: white;
            color: #3b82f6;
            font-size: 11px;
            font-weight: 600;
            border-radius: 10px;
        }
        .multi-select-btn.active .multi-select-badge {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        /* Filter tags */
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
            font-size: 12px;
            color: white;
            backdrop-filter: blur(4px);
        }
        .filter-tag-remove {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.15s ease;
        }
        .filter-tag-remove:hover {
            opacity: 1;
        }
        /* Filters section */
        .filters-wrapper {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }
        .filters-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-start;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: box-shadow 0.2s ease;
        }
        .card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .stat-card { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 768px) {
            .sidebar-mobile { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar-mobile.open { transform: translateX(0); }
            .sidebar-overlay { opacity: 0; visibility: hidden; transition: all 0.3s ease; }
            .sidebar-overlay.open { opacity: 1; visibility: visible; }
        }
    </style>
</head>
<body class="bg-mdt-light font-sans">
    <div class="flex h-screen overflow-hidden">
        <div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 bg-black/50 z-40 md:hidden" onclick="toggleMobileMenu()"></div>
        <aside id="sidebar" class="sidebar-mobile md:translate-x-0 fixed md:relative w-64 md:w-52 bg-sidebar flex flex-col z-50 h-full">
            <div class="p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-sidebar-active rounded-lg flex items-center justify-center text-white font-bold text-lg">M</div>
                <div>
                    <div class="text-white font-semibold text-sm">Administration</div>
                    <div class="text-white/60 text-xs">Maison du Test</div>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">Tableau de bord</button>
                <button onclick="switchTab('studies')" id="nav-studies" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">Études</button>
                <?php if ($adminRole === 'super_admin'): ?>
                <button onclick="switchTab('dataia')" id="nav-dataia" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">🤖 Data IA</button>
                <?php endif; ?>
                <button onclick="switchTab('closed')" id="nav-closed" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">Archives</button>
                <?php if ($adminRole === 'super_admin'): ?>
                <button onclick="switchTab('accounts')" id="nav-accounts" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">Comptes</button>
                <?php endif; ?>
            </nav>
            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-white font-medium text-sm"><?= strtoupper(substr($adminDisplayName, 0, 1)) ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white text-sm font-medium truncate"><?= htmlspecialchars($adminDisplayName) ?></div>
                        <div class="text-white/50 text-xs truncate"><?php if ($adminRole === 'super_admin') echo 'Super Admin'; elseif ($adminRole === 'admin') echo 'Admin'; else echo 'Utilisateur'; ?></div>
                    </div>
                </div>
            </div>
        </aside>
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-4 md:px-6 py-3 md:py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button onclick="toggleMobileMenu()" class="md:hidden p-2 hover:bg-gray-100 rounded-lg transition text-gray-600 text-xl">☰</button>
                    <h1 class="text-lg md:text-xl font-semibold text-gray-800 truncate" id="page-title">Tableau de bord</h1>
                </div>
                <div class="flex items-center gap-2 md:gap-3">
                    <button onclick="loadData()" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-500 text-lg" title="Actualiser">↻</button>
                    <?php if ($adminRole === 'super_admin'): ?>
                    <a href="change-password.php" class="hidden sm:block p-2 hover:bg-gray-100 rounded-lg transition text-gray-500 text-lg" title="Paramètres">⚙</a>
                    <?php endif; ?>
                    <a href="?logout=1" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition"><span class="hidden sm:inline">Déconnexion</span><span class="sm:hidden">✕</span></a>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto scrollbar-thin p-4 md:p-6" id="main-content">
                <div class="flex items-center justify-center h-64"><div class="animate-spin w-8 h-8 border-4 border-sidebar-active border-t-transparent rounded-full"></div></div>
            </main>
        </div>
    </div>
    <div id="responses-modal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-2 md:p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 id="modal-title" class="font-semibold text-gray-800">Détail</h3>
                <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-400 text-xl">✕</button>
            </div>
            <div id="modal-body" class="p-4 md:p-6 overflow-y-auto flex-1 scrollbar-thin"></div>
            <div id="modal-footer" class="px-4 md:px-6 py-4 border-t border-gray-100 flex justify-end gap-3 hidden">
                <button onclick="closeModal()" class="px-4 py-2 text-sm btn-secondary rounded-lg">Annuler</button>
                <button onclick="saveChanges()" class="px-4 py-2 text-sm btn-primary rounded-lg">Enregistrer</button>
            </div>
        </div>
    </div>
    <div id="preview-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-2 md:p-4">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-4 md:px-6 py-4 bg-purple-600 text-white flex items-center justify-between">
                <div><h3 id="preview-title" class="font-semibold">Prévisualisation</h3><p id="preview-subtitle" class="text-sm text-purple-200"></p></div>
                <button onclick="closePreview()" class="p-2 hover:bg-purple-500 rounded-lg transition text-xl">✕</button>
            </div>
            <div class="px-4 md:px-6 py-3 bg-white border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2"><span class="text-sm text-gray-500">Question</span><span id="preview-counter" class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded font-medium text-sm">1 / 10</span></div>
                <select id="preview-jump" onchange="jumpToQuestion()" class="filter-select px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white shadow-sm"></select>
            </div>
            <div id="preview-body" class="p-4 md:p-6 overflow-y-auto flex-1 bg-white"></div>
            <div class="px-4 md:px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <button onclick="prevQuestion()" id="preview-prev" class="px-4 py-2 text-sm btn-secondary rounded-lg">← Précédent</button>
                <div class="hidden md:flex items-center gap-1" id="preview-dots"></div>
                <button onclick="nextQuestion()" id="preview-next" class="px-4 py-2 text-sm btn-primary rounded-lg">Suivant →</button>
            </div>
        </div>
    </div>
    <script>
        let allData = null;
        let currentTab = 'dashboard';
        let currentStudyId = null;
        let currentEditData = null;
        let isEditMode = false;
        const userRole = '<?= $adminRole ?>';
        const canEdit = (userRole === 'super_admin');

        // Récupère le token CSRF depuis la balise meta
        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        // Fonction utilitaire pour les requêtes POST sécurisées avec CSRF (JSON)
        async function securePost(url, data) {
            data.csrf_token = getCsrfToken();
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
        }

        // Fonction utilitaire pour les requêtes POST form sécurisées avec CSRF
        async function securePostForm(url, formBody) {
            formBody += '&csrf_token=' + encodeURIComponent(getCsrfToken());
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formBody
            });
        }

        function toggleMobileMenu() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }

        async function loadData() {
            try {
                const response = await fetch('../api/admin-data.php');
                const data = await response.json();
                if (data.success) { allData = data; render(); }
            } catch (error) { console.error('Erreur:', error); }
        }

        function switchTab(tab, studyId = null) {
            currentTab = tab;
            currentStudyId = studyId;
            document.querySelectorAll('.nav-item').forEach(btn => {
                btn.classList.remove('bg-sidebar-active', 'text-white');
                btn.classList.add('text-white/70', 'hover:bg-sidebar-hover');
            });
            const active = document.getElementById('nav-' + tab);
            if (active) {
                active.classList.add('bg-sidebar-active', 'text-white');
                active.classList.remove('text-white/70', 'hover:bg-sidebar-hover');
            }
            let title = { dashboard: 'Tableau de bord', studies: 'Études actives', dataia: 'Data IA - Collecte de données', closed: 'Archives', accounts: 'Gestion des comptes' }[tab] || 'Tableau de bord';
            if (studyId && allData) {
                const study = allData.studies.find(s => s.studyId === studyId);
                if (study) title = study.studyName;
            }
            document.getElementById('page-title').textContent = title;
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('sidebar-overlay').classList.remove('open');
            }
            render();
        }

        function goToStudy(studyId) {
            const study = allData.studies.find(s => s.studyId === studyId);
            if (!study) return;
            // Rediriger vers l'onglet approprié selon le type d'étude
            let tab;
            if (study.status === 'closed') {
                tab = 'closed';
            } else if (study.studyType === 'data_collection') {
                tab = 'dataia';
            } else {
                tab = 'studies';
            }
            switchTab(tab, studyId);
        }

        function render() {
            if (!allData && currentTab !== 'accounts') return;
            if (currentTab === 'dashboard') renderDashboard();
            else if (currentTab === 'studies') { if (currentStudyId) renderStudyDetail(currentStudyId, false); else renderStudiesList(false); }
            else if (currentTab === 'dataia') { if (currentStudyId) renderStudyDetail(currentStudyId, false); else renderIADashboard(); }
            else if (currentTab === 'closed') { if (currentStudyId) renderStudyDetail(currentStudyId, true); else renderStudiesList(true); }
            else if (currentTab === 'accounts') renderAccounts();
        }

        function renderDashboard() {
            const studies = allData.studies || [];
            // Séparer les études normales et IA
            const normalStudies = studies.filter(s => s.studyType !== 'data_collection');
            const iaStudies = studies.filter(s => s.studyType === 'data_collection');
            const activeNormalStudies = normalStudies.filter(s => s.status !== 'closed');
            const activeIaStudies = iaStudies.filter(s => s.status !== 'closed');
            
            // Stats pour études normales uniquement
            let totalQualifies = 0, totalRefuses = 0, totalParticipants = 0;
            normalStudies.forEach(s => { totalQualifies += s.stats?.qualifies || 0; totalRefuses += s.stats?.refuses || 0; totalParticipants += s.stats?.total || 0; });
            
            // Stats pour études IA
            let iaParticipants = 0, iaTexts = 0;
            iaStudies.forEach(s => {
                iaParticipants += (s.qualifies || []).length;
                (s.qualifies || []).forEach(p => { iaTexts += parseInt(p.texts_count) || 0; });
            });
            
            let html = `<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
                <div class="card stat-card p-4"><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Études actives</p><p class="text-2xl md:text-3xl font-bold text-sidebar-active">${activeNormalStudies.length}</p></div>
                <div class="card stat-card p-4"><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total participants</p><p class="text-2xl md:text-3xl font-bold text-gray-800">${totalParticipants}</p></div>
                <div class="card stat-card p-4"><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Qualifiés</p><p class="text-2xl md:text-3xl font-bold text-green-600">${totalQualifies}</p></div>
                <div class="card stat-card p-4"><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Refusés</p><p class="text-2xl md:text-3xl font-bold text-red-500">${totalRefuses}</p></div>
            </div>
            
            ${(activeIaStudies.length > 0 && userRole === 'super_admin') ? `
            <!-- Encart Data IA (super_admin uniquement) -->
            <div class="mb-6 card p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-purple-200 cursor-pointer hover:shadow-lg transition" onclick="switchTab('dataia')">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center text-white text-2xl shadow-lg">🤖</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Collecte Data IA</h3>
                            <p class="text-sm text-purple-600">${activeIaStudies.length} étude${activeIaStudies.length > 1 ? 's' : ''} active${activeIaStudies.length > 1 ? 's' : ''} • ${iaParticipants} participants • ${iaTexts} textes</p>
                        </div>
                    </div>
                    <div class="text-purple-600 text-xl">→</div>
                </div>
            </div>` : ''}
            
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6">
                <div class="xl:col-span-2 card p-4 md:p-6">
                    <div class="flex items-center justify-between mb-4"><h2 class="font-semibold text-gray-800">Études en cours</h2><button onclick="switchTab('studies')" class="text-sm text-sidebar-active hover:underline">Voir tout</button></div>
                    <div class="space-y-3">${activeNormalStudies.length === 0 ? '<p class="text-gray-400 text-sm">Aucune étude active</p>' : activeNormalStudies.slice(0, 5).map(s => {
                        const progress = s.stats?.total > 0 ? Math.round((s.stats.qualifies / s.stats.total) * 100) : 0;
                        return `<div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition" onclick="goToStudy('${s.studyId}')">
                            <div class="w-10 h-10 bg-sidebar-active/10 rounded-lg flex items-center justify-center text-sidebar-active font-bold text-sm">${s.studyName.substring(0,2).toUpperCase()}</div>
                            <div class="flex-1 min-w-0"><p class="font-medium text-gray-800 truncate">${esc(s.studyName)}</p><div class="flex items-center gap-2 mt-1"><div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-sidebar-active rounded-full" style="width: ${progress}%"></div></div><span class="text-xs text-gray-500">${progress}%</span></div></div>
                            <div class="text-right"><p class="text-sm font-semibold text-gray-800">${s.stats?.qualifies || 0}</p><p class="text-xs text-gray-400">qualifiés</p></div>
                        </div>`;
                    }).join('')}</div>
                </div>
                <div class="card p-4 md:p-6"><h2 class="font-semibold text-gray-800 mb-4">Activité récente</h2><div class="space-y-4">${getRecentActivity()}</div></div>
            </div>`;
            document.getElementById('main-content').innerHTML = html;
        }

        function getRecentActivity() {
            const activities = [];
            (allData.studies || []).forEach(s => { (s.qualifies || []).forEach(p => { activities.push({ name: p.prenom + ' ' + p.nom, study: s.studyName, studyId: s.studyId, date: p.date }); }); });
            activities.sort((a, b) => new Date(b.date) - new Date(a.date));
            return activities.slice(0, 5).map(a => `<div class="flex items-start gap-3"><div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 text-green-600 text-sm font-bold">✓</div><div class="flex-1 min-w-0"><p class="text-sm"><span class="font-medium text-gray-800">${esc(a.name)}</span> <span class="text-gray-500">qualifié(e)</span></p><p class="text-xs text-sidebar-active truncate">${esc(a.study)}</p><p class="text-xs text-gray-400">${formatTimeAgo(a.date)}</p></div></div>`).join('') || '<p class="text-gray-400 text-sm">Aucune activité récente</p>';
        }

        function formatTimeAgo(date) {
            if (!date) return '';
            const diff = Date.now() - new Date(date).getTime();
            const mins = Math.floor(diff / 60000);
            if (mins < 60) return 'Il y a ' + mins + ' min';
            const hours = Math.floor(mins / 60);
            if (hours < 24) return 'Il y a ' + hours + 'h';
            return 'Il y a ' + Math.floor(hours / 24) + 'j';
        }

        // ============================================================
        // DASHBOARD DATA IA - Métriques spécifiques pour la collecte de données
        // ============================================================
        // DASHBOARD IA - MÉTRIQUES DE QUALITÉ AVANCÉES v2.0
        // ============================================================
        function renderIADashboard() {
            const iaStudies = (allData.studies || []).filter(s => s.studyType === 'data_collection' && s.status !== 'closed');
            const closedIaStudies = (allData.studies || []).filter(s => s.studyType === 'data_collection' && s.status === 'closed');

            // Calculer les métriques globales
            let totalParticipants = 0, totalTexts = 0, totalWords = 0;
            let sumQuality = 0, sumBehavior = 0, sumContent = 0, sumAttention = 0;
            let participantsWithData = 0;
            const gradeDistribution = { A: 0, B: 0, C: 0, D: 0, F: 0 };
            const allFlags = {};
            let totalFlags = 0;

            iaStudies.forEach(s => {
                const qualifies = s.qualifies || [];
                totalParticipants += qualifies.length;
                qualifies.forEach(p => {
                    const qd = p.qualityData || {};
                    if (qd.overallQualityScore !== undefined) {
                        sumQuality += qd.overallQualityScore;
                        sumBehavior += qd.behaviorScore || 0;
                        sumContent += qd.contentScore || 0;
                        sumAttention += qd.attentionScore || 0;
                        participantsWithData++;
                        const grade = qd.qualityGrade || 'F';
                        gradeDistribution[grade] = (gradeDistribution[grade] || 0) + 1;
                        (qd.flags || []).forEach(flag => {
                            const flagName = flag.split(':')[0];
                            allFlags[flagName] = (allFlags[flagName] || 0) + 1;
                            totalFlags++;
                        });
                    }
                    totalTexts += qd.totalTextResponses || 0;
                    totalWords += qd.totalWords || 0;
                });
            });

            const avgQuality = participantsWithData > 0 ? Math.round(sumQuality / participantsWithData) : 0;
            const avgBehavior = participantsWithData > 0 ? Math.round(sumBehavior / participantsWithData) : 0;
            const avgContent = participantsWithData > 0 ? Math.round(sumContent / participantsWithData) : 0;
            const avgAttention = participantsWithData > 0 ? Math.round(sumAttention / participantsWithData) : 0;

            const getScoreClass = (score) => score >= 80 ? 'text-green-600' : score >= 60 ? 'text-amber-600' : 'text-red-500';
            const getGradeBg = (grade) => ({ A: 'bg-green-600', B: 'bg-blue-600', C: 'bg-amber-500', D: 'bg-orange-500', F: 'bg-red-500' }[grade] || 'bg-gray-400');

            let html = `
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Collecte de données IA</h2>
                        <p class="text-sm text-gray-500">${totalParticipants} participants &middot; ${totalTexts} réponses textuelles</p>
                    </div>
                </div>
            </div>

            <!-- Scores -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="card p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Score global</p>
                    <p class="text-2xl font-bold ${getScoreClass(avgQuality)}">${avgQuality}<span class="text-sm font-normal text-gray-400">/100</span></p>
                    <div class="mt-2 h-1.5 bg-gray-100 rounded-full"><div class="h-full rounded-full ${avgQuality >= 80 ? 'bg-green-500' : avgQuality >= 60 ? 'bg-amber-500' : 'bg-red-500'}" style="width:${avgQuality}%"></div></div>
                </div>
                <div class="card p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Comportement</p>
                    <p class="text-2xl font-bold ${getScoreClass(avgBehavior)}">${avgBehavior}<span class="text-sm font-normal text-gray-400">/100</span></p>
                    <p class="text-xs text-gray-400 mt-1">Trust score, focus</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Contenu</p>
                    <p class="text-2xl font-bold ${getScoreClass(avgContent)}">${avgContent}<span class="text-sm font-normal text-gray-400">/100</span></p>
                    <p class="text-xs text-gray-400 mt-1">Qualité des textes</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Attention</p>
                    <p class="text-2xl font-bold ${getScoreClass(avgAttention)}">${avgAttention}<span class="text-sm font-normal text-gray-400">/100</span></p>
                    <p class="text-xs text-gray-400 mt-1">Checks réussis</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Études actives -->
                <div class="xl:col-span-2 card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Études actives</h3>
                        <span class="text-xs text-gray-500">${iaStudies.length} étude(s)</span>
                    </div>
                    ${iaStudies.length === 0 ? '<p class="text-gray-400 text-sm py-6 text-center">Aucune étude active</p>' :
                    `<div class="space-y-3">
                        ${iaStudies.map(s => {
                            const qualifies = s.qualifies || [];
                            let studyAvgQ = 0, studyTexts = 0, studyFlags = 0;
                            qualifies.forEach(p => {
                                const qd = p.qualityData || {};
                                studyAvgQ += qd.overallQualityScore || 0;
                                studyTexts += qd.totalTextResponses || 0;
                                studyFlags += qd.flagsCount || 0;
                            });
                            studyAvgQ = qualifies.length > 0 ? Math.round(studyAvgQ / qualifies.length) : 0;
                            const gradeCount = { A: 0, B: 0, C: 0, D: 0, F: 0 };
                            qualifies.forEach(p => { const g = p.qualityData?.qualityGrade || 'F'; gradeCount[g]++; });

                            return `<div class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition" onclick="switchTab('dataia', '${s.studyId}')">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-bold ${studyAvgQ >= 80 ? 'bg-green-600' : studyAvgQ >= 60 ? 'bg-amber-500' : 'bg-red-500'}">${studyAvgQ}%</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-800 truncate">${esc(s.studyName)}</p>
                                        <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                            <span>${qualifies.length} participants</span>
                                            <span>${studyTexts} textes</span>
                                            ${studyFlags > 0 ? `<span class="text-red-500">${studyFlags} alertes</span>` : ''}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        ${['A','B','C','D','F'].filter(g => gradeCount[g] > 0).map(g => `<span class="text-xs px-1.5 py-0.5 rounded ${getGradeBg(g)} text-white">${g}:${gradeCount[g]}</span>`).join('')}
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="event.stopPropagation(); exportJSONL('${s.studyId}')" class="px-3 py-1.5 text-xs btn-primary rounded">JSONL</button>
                                        <button onclick="event.stopPropagation(); exportStudy('${s.studyId}', 'qualifies')" class="px-3 py-1.5 text-xs btn-secondary rounded">Excel</button>
                                    </div>
                                </div>
                            </div>`;
                        }).join('')}
                    </div>`}
                </div>

                <!-- Panel latéral -->
                <div class="space-y-4">
                    <!-- Distribution des grades -->
                    <div class="card p-5">
                        <h3 class="font-semibold text-gray-800 mb-4">Distribution des grades</h3>
                        <div class="space-y-3">
                            ${['A','B','C','D','F'].map(grade => {
                                const count = gradeDistribution[grade] || 0;
                                const percent = participantsWithData > 0 ? Math.round(count / participantsWithData * 100) : 0;
                                const labels = { A: 'Excellent', B: 'Bon', C: 'Correct', D: 'Faible', F: 'Rejeté' };
                                return `<div class="flex items-center gap-3">
                                    <span class="w-6 h-6 ${getGradeBg(grade)} text-white rounded text-xs font-bold flex items-center justify-center">${grade}</span>
                                    <div class="flex-1">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="text-gray-600">${labels[grade]}</span>
                                            <span class="font-medium text-gray-800">${count}</span>
                                        </div>
                                        <div class="h-1.5 bg-gray-100 rounded-full"><div class="h-full ${getGradeBg(grade)} rounded-full" style="width:${percent}%"></div></div>
                                    </div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>

                    <!-- Alertes -->
                    <div class="card p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-800">Alertes qualité</h3>
                            ${totalFlags > 0 ? `<span class="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded-full">${totalFlags}</span>` : ''}
                        </div>
                        ${Object.keys(allFlags).length === 0 ?
                            '<p class="text-sm text-green-600 text-center py-3">Aucune alerte</p>' :
                            `<div class="space-y-2">
                                ${Object.entries(allFlags).sort((a,b) => b[1] - a[1]).slice(0, 6).map(([flag, count]) => {
                                    const flagLabels = {
                                        'SPEEDRUN_DETECTED': 'Réponse trop rapide',
                                        'EXCESSIVE_PASTE': 'Copier-coller excessif',
                                        'EXCESSIVE_TAB_SWITCHES': 'Changements onglets',
                                        'ATTENTION_FAILED': 'Attention check échoué',
                                        'SESSION_TOO_SHORT': 'Session trop courte',
                                        'LOW_KEYSTROKE_RATIO': 'Peu de frappe clavier',
                                        'DUPLICATE_RESPONSES': 'Réponses dupliquées',
                                        'MANY_SHORT_RESPONSES': 'Réponses courtes'
                                    };
                                    return `<div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                        <span class="text-xs text-gray-600">${flagLabels[flag] || flag}</span>
                                        <span class="text-xs font-medium text-red-600">${count}</span>
                                    </div>`;
                                }).join('')}
                            </div>`
                        }
                    </div>

                    <!-- Stats -->
                    <div class="card p-5">
                        <h3 class="font-semibold text-gray-800 mb-3">Statistiques</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="text-center p-2 bg-gray-50 rounded">
                                <p class="text-xs text-gray-500">Total mots</p>
                                <p class="font-semibold text-gray-800">${totalWords.toLocaleString()}</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded">
                                <p class="text-xs text-gray-500">Moy/texte</p>
                                <p class="font-semibold text-gray-800">${totalTexts > 0 ? Math.round(totalWords / totalTexts) : 0}</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded">
                                <p class="text-xs text-gray-500">Grade A+B</p>
                                <p class="font-semibold text-green-600">${gradeDistribution.A + gradeDistribution.B}</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded">
                                <p class="text-xs text-gray-500">Grade D+F</p>
                                <p class="font-semibold text-red-500">${gradeDistribution.D + gradeDistribution.F}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            ${closedIaStudies.length > 0 ? `
            <div class="mt-6 card p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Études terminées (${closedIaStudies.length})</h3>
                <div class="space-y-2">
                    ${closedIaStudies.map(s => {
                        const qualifies = s.qualifies || [];
                        let avgQ = 0;
                        qualifies.forEach(p => avgQ += p.qualityData?.overallQualityScore || 0);
                        avgQ = qualifies.length > 0 ? Math.round(avgQ / qualifies.length) : 0;
                        return `<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded flex items-center justify-center text-white text-xs font-bold ${avgQ >= 70 ? 'bg-green-600' : 'bg-gray-400'}">${avgQ}%</div>
                                <span class="text-gray-700">${esc(s.studyName)}</span>
                                <span class="text-xs text-gray-400">(${qualifies.length})</span>
                            </div>
                            <button onclick="exportJSONL('${s.studyId}')" class="px-3 py-1 text-xs btn-secondary rounded">Export</button>
                        </div>`;
                    }).join('')}
                </div>
            </div>` : ''}
            `;

            document.getElementById('main-content').innerHTML = html;
        }

        function renderStudiesList(showClosed) {
            // Exclure les études de type data_collection (affichées dans l'onglet Data IA)
            let studies = (allData.studies || []).filter(s => {
                const isDataCollection = s.studyType === 'data_collection';
                const matchStatus = showClosed ? s.status === 'closed' : s.status !== 'closed';
                return matchStatus && !isDataCollection;
            });
            
            // Header avec bouton créer (seulement pour études actives et si admin/super_admin)
            let headerHtml = '';
            if (!showClosed && (userRole === 'admin' || userRole === 'super_admin')) {
                headerHtml = `<div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Études en cours (${studies.length})</h2>
                    <button onclick="openStudyBuilder()" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition flex items-center gap-2 text-sm">
                        <span class="text-lg">+</span> Créer une étude
                    </button>
                </div>`;
            }
            
            if (studies.length === 0) { 
                let emptyHtml = headerHtml + '<div class="flex flex-col items-center justify-center h-64 text-gray-400"><p>Aucune étude</p>';
                if (!showClosed && (userRole === 'admin' || userRole === 'super_admin')) {
                    emptyHtml += '<button onclick="openStudyBuilder()" class="mt-4 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm">Créer ma première étude</button>';
                }
                emptyHtml += '</div>';
                document.getElementById('main-content').innerHTML = emptyHtml; 
                return; 
            }
            let html = headerHtml + `<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">`;
            studies.forEach(study => {
                const stats = study.stats || {};
                const progress = stats.total > 0 ? Math.round((stats.qualifies / stats.total) * 100) : 0;
                html += `<div class="card p-5 hover:shadow-lg transition cursor-pointer" onclick="goToStudy('${study.studyId}')">
                    <div class="flex items-start justify-between mb-4"><div class="w-12 h-12 bg-sidebar-active/10 rounded-xl flex items-center justify-center text-sidebar-active font-bold">${study.studyName.substring(0,2).toUpperCase()}</div>${showClosed ? '<span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">Terminée</span>' : '<span class="px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded-full">Active</span>'}</div>
                    <h3 class="font-semibold text-gray-800 mb-1 leading-tight">${esc(study.studyName)}</h3><p class="text-sm text-gray-500 mb-4">${esc(study.studyDate || '')}</p>
                    <div class="flex items-center justify-between mb-2"><span class="text-sm text-gray-500">Progression</span><span class="text-sm font-semibold text-gray-800">${stats.qualifies || 0}/${stats.total || 0}</span></div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-sidebar-active rounded-full transition-all" style="width: ${progress}%"></div></div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-sm"><div class="text-center"><p class="font-semibold text-blue-600">${stats.qualifies || 0}</p><p class="text-gray-400 text-xs">Qualifiés</p></div><div class="text-center"><p class="font-semibold text-red-500">${stats.refuses || 0}</p><p class="text-gray-400 text-xs">Refusés</p></div><div class="text-center"><p class="font-semibold text-amber-500">${progress}%</p><p class="text-gray-400 text-xs">Taux</p></div></div>
                </div>`;
            });
            html += '</div>';
            document.getElementById('main-content').innerHTML = html;
        }

        function renderStudyDetail(studyId, showClosed) {
            const study = allData.studies.find(s => s.studyId === studyId);
            if (!study) { renderStudiesList(showClosed); return; }
            const stats = study.stats || {}, qualifies = study.qualifies || [], refuses = study.refuses || [];
            const isDataCollection = study.studyType === 'data_collection';

            // Headers différents selon le type d'étude
            const qualifiesHeaders = isDataCollection
                ? `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Grade</th><th class="px-4 py-3 text-left">Score</th><th class="px-4 py-3 text-left">Comportement</th><th class="px-4 py-3 text-left">Contenu</th><th class="px-4 py-3 text-left">Attention</th><th class="px-4 py-3 text-left">Textes</th><th class="px-4 py-3 text-left">Alertes</th><th class="px-4 py-3 text-left">Actions</th>`
                : `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Nom</th><th class="px-4 py-3 text-left">Prénom</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Téléphone</th><th class="px-4 py-3 text-left">Ville</th><th class="px-4 py-3 text-left">Horaire</th><th class="px-4 py-3 text-left">Actions</th>`;
            
            const encoursHeaders = isDataCollection
                ? `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Pseudo</th><th class="px-4 py-3 text-left">Questions</th><th class="px-4 py-3 text-left">Début</th><th class="px-4 py-3 text-left">Actions</th>`
                : `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Nom</th><th class="px-4 py-3 text-left">Prénom</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Téléphone</th><th class="px-4 py-3 text-left">Questions</th><th class="px-4 py-3 text-left">Début</th><th class="px-4 py-3 text-left">Actions</th>`;

            const refusesHeaders = isDataCollection
                ? `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Pseudo</th><th class="px-4 py-3 text-left">Raisons</th><th class="px-4 py-3 text-left">Actions</th>`
                : `<th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Nom</th><th class="px-4 py-3 text-left">Prénom</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Téléphone</th><th class="px-4 py-3 text-left">Raisons</th><th class="px-4 py-3 text-left">Actions</th>`;

            // Export bouton différent pour data_collection
            const exportBtn = isDataCollection 
                ? `<div class="flex gap-2"><button onclick="exportStudy('${study.studyId}', 'qualifies')" class="px-4 py-2 text-sm btn-primary rounded-lg">Export Excel</button><button onclick="exportJSONL('${study.studyId}')" class="px-4 py-2 text-sm btn-secondary rounded-lg">Export JSONL</button></div>`
                : `<button onclick="exportStudy('${study.studyId}', 'qualifies')" class="px-4 py-2 text-sm btn-primary rounded-lg w-full sm:w-auto">Exporter</button>`;

            let html = `<div class="mb-6">
                <button onclick="switchTab('${showClosed ? 'closed' : 'studies'}')" class="flex items-center gap-2 text-gray-500 hover:text-gray-800 transition mb-4 text-sm">← Retour aux études</button>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div><h2 class="text-xl font-semibold text-gray-800">${esc(study.studyName)}${isDataCollection ? ' <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full ml-2">Data Collection</span>' : ''}</h2><p class="text-sm text-gray-500">${esc(study.studyDate || '')}</p><a href="../studies/${study.folder}/" target="_blank" class="inline-flex items-center gap-1 text-sm text-sidebar-active hover:underline mt-1">Ouvrir le questionnaire →</a></div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button onclick="previewStudy('${study.folder}')" class="px-4 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded-lg transition">Prévisualiser</button>
                        <button onclick="copyStudyLink('${study.folder}')" class="px-4 py-2 text-sm btn-secondary rounded-lg">Copier le lien</button>
                        ${!showClosed ? `<button onclick="closeStudy('${study.folder}')" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">Terminer</button>` : `<button onclick="reopenStudy('${study.folder}')" class="px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition">Réouvrir</button>${userRole === 'super_admin' ? `<button onclick="deleteStudy('${study.folder}', '${esc(study.studyName).replace(/'/g, "\\'")}')" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition">🗑 Supprimer</button>` : ''}`}
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 mb-6">
                <div class="card p-4"><p class="text-xs text-gray-400 uppercase mb-1">Total</p><p class="text-xl md:text-2xl font-bold text-gray-800">${stats.total || 0}</p></div>
                <div class="card p-4"><p class="text-xs text-gray-400 uppercase mb-1">${isDataCollection ? 'Complétés' : 'Qualifiés'}</p><p class="text-xl md:text-2xl font-bold text-blue-600">${stats.qualifies || 0}</p></div>
                <div class="card p-4 border-amber-200"><p class="text-xs text-amber-500 uppercase mb-1">En cours</p><p class="text-xl md:text-2xl font-bold text-amber-500">${stats.en_cours || 0}</p></div>
                <div class="card p-4"><p class="text-xs text-gray-400 uppercase mb-1">${isDataCollection ? 'Abandons' : 'Refusés'}</p><p class="text-xl md:text-2xl font-bold text-red-500">${stats.refuses || 0}</p></div>
                <div class="card p-4 col-span-2 sm:col-span-1"><p class="text-xs text-gray-400 uppercase mb-1">Taux</p><p class="text-xl md:text-2xl font-bold text-gray-600">${stats.total > 0 ? Math.round((stats.qualifies / stats.total) * 100) : 0}%</p></div>
            </div>
            ${!showClosed && canEdit && !isDataCollection ? `<div class="card p-4 mb-6"><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"><div><p class="text-sm font-medium text-gray-700">IDs d'accès au questionnaire</p><p class="text-xs text-gray-400">Codes pour permettre aux participants d'accéder</p></div><button onclick="toggleAccessIdsPanel('${study.folder}')" class="px-4 py-2 text-sm btn-primary rounded-lg w-full sm:w-auto">Gérer les IDs</button></div><div id="access-ids-panel-${study.folder}" class="hidden"><div class="flex flex-col sm:flex-row gap-2 mb-4"><input type="text" id="new-access-ids-${study.folder}" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="IDs séparés par virgules"><button onclick="addAccessIds('${study.folder}')" class="px-4 py-2 text-sm btn-primary rounded-lg">Ajouter</button></div><div id="access-ids-list-${study.folder}" class="max-h-40 overflow-y-auto"><p class="text-gray-400 text-sm text-center py-2">Chargement...</p></div></div></div>` : ''}
            ${!isDataCollection ? `<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-6">${(study.quotas || []).map(q => `<div class="card p-4"><p class="text-sm font-medium text-gray-700 mb-3">${q.titre}</p>${q.criteres.map(c => `<div class="flex items-center justify-between py-1.5"><span class="text-sm text-gray-500">${c.label}</span><span class="text-sm font-medium ${c.atteint ? 'text-blue-600' : 'text-amber-500'}">${c.actuel}${c.objectif ? '/' + c.objectif : ''}</span></div>`).join('')}</div>`).join('')}</div>` : ''}
            ${!isDataCollection ? `<div class="card p-4 mb-6"><p class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2"><svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>Filtres croisés</p><div class="filters-wrapper mb-4"><div class="filters-grid" id="filter-grid-${study.studyId}">${buildCrossFilters(study)}</div></div><div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-sidebar text-white rounded-lg p-3"><div class="flex items-center gap-2"><span class="text-2xl font-bold" id="result-count-${study.studyId}">${qualifies.length}</span><span class="text-sm opacity-80">résultats</span></div><div class="flex flex-wrap items-center gap-2 w-full sm:w-auto"><div id="active-filters-${study.studyId}" class="flex flex-wrap gap-1"></div><button onclick="resetFilters('${study.studyId}')" class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded text-sm transition flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Reset</button></div></div></div>` : ''}
            <div class="card overflow-hidden mb-6"><div class="px-4 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div class="flex items-center gap-2"><p class="font-medium text-gray-700">${isDataCollection ? 'Réponses complétées' : 'Participants qualifiés'}</p><span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">${qualifies.length}</span></div>${exportBtn}</div><div class="table-responsive"><table class="w-full min-w-[700px]" id="qualifies-table-${study.studyId}" data-study-type="${study.studyType || 'classic'}"><thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>${qualifiesHeaders}</tr></thead><tbody id="qualifies-tbody-${study.studyId}" class="divide-y divide-gray-50"></tbody></table></div><div id="qualifies-pagination-${study.studyId}" class="px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2"></div></div>
            <div class="card border-amber-200 overflow-hidden mb-6"><div class="px-4 py-3 border-b border-amber-200 bg-amber-50 flex items-center gap-2"><span class="text-amber-500">⏱</span><p class="font-medium text-amber-700">Participants en cours</p><span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">${(study.enCours || []).length}</span></div><div class="table-responsive"><table class="w-full min-w-[700px]" id="encours-table-${study.studyId}" data-study-type="${study.studyType || 'classic'}"><thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>${encoursHeaders}</tr></thead><tbody id="encours-tbody-${study.studyId}" class="divide-y divide-gray-50"></tbody></table></div><div id="encours-pagination-${study.studyId}" class="px-4 py-3 border-t border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-2"></div></div>
            <div class="card overflow-hidden mb-6"><div class="px-4 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div class="flex items-center gap-2"><p class="font-medium text-gray-700">${isDataCollection ? 'Abandons' : 'Participants refusés'}</p><span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">${refuses.length}</span></div><button onclick="exportStudy('${study.studyId}', 'refuses')" class="px-4 py-2 text-sm btn-secondary rounded-lg w-full sm:w-auto">Exporter ${isDataCollection ? 'abandons' : 'refusés'}</button></div><div class="table-responsive"><table class="w-full min-w-[700px]" id="refuses-table-${study.studyId}" data-study-type="${study.studyType || 'classic'}"><thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>${refusesHeaders}</tr></thead><tbody id="refuses-tbody-${study.studyId}" class="divide-y divide-gray-50"></tbody></table></div><div id="refuses-pagination-${study.studyId}" class="px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2"></div></div>`;
            document.getElementById('main-content').innerHTML = html;
            initPaginatedTables(study);
        }

        const ITEMS_PER_PAGE = 15;
        const paginationData = {};

        function initPaginatedTables(study) {
            ['qualifies', 'refuses', 'enCours'].forEach(type => {
                const data = study[type] || [];
                paginationData[`${study.studyId}_${type}`] = { data, currentPage: 1, totalPages: Math.ceil(data.length / ITEMS_PER_PAGE) || 1 };
                renderPaginatedTable(study.studyId, type);
            });
        }

        function renderPaginatedTable(studyId, type) {
            const key = `${studyId}_${type}`, pd = paginationData[key];
            if (!pd) return;
            const start = (pd.currentPage - 1) * ITEMS_PER_PAGE, end = start + ITEMS_PER_PAGE, pageData = pd.data.slice(start, end);
            // Convertir enCours -> encours pour matcher les IDs HTML
            const htmlType = type === 'enCours' ? 'encours' : type;
            const tbody = document.getElementById(`${htmlType}-tbody-${studyId}`);
            if (!tbody) return;

            // Détecter le type d'étude
            const table = document.getElementById(`${htmlType}-table-${studyId}`);
            const isDataCollection = table && table.dataset.studyType === 'data_collection';

            if (type === 'qualifies') {
                if (isDataCollection) {
                    // Rendu spécial pour data_collection avec métriques qualité détaillées
                    tbody.innerHTML = pageData.map((p, i) => {
                        const gi = start + i;
                        const qd = p.qualityData || {};

                        // Scores (structure plate depuis admin-data.php)
                        const overallScore = qd.overallQualityScore ?? '-';
                        const behaviorScore = qd.behaviorScore ?? '-';
                        const contentScore = qd.contentScore ?? '-';
                        const attentionScore = qd.attentionScore ?? '-';
                        const grade = qd.qualityGrade || 'F';
                        const textCount = qd.totalTextResponses ?? 0;
                        const flagsCount = qd.flags?.length ?? qd.flagsCount ?? 0;

                        // Couleurs selon les grades
                        const gradeColors = {
                            'A': 'bg-green-100 text-green-800',
                            'B': 'bg-blue-100 text-blue-800',
                            'C': 'bg-amber-100 text-amber-800',
                            'D': 'bg-orange-100 text-orange-800',
                            'F': 'bg-red-100 text-red-800'
                        };
                        const gradeClass = gradeColors[grade] || gradeColors['F'];

                        // Couleurs pour scores numériques
                        const scoreColor = (s) => s === '-' ? 'text-gray-400' : s >= 80 ? 'text-green-600' : s >= 60 ? 'text-amber-600' : 'text-red-600';

                        // Alertes tooltip
                        const flagsTooltip = flagsCount > 0 ? qd.flags.join(', ') : 'Aucune alerte';
                        const flagsClass = flagsCount === 0 ? 'text-green-600' : flagsCount <= 2 ? 'text-amber-600' : 'text-red-600';

                        return `<tr data-index="${gi}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-sidebar-active font-mono">P${(gi+1).toString().padStart(3,'0')}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs font-bold rounded ${gradeClass}">${grade}</span></td>
                            <td class="px-4 py-3 text-sm font-bold ${scoreColor(overallScore)}">${overallScore !== '-' ? overallScore + '%' : '-'}</td>
                            <td class="px-4 py-3 text-sm font-medium ${scoreColor(behaviorScore)}">${behaviorScore !== '-' ? behaviorScore + '%' : '-'}</td>
                            <td class="px-4 py-3 text-sm font-medium ${scoreColor(contentScore)}">${contentScore !== '-' ? contentScore + '%' : '-'}</td>
                            <td class="px-4 py-3 text-sm font-medium ${scoreColor(attentionScore)}">${attentionScore !== '-' ? attentionScore + '%' : '-'}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">${textCount}</td>
                            <td class="px-4 py-3 text-sm font-medium ${flagsClass}" title="${esc(flagsTooltip)}">${flagsCount}</td>
                            <td class="px-4 py-3"><div class="flex gap-1"><button onclick="showResponses('${studyId}', ${gi}, 'qualifies')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button>${canEdit ? `<button onclick="deleteParticipant('${studyId}', '${p.id}', 'qualifies')" class="px-3 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition">Suppr.</button>` : ''}</div></td>
                        </tr>`;
                    }).join('');
                } else {
                    // Rendu classique
                    tbody.innerHTML = pageData.map((p, i) => { const gi = start + i; return `<tr data-index="${gi}" class="hover:bg-gray-50"><td class="px-4 py-3 text-sm text-sidebar-active font-mono">${p.accessId || (gi + 1).toString().padStart(3, '0')}</td><td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(p.nom)}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.prenom)}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.email)}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.telephone || '')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.ville || '')}</td><td class="px-4 py-3 text-sm font-medium text-gray-700">${esc(p.horaire || '')}</td><td class="px-4 py-3"><div class="flex gap-1"><button onclick="showResponses('${studyId}', ${gi}, 'qualifies')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button>${canEdit ? `<button onclick="deleteParticipant('${studyId}', '${p.id}', 'qualifies')" class="px-3 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition">Suppr.</button>` : ''}</div></td></tr>`; }).join('');
                }
            } else if (type === 'enCours') {
                if (isDataCollection) {
                    tbody.innerHTML = pageData.map((p, i) => { 
                        const gi = start + i;
                        const qc = p.reponses ? Object.keys(p.reponses).length : 0;
                        const pseudo = p.nom !== 'N/A' ? p.nom : (p.email ? p.email.split('@')[0] : '-');
                        return `<tr data-index="${gi}" class="hover:bg-amber-50">
                            <td class="px-4 py-3 text-sm text-amber-600 font-mono">${p.id ? p.id.substring(0,8) : '-'}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(pseudo)}</td>
                            <td class="px-4 py-3 text-sm text-amber-600 font-medium">${qc} réponse(s)</td>
                            <td class="px-4 py-3 text-sm text-gray-500">${p.dateDebut && p.dateDebut !== 'N/A' ? new Date(p.dateDebut).toLocaleString('fr-FR') : '-'}</td>
                            <td class="px-4 py-3"><button onclick="showResponses('${studyId}', ${gi}, 'enCours')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button></td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = pageData.map((p, i) => { const gi = start + i, qc = p.reponses ? Object.keys(p.reponses).length : 0; return `<tr data-index="${gi}" class="hover:bg-amber-50"><td class="px-4 py-3 text-sm text-amber-600 font-mono">${p.accessId || '-'}</td><td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(p.nom || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.prenom || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.email || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.telephone || '-')}</td><td class="px-4 py-3 text-sm text-amber-600 font-medium">${qc} réponse(s)</td><td class="px-4 py-3 text-sm text-gray-500">${p.date ? new Date(p.date).toLocaleString('fr-FR') : '-'}</td><td class="px-4 py-3"><button onclick="showResponses('${studyId}', ${gi}, 'enCours')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button></td></tr>`; }).join('');
                }
            } else if (type === 'refuses') {
                if (isDataCollection) {
                    tbody.innerHTML = pageData.map((p, i) => { 
                        const gi = start + i;
                        const pseudo = p.nom !== 'N/A' ? p.nom : (p.email ? p.email.split('@')[0] : '-');
                        return `<tr data-index="${gi}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-400 font-mono">${p.id ? p.id.substring(0,8) : '-'}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(pseudo)}</td>
                            <td class="px-4 py-3 text-sm text-red-500">${(p.raisons||[]).join(', ') || 'Abandonné'}</td>
                            <td class="px-4 py-3"><button onclick="showResponses('${studyId}', ${gi}, 'refuses')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button></td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = pageData.map((p, i) => { const gi = start + i; return `<tr data-index="${gi}" class="hover:bg-gray-50"><td class="px-4 py-3 text-sm text-gray-400 font-mono">${p.accessId || '-'}</td><td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(p.nom || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.prenom || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.email || '-')}</td><td class="px-4 py-3 text-sm text-gray-600">${esc(p.telephone || '-')}</td><td class="px-4 py-3 text-sm text-red-500">${(p.raisons||[]).join(', ') || '-'}</td><td class="px-4 py-3"><div class="flex flex-col sm:flex-row gap-1"><button onclick="showResponses('${studyId}', ${gi}, 'refuses')" class="px-3 py-1 text-xs btn-secondary rounded">Voir</button>${canEdit ? `<button onclick="moveToQualified('${studyId}', '${p.id}')" class="px-3 py-1 text-xs text-green-600 hover:bg-green-50 rounded transition">Qualifier</button>` : ''}</div></td></tr>`; }).join('');
                }
            }

            const paginationDiv = document.getElementById(`${htmlType}-pagination-${studyId}`);
            if (paginationDiv) {
                if (pd.data.length <= ITEMS_PER_PAGE) { paginationDiv.innerHTML = `<span class="text-sm text-gray-500">${pd.data.length} élément(s)</span>`; }
                else {
                    let pagesHtml = '';
                    for (let i = 1; i <= pd.totalPages; i++) { pagesHtml += i === pd.currentPage ? `<button class="px-3 py-1 text-sm btn-primary rounded">${i}</button>` : `<button onclick="goToPage('${studyId}', '${type}', ${i})" class="px-3 py-1 text-sm btn-secondary rounded">${i}</button>`; }
                    paginationDiv.innerHTML = `<span class="text-sm text-gray-500">${start + 1}-${Math.min(end, pd.data.length)} sur ${pd.data.length}</span><div class="flex items-center gap-1 flex-wrap"><button onclick="goToPage('${studyId}', '${type}', ${pd.currentPage - 1})" ${pd.currentPage === 1 ? 'disabled' : ''} class="px-3 py-1 text-sm ${pd.currentPage === 1 ? 'text-gray-300' : 'btn-secondary'} rounded">←</button>${pagesHtml}<button onclick="goToPage('${studyId}', '${type}', ${pd.currentPage + 1})" ${pd.currentPage === pd.totalPages ? 'disabled' : ''} class="px-3 py-1 text-sm ${pd.currentPage === pd.totalPages ? 'text-gray-300' : 'btn-secondary'} rounded">→</button></div>`;
                }
            }
        }

        function goToPage(studyId, type, page) {
            const key = `${studyId}_${type}`, pd = paginationData[key];
            if (!pd || page < 1 || page > pd.totalPages) return;
            pd.currentPage = page;
            renderPaginatedTable(studyId, type);
        }

        let studyQuestionTitles = {};
        const studyQuestionData = {};
        
        async function loadStudyQuestions(folder) {
            if (studyQuestionData[folder]) return studyQuestionData[folder];
            try {
                const response = await fetch('../studies/' + folder + '/questions.js?v=' + Date.now());
                const jsContent = await response.text();
                const data = { titles: {}, options: {}, optionLabels: {}, order: [], fields: {} };
                
                // Parser le contenu JS pour extraire les questions
                const questionBlocks = jsContent.split(/\{\s*id:/);
                
                questionBlocks.forEach(block => {
                    if (!block.trim()) return;
                    
                    // Extraire l'ID
                    const idMatch = block.match(/^\s*['"]([^'"]+)['"]/);
                    if (!idMatch) return;
                    const qId = idMatch[1];
                    
                    // Ajouter à l'ordre
                    data.order.push(qId);
                    
                    // Extraire le titre (gérer les apostrophes échappées)
                    // Chercher title: '...' ou title: "..."
                    let title = null;
                    const titleMatch1 = block.match(/title:\s*'((?:[^'\\]|\\.)*)'/);
                    const titleMatch2 = block.match(/title:\s*"((?:[^"\\]|\\.)*)"/);
                    if (titleMatch1) title = titleMatch1[1].replace(/\\'/g, "'");
                    else if (titleMatch2) title = titleMatch2[1].replace(/\\"/g, '"');
                    
                    // Si pas de title, chercher question
                    if (!title) {
                        const questionMatch1 = block.match(/question:\s*'((?:[^'\\]|\\.)*)'/);
                        const questionMatch2 = block.match(/question:\s*"((?:[^"\\]|\\.)*)"/);
                        if (questionMatch1) title = questionMatch1[1].replace(/\\'/g, "'");
                        else if (questionMatch2) title = questionMatch2[1].replace(/\\"/g, '"');
                    }
                    
                    data.titles[qId] = title ? title.replace(/<[^>]*>/g, '').trim() : qId;
                    
                    // Extraire les fields pour double_text
                    const fieldsMatch = block.match(/fields:\s*\[([\s\S]*?)\]/);
                    if (fieldsMatch) {
                        const fieldsStr = fieldsMatch[1];
                        const fields = {};
                        // Gérer les apostrophes échappées dans les labels
                        const fieldRegex = /key:\s*['"]([^'"]+)['"][\s\S]*?label:\s*'((?:[^'\\]|\\.)*)'/g;
                        let fMatch;
                        while ((fMatch = fieldRegex.exec(fieldsStr)) !== null) {
                            fields[fMatch[1]] = fMatch[2].replace(/\\'/g, "'");
                        }
                        // Essayer aussi avec guillemets doubles
                        const fieldRegex2 = /key:\s*['"]([^'"]+)['"][\s\S]*?label:\s*"((?:[^"\\]|\\.)*)"/g;
                        while ((fMatch = fieldRegex2.exec(fieldsStr)) !== null) {
                            fields[fMatch[1]] = fMatch[2].replace(/\\"/g, '"');
                        }
                        if (Object.keys(fields).length > 0) {
                            data.fields[qId] = fields;
                        }
                    }
                    
                    // Extraire les options avec leurs labels
                    const optionsMatch = block.match(/options:\s*\[([\s\S]*?)\]/);
                    if (optionsMatch) {
                        const optionsStr = optionsMatch[1];
                        const opts = [];
                        const labels = {};
                        
                        // Chercher les paires value/label (gérer apostrophes échappées)
                        const pairRegex = /value:\s*['"]([^'"]+)['"][\s\S]*?label:\s*'((?:[^'\\]|\\.)*)'/g;
                        let match;
                        while ((match = pairRegex.exec(optionsStr)) !== null) {
                            opts.push(match[1]);
                            labels[match[1]] = match[2].replace(/\\'/g, "'");
                        }
                        
                        // Essayer aussi avec guillemets doubles pour label
                        if (opts.length === 0) {
                            const pairRegex2 = /value:\s*['"]([^'"]+)['"][\s\S]*?label:\s*"((?:[^"\\]|\\.)*)"/g;
                            while ((match = pairRegex2.exec(optionsStr)) !== null) {
                                opts.push(match[1]);
                                labels[match[1]] = match[2].replace(/\\"/g, '"');
                            }
                        }
                        
                        if (opts.length > 0 && opts.length <= 50) {
                            data.options[qId] = opts;
                            data.optionLabels[qId] = labels;
                        }
                    }
                });
                
                studyQuestionData[folder] = data;
                studyQuestionTitles[folder] = data.titles;
                return data;
            } catch (e) { 
                console.error('Erreur chargement questions:', e);
                return { titles: {}, options: {}, optionLabels: {}, order: [], fields: {} }; 
            }
        }

        async function initFiltersForStudy(study) {
            const data = await loadStudyQuestions(study.folder);
            const container = document.getElementById('filter-grid-' + study.studyId);
            if (container) container.innerHTML = buildCrossFiltersSync(study, data);
        }

        function buildCrossFilters(study) { setTimeout(() => initFiltersForStudy(study), 100); return '<p class="text-gray-400 text-sm">Chargement des filtres...</p>'; }

        function buildCrossFiltersSync(study, data) {
            const titles = data.titles || {};
            const questionOptions = data.options || {};
            
            // Construire la liste des questions à afficher (celles qui ont des options)
            const sortedQuestions = Object.keys(questionOptions).sort((a, b) => { 
                const numA = parseInt(a.replace(/\D/g, '')) || 0;
                const numB = parseInt(b.replace(/\D/g, '')) || 0; 
                return numA - numB || a.localeCompare(b); 
            });
            
            if (sortedQuestions.length === 0) return '<p class="text-gray-400 text-sm">Aucun filtre disponible</p>';
            
            let html = '';
            sortedQuestions.forEach(qId => { 
                const options = questionOptions[qId];
                if (!options || options.length === 0) return;
                
                const title = titles[qId] || qId;
                const shortTitle = title.length > 20 ? title.substring(0, 18) + '...' : title;
                const optionsHtml = options.map(o => `<label class="multi-select-option"><input type="checkbox" value="${esc(o)}" onchange="applyFilters('${study.studyId}')"><span>${esc(o.replace(/_/g, ' '))}</span></label>`).join('');
                
                html += `<div class="multi-select-container" data-study="${study.studyId}" data-question="${qId}">
                    <button type="button" class="multi-select-btn" onclick="toggleMultiSelect(this)" title="${esc(title)}">
                        <span class="truncate">${esc(shortTitle)}</span>
                        <svg class="arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="multi-select-dropdown">${optionsHtml}</div>
                </div>`; 
            });
            return html || '<p class="text-gray-400 text-sm">Aucun filtre disponible</p>';
        }

        function toggleMultiSelect(btn) {
            const dropdown = btn.nextElementSibling;
            const isOpen = dropdown.classList.contains('show');
            
            // Fermer tous les autres dropdowns ouverts
            document.querySelectorAll('.multi-select-dropdown.show').forEach(d => {
                d.classList.remove('show');
                d.previousElementSibling.classList.remove('open');
            });
            
            if (!isOpen) {
                dropdown.classList.add('show');
                btn.classList.add('open');
            }
        }
        
        // Fermer dropdown quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.multi-select-container')) {
                document.querySelectorAll('.multi-select-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                    d.previousElementSibling.classList.remove('open');
                });
            }
        });

        function applyFilters(studyId) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            const containers = document.querySelectorAll(`.multi-select-container[data-study="${studyId}"]`);
            const filters = {};
            const tags = [];
            
            containers.forEach(container => {
                const qId = container.dataset.question;
                const checkboxes = container.querySelectorAll('input[type="checkbox"]:checked');
                const btn = container.querySelector('.multi-select-btn');
                
                if (checkboxes.length > 0) {
                    filters[qId] = Array.from(checkboxes).map(cb => cb.value);
                    btn.classList.add('active');
                    // Ajouter badge avec le nombre
                    let badge = btn.querySelector('.multi-select-badge');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'multi-select-badge';
                        btn.insertBefore(badge, btn.querySelector('.arrow'));
                    }
                    badge.textContent = checkboxes.length;
                    // Ajouter tags pour affichage
                    checkboxes.forEach(cb => {
                        tags.push({ qId: qId, value: cb.value, label: cb.value.replace(/_/g, ' ') });
                    });
                } else {
                    btn.classList.remove('active');
                    const badge = btn.querySelector('.multi-select-badge');
                    if (badge) badge.remove();
                }
            });
            
            let count = 0;
            const qualifiesData = paginationData[`${studyId}_qualifies`]?.data || study.qualifies;
            qualifiesData.forEach((p, i) => {
                let match = true;
                Object.entries(filters).forEach(([qId, vals]) => { 
                    const a = p.reponses?.[qId]; 
                    if (!a) { match = false; return; } 
                    if (a.value !== undefined) { 
                        if (!vals.includes(String(a.value))) match = false; 
                    } else if (a.values && Array.isArray(a.values)) { 
                        // Pour les questions multi, vérifier si au moins une valeur sélectionnée est dans les réponses
                        const hasMatch = vals.some(v => a.values.map(String).includes(v));
                        if (!hasMatch) match = false;
                    } else { 
                        match = false; 
                    } 
                });
                const row = document.querySelector(`#qualifies-table-${studyId} tr[data-index="${i}"]`);
                if (row) row.classList.toggle('hidden-by-filter', !match);
                if (match) count++;
            });
            
            document.getElementById(`result-count-${studyId}`).textContent = count;
            
            // Afficher les tags des filtres actifs
            const tagsHtml = tags.map(t => 
                `<span class="filter-tag">
                    <span>${esc(t.label)}</span>
                    <span class="filter-tag-remove" onclick="removeFilter('${studyId}', '${t.qId}', '${esc(t.value)}')">✕</span>
                </span>`
            ).join('');
            document.getElementById(`active-filters-${studyId}`).innerHTML = tagsHtml;
        }
        
        function removeFilter(studyId, qId, value) {
            const container = document.querySelector(`.multi-select-container[data-study="${studyId}"][data-question="${qId}"]`);
            if (container) {
                const checkbox = container.querySelector(`input[type="checkbox"][value="${value}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                    applyFilters(studyId);
                }
            }
        }

        function resetFilters(studyId) {
            // Décocher toutes les checkboxes
            document.querySelectorAll(`.multi-select-container[data-study="${studyId}"] input[type="checkbox"]`).forEach(cb => cb.checked = false);
            // Retirer le style actif et les badges
            document.querySelectorAll(`.multi-select-container[data-study="${studyId}"] .multi-select-btn`).forEach(btn => {
                btn.classList.remove('active');
                const badge = btn.querySelector('.multi-select-badge');
                if (badge) badge.remove();
            });
            // Afficher toutes les lignes
            document.querySelectorAll(`#qualifies-table-${studyId} tr`).forEach(r => r.classList.remove('hidden-by-filter'));
            const study = allData.studies.find(s => s.studyId === studyId);
            document.getElementById(`result-count-${studyId}`).textContent = study ? study.qualifies.length : 0;
            document.getElementById(`active-filters-${studyId}`).innerHTML = '';
        }

        let questionTitles = {};
        let questionOrder = [];
        let questionLabels = {};
        let questionFields = {};
        async function showResponses(studyId, index, type) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            let list = type === 'qualifies' ? study.qualifies : type === 'refuses' ? study.refuses : type === 'enCours' ? study.enCours : study.qualifies;
            const p = list[index]; if (!p) return;
            currentEditData = { studyId, studyFolder: study.folder, participantId: p.id, type, participant: p, originalReponses: JSON.parse(JSON.stringify(p.reponses || {})) };
            isEditMode = false;
            const qData = studyQuestionData[study.folder] || await loadStudyQuestions(study.folder);
            questionTitles = qData.titles || {};
            questionOrder = qData.order || [];
            questionLabels = qData.optionLabels || {};
            questionFields = qData.fields || {};
            renderModal(false);
        }

        function renderModal(edit) {
            if (!canEdit) edit = false;
            const p = currentEditData.participant, r = p.reponses || {}, folder = currentEditData.studyFolder;
            let html = `<div class="mb-4 pb-4 border-b border-gray-100"><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3"><div><p class="font-semibold text-gray-800">${esc(p.prenom)} ${esc(p.nom)}</p><p class="text-sm text-gray-500">${esc(p.email)}</p></div>${canEdit ? `<button onclick="toggleEdit()" class="px-4 py-2 text-sm ${edit ? 'btn-secondary' : 'bg-amber-50 text-amber-600 hover:bg-amber-100'} rounded-lg transition">${edit ? 'Annuler' : 'Modifier'}</button>` : ''}</div>${edit ? `<div class="grid grid-cols-1 sm:grid-cols-2 gap-2"><input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="nom" value="${esc(p.nom||'')}" placeholder="Nom"><input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="prenom" value="${esc(p.prenom||'')}" placeholder="Prénom"><input type="email" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="email" value="${esc(p.email||'')}" placeholder="Email"><input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="telephone" value="${esc(p.telephone||'')}" placeholder="Téléphone"><input type="text" class="edit-sig sm:col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="adresse" value="${esc(p.adresse||'')}" placeholder="Adresse"><input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="codePostal" value="${esc(p.codePostal||'')}" placeholder="CP"><input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="ville" value="${esc(p.ville||'')}" placeholder="Ville"><input type="text" class="edit-sig sm:col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="horaire" value="${esc(p.horaire||'')}" placeholder="Horaire"></div>` : `<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm"><p><span class="text-gray-400">Adresse:</span> ${esc(p.adresse||'-')}</p><p><span class="text-gray-400">CP:</span> ${esc(p.codePostal||'-')}</p><p><span class="text-gray-400">Ville:</span> ${esc(p.ville||'-')}</p><p><span class="text-gray-400">Horaire:</span> ${esc(p.horaire||'-')}</p></div>`}</div><div class="mb-3"><p class="text-sm font-medium text-gray-700">Réponses au questionnaire</p></div><div class="space-y-2">`;
            // Trier les réponses selon l'ordre du questionnaire
            const responseKeys = Object.keys(r);
            const sortedKeys = questionOrder.length > 0 
                ? questionOrder.filter(qId => responseKeys.includes(qId))
                : responseKeys.sort((a, b) => {
                    const numA = parseInt(a.replace(/\D/g, '')) || 0;
                    const numB = parseInt(b.replace(/\D/g, '')) || 0;
                    return numA - numB;
                });
            responseKeys.forEach(k => { if (!sortedKeys.includes(k)) sortedKeys.push(k); });
            sortedKeys.forEach(qId => { const ans = r[qId]; if (!ans) return; const title = questionTitles[qId] || qId; if (edit) { const cv = getAnswerValue(ans); html += `<div class="py-2 border-b border-gray-50"><p class="text-xs text-gray-400 mb-1">${esc(title)}</p><input type="text" class="edit-response w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" data-qid="${qId}" value="${esc(cv)}" placeholder="Réponse"></div>`; } else { html += `<div class="flex flex-col sm:flex-row sm:justify-between py-2 border-b border-gray-50 gap-1"><span class="text-sm text-gray-500 flex-1">${esc(title)}</span><span class="text-sm sm:text-right text-gray-800 sm:ml-4">${formatAns(ans, folder, qId)}</span></div>`; } });
            html += '</div>';
            document.getElementById('modal-body').innerHTML = html;
            document.getElementById('modal-title').textContent = edit ? 'Modifier' : 'Détail';
            document.getElementById('modal-footer').classList.toggle('hidden', !edit);
            document.getElementById('responses-modal').classList.remove('hidden');
            document.getElementById('responses-modal').classList.add('flex');
        }

        function getAnswerValue(ans) {
            if (!ans) return '';
            if (ans.file || ans.filename) return '[Photo]';
            if (ans.value !== undefined) return String(ans.value).replace(/_/g, ' ');
            if (ans.values) { if (Array.isArray(ans.values)) return ans.values.join(', ').replace(/_/g, ' '); return Object.entries(ans.values).map(([k,v]) => k + ': ' + v).join(' | '); }
            if (ans.matrix) return Object.entries(ans.matrix).map(([k,v]) => k + ': ' + v).join(' | ');
            return JSON.stringify(ans);
        }

        function formatAns(ans, folder, qId) {
            if (!ans) return '-';
            
            // Photos
            if (ans.file && ans.file.filename) return `<a href="../api/photo.php?study=${folder}&file=${encodeURIComponent(ans.file.filename)}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition text-xs font-medium">📷 Voir photo</a>`;
            if (ans.filename) return `<a href="../api/photo.php?study=${folder}&file=${encodeURIComponent(ans.filename)}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition text-xs font-medium">📷 Voir photo</a>`;
            
            const labels = questionLabels[qId] || {};
            const fields = questionFields[qId] || {};
            
            // Valeur simple (single, number)
            if (ans.value !== undefined) {
                const val = String(ans.value);
                const label = labels[val] || val.replace(/_/g, ' ');
                return esc(label);
            }
            
            // Texte brut (pour les questions de type text)
            if (ans.text !== undefined) {
                return esc(String(ans.text));
            }
            
            // Valeurs multiples (multiple) ou double_text
            if (ans.values) {
                // Array = choix multiples
                if (Array.isArray(ans.values)) {
                    const formatted = ans.values.map(v => labels[v] || v.replace(/_/g, ' '));
                    return esc(formatted.join(', '));
                }
                // Object = double_text ou autre
                if (typeof ans.values === 'object') {
                    const parts = [];
                    Object.entries(ans.values).forEach(([k, v]) => {
                        const fieldLabel = fields[k] || k.replace(/_/g, ' ');
                        const value = String(v).replace(/_/g, ' ');
                        // Format: "Label : valeur"
                        parts.push(`<span class="text-gray-400">${esc(fieldLabel)} :</span> ${esc(value)}`);
                    });
                    return parts.join('<br>');
                }
            }
            
            // Matrix
            if (ans.matrix) {
                const parts = Object.entries(ans.matrix).map(([k,v]) => `${k.replace(/_/g, ' ')}: ${v}`);
                return esc(parts.join(', '));
            }
            
            // Extra texts
            if (ans.extraTexts && Object.keys(ans.extraTexts).length > 0) {
                const parts = Object.entries(ans.extraTexts).map(([k,v]) => v).filter(v => v);
                return esc(parts.join(', '));
            }
            
            // Fallback: si c'est une string directe
            if (typeof ans === 'string') {
                return esc(ans);
            }
            
            return esc(JSON.stringify(ans));
        }

        function toggleEdit() { isEditMode = !isEditMode; renderModal(isEditMode); }

        async function saveChanges() {
            if (!currentEditData) return;
            const updatedSig = {}, updatedReponses = JSON.parse(JSON.stringify(currentEditData.originalReponses));
            document.querySelectorAll('.edit-sig').forEach(inp => updatedSig[inp.dataset.field] = inp.value);
            document.querySelectorAll('.edit-response').forEach(inp => { const qId = inp.dataset.qid, nv = inp.value.trim(); if (updatedReponses[qId]) { if (updatedReponses[qId].value !== undefined) updatedReponses[qId].value = nv; else if (updatedReponses[qId].values && Array.isArray(updatedReponses[qId].values)) updatedReponses[qId].values = nv.split(',').map(v => v.trim()); } });
            try {
                const res = await securePost('../api/admin-data.php', { action: 'update_participant', studyId: currentEditData.studyId, participantId: currentEditData.participantId, type: currentEditData.type, signaletics: updatedSig, reponses: updatedReponses });
                const data = await res.json();
                if (data.success) { closeModal(); loadData(); } else alert('Erreur: ' + (data.error || 'Échec'));
            } catch (e) { alert('Erreur de connexion'); }
        }

        function closeModal() { document.getElementById('responses-modal').classList.add('hidden'); document.getElementById('responses-modal').classList.remove('flex'); currentEditData = null; isEditMode = false; }
        function openModal() { document.getElementById('responses-modal').classList.remove('hidden'); document.getElementById('responses-modal').classList.add('flex'); }
        function esc(str) { if (str === null || str === undefined) return ''; return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

        async function exportStudy(studyId, type) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            const data = type === 'qualifies' ? study.qualifies : study.refuses; if (!data || data.length === 0) { alert('Aucune donnée à exporter'); return; }
            
            function formatExportDate(ds) { if (!ds) return ''; try { const d = new Date(ds); if (isNaN(d.getTime())) return ds; return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`; } catch (e) { return ds; } }
            
            // Charger les données des questions
            const qData = await loadStudyQuestions(study.folder);
            const titles = qData.titles || {};
            const optLabels = qData.optionLabels || {};
            const fields = qData.fields || {};
            
            // Collecter tous les IDs de questions
            const allQIds = new Set(); 
            data.forEach(p => { if (p.reponses) Object.keys(p.reponses).forEach(q => allQIds.add(q)); });
            
            // Trier selon l'ordre du questionnaire ou par numéro
            const qIds = qData.order && qData.order.length > 0 
                ? qData.order.filter(qId => allQIds.has(qId))
                : Array.from(allQIds).sort((a, b) => { 
                    const na = parseInt(a.replace(/\D/g, '')) || 0, nb = parseInt(b.replace(/\D/g, '')) || 0; 
                    return na !== nb ? na - nb : a.localeCompare(b); 
                });
            
            // Ajouter les questions non triées
            allQIds.forEach(qId => { if (!qIds.includes(qId)) qIds.push(qId); });
            
            // Construire les en-têtes
            let headers = ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Adresse', 'CP', 'Ville', 'Horaire', 'Date']; 
            if (type === 'refuses') headers.push('Raisons'); 
            qIds.forEach(qId => headers.push(titles[qId] || qId));
            
            // Fonction pour formater une réponse pour l'export
            function formatExportAnswer(a, qId) {
                if (!a) return '';
                if (a.file) return a.file.filename || 'Photo';
                
                const labels = optLabels[qId] || {};
                const flds = fields[qId] || {};
                
                // Valeur simple (single, number, text)
                if (a.value !== undefined) {
                    const val = String(a.value);
                    return labels[val] || val.replace(/_/g, ' ');
                }
                
                // Texte brut (pour les questions de type text)
                if (a.text !== undefined) {
                    return String(a.text);
                }
                
                // Valeurs multiples ou double_text
                if (a.values) {
                    if (Array.isArray(a.values)) {
                        // Choix multiples - utiliser les labels
                        return a.values.map(v => labels[v] || v.replace(/_/g, ' ')).join(', ');
                    }
                    if (typeof a.values === 'object') {
                        // Double text - formater avec labels des champs
                        return Object.entries(a.values)
                            .map(([k, v]) => {
                                const fieldLabel = flds[k] || k.replace(/_/g, ' ');
                                return fieldLabel + ' : ' + String(v).replace(/_/g, ' ');
                            })
                            .join(' | ');
                    }
                }
                
                // Fallback: si c'est une string directe
                if (typeof a === 'string') {
                    return a;
                }
                
                return JSON.stringify(a);
            }
            
            // Construire les lignes
            const rows = data.map(p => { 
                let row = [p.accessId||'', p.nom||'', p.prenom||'', p.email||'', p.telephone||'', p.adresse||'', p.codePostal||'', p.ville||'', p.horaire||'', formatExportDate(p.date)]; 
                if (type === 'refuses') row.push((p.raisons||[]).join(' | ')); 
                qIds.forEach(qId => { 
                    row.push(formatExportAnswer(p.reponses?.[qId], qId)); 
                }); 
                return row; 
            });
            
            // Envoyer au serveur
            const form = document.createElement('form'); 
            form.method = 'POST'; 
            form.action = '../api/export-xlsx.php'; 
            form.innerHTML = '<input type="hidden" name="data" value=\'' + JSON.stringify({headers, rows}).replace(/'/g, '&#39;') + '\'><input type="hidden" name="filename" value="' + studyId + '_' + type + '"><input type="hidden" name="studyId" value="' + studyId + '">'; 
            document.body.appendChild(form); 
            form.submit(); 
            document.body.removeChild(form);
        }

        function exportJSONL(studyId) {
            // Utiliser l'export universel qui fonctionne avec toutes les études
            window.open('../api/export-jsonl-universal.php?study=' + encodeURIComponent(studyId), '_blank');
        }

        function copyStudyLink(folder) {
            const link = window.location.origin + window.location.pathname.replace('/admin/dashboard.php', '') + '/studies/' + folder + '/';
            navigator.clipboard.writeText(link).then(() => { const btn = event.target.closest('button'), ot = btn.innerHTML; btn.innerHTML = '✓ Copié !'; btn.classList.add('text-green-600'); setTimeout(() => { btn.innerHTML = ot; btn.classList.remove('text-green-600'); }, 2000); }).catch(() => { const input = document.createElement('input'); input.value = link; document.body.appendChild(input); input.select(); document.execCommand('copy'); document.body.removeChild(input); alert('Lien copié : ' + link); });
        }

        function toggleAccessIdsPanel(folder) { const p = document.getElementById('access-ids-panel-' + folder); if (p.classList.contains('hidden')) { p.classList.remove('hidden'); loadAccessIds(folder); } else p.classList.add('hidden'); }

        async function loadAccessIds(folder) {
            const listEl = document.getElementById('access-ids-list-' + folder);
            try {
                const res = await fetch('../api/admin-data.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'get_access_ids', studyFolder: folder }) });
                const data = await res.json();
                if (data.success) { const ids = data.ids || [], used = data.usedIds || []; if (ids.length === 0) listEl.innerHTML = '<p class="text-gray-400 text-sm text-center py-2">Aucun ID configuré</p>'; else listEl.innerHTML = '<div class="flex flex-wrap gap-2">' + ids.map(id => { const isUsed = used.includes(id); return `<div class="flex items-center gap-1 px-2 py-1 ${isUsed ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'} rounded text-sm"><span class="font-mono">${esc(id)}</span>${isUsed ? '<span class="text-xs">(utilisé)</span>' : `<button onclick="removeAccessId('${folder}', '${id}')" class="ml-1 text-red-500 hover:text-red-700">×</button>`}</div>`; }).join('') + '</div>'; }
            } catch (e) { listEl.innerHTML = '<p class="text-red-500 text-sm text-center py-2">Erreur de chargement</p>'; }
        }

        async function addAccessIds(folder) { const input = document.getElementById('new-access-ids-' + folder), newIds = input.value.split(/[,\n]+/).map(id => id.trim()).filter(id => id); if (newIds.length === 0) return; try { const res = await securePost('../api/admin-data.php', { action: 'add_access_ids', studyFolder: folder, ids: newIds }); const data = await res.json(); if (data.success) { input.value = ''; loadAccessIds(folder); } else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur de connexion'); } }
        async function removeAccessId(folder, id) { if (!confirm('Supprimer l\'ID "' + id + '" ?')) return; try { const res = await securePost('../api/admin-data.php', { action: 'delete_access_id', studyFolder: folder, accessCode: id }); const data = await res.json(); if (data.success) { loadAccessIds(folder); showToast('✓ ID supprimé'); } else { alert('Erreur: ' + (data.error || 'Échec')); } } catch (e) { alert('Erreur de connexion'); } }
        async function closeStudy(folder) { if (!confirm('Terminer cette étude ? Elle sera archivée.')) return; try { const res = await fetch('../api/study-status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ folder: folder, status: 'closed' }) }); const data = await res.json(); if (data.success) { loadData(); switchTab('closed'); } else { alert('Erreur: ' + (data.error || 'Échec')); } } catch (e) { alert('Erreur de connexion'); } }
        async function reopenStudy(folder) { if (!confirm('Réouvrir cette étude ?')) return; try { const res = await fetch('../api/study-status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ folder: folder, status: 'active' }) }); const data = await res.json(); if (data.success) { loadData(); switchTab('studies'); } else { alert('Erreur: ' + (data.error || 'Échec')); } } catch (e) { alert('Erreur de connexion'); } }
        async function deleteStudy(folder, studyName) { 
            if (!confirm('⚠️ ATTENTION ⚠️\n\nVous allez supprimer définitivement l\'étude :\n"' + studyName + '"\n\nCette action supprimera :\n- Toutes les réponses des participants\n- Tous les fichiers uploadés\n- Toutes les données associées\n\nCette action est IRRÉVERSIBLE !\n\nContinuer ?')) return; 
            const confirmText = prompt('Pour confirmer, tapez le nom de l\'étude :\n' + studyName);
            if (confirmText !== studyName) { alert('Le nom ne correspond pas. Suppression annulée.'); return; }
            try { 
                const res = await fetch('../api/study-status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ folder: folder, action: 'delete' }) }); 
                const data = await res.json(); 
                if (data.success) { alert('✓ Étude supprimée définitivement'); loadData(); switchTab('closed'); } 
                else { alert('Erreur: ' + (data.error || 'Échec de la suppression')); } 
            } catch (e) { alert('Erreur de connexion'); } 
        }
        async function deleteParticipant(studyId, participantId, type) { if (!confirm('Supprimer ce participant ?')) return; try { const res = await securePost('../api/admin-data.php', { action: 'delete_participant', studyId, participantId, type }); const data = await res.json(); if (data.success) loadData(); } catch (e) { alert('Erreur'); } }
        async function moveToQualified(studyId, participantId) { if (!confirm('Déplacer ce participant vers les qualifiés ?')) return; try { const res = await securePost('../api/admin-data.php', { action: 'move_to_qualified', studyId, participantId }); const data = await res.json(); if (data.success) loadData(); else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur'); } }

        let previewQuestions = [], previewIndex = 0;
        async function previewStudy(folder) {
            try {
                const res = await fetch('../studies/' + folder + '/questions.js?v=' + Date.now()), js = await res.text();
                // Chercher questions: [...] dans STUDY_CONFIG ou const questions = [...]
                let match = js.match(/questions:\s*\[([\s\S]*?)\]\s*\};?\s*$/);
                if (!match) match = js.match(/const\s+questions\s*=\s*(\[[\s\S]*?\]);/);
                if (!match) { alert('Format de questions non reconnu'); return; }
                // Reconstruire le tableau
                const questionsStr = match[0].includes('questions:') ? '[' + match[1] + ']' : match[1];
                try { previewQuestions = eval(questionsStr); } catch(e) { 
                    // Fallback: exécuter tout le fichier et récupérer STUDY_CONFIG
                    eval(js);
                    if (typeof STUDY_CONFIG !== 'undefined' && STUDY_CONFIG.questions) {
                        previewQuestions = STUDY_CONFIG.questions;
                    } else { alert('Format de questions non reconnu'); return; }
                }
                previewIndex = 0;
                document.getElementById('preview-title').textContent = 'Prévisualisation';
                document.getElementById('preview-subtitle').textContent = folder;
                document.getElementById('preview-jump').innerHTML = previewQuestions.map((q, i) => `<option value="${i}">${i + 1}. ${q.id}</option>`).join('');
                renderPreviewQuestion();
                document.getElementById('preview-modal').classList.remove('hidden');
                document.getElementById('preview-modal').classList.add('flex');
            } catch (e) { alert('Erreur de chargement'); }
        }

        function renderPreviewQuestion() {
            const q = previewQuestions[previewIndex]; if (!q) return;
            document.getElementById('preview-counter').textContent = `${previewIndex + 1} / ${previewQuestions.length}`;
            document.getElementById('preview-jump').value = previewIndex;
            let html = `<div class="mb-4"><span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded mb-2">${q.type}</span><p class="text-xs text-gray-400 mb-1">ID: ${q.id}</p><h3 class="text-lg font-medium text-gray-800">${q.title || q.question || ''}</h3></div>`;
            if (q.options) { html += '<div class="space-y-2">'; q.options.forEach(opt => { const label = typeof opt === 'string' ? opt : opt.label; html += `<div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">${label}</div>`; }); html += '</div>'; }
            if (q.image) html += `<div class="mt-4"><img src="../studies/${document.getElementById('preview-subtitle').textContent}/${q.image}" class="max-w-full rounded-lg shadow"></div>`;
            document.getElementById('preview-body').innerHTML = html;
            document.getElementById('preview-prev').disabled = previewIndex === 0;
            document.getElementById('preview-prev').classList.toggle('opacity-50', previewIndex === 0);
            document.getElementById('preview-next').disabled = previewIndex === previewQuestions.length - 1;
            document.getElementById('preview-next').classList.toggle('opacity-50', previewIndex === previewQuestions.length - 1);
            document.getElementById('preview-dots').innerHTML = previewQuestions.length <= 15 ? previewQuestions.map((_, i) => `<button onclick="goToPreviewQuestion(${i})" class="w-2 h-2 rounded-full ${i === previewIndex ? 'bg-purple-600' : 'bg-gray-300'} transition"></button>`).join('') : '';
        }

        function prevQuestion() { if (previewIndex > 0) { previewIndex--; renderPreviewQuestion(); } }
        function nextQuestion() { if (previewIndex < previewQuestions.length - 1) { previewIndex++; renderPreviewQuestion(); } }
        function jumpToQuestion() { previewIndex = parseInt(document.getElementById('preview-jump').value); renderPreviewQuestion(); }
        function goToPreviewQuestion(index) { previewIndex = index; renderPreviewQuestion(); }
        function closePreview() { document.getElementById('preview-modal').classList.add('hidden'); document.getElementById('preview-modal').classList.remove('flex'); }

        let usersData = [], studiesList = [];
        let logsData = '';
        async function loadAccounts() { 
            try { 
                const res = await fetch('../api/users-management.php?action=list'), data = await res.json(); 
                if (data.success) { usersData = data.users || []; studiesList = data.studies || []; }
                renderAccountsContent();
                loadLogs();
            } catch (e) { document.getElementById('main-content').innerHTML = '<div class="text-center text-red-500 py-8">Erreur de chargement</div>'; } 
        }
        
        async function loadLogs() {
            try {
                const res = await fetch('../api/admin-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_logs' })
                });
                const data = await res.json();
                if (data.success && data.logs) {
                    const logsHtml = data.logs.split('\n').filter(l => l.trim()).reverse().slice(0, 50).map(line => 
                        `<div class="py-1 border-b border-gray-800">${esc(line)}</div>`
                    ).join('');
                    document.getElementById('logs-container').innerHTML = logsHtml || '<div class="text-gray-500">Aucun log disponible</div>';
                }
            } catch (e) {
                document.getElementById('logs-container').innerHTML = '<div class="text-red-400">Erreur de chargement des logs</div>';
            }
        }
        function renderAccounts() { document.getElementById('main-content').innerHTML = '<div class="flex items-center justify-center h-64"><div class="animate-spin w-8 h-8 border-4 border-sidebar-active border-t-transparent rounded-full"></div></div>'; loadAccounts(); }

        function renderAccountsContent() {
            const users = usersData, isSuperAdmin = userRole === 'super_admin', isAdmin = userRole === 'admin', canCreateUsers = isSuperAdmin || isAdmin, currentUserId = '<?= $userId ?>';
            let html = `<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6"><div><h2 class="text-xl font-semibold text-gray-800">Gestion des comptes</h2><p class="text-sm text-gray-500">${users.length} compte(s) au total</p></div>${canCreateUsers ? `<button onclick="showCreateUserModal()" class="px-4 py-2 btn-primary rounded-lg text-sm font-medium w-full sm:w-auto">+ Nouveau compte</button>` : ''}</div><div class="card"><div class="px-4 md:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"><div class="flex items-center gap-3"><div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold">U</div><div><h3 class="font-semibold text-gray-800">Comptes utilisateurs</h3><p class="text-sm text-gray-500">${users.length} compte(s)</p></div></div><button onclick="loadAccounts()" class="p-2 btn-secondary rounded-lg" title="Actualiser">↻</button></div><div class="p-4 md:p-6">`;
            if (users.length === 0) { html += `<div class="text-center py-8 text-gray-400"><p>Aucun compte</p></div>`; }
            else {
                html += `<div class="table-responsive"><table class="w-full min-w-[600px]"><thead><tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><th class="pb-3">Utilisateur</th><th class="pb-3">Identifiant</th><th class="pb-3">Rôle</th><th class="pb-3">Dernière connexion</th><th class="pb-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-gray-100">`;
                users.forEach(u => {
                    const isSU = u.role === 'super_admin', isA = u.role === 'admin', isU = u.role === 'user';
                    let roleClass, roleLabel;
                    if (isSU) { roleClass = 'bg-purple-100 text-purple-700'; roleLabel = 'Super Admin'; } else if (isA) { roleClass = 'bg-blue-100 text-blue-700'; roleLabel = 'Admin'; } else { roleClass = 'bg-gray-100 text-gray-700'; roleLabel = 'Utilisateur'; }
                    const canE = isSuperAdmin, canD = isSuperAdmin && u.id !== currentUserId, canCR = isSuperAdmin && u.id !== currentUserId, canMS = isSuperAdmin && isU;
                    const sc = u.allowed_studies?.includes('*') ? 'Toutes' : (u.allowed_studies?.length || 0);
                    const initials = u.display_name.charAt(0).toUpperCase();
                    const bgColors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-amber-500', 'bg-red-500'], bgC = bgColors[u.display_name.charCodeAt(0) % bgColors.length];
                    html += `<tr class="hover:bg-gray-50"><td class="py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 ${bgC} rounded-full flex items-center justify-center text-sm font-medium text-white">${initials}</div><div><span class="font-medium text-gray-800">${esc(u.display_name)}</span>${isU ? `<p class="text-xs text-gray-400">${sc} étude(s)</p>` : ''}</div></div></td><td class="py-3 text-sm text-gray-600">${esc(u.username)}</td><td class="py-3">${canCR ? `<select onchange="changeUserRole('${u.id}', this.value)" class="filter-select px-2 py-1 text-xs font-medium rounded border border-gray-200 bg-white"><option value="admin" ${isA ? 'selected' : ''}>Admin</option><option value="user" ${isU ? 'selected' : ''}>Utilisateur</option></select>` : `<span class="px-2 py-1 text-xs font-medium rounded ${roleClass}">${roleLabel}</span>`}</td><td class="py-3 text-sm text-gray-500">${u.last_login || 'Jamais'}</td><td class="py-3 text-right"><div class="flex items-center justify-end gap-1 flex-wrap">${canMS ? `<button onclick="showStudiesModal('${u.id}', '${esc(u.display_name)}')" class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded transition">Études</button>` : ''}${canE ? `<button onclick="showEditUserModal('${u.id}')" class="px-3 py-1 text-xs btn-secondary rounded">Modifier</button>` : ''}${canD ? `<button onclick="deleteUser('${u.id}', '${esc(u.display_name)}')" class="px-3 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition">Suppr.</button>` : ''}</div></td></tr>`;
                });
                html += `</tbody></table></div>`;
            }
            html += `</div></div>`;
            
            // Section des logs (style terminal)
            html += `<div class="card mt-6"><div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3"><div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 font-bold">L</div><div><h3 class="font-semibold text-gray-800">Journal des connexions</h3><p class="text-sm text-gray-500">Historique des activités</p></div></div><div class="p-4 md:p-6"><div id="logs-container" class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-300 max-h-64 overflow-y-auto scrollbar-thin">Chargement des logs...</div></div></div>`;
            
            document.getElementById('main-content').innerHTML = html;
        }

        function showCreateUserModal() {
            if (userRole !== 'super_admin' && userRole !== 'admin') { alert('Accès non autorisé'); return; }
            const isSuperAdmin = userRole === 'super_admin';
            document.getElementById('modal-title').textContent = 'Créer un compte';
            document.getElementById('modal-body').innerHTML = `<div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'affichage</label>
                    <input type="text" id="new-display-name" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" placeholder="Jean Dupont">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                    <input type="text" id="new-username" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" placeholder="jean.dupont">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="new-password" class="w-full px-3 py-2 pr-10 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" placeholder="Min. 6 caractères">
                        <button type="button" onclick="togglePwdVisibility('new-password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600" title="Afficher/Masquer">
                            <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                    <div class="relative">
                        <input type="password" id="new-password-confirm" class="w-full px-3 py-2 pr-10 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" placeholder="Retapez le mot de passe">
                        <button type="button" onclick="togglePwdVisibility('new-password-confirm', this)" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600" title="Afficher/Masquer">
                            <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    <p id="pwd-match-error" class="text-xs text-red-500 mt-1 hidden">Les mots de passe ne correspondent pas</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                    <select id="new-role" class="filter-select w-full px-3 py-2 border border-gray-200 rounded-lg bg-white focus:border-teal-400 outline-none">
                        ${isSuperAdmin ? '<option value="super_admin">Super Admin</option><option value="admin">Admin</option>' : ''}
                        <option value="user" selected>Utilisateur</option>
                    </select>
                </div>
            </div>`;
            document.getElementById('modal-footer').classList.remove('hidden');
            document.getElementById('modal-footer').innerHTML = `<button onclick="closeModal()" class="px-4 py-2 text-sm btn-secondary rounded-lg">Annuler</button><button onclick="createUser()" class="px-4 py-2 text-sm btn-primary rounded-lg">Créer</button>`;
            openModal();
        }
        
        function togglePwdVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const eyeShow = btn.querySelector('.eye-show');
            const eyeHide = btn.querySelector('.eye-hide');
            if (input.type === 'password') {
                input.type = 'text';
                eyeShow.classList.add('hidden');
                eyeHide.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeShow.classList.remove('hidden');
                eyeHide.classList.add('hidden');
            }
        }

        async function createUser() { 
            const dn = document.getElementById('new-display-name').value.trim();
            const un = document.getElementById('new-username').value.trim();
            const pw = document.getElementById('new-password').value;
            const pwConfirm = document.getElementById('new-password-confirm').value;
            const role = document.getElementById('new-role').value;
            const errorEl = document.getElementById('pwd-match-error');
            
            if (!dn || !un || !pw) { alert('Veuillez remplir tous les champs'); return; }
            if (pw.length < 6) { alert('Le mot de passe doit faire au moins 6 caractères'); return; }
            
            if (pw !== pwConfirm) {
                errorEl.classList.remove('hidden');
                document.getElementById('new-password-confirm').classList.add('border-red-400');
                return;
            }
            errorEl.classList.add('hidden');
            document.getElementById('new-password-confirm').classList.remove('border-red-400');
            
            try {
                const res = await securePostForm('../api/users-management.php', `action=create&username=${encodeURIComponent(un)}&password=${encodeURIComponent(pw)}&display_name=${encodeURIComponent(dn)}&role=${encodeURIComponent(role)}`);
                const data = await res.json();
                if (data.success) { closeModal(); loadAccounts(); showToast('✓ Compte créé !'); }
                else alert('Erreur: ' + (data.error || 'Échec'));
            } catch (e) {
                console.error('Erreur création compte:', e);
                alert('Erreur de connexion au serveur');
            } 
        }
        function showEditUserModal(userId) { 
            const user = usersData.find(u => u.id === userId); 
            if (!user) return; 
            document.getElementById('modal-title').textContent = 'Modifier le compte'; 
            document.getElementById('modal-body').innerHTML = `<div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'affichage</label>
                    <input type="text" id="edit-display-name" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" value="${esc(user.display_name)}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe <span class="text-gray-400 font-normal">(laisser vide pour ne pas changer)</span></label>
                    <div class="relative">
                        <input type="password" id="edit-password" class="w-full px-3 py-2 pr-10 border border-gray-200 rounded-lg focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" placeholder="Min. 6 caractères">
                        <button type="button" onclick="togglePwdVisibility('edit-password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600" title="Afficher/Masquer">
                            <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>
            </div>`; 
            document.getElementById('modal-footer').classList.remove('hidden'); 
            document.getElementById('modal-footer').innerHTML = `<button onclick="closeModal()" class="px-4 py-2 text-sm btn-secondary rounded-lg">Annuler</button><button onclick="updateUser('${userId}')" class="px-4 py-2 text-sm btn-primary rounded-lg">Enregistrer</button>`; 
            openModal(); 
        }
        async function updateUser(userId) { const dn = document.getElementById('edit-display-name').value.trim(), pw = document.getElementById('edit-password').value; if (!dn) { alert('Le nom d\'affichage est requis'); return; } if (pw && pw.length < 6) { alert('Le mot de passe doit faire au moins 6 caractères'); return; } let body = `action=update&user_id=${encodeURIComponent(userId)}&display_name=${encodeURIComponent(dn)}`; if (pw) body += `&password=${encodeURIComponent(pw)}`; try { const res = await securePostForm('../api/users-management.php', body); const data = await res.json(); if (data.success) { closeModal(); loadAccounts(); } else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur de connexion'); } }
        async function changeUserRole(userId, newRole) { try { const res = await securePostForm('../api/users-management.php', `action=update&user_id=${encodeURIComponent(userId)}&role=${encodeURIComponent(newRole)}`); const data = await res.json(); if (data.success) loadAccounts(); else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur de connexion'); } }
        async function deleteUser(userId, displayName) { if (!confirm(`Supprimer le compte "${displayName}" ?`)) return; try { const res = await securePostForm('../api/users-management.php', `action=delete&user_id=${encodeURIComponent(userId)}`); const data = await res.json(); if (data.success) loadAccounts(); else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur de connexion'); } }
        function showStudiesModal(userId, displayName) { const user = usersData.find(u => u.id === userId); if (!user) return; const us = user.allowed_studies || [], hasAll = us.includes('*'); let studiesHtml = `<div class="mb-4"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="all-studies" ${hasAll ? 'checked' : ''} onchange="toggleAllStudies(this)" class="w-4 h-4 rounded border-gray-300"><span class="font-medium">Toutes les études (actuelles et futures)</span></label></div><div id="studies-list" class="${hasAll ? 'opacity-50 pointer-events-none' : ''}"><p class="text-sm text-gray-500 mb-2">Ou sélectionnez des études spécifiques :</p><div class="space-y-2 max-h-64 overflow-y-auto">`; studiesList.forEach(s => { const isChecked = hasAll || us.includes(s.id); studiesHtml += `<label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer"><input type="checkbox" class="study-checkbox w-4 h-4 rounded border-gray-300" value="${esc(s.id)}" ${isChecked ? 'checked' : ''}><span>${esc(s.name)}</span><span class="text-xs text-gray-400">(${s.status})</span></label>`; }); studiesHtml += `</div></div>`; document.getElementById('modal-title').textContent = `Études autorisées - ${displayName}`; document.getElementById('modal-body').innerHTML = studiesHtml; document.getElementById('modal-footer').classList.remove('hidden'); document.getElementById('modal-footer').innerHTML = `<button onclick="closeModal()" class="px-4 py-2 text-sm btn-secondary rounded-lg">Annuler</button><button onclick="saveUserStudies('${userId}')" class="px-4 py-2 text-sm btn-primary rounded-lg">Enregistrer</button>`; openModal(); }
        function toggleAllStudies(checkbox) { const list = document.getElementById('studies-list'); if (checkbox.checked) list.classList.add('opacity-50', 'pointer-events-none'); else list.classList.remove('opacity-50', 'pointer-events-none'); }
        async function saveUserStudies(userId) { const allStudies = document.getElementById('all-studies').checked; let studies = []; if (allStudies) studies = ['*']; else document.querySelectorAll('.study-checkbox:checked').forEach(cb => studies.push(cb.value)); try { const res = await securePostForm('../api/users-management.php', `action=update&user_id=${encodeURIComponent(userId)}&allowed_studies=${encodeURIComponent(JSON.stringify(studies))}`); const data = await res.json(); if (data.success) { closeModal(); loadAccounts(); } else alert('Erreur: ' + (data.error || 'Échec')); } catch (e) { alert('Erreur de connexion'); } }

        document.getElementById('preview-modal').addEventListener('click', e => { if (e.target.id === 'preview-modal') closePreview(); });
        document.getElementById('responses-modal').addEventListener('click', e => { if (e.target.id === 'responses-modal') closeModal(); });
        document.getElementById('study-builder-modal')?.addEventListener('click', e => { if (e.target.id === 'study-builder-modal') closeStudyBuilder(); });
        document.getElementById('question-editor-modal')?.addEventListener('click', e => { if (e.target.id === 'question-editor-modal') closeQuestionEditor(); });
        switchTab('dashboard');
        loadData();
        setInterval(() => { if (currentTab === 'dashboard' || (currentTab === 'studies' && !currentStudyId) || (currentTab === 'dataia' && !currentStudyId) || (currentTab === 'closed' && !currentStudyId)) loadData(); }, 30000);
    </script>
    
    <?php if ($adminRole === 'admin' || $adminRole === 'super_admin'): ?>
    <?php include 'study-builder.php'; ?>
    <?php endif; ?>
</body>
</html>