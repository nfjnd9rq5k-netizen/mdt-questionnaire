<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        'sidebar-active': '#3b82f6'
                    }
                }
            }
        }
    </script>
    <style>
        tr.hidden-by-filter { display: none; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-52 bg-sidebar flex flex-col">
            <div class="p-4 flex items-center gap-3">
                <div class="w-9 h-9 bg-sidebar-active rounded-lg flex items-center justify-center text-white font-bold">M</div>
                <div>
                    <div class="text-white font-semibold text-sm">Administration</div>
                    <div class="text-white/60 text-xs">Maison du Test</div>
                </div>
            </div>
            
            <nav class="flex-1 px-3 py-4 space-y-1">
                <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Tableau de bord
                </button>
                <button onclick="switchTab('studies')" id="nav-studies" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Études
                </button>
                <button onclick="switchTab('closed')" id="nav-closed" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Archives
                </button>
            </nav>
            
            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-white text-sm font-medium truncate">Admin MDT</div>
                        <div class="text-white/50 text-xs truncate">admin@mdt.fr</div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-semibold text-gray-800" id="page-title">Tableau de bord</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="loadData()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Actualiser">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <a href="change-password.php" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Paramètres">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </a>
                    <a href="?logout=1" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">
                        Déconnexion
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto scrollbar-thin p-6" id="main-content">
                <div class="flex items-center justify-center h-64">
                    <div class="animate-spin w-8 h-8 border-4 border-sidebar-active border-t-transparent rounded-full"></div>
                </div>
            </main>
        </div>
    </div>

    <div id="responses-modal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 id="modal-title" class="font-semibold text-gray-800">Détail</h3>
                <button onclick="closeModal()" class="p-1 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="modal-body" class="p-6 overflow-y-auto flex-1 scrollbar-thin"></div>
            <div id="modal-footer" class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 hidden">
                <button onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Annuler</button>
                <button onclick="saveChanges()" class="px-4 py-2 text-sm bg-sidebar-active text-white hover:bg-blue-600 rounded-lg transition">Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        let allData = null;
        let currentTab = 'dashboard';
        let currentStudyId = null;
        let currentEditData = null;
        let isEditMode = false;

        async function loadData() {
            try {
                const response = await fetch('../api/admin-data.php');
                const data = await response.json();
                if (data.success) {
                    allData = data;
                    render();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
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
            
            let title = { dashboard: 'Tableau de bord', studies: 'Études actives', closed: 'Archives' }[tab] || 'Tableau de bord';
            if (studyId && allData) {
                const study = allData.studies.find(s => s.studyId === studyId);
                if (study) title = study.studyName;
            }
            document.getElementById('page-title').textContent = title;
            render();
        }

        function goToStudy(studyId) {
            const study = allData.studies.find(s => s.studyId === studyId);
            if (!study) return;
            const isClosed = study.status === 'closed';
            switchTab(isClosed ? 'closed' : 'studies', studyId);
        }

        function render() {
            if (!allData) return;
            if (currentTab === 'dashboard') renderDashboard();
            else if (currentTab === 'studies') {
                if (currentStudyId) renderStudyDetail(currentStudyId, false);
                else renderStudiesList(false);
            }
            else if (currentTab === 'closed') {
                if (currentStudyId) renderStudyDetail(currentStudyId, true);
                else renderStudiesList(true);
            }
        }

        function renderDashboard() {
            const studies = allData.studies || [];
            const activeStudies = studies.filter(s => s.status !== 'closed');
            let totalQualifies = 0, totalRefuses = 0, totalParticipants = 0;
            
            studies.forEach(s => {
                totalQualifies += (s.stats?.qualifies || 0);
                totalRefuses += (s.stats?.refuses || 0);
                totalParticipants += (s.stats?.total || 0);
            });
            
            const taux = totalParticipants > 0 ? Math.round((totalQualifies / totalParticipants) * 100) : 0;

            let html = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <div class="bg-white rounded-xl p-5 border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Participants total</p>
                            <p class="text-3xl font-bold text-gray-800">${totalParticipants}</p>
                            <p class="text-xs text-blue-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                actif
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Qualifiés</p>
                            <p class="text-3xl font-bold text-gray-800">${totalQualifies}</p>
                            <p class="text-xs text-blue-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                +${totalQualifies}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Études actives</p>
                            <p class="text-3xl font-bold text-gray-800">${activeStudies.length}</p>
                            <p class="text-xs text-blue-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                en cours
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Taux de réponse</p>
                            <p class="text-3xl font-bold text-gray-800">${taux}%</p>
                            <p class="text-xs text-blue-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                +${taux > 50 ? '5' : '2'}%
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="font-semibold text-gray-800">Études récentes</h2>
                            <button onclick="switchTab('studies')" class="text-sm text-sidebar-active hover:underline">Voir tout</button>
                        </div>
                        <div class="space-y-4">
                            ${activeStudies.slice(0, 5).map(s => `
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer" onclick="goToStudy('${s.studyId}')">
                                <div>
                                    <p class="font-medium text-gray-800 hover:text-sidebar-active">${s.studyName}</p>
                                    <p class="text-sm text-gray-500">${s.studyDate || ''}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-800">${s.stats?.qualifies || 0}/${s.stats?.total || 0}</p>
                                        <p class="text-xs text-gray-500">qualifiés</p>
                                    </div>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-sidebar-active rounded-full" style="width: ${s.stats?.total > 0 ? Math.round((s.stats.qualifies / s.stats.total) * 100) : 0}%"></div>
                                    </div>
                                </div>
                            </div>
                            `).join('') || '<p class="text-gray-400 text-center py-8">Aucune étude active</p>'}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 p-6">
                        <h2 class="font-semibold text-gray-800 mb-6">Activité récente</h2>
                        <div class="space-y-4">
                            ${getRecentActivity()}
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('main-content').innerHTML = html;
        }

        function getRecentActivity() {
            const activities = [];
            (allData.studies || []).forEach(s => {
                (s.qualifies || []).forEach(p => {
                    activities.push({ name: p.prenom + ' ' + p.nom, study: s.studyName, studyId: s.studyId, date: p.date });
                });
            });
            activities.sort((a, b) => new Date(b.date) - new Date(a.date));
            
            return activities.slice(0, 5).map(a => `
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm"><span class="font-medium text-gray-800">${esc(a.name)}</span> <span class="text-gray-500">s'est qualifié(e)</span></p>
                        <p class="text-xs text-sidebar-active truncate">${esc(a.study)}</p>
                        <p class="text-xs text-gray-400">${formatTimeAgo(a.date)}</p>
                    </div>
                </div>
            `).join('') || '<p class="text-gray-400 text-sm">Aucune activité récente</p>';
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

        function renderStudiesList(showClosed) {
            let studies = (allData.studies || []).filter(s => showClosed ? s.status === 'closed' : s.status !== 'closed');
            
            if (studies.length === 0) {
                document.getElementById('main-content').innerHTML = '<div class="flex flex-col items-center justify-center h-64 text-gray-400"><p>Aucune étude</p></div>';
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
            studies.forEach(study => {
                const stats = study.stats || {};
                const progress = stats.total > 0 ? Math.round((stats.qualifies / stats.total) * 100) : 0;
                html += `
                <div class="bg-white rounded-xl border border-gray-100 p-6 hover:shadow-lg transition cursor-pointer" onclick="goToStudy('${study.studyId}')">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-sidebar-active/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-sidebar-active" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        ${showClosed ? '<span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">Terminée</span>' : '<span class="px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded-full">Active</span>'}
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-1">${study.studyName}</h3>
                    <p class="text-sm text-gray-500 mb-4">${study.studyDate || ''}</p>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-500">Progression</span>
                        <span class="text-sm font-semibold text-gray-800">${stats.qualifies || 0}/${stats.total || 0}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-sidebar-active rounded-full transition-all" style="width: ${progress}%"></div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-sm">
                        <div class="text-center">
                            <p class="font-semibold text-blue-600">${stats.qualifies || 0}</p>
                            <p class="text-gray-400 text-xs">Qualifiés</p>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold text-red-500">${stats.refuses || 0}</p>
                            <p class="text-gray-400 text-xs">Refusés</p>
                        </div>
                        <div class="text-center">
                            <p class="font-semibold text-amber-500">${progress}%</p>
                            <p class="text-gray-400 text-xs">Taux</p>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            document.getElementById('main-content').innerHTML = html;
        }

        function renderStudyDetail(studyId, showClosed) {
            const study = allData.studies.find(s => s.studyId === studyId);
            if (!study) {
                renderStudiesList(showClosed);
                return;
            }

            const stats = study.stats || {};
            const qualifies = study.qualifies || [];
            const refuses = study.refuses || [];

            let html = `
            <div class="mb-6">
                <button onclick="switchTab('${showClosed ? 'closed' : 'studies'}')" class="flex items-center gap-2 text-gray-500 hover:text-gray-800 transition mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Retour aux études
                </button>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">${study.studyName}</h2>
                        <p class="text-sm text-gray-500">${study.studyDate || ''}</p>
                        <a href="../studies/${study.folder}/" target="_blank" class="inline-flex items-center gap-1 text-sm text-sidebar-active hover:underline mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Ouvrir le questionnaire
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="copyStudyLink('${study.folder}')" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            Copier le lien
                        </button>
                        ${!showClosed ? `<button onclick="closeStudy('${study.folder}')" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">Terminer</button>` : `<button onclick="reopenStudy('${study.folder}')" class="px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition">Réouvrir l'étude</button>`}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase mb-1">Total</p>
                    <p class="text-2xl font-bold text-gray-800">${stats.total || 0}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase mb-1">Qualifiés</p>
                    <p class="text-2xl font-bold text-blue-600">${stats.qualifies || 0}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-amber-200">
                    <p class="text-xs text-amber-500 uppercase mb-1">En cours</p>
                    <p class="text-2xl font-bold text-amber-500">${stats.en_cours || 0}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase mb-1">Refusés</p>
                    <p class="text-2xl font-bold text-red-500">${stats.refuses || 0}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="text-xs text-gray-400 uppercase mb-1">Taux</p>
                    <p class="text-2xl font-bold text-gray-600">${stats.total > 0 ? Math.round((stats.qualifies / stats.total) * 100) : 0}%</p>
                </div>
            </div>

            ${!showClosed ? `
            <div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">IDs d'accès au questionnaire</p>
                        <p class="text-xs text-gray-400">Ajoutez des codes pour permettre aux participants d'accéder au questionnaire</p>
                    </div>
                    <button onclick="toggleAccessIdsPanel('${study.folder}')" class="px-3 py-1.5 text-sm bg-sidebar-active text-white rounded-lg hover:bg-blue-600 transition">
                        Gérer les IDs
                    </button>
                </div>
                <div id="access-ids-panel-${study.folder}" class="hidden">
                    <div class="flex gap-2 mb-4">
                        <input type="text" id="new-access-ids-${study.folder}" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Entrez les IDs séparés par des virgules ou retours à la ligne">
                        <button onclick="addAccessIds('${study.folder}')" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Ajouter</button>
                    </div>
                    <div id="access-ids-list-${study.folder}" class="max-h-40 overflow-y-auto">
                        <p class="text-gray-400 text-sm text-center py-2">Chargement...</p>
                    </div>
                </div>
            </div>
            ` : ''}

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
                ${(study.quotas || []).map(q => `
                <div class="bg-white rounded-xl p-4 border border-gray-100">
                    <p class="text-sm font-medium text-gray-700 mb-3">${q.titre}</p>
                    ${q.criteres.map(c => `
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm text-gray-500">${c.label}</span>
                        <span class="text-sm font-medium ${c.atteint ? 'text-blue-600' : 'text-amber-500'}">${c.actuel}${c.objectif ? '/' + c.objectif : ''}</span>
                    </div>
                    `).join('')}
                </div>
                `).join('')}
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
                <p class="text-sm font-medium text-gray-700 mb-3">Filtres croisés</p>
                <div class="flex flex-wrap gap-3 mb-3" id="filter-grid-${study.studyId}">${buildCrossFilters(study)}</div>
                <div class="flex items-center justify-between bg-sidebar text-white rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-bold" id="result-count-${study.studyId}">${qualifies.length}</span>
                        <span class="text-sm opacity-80">résultats</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div id="active-filters-${study.studyId}" class="flex gap-1"></div>
                        <button onclick="resetFilters('${study.studyId}')" class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded text-sm transition">Reset</button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="font-medium text-gray-700">Participants qualifiés</p>
                    <button onclick="exportStudy('${study.studyId}', 'qualifies')" class="px-3 py-1.5 text-sm bg-sidebar-active text-white rounded-lg hover:bg-blue-600 transition">Exporter</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="qualifies-table-${study.studyId}">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Ville</th>
                            <th class="px-4 py-3 text-left">Horaire</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            ${qualifies.length === 0 ? '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Aucun</td></tr>' : 
                            qualifies.map((p, i) => `
                            <tr data-index="${i}" class="hover:bg-gray-50">
                                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">${esc(p.accessId || '-')}</span></td>
                                <td class="px-4 py-3 font-medium text-gray-800">${esc(p.nom)}</td>
                                <td class="px-4 py-3 text-gray-600">${esc(p.prenom)}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${esc(p.email)}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">${esc(p.telephone)}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">${esc(p.ville)}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-800">${esc(p.horaire)}</td>
                                <td class="px-4 py-3"><div class="flex gap-1">
                                    <button onclick="showResponses('${study.studyId}', ${i}, 'qualifies')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition">Voir</button>
                                    <button onclick="deleteParticipant('${study.studyId}', '${p.id}', '${esc(p.prenom)} ${esc(p.nom)}')" class="px-2 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition">Suppr.</button>
                                </div></td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-amber-200 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-amber-200 bg-amber-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="font-medium text-amber-700">Participants en cours</p>
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">${(study.enCours || []).length}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="encours-table-${study.studyId}">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Questions</th>
                            <th class="px-4 py-3 text-left">Début</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            ${(study.enCours || []).length === 0 ? '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Aucun participant en cours</td></tr>' : 
                            (study.enCours || []).map((p, i) => `
                            <tr class="hover:bg-amber-50">
                                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-amber-100 rounded text-xs font-mono">${esc(p.accessId || '-')}</span></td>
                                <td class="px-4 py-3 font-medium text-gray-800">${esc(p.nom)}</td>
                                <td class="px-4 py-3 text-gray-600">${esc(p.prenom)}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${esc(p.email)}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">${esc(p.telephone)}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded">${p.questionsRepondues || 0} répondue(s)</span></td>
                                <td class="px-4 py-3 text-sm text-gray-500">${formatDate(p.dateDebut)}</td>
                                <td class="px-4 py-3"><div class="flex gap-1">
                                    <button onclick="showResponses('${study.studyId}', ${i}, 'enCours')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition">Voir</button>
                                    <button onclick="deleteParticipant('${study.studyId}', '${p.id}', '${esc(p.prenom)} ${esc(p.nom)}')" class="px-2 py-1 text-xs text-red-600 hover:bg-red-50 rounded transition">Suppr.</button>
                                </div></td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="font-medium text-gray-700">Participants refusés</p>
                    <button onclick="exportStudy('${study.studyId}', 'refuses')" class="px-3 py-1.5 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition">Exporter</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Raison</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            ${refuses.length === 0 ? '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucun</td></tr>' : 
                            refuses.map((p, i) => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono">${esc(p.accessId || '-')}</span></td>
                                <td class="px-4 py-3 font-medium text-gray-800">${esc(p.nom)}</td>
                                <td class="px-4 py-3 text-gray-600">${esc(p.prenom)}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">${esc(p.email)}</td>
                                <td class="px-4 py-3 text-sm text-red-500">${(p.raisons || []).join(', ')}</td>
                                <td class="px-4 py-3"><button onclick="showResponses('${study.studyId}', ${i}, 'refuses')" class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition">Voir</button></td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
            document.getElementById('main-content').innerHTML = html;
        }

        function esc(t) { if (t === null || t === undefined) return '-'; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

        function formatDate(dateStr) {
            if (!dateStr || dateStr === 'N/A') return '-';
            try {
                const date = new Date(dateStr);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                return dateStr;
            }
        }

        let studyQuestionTitles = {};

        async function loadStudyQuestions(folder) {
            if (studyQuestionTitles[folder]) return studyQuestionTitles[folder];
            try {
                // Ajouter timestamp pour éviter le cache
                const response = await fetch('../studies/' + folder + '/questions.js?v=' + Date.now());
                const jsContent = await response.text();
                const titles = {};
                
                // Parser toutes les questions pour extraire les IDs et titres
                // Méthode améliorée: chercher chaque bloc de question
                const regex = /id:\s*['"]([^'"]+)['"][^}]*?(?:title|question):\s*['"]([^'"]+)['"]/gs;
                let match;
                while ((match = regex.exec(jsContent)) !== null) {
                    const id = match[1];
                    const title = match[2].replace(/<[^>]*>/g, '').trim();
                    if (title) {
                        titles[id] = title.substring(0, 35);
                    }
                }
                
                console.log('Titres chargés pour', folder, ':', titles);
                studyQuestionTitles[folder] = titles;
                return titles;
            } catch (e) {
                console.error('Erreur chargement questions:', e);
                return {};
            }
        }

        async function initFiltersForStudy(study) {
            const titles = await loadStudyQuestions(study.folder);
            const container = document.getElementById('filter-grid-' + study.studyId);
            if (container) {
                container.innerHTML = buildCrossFiltersSync(study, titles);
            }
        }

        function buildCrossFilters(study) {
            // Retourner un placeholder, puis charger les vrais filtres
            setTimeout(() => initFiltersForStudy(study), 100);
            return '<p class="text-gray-400 text-sm">Chargement des filtres...</p>';
        }

        function buildCrossFiltersSync(study, titles) {
            const allParticipants = [...(study.qualifies || []), ...(study.refuses || [])];
            if (allParticipants.length === 0) return '<p class="text-gray-400 text-sm">Aucune donnée pour les filtres</p>';
            
            // Collecter toutes les options possibles pour chaque question
            const opts = {};
            allParticipants.forEach(p => { 
                if (!p.reponses) return; 
                Object.entries(p.reponses).forEach(([id, a]) => { 
                    // Ignorer les questions de type fichier/photo
                    if (a.file) return;
                    if (!opts[id]) opts[id] = new Set(); 
                    if (a.value !== undefined) {
                        opts[id].add(String(a.value)); 
                    } else if (a.values && Array.isArray(a.values)) {
                        a.values.forEach(v => opts[id].add(String(v))); 
                    }
                }); 
            });
            
            // Trier les questions par ordre numérique
            const sortedQuestions = Object.keys(opts).sort((a, b) => {
                const numA = parseInt(a.replace(/\D/g, '')) || 0;
                const numB = parseInt(b.replace(/\D/g, '')) || 0;
                return numA - numB || a.localeCompare(b);
            });
            
            let html = '';
            sortedQuestions.forEach(qId => {
                if (!opts[qId] || opts[qId].size === 0) return;
                // Limiter aux questions avec moins de 20 options distinctes
                if (opts[qId].size > 20) return;
                
                const title = titles[qId] || qId;
                const options = Array.from(opts[qId]).sort().map(o => 
                    `<option value="${o}">${o.replace(/_/g, ' ')}</option>`
                ).join('');
                
                html += `<select class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm min-w-[120px]" 
                    data-study="${study.studyId}" data-question="${qId}" onchange="applyFilters('${study.studyId}')"
                    title="${esc(title)}">
                    <option value="">${esc(title)}</option>${options}</select>`;
            });
            
            return html || '<p class="text-gray-400 text-sm">Aucun filtre disponible</p>';
        }

        function applyFilters(studyId) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            const sels = document.querySelectorAll(`select[data-study="${studyId}"]`);
            const filters = {};
            const tags = [];
            
            sels.forEach(s => { 
                if (s.value) { 
                    filters[s.dataset.question] = s.value; 
                    tags.push(s.value.replace(/_/g, ' ')); 
                } 
            });
            
            let count = 0;
            study.qualifies.forEach((p, i) => {
                let match = true;
                
                Object.entries(filters).forEach(([qId, val]) => { 
                    const a = p.reponses?.[qId]; 
                    if (!a) { 
                        match = false; 
                        return; 
                    } 
                    if (a.value !== undefined) { 
                        if (String(a.value) !== val) match = false; 
                    } else if (a.values && Array.isArray(a.values)) { 
                        if (!a.values.map(String).includes(val)) match = false; 
                    } else { 
                        match = false; 
                    } 
                });
                
                const row = document.querySelector(`#qualifies-table-${studyId} tr[data-index="${i}"]`);
                if (row) row.classList.toggle('hidden-by-filter', !match);
                if (match) count++;
            });
            
            document.getElementById(`result-count-${studyId}`).textContent = count;
            document.getElementById(`active-filters-${studyId}`).innerHTML = tags.map(t => 
                `<span class="px-2 py-0.5 bg-white/20 rounded text-xs">${t}</span>`
            ).join('');
        }

        function resetFilters(studyId) {
            document.querySelectorAll(`select[data-study="${studyId}"]`).forEach(s => s.value = '');
            document.querySelectorAll(`#qualifies-table-${studyId} tr`).forEach(r => r.classList.remove('hidden-by-filter'));
            const study = allData.studies.find(s => s.studyId === studyId);
            document.getElementById(`result-count-${studyId}`).textContent = study ? study.qualifies.length : 0;
            document.getElementById(`active-filters-${studyId}`).innerHTML = '';
        }

        let questionTitles = {};

        async function showResponses(studyId, index, type) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            let list;
            if (type === 'qualifies') list = study.qualifies;
            else if (type === 'refuses') list = study.refuses;
            else if (type === 'enCours') list = study.enCours;
            else list = study.qualifies;
            const p = list[index]; if (!p) return;
            currentEditData = { studyId, studyFolder: study.folder, participantId: p.id, type, participant: p, originalReponses: JSON.parse(JSON.stringify(p.reponses || {})) };
            isEditMode = false;
            
            // Charger les titres des questions (utiliser le cache si disponible)
            if (studyQuestionTitles[study.folder] && Object.keys(studyQuestionTitles[study.folder]).length > 0) {
                questionTitles = studyQuestionTitles[study.folder];
            } else {
                // Charger les titres
                questionTitles = await loadStudyQuestions(study.folder);
            }
            
            console.log('Titres pour modal:', questionTitles);
            renderModal(false);
        }

        function renderModal(edit) {
            const p = currentEditData.participant, r = p.reponses || {}, folder = currentEditData.studyFolder;
            let html = `<div class="mb-4 pb-4 border-b border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div><p class="font-semibold text-gray-800">${esc(p.prenom)} ${esc(p.nom)}</p><p class="text-sm text-gray-500">${esc(p.email)}</p></div>
                    <button onclick="toggleEdit()" class="px-3 py-1.5 text-sm ${edit ? 'bg-gray-100' : 'bg-amber-50 text-amber-600'} rounded-lg">${edit ? 'Annuler' : 'Modifier'}</button>
                </div>
                ${edit ? `<div class="grid grid-cols-2 gap-2">
                    <input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="nom" value="${esc(p.nom||'')}" placeholder="Nom">
                    <input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="prenom" value="${esc(p.prenom||'')}" placeholder="Prénom">
                    <input type="email" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="email" value="${esc(p.email||'')}" placeholder="Email">
                    <input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="telephone" value="${esc(p.telephone||'')}" placeholder="Téléphone">
                    <input type="text" class="edit-sig col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="adresse" value="${esc(p.adresse||'')}" placeholder="Adresse">
                    <input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="codePostal" value="${esc(p.codePostal||'')}" placeholder="CP">
                    <input type="text" class="edit-sig px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="ville" value="${esc(p.ville||'')}" placeholder="Ville">
                    <input type="text" class="edit-sig col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm" data-field="horaire" value="${esc(p.horaire||'')}" placeholder="Horaire">
                </div>` : `<div class="grid grid-cols-2 gap-2 text-sm"><p><span class="text-gray-400">Adresse:</span> ${esc(p.adresse||'-')}</p><p><span class="text-gray-400">CP:</span> ${esc(p.codePostal||'-')}</p><p><span class="text-gray-400">Ville:</span> ${esc(p.ville||'-')}</p><p><span class="text-gray-400">Horaire:</span> ${esc(p.horaire||'-')}</p></div>`}
            </div>
            <div class="mb-3"><p class="text-sm font-medium text-gray-700">Réponses au questionnaire</p></div>
            <div class="space-y-2">`;
            
            Object.entries(r).forEach(([qId, ans]) => {
                const title = questionTitles[qId] || qId;
                if (edit) {
                    const currentValue = getAnswerValue(ans);
                    html += `<div class="py-2 border-b border-gray-50">
                        <p class="text-xs text-gray-400 mb-1">${esc(title)}</p>
                        <input type="text" class="edit-response w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" 
                               data-qid="${qId}" value="${esc(currentValue)}" placeholder="Réponse">
                    </div>`;
                } else {
                    html += `<div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500 flex-1">${esc(title)}</span>
                        <span class="text-sm text-right text-gray-800 ml-4">${formatAns(ans, folder)}</span>
                    </div>`;
                }
            });
            html += '</div>';
            document.getElementById('modal-body').innerHTML = html;
            document.getElementById('modal-title').textContent = edit ? 'Modifier' : 'Détail';
            document.getElementById('modal-footer').classList.toggle('hidden', !edit);
            document.getElementById('responses-modal').classList.remove('hidden');
            document.getElementById('responses-modal').classList.add('flex');
        }

        function getAnswerValue(ans) {
            if (!ans) return '';
            if (ans.file) return '[Photo]';
            if (ans.value !== undefined) return String(ans.value).replace(/_/g, ' ');
            if (ans.values) {
                if (Array.isArray(ans.values)) return ans.values.join(', ').replace(/_/g, ' ');
                return Object.entries(ans.values).map(([k,v]) => k + ': ' + v).join(' | ');
            }
            return JSON.stringify(ans);
        }

        function toggleEdit() { 
            isEditMode = !isEditMode; 
            if (!isEditMode) {
                currentEditData.participant.reponses = JSON.parse(JSON.stringify(currentEditData.originalReponses)); 
            }
            renderModal(isEditMode); 
        }

        function formatAns(a, folder) {
            if (!a) return '-';
            if (a.file) { let fn = a.file.filename || a.file.name || (typeof a.file === 'string' ? a.file : null); if (fn) { let url = '../api/photo.php?file=' + encodeURIComponent(fn); if (folder) url += '&study=' + encodeURIComponent(folder); return '<a href="' + url + '" target="_blank" class="text-sidebar-active hover:underline">Voir photo</a>'; } }
            if (a.value !== undefined) return esc(String(a.value).replace(/_/g, ' '));
            if (a.values) { 
                if (Array.isArray(a.values)) return esc(a.values.join(', ').replace(/_/g, ' ')); 
                // Format objet : "Marque : Babyliss, Modèle : Ndp"
                return Object.entries(a.values).map(([k,v]) => {
                    // Capitaliser la clé et remplacer les underscores
                    const key = k.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
                    // Remplacer les underscores dans la valeur aussi
                    const val = String(v).replace(/_/g, ' ');
                    return key + ' : ' + val;
                }).join(', '); 
            }
            return esc(JSON.stringify(a));
        }

        function closeModal() { document.getElementById('responses-modal').classList.add('hidden'); document.getElementById('responses-modal').classList.remove('flex'); }

        async function saveChanges() {
            if (!currentEditData) return;
            const sig = {}; 
            document.querySelectorAll('.edit-sig').forEach(i => { 
                if (i.dataset.field) sig[i.dataset.field] = i.value.trim(); 
            });
            
            // Récupérer les réponses modifiées
            const newReponses = JSON.parse(JSON.stringify(currentEditData.participant.reponses || {}));
            document.querySelectorAll('.edit-response').forEach(input => {
                const qId = input.dataset.qid;
                const newValue = input.value.trim();
                if (qId && newReponses[qId]) {
                    // Mettre à jour la valeur selon le type de réponse
                    if (newReponses[qId].value !== undefined) {
                        newReponses[qId].value = newValue.replace(/ /g, '_');
                    } else if (newReponses[qId].values && Array.isArray(newReponses[qId].values)) {
                        newReponses[qId].values = newValue.split(',').map(v => v.trim().replace(/ /g, '_'));
                    }
                }
            });
            
            try {
                const res = await fetch('../api/admin-data.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ 
                        action: 'update_participant', 
                        studyFolder: currentEditData.studyFolder, 
                        participantId: currentEditData.participantId, 
                        signaletique: sig, 
                        horaire: sig.horaire || '', 
                        reponses: newReponses 
                    }) 
                });
                const data = await res.json(); 
                if (data.success) { 
                    closeModal(); 
                    loadData(); 
                } else {
                    alert(data.error || 'Erreur lors de la sauvegarde');
                }
            } catch (e) { 
                console.error(e); 
                alert('Erreur de connexion');
            }
        }

        async function deleteParticipant(studyId, participantId, name) {
            if (!confirm('Supprimer ' + name + ' ?')) return;
            try { const res = await fetch('../api/admin-data.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_participant', studyId, participantId }) }); const data = await res.json(); if (data.success) loadData(); } catch (e) { console.error(e); }
        }

        async function closeStudy(folder) {
            if (!confirm('Terminer cette étude ?')) return;
            try { const res = await fetch('../api/study-status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'close', studyFolder: folder }) }); const data = await res.json(); if (data.success) loadData(); } catch (e) { console.error(e); }
        }

        async function reopenStudy(folder) {
            if (!confirm('Réouvrir cette étude ?')) return;
            try { 
                const res = await fetch('../api/study-status.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ action: 'reopen', studyFolder: folder }) 
                }); 
                const data = await res.json(); 
                if (data.success) {
                    switchTab('studies');
                    loadData(); 
                }
            } catch (e) { console.error(e); }
        }

        function exportStudy(studyId, type) {
            const study = allData.studies.find(s => s.studyId === studyId); if (!study) return;
            const data = type === 'qualifies' ? study.qualifies : study.refuses;
            if (!data || data.length === 0) { alert('Aucune donnée'); return; }
            fetch('../studies/' + study.folder + '/questions.js').then(r => r.text()).then(js => {
                const titles = {}; js.split(/\{\s*id:/).forEach(b => { const idM = b.match(/^\s*['"]([^'"]+)['"]/); if (!idM) return; const tM = b.match(/title:\s*['"]([^'"]+)['"]/) || b.match(/question:\s*['"]([^'"]+)['"]/); if (tM) titles[idM[1]] = tM[1].replace(/<[^>]*>/g, '').substring(0, 50); });
                const allQIds = new Set(); data.forEach(p => { if (p.reponses) Object.keys(p.reponses).forEach(q => allQIds.add(q)); });
                const qIds = Array.from(allQIds).sort((a, b) => { const na = parseInt(a.replace(/\D/g, '')) || 0, nb = parseInt(b.replace(/\D/g, '')) || 0; return na !== nb ? na - nb : a.localeCompare(b); });
                let headers = ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Adresse', 'CP', 'Ville', 'Horaire', 'Date']; if (type === 'refuses') headers.push('Raisons'); qIds.forEach(qId => headers.push(titles[qId] || qId));
                const rows = data.map(p => { let row = [p.accessId||'', p.nom||'', p.prenom||'', p.email||'', p.telephone||'', p.adresse||'', p.codePostal||'', p.ville||'', p.horaire||'', p.date||'']; if (type === 'refuses') row.push((p.raisons||[]).join(' | ')); qIds.forEach(qId => { const a = p.reponses?.[qId]; if (!a) { row.push(''); return; } if (a.file) { row.push(a.file.filename || 'Photo'); return; } if (a.value !== undefined) { row.push(String(a.value).replace(/_/g, ' ')); return; } if (a.values) { if (Array.isArray(a.values)) { row.push(a.values.join(', ').replace(/_/g, ' ')); return; } row.push(Object.entries(a.values).map(([k,v]) => k + ': ' + v).join(' | ')); return; } row.push(JSON.stringify(a)); }); return row; });
                const form = document.createElement('form'); form.method = 'POST'; form.action = '../api/export-xlsx.php'; form.innerHTML = '<input type="hidden" name="data" value=\'' + JSON.stringify({headers, rows}).replace(/'/g, '&#39;') + '\'><input type="hidden" name="filename" value="' + studyId + '_' + type + '"><input type="hidden" name="studyId" value="' + studyId + '">'; document.body.appendChild(form); form.submit(); document.body.removeChild(form);
            });
        }

        function copyStudyLink(folder) {
            const baseUrl = window.location.origin + window.location.pathname.replace('/admin/dashboard.php', '');
            const link = baseUrl + '/studies/' + folder + '/';
            navigator.clipboard.writeText(link).then(() => {
                // Afficher une notification temporaire
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copié !';
                btn.classList.add('text-green-600');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('text-green-600');
                }, 2000);
            }).catch(() => {
                // Fallback pour les navigateurs plus anciens
                const input = document.createElement('input');
                input.value = link;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('Lien copié : ' + link);
            });
        }

        function toggleAccessIdsPanel(folder) {
            const panel = document.getElementById('access-ids-panel-' + folder);
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                loadAccessIds(folder);
            } else {
                panel.classList.add('hidden');
            }
        }

        async function loadAccessIds(folder) {
            const listEl = document.getElementById('access-ids-list-' + folder);
            try {
                const res = await fetch('../api/admin-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_access_ids', studyFolder: folder })
                });
                const data = await res.json();
                if (data.success) {
                    const ids = data.ids || [];
                    const used = data.usedIds || [];
                    if (ids.length === 0) {
                        listEl.innerHTML = '<p class="text-gray-400 text-sm text-center py-2">Aucun ID configuré</p>';
                    } else {
                        listEl.innerHTML = '<div class="flex flex-wrap gap-2">' + ids.map(id => {
                            const isUsed = used.includes(id);
                            return `<div class="flex items-center gap-1 px-2 py-1 ${isUsed ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'} rounded text-sm">
                                <span class="font-mono">${esc(id)}</span>
                                ${isUsed ? '<span class="text-xs">(utilisé)</span>' : `<button onclick="removeAccessId('${folder}', '${id}')" class="ml-1 text-red-500 hover:text-red-700">&times;</button>`}
                            </div>`;
                        }).join('') + '</div>';
                    }
                }
            } catch (e) {
                listEl.innerHTML = '<p class="text-red-500 text-sm text-center py-2">Erreur de chargement</p>';
            }
        }

        async function addAccessIds(folder) {
            const input = document.getElementById('new-access-ids-' + folder);
            const rawValue = input.value.trim();
            if (!rawValue) return;
            
            const ids = rawValue.split(/[\s,;]+/).map(id => id.trim()).filter(id => id.length > 0);
            if (ids.length === 0) return;
            
            try {
                const res = await fetch('../api/admin-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add_access_ids', studyFolder: folder, ids: ids })
                });
                const data = await res.json();
                if (data.success) {
                    input.value = '';
                    loadAccessIds(folder);
                } else {
                    alert(data.error || 'Erreur');
                }
            } catch (e) {
                alert('Erreur de connexion');
            }
        }

        async function removeAccessId(folder, id) {
            if (!confirm('Supprimer l\'ID "' + id + '" ?')) return;
            try {
                const res = await fetch('../api/admin-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove_access_id', studyFolder: folder, accessId: id })
                });
                const data = await res.json();
                if (data.success) {
                    loadAccessIds(folder);
                }
            } catch (e) {
                alert('Erreur de connexion');
            }
        }

        document.getElementById('responses-modal').addEventListener('click', e => { if (e.target.id === 'responses-modal') closeModal(); });
        switchTab('dashboard');
        loadData();
        setInterval(loadData, 30000);
    </script>
</body>
</html>
