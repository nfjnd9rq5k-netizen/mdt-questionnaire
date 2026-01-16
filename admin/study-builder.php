<?php
/**
 * ============================================================
 * STUDY BUILDER - INTERFACE NO-CODE (VERSION AMÉLIORÉE)
 * ============================================================
 */
?>

<!-- Modal Builder d'étude -->
<div id="study-builder-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-2 md:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col my-4">
        <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-teal-700 text-white flex items-center justify-between flex-shrink-0">
            <div>
                <h3 class="font-semibold text-lg">Créer une nouvelle étude</h3>
                <p class="text-sm text-teal-100" id="builder-step-label">Étape 1/3 - Configuration</p>
            </div>
            <button onclick="closeStudyBuilder()" class="p-2 hover:bg-teal-500 rounded-lg transition text-xl">✕</button>
        </div>
        <div class="h-1 bg-teal-100 flex-shrink-0"><div id="builder-progress" class="h-full bg-teal-500 transition-all duration-300" style="width: 33%"></div></div>
        <div class="flex-1 overflow-y-auto p-6" id="builder-content">
            <!-- Step 1: Configuration -->
            <div id="builder-step-1" class="builder-step">
                <h4 class="text-lg font-semibold text-gray-800 mb-6">📋 Configuration de l'étude</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Identifiant unique *</label>
                        <input type="text" id="builder-studyId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Ex: ETUDE_PRODUIT_JAN2026" oninput="validateStudyId()">
                        <p id="builder-studyId-status" class="text-xs mt-1 text-gray-400">Sera utilisé comme nom de dossier</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titre de l'étude *</label>
                        <input type="text" id="builder-studyTitle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Ex: Test produit XYZ">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Date de l'étude</label><input type="text" id="builder-studyDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Janvier 2026"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Récompense</label><input type="text" id="builder-reward" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: 50€"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Durée</label><input type="text" id="builder-duration" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: 45 min"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre de participants cible</label><input type="number" id="builder-totalParticipants" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="10" min="1"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Horaires disponibles</label><input type="text" id="builder-horaires" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: 9h, 11h, 14h, 16h (séparés par virgules)"></div>
                </div>
                <div class="flex items-center gap-6 p-4 bg-gray-50 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="builder-requireAccessId" checked class="w-4 h-4 text-teal-600 rounded"><span class="text-sm text-gray-700">Exiger un ID d'accès</span></label>
                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="builder-hideHoraires" class="w-4 h-4 text-teal-600 rounded"><span class="text-sm text-gray-700">Masquer les horaires</span></label>
                </div>
            </div>
            <!-- Step 2: Questions -->
            <div id="builder-step-2" class="builder-step hidden">
                <!-- Templates organisés par catégorie -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-800">📝 Questions</h4>
                        <button onclick="addQuestion()" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700 transition flex items-center gap-2">
                            <span>+</span> Question personnalisée
                        </button>
                    </div>
                    
                    <!-- Catégorie: Qualification -->
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-red-500">🚫</span>
                            <span class="text-sm font-medium text-gray-700">Qualification (STOP)</span>
                            <span class="text-xs text-gray-400">— Pour filtrer les participants non éligibles</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <button onclick="addTemplateQuestion('secteurs')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-red-100 rounded-lg text-left hover:border-red-300 hover:bg-red-50 transition">
                                <span class="text-xl">🏢</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Secteurs d'activité</p>
                                    <p class="text-xs text-gray-500 truncate">STOP si pub, marketing, journalisme...</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded">STOP</span>
                            </button>
                            <button onclick="addTemplateQuestion('participation')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-red-100 rounded-lg text-left hover:border-red-300 hover:bg-red-50 transition">
                                <span class="text-xl">📅</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Participation récente</p>
                                    <p class="text-xs text-gray-500 truncate">STOP si étude dans les 6 derniers mois</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded">STOP</span>
                            </button>
                            <button onclick="addTemplateQuestion('age')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-amber-100 rounded-lg text-left hover:border-amber-300 hover:bg-amber-50 transition">
                                <span class="text-xl">🎂</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Âge</p>
                                    <p class="text-xs text-gray-500 truncate">STOP si &lt;18 ou &gt;65 ans (modifiable)</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-amber-100 text-amber-600 rounded">STOP</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Catégorie: Profil sociodémographique -->
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-blue-500">👤</span>
                            <span class="text-sm font-medium text-gray-700">Profil sociodémographique</span>
                            <span class="text-xs text-gray-400">— Pour les quotas et la segmentation</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            <button onclick="addTemplateQuestion('sexe')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">⚧</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Sexe</p>
                                    <p class="text-xs text-gray-500">Homme / Femme / Autre</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-600 rounded">Quota</span>
                            </button>
                            <button onclick="addTemplateQuestion('revenus')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">💰</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Revenus</p>
                                    <p class="text-xs text-gray-500">Tranches de revenus du foyer</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('profession')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">💼</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Profession</p>
                                    <p class="text-xs text-gray-500">Profession + secteur d'activité</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('csp')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">📊</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">CSP</p>
                                    <p class="text-xs text-gray-500">Catégorie socioprofessionnelle</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-600 rounded">Quota</span>
                            </button>
                            <button onclick="addTemplateQuestion('region')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">📍</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Région</p>
                                    <p class="text-xs text-gray-500">Région de résidence</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-600 rounded">Quota</span>
                            </button>
                            <button onclick="addTemplateQuestion('habitat')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-blue-100 rounded-lg text-left hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="text-xl">🏠</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Type d'habitat</p>
                                    <p class="text-xs text-gray-500">Maison / Appartement</p>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Catégorie: Famille -->
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-purple-500">👨‍👩‍👧‍👦</span>
                            <span class="text-sm font-medium text-gray-700">Famille & Foyer</span>
                            <span class="text-xs text-gray-400">— Composition du foyer</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            <button onclick="addTemplateQuestion('enfants')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-purple-100 rounded-lg text-left hover:border-purple-300 hover:bg-purple-50 transition">
                                <span class="text-xl">👶</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Enfants au foyer</p>
                                    <p class="text-xs text-gray-500">Oui / Non</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('nb_enfants')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-indigo-200 rounded-lg text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                                <span class="text-xl">🔢</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Nombre d'enfants</p>
                                    <p class="text-xs text-indigo-600">🔗 Affiché si enfants = Oui</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-600 rounded">Cond.</span>
                            </button>
                            <button onclick="addTemplateQuestion('ages_enfants')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-indigo-200 rounded-lg text-left hover:border-indigo-400 hover:bg-indigo-50 transition">
                                <span class="text-xl">📊</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Âges des enfants</p>
                                    <p class="text-xs text-indigo-600">🔗 Affiché si enfants = Oui</p>
                                </div>
                                <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-600 rounded">Cond.</span>
                            </button>
                            <button onclick="addTemplateQuestion('situation')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-purple-100 rounded-lg text-left hover:border-purple-300 hover:bg-purple-50 transition">
                                <span class="text-xl">💑</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Situation familiale</p>
                                    <p class="text-xs text-gray-500">Célibataire, marié, etc.</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('foyer')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-purple-100 rounded-lg text-left hover:border-purple-300 hover:bg-purple-50 transition">
                                <span class="text-xl">👥</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Taille du foyer</p>
                                    <p class="text-xs text-gray-500">Nombre de personnes</p>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Catégorie: Fin de questionnaire -->
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-green-500">✅</span>
                            <span class="text-sm font-medium text-gray-700">Fin de questionnaire</span>
                            <span class="text-xs text-gray-400">— À placer en dernier</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            <button onclick="addTemplateQuestion('photo')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-green-100 rounded-lg text-left hover:border-green-300 hover:bg-green-50 transition">
                                <span class="text-xl">📷</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Photo</p>
                                    <p class="text-xs text-gray-500">Upload de photo/document</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('signaletique')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-green-100 rounded-lg text-left hover:border-green-300 hover:bg-green-50 transition">
                                <span class="text-xl">📋</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Coordonnées</p>
                                    <p class="text-xs text-gray-500">Nom + Prénom</p>
                                </div>
                            </button>
                            <button onclick="addTemplateQuestion('commentaires')" class="template-btn group flex items-start gap-3 p-3 bg-white border-2 border-green-100 rounded-lg text-left hover:border-green-300 hover:bg-green-50 transition">
                                <span class="text-xl">💬</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 text-sm">Commentaires</p>
                                    <p class="text-xs text-gray-500">Zone de texte libre</p>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Légende -->
                    <div class="flex flex-wrap gap-4 text-xs text-gray-500 mt-4 pt-4 border-t border-gray-100">
                        <span class="flex items-center gap-1"><span class="px-2 py-0.5 bg-red-100 text-red-600 rounded">STOP</span> Question de filtrage</span>
                        <span class="flex items-center gap-1"><span class="px-2 py-0.5 bg-blue-100 text-blue-600 rounded">Quota</span> Utile pour quotas</span>
                        <span class="flex items-center gap-1"><span class="px-2 py-0.5 bg-indigo-100 text-indigo-600 rounded">Cond.</span> Affichage conditionnel</span>
                    </div>
                </div>
                
                <!-- Liste des questions ajoutées -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="font-medium text-gray-700">Questions ajoutées</h5>
                        <span id="questions-count" class="text-sm text-gray-400"></span>
                    </div>
                    <div id="builder-questions-list" class="space-y-3"></div>
                    <div id="builder-no-questions" class="text-center py-12 text-gray-400">
                        <p class="text-4xl mb-3">📝</p>
                        <p>Aucune question pour l'instant</p>
                        <p class="text-sm mt-2">Cliquez sur un template ci-dessus pour commencer</p>
                    </div>
                </div>
            </div>
            <!-- Step 3: Quotas -->
            <div id="builder-step-3" class="builder-step hidden">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">📊 Quotas (optionnel)</h4>
                    <p class="text-sm text-gray-500">Cliquez sur une question pour créer un quota et définir les objectifs par critère.</p>
                </div>
                
                <!-- Questions disponibles pour quotas -->
                <div id="builder-quotas-available" class="mb-6"></div>
                
                <!-- Quotas configurés -->
                <div id="builder-quotas-configured" class="space-y-4"></div>
                
                <!-- Message si aucune question éligible -->
                <div id="builder-no-quotas" class="text-center py-12 text-gray-400 hidden">
                    <p class="text-4xl mb-3">📊</p>
                    <p>Aucune question éligible pour les quotas</p>
                    <p class="text-sm mt-2">Ajoutez des questions à choix unique ou multiples à l'étape précédente</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0 bg-gray-50">
            <button onclick="prevBuilderStep()" id="builder-prev-btn" class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg transition hidden">← Précédent</button>
            <div class="flex-1"></div>
            <div class="flex items-center gap-3">
                <button onclick="closeStudyBuilder()" class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg transition">Annuler</button>
                <button onclick="nextBuilderStep()" id="builder-next-btn" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Suivant →</button>
                <button onclick="createStudy()" id="builder-create-btn" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition hidden">✓ Créer l'étude</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal édition question -->
<div id="question-editor-modal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center p-2 md:p-4 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col my-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="font-semibold text-gray-800" id="question-editor-title">Nouvelle question</h3>
            <button onclick="closeQuestionEditor()" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-400 text-xl">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <input type="hidden" id="edit-question-index" value="-1">
            <div class="mb-6 p-4 bg-teal-50 rounded-xl border border-teal-100">
                <label class="block text-sm font-medium text-teal-800 mb-2">Type de question *</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button type="button" onclick="selectQuestionType('single')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="single"><span class="text-xl">○</span><p class="text-xs mt-1">Choix unique</p></button>
                    <button type="button" onclick="selectQuestionType('multiple')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="multiple"><span class="text-xl">☑</span><p class="text-xs mt-1">Choix multiples</p></button>
                    <button type="button" onclick="selectQuestionType('number')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="number"><span class="text-xl">123</span><p class="text-xs mt-1">Nombre</p></button>
                    <button type="button" onclick="selectQuestionType('text')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="text"><span class="text-xl">Aa</span><p class="text-xs mt-1">Texte libre</p></button>
                    <button type="button" onclick="selectQuestionType('double_text')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="double_text"><span class="text-xl">Aa|Bb</span><p class="text-xs mt-1">Double texte</p></button>
                    <button type="button" onclick="selectQuestionType('file')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="file"><span class="text-xl">📷</span><p class="text-xs mt-1">Photo/Fichier</p></button>
                    <button type="button" onclick="selectQuestionType('single_with_text')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="single_with_text"><span class="text-xl">○+</span><p class="text-xs mt-1">Choix + texte</p></button>
                    <button type="button" onclick="selectQuestionType('multiple_with_text')" class="type-btn p-3 bg-white border-2 border-gray-200 rounded-lg text-center hover:border-teal-400 transition" data-type="multiple_with_text"><span class="text-xl">☑+</span><p class="text-xs mt-1">Multi + texte</p></button>
                </div>
                <input type="hidden" id="edit-question-type" value="single">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">ID *</label><input type="text" id="edit-question-id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono" placeholder="q1"><p class="text-xs text-gray-400 mt-1">Identifiant unique</p></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Titre court</label><input type="text" id="edit-question-title" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Secteurs d'activité"><p class="text-xs text-gray-400 mt-1">Affiché dans le dashboard</p></div>
            </div>
            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Question affichée au participant *</label><textarea id="edit-question-text" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Vous ou un proche travaillez-vous dans l'un des secteurs suivants ?"></textarea></div>
            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Note interne</label><input type="text" id="edit-question-note" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Ex: STOP si marketing (visible uniquement dans l'admin)"></div>
            <div id="number-options" class="mb-4 p-4 bg-amber-50 rounded-lg border border-amber-100 hidden">
                <h5 class="font-medium text-amber-800 mb-3">⚙️ Options numériques</h5>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div><label class="block text-xs text-gray-600 mb-1">Minimum</label><input type="number" id="edit-question-min" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Ex: 18"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Maximum</label><input type="number" id="edit-question-max" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Ex: 65"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Unité</label><input type="text" id="edit-question-suffix" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Ex: ans, €"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-amber-200">
                    <div><label class="block text-xs text-gray-600 mb-1">🚫 STOP si &lt;</label><input type="number" id="edit-question-stopMin" class="w-full px-2 py-1.5 border border-red-200 rounded text-sm bg-red-50" placeholder="Ex: 18"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">🚫 STOP si &gt;</label><input type="number" id="edit-question-stopMax" class="w-full px-2 py-1.5 border border-red-200 rounded text-sm bg-red-50" placeholder="Ex: 65"></div>
                </div>
            </div>
            <div id="double-text-options" class="mb-4 p-4 bg-purple-50 rounded-lg border border-purple-100 hidden">
                <h5 class="font-medium text-purple-800 mb-3">⚙️ Deux champs texte</h5>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs text-gray-600 mb-1">Label 1er champ</label><input type="text" id="edit-field1-label" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Ex: Profession"></div>
                    <div><label class="block text-xs text-gray-600 mb-1">Label 2ème champ</label><input type="text" id="edit-field2-label" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Ex: Secteur"></div>
                </div>
            </div>
            <div id="file-options" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-100 hidden">
                <h5 class="font-medium text-blue-800 mb-3">⚙️ Type de fichier</h5>
                <select id="edit-question-accept" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="image/*">📷 Images uniquement</option>
                    <option value=".pdf">📄 PDF uniquement</option>
                    <option value="image/*,.pdf">📷📄 Images et PDF</option>
                </select>
            </div>
            <div id="choices-options" class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="font-medium text-gray-700">Options de réponse</h5>
                    <button onclick="addOption()" class="px-3 py-1.5 bg-teal-100 hover:bg-teal-200 text-teal-700 rounded-lg text-sm transition">+ Ajouter</button>
                </div>
                <div class="grid grid-cols-12 gap-2 mb-2 px-2 text-xs text-gray-500 hidden md:grid">
                    <div class="col-span-3">Valeur (code)</div>
                    <div class="col-span-5">Libellé affiché</div>
                    <div class="col-span-2 text-center">STOP ?</div>
                    <div class="col-span-2 text-center">Actions</div>
                </div>
                <div id="options-list" class="space-y-2"></div>
                <p id="options-empty" class="text-sm text-gray-400 text-center py-6 bg-gray-50 rounded-lg">Cliquez sur "+ Ajouter" pour créer des options</p>
            </div>
            <!-- Condition d'affichage -->
            <div id="showif-options" class="mb-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="font-medium text-indigo-800">🔗 Condition d'affichage</h5>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit-question-hasCondition" class="w-4 h-4 text-indigo-600 rounded" onchange="toggleConditionOptions()">
                        <span class="text-sm text-indigo-700">Activer</span>
                    </label>
                </div>
                <div id="condition-fields" class="hidden">
                    <p class="text-xs text-gray-500 mb-3">Afficher cette question seulement si...</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Question source</label>
                            <select id="edit-condition-source" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" onchange="updateConditionValues()">
                                <option value="">-- Sélectionner --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Opérateur</label>
                            <select id="edit-condition-operator" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm">
                                <option value="equals">est égal à</option>
                                <option value="not_equals">est différent de</option>
                                <option value="contains">contient</option>
                                <option value="not_contains">ne contient pas</option>
                                <option value="greater">est supérieur à</option>
                                <option value="less">est inférieur à</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Valeur</label>
                            <select id="edit-condition-value-select" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm">
                                <option value="">-- Sélectionner --</option>
                            </select>
                            <input type="text" id="edit-condition-value-text" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm hidden mt-1" placeholder="Valeur">
                        </div>
                    </div>
                    <p class="text-xs text-indigo-600 mt-2" id="condition-preview"></p>
                </div>
            </div>

            <div class="flex items-center gap-6 p-3 bg-gray-50 rounded-lg">
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="edit-question-optional" class="w-4 h-4 text-teal-600 rounded"><span class="text-sm text-gray-700">Question optionnelle</span></label>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0 bg-gray-50">
            <button onclick="previewQuestion()" class="px-4 py-2 text-purple-600 hover:bg-purple-50 rounded-lg transition text-sm">👁 Aperçu</button>
            <div class="flex items-center gap-3">
                <button onclick="closeQuestionEditor()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">Annuler</button>
                <button onclick="saveQuestion()" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">✓ Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal aperçu -->
<div id="question-preview-modal" class="fixed inset-0 bg-black/50 z-[70] hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="px-4 py-3 bg-purple-600 text-white flex items-center justify-between"><span class="font-medium">👁 Aperçu</span><button onclick="closePreviewQuestion()" class="p-1 hover:bg-purple-500 rounded">✕</button></div>
        <div id="question-preview-content" class="p-6"></div>
    </div>
</div>

<script>
let builderStep = 1, builderQuestions = [], builderQuotas = [], editingQuestionIndex = -1, questionOptions = [];

const questionTemplates = {
    // === QUALIFICATION (STOP) ===
    secteurs: { 
        id: 'q_secteurs', 
        type: 'multiple', 
        title: 'Secteurs d\'activité', 
        question: 'Vous-même ou quelqu\'un de votre entourage proche travaillez-vous dans l\'un des secteurs suivants ?', 
        note: 'STOP si secteur sensible', 
        options: [
            { value: 'publicite', label: 'Publicité', stop: true },
            { value: 'relations_publiques', label: 'Relations publiques', stop: true },
            { value: 'journalisme', label: 'Journalisme', stop: true },
            { value: 'etudes_marche', label: 'Études de marché', stop: true },
            { value: 'marketing', label: 'Marketing', stop: true },
            { value: 'aucun', label: 'Aucun de ces secteurs', stop: false, exclusive: true }
        ] 
    },
    participation: { 
        id: 'q_participation', 
        type: 'single', 
        title: 'Participation récente', 
        question: 'Avez-vous participé à une étude au cours des 6 derniers mois ?', 
        note: 'STOP si oui', 
        options: [
            { value: 'oui', label: 'Oui', stop: true },
            { value: 'non', label: 'Non', stop: false }
        ] 
    },
    age: { 
        id: 'q_age', 
        type: 'number', 
        title: 'Âge', 
        question: 'Quel âge avez-vous ?', 
        note: 'Ajuster STOP selon critères', 
        min: 18, 
        max: 99, 
        suffix: 'ans', 
        stopMin: 18, 
        stopMax: 65 
    },
    
    // === PROFIL SOCIODÉMOGRAPHIQUE ===
    sexe: { 
        id: 'q_sexe', 
        type: 'single', 
        title: 'Sexe', 
        question: 'Vous êtes :', 
        note: 'Pour quotas', 
        options: [
            { value: 'homme', label: 'Un homme', stop: false },
            { value: 'femme', label: 'Une femme', stop: false },
            { value: 'autre', label: 'Autre / Ne souhaite pas répondre', stop: false }
        ] 
    },
    revenus: { 
        id: 'q_revenus', 
        type: 'single', 
        title: 'Revenus', 
        question: 'Dans quelle tranche se situent les revenus annuels bruts de votre foyer ?', 
        note: '', 
        options: [
            { value: 'moins_20k', label: 'Moins de 20 000€', stop: false },
            { value: '20k_30k', label: '20 000€ - 30 000€', stop: false },
            { value: '30k_45k', label: '30 000€ - 45 000€', stop: false },
            { value: '45k_60k', label: '45 000€ - 60 000€', stop: false },
            { value: '60k_80k', label: '60 000€ - 80 000€', stop: false },
            { value: 'plus_80k', label: 'Plus de 80 000€', stop: false },
            { value: 'nsp', label: 'Je ne souhaite pas répondre', stop: false }
        ] 
    },
    profession: { 
        id: 'q_profession', 
        type: 'double_text', 
        title: 'Profession', 
        question: 'Quelle est votre profession et votre secteur d\'activité ?', 
        note: '', 
        fields: [
            { key: 'profession', label: 'Profession' },
            { key: 'secteur', label: 'Secteur d\'activité' }
        ] 
    },
    csp: { 
        id: 'q_csp', 
        type: 'single', 
        title: 'CSP', 
        question: 'Quelle est votre catégorie socioprofessionnelle ?', 
        note: 'Pour quotas', 
        options: [
            { value: 'agriculteur', label: 'Agriculteur exploitant', stop: false },
            { value: 'artisan', label: 'Artisan, commerçant, chef d\'entreprise', stop: false },
            { value: 'cadre', label: 'Cadre, profession intellectuelle supérieure', stop: false },
            { value: 'prof_intermediaire', label: 'Profession intermédiaire', stop: false },
            { value: 'employe', label: 'Employé', stop: false },
            { value: 'ouvrier', label: 'Ouvrier', stop: false },
            { value: 'retraite', label: 'Retraité', stop: false },
            { value: 'etudiant', label: 'Étudiant', stop: false },
            { value: 'sans_activite', label: 'Sans activité professionnelle', stop: false }
        ] 
    },
    region: { 
        id: 'q_region', 
        type: 'single', 
        title: 'Région', 
        question: 'Dans quelle région résidez-vous ?', 
        note: 'Pour quotas géographiques', 
        options: [
            { value: 'idf', label: 'Île-de-France', stop: false },
            { value: 'aura', label: 'Auvergne-Rhône-Alpes', stop: false },
            { value: 'hdf', label: 'Hauts-de-France', stop: false },
            { value: 'nouvelle_aquitaine', label: 'Nouvelle-Aquitaine', stop: false },
            { value: 'occitanie', label: 'Occitanie', stop: false },
            { value: 'grand_est', label: 'Grand Est', stop: false },
            { value: 'paca', label: 'Provence-Alpes-Côte d\'Azur', stop: false },
            { value: 'pdl', label: 'Pays de la Loire', stop: false },
            { value: 'bretagne', label: 'Bretagne', stop: false },
            { value: 'normandie', label: 'Normandie', stop: false },
            { value: 'bfc', label: 'Bourgogne-Franche-Comté', stop: false },
            { value: 'cvl', label: 'Centre-Val de Loire', stop: false },
            { value: 'corse', label: 'Corse', stop: false },
            { value: 'dom_tom', label: 'DOM-TOM', stop: false }
        ] 
    },
    habitat: { 
        id: 'q_habitat', 
        type: 'single', 
        title: 'Type d\'habitat', 
        question: 'Dans quel type de logement habitez-vous ?', 
        note: '', 
        options: [
            { value: 'maison', label: 'Maison individuelle', stop: false },
            { value: 'appartement', label: 'Appartement', stop: false },
            { value: 'autre', label: 'Autre', stop: false }
        ] 
    },
    
    // === FAMILLE & FOYER ===
    enfants: { 
        id: 'q_enfants', 
        type: 'single', 
        title: 'Enfants au foyer', 
        question: 'Avez-vous des enfants de moins de 18 ans vivant au foyer ?', 
        note: '', 
        options: [
            { value: 'oui', label: 'Oui', stop: false },
            { value: 'non', label: 'Non', stop: false }
        ] 
    },
    nb_enfants: { 
        id: 'q_nb_enfants', 
        type: 'number', 
        title: 'Nombre d\'enfants', 
        question: 'Combien d\'enfants avez-vous au foyer ?', 
        note: 'Affiché si enfants = oui', 
        min: 1, 
        max: 10, 
        suffix: 'enfant(s)', 
        condition: { source: 'q_enfants', operator: 'equals', value: 'oui' } 
    },
    ages_enfants: { 
        id: 'q_ages_enfants', 
        type: 'multiple', 
        title: 'Âges des enfants', 
        question: 'Dans quelle(s) tranche(s) d\'âge se situent vos enfants ?', 
        note: 'Affiché si enfants = oui', 
        condition: { source: 'q_enfants', operator: 'equals', value: 'oui' }, 
        options: [
            { value: '0_2', label: '0-2 ans', stop: false },
            { value: '3_5', label: '3-5 ans', stop: false },
            { value: '6_10', label: '6-10 ans', stop: false },
            { value: '11_14', label: '11-14 ans', stop: false },
            { value: '15_17', label: '15-17 ans', stop: false }
        ] 
    },
    situation: { 
        id: 'q_situation', 
        type: 'single', 
        title: 'Situation familiale', 
        question: 'Quelle est votre situation familiale ?', 
        note: '', 
        options: [
            { value: 'celibataire', label: 'Célibataire', stop: false },
            { value: 'couple', label: 'En couple (marié, pacsé, concubinage)', stop: false },
            { value: 'divorce', label: 'Divorcé(e) / Séparé(e)', stop: false },
            { value: 'veuf', label: 'Veuf / Veuve', stop: false }
        ] 
    },
    foyer: { 
        id: 'q_foyer', 
        type: 'single', 
        title: 'Taille du foyer', 
        question: 'Combien de personnes composent votre foyer (vous inclus) ?', 
        note: '', 
        options: [
            { value: '1', label: '1 personne (je vis seul)', stop: false },
            { value: '2', label: '2 personnes', stop: false },
            { value: '3', label: '3 personnes', stop: false },
            { value: '4', label: '4 personnes', stop: false },
            { value: '5_plus', label: '5 personnes ou plus', stop: false }
        ] 
    },
    
    // === FIN DE QUESTIONNAIRE ===
    photo: { 
        id: 'q_photo', 
        type: 'file', 
        title: 'Photo', 
        question: 'Merci de prendre une photo', 
        note: 'Vérification', 
        accept: 'image/*' 
    },
    signaletique: { 
        id: 'q_signaletique', 
        type: 'double_text', 
        title: 'Coordonnées', 
        question: 'Merci de renseigner vos coordonnées', 
        note: 'Fin du questionnaire', 
        fields: [
            { key: 'nom', label: 'Nom' },
            { key: 'prenom', label: 'Prénom' }
        ] 
    },
    commentaires: { 
        id: 'q_commentaires', 
        type: 'text', 
        title: 'Commentaires', 
        question: 'Avez-vous des commentaires ou remarques à ajouter ?', 
        note: 'Question optionnelle', 
        optional: true 
    }
};

function addTemplateQuestion(key) {
    const t = questionTemplates[key]; if (!t) return;
    let id = t.id, c = 1; while (builderQuestions.some(q => q.id === id)) { id = t.id + '_' + c++; }
    const q = JSON.parse(JSON.stringify(t)); q.id = id;
    builderQuestions.push(q); renderQuestionsList(); showToast('Question "' + q.title + '" ajoutée !');
}
function showToast(msg) { const t = document.createElement('div'); t.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg z-[100]'; t.textContent = msg; document.body.appendChild(t); setTimeout(() => t.remove(), 2000); }

function openStudyBuilder() {
    builderStep = 1; builderQuestions = []; builderQuotas = [];
    ['builder-studyId','builder-studyTitle','builder-studyDate','builder-reward','builder-duration','builder-horaires'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('builder-totalParticipants').value = '10';
    document.getElementById('builder-requireAccessId').checked = true;
    document.getElementById('builder-hideHoraires').checked = false;
    updateBuilderUI();
    document.getElementById('study-builder-modal').classList.remove('hidden');
    document.getElementById('study-builder-modal').classList.add('flex');
}
function closeStudyBuilder() { document.getElementById('study-builder-modal').classList.add('hidden'); document.getElementById('study-builder-modal').classList.remove('flex'); }
function nextBuilderStep() {
    if (builderStep === 1) { if (!document.getElementById('builder-studyId').value.trim() || !document.getElementById('builder-studyTitle').value.trim()) { alert('Remplissez l\'identifiant et le titre'); return; } }
    if (builderStep === 2 && builderQuestions.length === 0 && !confirm('Aucune question. Continuer ?')) return;
    if (builderStep < 3) { builderStep++; updateBuilderUI(); }
}
function prevBuilderStep() { if (builderStep > 1) { builderStep--; updateBuilderUI(); } }
function updateBuilderUI() {
    document.querySelectorAll('.builder-step').forEach(el => el.classList.add('hidden'));
    document.getElementById('builder-step-' + builderStep).classList.remove('hidden');
    document.getElementById('builder-progress').style.width = (builderStep * 33.33) + '%';
    document.getElementById('builder-step-label').textContent = 'Étape ' + builderStep + '/3 - ' + ['Configuration','Questions','Quotas'][builderStep-1];
    document.getElementById('builder-prev-btn').classList.toggle('hidden', builderStep === 1);
    document.getElementById('builder-next-btn').classList.toggle('hidden', builderStep === 3);
    document.getElementById('builder-create-btn').classList.toggle('hidden', builderStep !== 3);
    if (builderStep === 2) renderQuestionsList();
    if (builderStep === 3) renderQuotasList();
}

let validateTimeout;
function validateStudyId() {
    const v = document.getElementById('builder-studyId').value.trim(), s = document.getElementById('builder-studyId-status');
    if (!v) { s.textContent = 'Sera utilisé comme nom de dossier'; s.className = 'text-xs mt-1 text-gray-400'; return; }
    clearTimeout(validateTimeout);
    validateTimeout = setTimeout(async () => {
        try {
            const res = await fetch('../api/create-study.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'validate_study_id', studyId: v }) });
            const data = await res.json();
            s.textContent = data.available ? '✓ Disponible → ' + data.suggestion : '✗ Existe déjà';
            s.className = 'text-xs mt-1 ' + (data.available ? 'text-green-600' : 'text-red-600');
        } catch (e) { s.textContent = 'Erreur'; s.className = 'text-xs mt-1 text-red-600'; }
    }, 500);
}

function renderQuestionsList() {
    const list = document.getElementById('builder-questions-list'), noQ = document.getElementById('builder-no-questions');
    const countEl = document.getElementById('questions-count');
    
    if (builderQuestions.length === 0) { 
        list.innerHTML = ''; 
        noQ.classList.remove('hidden'); 
        if (countEl) countEl.textContent = '';
        return; 
    }
    
    noQ.classList.add('hidden');
    if (countEl) countEl.textContent = builderQuestions.length + ' question' + (builderQuestions.length > 1 ? 's' : '');
    
    list.innerHTML = builderQuestions.map((q, i) => `<div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
        <div class="w-8 h-8 bg-teal-100 text-teal-700 rounded-lg flex items-center justify-center text-sm font-bold">${i + 1}</div>
        <div class="flex-1 min-w-0"><p class="font-medium text-gray-800 truncate">${escHtml(q.title || q.question)}</p><p class="text-xs text-gray-400"><span class="font-mono bg-gray-200 px-1 rounded">${q.id}</span> • ${getTypeLabel(q.type)} ${q.options ? '• ' + q.options.length + ' opt.' : ''} ${q.options && q.options.some(o => o.stop) ? '• <span class="text-red-500">STOP</span>' : ''} ${q.condition ? '• <span class="text-indigo-500">🔗 Condition</span>' : ''}</p></div>
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
            <button onclick="duplicateQuestion(${i})" class="p-2 hover:bg-blue-100 rounded-lg text-blue-600" title="Dupliquer">⧉</button>
            <button onclick="editQuestion(${i})" class="p-2 hover:bg-teal-100 rounded-lg text-teal-600" title="Modifier">✎</button>
            <button onclick="moveQuestion(${i}, -1)" class="p-2 hover:bg-gray-200 rounded-lg text-gray-400 ${i === 0 ? 'invisible' : ''}" title="Monter">↑</button>
            <button onclick="moveQuestion(${i}, 1)" class="p-2 hover:bg-gray-200 rounded-lg text-gray-400 ${i === builderQuestions.length - 1 ? 'invisible' : ''}" title="Descendre">↓</button>
            <button onclick="deleteQuestion(${i})" class="p-2 hover:bg-red-100 rounded-lg text-red-500" title="Supprimer">🗑</button>
        </div>
    </div>`).join('');
}
function getTypeLabel(t) { return { single: 'Choix unique', multiple: 'Choix multiples', number: 'Nombre', text: 'Texte', double_text: 'Double texte', file: 'Fichier', single_with_text: 'Choix+texte', multiple_with_text: 'Multi+texte' }[t] || t; }

function addQuestion() {
    editingQuestionIndex = -1; questionOptions = [];
    document.getElementById('edit-question-index').value = '-1';
    document.getElementById('edit-question-id').value = 'q' + (builderQuestions.length + 1);
    ['edit-question-title','edit-question-text','edit-question-note','edit-question-min','edit-question-max','edit-question-suffix','edit-question-stopMin','edit-question-stopMax','edit-field1-label','edit-field2-label'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('edit-question-optional').checked = false;
    document.getElementById('edit-question-hasCondition').checked = false;
    document.getElementById('condition-fields').classList.add('hidden');
    selectQuestionType('single'); renderOptionsList(); populateConditionSources();
    document.getElementById('question-editor-title').textContent = 'Nouvelle question';
    document.getElementById('question-editor-modal').classList.remove('hidden');
    document.getElementById('question-editor-modal').classList.add('flex');
}
function duplicateQuestion(i) {
    const o = builderQuestions[i], c = JSON.parse(JSON.stringify(o));
    let id = c.id + '_copie', n = 1; while (builderQuestions.some(q => q.id === id)) { id = c.id + '_copie' + n++; }
    c.id = id; c.title = (c.title || '') + ' (copie)';
    builderQuestions.splice(i + 1, 0, c); renderQuestionsList(); showToast('Dupliquée !');
}
function editQuestion(i) {
    const q = builderQuestions[i]; editingQuestionIndex = i;
    document.getElementById('edit-question-index').value = i;
    document.getElementById('edit-question-id').value = q.id;
    document.getElementById('edit-question-title').value = q.title || '';
    document.getElementById('edit-question-text').value = q.question || '';
    document.getElementById('edit-question-note').value = q.note || '';
    document.getElementById('edit-question-optional').checked = q.optional || false;
    if (q.type === 'number') { document.getElementById('edit-question-min').value = q.min || ''; document.getElementById('edit-question-max').value = q.max || ''; document.getElementById('edit-question-suffix').value = q.suffix || ''; document.getElementById('edit-question-stopMin').value = q.stopMin || ''; document.getElementById('edit-question-stopMax').value = q.stopMax || ''; }
    if (q.type === 'double_text' && q.fields) { document.getElementById('edit-field1-label').value = q.fields[0]?.label || ''; document.getElementById('edit-field2-label').value = q.fields[1]?.label || ''; }
    if (q.type === 'file') { document.getElementById('edit-question-accept').value = q.accept || 'image/*'; }
    questionOptions = q.options ? JSON.parse(JSON.stringify(q.options)) : [];
    selectQuestionType(q.type); renderOptionsList(); populateConditionSources(i);
    // Charger condition
    if (q.condition) {
        document.getElementById('edit-question-hasCondition').checked = true;
        document.getElementById('condition-fields').classList.remove('hidden');
        document.getElementById('edit-condition-source').value = q.condition.source || '';
        document.getElementById('edit-condition-operator').value = q.condition.operator || 'equals';
        updateConditionValues();
        setTimeout(() => {
            const srcQ = builderQuestions.find(x => x.id === q.condition.source);
            if (srcQ && srcQ.type === 'number') { document.getElementById('edit-condition-value-text').value = q.condition.value || ''; }
            else { document.getElementById('edit-condition-value-select').value = q.condition.value || ''; }
            updateConditionPreview();
        }, 50);
    } else {
        document.getElementById('edit-question-hasCondition').checked = false;
        document.getElementById('condition-fields').classList.add('hidden');
    }
    document.getElementById('question-editor-title').textContent = 'Modifier la question';
    document.getElementById('question-editor-modal').classList.remove('hidden');
    document.getElementById('question-editor-modal').classList.add('flex');
}

function toggleConditionOptions() {
    const checked = document.getElementById('edit-question-hasCondition').checked;
    document.getElementById('condition-fields').classList.toggle('hidden', !checked);
    if (checked) populateConditionSources();
}

function populateConditionSources(excludeIndex = -1) {
    const select = document.getElementById('edit-condition-source');
    const currentVal = select.value;
    select.innerHTML = '<option value="">-- Sélectionner --</option>';
    builderQuestions.forEach((q, i) => {
        if (i !== excludeIndex && i !== editingQuestionIndex) {
            select.innerHTML += `<option value="${q.id}">${q.id} - ${escHtml((q.title || q.question).substring(0, 40))}</option>`;
        }
    });
    if (currentVal) select.value = currentVal;
}

function updateConditionValues() {
    const sourceId = document.getElementById('edit-condition-source').value;
    const selectEl = document.getElementById('edit-condition-value-select');
    const textEl = document.getElementById('edit-condition-value-text');
    const operatorEl = document.getElementById('edit-condition-operator');
    
    selectEl.innerHTML = '<option value="">-- Sélectionner --</option>';
    
    if (!sourceId) { selectEl.classList.remove('hidden'); textEl.classList.add('hidden'); updateConditionPreview(); return; }
    
    const srcQ = builderQuestions.find(q => q.id === sourceId);
    if (!srcQ) { updateConditionPreview(); return; }
    
    if (srcQ.type === 'number') {
        selectEl.classList.add('hidden');
        textEl.classList.remove('hidden');
        operatorEl.innerHTML = '<option value="equals">est égal à</option><option value="not_equals">est différent de</option><option value="greater">est supérieur à</option><option value="less">est inférieur à</option>';
    } else if (srcQ.options && srcQ.options.length > 0) {
        selectEl.classList.remove('hidden');
        textEl.classList.add('hidden');
        srcQ.options.forEach(o => {
            selectEl.innerHTML += `<option value="${escHtml(o.value)}">${escHtml(o.label)}</option>`;
        });
        if (['single', 'single_with_text'].includes(srcQ.type)) {
            operatorEl.innerHTML = '<option value="equals">est égal à</option><option value="not_equals">est différent de</option>';
        } else {
            operatorEl.innerHTML = '<option value="contains">contient</option><option value="not_contains">ne contient pas</option>';
        }
    } else {
        selectEl.classList.add('hidden');
        textEl.classList.remove('hidden');
        operatorEl.innerHTML = '<option value="equals">est égal à</option><option value="not_equals">est différent de</option>';
    }
    updateConditionPreview();
}

function updateConditionPreview() {
    const sourceId = document.getElementById('edit-condition-source').value;
    const operator = document.getElementById('edit-condition-operator').value;
    const valueSelect = document.getElementById('edit-condition-value-select');
    const valueText = document.getElementById('edit-condition-value-text');
    const preview = document.getElementById('condition-preview');
    
    if (!sourceId) { preview.textContent = ''; return; }
    
    const srcQ = builderQuestions.find(q => q.id === sourceId);
    const value = valueSelect.classList.contains('hidden') ? valueText.value : valueSelect.options[valueSelect.selectedIndex]?.text || '';
    const opText = { equals: '=', not_equals: '≠', contains: 'contient', not_contains: 'ne contient pas', greater: '>', less: '<' }[operator] || operator;
    
    preview.textContent = srcQ ? `📋 Si "${srcQ.title || srcQ.id}" ${opText} "${value}"` : '';
}

// Ajouter listeners pour mise à jour preview
document.addEventListener('DOMContentLoaded', () => {
    ['edit-condition-source', 'edit-condition-operator', 'edit-condition-value-select'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', updateConditionPreview);
    });
    document.getElementById('edit-condition-value-text')?.addEventListener('input', updateConditionPreview);
});
function closeQuestionEditor() { document.getElementById('question-editor-modal').classList.add('hidden'); document.getElementById('question-editor-modal').classList.remove('flex'); }
function selectQuestionType(t) {
    document.getElementById('edit-question-type').value = t;
    document.querySelectorAll('.type-btn').forEach(b => { b.classList.toggle('border-teal-500', b.dataset.type === t); b.classList.toggle('bg-teal-50', b.dataset.type === t); });
    document.getElementById('number-options').classList.toggle('hidden', t !== 'number');
    document.getElementById('double-text-options').classList.toggle('hidden', t !== 'double_text');
    document.getElementById('file-options').classList.toggle('hidden', t !== 'file');
    document.getElementById('choices-options').classList.toggle('hidden', !['single', 'multiple', 'single_with_text', 'multiple_with_text'].includes(t));
}
function renderOptionsList() {
    const list = document.getElementById('options-list'), empty = document.getElementById('options-empty');
    if (questionOptions.length === 0) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');
    list.innerHTML = questionOptions.map((o, i) => `<div class="grid grid-cols-12 gap-2 p-2 bg-gray-50 rounded-lg items-center">
        <div class="col-span-12 md:col-span-3"><input type="text" value="${escHtml(o.value)}" onchange="updateOption(${i}, 'value', this.value)" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm font-mono" placeholder="valeur"></div>
        <div class="col-span-12 md:col-span-5"><input type="text" value="${escHtml(o.label)}" onchange="updateOption(${i}, 'label', this.value)" oninput="generateValue(${i}, this.value)" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" placeholder="Texte affiché"></div>
        <div class="col-span-6 md:col-span-2 flex items-center justify-center"><label class="flex items-center gap-1 cursor-pointer"><input type="checkbox" ${o.stop ? 'checked' : ''} onchange="updateOption(${i}, 'stop', this.checked)" class="w-4 h-4 text-red-500 rounded"><span class="text-xs text-red-600">STOP</span></label></div>
        <div class="col-span-6 md:col-span-2 flex items-center justify-center gap-1"><label class="flex items-center gap-1 cursor-pointer" title="Exclusif"><input type="checkbox" ${o.exclusive ? 'checked' : ''} onchange="updateOption(${i}, 'exclusive', this.checked)" class="w-3 h-3 rounded"><span class="text-xs text-gray-500">Excl.</span></label><button onclick="removeOption(${i})" class="p-1 hover:bg-red-100 rounded text-red-500 text-sm ml-1">✕</button></div>
    </div>`).join('');
}
function addOption() { questionOptions.push({ value: '', label: '', stop: false, exclusive: false }); renderOptionsList(); setTimeout(() => { const inputs = document.querySelectorAll('#options-list input[placeholder="Texte affiché"]'); if (inputs.length > 0) inputs[inputs.length - 1].focus(); }, 50); }
function updateOption(i, f, v) { questionOptions[i][f] = v; }
function generateValue(i, label) {
    const vi = document.querySelectorAll('#options-list input[placeholder="valeur"]')[i];
    if (vi && !vi.dataset.manual) {
        const v = label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').substring(0, 30);
        questionOptions[i].value = v; vi.value = v;
    }
}
function removeOption(i) { questionOptions.splice(i, 1); renderOptionsList(); }

function saveQuestion() {
    const id = document.getElementById('edit-question-id').value.trim(), type = document.getElementById('edit-question-type').value, question = document.getElementById('edit-question-text').value.trim();
    if (!id) { alert('Renseignez un ID'); return; }
    if (!question) { alert('Renseignez la question'); return; }
    if (builderQuestions.some((q, i) => q.id === id && i !== editingQuestionIndex)) { alert('ID déjà utilisé'); return; }
    const q = { id, type, title: document.getElementById('edit-question-title').value.trim(), question, note: document.getElementById('edit-question-note').value.trim(), optional: document.getElementById('edit-question-optional').checked };
    if (type === 'number') { const min = document.getElementById('edit-question-min').value, max = document.getElementById('edit-question-max').value; if (min !== '') q.min = parseInt(min); if (max !== '') q.max = parseInt(max); q.suffix = document.getElementById('edit-question-suffix').value; const sMin = document.getElementById('edit-question-stopMin').value, sMax = document.getElementById('edit-question-stopMax').value; if (sMin !== '') q.stopMin = parseInt(sMin); if (sMax !== '') q.stopMax = parseInt(sMax); }
    if (type === 'double_text') { q.fields = [{ key: 'field1', label: document.getElementById('edit-field1-label').value.trim() || 'Champ 1' },{ key: 'field2', label: document.getElementById('edit-field2-label').value.trim() || 'Champ 2' }]; }
    if (type === 'file') { q.accept = document.getElementById('edit-question-accept').value; }
    if (['single', 'multiple', 'single_with_text', 'multiple_with_text'].includes(type)) {
        const valid = questionOptions.filter(o => o.label.trim());
        valid.forEach(o => { if (!o.value.trim()) { o.value = o.label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').substring(0, 30); } });
        q.options = valid;
        if (q.options.length === 0) { alert('Ajoutez au moins une option'); return; }
    }
    // Sauvegarder condition
    if (document.getElementById('edit-question-hasCondition').checked) {
        const source = document.getElementById('edit-condition-source').value;
        const operator = document.getElementById('edit-condition-operator').value;
        const valueSelect = document.getElementById('edit-condition-value-select');
        const valueText = document.getElementById('edit-condition-value-text');
        const value = valueSelect.classList.contains('hidden') ? valueText.value : valueSelect.value;
        if (source && value) {
            q.condition = { source, operator, value };
        }
    }
    if (editingQuestionIndex >= 0) { builderQuestions[editingQuestionIndex] = q; showToast('Modifiée !'); } else { builderQuestions.push(q); showToast('Ajoutée !'); }
    closeQuestionEditor(); renderQuestionsList();
}
function deleteQuestion(i) { if (confirm('Supprimer ?')) { builderQuestions.splice(i, 1); renderQuestionsList(); } }
function moveQuestion(i, d) { const n = i + d; if (n < 0 || n >= builderQuestions.length) return; const t = builderQuestions[i]; builderQuestions[i] = builderQuestions[n]; builderQuestions[n] = t; renderQuestionsList(); }

function previewQuestion() {
    const type = document.getElementById('edit-question-type').value, qText = document.getElementById('edit-question-text').value || 'Votre question...';
    let html = `<p class="font-medium text-gray-800 mb-4">${escHtml(qText)}</p>`;
    if (['single', 'single_with_text'].includes(type)) { html += '<div class="space-y-2">'; questionOptions.forEach(o => { html += `<label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50"><input type="radio" name="preview" class="w-4 h-4"><span>${escHtml(o.label || 'Option')}</span>${o.stop ? '<span class="text-xs text-red-500 ml-auto">→ STOP</span>' : ''}</label>`; }); html += '</div>'; }
    else if (['multiple', 'multiple_with_text'].includes(type)) { html += '<div class="space-y-2">'; questionOptions.forEach(o => { html += `<label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50"><input type="checkbox" class="w-4 h-4 rounded"><span>${escHtml(o.label || 'Option')}</span>${o.stop ? '<span class="text-xs text-red-500 ml-auto">→ STOP</span>' : ''}</label>`; }); html += '</div>'; }
    else if (type === 'number') { const s = document.getElementById('edit-question-suffix').value; html += `<div class="flex items-center gap-2"><input type="number" class="w-32 px-3 py-2 border rounded-lg" placeholder="0">${s ? `<span class="text-gray-500">${escHtml(s)}</span>` : ''}</div>`; }
    else if (type === 'text') { html += `<textarea class="w-full px-3 py-2 border rounded-lg" rows="3" placeholder="Votre réponse..."></textarea>`; }
    else if (type === 'double_text') { const l1 = document.getElementById('edit-field1-label').value || 'Champ 1', l2 = document.getElementById('edit-field2-label').value || 'Champ 2'; html += `<div class="space-y-3"><div><label class="block text-sm text-gray-600 mb-1">${escHtml(l1)}</label><input type="text" class="w-full px-3 py-2 border rounded-lg"></div><div><label class="block text-sm text-gray-600 mb-1">${escHtml(l2)}</label><input type="text" class="w-full px-3 py-2 border rounded-lg"></div></div>`; }
    else if (type === 'file') { html += `<div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center"><p class="text-gray-400">📷 Cliquez pour télécharger</p></div>`; }
    document.getElementById('question-preview-content').innerHTML = html;
    document.getElementById('question-preview-modal').classList.remove('hidden');
    document.getElementById('question-preview-modal').classList.add('flex');
}
function closePreviewQuestion() { document.getElementById('question-preview-modal').classList.add('hidden'); document.getElementById('question-preview-modal').classList.remove('flex'); }

function renderQuotasList() {
    const availableContainer = document.getElementById('builder-quotas-available');
    const configuredContainer = document.getElementById('builder-quotas-configured');
    const noQuotasMsg = document.getElementById('builder-no-quotas');
    
    // Filtrer les questions éligibles aux quotas (single/multiple avec options)
    const eligibleQuestions = builderQuestions.filter(q => 
        ['single', 'multiple', 'single_with_text', 'multiple_with_text'].includes(q.type) && 
        q.options && q.options.length > 0
    );
    
    if (eligibleQuestions.length === 0) {
        availableContainer.innerHTML = '';
        configuredContainer.innerHTML = '';
        noQuotasMsg.classList.remove('hidden');
        return;
    }
    
    noQuotasMsg.classList.add('hidden');
    
    // Séparer les questions avec et sans quota
    const questionsWithQuota = eligibleQuestions.filter(q => builderQuotas.some(qt => qt.source === q.id));
    const questionsWithoutQuota = eligibleQuestions.filter(q => !builderQuotas.some(qt => qt.source === q.id));
    
    // Afficher les questions disponibles (sans quota)
    if (questionsWithoutQuota.length > 0) {
        availableContainer.innerHTML = `
            <div class="mb-2 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-600">Questions disponibles</span>
                <span class="text-xs text-gray-400">(cliquez pour ajouter un quota)</span>
            </div>
            <div class="flex flex-wrap gap-2">
                ${questionsWithoutQuota.map(q => `
                    <button onclick="addQuotaFromQuestion('${q.id}')" 
                            class="group flex items-center gap-2 px-4 py-2 bg-white border-2 border-dashed border-gray-300 rounded-lg hover:border-teal-400 hover:bg-teal-50 transition">
                        <span class="text-lg opacity-50 group-hover:opacity-100">📊</span>
                        <span class="text-sm text-gray-600 group-hover:text-teal-700">${escHtml(q.title || q.id)}</span>
                        <span class="text-xs text-gray-400">(${q.options.length} options)</span>
                    </button>
                `).join('')}
            </div>
        `;
    } else {
        availableContainer.innerHTML = '';
    }
    
    // Afficher les quotas configurés
    if (builderQuotas.length > 0) {
        configuredContainer.innerHTML = `
            <div class="mb-3 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-600">✓ Quotas configurés</span>
                <span class="px-2 py-0.5 bg-teal-100 text-teal-700 rounded-full text-xs">${builderQuotas.length}</span>
            </div>
            ${builderQuotas.map((quota, i) => {
                const question = builderQuestions.find(q => q.id === quota.source);
                const totalObjectif = quota.criteres.reduce((sum, c) => sum + (c.objectif || 0), 0);
                const targetTotal = parseInt(document.getElementById('builder-totalParticipants')?.value) || 10;
                
                return `
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="px-4 py-3 bg-gradient-to-r from-teal-50 to-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">📊</span>
                            <div>
                                <p class="font-medium text-gray-800">${escHtml(quota.titre)}</p>
                                <p class="text-xs text-gray-400">Question : ${escHtml(quota.source)}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-gray-400">Total objectifs</p>
                                <p class="font-semibold ${totalObjectif === targetTotal ? 'text-green-600' : totalObjectif > targetTotal ? 'text-red-600' : 'text-amber-600'}">${totalObjectif} / ${targetTotal}</p>
                            </div>
                            <button onclick="deleteQuota(${i})" class="p-2 hover:bg-red-100 rounded-lg text-red-500 transition" title="Supprimer ce quota">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid gap-2">
                            ${quota.criteres.map((c, ci) => `
                                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-700">${escHtml(c.label)}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400">Objectif :</span>
                                        <input type="number" 
                                               value="${c.objectif || ''}" 
                                               onchange="updateQuotaObjectif(${i}, ${ci}, this.value)"
                                               class="w-20 px-3 py-1.5 border border-gray-200 rounded-lg text-center text-sm focus:border-teal-400 focus:ring-1 focus:ring-teal-200 outline-none" 
                                               placeholder="—"
                                               min="0">
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                            <button onclick="distributeQuotaEvenly(${i})" class="text-xs text-teal-600 hover:text-teal-800 hover:underline">
                                ⚖️ Répartir également
                            </button>
                        </div>
                    </div>
                </div>
                `;
            }).join('')}
        `;
    } else {
        configuredContainer.innerHTML = '';
    }
}

function addQuotaFromQuestion(qid) {
    const q = builderQuestions.find(x => x.id === qid);
    if (!q) { alert('Question non trouvée'); return; }
    if (builderQuotas.some(x => x.source === qid)) { alert('Quota déjà existant pour cette question'); return; }
    
    const quota = { 
        id: 'quota_' + qid, 
        titre: q.title || q.question?.substring(0, 40) || q.id, 
        source: qid, 
        criteres: q.options ? q.options.map(o => ({ valeur: o.value, label: o.label, objectif: null })) : [] 
    };
    
    builderQuotas.push(quota); 
    renderQuotasList(); 
    showToast('✓ Quota ajouté !');
}

function updateQuotaObjectif(qi, ci, v) { 
    builderQuotas[qi].criteres[ci].objectif = v ? parseInt(v) : null; 
    renderQuotasList(); // Re-render pour mettre à jour le total
}

function deleteQuota(i) { 
    if (confirm('Supprimer ce quota ?')) {
        builderQuotas.splice(i, 1); 
        renderQuotasList(); 
        showToast('Quota supprimé');
    }
}

function distributeQuotaEvenly(qi) {
    const targetTotal = parseInt(document.getElementById('builder-totalParticipants')?.value) || 10;
    const quota = builderQuotas[qi];
    const count = quota.criteres.length;
    
    if (count === 0) return;
    
    const perCritere = Math.floor(targetTotal / count);
    const remainder = targetTotal % count;
    
    quota.criteres.forEach((c, i) => {
        c.objectif = perCritere + (i < remainder ? 1 : 0);
    });
    
    renderQuotasList();
    showToast('Objectifs répartis également');
}

async function createStudy() {
    const studyId = document.getElementById('builder-studyId').value.trim(), studyTitle = document.getElementById('builder-studyTitle').value.trim();
    if (!studyId || !studyTitle) { alert('Remplissez identifiant et titre'); builderStep = 1; updateBuilderUI(); return; }
    if (builderQuestions.length === 0 && !confirm('Aucune question. Continuer ?')) { builderStep = 2; updateBuilderUI(); return; }
    const horaires = document.getElementById('builder-horaires').value.trim().split(',').map(h => h.trim()).filter(h => h);
    const config = { studyId, studyTitle, studyDate: document.getElementById('builder-studyDate').value.trim(), reward: document.getElementById('builder-reward').value.trim(), duration: document.getElementById('builder-duration').value.trim(), totalParticipants: parseInt(document.getElementById('builder-totalParticipants').value) || 10, horaires, hideHoraires: document.getElementById('builder-hideHoraires').checked, requireAccessId: document.getElementById('builder-requireAccessId').checked, questions: builderQuestions, quotas: builderQuotas };
    try {
        document.getElementById('builder-create-btn').disabled = true;
        document.getElementById('builder-create-btn').innerHTML = '<span class="animate-pulse">Création...</span>';
        const res = await fetch('../api/create-study.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'create_study', config }) });
        const data = await res.json();
        if (data.success) { closeStudyBuilder(); showToast('✓ Étude créée !'); loadData(); setTimeout(() => { if (confirm('Étude "' + studyTitle + '" créée !\n\nY accéder ?')) goToStudy(studyId); }, 500); }
        else alert('Erreur: ' + (data.error || 'Échec'));
    } catch (e) { alert('Erreur: ' + e.message); }
    finally { document.getElementById('builder-create-btn').disabled = false; document.getElementById('builder-create-btn').innerHTML = '✓ Créer l\'étude'; }
}
function escHtml(s) { return s ? String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : ''; }
</script>