import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, useParams, useLocation } from 'react-router-dom';
import '@/app/globals.css';

import { LanguageProvider } from '@/context/LanguageContext';
import { ThemeProvider } from '@/context/ThemeContext';
import { AuthProvider } from '@/context/AuthContext';
import { AudioProvider } from '@/context/AudioContext';
import { Navbar } from '@/components/Navbar';
import { Footer } from '@/components/Footer';
import { AudioPlayer } from '@/components/AudioPlayer';

// Pages
import HomePage from '@/app/page';
import EpisodesPage from '@/app/episodes/page';
import EpisodeDetailPage from '@/app/episodes/[slug]/page';
import MembershipPage from '@/app/membership/page';
import AboutPage from '@/app/about/page';
import AccountPage from '@/app/account/page';
import AdminPage from '@/app/admin/page';
import SuperAdminDashboard from '@/app/super-admin/page';
import SuperAdminPlans from '@/app/super-admin/plans/page';
import SuperAdminBenefits from '@/app/super-admin/benefits/page';
import SuperAdminUsers from '@/app/super-admin/users/page';
import SuperAdminSettings from '@/app/super-admin/settings/page';
import LoginPage from '@/app/login/page';
import RegisterPage from '@/app/register/page';
import PaymentSuccessPage from '@/app/payment/success/page';
import PaymentFailedPage from '@/app/payment/failed/page';
import PrivacyPolicyPage from '@/app/privacy-policy/page';
import TermsOfServicePage from '@/app/terms-of-service/page';
import SearchPage from '@/app/search/page';
import SitemapPage from '@/app/sitemap/page';

// Patch window.fetch to support subfolder deployments (e.g. /love-talkpodcast2/public/)
if (typeof window !== 'undefined') {
  const originalFetch = window.fetch;
  const basePathMatch = window.location.pathname.match(/^(\/[^/]+\/public)/);
  const detectedBase = basePathMatch ? basePathMatch[1] : '';

  window.fetch = function (input: RequestInfo | URL, init?: RequestInit) {
    if (typeof input === 'string') {
      if (input.startsWith('/api/') || input.startsWith('/images/')) {
        input = `${detectedBase}${input}`;
      }
    }
    return originalFetch.call(this, input, init);
  };
}

// Wrapper for dynamic slug route
const EpisodeDetailWrapper: React.FC = () => {
  const { slug } = useParams<{ slug: string }>();
  return <EpisodeDetailPage params={{ slug: slug || '' }} />;
};

// Scroll to top on route change
const ScrollToTop: React.FC = () => {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);
  return null;
};

// Main App Router & Layout
const App: React.FC = () => {
  const basePathMatch = window.location.pathname.match(/^(\/[^/]+\/public)/);
  const basename = basePathMatch ? basePathMatch[1] : '';

  return (
    <BrowserRouter basename={basename}>
      <LanguageProvider>
        <ThemeProvider>
          <AuthProvider>
            <AudioProvider>
              <ScrollToTop />
              <div className="min-h-screen flex flex-col justify-between bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-slate-100 transition-colors duration-200">
                <Navbar />
                <main className="flex-grow">
                  <Routes>
                    <Route path="/" element={<HomePage />} />
                    <Route path="/episodes" element={<EpisodesPage />} />
                    <Route path="/episodes/:slug" element={<EpisodeDetailWrapper />} />
                    <Route path="/membership" element={<MembershipPage />} />
                    <Route path="/about" element={<AboutPage />} />
                    <Route path="/account" element={<AccountPage />} />
                    <Route path="/admin" element={<AdminPage />} />
                    <Route path="/super-admin" element={<SuperAdminDashboard />} />
                    <Route path="/super-admin/plans" element={<SuperAdminPlans />} />
                    <Route path="/super-admin/benefits" element={<SuperAdminBenefits />} />
                    <Route path="/super-admin/users" element={<SuperAdminUsers />} />
                    <Route path="/super-admin/settings" element={<SuperAdminSettings />} />
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/register" element={<RegisterPage />} />
                    <Route path="/payment/success" element={<PaymentSuccessPage />} />
                    <Route path="/payment/failed" element={<PaymentFailedPage />} />
                    <Route path="/privacy-policy" element={<PrivacyPolicyPage />} />
                    <Route path="/terms-of-service" element={<TermsOfServicePage />} />
                    <Route path="/search" element={<SearchPage />} />
                    <Route path="/sitemap" element={<SitemapPage />} />
                    <Route path="*" element={<HomePage />} />
                  </Routes>
                </main>
                <Footer />
                <AudioPlayer />
              </div>
            </AudioProvider>
          </AuthProvider>
        </ThemeProvider>
      </LanguageProvider>
    </BrowserRouter>
  );
};

const rootEl = document.getElementById('root');
if (rootEl) {
  createRoot(rootEl).render(<App />);
}

export default App;
