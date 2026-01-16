import React, { useState, useEffect } from 'react';
import { User, Home, FileText, ClipboardList, Settings, Bell, ChevronRight, Check, Clock, AlertCircle, Camera, LogOut, Eye, EyeOff, Mail, Lock, Phone, MapPin, Calendar, ChevronLeft, Send, X, CheckCircle, Upload, Loader2, ExternalLink } from 'lucide-react';
import { useAuth } from './contexts/AuthContext';
import api from './services/api';

// Simuler un téléphone mobile
const PhoneFrame = ({ children }) => (
  <div className="relative mx-auto" style={{ width: '375px', height: '812px' }}>
    <div className="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900 rounded-[3rem] shadow-2xl border-4 border-slate-700">
      {/* Notch */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-7 bg-slate-900 rounded-b-2xl z-20" />
      {/* Screen */}
      <div className="absolute top-2 left-2 right-2 bottom-2 bg-white rounded-[2.5rem] overflow-hidden">
        {children}
      </div>
      {/* Home indicator */}
      <div className="absolute bottom-3 left-1/2 -translate-x-1/2 w-32 h-1 bg-slate-600 rounded-full" />
    </div>
  </div>
);

// Loading Spinner
const LoadingSpinner = () => (
  <div className="flex items-center justify-center h-full">
    <Loader2 className="w-8 h-8 text-emerald-500 animate-spin" />
  </div>
);

// Modal WebView pour questionnaire
const WebViewModal = ({ url, onClose, title }) => (
  <div className="absolute inset-0 bg-white z-50 flex flex-col">
    <div className="bg-emerald-500 px-4 pt-10 pb-4 flex items-center justify-between">
      <h2 className="text-white font-semibold truncate flex-1">{title}</h2>
      <button onClick={onClose} className="p-2 text-white/80 hover:text-white">
        <X className="w-6 h-6" />
      </button>
    </div>
    <iframe
      src={url}
      className="flex-1 w-full border-0"
      title="Questionnaire"
    />
  </div>
);

// Ecran de connexion
const LoginScreen = ({ onLogin, onRegister }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    const result = await onLogin(email, password);
    if (!result.success) {
      setError(result.error);
    }
    setLoading(false);
  };

  return (
    <div className="h-full bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 flex flex-col">
      <div className="relative h-48 flex items-center justify-center">
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute -top-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-emerald-300/20 rounded-full blur-2xl" />
        </div>
        <div className="relative text-center">
          <div className="w-20 h-20 bg-white rounded-2xl shadow-lg flex items-center justify-center mx-auto mb-3">
            <span className="text-3xl font-black text-emerald-600">MDT</span>
          </div>
          <h1 className="text-white text-xl font-bold">Maison du Test</h1>
          <p className="text-emerald-100 text-sm">Espace Paneliste</p>
        </div>
      </div>

      <div className="flex-1 bg-white rounded-t-[2rem] px-6 pt-8 pb-6">
        <h2 className="text-2xl font-bold text-slate-800 mb-6">Connexion</h2>

        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Email</label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="votre@email.com"
                className="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                required
              />
            </div>
          </div>

          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Mot de passe</label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
              <input
                type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="--------"
                className="w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
              </button>
            </div>
          </div>

          <div className="flex justify-end">
            <button type="button" className="text-sm text-emerald-600 font-medium hover:underline">
              Mot de passe oublie ?
            </button>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : null}
            Se connecter
          </button>

          <div className="relative my-6">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-slate-200" />
            </div>
            <div className="relative flex justify-center">
              <span className="px-4 bg-white text-sm text-slate-500">ou</span>
            </div>
          </div>

          <button
            type="button"
            onClick={onRegister}
            className="w-full py-3.5 bg-slate-100 text-slate-700 font-semibold rounded-xl hover:bg-slate-200 transition-all active:scale-[0.98]"
          >
            Creer un compte
          </button>
        </form>
      </div>
    </div>
  );
};

// Ecran d'inscription
const RegisterScreen = ({ onBack, onRegister }) => {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState({
    gender: 'M',
    birth_date: '',
    last_name: '',
    first_name: '',
    email: '',
    phone: '',
    password: '',
    address: '',
    postal_code: '',
    city: '',
    family_status: 'single',
    children: '0',
    profession: '',
    sector: 'tech',
    income: '1500-2500',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const updateField = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async () => {
    setError('');
    setLoading(true);
    const result = await onRegister(formData);
    if (!result.success) {
      setError(result.error);
    }
    setLoading(false);
  };

  return (
    <div className="h-full bg-white flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <button onClick={onBack} className="flex items-center text-white/80 hover:text-white mb-4">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-white text-2xl font-bold">Inscription</h1>
        <p className="text-emerald-100 text-sm mt-1">Etape {step} sur 3</p>
        <div className="flex gap-2 mt-4">
          {[1, 2, 3].map(i => (
            <div
              key={i}
              className={`flex-1 h-1 rounded-full ${i <= step ? 'bg-white' : 'bg-white/30'}`}
            />
          ))}
        </div>
      </div>

      <div className="flex-1 px-6 py-6 overflow-auto">
        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">
            {error}
          </div>
        )}

        {step === 1 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Informations personnelles</h3>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Civilite</label>
                <select
                  value={formData.gender}
                  onChange={(e) => updateField('gender', e.target.value)}
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
                >
                  <option value="M">M.</option>
                  <option value="F">Mme</option>
                </select>
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Date naissance</label>
                <input
                  type="date"
                  value={formData.birth_date}
                  onChange={(e) => updateField('birth_date', e.target.value)}
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
                />
              </div>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Nom</label>
              <input
                type="text"
                value={formData.last_name}
                onChange={(e) => updateField('last_name', e.target.value)}
                placeholder="Dupont"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Prenom</label>
              <input
                type="text"
                value={formData.first_name}
                onChange={(e) => updateField('first_name', e.target.value)}
                placeholder="Jean"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Email</label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => updateField('email', e.target.value)}
                placeholder="jean.dupont@email.com"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Telephone</label>
              <input
                type="tel"
                value={formData.phone}
                onChange={(e) => updateField('phone', e.target.value)}
                placeholder="06 12 34 56 78"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Mot de passe</label>
              <input
                type="password"
                value={formData.password}
                onChange={(e) => updateField('password', e.target.value)}
                placeholder="--------"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>
          </div>
        )}

        {step === 2 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Adresse</h3>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Adresse</label>
              <input
                type="text"
                value={formData.address}
                onChange={(e) => updateField('address', e.target.value)}
                placeholder="123 rue de la Paix"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Code postal</label>
                <input
                  type="text"
                  value={formData.postal_code}
                  onChange={(e) => updateField('postal_code', e.target.value)}
                  placeholder="75001"
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
                />
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Ville</label>
                <input
                  type="text"
                  value={formData.city}
                  onChange={(e) => updateField('city', e.target.value)}
                  placeholder="Paris"
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
                />
              </div>
            </div>

            <h3 className="font-semibold text-slate-800 mt-6 mb-4">Situation</h3>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Situation familiale</label>
              <select
                value={formData.family_status}
                onChange={(e) => updateField('family_status', e.target.value)}
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              >
                <option value="single">Celibataire</option>
                <option value="married">Marie(e)</option>
                <option value="pacs">Pacse(e)</option>
                <option value="divorced">Divorce(e)</option>
              </select>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Nombre d'enfants a charge</label>
              <select
                value={formData.children}
                onChange={(e) => updateField('children', e.target.value)}
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              >
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3+">3+</option>
              </select>
            </div>
          </div>
        )}

        {step === 3 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Informations professionnelles</h3>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Profession</label>
              <input
                type="text"
                value={formData.profession}
                onChange={(e) => updateField('profession', e.target.value)}
                placeholder="Developpeur"
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              />
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Secteur d'activite</label>
              <select
                value={formData.sector}
                onChange={(e) => updateField('sector', e.target.value)}
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              >
                <option value="tech">Informatique / Tech</option>
                <option value="health">Sante</option>
                <option value="finance">Finance</option>
                <option value="commerce">Commerce</option>
                <option value="other">Autre</option>
              </select>
            </div>

            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Revenu mensuel net du foyer</label>
              <select
                value={formData.income}
                onChange={(e) => updateField('income', e.target.value)}
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              >
                <option value="<1500">Moins de 1500EUR</option>
                <option value="1500-2500">1500EUR - 2500EUR</option>
                <option value="2500-4000">2500EUR - 4000EUR</option>
                <option value=">4000">Plus de 4000EUR</option>
              </select>
            </div>

            <div className="mt-6 p-4 bg-slate-50 rounded-xl">
              <label className="flex items-start gap-3">
                <input type="checkbox" className="mt-1 w-4 h-4 text-emerald-500 rounded" required />
                <span className="text-xs text-slate-600">
                  J'accepte la <a href="#" className="text-emerald-600 underline">politique de confidentialite</a> et les <a href="#" className="text-emerald-600 underline">conditions d'utilisation</a>
                </span>
              </label>
            </div>
          </div>
        )}
      </div>

      <div className="px-6 py-4 border-t border-slate-100">
        <div className="flex gap-3">
          {step > 1 && (
            <button
              onClick={() => setStep(step - 1)}
              className="flex-1 py-3 bg-slate-100 text-slate-700 font-semibold rounded-xl"
            >
              Precedent
            </button>
          )}
          <button
            onClick={() => step < 3 ? setStep(step + 1) : handleSubmit()}
            disabled={loading}
            className="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl flex items-center justify-center gap-2"
          >
            {loading && <Loader2 className="w-5 h-5 animate-spin" />}
            {step < 3 ? 'Suivant' : "S'inscrire"}
          </button>
        </div>
      </div>
    </div>
  );
};

// Ecran OTP
const OTPScreen = ({ onVerify }) => {
  const [code, setCode] = useState(['', '', '', '', '', '']);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (index, value) => {
    if (value.length > 1) return;
    const newCode = [...code];
    newCode[index] = value;
    setCode(newCode);

    // Auto-focus next input
    if (value && index < 5) {
      document.getElementById(`otp-${index + 1}`)?.focus();
    }
  };

  const handleSubmit = async () => {
    const fullCode = code.join('');
    if (fullCode.length !== 6) return;

    setLoading(true);
    setError('');
    const result = await onVerify(fullCode);
    if (!result.success) {
      setError(result.error);
    }
    setLoading(false);
  };

  return (
    <div className="h-full bg-white flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-8 text-center">
        <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
          <Lock className="w-8 h-8 text-white" />
        </div>
        <h1 className="text-white text-xl font-bold">Verification</h1>
        <p className="text-emerald-100 text-sm mt-1">Double authentification</p>
      </div>

      <div className="flex-1 px-6 py-8">
        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm text-center">
            {error}
          </div>
        )}

        <p className="text-slate-600 text-center mb-8">
          Entrez le code a 6 chiffres de votre application Google Authenticator
        </p>

        <div className="flex justify-center gap-2 mb-8">
          {code.map((digit, i) => (
            <input
              key={i}
              id={`otp-${i}`}
              type="text"
              maxLength="1"
              value={digit}
              onChange={(e) => handleChange(i, e.target.value)}
              className="w-12 h-14 text-center text-2xl font-bold bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-emerald-500 focus:outline-none"
            />
          ))}
        </div>

        <button
          onClick={handleSubmit}
          disabled={loading || code.join('').length !== 6}
          className="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
        >
          {loading && <Loader2 className="w-5 h-5 animate-spin" />}
          Verifier
        </button>

        <p className="text-center text-sm text-slate-500 mt-6">
          Vous n'avez pas configure l'authentification ?<br />
          <button className="text-emerald-600 font-medium">Scanner le QR code</button>
        </p>
      </div>
    </div>
  );
};

// Navigation bottom
const BottomNav = ({ active, setActive }) => {
  const items = [
    { id: 'home', icon: Home, label: 'Accueil' },
    { id: 'sollicitations', icon: FileText, label: 'Sollicitations' },
    { id: 'etudes', icon: ClipboardList, label: 'Etudes' },
    { id: 'settings', icon: Settings, label: 'Parametres' },
  ];

  return (
    <div className="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-2 pb-6 pt-2">
      <div className="flex justify-around">
        {items.map(item => (
          <button
            key={item.id}
            onClick={() => setActive(item.id)}
            className={`flex flex-col items-center py-2 px-4 rounded-xl transition-all ${
              active === item.id
                ? 'text-emerald-600'
                : 'text-slate-400 hover:text-slate-600'
            }`}
          >
            <item.icon className={`w-6 h-6 ${active === item.id ? 'stroke-[2.5]' : ''}`} />
            <span className="text-xs mt-1 font-medium">{item.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
};

// Dashboard / Accueil
const HomeScreen = ({ setActiveTab, user, sollicitations, etudes, notifications }) => {
  const pendingSollicitations = sollicitations.filter(s => s.status === 'pending');
  const inProgressEtudes = etudes.filter(e => e.status === 'in_progress');
  const completedEtudes = etudes.filter(e => e.status === 'completed');

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <p className="text-emerald-100 text-sm">Bonjour,</p>
            <h1 className="text-white text-xl font-bold">{user?.first_name} {user?.last_name}</h1>
          </div>
          <button className="relative w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <Bell className="w-5 h-5 text-white" />
            {notifications.unread_count > 0 && (
              <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">
                {notifications.unread_count}
              </span>
            )}
          </button>
        </div>

        <div className="grid grid-cols-3 gap-3 mt-4">
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">{pendingSollicitations.length}</p>
            <p className="text-xs text-emerald-100">En attente</p>
          </div>
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">{inProgressEtudes.length}</p>
            <p className="text-xs text-emerald-100">En cours</p>
          </div>
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">{completedEtudes.length}</p>
            <p className="text-xs text-emerald-100">Terminees</p>
          </div>
        </div>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {/* Sollicitations en attente */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-bold text-slate-800">Sollicitations en attente</h2>
            <button onClick={() => setActiveTab('sollicitations')} className="text-sm text-emerald-600 font-medium">Voir tout</button>
          </div>

          {pendingSollicitations.length === 0 ? (
            <p className="text-sm text-slate-500 text-center py-4">Aucune sollicitation en attente</p>
          ) : (
            <div className="space-y-3">
              {pendingSollicitations.slice(0, 2).map(soll => (
                <div key={soll.id} className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                  <div className="flex items-start gap-3">
                    <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <Clock className="w-5 h-5 text-amber-600" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-slate-800 truncate">{soll.title}</h3>
                      <p className="text-sm text-slate-500 mt-0.5">+{soll.reward_points} points</p>
                    </div>
                    <ChevronRight className="w-5 h-5 text-slate-300" />
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Etudes en cours */}
        <div>
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-bold text-slate-800">Etudes en cours</h2>
            <button onClick={() => setActiveTab('etudes')} className="text-sm text-emerald-600 font-medium">Voir tout</button>
          </div>

          {inProgressEtudes.length === 0 ? (
            <p className="text-sm text-slate-500 text-center py-4">Aucune etude en cours</p>
          ) : (
            <div className="space-y-3">
              {inProgressEtudes.map(etude => {
                const completedTasks = etude.tasks?.filter(t => t.status === 'validated').length || 0;
                const totalTasks = etude.tasks?.length || 1;
                const progress = Math.round((completedTasks / totalTasks) * 100);

                return (
                  <div key={etude.id} className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                    <div className="flex items-start gap-3">
                      <div className="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <ClipboardList className="w-5 h-5 text-emerald-600" />
                      </div>
                      <div className="flex-1">
                        <h3 className="font-semibold text-slate-800">{etude.title}</h3>
                        <p className="text-sm text-slate-500 mt-0.5">{completedTasks} taches sur {totalTasks} completees</p>
                        <div className="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                          <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${progress}%` }} />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Points */}
        <div className="mt-6 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl p-4 text-white">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-emerald-100 text-sm">Mes points</p>
              <p className="text-3xl font-bold">{user?.points_balance || 0}</p>
            </div>
            <button className="px-4 py-2 bg-white/20 rounded-lg text-sm font-medium hover:bg-white/30 transition">
              Historique
            </button>
          </div>
        </div>
      </div>

      <BottomNav active="home" setActive={setActiveTab} />
    </div>
  );
};

// Liste des sollicitations
const SollicitationsScreen = ({ setActiveTab, onSelectSollicitation, sollicitations }) => {
  const [filter, setFilter] = useState('all');

  const filteredSollicitations = filter === 'all'
    ? sollicitations
    : sollicitations.filter(s => s.status === filter);

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <h1 className="text-xl font-bold text-slate-800 mb-4">Mes Sollicitations</h1>

        <div className="flex gap-2">
          {[
            { id: 'all', label: 'Toutes' },
            { id: 'pending', label: 'En attente' },
            { id: 'completed', label: 'Terminees' },
          ].map(f => (
            <button
              key={f.id}
              onClick={() => setFilter(f.id)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                filter === f.id
                  ? 'bg-emerald-500 text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {filteredSollicitations.length === 0 ? (
          <p className="text-center text-slate-500 py-8">Aucune sollicitation</p>
        ) : (
          <div className="space-y-3">
            {filteredSollicitations.map(soll => (
              <button
                key={soll.id}
                onClick={() => onSelectSollicitation(soll)}
                className="w-full bg-white rounded-xl p-4 shadow-sm border border-slate-100 text-left"
              >
                <div className="flex items-start gap-3">
                  <div className={`w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ${
                    soll.status === 'pending' ? 'bg-amber-100' : 'bg-emerald-100'
                  }`}>
                    {soll.status === 'pending'
                      ? <Clock className="w-5 h-5 text-amber-600" />
                      : <Check className="w-5 h-5 text-emerald-600" />
                    }
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <h3 className="font-semibold text-slate-800 truncate">{soll.title}</h3>
                    </div>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full">{soll.category}</span>
                      <span className="text-xs text-emerald-600 font-medium">+{soll.reward_points} pts</span>
                    </div>
                  </div>
                  <ChevronRight className="w-5 h-5 text-slate-300" />
                </div>
              </button>
            ))}
          </div>
        )}
      </div>

      <BottomNav active="sollicitations" setActive={setActiveTab} />
    </div>
  );
};

// Detail d'une sollicitation
const SollicitationDetailScreen = ({ sollicitation, onBack }) => {
  const [response, setResponse] = useState('');
  const [externalCompleted, setExternalCompleted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [webViewUrl, setWebViewUrl] = useState(null);

  const handleStartStudy = async () => {
    setLoading(true);
    const result = await api.startStudy(sollicitation.id);
    if (result.success && result.study_url) {
      // Ajouter le token JWT a l'URL
      const url = new URL(result.study_url);
      url.searchParams.set('token', result.webview_token);
      setWebViewUrl(url.toString());
    }
    setLoading(false);
  };

  const handleSubmitResponse = async () => {
    setLoading(true);
    await api.respondToSollicitation(sollicitation.id, response, externalCompleted);
    setLoading(false);
    onBack();
  };

  if (webViewUrl) {
    return (
      <WebViewModal
        url={webViewUrl}
        title={sollicitation.title}
        onClose={() => {
          setWebViewUrl(null);
          setExternalCompleted(true);
        }}
      />
    );
  }

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <button onClick={onBack} className="flex items-center text-slate-500 hover:text-slate-700 mb-3">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-xl font-bold text-slate-800">{sollicitation.title}</h1>
        <div className="flex items-center gap-2 mt-2">
          <span className={`text-xs px-2 py-1 rounded-full font-medium ${
            sollicitation.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'
          }`}>
            {sollicitation.status === 'pending' ? 'En attente' : 'Terminee'}
          </span>
          <span className="text-xs text-emerald-600 font-semibold">+{sollicitation.reward_points} points</span>
        </div>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-2">Description</h3>
          <p className="text-sm text-slate-600 leading-relaxed">
            {sollicitation.description}
          </p>
          <div className="flex items-center gap-4 mt-3 text-xs text-slate-500">
            <span className="flex items-center gap-1">
              <Clock className="w-4 h-4" />
              {sollicitation.estimated_duration}
            </span>
          </div>
        </div>

        {sollicitation.study_url && sollicitation.status === 'pending' && (
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
            <h3 className="font-semibold text-slate-800 mb-3">Questionnaire</h3>
            <button
              onClick={handleStartStudy}
              disabled={loading}
              className="flex items-center justify-center gap-2 w-full py-3 bg-emerald-50 text-emerald-600 rounded-xl font-medium hover:bg-emerald-100 transition"
            >
              {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ExternalLink className="w-4 h-4" />}
              Acceder au questionnaire
            </button>
          </div>
        )}

        {sollicitation.admin_question && sollicitation.status === 'pending' && (
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
            <h3 className="font-semibold text-slate-800 mb-3">Question de l'administrateur</h3>
            <p className="text-sm text-slate-600 mb-3 p-3 bg-slate-50 rounded-lg italic">
              "{sollicitation.admin_question}"
            </p>
            <textarea
              value={response}
              onChange={(e) => setResponse(e.target.value)}
              placeholder="Votre reponse..."
              className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm resize-none h-24"
            />
          </div>
        )}

        {sollicitation.study_url && sollicitation.status === 'pending' && (
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
            <label className="flex items-center gap-3">
              <input
                type="checkbox"
                checked={externalCompleted}
                onChange={(e) => setExternalCompleted(e.target.checked)}
                className="w-5 h-5 text-emerald-500 rounded"
              />
              <span className="text-sm text-slate-700">J'ai repondu au questionnaire</span>
            </label>
          </div>
        )}

        {sollicitation.status === 'pending' && (
          <button
            onClick={handleSubmitResponse}
            disabled={loading || (sollicitation.study_url && !externalCompleted)}
            className="w-full mt-4 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {loading && <Loader2 className="w-5 h-5 animate-spin" />}
            Envoyer ma reponse
          </button>
        )}
      </div>
    </div>
  );
};

// Liste des etudes
const EtudesScreen = ({ setActiveTab, onSelectEtude, etudes }) => {
  const [filter, setFilter] = useState('all');

  const filteredEtudes = filter === 'all'
    ? etudes
    : etudes.filter(e => e.status === filter);

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <h1 className="text-xl font-bold text-slate-800 mb-4">Mes Etudes</h1>

        <div className="flex gap-2">
          {[
            { id: 'all', label: 'Toutes' },
            { id: 'in_progress', label: 'En cours' },
            { id: 'completed', label: 'Terminees' },
          ].map(f => (
            <button
              key={f.id}
              onClick={() => setFilter(f.id)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                filter === f.id
                  ? 'bg-emerald-500 text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {filteredEtudes.length === 0 ? (
          <p className="text-center text-slate-500 py-8">Aucune etude</p>
        ) : (
          <div className="space-y-3">
            {filteredEtudes.map(etude => {
              const completedTasks = etude.tasks?.filter(t => t.status === 'validated').length || 0;
              const totalTasks = etude.tasks?.length || 1;
              const progress = Math.round((completedTasks / totalTasks) * 100);

              return (
                <button
                  key={etude.id}
                  onClick={() => onSelectEtude(etude)}
                  className="w-full bg-white rounded-xl p-4 shadow-sm border border-slate-100 text-left"
                >
                  <div className="flex items-center justify-between mb-2">
                    <h3 className="font-semibold text-slate-800">{etude.title}</h3>
                    <span className={`text-xs px-2 py-1 rounded-full font-medium ${
                      etude.status === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                      etude.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                      'bg-amber-100 text-amber-700'
                    }`}>
                      {etude.status === 'completed' ? 'Terminee' : etude.status === 'in_progress' ? 'En cours' : 'En attente'}
                    </span>
                  </div>
                  <p className="text-sm text-slate-500 mb-2">{completedTasks} taches sur {totalTasks} completees</p>
                  <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div
                      className={`h-full rounded-full transition-all ${
                        etude.status === 'completed' ? 'bg-emerald-500' : 'bg-blue-500'
                      }`}
                      style={{ width: `${progress}%` }}
                    />
                  </div>
                </button>
              );
            })}
          </div>
        )}
      </div>

      <BottomNav active="etudes" setActive={setActiveTab} />
    </div>
  );
};

// Detail d'une etude
const EtudeDetailScreen = ({ etude, onBack, onRefresh }) => {
  const [loading, setLoading] = useState(false);

  const completedTasks = etude.tasks?.filter(t => t.status === 'validated').length || 0;
  const totalTasks = etude.tasks?.length || 1;
  const progress = Math.round((completedTasks / totalTasks) * 100);

  const handleCompleteTask = async (taskId) => {
    setLoading(true);
    await api.completeTask(etude.id, taskId);
    setLoading(false);
    onRefresh?.();
  };

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <button onClick={onBack} className="flex items-center text-white/80 hover:text-white mb-3">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-xl font-bold text-white">{etude.title}</h1>
        <p className="text-emerald-100 text-sm mt-1">{completedTasks} taches sur {totalTasks} completees</p>
        <div className="mt-3 h-2 bg-white/30 rounded-full overflow-hidden">
          <div className="h-full bg-white rounded-full" style={{ width: `${progress}%` }} />
        </div>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-2">Informations</h3>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-slate-500">Type</span>
              <span className="text-slate-800 font-medium">{etude.type}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Date limite</span>
              <span className="text-slate-800 font-medium">{etude.deadline}</span>
            </div>
          </div>
        </div>

        <h3 className="font-semibold text-slate-800 mb-3">Taches a realiser</h3>

        <div className="space-y-3">
          {etude.tasks?.map((task, index) => (
            <div
              key={task.id}
              className={`bg-white rounded-xl p-4 shadow-sm border ${
                task.status === 'locked' ? 'border-slate-200 opacity-60' : 'border-slate-100'
              }`}
            >
              <div className="flex items-start gap-3">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                  task.status === 'validated' ? 'bg-emerald-100' :
                  task.status === 'pending' ? 'bg-amber-100' :
                  task.status === 'pending_validation' ? 'bg-blue-100' :
                  'bg-slate-100'
                }`}>
                  {task.status === 'validated' ? (
                    <CheckCircle className="w-5 h-5 text-emerald-600" />
                  ) : task.status === 'pending' ? (
                    <span className="text-sm font-bold text-amber-600">{index + 1}</span>
                  ) : task.status === 'pending_validation' ? (
                    <Loader2 className="w-4 h-4 text-blue-600 animate-spin" />
                  ) : (
                    <Lock className="w-4 h-4 text-slate-400" />
                  )}
                </div>
                <div className="flex-1">
                  <h4 className="font-medium text-slate-800">{task.title}</h4>
                  {task.status === 'validated' && (
                    <span className="text-xs text-emerald-600">Valide par l'admin</span>
                  )}
                  {task.status === 'pending_validation' && (
                    <span className="text-xs text-blue-600">En attente de validation</span>
                  )}
                  {task.status === 'pending' && (
                    <div className="mt-2 flex gap-2">
                      <button className="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium">
                        <Camera className="w-4 h-4" />
                        Photo
                      </button>
                      <button
                        onClick={() => handleCompleteTask(task.id)}
                        disabled={loading}
                        className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium"
                      >
                        <Check className="w-4 h-4" />
                        Terminer
                      </button>
                    </div>
                  )}
                  {task.status === 'locked' && (
                    <span className="text-xs text-slate-400">Debloquer apres validation precedente</span>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// Ecran parametres
const SettingsScreen = ({ setActiveTab, onLogout, user }) => {
  const initials = user ? `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}` : 'U';

  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <h1 className="text-xl font-bold text-slate-800">Parametres</h1>
      </div>

      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {/* Profil */}
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center">
              <span className="text-2xl font-bold text-white">{initials}</span>
            </div>
            <div>
              <h3 className="font-semibold text-slate-800">{user?.first_name} {user?.last_name}</h3>
              <p className="text-sm text-slate-500">{user?.email}</p>
              <p className="text-xs text-emerald-600 font-semibold mt-1">{user?.points_balance || 0} points</p>
            </div>
          </div>
        </div>

        {/* Options */}
        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden mb-4">
          <button className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition border-b border-slate-100">
            <div className="flex items-center gap-3">
              <User className="w-5 h-5 text-slate-400" />
              <span className="text-slate-800">Modifier mon profil</span>
            </div>
            <ChevronRight className="w-5 h-5 text-slate-300" />
          </button>
          <button className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition border-b border-slate-100">
            <div className="flex items-center gap-3">
              <Bell className="w-5 h-5 text-slate-400" />
              <span className="text-slate-800">Notifications</span>
            </div>
            <ChevronRight className="w-5 h-5 text-slate-300" />
          </button>
          <button className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition">
            <div className="flex items-center gap-3">
              <Lock className="w-5 h-5 text-slate-400" />
              <span className="text-slate-800">Securite</span>
            </div>
            <ChevronRight className="w-5 h-5 text-slate-300" />
          </button>
        </div>

        {/* Actions */}
        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden mb-4">
          <button className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition">
            <div className="flex items-center gap-3">
              <AlertCircle className="w-5 h-5 text-red-400" />
              <span className="text-red-600">Demander la suppression du compte</span>
            </div>
          </button>
        </div>

        <button
          onClick={onLogout}
          className="w-full flex items-center justify-center gap-2 p-4 bg-slate-100 text-slate-600 rounded-xl font-medium hover:bg-slate-200 transition"
        >
          <LogOut className="w-5 h-5" />
          Se deconnecter
        </button>
      </div>

      <BottomNav active="settings" setActive={setActiveTab} />
    </div>
  );
};

// Application Mobile principale
const MobileApp = () => {
  const { user, loading: authLoading, isAuthenticated, login, verifyOTP, register, logout } = useAuth();
  const [screen, setScreen] = useState('login');
  const [activeTab, setActiveTab] = useState('home');
  const [selectedSollicitation, setSelectedSollicitation] = useState(null);
  const [selectedEtude, setSelectedEtude] = useState(null);

  // Data states
  const [sollicitations, setSollicitations] = useState([]);
  const [etudes, setEtudes] = useState([]);
  const [notifications, setNotifications] = useState({ notifications: [], unread_count: 0 });
  const [dataLoading, setDataLoading] = useState(false);

  // Charger les donnees quand authentifie
  useEffect(() => {
    if (isAuthenticated) {
      setScreen('app');
      loadData();
    } else {
      setScreen('login');
    }
  }, [isAuthenticated]);

  const loadData = async () => {
    setDataLoading(true);
    try {
      const [sollResult, etudesResult, notifResult] = await Promise.all([
        api.getSollicitations(),
        api.getEtudes(),
        api.getNotifications(),
      ]);

      if (sollResult.success) setSollicitations(sollResult.sollicitations || []);
      if (etudesResult.success) setEtudes(etudesResult.etudes || []);
      if (notifResult.success) setNotifications(notifResult);
    } catch (error) {
      console.error('Error loading data:', error);
    }
    setDataLoading(false);
  };

  const handleLogin = async (email, password) => {
    const result = await login(email, password);
    if (result.success) {
      setScreen('otp');
    }
    return result;
  };

  const handleOTPVerify = async (code) => {
    const result = await verifyOTP(code);
    if (result.success) {
      setScreen('app');
      setActiveTab('home');
    }
    return result;
  };

  const handleRegister = async (userData) => {
    const result = await register(userData);
    if (result.success) {
      setScreen('app');
      setActiveTab('home');
    }
    return result;
  };

  const handleLogout = async () => {
    await logout();
    setScreen('login');
    setActiveTab('home');
    setSollicitations([]);
    setEtudes([]);
    setNotifications({ notifications: [], unread_count: 0 });
  };

  if (authLoading) {
    return (
      <PhoneFrame>
        <LoadingSpinner />
      </PhoneFrame>
    );
  }

  const renderScreen = () => {
    if (screen === 'login') {
      return <LoginScreen onLogin={handleLogin} onRegister={() => setScreen('register')} />;
    }
    if (screen === 'register') {
      return <RegisterScreen onBack={() => setScreen('login')} onRegister={handleRegister} />;
    }
    if (screen === 'otp') {
      return <OTPScreen onVerify={handleOTPVerify} />;
    }

    // App principale
    if (dataLoading && sollicitations.length === 0) {
      return <LoadingSpinner />;
    }

    if (selectedSollicitation) {
      return (
        <SollicitationDetailScreen
          sollicitation={selectedSollicitation}
          onBack={() => {
            setSelectedSollicitation(null);
            loadData(); // Refresh data
          }}
        />
      );
    }
    if (selectedEtude) {
      return (
        <EtudeDetailScreen
          etude={selectedEtude}
          onBack={() => {
            setSelectedEtude(null);
            loadData();
          }}
          onRefresh={loadData}
        />
      );
    }

    switch (activeTab) {
      case 'home':
        return (
          <HomeScreen
            setActiveTab={setActiveTab}
            user={user}
            sollicitations={sollicitations}
            etudes={etudes}
            notifications={notifications}
          />
        );
      case 'sollicitations':
        return (
          <SollicitationsScreen
            setActiveTab={setActiveTab}
            onSelectSollicitation={setSelectedSollicitation}
            sollicitations={sollicitations}
          />
        );
      case 'etudes':
        return (
          <EtudesScreen
            setActiveTab={setActiveTab}
            onSelectEtude={setSelectedEtude}
            etudes={etudes}
          />
        );
      case 'settings':
        return (
          <SettingsScreen
            setActiveTab={setActiveTab}
            onLogout={handleLogout}
            user={user}
          />
        );
      default:
        return (
          <HomeScreen
            setActiveTab={setActiveTab}
            user={user}
            sollicitations={sollicitations}
            etudes={etudes}
            notifications={notifications}
          />
        );
    }
  };

  return (
    <PhoneFrame>
      {renderScreen()}
    </PhoneFrame>
  );
};

export default MobileApp;
