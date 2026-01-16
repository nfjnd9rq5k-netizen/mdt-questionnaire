import React, { useState } from 'react';
import { 
  Users, FileText, ClipboardList, Settings, Bell, Search, Plus, Filter, Download, Upload,
  ChevronDown, ChevronRight, MoreVertical, Edit, Trash2, Copy, Send, Eye, Check, X, 
  Home, BarChart3, LogOut, Menu, User, Mail, Phone, MapPin, Calendar, Clock,
  CheckCircle, AlertCircle, XCircle, ArrowUpRight, TrendingUp, Activity
} from 'lucide-react';

// Sidebar
const Sidebar = ({ activeMenu, setActiveMenu, collapsed }) => {
  const menuItems = [
    { id: 'dashboard', icon: Home, label: 'Tableau de bord' },
    { id: 'panelistes', icon: Users, label: 'Panélistes' },
    { id: 'sollicitations', icon: FileText, label: 'Sollicitations' },
    { id: 'etudes', icon: ClipboardList, label: 'Études' },
    { id: 'admins', icon: Settings, label: 'Administrateurs' },
  ];
  
  return (
    <div className={`bg-slate-900 text-white flex flex-col transition-all duration-300 ${collapsed ? 'w-20' : 'w-64'}`}>
      <div className="p-4 border-b border-slate-700">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center font-bold text-lg flex-shrink-0">
            M
          </div>
          {!collapsed && (
            <div>
              <h1 className="font-bold text-lg">MDT Admin</h1>
              <p className="text-xs text-slate-400">Maison du Test</p>
            </div>
          )}
        </div>
      </div>
      
      <nav className="flex-1 py-4">
        {menuItems.map(item => (
          <button
            key={item.id}
            onClick={() => setActiveMenu(item.id)}
            className={`w-full flex items-center gap-3 px-4 py-3 transition-all ${
              activeMenu === item.id 
                ? 'bg-emerald-500/20 text-emerald-400 border-r-2 border-emerald-400' 
                : 'text-slate-400 hover:bg-slate-800 hover:text-white'
            }`}
          >
            <item.icon className="w-5 h-5 flex-shrink-0" />
            {!collapsed && <span className="font-medium">{item.label}</span>}
          </button>
        ))}
      </nav>
      
      <div className="p-4 border-t border-slate-700">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
            <User className="w-5 h-5 text-slate-300" />
          </div>
          {!collapsed && (
            <div className="flex-1 min-w-0">
              <p className="font-medium text-sm truncate">Admin MDT</p>
              <p className="text-xs text-slate-400 truncate">admin@mdt.fr</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

// Header
const Header = ({ title, onToggleSidebar }) => (
  <header className="bg-white border-b border-slate-200 px-6 py-4">
    <div className="flex items-center justify-between">
      <div className="flex items-center gap-4">
        <button onClick={onToggleSidebar} className="p-2 hover:bg-slate-100 rounded-lg">
          <Menu className="w-5 h-5 text-slate-600" />
        </button>
        <h1 className="text-xl font-bold text-slate-800">{title}</h1>
      </div>
      <div className="flex items-center gap-3">
        <button className="relative p-2 hover:bg-slate-100 rounded-lg">
          <Bell className="w-5 h-5 text-slate-600" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
        </button>
        <button className="flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded-lg">
          <div className="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
            <span className="text-sm font-bold text-emerald-600">A</span>
          </div>
          <ChevronDown className="w-4 h-4 text-slate-400" />
        </button>
      </div>
    </div>
  </header>
);

// Dashboard
const Dashboard = () => {
  const stats = [
    { label: 'Panélistes actifs', value: '1,234', change: '+12%', icon: Users, color: 'emerald' },
    { label: 'Sollicitations en cours', value: '8', change: '+3', icon: FileText, color: 'blue' },
    { label: 'Études actives', value: '5', change: '+1', icon: ClipboardList, color: 'purple' },
    { label: 'Taux de réponse', value: '78%', change: '+5%', icon: TrendingUp, color: 'amber' },
  ];
  
  const recentActivity = [
    { type: 'inscription', user: 'Marie Martin', time: 'Il y a 5 min' },
    { type: 'reponse', user: 'Pierre Durand', action: 'a répondu à "Test cosmétique"', time: 'Il y a 15 min' },
    { type: 'validation', user: 'Admin', action: 'a validé une tâche', time: 'Il y a 30 min' },
    { type: 'inscription', user: 'Sophie Bernard', time: 'Il y a 1h' },
  ];
  
  return (
    <div className="p-6 space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat, i) => (
          <div key={i} className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div className="flex items-start justify-between">
              <div>
                <p className="text-sm text-slate-500">{stat.label}</p>
                <p className="text-2xl font-bold text-slate-800 mt-1">{stat.value}</p>
                <p className="text-sm text-emerald-600 mt-1 flex items-center gap-1">
                  <ArrowUpRight className="w-4 h-4" />
                  {stat.change}
                </p>
              </div>
              <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                stat.color === 'emerald' ? 'bg-emerald-100' :
                stat.color === 'blue' ? 'bg-blue-100' :
                stat.color === 'purple' ? 'bg-purple-100' : 'bg-amber-100'
              }`}>
                <stat.icon className={`w-6 h-6 ${
                  stat.color === 'emerald' ? 'text-emerald-600' :
                  stat.color === 'blue' ? 'text-blue-600' :
                  stat.color === 'purple' ? 'text-purple-600' : 'text-amber-600'
                }`} />
              </div>
            </div>
          </div>
        ))}
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold text-slate-800">Évolution des inscriptions</h3>
            <select className="text-sm border border-slate-200 rounded-lg px-3 py-1.5">
              <option>7 derniers jours</option>
              <option>30 derniers jours</option>
            </select>
          </div>
          <div className="h-64 bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg flex items-center justify-center">
            <div className="text-center">
              <BarChart3 className="w-12 h-12 text-slate-300 mx-auto mb-2" />
              <p className="text-slate-400 text-sm">Graphique des inscriptions</p>
              <p className="text-slate-300 text-xs">(Recharts sera intégré ici)</p>
            </div>
          </div>
        </div>
        
        <div className="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
          <h3 className="font-semibold text-slate-800 mb-4">Activité récente</h3>
          <div className="space-y-4">
            {recentActivity.map((activity, i) => (
              <div key={i} className="flex items-start gap-3">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${
                  activity.type === 'inscription' ? 'bg-emerald-100' :
                  activity.type === 'reponse' ? 'bg-blue-100' : 'bg-purple-100'
                }`}>
                  {activity.type === 'inscription' ? <User className="w-4 h-4 text-emerald-600" /> :
                   activity.type === 'reponse' ? <FileText className="w-4 h-4 text-blue-600" /> :
                   <Check className="w-4 h-4 text-purple-600" />}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-slate-800">
                    <span className="font-medium">{activity.user}</span>
                    {activity.type === 'inscription' ? " s'est inscrit(e)" : ` ${activity.action}`}
                  </p>
                  <p className="text-xs text-slate-400">{activity.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

// Page Panélistes
const PanelistesPage = () => {
  const [showFilters, setShowFilters] = useState(false);
  
  const panelistes = [
    { id: 1, nom: 'Dupont', prenom: 'Jean', email: 'jean.dupont@email.com', ville: 'Paris', age: 35, status: 'actif', etudes: 5 },
    { id: 2, nom: 'Martin', prenom: 'Marie', email: 'marie.martin@email.com', ville: 'Lyon', age: 28, status: 'actif', etudes: 3 },
    { id: 3, nom: 'Bernard', prenom: 'Sophie', email: 'sophie.bernard@email.com', ville: 'Marseille', age: 42, status: 'inactif', etudes: 8 },
    { id: 4, nom: 'Durand', prenom: 'Pierre', email: 'pierre.durand@email.com', ville: 'Toulouse', age: 31, status: 'actif', etudes: 2 },
  ];
  
  return (
    <div className="p-6">
      <div className="flex flex-col sm:flex-row gap-4 mb-6">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input 
            type="text"
            placeholder="Rechercher un panéliste..."
            className="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <div className="flex gap-2">
          <button onClick={() => setShowFilters(!showFilters)} className="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50">
            <Filter className="w-4 h-4" /> Filtres
          </button>
          <button className="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50">
            <Download className="w-4 h-4" /> Export
          </button>
          <button className="flex items-center gap-2 px-4 py-2.5 border border-slate-200 rounded-xl hover:bg-slate-50">
            <Upload className="w-4 h-4" /> Import
          </button>
          <button className="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600">
            <Plus className="w-4 h-4" /> Ajouter
          </button>
        </div>
      </div>
      
      {showFilters && (
        <div className="bg-slate-50 rounded-xl p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Ville</label>
            <select className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
              <option>Toutes</option>
              <option>Paris</option>
              <option>Lyon</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Âge</label>
            <select className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
              <option>Tous</option>
              <option>18-25</option>
              <option>26-35</option>
              <option>36-50</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Statut</label>
            <select className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
              <option>Tous</option>
              <option>Actif</option>
              <option>Inactif</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-slate-600 mb-1 block">Profession</label>
            <select className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
              <option>Toutes</option>
            </select>
          </div>
        </div>
      )}
      
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200">
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600"><input type="checkbox" /></th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Nom</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Email</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Ville</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Âge</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Études</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Statut</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            {panelistes.map(p => (
              <tr key={p.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-4 py-3"><input type="checkbox" /></td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                      <span className="text-sm font-medium text-emerald-600">{p.prenom[0]}{p.nom[0]}</span>
                    </div>
                    <span className="font-medium text-slate-800">{p.prenom} {p.nom}</span>
                  </div>
                </td>
                <td className="px-4 py-3 text-slate-600">{p.email}</td>
                <td className="px-4 py-3 text-slate-600">{p.ville}</td>
                <td className="px-4 py-3 text-slate-600">{p.age} ans</td>
                <td className="px-4 py-3 text-slate-600">{p.etudes}</td>
                <td className="px-4 py-3">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    p.status === 'actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'
                  }`}>{p.status === 'actif' ? 'Actif' : 'Inactif'}</span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1">
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Eye className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Edit className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Send className="w-4 h-4 text-slate-400" /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
          <p className="text-sm text-slate-600">1-4 sur 1,234 panélistes</p>
          <div className="flex gap-1">
            <button className="px-3 py-1.5 border border-slate-200 rounded-lg text-sm">Précédent</button>
            <button className="px-3 py-1.5 bg-emerald-500 text-white rounded-lg text-sm">1</button>
            <button className="px-3 py-1.5 border border-slate-200 rounded-lg text-sm">2</button>
            <button className="px-3 py-1.5 border border-slate-200 rounded-lg text-sm">Suivant</button>
          </div>
        </div>
      </div>
    </div>
  );
};

// Page Sollicitations
const SollicitationsPage = () => {
  const [showModal, setShowModal] = useState(false);
  
  const sollicitations = [
    { id: 1, titre: 'Test produit cosmétique', categorie: 'Produit', status: 'active', panelistes: 150, reponses: 89, date: '15/01/2026' },
    { id: 2, titre: 'Enquête alimentaire', categorie: 'Concept', status: 'active', panelistes: 200, reponses: 45, date: '18/01/2026' },
    { id: 3, titre: 'Test interface bancaire', categorie: 'Digital', status: 'terminee', panelistes: 100, reponses: 98, date: '10/01/2026' },
    { id: 4, titre: 'Sondage mobilité', categorie: 'Concept', status: 'brouillon', panelistes: 0, reponses: 0, date: '-' },
  ];
  
  return (
    <div className="p-6">
      <div className="flex flex-col sm:flex-row gap-4 mb-6">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input type="text" placeholder="Rechercher..." className="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl" />
        </div>
        <div className="flex gap-2">
          <select className="px-4 py-2.5 border border-slate-200 rounded-xl">
            <option>Tous les statuts</option>
            <option>Active</option>
            <option>Terminée</option>
            <option>Brouillon</option>
          </select>
          <button onClick={() => setShowModal(true)} className="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600">
            <Plus className="w-4 h-4" /> Nouvelle sollicitation
          </button>
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {sollicitations.map(s => (
          <div key={s.id} className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition">
            <div className="p-5">
              <div className="flex items-start justify-between mb-3">
                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                  s.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                  s.status === 'terminee' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700'
                }`}>{s.status === 'active' ? 'Active' : s.status === 'terminee' ? 'Terminée' : 'Brouillon'}</span>
                <button className="p-1 hover:bg-slate-100 rounded"><MoreVertical className="w-4 h-4 text-slate-400" /></button>
              </div>
              <h3 className="font-semibold text-slate-800 mb-1">{s.titre}</h3>
              <span className="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{s.categorie}</span>
              <div className="mt-4 grid grid-cols-2 gap-4">
                <div>
                  <p className="text-2xl font-bold text-slate-800">{s.panelistes}</p>
                  <p className="text-xs text-slate-500">Panélistes</p>
                </div>
                <div>
                  <p className="text-2xl font-bold text-emerald-600">{s.reponses}</p>
                  <p className="text-xs text-slate-500">Réponses</p>
                </div>
              </div>
              {s.status === 'active' && (
                <div className="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div className="h-full bg-emerald-500 rounded-full" style={{ width: `${(s.reponses / s.panelistes) * 100}%` }} />
                </div>
              )}
            </div>
            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
              <span className="text-xs text-slate-500">{s.date !== '-' ? `Expire: ${s.date}` : 'Non publié'}</span>
              <div className="flex gap-1">
                <button className="p-1.5 hover:bg-slate-200 rounded"><Copy className="w-4 h-4 text-slate-400" /></button>
                <button className="p-1.5 hover:bg-slate-200 rounded"><Eye className="w-4 h-4 text-slate-400" /></button>
                <button className="p-1.5 hover:bg-slate-200 rounded"><Send className="w-4 h-4 text-slate-400" /></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl w-full max-w-2xl">
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-800">Nouvelle sollicitation</h2>
              <button onClick={() => setShowModal(false)} className="p-2 hover:bg-slate-100 rounded-lg"><X className="w-5 h-5" /></button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="text-sm font-medium text-slate-700 mb-1 block">Titre</label>
                <input type="text" placeholder="Ex: Test produit cosmétique" className="w-full px-4 py-2.5 border border-slate-200 rounded-xl" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-slate-700 mb-1 block">Catégorie</label>
                  <select className="w-full px-4 py-2.5 border border-slate-200 rounded-xl">
                    <option>Produit</option>
                    <option>Concept</option>
                    <option>Digital</option>
                  </select>
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700 mb-1 block">Date de fin</label>
                  <input type="date" className="w-full px-4 py-2.5 border border-slate-200 rounded-xl" />
                </div>
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700 mb-1 block">Description</label>
                <textarea placeholder="Décrivez..." className="w-full px-4 py-2.5 border border-slate-200 rounded-xl h-24 resize-none" />
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700 mb-1 block">URL externe (optionnel)</label>
                <input type="url" placeholder="https://..." className="w-full px-4 py-2.5 border border-slate-200 rounded-xl" />
              </div>
            </div>
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
              <button onClick={() => setShowModal(false)} className="px-4 py-2.5 border border-slate-200 rounded-xl">Annuler</button>
              <button className="px-4 py-2.5 bg-emerald-500 text-white rounded-xl">Créer</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// Page Études
const EtudesPage = () => {
  const [showModal, setShowModal] = useState(false);
  
  const etudes = [
    { id: 1, titre: 'Test application mobile', type: 'Distanciel', status: 'active', participants: 25, progression: 65, taches: 5 },
    { id: 2, titre: 'Évaluation packaging', type: 'Présentiel', status: 'active', participants: 15, progression: 30, taches: 4 },
    { id: 3, titre: 'Test produit alimentaire', type: 'Distanciel', status: 'terminee', participants: 40, progression: 100, taches: 6 },
  ];
  
  return (
    <div className="p-6">
      <div className="flex flex-col sm:flex-row gap-4 mb-6">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input type="text" placeholder="Rechercher..." className="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl" />
        </div>
        <button onClick={() => setShowModal(true)} className="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white rounded-xl">
          <Plus className="w-4 h-4" /> Nouvelle étude
        </button>
      </div>
      
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200">
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Étude</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Type</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Participants</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Tâches</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Progression</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Statut</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            {etudes.map(e => (
              <tr key={e.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-4 py-4 font-medium text-slate-800">{e.titre}</td>
                <td className="px-4 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    e.type === 'Distanciel' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                  }`}>{e.type}</span>
                </td>
                <td className="px-4 py-4 text-slate-600">{e.participants}</td>
                <td className="px-4 py-4 text-slate-600">{e.taches}</td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-2">
                    <div className="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className={`h-full rounded-full ${e.progression === 100 ? 'bg-emerald-500' : 'bg-blue-500'}`} style={{ width: `${e.progression}%` }} />
                    </div>
                    <span className="text-sm text-slate-600">{e.progression}%</span>
                  </div>
                </td>
                <td className="px-4 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    e.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'
                  }`}>{e.status === 'active' ? 'Active' : 'Terminée'}</span>
                </td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-1">
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Eye className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Edit className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Copy className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Send className="w-4 h-4 text-slate-400" /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-2xl w-full max-w-2xl">
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-800">Nouvelle étude</h2>
              <button onClick={() => setShowModal(false)} className="p-2 hover:bg-slate-100 rounded-lg"><X className="w-5 h-5" /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-slate-700 mb-1 block">Titre</label>
                  <input type="text" placeholder="Ex: Test application" className="w-full px-4 py-2.5 border border-slate-200 rounded-xl" />
                </div>
                <div>
                  <label className="text-sm font-medium text-slate-700 mb-1 block">Type</label>
                  <select className="w-full px-4 py-2.5 border border-slate-200 rounded-xl">
                    <option>Distanciel</option>
                    <option>Présentiel</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700 mb-1 block">Description</label>
                <textarea placeholder="Décrivez..." className="w-full px-4 py-2.5 border border-slate-200 rounded-xl h-20 resize-none" />
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700 mb-2 block">Tâches</label>
                <div className="space-y-2">
                  <div className="flex gap-2">
                    <input type="text" placeholder="Tâche 1" className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" />
                    <input type="text" placeholder="Consigne" className="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm" />
                  </div>
                  <button className="flex items-center gap-2 text-sm text-emerald-600"><Plus className="w-4 h-4" /> Ajouter</button>
                </div>
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700 mb-1 block">Lier à une sollicitation</label>
                <select className="w-full px-4 py-2.5 border border-slate-200 rounded-xl">
                  <option>Aucune</option>
                  <option>Test produit cosmétique</option>
                </select>
              </div>
            </div>
            <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
              <button onClick={() => setShowModal(false)} className="px-4 py-2.5 border border-slate-200 rounded-xl">Annuler</button>
              <button className="px-4 py-2.5 bg-emerald-500 text-white rounded-xl">Créer</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// Page Administrateurs
const AdminsPage = () => {
  const admins = [
    { id: 1, nom: 'Admin Principal', email: 'admin@mdt.fr', role: 'Super Admin', lastLogin: '05/01/2026' },
    { id: 2, nom: 'Marie Gestionnaire', email: 'marie@mdt.fr', role: 'Admin', lastLogin: '04/01/2026' },
  ];
  
  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h2 className="text-lg font-semibold text-slate-800">Gestion des administrateurs</h2>
          <p className="text-sm text-slate-500">Gérez les accès et permissions</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2.5 bg-emerald-500 text-white rounded-xl">
          <Plus className="w-4 h-4" /> Nouvel admin
        </button>
      </div>
      
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200">
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Nom</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Email</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Rôle</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Dernière connexion</th>
              <th className="text-left px-4 py-3 text-sm font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody>
            {admins.map(a => (
              <tr key={a.id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="px-4 py-4 font-medium text-slate-800">{a.nom}</td>
                <td className="px-4 py-4 text-slate-600">{a.email}</td>
                <td className="px-4 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    a.role === 'Super Admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'
                  }`}>{a.role}</span>
                </td>
                <td className="px-4 py-4 text-slate-600">{a.lastLogin}</td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-1">
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Edit className="w-4 h-4 text-slate-400" /></button>
                    <button className="p-1.5 hover:bg-slate-100 rounded-lg"><Trash2 className="w-4 h-4 text-red-400" /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

// App principale
const WebAdminApp = () => {
  const [activeMenu, setActiveMenu] = useState('dashboard');
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  
  const titles = {
    dashboard: 'Tableau de bord',
    panelistes: 'Gestion des panélistes',
    sollicitations: 'Gestion des sollicitations',
    etudes: 'Gestion des études',
    admins: 'Administrateurs',
  };
  
  const renderPage = () => {
    switch (activeMenu) {
      case 'dashboard': return <Dashboard />;
      case 'panelistes': return <PanelistesPage />;
      case 'sollicitations': return <SollicitationsPage />;
      case 'etudes': return <EtudesPage />;
      case 'admins': return <AdminsPage />;
      default: return <Dashboard />;
    }
  };
  
  return (
    <div className="h-screen flex bg-slate-100">
      <Sidebar activeMenu={activeMenu} setActiveMenu={setActiveMenu} collapsed={sidebarCollapsed} />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header title={titles[activeMenu]} onToggleSidebar={() => setSidebarCollapsed(!sidebarCollapsed)} />
        <main className="flex-1 overflow-auto">{renderPage()}</main>
      </div>
    </div>
  );
};

export default WebAdminApp;
