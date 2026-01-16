/**
 * API Service - Communication avec le backend
 * Mode DEMO activé pour tester sans backend
 */

// === MODE DEMO ===
// Mettre à false pour utiliser le vrai backend
const DEMO_MODE = false;

// URL de l'API
const API_BASE_URL = 'https://mediumvioletred-elephant-640253.hostingersite.com/api/mobile';

// Données de démo
const DEMO_USER = {
  id: 'PAN_DEMO123',
  email: 'demo@test.com',
  first_name: 'Jean',
  last_name: 'Dupont',
  status: 'active',
  email_verified: true,
  points_balance: 250,
  points_lifetime: 450,
  studies_completed: 5,
  studies_in_progress: 1,
  studies_pending: 2,
  gender: 'M',
  birth_date: '1990-05-15',
  region: 'Île-de-France',
  city: 'Paris',
  postal_code: '75001',
  phone: '06 12 34 56 78',
};

const DEMO_SOLLICITATIONS = [
  {
    id: 1,
    title: 'Test produit cosmétique',
    description: 'Nous recherchons des testeurs pour évaluer une nouvelle gamme de produits cosmétiques bio. Le test comprend l\'utilisation quotidienne pendant 2 semaines et un questionnaire détaillé.',
    estimated_duration: '15-20 min',
    reward_points: 100,
    status: 'pending',
    category: 'Produit',
    expires_at: '2026-01-17',
    study_url: 'https://etudes.lamaisondutest.fr/studies/COSMETIQUE_BIO/',
    admin_question: 'Avez-vous des allergies connues aux produits cosmétiques ?',
  },
  {
    id: 2,
    title: 'Enquête alimentaire',
    description: 'Questionnaire sur vos habitudes de consommation et vos préférences alimentaires.',
    estimated_duration: '10 min',
    reward_points: 50,
    status: 'pending',
    category: 'Concept',
    expires_at: '2026-01-20',
    study_url: 'https://etudes.lamaisondutest.fr/studies/ALIM_HABITUDES/',
    admin_question: null,
  },
  {
    id: 3,
    title: 'Test interface bancaire',
    description: 'Évaluez la nouvelle interface de l\'application mobile d\'une grande banque.',
    estimated_duration: '20 min',
    reward_points: 75,
    status: 'completed',
    category: 'Digital',
    expires_at: '2026-01-10',
  },
  {
    id: 4,
    title: 'Sondage mobilité',
    description: 'Questionnaire sur vos habitudes de déplacement et vos attentes en matière de mobilité.',
    estimated_duration: '8 min',
    reward_points: 40,
    status: 'completed',
    category: 'Concept',
    expires_at: '2026-01-05',
  },
];

const DEMO_ETUDES = [
  {
    id: 1,
    title: 'Test application mobile',
    description: 'Test utilisateur d\'une nouvelle application mobile de livraison.',
    status: 'in_progress',
    type: 'Distanciel',
    deadline: '2026-01-20',
    tasks: [
      { id: 1, title: 'Prendre en photo le produit', status: 'validated', has_photo: true },
      { id: 2, title: 'Tester la fonctionnalité A', status: 'validated', has_photo: true },
      { id: 3, title: 'Tester la fonctionnalité B', status: 'validated', has_photo: false },
      { id: 4, title: 'Remplir le questionnaire', status: 'pending', has_photo: false },
      { id: 5, title: 'Donner votre avis final', status: 'locked', has_photo: false },
    ],
  },
  {
    id: 2,
    title: 'Évaluation packaging',
    description: 'Évaluez différentes versions de packaging pour un nouveau produit.',
    status: 'pending',
    type: 'Distanciel',
    deadline: '2026-01-25',
    tasks: [
      { id: 1, title: 'Regarder les photos des packagings', status: 'locked' },
      { id: 2, title: 'Répondre au questionnaire', status: 'locked' },
      { id: 3, title: 'Donner votre classement', status: 'locked' },
      { id: 4, title: 'Commentaires libres', status: 'locked' },
    ],
  },
  {
    id: 3,
    title: 'Test produit alimentaire',
    description: 'Testez un nouveau snack healthy et donnez votre avis.',
    status: 'completed',
    type: 'À domicile',
    deadline: '2026-01-08',
    tasks: [
      { id: 1, title: 'Réception du produit', status: 'validated' },
      { id: 2, title: 'Premier test dégustation', status: 'validated' },
      { id: 3, title: 'Test après 3 jours', status: 'validated' },
      { id: 4, title: 'Questionnaire final', status: 'validated' },
      { id: 5, title: 'Évaluation globale', status: 'validated' },
      { id: 6, title: 'Photo du produit', status: 'validated' },
    ],
  },
];

const DEMO_POINTS_HISTORY = [
  { points: 50, type: 'study_completed', description: 'Étude: Cosmétiques bio', balance_after: 250, date: '2026-01-15' },
  { points: 75, type: 'study_completed', description: 'Étude: App streaming', balance_after: 200, date: '2026-01-10' },
  { points: 100, type: 'bonus', description: 'Bonus inscription', balance_after: 125, date: '2026-01-05' },
  { points: 25, type: 'study_completed', description: 'Étude: Snacks', balance_after: 25, date: '2026-01-02' },
];

class ApiService {
  constructor() {
    this.accessToken = null;
    this.refreshToken = null;
    this.init();
  }

  async init() {
    try {
      this.accessToken = localStorage.getItem('accessToken');
      this.refreshToken = localStorage.getItem('refreshToken');
    } catch (e) {
      console.log('Error loading tokens:', e);
    }
  }

  saveTokens(tokens) {
    this.accessToken = tokens.access_token;
    this.refreshToken = tokens.refresh_token;
    localStorage.setItem('accessToken', tokens.access_token);
    localStorage.setItem('refreshToken', tokens.refresh_token);
  }

  clearTokens() {
    this.accessToken = null;
    this.refreshToken = null;
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
  }

  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;

    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (this.accessToken && !options.noAuth) {
      headers['Authorization'] = `Bearer ${this.accessToken}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
        body: options.body ? JSON.stringify(options.body) : undefined,
      });

      const data = await response.json();

      // Si token expiré, essayer de rafraîchir
      if (response.status === 401 && data.code === 'INVALID_TOKEN' && this.refreshToken) {
        const refreshed = await this.refreshAccessToken();
        if (refreshed) {
          headers['Authorization'] = `Bearer ${this.accessToken}`;
          const retryResponse = await fetch(url, {
            ...options,
            headers,
            body: options.body ? JSON.stringify(options.body) : undefined,
          });
          return await retryResponse.json();
        }
      }

      return data;
    } catch (error) {
      console.error('API Error:', error);
      return { success: false, error: 'Erreur de connexion au serveur' };
    }
  }

  async refreshAccessToken() {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/refresh.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ refresh_token: this.refreshToken }),
      });

      const data = await response.json();

      if (data.success && data.tokens) {
        this.saveTokens(data.tokens);
        return true;
      }

      this.clearTokens();
      return false;
    } catch (e) {
      return false;
    }
  }

  // === AUTH ===

  async register(userData) {
    if (DEMO_MODE) {
      this.saveTokens({ access_token: 'demo_token', refresh_token: 'demo_refresh' });
      return {
        success: true,
        panelist: { ...DEMO_USER, ...userData },
        tokens: { access_token: 'demo_token', refresh_token: 'demo_refresh' },
        message: 'Compte créé (mode démo)',
      };
    }
    return this.request('/auth/register.php', {
      method: 'POST',
      body: userData,
      noAuth: true,
    });
  }

  async login(email, password) {
    if (DEMO_MODE) {
      // Simuler un délai réseau
      await new Promise(r => setTimeout(r, 500));
      this.saveTokens({ access_token: 'demo_token', refresh_token: 'demo_refresh' });
      return {
        success: true,
        panelist: { ...DEMO_USER, email },
        tokens: { access_token: 'demo_token', refresh_token: 'demo_refresh' },
      };
    }

    const data = await this.request('/auth/login.php', {
      method: 'POST',
      body: { email, password },
      noAuth: true,
    });

    if (data.success && data.tokens) {
      this.saveTokens(data.tokens);
    }

    return data;
  }

  async verifyOTP(code) {
    if (DEMO_MODE) {
      await new Promise(r => setTimeout(r, 300));
      return { success: true };
    }
    return this.request('/auth/verify-otp.php', {
      method: 'POST',
      body: { code },
    });
  }

  async logout() {
    if (DEMO_MODE) {
      this.clearTokens();
      return { success: true };
    }
    await this.request('/auth/logout.php', {
      method: 'POST',
      body: { refresh_token: this.refreshToken },
    });
    this.clearTokens();
  }

  // === PROFILE ===

  async getProfile() {
    if (DEMO_MODE) {
      return { success: true, profile: DEMO_USER };
    }
    return this.request('/profile.php');
  }

  async updateProfile(profileData) {
    if (DEMO_MODE) {
      Object.assign(DEMO_USER, profileData);
      return { success: true, message: 'Profil mis à jour (mode démo)' };
    }
    return this.request('/profile.php', {
      method: 'PUT',
      body: profileData,
    });
  }

  // === SOLLICITATIONS ===

  async getSollicitations() {
    if (DEMO_MODE) {
      return { success: true, sollicitations: DEMO_SOLLICITATIONS };
    }
    return this.request('/studies.php');
  }

  async respondToSollicitation(solicitationId, response, hasCompletedExternal = false) {
    if (DEMO_MODE) {
      const soll = DEMO_SOLLICITATIONS.find(s => s.id === solicitationId);
      if (soll) soll.status = 'responded';
      return { success: true, message: 'Réponse enregistrée' };
    }
    return this.request('/solicitation-respond.php', {
      method: 'POST',
      body: { solicitation_id: solicitationId, response, has_completed_external: hasCompletedExternal },
    });
  }

  // === ÉTUDES ===

  async getEtudes() {
    if (DEMO_MODE) {
      return { success: true, etudes: DEMO_ETUDES };
    }
    return this.request('/etudes.php');
  }

  async startStudy(solicitationId) {
    if (DEMO_MODE) {
      const soll = DEMO_SOLLICITATIONS.find(s => s.id === solicitationId);
      return {
        success: true,
        study_url: soll?.study_url || 'https://etudes.lamaisondutest.fr/studies/DEMO/',
        webview_token: 'demo_token',
      };
    }
    return this.request('/study-start.php', {
      method: 'POST',
      body: { solicitation_id: solicitationId },
    });
  }

  async completeTask(etudeId, taskId, photoData = null) {
    if (DEMO_MODE) {
      const etude = DEMO_ETUDES.find(e => e.id === etudeId);
      if (etude) {
        const task = etude.tasks.find(t => t.id === taskId);
        if (task) {
          task.status = 'pending_validation';
          // Débloquer la tâche suivante
          const nextTask = etude.tasks.find(t => t.id === taskId + 1);
          if (nextTask && nextTask.status === 'locked') {
            nextTask.status = 'pending';
          }
        }
      }
      return { success: true, message: 'Tâche soumise pour validation' };
    }
    return this.request('/task-complete.php', {
      method: 'POST',
      body: { etude_id: etudeId, task_id: taskId, photo: photoData },
    });
  }

  // === POINTS ===

  async getPointsHistory(limit = 20, offset = 0) {
    if (DEMO_MODE) {
      return {
        success: true,
        stats: {
          current_balance: DEMO_USER.points_balance,
          lifetime_earned: DEMO_USER.points_lifetime,
          studies_completed: DEMO_USER.studies_completed,
        },
        history: DEMO_POINTS_HISTORY.slice(offset, offset + limit),
        pagination: { total: DEMO_POINTS_HISTORY.length, limit, offset, has_more: false },
      };
    }
    return this.request(`/points-history.php?limit=${limit}&offset=${offset}`);
  }

  // === NOTIFICATIONS ===

  async getNotifications(limit = 20, unreadOnly = false) {
    if (DEMO_MODE) {
      return {
        success: true,
        notifications: [
          { id: 1, title: 'Nouvelle sollicitation', message: 'Test produit cosmétique vous attend !', read: false, date: '2026-01-15' },
          { id: 2, title: 'Tâche validée', message: 'Votre tâche a été validée par l\'admin', read: false, date: '2026-01-14' },
          { id: 3, title: 'Points crédités', message: '+50 points pour l\'étude Cosmétiques', read: true, date: '2026-01-13' },
        ],
        unread_count: 2,
      };
    }
    return this.request(`/notifications.php?limit=${limit}&unread_only=${unreadOnly}`);
  }

  async markNotificationRead(notificationId) {
    if (DEMO_MODE) {
      return { success: true };
    }
    return this.request('/notifications.php', {
      method: 'POST',
      body: { notification_id: notificationId },
    });
  }

  // === HELPERS ===

  isLoggedIn() {
    return !!this.accessToken;
  }

  getAccessToken() {
    return this.accessToken;
  }
}

export const api = new ApiService();
export default api;
