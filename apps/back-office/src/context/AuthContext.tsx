import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
import api, { apiClient } from '../api';

interface User {
  id: string;
  name: string;
  email: string;
  agency_id: string;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (credentials: any) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const response = await apiClient.get('/me');
      setUser(response.data.user);
    } catch (error) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (credentials: any) => {
    // Get CSRF cookie first
    await api.get('/sanctum/csrf-cookie');
    
    // Login
    await apiClient.post('/login', credentials);
    
    // Fetch user details
    await checkAuth();
  };

  const logout = async () => {
    try {
      await apiClient.post('/logout');
    } finally {
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
