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
        
        this.addAnimationStyles();
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
                    errorDiv.textContent = result.message || 'Identifiant non reconnu. Veuillez vérifier votre identifiant.';
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
                    <p class="subtitle">${this.config.studyDate}</p>
                </div>

                <div class="card">
                    <div class="access-login">
                        <div class="access-icon">✅</div>
                        <h2>Questionnaire déjà complété</h2>
                        <p style="color: #64748b; margin-bottom: 16px;">
                            Bonjour <strong>${this.signaletique.prenom || ''} ${this.signaletique.nom || ''}</strong> !
                        </p>
                        <p style="color: #64748b; margin-bottom: 24px;">
                            Vous avez déjà répondu à ce questionnaire. Merci de votre participation !
                        </p>
                        <p style="background: #f0fdfa; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem;">
                            📧 Si vous avez des questions, contactez-nous.
                        </p>
                    </div>
                </div>
            `;
            return;
        }
        
        const answeredQuestions = Object.keys(this.answers);
        let resumeStep = 0;
        let foundUnanswered = false;
        
        for (let i = 0; i < this.config.questions.length; i++) {
            const q = this.config.questions[i];
            
            if (q.showIf && !q.showIf(this.answers)) {
                continue;
            }
            
            if (!answeredQuestions.includes(q.id)) {
                resumeStep = i;
                foundUnanswered = true;
                break;
            }
        }
        
        if (!foundUnanswered) {
            resumeStep = this.config.questions.length;
        }
        
        let visibleAnswered = 0;
        let totalVisible = 0;
        for (let i = 0; i < this.config.questions.length; i++) {
            const q = this.config.questions[i];
            if (q.showIf && !q.showIf(this.answers)) continue;
            totalVisible++;
            if (answeredQuestions.includes(q.id)) visibleAnswered++;
        }
        
        this.container.innerHTML = `
            <div class="header">
                <h1>${this.config.studyTitle}</h1>
                <p class="subtitle">${this.config.studyDate}</p>
            </div>

            <div class="card">
                <div class="access-login">
                    <div class="access-icon">📋</div>
                    <h2>Questionnaire en cours</h2>
                    <p style="color: #64748b; margin-bottom: 16px;">
                        Bonjour <strong>${this.signaletique.prenom || ''} ${this.signaletique.nom || ''}</strong> !
                    </p>
                    <p style="color: #64748b; margin-bottom: 24px;">
                        Vous avez déjà commencé ce questionnaire. Voulez-vous reprendre là où vous vous êtes arrêté(e) ?
                    </p>
                    <p style="background: #f0fdfa; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem;">
                        📊 Progression : <strong>${visibleAnswered}</strong> question(s) répondue(s) sur <strong>${totalVisible}</strong>
                    </p>
                    
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary" id="btn-resume">
                            ▶️ Reprendre le questionnaire
                        </button>
                        <button type="button" class="btn btn-secondary" id="btn-restart">
                            🔄 Recommencer à zéro
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('btn-resume').addEventListener('click', () => {
            this.currentStep = resumeStep;
            if (this.currentStep >= this.config.questions.length) {
                this.renderCompleted();
            } else {
                this.renderQuestion();
            }
        });
        
        document.getElementById('btn-restart').addEventListener('click', () => {
            if (confirm('Êtes-vous sûr(e) de vouloir recommencer ? Toutes vos réponses précédentes seront effacées.')) {
                this.answers = {};
                this.isDisqualified = false;
                this.disqualifyReason = '';
                this.stopReasons = [];
                this.currentStep = 0;
                this.renderSignaletique();
            }
        });
    }

    async sendToServer(action, data) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action,
                    ...data
                })
            });
            
            const result = await response.json();
            return result;
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
            dateDebut: this.startTime
        };
    }

    renderSignaletique() {
        const { studyTitle, studyDate, reward, duration, horaires, hideHoraires, horaireMessage } = this.config;
        
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
        
        this.container.innerHTML = `
            <div class="header">
                <h1>${studyTitle}</h1>
                <p class="subtitle">${studyDate}</p>
            </div>

            ${horairesSection}

            <div class="card">
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
                    <input type="text" class="form-input" id="sig-code" placeholder="Bât A, 3ème étage, code 1234">
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

        const fields = ['nom', 'prenom', 'adresse', 'code', 'cp', 'ville', 'tel', 'email'];
        fields.forEach(field => {
            const input = document.getElementById(`sig-${field}`);
            if (input) {
                input.addEventListener('input', () => this.checkSignaletiqueComplete());
            }
        });

        document.getElementById('btn-start').addEventListener('click', async () => {
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
            
            const result = await this.sendToServer('save', this.getAllData());
            if (result.id) {
                this.responseId = result.id;
            }
            
            this.currentStep = 0;
            this.renderQuestion();
        });
    }

    checkSignaletiqueComplete() {
        const nom = document.getElementById('sig-nom').value.trim();
        const prenom = document.getElementById('sig-prenom').value.trim();
        const adresse = document.getElementById('sig-adresse').value.trim();
        const cp = document.getElementById('sig-cp').value.trim();
        const ville = document.getElementById('sig-ville').value.trim();
        const tel = document.getElementById('sig-tel').value.trim();
        const email = document.getElementById('sig-email').value.trim();

        const horaireOk = this.config.hideHoraires || this.selectedHoraire;
        const isComplete = nom && prenom && adresse && cp && ville && tel && email && horaireOk;
        document.getElementById('btn-start').disabled = !isComplete;
    }

    goBack() {
        const currentQuestion = this.config.questions[this.currentStep];
        if (currentQuestion && this.answers[currentQuestion.id]) {
            delete this.answers[currentQuestion.id];
        }
        
        this.currentStep--;
        
        while (this.currentStep >= 0) {
            const question = this.config.questions[this.currentStep];
            
            if (!question.showIf || question.showIf(this.answers)) {
                break;
            }
            
            if (this.answers[question.id]) {
                delete this.answers[question.id];
            }
            
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
        
        if (this.currentStep < 0) {
            this.currentStep = 0;
        }
        
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
        
        const progress = ((this.currentStep + 1) / this.config.questions.length) * 100;

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
                <h2 class="question-title">${question.question}</h2>

                <div id="question-content">
                    ${this.renderQuestionContent(question)}
                </div>

                <button type="button" class="btn btn-primary" id="btn-continue" ${question.type === 'file' && question.optional ? '' : 'disabled'}>
                    Continuer
                </button>

                ${this.currentStep > 0 ? `
                    <button type="button" class="btn btn-secondary" id="btn-back">
                        ← Question précédente
                    </button>
                ` : ''}
            </div>

            <p class="participant-footer">
                Participant : ${this.signaletique.prenom} ${this.signaletique.nom}
            </p>
        `;

        this.bindQuestionEvents(question);
    }

    renderQuestionContent(question) {
        switch (question.type) {
            case 'single':
            case 'single_with_text':
                return this.renderSingleChoice(question);
            case 'multiple':
            case 'multiple_with_text':
            case 'multiple_with_brands':
                return this.renderMultipleChoice(question);
            case 'number':
                return this.renderNumberInput(question);
            case 'double_text':
                return this.renderDoubleText(question);
            case 'matrix':
                return this.renderMatrix(question);
            case 'file':
                return this.renderFileUpload(question);
            default:
                return this.renderSingleChoice(question);
        }
    }

    renderSingleChoice(question) {
        const imageHtml = question.image ? `
            <div class="question-image" style="margin-bottom: 20px; text-align: center;">
                <img src="${question.image}" alt="${question.imageAlt || 'Image de référence'}" 
                     style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
        ` : '';
        
        return `
            ${imageHtml}
            <div class="options-list">
                ${question.options.map((opt, idx) => `
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
            ${question.needsExactValue ? `
                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">${question.exactValueLabel}</label>
                    <input type="text" class="form-input" id="exact-value">
                </div>
            ` : ''}
        `;
    }

    renderMultipleChoice(question) {
        return `
            <div class="options-list">
                ${question.options.map((opt, idx) => `
                    <div class="option-item">
                        <label class="option-label" data-value="${opt.value}" data-exclusive="${opt.exclusive || false}">
                            <input type="checkbox" value="${opt.value}" 
                                   class="option-input" data-stop="${opt.stop || false}" 
                                   data-exclusive="${opt.exclusive || false}">
                            <span class="option-text">${opt.label}</span>
                        </label>
                        ${opt.needsText || opt.needsBrand ? `
                            <div class="option-extra-input" style="display: none;" data-for="${opt.value}">
                                <input type="text" placeholder="${opt.needsBrand ? 'Quelle marque ?' : (opt.textLabel || 'Préciser')}" 
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
                <span class="number-suffix">${question.suffix || 'ans'}</span>
            </div>
        `;
    }

    renderDoubleText(question) {
        return `
            <div style="margin-top: 20px;">
                ${question.fields.map(field => `
                    <div class="form-group">
                        <label class="form-label">${field.label}</label>
                        <input type="text" class="form-input double-text-input" 
                               data-key="${field.key}" placeholder="${field.label}">
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
                                               data-col="${colIdx}" data-row="${rowIdx}"
                                               data-stop="${row.stopCols ? row.stopCols.includes(colIdx) : false}">
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
                <!-- Input pour la caméra (mobile) -->
                <input type="file" id="file-input-camera" 
                       accept="image/*" 
                       capture="environment" 
                       style="display:none;">
                       
                <!-- Input pour la galerie -->
                <input type="file" id="file-input-gallery" 
                       accept="image/*" 
                       style="display:none;">
                
                <div class="file-upload-buttons" id="upload-buttons">
                    <button type="button" class="btn-upload" id="btn-take-photo">
                        <span class="btn-upload-icon">📸</span>
                        <span class="btn-upload-text">Prendre une photo</span>
                    </button>
                    <button type="button" class="btn-upload btn-upload-secondary" id="btn-choose-gallery">
                        <span class="btn-upload-icon">🖼️</span>
                        <span class="btn-upload-text">Galerie</span>
                    </button>
                </div>
                
                <div id="file-preview" class="file-preview" style="display: none;">
                    <img id="preview-image" src="" alt="Aperçu">
                    <button type="button" class="btn btn-secondary btn-sm" id="remove-file">✕ Supprimer</button>
                </div>
                
                <p class="file-upload-hint">${question.optional ? '(Optionnel)' : '(Obligatoire)'}</p>
            </div>
            <style>
                .file-upload-container { margin: 20px 0; }
                .file-upload-buttons {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    margin-bottom: 20px;
                }
                .btn-upload {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                    padding: 16px 24px;
                    border: 2px solid #0d9488;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
                    color: white;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    -webkit-tap-highlight-color: transparent;
                }
                .btn-upload:active {
                    transform: scale(0.98);
                    background: #0f766e;
                }
                .btn-upload-secondary {
                    background: white;
                    color: #0d9488;
                }
                .btn-upload-secondary:active {
                    background: #f0fdfa;
                }
                .btn-upload-icon { font-size: 24px; }
                .btn-upload-text { font-size: 1rem; }
                .file-upload-hint { 
                    font-size: 0.85rem; 
                    color: #94a3b8; 
                    text-align: center;
                    margin-top: 10px;
                }
                .file-preview {
                    margin-top: 20px;
                    text-align: center;
                }
                .file-preview img {
                    max-width: 100%;
                    max-height: 300px;
                    border-radius: 8px;
                    margin-bottom: 10px;
                }
                .btn-sm { padding: 8px 16px; font-size: 0.85rem; }
                
                @media (min-width: 600px) {
                    .file-upload-buttons {
                        flex-direction: row;
                        justify-content: center;
                    }
                    .btn-upload {
                        min-width: 200px;
                    }
                }
            </style>
        `;
    }

    bindQuestionEvents(question) {
        const continueBtn = document.getElementById('btn-continue');
        const backBtn = document.getElementById('btn-back');

        if (backBtn) {
            backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.goBack();
            });
        }

        continueBtn.addEventListener('click', () => this.handleContinue(question));

        switch (question.type) {
            case 'single':
            case 'single_with_text':
                this.bindSingleEvents(question);
                break;
            case 'multiple':
            case 'multiple_with_text':
            case 'multiple_with_brands':
                this.bindMultipleEvents(question);
                break;
            case 'number':
                this.bindNumberEvents(question);
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
        const continueBtn = document.getElementById('btn-continue');
        const radios = document.querySelectorAll('.matrix-table input[type="radio"]');

        const checkComplete = () => {
            const selectedCols = new Set();
            radios.forEach(r => {
                if (r.checked) selectedCols.add(r.dataset.col);
            });
            continueBtn.disabled = selectedCols.size !== question.columns.length;
        };

        radios.forEach(radio => radio.addEventListener('change', checkComplete));
    }

    bindFileEvents(question) {
        const btnTakePhoto = document.getElementById('btn-take-photo');
        const btnChooseGallery = document.getElementById('btn-choose-gallery');
        const fileInputCamera = document.getElementById('file-input-camera');
        const fileInputGallery = document.getElementById('file-input-gallery');
        const uploadButtons = document.getElementById('upload-buttons');
        const preview = document.getElementById('file-preview');
        const previewImage = document.getElementById('preview-image');
        const removeBtn = document.getElementById('remove-file');
        const continueBtn = document.getElementById('btn-continue');

        if (!fileInputCamera || !fileInputGallery) {
            console.error('File inputs not found');
            return;
        }

        this.selectedFile = null;

        const handleFile = (file) => {
            if (file) {
                this.handleFileSelect(file, question, uploadButtons, preview, previewImage, continueBtn);
            }
        };

        btnTakePhoto.addEventListener('click', () => {
            fileInputCamera.click();
        });

        btnChooseGallery.addEventListener('click', () => {
            fileInputGallery.click();
        });

        fileInputCamera.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        fileInputGallery.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectedFile = null;
            previewImage.src = '';
            preview.style.display = 'none';
            uploadButtons.style.display = 'flex';
            fileInputCamera.value = '';
            fileInputGallery.value = '';
            continueBtn.disabled = !question.optional;
        });

        continueBtn.disabled = !question.optional;
    }

    handleFileSelect(file, question, uploadButtons, preview, previewImage, continueBtn) {
        const isValidType = file.type.startsWith('image/') || file.name.match(/\.(jpg|jpeg|png|gif|webp|heic|heif)$/i);
        
        if (!isValidType) {
            alert('Veuillez sélectionner une image');
            return;
        }

        previewImage.src = '';
        preview.style.display = 'block';
        uploadButtons.style.display = 'none';
        previewImage.alt = 'Compression en cours...';
        
        this.compressImage(file, 800, 0.7)
            .then(compressedDataUrl => {
                this.selectedFile = compressedDataUrl;
                
                previewImage.src = compressedDataUrl;
                previewImage.alt = 'Aperçu';
                continueBtn.disabled = false;
            })
            .catch(error => {
                console.error('Erreur compression:', error);
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.selectedFile = e.target.result;
                    previewImage.src = e.target.result;
                    previewImage.alt = 'Aperçu';
                    continueBtn.disabled = false;
                };
                reader.onerror = () => {
                    alert('Erreur lors de la lecture du fichier.');
                    preview.style.display = 'none';
                    uploadButtons.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            });
    }

    compressImage(file, maxSize = 800, quality = 0.7) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            
            img.onload = () => {
                try {
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > height) {
                        if (width > maxSize) {
                            height = Math.round(height * maxSize / width);
                            width = maxSize;
                        }
                    } else {
                        if (height > maxSize) {
                            width = Math.round(width * maxSize / height);
                            height = maxSize;
                        }
                    }
                    
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                    
                    URL.revokeObjectURL(img.src);
                    
                    resolve(compressedDataUrl);
                } catch (e) {
                    reject(e);
                }
            };
            
            img.onerror = () => {
                URL.revokeObjectURL(img.src);
                reject(new Error('Erreur chargement image'));
            };
            
            img.src = URL.createObjectURL(file);
        });
    }

    async handleContinue(question) {
        if (this.isProcessing) {
            console.log('Déjà en cours de traitement, ignoré');
            return;
        }
        this.isProcessing = true;
        
        const continueBtn = document.getElementById('btn-continue');
        if (continueBtn) {
            continueBtn.disabled = true;
            continueBtn.textContent = 'Chargement...';
        }
        
        try {
            let answer = { timestamp: new Date().toISOString() };
            let shouldStop = false;
            let stopReason = '';

            switch (question.type) {
                case 'single':
                case 'single_with_text': {
                    const selected = document.querySelector(`input[name="q-${question.id}"]:checked`);
                    if (!selected) {
                        alert('Veuillez sélectionner une réponse');
                        this.isProcessing = false;
                        if (continueBtn) {
                            continueBtn.disabled = false;
                            continueBtn.textContent = 'Continuer →';
                        }
                        return;
                    }
                    answer.value = selected.value;
                    shouldStop = selected.dataset.stop === 'true';
                
                const extraInput = document.querySelector(`.extra-text[data-option="${selected.value}"]`);
                if (extraInput) answer.extraText = extraInput.value;
                
                const exactValue = document.getElementById('exact-value');
                if (exactValue) answer.exactValue = exactValue.value;

                const option = question.options.find(o => o.value === selected.value);
                stopReason = `${question.title}: ${option.label}`;
                
                if (question.customValidation) {
                    const validation = question.customValidation(answer, this.answers);
                    if (validation.stop) {
                        shouldStop = true;
                        stopReason = validation.reason || stopReason;
                    }
                }
                break;
            }

            case 'multiple':
            case 'multiple_with_text':
            case 'multiple_with_brands': {
                const checked = document.querySelectorAll('.option-input[type="checkbox"]:checked');
                answer.values = Array.from(checked).map(cb => cb.value);
                answer.extraTexts = {};
                
                checked.forEach(cb => {
                    if (cb.dataset.stop === 'true') {
                        shouldStop = true;
                        const option = question.options.find(o => o.value === cb.value);
                        stopReason = `${question.title}: ${option.label}`;
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
                    stopReason = validation.reason || `${question.title}: valeur ${value}`;
                }
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
                    if (r.dataset.stop === 'true') {
                        shouldStop = true;
                        stopReason = `${question.title}: sélection incompatible`;
                    }
                });
                break;
            }

            case 'file': {
                if (this.selectedFile) {
                    if (typeof this.selectedFile === 'string') {
                        answer.file = {
                            name: 'photo_' + Date.now() + '.jpg',
                            type: 'image/jpeg',
                            data: this.selectedFile
                        };
                    } else {
                        const base64 = await this.fileToBase64(this.selectedFile);
                        answer.file = {
                            name: this.selectedFile.name,
                            type: this.selectedFile.type,
                            size: this.selectedFile.size,
                            data: base64
                        };
                    }
                } else {
                    answer.file = null;
                }
                break;
            }
        }

        this.answers[question.id] = answer;

        await this.saveToServer();

        if (shouldStop) {
            this.isDisqualified = true;
            if (!this.disqualifyReason) {
                this.disqualifyReason = stopReason;
            }
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
            console.error('Erreur dans handleContinue:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        } finally {
            this.isProcessing = false;
        }
    }

    async saveToServer() {
        const data = this.getAllData();
        
        if (this.responseId) {
            await this.sendToServer('update', {
                id: this.responseId,
                ...data
            });
        } else {
            const result = await this.sendToServer('save', data);
            if (result.id) {
                this.responseId = result.id;
            }
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

    
    addAnimationStyles() {
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                .result-icon.warning {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                }
            `;
            document.head.appendChild(style);
        }
    }

    async renderCompleted() {
        this.questionnaireCompleted = true;
        
        await this.saveToServer();
        
        this.container.innerHTML = `
            <div class="card">
                <div class="result-screen">
                    <div class="result-icon success">✅</div>
                    <h2 class="result-title">Merci de vos réponses !</h2>
                    <p class="result-message">
                        Merci ${this.signaletique.prenom} d'avoir pris le temps de répondre à ce questionnaire.
                        <br><br>
                        Nous avons bien enregistré toutes vos réponses.
                        <br><br>
                        <strong>Nous vous recontacterons très prochainement si vous avez été sélectionné(e).</strong>
                    </p>
                </div>
            </div>
        `;
    }
}

window.QuestionnaireEngine = QuestionnaireEngine;
