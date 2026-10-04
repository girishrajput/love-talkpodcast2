'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { UserProfile, UserMembership, PaymentRecord } from '@/lib/types';

interface AuthContextType {
  user: UserProfile | null;
  membership: UserMembership | null;
  payments: PaymentRecord[];
  isPremium: boolean;
  isLoading: boolean;
  /** Sign in with the ID token (`credential`) returned by Google Identity Services. */
  loginWithGoogle: (credential: string) => Promise<UserProfile>;
  logout: () => Promise<void>;
  updateProfile: (data: Partial<UserProfile>) => Promise<void>;
  refreshSession: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [membership, setMembership] = useState<UserMembership | null>(null);
  const [payments, setPayments] = useState<PaymentRecord[]>([]);
  const [isPremium, setIsPremium] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const clearState = () => {
    setUser(null);
    setMembership(null);
    setPayments([]);
    setIsPremium(false);
  };

  // The server session cookie is the source of truth; ask it who we are.
  const fetchSession = async () => {
    try {
      const res = await fetch('/api/auth/me', {
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      const json = res.ok ? await res.json() : null;
      if (json?.success && json.user) {
        setUser(json.user);
        setMembership(json.membership);
        setIsPremium(json.isPremium);
        setPayments(json.payments || []);
      } else {
        clearState();
      }
    } catch (err) {
      console.warn('Failed to load session:', err);
    }
  };

  useEffect(() => {
    // Drop the old client-side identity cache; it is no longer trusted.
    try { localStorage.removeItem('lovetalk_auth_user'); } catch {}
    fetchSession().finally(() => setIsLoading(false));
  }, []);

  const loginWithGoogle = async (credential: string): Promise<UserProfile> => {
    setIsLoading(true);
    try {
      const res = await fetch('/api/auth/google', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ credential }),
      });

      const json = await res.json();

      if (!res.ok || !json.success) {
        throw new Error(json.error || json.message || 'Authentication failed');
      }

      setUser(json.user);
      setMembership(json.membership);
      setIsPremium(json.isPremium);
      setPayments(json.payments || []);
      return json.user;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      await fetch('/api/auth/logout', { method: 'POST', headers: { Accept: 'application/json' } });
    } catch (err) {
      console.warn('Logout request failed:', err);
    }
    window.google?.accounts?.id?.disableAutoSelect?.();
    clearState();
  };

  const updateProfile = async (data: Partial<UserProfile>) => {
    if (!user) return;
    try {
      const res = await fetch('/api/auth/profile', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(data),
      });

      const json = await res.json();
      if (json.success && json.user) {
        setUser(json.user);
      }
    } catch (err) {
      console.error('Failed to update profile via API:', err);
    }
  };

  const refreshSession = async () => {
    await fetchSession();
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        membership,
        payments,
        isPremium,
        isLoading,
        loginWithGoogle,
        logout,
        updateProfile,
        refreshSession
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
};
