'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Radio, ArrowRight } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { GoogleAuthModal } from '@/components/GoogleAuthModal';

export default function LoginPage() {
  const router = useRouter();
  const { user, isLoading, loginWithGoogle } = useAuth();
  const [googleModalOpen, setGoogleModalOpen] = useState(false);
  const [authError, setAuthError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      if (user.role === 'super_admin' || user.role === 'admin') {
        router.push('/super-admin');
      } else {
        router.push('/account');
      }
    }
  }, [user, router]);

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const urlParams = new URLSearchParams(window.location.search);
    const errParam = urlParams.get('error');
    if (errParam) {
      setAuthError(decodeURIComponent(errParam));
      return;
    }

    const hash = window.location.hash;
    if (hash && hash.includes('id_token=')) {
      try {
        const params = new URLSearchParams(hash.replace(/^#/, ''));
        const idToken = params.get('id_token');
        if (idToken) {
          loginWithGoogle(idToken).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
          }).catch((err: any) => {
            console.error('Google OAuth Hash sync failed:', err);
            setAuthError(err.message || 'Failed to sync Google user session');
          });
        }
      } catch (err: any) {
        console.error('Failed to parse Google OAuth redirect token:', err);
        setAuthError('Invalid Google OAuth token response');
      }
    }
  }, [loginWithGoogle]);

  const handleGoogleRedirect = () => {
    // Dynamically calculate the base path for localhost/love-talkpodcast2/public or port 8000
    const origin = window.location.origin;
    const isSubfolder = window.location.pathname.startsWith('/love-talkpodcast2/public');
    const basePath = isSubfolder ? '/love-talkpodcast2/public' : '';
    window.location.href = `${origin}${basePath}/auth/google`;
  };

  return (
    <div className="max-w-md mx-auto py-12 px-4 space-y-6">
      <div className="text-center space-y-3">
        <div className="w-16 h-16 rounded-3xl bg-gradient-to-tr from-brand-600 to-rose-500 text-white flex items-center justify-center mx-auto shadow-xl shadow-brand-500/30">
          <Radio className="w-8 h-8 animate-pulse" />
        </div>
        <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
          Welcome to Love Talk
        </h1>
        <p className="text-xs text-gray-500">Sign in to access your account, playlists, and premium episodes.</p>
      </div>

      <div className="bg-white dark:bg-gray-900 p-8 rounded-3xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl space-y-6">
        {authError && (
          <div className="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-semibold">
            {authError}
          </div>
        )}

        <div className="space-y-3 pt-2">
          {/* Main Google Socialite Redirect Button */}
          <button
            type="button"
            disabled={isLoading}
            onClick={handleGoogleRedirect}
            className="w-full py-4 px-6 rounded-2xl bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-white font-bold text-sm border border-gray-300 dark:border-gray-700 shadow-md transition-all flex items-center justify-center gap-3 group"
          >
            <svg className="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
            </svg>
            Sign in with Google
          </button>

          <div className="relative flex py-1 items-center">
            <div className="flex-grow border-t border-gray-200 dark:border-gray-800"></div>
            <span className="flex-shrink mx-2 text-[10px] text-gray-400 font-medium">or instant 1-click</span>
            <div className="flex-grow border-t border-gray-200 dark:border-gray-800"></div>
          </div>

          {/* 1-Click Instant Sign-In */}
          <button
            type="button"
            disabled={isLoading}
            onClick={async () => {
              try {
                setAuthError(null);
                await loginWithGoogle({
                  email: 'girishkumarlodhi7@gmail.com',
                  name: 'Girish Kumar Lodhi',
                  avatar_url: 'https://api.dicebear.com/7.x/avataaars/svg?seed=girishkumarlodhi7@gmail.com'
                });
              } catch (err: any) {
                setAuthError(err.message || 'Login failed');
              }
            }}
            className="w-full py-3 px-4 rounded-xl bg-gray-50 dark:bg-gray-800/80 hover:bg-gray-100 dark:hover:bg-gray-700/80 text-gray-700 dark:text-gray-200 font-semibold text-xs border border-gray-200 dark:border-gray-700 transition-all flex items-center justify-between group"
          >
            <div className="flex items-center gap-2">
              <span className="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-[10px]">G</span>
              <span>Fast Login as <strong>girishkumarlodhi7@gmail.com</strong></span>
            </div>
            <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
          </button>
        </div>

        <div className="pt-2 text-center text-xs text-gray-500">
          By signing in, you agree to Love Talk's Privacy Policy and Terms of Service.
        </div>
      </div>

      <div className="text-center text-xs text-gray-500">
        Don't have an account? <Link href="/register" className="font-bold text-brand-600 hover:underline">Register with Google</Link>
      </div>

      <GoogleAuthModal
        isOpen={googleModalOpen}
        onClose={() => setGoogleModalOpen(false)}
        onSuccess={() => setGoogleModalOpen(false)}
      />
    </div>
  );
}
