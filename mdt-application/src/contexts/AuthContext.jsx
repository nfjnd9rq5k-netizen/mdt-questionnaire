import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  // Charger l'utilisateur au démarrage
  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      if (api.isLoggedIn()) {
        const result = await api.getProfile();
        if (result.success) {
          setUser(result.profile);
          setIsAuthenticated(true);
        } else {
          api.clearTokens();
        }
      }
    } catch (error) {
      console.error('Auth check error:', error);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    setLoading(true);
    try {
      const result = await api.login(email, password);
      if (result.success) {
        setUser(result.panelist);
        setIsAuthenticated(true);
        return { success: true };
      }
      return { success: false, error: result.error || 'Identifiants incorrects' };
    } catch (error) {
      return { success: false, error: 'Erreur de connexion' };
    } finally {
      setLoading(false);
    }
  };

  const verifyOTP = async (code) => {
    setLoading(true);
    try {
      const result = await api.verifyOTP(code);
      if (result.success) {
        return { success: true };
      }
      return { success: false, error: result.error || 'Code invalide' };
    } catch (error) {
      return { success: false, error: 'Erreur de vérification' };
    } finally {
      setLoading(false);
    }
  };

  const register = async (userData) => {
    setLoading(true);
    try {
      const result = await api.register(userData);
      if (result.success) {
        setUser(result.panelist);
        setIsAuthenticated(true);
        return { success: true };
      }
      return { success: false, error: result.error || 'Erreur d\'inscription' };
    } catch (error) {
      return { success: false, error: 'Erreur de connexion' };
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    setLoading(true);
    try {
      await api.logout();
    } finally {
      setUser(null);
      setIsAuthenticated(false);
      setLoading(false);
    }
  };

  const updateProfile = async (profileData) => {
    try {
      const result = await api.updateProfile(profileData);
      if (result.success) {
        setUser(prev => ({ ...prev, ...profileData }));
        return { success: true };
      }
      return { success: false, error: result.error };
    } catch (error) {
      return { success: false, error: 'Erreur de mise à jour' };
    }
  };

  const value = {
    user,
    loading,
    isAuthenticated,
    login,
    verifyOTP,
    register,
    logout,
    updateProfile,
    refreshUser: checkAuth,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export default AuthContext;
