'use client';

import React, { useState, useEffect, useRef } from 'react';
import { X, ShieldCheck } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

interface GoogleAuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: () => void;
}

declare global {
  interface Window {
    google?: any;
  }
}

const googleClientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID || '';

export const GoogleAuthModal: React.FC<GoogleAuthModalProps> = ({ isOpen, onClose, onSuccess }) => {
  const { loginWithGoogle } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const googleButtonRef = useRef<HTMLDivElement>(null);

  // Keep the latest handler reachable from the GIS callback, which is registered once.
  const credentialHandlerRef = useRef<(response: any) => void>(() => {});
  credentialHandlerRef.current = async (response: any) => {
    try {
      setIsSubmitting(true);
      setErrorMsg('');
      await loginWithGoogle(response.credential);
      onClose();
      onSuccess?.();
    } catch (err: any) {
      console.error('Google sign-in failed:', err);
      setErrorMsg(err.message || 'Google authentication failed');
    } finally {
      setIsSubmitting(false);
    }
  };

  useEffect(() => {
    if (!isOpen || !googleClientId) return;

    const renderButton = () => {
      if (!window.google?.accounts?.id || !googleButtonRef.current) return;
      window.google.accounts.id.initialize({
        client_id: googleClientId,
        callback: (response: any) => credentialHandlerRef.current(response),
        auto_select: false,
        cancel_on_tap_outside: true,
      });
      window.google.accounts.id.renderButton(googleButtonRef.current, {
        theme: 'outline',
        size: 'large',
        width: 320,
        text: 'continue_with',
        shape: 'pill',
      });
    };

    const scriptId = 'google-gis-script';
    const existing = document.getElementById(scriptId) as HTMLScriptElement | null;
    if (existing) {
      if (window.google?.accounts?.id) renderButton();
      else existing.addEventListener('load', renderButton, { once: true });
      return;
    }

    const script = document.createElement('script');
    script.id = scriptId;
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.onload = renderButton;
    script.onerror = () => setErrorMsg('Could not load Google Sign-In. Check your connection and try again.');
    document.body.appendChild(script);
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl relative my-8 animate-in fade-in zoom-in-95">

        {/* Header */}
        <div className="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center shadow-sm">
              <svg className="w-6 h-6" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
              </svg>
            </div>
            <div>
              <h3 className="font-extrabold text-lg text-gray-900 dark:text-white">Sign in with Google</h3>
              <p className="text-xs text-gray-500">Love Talk Podcast</p>
            </div>
          </div>

          <button onClick={onClose} className="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Close">
            <X className="w-5 h-5" />
          </button>
        </div>

        {googleClientId ? (
          <div className="flex flex-col items-center gap-3">
            <div ref={googleButtonRef} className={isSubmitting ? 'opacity-50 pointer-events-none' : ''}></div>
            {isSubmitting && <p className="text-xs text-gray-500">Signing you in…</p>}
          </div>
        ) : (
          <p className="text-xs text-rose-500 font-semibold text-center">
            Google Sign-In is not configured (missing NEXT_PUBLIC_GOOGLE_CLIENT_ID).
          </p>
        )}

        {errorMsg && (
          <p className="text-xs text-rose-500 font-semibold text-center">{errorMsg}</p>
        )}

        <p className="flex items-center justify-center gap-1.5 text-[11px] text-gray-400">
          <ShieldCheck className="w-3.5 h-3.5" /> We only receive your name, email and profile photo.
        </p>
      </div>
    </div>
  );
};
