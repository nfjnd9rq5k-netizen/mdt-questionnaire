/**
 * ============================================================
 * MOTEUR DE QUESTIONNAIRE - La Maison du Test
 * Version: 2.0 avec tracking comportemental anti-bot/IA
 * ============================================================
 */

class QuestionnaireEngine {
    constructor(config) {
        this.config = config;
        this.currentStep = -1;
        this.answers = {};
        this.signaletique = {};
        this.selectedHoraire = '';
        this.isDisqualified = false;
        this.disqualifyReason = '';
        this.stopReasons = [];  
        this.questionnaireCompleted = false;  
        this.isProcessing = false;  
        this.container = document.getElementById('app');
        this.responseId = null;
        this.startTime = new Date().toISOString();
        
        this.apiUrl = this.getApiUrl();
        
        // ============================================================
        // TRACKING COMPORTEMENTAL ANTI-BOT/IA
        // ============================================================
        this.behaviorMetrics = {
            sessionStart: Date.now(),
            questionStartTime: null,
            timePerQuestion: {},
            pasteEvents: 0,
            pasteDetails: [],
            tabSwitches: 0,
            focusLostCount: 0,
            focusLostDuration: 0,
            lastFocusLost: null,
            keystrokes: 0,
            backspaces: 0,
            typingIntervals: [],
            lastKeystroke: null,
            mouseMovements: 0,
            scrollEvents: 0,
            totalCharactersTyped: 0,
            questionInteractions: {}
        };
        
        this.initBehaviorTracking();
        this.addAnimationStyles();
    }

    // ============================================================
    // INITIALISATION DU TRACKING
    // ============================================================
    initBehaviorTracking() {
        // Détection copier-coller
        document.addEventListener('paste', (e) => {
            this.behaviorMetrics.pasteEvents++;
            this.behaviorMetrics.pasteDetails.push({
                timestamp: Date.now(),
                questionId: this.getCurrentQuestionId(),
                textLength: (e.clipboardData?.getData('text') || '').length
            });
        });

        // Détection changement d'onglet / perte de focus
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.behaviorMetrics.tabSwitches++;
                this.behaviorMetrics.focusLostCount++;
                this.behaviorMetrics.lastFocusLost = Date.now();
            } else if (this.behaviorMetrics.lastFocusLost) {
                this.behaviorMetrics.focusLostDuration += Date.now() - this.behaviorMetrics.lastFocusLost;
                this.behaviorMetrics.lastFocusLost = null;
            }
        });

        // Tracking clavier global
        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            this.behaviorMetrics.keystrokes++;
            
            if (e.key === 'Backspace' || e.key === 'Delete') {
                this.behaviorMetrics.backspaces++;
            }
            
            // Calcul intervalle entre frappes
            if (this.behaviorMetrics.lastKeystroke) {
                const interval = now - this.behaviorMetrics.lastKeystroke;
                if (interval < 2000) { // Ignore les pauses > 2s
                    this.behaviorMetrics.typingIntervals.push(interval);
                }
            }
            this.behaviorMetrics.lastKeystroke = now;
        });

        // Tracking souris
        document.addEventListener('mousemove', () => {
            this.behaviorMetrics.mouseMovements++;
        });

        // Tracking scroll
        document.addEventListener('scroll', () => {
            this.behaviorMetrics.scrollEvents++;
        });
    }

    getCurrentQuestionId() {
        if (this.currentStep >= 0 && this.currentStep < this.config.questions.length) {
            return this.config.questions[this.currentStep].id;
        }
        return 'unknown';
    }

    startQuestionTimer() {
        this.behaviorMetrics.questionStartTime = Date.now();
        const qId = this.getCurrentQuestionId();
        this.behaviorMetrics.questionInteractions[qId] = {
            startTime: Date.now(),
            keystrokes: 0,
            backspaces: 0,
            pasteEvents: 0,
            mouseClicks: 0
        };
    }

    endQuestionTimer() {
        if (this.behaviorMetrics.questionStartTime) {
            const qId = this.getCurrentQuestionId();
            const timeSpent = (Date.now() - this.behaviorMetrics.questionStartTime) / 1000;
            this.behaviorMetrics.timePerQuestion[qId] = timeSpent;
        }
    }

    // Calculer les métriques finales
    calculateFinalMetrics() {
        const intervals = this.behaviorMetrics.typingIntervals;
        const avgTypingSpeed = intervals.length > 0 
            ? intervals.reduce((a, b) => a + b, 0) / intervals.length 
            : 0;
        
        const backspaceRatio = this.behaviorMetrics.keystrokes > 0
            ? this.behaviorMetrics.backspaces / this.behaviorMetrics.keystrokes
            : 0;

        const totalTime = (Date.now() - this.behaviorMetrics.sessionStart) / 1000;
        const questionTimes = Object.values(this.behaviorMetrics.timePerQuestion);
        const avgTimePerQuestion = questionTimes.length > 0
            ? questionTimes.reduce((a, b) => a + b, 0) / questionTimes.length
            : 0;

        // Score de confiance (0-100)
        // Plus le score est élevé, plus c'est probablement un humain
        let trustScore = 100;
        
        // Pénalités
        if (this.behaviorMetrics.pasteEvents > 5) trustScore -= 15;
        if (this.behaviorMetrics.pasteEvents > 10) trustScore -= 20;
        if (backspaceRatio < 0.02) trustScore -= 10; // Trop peu d'erreurs = suspect
        if (backspaceRatio > 0.3) trustScore -= 5; // Trop d'erreurs aussi
        if (avgTypingSpeed < 50) trustScore -= 10; // Frappe trop rapide (< 50ms)
        if (this.behaviorMetrics.mouseMovements < 50) trustScore -= 10;
        if (totalTime < 300) trustScore -= 20; // Moins de 5 min = très suspect
        if (this.behaviorMetrics.tabSwitches > 20) trustScore -= 10;
        
        trustScore = Math.max(0, Math.min(100, trustScore));

        return {
            sessionDuration: Math.round(totalTime),
            timePerQuestion: this.behaviorMetrics.timePerQuestion,
            avgTimePerQuestion: Math.round(avgTimePerQuestion),
            pasteEvents: this.behaviorMetrics.pasteEvents,
            pasteDetails: this.behaviorMetrics.pasteDetails,
            tabSwitches: this.behaviorMetrics.tabSwitches,
            focusLostCount: this.behaviorMetrics.focusLostCount,
            focusLostDuration: Math.round(this.behaviorMetrics.focusLostDuration / 1000),
            totalKeystrokes: this.behaviorMetrics.keystrokes,
            backspaceRatio: Math.round(backspaceRatio * 100) / 100,
            avgTypingInterval: Math.round(avgTypingSpeed),
            mouseMovements: this.behaviorMetrics.mouseMovements,
            scrollEvents: this.behaviorMetrics.scrollEvents,
            trustScore: trustScore
        };
    }

    getApiUrl() {
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('/studies/')) {
            const parts = currentPath.split('/');
            const studiesIndex = parts.indexOf('studies');
            if (studiesIndex !== -1) {
                const basePath = parts.slice(0, studiesIndex).join('/');
                return basePath + '/api/save.php';
            }
        }
        
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        return basePath + '/api/save.php';
    }

    async init() {
        if (this.config.requireAccessId) {
            this.renderAccessLogin();
        } else {
            this.renderSignaletique();
        }
    }
    
    renderAccessLogin() {
        this.container.innerHTML = `
            <div class="header">
                <h1>${this.config.studyTitle}</h1>
                <p class="subtitle">${this.config.studyDate}</p>
            </div>

            <div class="card">
                <div class="access-login">
                    <div class="access-icon">🔐</div>
                    <h2>Accès au questionnaire</h2>
                    <p style="color: #64748b; margin-bottom: 24px;">
                        Veuillez entrer votre identifiant pour accéder au questionnaire.
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label">Votre identifiant <span class="required">*</span></label>
                        <input type="text" class="form-input" id="access-id" placeholder="Entrez votre identifiant" autocomplete="off">
                        <div id="access-error" class="access-error" style="display: none;"></div>
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="btn-access" disabled>
                        Accéder au questionnaire →
                    </button>
                </div>
            </div>
        `;
        
        this.bindAccessEvents();
    }
    
    bindAccessEvents() {
        const input = document.getElementById('access-id');
        const btn = document.getElementById('btn-access');
        const errorDiv = document.getElementById('access-error');
        
        input.addEventListener('input', () => {
            btn.disabled = input.value.trim().length === 0;
            errorDiv.style.display = 'none';
        });
        
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !btn.disabled) {
                btn.click();
            }
        });
        
        btn.addEventListener('click', async () => {
            const accessId = input.value.trim();
            btn.disabled = true;
            btn.textContent = 'Vérification...';
            
            try {
                const response = await fetch(this.apiUrl.replace('save.php', 'check-access.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        studyId: this.config.studyId,
                        accessId: accessId
                    })
                });
                
                const result = await response.json();
                
                if (result.success && result.valid) {
                    this.participantAccessId = accessId;
                    
                    if (result.hasProgress && result.progress) {
                        this.restoreProgress(result.progress, result.isCompleted);
                    } else {
                        this.renderSignaletique();
                    }
                } else {
                    errorDiv.textContent = result.message || 'Identifiant non reconnu.';
                    errorDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Accéder au questionnaire →';
                }
            } catch (error) {
                errorDiv.textContent = 'Erreur de connexion. Veuillez réessayer.';
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Accéder au questionnaire →';
            }
        });
    }
    
    restoreProgress(progress, isCompleted) {
        this.responseId = progress.id;
        this.signaletique = progress.signaletique || {};
        this.selectedHoraire = progress.horaire || '';
        this.answers = progress.reponses || {};
        this.startTime = progress.dateDebut || new Date().toISOString();
        
        if (isCompleted) {
            this.container.innerHTML = `
                <div class="header">
                    <h1>${this.config.studyTitle}</h1>
                </div>

                <div class="card">
                    <div class="access-login">
                        <div class="access-icon">✅</div>
                        <h2>Questionnaire déjà complété</h2>
                        <p style="color: #64748b;">
                            Bonjour <strong>${this.signaletique.prenom || ''}</strong> !
                            Vous avez déjà répondu à ce questionnaire. Merci !
                        </p>
                    </div>
                </div>
            `;
            return;
        }
        
        const answeredQuestions = Object.keys(this.answers);
        let resumeStep = 0;
        
        for (let i = 0; i < this.config.questions.length; i++) {
            const q = this.config.questions[i];
            if (q.showIf && !q.showIf(this.answers)) continue;
            if (!answeredQuestions.includes(q.id)) {
                resumeStep = i;
                break;
            }
        }
        
        this.currentStep = resumeStep;
        this.renderQuestion();
    }

    renderSignaletique() {
        const { studyTitle, studyDate, horaires, hideHoraires, horaireMessage, anonymousMode } = this.config;
        
        let horairesSection = '';
        if (hideHoraires) {
            if (horaireMessage) {
                horairesSection = `
                    <div class="card">
                        <div class="info-message">
                            <span class="info-icon">📅</span>
                            <p>${horaireMessage}</p>
                        </div>
                    </div>
                `;
            }
        } else if (horaires && horaires.length > 0) {
            horairesSection = `
                <div class="card">
                    <div class="form-group">
                        <label class="form-label">Proposition d'horaires <span class="required">*</span></label>
                        <div class="horaires-grid" id="horaires-grid">
                            ${horaires.map(h => `
                                <button type="button" class="horaire-btn" data-horaire="${h}">${h}</button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }
        
        let signaletiqueContent = '';
        if (anonymousMode) {
            signaletiqueContent = `
                <h2 class="section-title">👤 Avant de commencer</h2>
                
                <div class="info-message" style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px; margin-bottom: 20px; border-radius: 0 8px 8px 0;">
                    <p style="margin: 0; color: #166534;">
                        🔒 <strong>Étude anonyme</strong> - Vos réponses ne seront pas associées à votre identité. 
                        Nous avons juste besoin d'un pseudonyme et d'un email pour vous envoyer votre compensation.
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pseudonyme ou Prénom <span class="required">*</span></label>
                    <input type="text" class="form-input" id="sig-prenom" placeholder="Comment devons-nous vous appeler ?">
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" class="form-input" id="sig-email" placeholder="Pour recevoir votre compensation">
                    <small style="color: #64748b; margin-top: 4px; display: block;">Uniquement pour l'envoi de votre bon d'achat</small>
                </div>
            `;
        } else {
            signaletiqueContent = `
                <h2 class="section-title">📋 Signalétique</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom <span class="required">*</span></label>
                        <input type="text" class="form-input" id="sig-nom" placeholder="Dupont">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" class="form-input" id="sig-prenom" placeholder="Marie">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse complète <span class="required">*</span></label>
                    <input type="text" class="form-input" id="sig-adresse" placeholder="12 rue de la Paix">
                </div>

                <div class="form-group">
                    <label class="form-label">Code / Interphone / Bâtiment / Étage</label>
                    <input type="text" class="form-input" id="sig-code" placeholder="Bât A, 3ème étage">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Code Postal <span class="required">*</span></label>
                        <input type="text" class="form-input" id="sig-cp" placeholder="75001" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ville <span class="required">*</span></label>
                        <input type="text" class="form-input" id="sig-ville" placeholder="Paris">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone mobile <span class="required">*</span></label>
                    <input type="tel" class="form-input" id="sig-tel" placeholder="06 12 34 56 78">
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" class="form-input" id="sig-email" placeholder="marie.dupont@email.com">
                </div>
            `;
        }
        
        this.container.innerHTML = `
            <div class="header">
                <h1>${studyTitle}</h1>
                <p class="subtitle">${studyDate}</p>
            </div>

            ${horairesSection}

            <div class="card">
                ${signaletiqueContent}

                <button type="button" class="btn btn-primary" id="btn-start" disabled>
                    Commencer le questionnaire →
                </button>
            </div>
        `;

        this.bindSignaletiqueEvents();
    }

    bindSignaletiqueEvents() {
        if (!this.config.hideHoraires) {
            document.querySelectorAll('.horaire-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.horaire-btn').forEach(b => b.classList.remove('selected'));
                    e.target.classList.add('selected');
                    this.selectedHoraire = e.target.dataset.horaire;
                    this.checkSignaletiqueComplete();
                });
            });
        } else {
            this.selectedHoraire = 'À définir';
        }

        const fields = this.config.anonymousMode 
            ? ['prenom', 'email'] 
            : ['nom', 'prenom', 'adresse', 'code', 'cp', 'ville', 'tel', 'email'];
        
        fields.forEach(field => {
            const input = document.getElementById(`sig-${field}`);
            if (input) {
                input.addEventListener('input', () => this.checkSignaletiqueComplete());
            }
        });

        document.getElementById('btn-start').addEventListener('click', async () => {
            const btn = document.getElementById('btn-start');
            const email = document.getElementById('sig-email').value.trim().toLowerCase();
            
            // Mode anonyme avec protection anti-doublons
            if (this.config.anonymousMode) {
                // 1. Vérifier le localStorage
                const storageKey = `participated_${this.config.studyId}`;
                if (localStorage.getItem(storageKey)) {
                    alert('Vous avez déjà participé à cette étude depuis ce navigateur.');
                    return;
                }
                
                // 2. Vérifier l'email en base
                btn.disabled = true;
                btn.textContent = 'Vérification...';
                
                try {
                    const checkResponse = await fetch(this.apiUrl.replace('save.php', 'check-duplicate.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, studyId: this.config.studyId })
                    });
                    const checkResult = await checkResponse.json();
                    
                    if (checkResult.exists) {
                        alert('Cet email a déjà participé à cette étude. Merci de votre intérêt !');
                        btn.disabled = false;
                        btn.textContent = 'Commencer';
                        return;
                    }
                } catch (e) {
                    // Vérification anti-doublon non disponible
                }
                
                btn.textContent = 'Commencer';
                
                // 3. Stocker le marqueur localStorage
                localStorage.setItem(storageKey, JSON.stringify({
                    email: email,
                    date: new Date().toISOString()
                }));
                
                this.signaletique = {
                    nom: '',
                    prenom: document.getElementById('sig-prenom').value.trim(),
                    adresse: '',
                    code: '',
                    codePostal: '',
                    ville: '',
                    telephone: '',
                    email: email
                };
            } else {
                this.signaletique = {
                    nom: document.getElementById('sig-nom').value.trim(),
                    prenom: document.getElementById('sig-prenom').value.trim(),
                    adresse: document.getElementById('sig-adresse').value.trim(),
                    code: document.getElementById('sig-code').value.trim(),
                    codePostal: document.getElementById('sig-cp').value.trim(),
                    ville: document.getElementById('sig-ville').value.trim(),
                    telephone: document.getElementById('sig-tel').value.trim(),
                    email: document.getElementById('sig-email').value.trim()
                };
            }
            
            const result = await this.sendToServer('save', this.getAllData());
            if (result.id) {
                this.responseId = result.id;
            }
            
            this.currentStep = 0;
            this.startQuestionTimer();
            this.renderQuestion();
        });
    }

    checkSignaletiqueComplete() {
        let isComplete = false;
        const horaireOk = this.config.hideHoraires || this.selectedHoraire;
        
        if (this.config.anonymousMode) {
            const prenom = document.getElementById('sig-prenom').value.trim();
            const email = document.getElementById('sig-email').value.trim();
            isComplete = prenom && email && horaireOk;
        } else {
            const nom = document.getElementById('sig-nom').value.trim();
            const prenom = document.getElementById('sig-prenom').value.trim();
            const adresse = document.getElementById('sig-adresse').value.trim();
            const cp = document.getElementById('sig-cp').value.trim();
            const ville = document.getElementById('sig-ville').value.trim();
            const tel = document.getElementById('sig-tel').value.trim();
            const email = document.getElementById('sig-email').value.trim();
            isComplete = nom && prenom && adresse && cp && ville && tel && email && horaireOk;
        }
        
        document.getElementById('btn-start').disabled = !isComplete;
    }

    async sendToServer(action, data) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...data })
            });
            
            return await response.json();
        } catch (error) {
            console.error('Erreur serveur:', error);
            this.saveLocally();
            return { success: false, error: error.message };
        }
    }

    saveLocally() {
        const data = this.getAllData();
        const key = `backup_${this.config.studyId}_${Date.now()}`;
        localStorage.setItem(key, JSON.stringify(data));
    }

    getAllData() {
        let statut = 'EN_COURS';
        if (this.questionnaireCompleted) {
            statut = this.isDisqualified ? 'REFUSE' : 'QUALIFIE';
        }
        
        return {
            studyId: this.config.studyId,
            accessId: this.participantAccessId || null,
            signaletique: this.signaletique,
            horaire: this.selectedHoraire,
            reponses: this.answers,
            statut: statut,
            raisonStop: this.disqualifyReason,
            toutesRaisonsStop: this.stopReasons,
            dateDebut: this.startTime,
            behaviorMetrics: this.calculateFinalMetrics()
        };
    }

    goBack() {
        const currentQuestion = this.config.questions[this.currentStep];
        if (currentQuestion && this.answers[currentQuestion.id]) {
            delete this.answers[currentQuestion.id];
        }
        
        this.currentStep--;
        
        while (this.currentStep >= 0) {
            const question = this.config.questions[this.currentStep];
            if (!question.showIf || question.showIf(this.answers)) break;
            if (this.answers[question.id]) delete this.answers[question.id];
            this.currentStep--;
        }
        
        if (this.currentStep >= 0) {
            const targetQuestion = this.config.questions[this.currentStep];
            if (targetQuestion && this.answers[targetQuestion.id]) {
                delete this.answers[targetQuestion.id];
            }
        }
        
        this.isDisqualified = false;
        this.disqualifyReason = '';
        this.stopReasons = [];
        
        if (this.currentStep < 0) this.currentStep = 0;
        
        this.startQuestionTimer();
        this.renderQuestion();
    }

    renderQuestion() {
        if (this.currentStep >= this.config.questions.length) {
            this.renderCompleted();
            return;
        }

        const question = this.config.questions[this.currentStep];
        
        if (question.showIf && typeof question.showIf === 'function') {
            if (!question.showIf(this.answers)) {
                this.currentStep++;
                this.renderQuestion();
                return;
            }
        }

        // Type "info" = écran d'information sans réponse
        if (question.type === 'info') {
            this.renderInfoScreen(question);
            return;
        }
        
        const progress = ((this.currentStep + 1) / this.config.questions.length) * 100;

        // Déterminer le titre et la question à afficher
        const questionTitle = question.question || question.title || '';
        const hasSubtitle = question.title && question.question && question.title !== question.question;
        
        // Afficher le texte descriptif si présent
        const textContent = question.text ? `<div class="question-description">${question.text}</div>` : '';

        this.container.innerHTML = `
            <div class="progress-container">
                <div class="progress-info">
                    <span>Question ${this.currentStep + 1} / ${this.config.questions.length}</span>
                    <span>${Math.round(progress)}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress}%"></div>
                </div>
            </div>

            <div class="card">
                ${hasSubtitle ? `<p class="question-subtitle">${question.title}</p>` : ''}
                <h2 class="question-title">${questionTitle}</h2>
                ${textContent}
                ${question.note ? `<p class="question-note">${question.note}</p>` : ''}

                <div id="question-content">
                    ${this.renderQuestionContent(question)}
                </div>

                <button type="button" class="btn btn-primary" id="btn-continue" ${question.optional ? '' : 'disabled'}>
                    Continuer →
                </button>

                ${this.currentStep > 0 ? `
                    <button type="button" class="btn btn-secondary" id="btn-back">
                        ← Question précédente
                    </button>
                ` : ''}
            </div>

            <p class="participant-footer">
                Participant : ${this.signaletique.prenom || ''}${this.signaletique.nom ? ' ' + this.signaletique.nom : ''}
            </p>
        `;

        this.startQuestionTimer();
        this.bindQuestionEvents(question);
    }

    renderInfoScreen(question) {
        const progress = ((this.currentStep + 1) / this.config.questions.length) * 100;

        this.container.innerHTML = `
            <div class="progress-container">
                <div class="progress-info">
                    <span>Partie ${this.currentStep + 1} / ${this.config.questions.length}</span>
                    <span>${Math.round(progress)}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress}%"></div>
                </div>
            </div>

            <div class="card">
                <div class="info-screen">
                    <h2 class="info-title">${question.title || ''}</h2>
                    <div class="info-content">${question.question || question.text || ''}</div>
                </div>

                <button type="button" class="btn btn-primary" id="btn-continue">
                    Continuer →
                </button>

                ${this.currentStep > 0 ? `
                    <button type="button" class="btn btn-secondary" id="btn-back">
                        ← Retour
                    </button>
                ` : ''}
            </div>
        `;

        document.getElementById('btn-continue').addEventListener('click', () => {
            this.currentStep++;
            this.renderQuestion();
        });

        const backBtn = document.getElementById('btn-back');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.goBack());
        }
    }

    renderQuestionContent(question) {
        switch (question.type) {
            case 'single':
            case 'radio':
            case 'single_with_text':
                return this.renderSingleChoice(question);
            case 'multiple':
            case 'multiple_with_text':
                return this.renderMultipleChoice(question);
            case 'number':
                return this.renderNumberInput(question);
            case 'text':
            case 'textarea':
                return this.renderTextInput(question);
            case 'double_text':
                return this.renderDoubleText(question);
            case 'matrix':
                return this.renderMatrix(question);
            case 'file':
                return this.renderFileUpload(question);
            default:
                // Si la question a des options, c'est probablement un choix single
                if (question.options && question.options.length > 0) {
                    return this.renderSingleChoice(question);
                }
                return this.renderTextInput(question);
        }
    }

    renderSingleChoice(question) {
        return `
            <div class="options-list">
                ${question.options.map(opt => `
                    <div class="option-item">
                        <label class="option-label" data-value="${opt.value}">
                            <input type="radio" name="q-${question.id}" value="${opt.value}" 
                                   class="option-input" data-stop="${opt.stop || false}">
                            <span class="option-text">${opt.label}</span>
                        </label>
                        ${opt.needsText ? `
                            <div class="option-extra-input" style="display: none;" data-for="${opt.value}">
                                <input type="text" placeholder="${opt.textLabel || 'Préciser'}" 
                                       class="extra-text" data-option="${opt.value}">
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderMultipleChoice(question) {
        return `
            <div class="options-list">
                ${question.options.map(opt => `
                    <div class="option-item">
                        <label class="option-label" data-value="${opt.value}" data-exclusive="${opt.exclusive || false}">
                            <input type="checkbox" value="${opt.value}" 
                                   class="option-input" data-stop="${opt.stop || false}" 
                                   data-exclusive="${opt.exclusive || false}">
                            <span class="option-text">${opt.label}</span>
                        </label>
                        ${opt.needsText ? `
                            <div class="option-extra-input" style="display: none;" data-for="${opt.value}">
                                <input type="text" placeholder="${opt.textLabel || 'Préciser'}" 
                                       class="extra-text" data-option="${opt.value}">
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderNumberInput(question) {
        return `
            <div class="number-input-group">
                <input type="number" class="number-input" id="number-value" 
                       min="${question.min || 0}" max="${question.max || 999}" placeholder="__">
                <span class="number-suffix">${question.suffix || ''}</span>
            </div>
        `;
    }

    renderTextInput(question) {
        const minWordsHint = question.minWords ? `Minimum ${question.minWords} mots` : '';
        const minLengthHint = question.minLength && !question.minWords ? `Minimum ${question.minLength} caractères` : '';
        const hint = minWordsHint || minLengthHint || '';
        
        return `
            <div class="text-input-group">
                <textarea class="text-input" id="text-value" rows="5" 
                          placeholder="${question.placeholder || 'Votre réponse...'}"
                          ${question.maxLength ? `maxlength="${question.maxLength}"` : ''}></textarea>
                ${hint ? `<small class="char-hint">${hint}</small>` : ''}
            </div>
        `;
    }

    renderDoubleText(question) {
        return `
            <div class="double-text-group">
                ${question.fields.map(field => `
                    <div class="form-group">
                        <label class="form-label">${field.label}</label>
                        <input type="text" class="form-input double-text-input" 
                               data-key="${field.key}" placeholder="${field.placeholder || field.label}">
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderMatrix(question) {
        return `
            <div class="matrix-container">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th></th>
                            ${question.columns.map(col => `<th>${col}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${question.rows.map((row, rowIdx) => `
                            <tr>
                                <td>${row.label || row}</td>
                                ${question.columns.map((_, colIdx) => `
                                    <td>
                                        <input type="radio" name="matrix-col-${colIdx}" 
                                               data-col="${colIdx}" data-row="${rowIdx}">
                                    </td>
                                `).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    renderFileUpload(question) {
        return `
            <div class="file-upload-container">
                <input type="file" id="file-input" accept="${question.accept || 'image/*'}" style="display:none;">
                <button type="button" class="btn-upload" id="btn-upload">
                    <span>📷 Ajouter une photo</span>
                </button>
                <div id="file-preview" style="display: none;">
                    <img id="preview-image" src="" alt="Aperçu">
                    <button type="button" class="btn btn-secondary btn-sm" id="remove-file">✕ Supprimer</button>
                </div>
            </div>
        `;
    }

    bindQuestionEvents(question) {
        const continueBtn = document.getElementById('btn-continue');
        const backBtn = document.getElementById('btn-back');

        if (backBtn) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.goBack();
            });
        }

        continueBtn.addEventListener('click', () => this.handleContinue(question));

        switch (question.type) {
            case 'single':
            case 'radio':
            case 'single_with_text':
                this.bindSingleEvents(question);
                break;
            case 'multiple':
            case 'multiple_with_text':
                this.bindMultipleEvents(question);
                break;
            case 'number':
                this.bindNumberEvents(question);
                break;
            case 'text':
            case 'textarea':
                this.bindTextEvents(question);
                break;
            case 'double_text':
                this.bindDoubleTextEvents(question);
                break;
            case 'matrix':
                this.bindMatrixEvents(question);
                break;
            case 'file':
                this.bindFileEvents(question);
                break;
            default:
                // Si la question a des options, c'est un choix single
                if (question.options && question.options.length > 0) {
                    this.bindSingleEvents(question);
                } else {
                    this.bindTextEvents(question);
                }
        }
    }

    bindSingleEvents(question) {
        const radios = document.querySelectorAll(`input[name="q-${question.id}"]`);
        const continueBtn = document.getElementById('btn-continue');

        radios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('.option-label').forEach(l => l.classList.remove('selected'));
                e.target.closest('.option-label').classList.add('selected');

                document.querySelectorAll('.option-extra-input').forEach(el => el.style.display = 'none');
                const extraInput = document.querySelector(`.option-extra-input[data-for="${e.target.value}"]`);
                if (extraInput) extraInput.style.display = 'block';

                continueBtn.disabled = false;
            });
        });
    }

    bindMultipleEvents(question) {
        const checkboxes = document.querySelectorAll('.option-input[type="checkbox"]');
        const continueBtn = document.getElementById('btn-continue');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const isExclusive = e.target.dataset.exclusive === 'true';
                
                if (isExclusive && e.target.checked) {
                    checkboxes.forEach(cb => {
                        if (cb !== e.target) {
                            cb.checked = false;
                            cb.closest('.option-label').classList.remove('selected');
                        }
                    });
                } else if (!isExclusive && e.target.checked) {
                    checkboxes.forEach(cb => {
                        if (cb.dataset.exclusive === 'true') {
                            cb.checked = false;
                            cb.closest('.option-label').classList.remove('selected');
                        }
                    });
                }

                if (e.target.checked) {
                    e.target.closest('.option-label').classList.add('selected');
                    const extraInput = document.querySelector(`.option-extra-input[data-for="${e.target.value}"]`);
                    if (extraInput) extraInput.style.display = 'block';
                } else {
                    e.target.closest('.option-label').classList.remove('selected');
                    const extraInput = document.querySelector(`.option-extra-input[data-for="${e.target.value}"]`);
                    if (extraInput) extraInput.style.display = 'none';
                }

                const hasSelection = Array.from(checkboxes).some(cb => cb.checked);
                continueBtn.disabled = !hasSelection;
            });
        });
    }

    bindNumberEvents(question) {
        const input = document.getElementById('number-value');
        const continueBtn = document.getElementById('btn-continue');

        input.addEventListener('input', () => {
            continueBtn.disabled = !input.value;
        });
    }

    bindTextEvents(question) {
        const textarea = document.getElementById('text-value');
        const continueBtn = document.getElementById('btn-continue');
        const charHint = document.querySelector('.char-hint');

        const updateValidation = () => {
            const text = textarea.value.trim();
            const charCount = text.length;
            const wordCount = text.split(/\s+/).filter(w => w.length > 0).length;
            
            const minLength = question.minLength || 1;
            const minWords = question.minWords || 0;
            
            let isValid = true;
            let hintText = '';
            
            if (minWords > 0) {
                isValid = wordCount >= minWords;
                hintText = `${wordCount}/${minWords} mots minimum`;
            } else if (minLength > 1) {
                isValid = charCount >= minLength;
                hintText = `${charCount}/${minLength} caractères minimum`;
            }
            
            if (charHint) {
                charHint.textContent = hintText;
                charHint.style.color = isValid ? '#22c55e' : '#94a3b8';
            }
            
            continueBtn.disabled = !isValid && !question.optional;
            
            // Track characters typed
            this.behaviorMetrics.totalCharactersTyped = charCount;
        };

        textarea.addEventListener('input', updateValidation);
        
        if (question.optional) {
            continueBtn.disabled = false;
        }
        
        updateValidation();
    }

    bindDoubleTextEvents(question) {
        const inputs = document.querySelectorAll('.double-text-input');
        const continueBtn = document.getElementById('btn-continue');

        const checkComplete = () => {
            if (question.optional) {
                continueBtn.disabled = false;
                return;
            }
            const firstInput = inputs[0];
            const firstFilled = firstInput && firstInput.value.trim();
            continueBtn.disabled = !firstFilled;
        };

        inputs.forEach(input => input.addEventListener('input', checkComplete));
        checkComplete();
    }

    bindMatrixEvents(question) {
        const radios = document.querySelectorAll('.matrix-table input[type="radio"]');
        const continueBtn = document.getElementById('btn-continue');

        const checkComplete = () => {
            const columns = question.columns.length;
            const answered = new Set();
            radios.forEach(r => {
                if (r.checked) answered.add(r.dataset.col);
            });
            continueBtn.disabled = answered.size < columns;
        };

        radios.forEach(r => r.addEventListener('change', checkComplete));
    }

    bindFileEvents(question) {
        const fileInput = document.getElementById('file-input');
        const uploadBtn = document.getElementById('btn-upload');
        const preview = document.getElementById('file-preview');
        const previewImg = document.getElementById('preview-image');
        const removeBtn = document.getElementById('remove-file');
        const continueBtn = document.getElementById('btn-continue');

        uploadBtn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.selectedFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    uploadBtn.style.display = 'none';
                    continueBtn.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', () => {
            this.selectedFile = null;
            fileInput.value = '';
            preview.style.display = 'none';
            uploadBtn.style.display = 'block';
            if (!question.optional) continueBtn.disabled = true;
        });

        if (question.optional) continueBtn.disabled = false;
    }

    async handleContinue(question) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        const continueBtn = document.getElementById('btn-continue');
        continueBtn.disabled = true;
        continueBtn.textContent = 'Enregistrement...';

        try {
            this.endQuestionTimer();

            let answer = { questionId: question.id };
            let shouldStop = false;
            let stopReason = '';

            switch (question.type) {
                case 'single':
                case 'radio':
                case 'single_with_text': {
                    const selected = document.querySelector(`input[name="q-${question.id}"]:checked`);
                    if (!selected && !question.optional) {
                        alert('Veuillez sélectionner une réponse');
                        this.isProcessing = false;
                        continueBtn.disabled = false;
                        continueBtn.textContent = 'Continuer →';
                        return;
                    }
                    if (selected) {
                        answer.value = selected.value;
                        shouldStop = selected.dataset.stop === 'true';
                        const option = question.options.find(o => o.value === selected.value);
                        stopReason = `${question.title}: ${option?.label}`;
                        
                        const extraInput = document.querySelector(`.extra-text[data-option="${selected.value}"]`);
                        if (extraInput) answer.extraText = extraInput.value;
                    }
                    break;
                }

                case 'multiple':
                case 'multiple_with_text': {
                    const checked = document.querySelectorAll('.option-input[type="checkbox"]:checked');
                    answer.values = Array.from(checked).map(cb => cb.value);
                    answer.extraTexts = {};
                    
                    // Vérifier stopIfEmpty - si aucune sélection et stopIfEmpty est activé
                    if (answer.values.length === 0 && question.stopIfEmpty) {
                        shouldStop = true;
                        stopReason = question.stopReason || `${question.title}: Aucune sélection`;
                    }
                    
                    checked.forEach(cb => {
                        if (cb.dataset.stop === 'true') {
                            shouldStop = true;
                            const option = question.options.find(o => o.value === cb.value);
                            stopReason = `${question.title}: ${option?.label}`;
                        }
                        const extraInput = document.querySelector(`.extra-text[data-option="${cb.value}"]`);
                        if (extraInput && extraInput.value) {
                            answer.extraTexts[cb.value] = extraInput.value;
                        }
                    });
                    break;
                }

                case 'number': {
                    const value = parseInt(document.getElementById('number-value').value);
                    answer.value = value;
                    
                    if (question.validation) {
                        const validation = question.validation(value);
                        shouldStop = validation.stop;
                        stopReason = validation.reason || `${question.title}: ${value}`;
                    }
                    break;
                }

                case 'text':
                case 'textarea': {
                    const text = document.getElementById('text-value').value.trim();
                    const words = text.split(/\s+/).filter(w => w.length > 0);
                    answer.value = text;
                    answer.metrics = {
                        char_count: text.length,
                        word_count: words.length,
                        unique_words: new Set(words.map(w => w.toLowerCase())).size,
                        sentence_count: (text.match(/[.!?]+/g) || []).length,
                        avg_word_length: words.length > 0 ? Math.round(words.join('').length / words.length * 10) / 10 : 0,
                        has_punctuation: /[.,!?;:]/.test(text)
                    };
                    break;
                }

                case 'double_text': {
                    const inputs = document.querySelectorAll('.double-text-input');
                    answer.values = {};
                    inputs.forEach(input => {
                        answer.values[input.dataset.key] = input.value.trim();
                    });
                    break;
                }

                case 'matrix': {
                    const radios = document.querySelectorAll('.matrix-table input[type="radio"]:checked');
                    answer.matrix = {};
                    radios.forEach(r => {
                        answer.matrix[r.dataset.col] = parseInt(r.dataset.row);
                    });
                    break;
                }

                case 'file': {
                    if (this.selectedFile) {
                        const base64 = await this.fileToBase64(this.selectedFile);
                        answer.file = {
                            name: this.selectedFile.name,
                            type: this.selectedFile.type,
                            size: this.selectedFile.size,
                            data: base64
                        };
                    }
                    break;
                }
            }

            this.answers[question.id] = answer;
            await this.saveToServer();

            if (shouldStop) {
                this.isDisqualified = true;
                if (!this.disqualifyReason) this.disqualifyReason = stopReason;
                this.stopReasons.push({
                    question: question.id,
                    raison: stopReason,
                    timestamp: new Date().toISOString()
                });
            }

            this.currentStep++;
            this.renderQuestion();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            this.isProcessing = false;
        }
    }

    async saveToServer() {
        const data = this.getAllData();
        
        if (this.responseId) {
            await this.sendToServer('update', { id: this.responseId, ...data });
        } else {
            const result = await this.sendToServer('save', data);
            if (result.id) this.responseId = result.id;
        }
    }

    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    async renderCompleted() {
        this.questionnaireCompleted = true;
        await this.saveToServer();
        
        const metrics = this.calculateFinalMetrics();
        
        this.container.innerHTML = `
            <div class="card">
                <div class="result-screen">
                    <div class="result-icon success">✅</div>
                    <h2 class="result-title">Merci pour votre participation !</h2>
                    <p class="result-message">
                        Merci ${this.signaletique.prenom} d'avoir pris le temps de répondre.
                        <br><br>
                        Vos réponses ont bien été enregistrées.
                        <br><br>
                        <strong>Votre compensation vous sera envoyée par email sous 48h après validation.</strong>
                    </p>
                    ${this.config.showMetrics ? `
                        <div class="metrics-debug" style="margin-top: 20px; padding: 15px; background: #f1f5f9; border-radius: 8px; font-size: 0.8rem; text-align: left;">
                            <strong>🔍 Métriques (debug) :</strong><br>
                            Temps total : ${Math.round(metrics.sessionDuration / 60)} min<br>
                            Score confiance : ${metrics.trustScore}/100<br>
                            Copier-coller : ${metrics.pasteEvents}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    addAnimationStyles() {
        if (!document.getElementById('engine-styles')) {
            const style = document.createElement('style');
            style.id = 'engine-styles';
            style.textContent = `
                .question-subtitle { color: #64748b; font-size: 0.9rem; margin-bottom: 8px; }
                .question-note { color: #64748b; font-size: 0.85rem; font-style: italic; margin-top: 8px; }
                .question-description { 
                    color: #475569; 
                    font-size: 0.95rem; 
                    line-height: 1.7; 
                    margin-bottom: 20px;
                    padding: 16px;
                    background: #f8fafc;
                    border-radius: 8px;
                    border-left: 4px solid #0d9488;
                }
                .question-description p { margin: 8px 0; }
                .question-description strong { color: #0f172a; }
                .info-screen { text-align: center; padding: 20px 0; }
                .info-title { color: #0f172a; margin-bottom: 16px; }
                .info-content { color: #475569; line-height: 1.7; }
                .text-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; resize: vertical; min-height: 120px; }
                .text-input:focus { border-color: #0d9488; outline: none; }
                .char-hint { color: #94a3b8; font-size: 0.8rem; margin-top: 8px; display: block; }
                .result-icon.success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); }
            `;
            document.head.appendChild(style);
        }
    }
}

window.QuestionnaireEngine = QuestionnaireEngine;
