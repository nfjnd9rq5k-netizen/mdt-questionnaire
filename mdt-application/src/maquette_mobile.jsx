import React, { useState } from 'react';
import { User, Home, FileText, ClipboardList, Settings, Bell, ChevronRight, Check, Clock, AlertCircle, Camera, LogOut, Eye, EyeOff, Mail, Lock, Phone, MapPin, Calendar, ChevronLeft, Send, Menu, X, CheckCircle, Circle, Upload } from 'lucide-react';

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

// Écran de connexion
const LoginScreen = ({ onLogin, onRegister }) => {
  const [showPassword, setShowPassword] = useState(false);
  
  return (
    <div className="h-full bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 flex flex-col">
      {/* Header decoratif */}
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
          <p className="text-emerald-100 text-sm">Espace Panéliste</p>
        </div>
      </div>
      
      {/* Formulaire */}
      <div className="flex-1 bg-white rounded-t-[2rem] px-6 pt-8 pb-6">
        <h2 className="text-2xl font-bold text-slate-800 mb-6">Connexion</h2>
        
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Email</label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
              <input 
                type="email" 
                placeholder="votre@email.com"
                className="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
          </div>
          
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Mot de passe</label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
              <input 
                type={showPassword ? "text" : "password"}
                placeholder="••••••••"
                className="w-full pl-11 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
              <button 
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
              </button>
            </div>
          </div>
          
          <div className="flex justify-end">
            <button className="text-sm text-emerald-600 font-medium hover:underline">
              Mot de passe oublié ?
            </button>
          </div>
          
          <button 
            onClick={onLogin}
            className="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all active:scale-[0.98]"
          >
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
            onClick={onRegister}
            className="w-full py-3.5 bg-slate-100 text-slate-700 font-semibold rounded-xl hover:bg-slate-200 transition-all active:scale-[0.98]"
          >
            Créer un compte
          </button>
        </div>
      </div>
    </div>
  );
};

// Écran d'inscription
const RegisterScreen = ({ onBack, onRegister }) => {
  const [step, setStep] = useState(1);
  
  return (
    <div className="h-full bg-white flex flex-col">
      {/* Header */}
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <button onClick={onBack} className="flex items-center text-white/80 hover:text-white mb-4">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-white text-2xl font-bold">Inscription</h1>
        <p className="text-emerald-100 text-sm mt-1">Étape {step} sur 3</p>
        {/* Progress bar */}
        <div className="flex gap-2 mt-4">
          {[1, 2, 3].map(i => (
            <div 
              key={i}
              className={`flex-1 h-1 rounded-full ${i <= step ? 'bg-white' : 'bg-white/30'}`}
            />
          ))}
        </div>
      </div>
      
      {/* Contenu */}
      <div className="flex-1 px-6 py-6 overflow-auto">
        {step === 1 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Informations personnelles</h3>
            
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Civilité</label>
                <select className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                  <option>M.</option>
                  <option>Mme</option>
                </select>
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Date naissance</label>
                <input type="date" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
              </div>
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Nom</label>
              <input type="text" placeholder="Dupont" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Prénom</label>
              <input type="text" placeholder="Jean" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Email</label>
              <input type="email" placeholder="jean.dupont@email.com" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Téléphone</label>
              <input type="tel" placeholder="06 12 34 56 78" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
          </div>
        )}
        
        {step === 2 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Adresse</h3>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Adresse</label>
              <input type="text" placeholder="123 rue de la Paix" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
            
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Code postal</label>
                <input type="text" placeholder="75001" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
              </div>
              <div>
                <label className="text-xs font-medium text-slate-500 mb-1 block">Ville</label>
                <input type="text" placeholder="Paris" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
              </div>
            </div>
            
            <h3 className="font-semibold text-slate-800 mt-6 mb-4">Situation</h3>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Situation familiale</label>
              <select className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <option>Célibataire</option>
                <option>Marié(e)</option>
                <option>Pacsé(e)</option>
                <option>Divorcé(e)</option>
              </select>
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Nombre d'enfants à charge</label>
              <select className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <option>0</option>
                <option>1</option>
                <option>2</option>
                <option>3+</option>
              </select>
            </div>
          </div>
        )}
        
        {step === 3 && (
          <div className="space-y-4">
            <h3 className="font-semibold text-slate-800 mb-4">Informations professionnelles</h3>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Profession</label>
              <input type="text" placeholder="Développeur" className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Secteur d'activité</label>
              <select className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <option>Informatique / Tech</option>
                <option>Santé</option>
                <option>Finance</option>
                <option>Commerce</option>
                <option>Autre</option>
              </select>
            </div>
            
            <div>
              <label className="text-xs font-medium text-slate-500 mb-1 block">Revenu mensuel net du foyer</label>
              <select className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <option>Moins de 1500€</option>
                <option>1500€ - 2500€</option>
                <option>2500€ - 4000€</option>
                <option>Plus de 4000€</option>
              </select>
            </div>
            
            <div className="mt-6 p-4 bg-slate-50 rounded-xl">
              <label className="flex items-start gap-3">
                <input type="checkbox" className="mt-1 w-4 h-4 text-emerald-500 rounded" />
                <span className="text-xs text-slate-600">
                  J'accepte la <a href="#" className="text-emerald-600 underline">politique de confidentialité</a> et les <a href="#" className="text-emerald-600 underline">conditions d'utilisation</a>
                </span>
              </label>
            </div>
          </div>
        )}
      </div>
      
      {/* Footer */}
      <div className="px-6 py-4 border-t border-slate-100">
        <div className="flex gap-3">
          {step > 1 && (
            <button 
              onClick={() => setStep(step - 1)}
              className="flex-1 py-3 bg-slate-100 text-slate-700 font-semibold rounded-xl"
            >
              Précédent
            </button>
          )}
          <button 
            onClick={() => step < 3 ? setStep(step + 1) : onRegister()}
            className="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl"
          >
            {step < 3 ? 'Suivant' : "S'inscrire"}
          </button>
        </div>
      </div>
    </div>
  );
};

// Écran Double Authentification
const OTPScreen = ({ onVerify }) => {
  return (
    <div className="h-full bg-white flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-8 text-center">
        <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
          <Lock className="w-8 h-8 text-white" />
        </div>
        <h1 className="text-white text-xl font-bold">Vérification</h1>
        <p className="text-emerald-100 text-sm mt-1">Double authentification</p>
      </div>
      
      <div className="flex-1 px-6 py-8">
        <p className="text-slate-600 text-center mb-8">
          Entrez le code à 6 chiffres de votre application Google Authenticator
        </p>
        
        <div className="flex justify-center gap-2 mb-8">
          {[...Array(6)].map((_, i) => (
            <input
              key={i}
              type="text"
              maxLength="1"
              className="w-12 h-14 text-center text-2xl font-bold bg-slate-50 border-2 border-slate-200 rounded-xl focus:border-emerald-500 focus:outline-none"
            />
          ))}
        </div>
        
        <button 
          onClick={onVerify}
          className="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg"
        >
          Vérifier
        </button>
        
        <p className="text-center text-sm text-slate-500 mt-6">
          Vous n'avez pas configuré l'authentification ?<br />
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
    { id: 'etudes', icon: ClipboardList, label: 'Études' },
    { id: 'settings', icon: Settings, label: 'Paramètres' },
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
const HomeScreen = ({ setActiveTab }) => {
  return (
    <div className="h-full bg-slate-50 flex flex-col">
      {/* Header */}
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <p className="text-emerald-100 text-sm">Bonjour,</p>
            <h1 className="text-white text-xl font-bold">Jean Dupont</h1>
          </div>
          <button className="relative w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <Bell className="w-5 h-5 text-white" />
            <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">3</span>
          </button>
        </div>
        
        {/* Stats rapides */}
        <div className="grid grid-cols-3 gap-3 mt-4">
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">2</p>
            <p className="text-xs text-emerald-100">En attente</p>
          </div>
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">1</p>
            <p className="text-xs text-emerald-100">En cours</p>
          </div>
          <div className="bg-white/20 rounded-xl p-3 text-center">
            <p className="text-2xl font-bold text-white">5</p>
            <p className="text-xs text-emerald-100">Terminées</p>
          </div>
        </div>
      </div>
      
      {/* Contenu */}
      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {/* Sollicitations en attente */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-bold text-slate-800">Sollicitations en attente</h2>
            <button onClick={() => setActiveTab('sollicitations')} className="text-sm text-emerald-600 font-medium">Voir tout</button>
          </div>
          
          <div className="space-y-3">
            <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Clock className="w-5 h-5 text-amber-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-slate-800 truncate">Test produit cosmétique</h3>
                  <p className="text-sm text-slate-500 mt-0.5">Expire dans 2 jours</p>
                </div>
                <ChevronRight className="w-5 h-5 text-slate-300" />
              </div>
            </div>
            
            <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <Clock className="w-5 h-5 text-amber-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-slate-800 truncate">Enquête alimentaire</h3>
                  <p className="text-sm text-slate-500 mt-0.5">Expire dans 5 jours</p>
                </div>
                <ChevronRight className="w-5 h-5 text-slate-300" />
              </div>
            </div>
          </div>
        </div>
        
        {/* Études en cours */}
        <div>
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-bold text-slate-800">Étude en cours</h2>
            <button onClick={() => setActiveTab('etudes')} className="text-sm text-emerald-600 font-medium">Voir tout</button>
          </div>
          
          <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <ClipboardList className="w-5 h-5 text-emerald-600" />
              </div>
              <div className="flex-1">
                <h3 className="font-semibold text-slate-800">Test application mobile</h3>
                <p className="text-sm text-slate-500 mt-0.5">3 tâches sur 5 complétées</p>
                <div className="mt-2 h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div className="h-full bg-emerald-500 rounded-full" style={{ width: '60%' }} />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <BottomNav active="home" setActive={setActiveTab} />
    </div>
  );
};

// Liste des sollicitations
const SollicitationsScreen = ({ setActiveTab, onSelectSollicitation }) => {
  const [filter, setFilter] = useState('all');
  
  const sollicitations = [
    { id: 1, title: 'Test produit cosmétique', status: 'pending', date: '15/01/2026', category: 'Produit' },
    { id: 2, title: 'Enquête alimentaire', status: 'pending', date: '18/01/2026', category: 'Concept' },
    { id: 3, title: 'Test interface bancaire', status: 'completed', date: '10/01/2026', category: 'Digital' },
    { id: 4, title: 'Sondage mobilité', status: 'completed', date: '05/01/2026', category: 'Concept' },
  ];
  
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
            { id: 'completed', label: 'Terminées' },
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
                    <span className="text-xs text-slate-400">{soll.date}</span>
                  </div>
                </div>
                <ChevronRight className="w-5 h-5 text-slate-300" />
              </div>
            </button>
          ))}
        </div>
      </div>
      
      <BottomNav active="sollicitations" setActive={setActiveTab} />
    </div>
  );
};

// Détail d'une sollicitation
const SollicitationDetailScreen = ({ sollicitation, onBack }) => {
  const [responseSent, setResponseSent] = useState(false);
  
  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <button onClick={onBack} className="flex items-center text-slate-500 hover:text-slate-700 mb-3">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-xl font-bold text-slate-800">{sollicitation?.title || 'Test produit cosmétique'}</h1>
        <div className="flex items-center gap-2 mt-2">
          <span className="text-xs px-2 py-1 bg-amber-100 text-amber-700 rounded-full font-medium">En attente</span>
          <span className="text-xs text-slate-500">Expire le 15/01/2026</span>
        </div>
      </div>
      
      <div className="flex-1 px-4 py-4 overflow-auto pb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-2">Description</h3>
          <p className="text-sm text-slate-600 leading-relaxed">
            Nous recherchons des testeurs pour évaluer une nouvelle gamme de produits cosmétiques bio. 
            Le test comprend l'utilisation quotidienne pendant 2 semaines et un questionnaire détaillé.
          </p>
        </div>
        
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-3">Questionnaire externe</h3>
          <a 
            href="#" 
            className="flex items-center justify-center gap-2 py-3 bg-emerald-50 text-emerald-600 rounded-xl font-medium hover:bg-emerald-100 transition"
          >
            <Send className="w-4 h-4" />
            Accéder au questionnaire
          </a>
        </div>
        
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-3">Question de l'administrateur</h3>
          <p className="text-sm text-slate-600 mb-3 p-3 bg-slate-50 rounded-lg italic">
            "Avez-vous des allergies connues aux produits cosmétiques ?"
          </p>
          <textarea 
            placeholder="Votre réponse..."
            className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm resize-none h-24"
          />
        </div>
        
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
          <label className="flex items-center gap-3">
            <input 
              type="checkbox" 
              checked={responseSent}
              onChange={(e) => setResponseSent(e.target.checked)}
              className="w-5 h-5 text-emerald-500 rounded" 
            />
            <span className="text-sm text-slate-700">J'ai répondu au questionnaire externe</span>
          </label>
        </div>
        
        <button className="w-full mt-4 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl shadow-lg">
          Envoyer ma réponse
        </button>
      </div>
    </div>
  );
};

// Liste des études
const EtudesScreen = ({ setActiveTab, onSelectEtude }) => {
  const [filter, setFilter] = useState('all');
  
  const etudes = [
    { id: 1, title: 'Test application mobile', status: 'in_progress', progress: 60, tasks: 5, completedTasks: 3 },
    { id: 2, title: 'Évaluation packaging', status: 'pending', progress: 0, tasks: 4, completedTasks: 0 },
    { id: 3, title: 'Test produit alimentaire', status: 'completed', progress: 100, tasks: 6, completedTasks: 6 },
  ];
  
  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <h1 className="text-xl font-bold text-slate-800 mb-4">Mes Études</h1>
        
        <div className="flex gap-2">
          {[
            { id: 'all', label: 'Toutes' },
            { id: 'in_progress', label: 'En cours' },
            { id: 'completed', label: 'Terminées' },
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
        <div className="space-y-3">
          {etudes.map(etude => (
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
                  {etude.status === 'completed' ? 'Terminée' : etude.status === 'in_progress' ? 'En cours' : 'En attente'}
                </span>
              </div>
              <p className="text-sm text-slate-500 mb-2">{etude.completedTasks} tâches sur {etude.tasks} complétées</p>
              <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div 
                  className={`h-full rounded-full transition-all ${
                    etude.status === 'completed' ? 'bg-emerald-500' : 'bg-blue-500'
                  }`}
                  style={{ width: `${etude.progress}%` }} 
                />
              </div>
            </button>
          ))}
        </div>
      </div>
      
      <BottomNav active="etudes" setActive={setActiveTab} />
    </div>
  );
};

// Détail d'une étude
const EtudeDetailScreen = ({ etude, onBack }) => {
  const tasks = [
    { id: 1, title: 'Prendre en photo le produit', status: 'validated', hasPhoto: true },
    { id: 2, title: 'Tester la fonctionnalité A', status: 'validated', hasPhoto: true },
    { id: 3, title: 'Tester la fonctionnalité B', status: 'validated', hasPhoto: false },
    { id: 4, title: 'Remplir le questionnaire', status: 'pending', hasPhoto: false },
    { id: 5, title: 'Donner votre avis final', status: 'locked', hasPhoto: false },
  ];
  
  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-gradient-to-r from-emerald-500 to-teal-500 px-4 pt-10 pb-6">
        <button onClick={onBack} className="flex items-center text-white/80 hover:text-white mb-3">
          <ChevronLeft className="w-5 h-5 mr-1" />
          <span className="text-sm">Retour</span>
        </button>
        <h1 className="text-xl font-bold text-white">{etude?.title || 'Test application mobile'}</h1>
        <p className="text-emerald-100 text-sm mt-1">3 tâches sur 5 complétées</p>
        <div className="mt-3 h-2 bg-white/30 rounded-full overflow-hidden">
          <div className="h-full bg-white rounded-full" style={{ width: '60%' }} />
        </div>
      </div>
      
      <div className="flex-1 px-4 py-4 overflow-auto pb-6">
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <h3 className="font-semibold text-slate-800 mb-2">Informations</h3>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-slate-500">Type</span>
              <span className="text-slate-800 font-medium">Distanciel</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-500">Date limite</span>
              <span className="text-slate-800 font-medium">20/01/2026</span>
            </div>
          </div>
        </div>
        
        <h3 className="font-semibold text-slate-800 mb-3">Tâches à réaliser</h3>
        
        <div className="space-y-3">
          {tasks.map((task, index) => (
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
                  'bg-slate-100'
                }`}>
                  {task.status === 'validated' ? (
                    <CheckCircle className="w-5 h-5 text-emerald-600" />
                  ) : task.status === 'pending' ? (
                    <span className="text-sm font-bold text-amber-600">{index + 1}</span>
                  ) : (
                    <Lock className="w-4 h-4 text-slate-400" />
                  )}
                </div>
                <div className="flex-1">
                  <h4 className="font-medium text-slate-800">{task.title}</h4>
                  {task.status === 'validated' && (
                    <span className="text-xs text-emerald-600">Validé par l'admin</span>
                  )}
                  {task.status === 'pending' && (
                    <div className="mt-2 flex gap-2">
                      <button className="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium">
                        <Camera className="w-4 h-4" />
                        Photo
                      </button>
                      <button className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium">
                        <Check className="w-4 h-4" />
                        Terminer
                      </button>
                    </div>
                  )}
                  {task.status === 'locked' && (
                    <span className="text-xs text-slate-400">Débloquer après validation précédente</span>
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

// Écran paramètres
const SettingsScreen = ({ setActiveTab, onLogout }) => {
  return (
    <div className="h-full bg-slate-50 flex flex-col">
      <div className="bg-white px-4 pt-10 pb-4 border-b border-slate-100">
        <h1 className="text-xl font-bold text-slate-800">Paramètres</h1>
      </div>
      
      <div className="flex-1 px-4 py-4 overflow-auto pb-24">
        {/* Profil */}
        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-4">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center">
              <span className="text-2xl font-bold text-white">JD</span>
            </div>
            <div>
              <h3 className="font-semibold text-slate-800">Jean Dupont</h3>
              <p className="text-sm text-slate-500">jean.dupont@email.com</p>
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
              <span className="text-slate-800">Sécurité</span>
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
          Se déconnecter
        </button>
      </div>
      
      <BottomNav active="settings" setActive={setActiveTab} />
    </div>
  );
};

// Application Mobile principale
const MobileApp = () => {
  const [screen, setScreen] = useState('login');
  const [activeTab, setActiveTab] = useState('home');
  const [selectedSollicitation, setSelectedSollicitation] = useState(null);
  const [selectedEtude, setSelectedEtude] = useState(null);
  
  const handleLogin = () => setScreen('otp');
  const handleOTPVerify = () => {
    setScreen('app');
    setActiveTab('home');
  };
  const handleRegister = () => setScreen('register');
  const handleLogout = () => {
    setScreen('login');
    setActiveTab('home');
  };
  
  const renderScreen = () => {
    if (screen === 'login') {
      return <LoginScreen onLogin={handleLogin} onRegister={handleRegister} />;
    }
    if (screen === 'register') {
      return <RegisterScreen onBack={() => setScreen('login')} onRegister={handleLogin} />;
    }
    if (screen === 'otp') {
      return <OTPScreen onVerify={handleOTPVerify} />;
    }
    
    // App principale
    if (selectedSollicitation) {
      return <SollicitationDetailScreen sollicitation={selectedSollicitation} onBack={() => setSelectedSollicitation(null)} />;
    }
    if (selectedEtude) {
      return <EtudeDetailScreen etude={selectedEtude} onBack={() => setSelectedEtude(null)} />;
    }
    
    switch (activeTab) {
      case 'home':
        return <HomeScreen setActiveTab={setActiveTab} />;
      case 'sollicitations':
        return <SollicitationsScreen setActiveTab={setActiveTab} onSelectSollicitation={setSelectedSollicitation} />;
      case 'etudes':
        return <EtudesScreen setActiveTab={setActiveTab} onSelectEtude={setSelectedEtude} />;
      case 'settings':
        return <SettingsScreen setActiveTab={setActiveTab} onLogout={handleLogout} />;
      default:
        return <HomeScreen setActiveTab={setActiveTab} />;
    }
  };
  
  return (
    <PhoneFrame>
      {renderScreen()}
    </PhoneFrame>
  );
};

export default MobileApp;
