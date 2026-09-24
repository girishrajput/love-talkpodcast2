'use client';

import React from 'react';
import Link from 'next/link';
import { Heart, Send, Instagram, Youtube, Music, Radio, ShieldCheck, Mail } from 'lucide-react';
import { useLanguage } from '@/context/LanguageContext';

export const Footer: React.FC = () => {
  const { t } = useLanguage();

  return (
    <footer className="bg-gray-900 dark:bg-gray-950 text-gray-300 border-t border-gray-800 pt-16 pb-28">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
          
          {/* Col 1: Brand & Host */}
          <div className="space-y-4">
            <div className="flex items-center gap-3">
              <img 
                src="images/favicon.png" 
                alt="Love Talk Podcast Logo" 
                className="w-10 h-10 object-contain rounded-xl bg-white" 
              />
              <div>
                <span className="font-bold text-xl text-white">Love Talk Podcast</span>
                <p className="text-xs text-white dark:text-white font-medium">By Kota RJ Pawan</p>
              </div>
            </div>
            <p className="text-sm text-gray-400 leading-relaxed">
              "Welcome to 'Love Talk' Podcast! We're glad that you are here." Hosted By Kota RJ Pawan. Real conversations on love, connection & mental wellness.
            </p>
            <div className="flex flex-wrap items-center gap-2 pt-1">
              <a
                href="https://youtube.com/playlist?list=PLMMrN7fUIrdSskjkpNFywhhidWODSklsr&si=WTaYQpmlksNBW25G"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600 text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="YouTube Playlist"
                title="YouTube Playlist"
              >
                <Youtube className="w-4 h-4" />
              </a>
              <a
                href="https://www.instagram.com/kotarjpawan?stkn=Y2tkOGhpYTA3enph"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-pink-600 text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="Instagram Profile"
                title="Instagram Profile"
              >
                <Instagram className="w-4 h-4" />
              </a>
              <a
                href="https://www.facebook.com/kotarjpawan"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-blue-600 text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="Facebook Page"
                title="Facebook Page"
              >
                <span className="font-bold text-xs">f</span>
              </a>
              <a
                href="https://x.com/KOTARJPAWAN"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-black text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="X (Twitter)"
                title="X (Twitter)"
              >
                <span className="font-bold text-xs">𝕏</span>
              </a>
              <a
                href="https://www.linkedin.com/in/kotarjpawan"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-sky-600 text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="LinkedIn Profile"
                title="LinkedIn Profile"
              >
                <span className="font-bold text-xs">in</span>
              </a>
              <a
                href="https://t.me/lovetalkpodcast"
                target="_blank"
                rel="noreferrer"
                className="w-8 h-8 rounded-lg bg-gray-800 hover:bg-sky-500 text-gray-300 hover:text-white flex items-center justify-center transition-colors"
                aria-label="Telegram"
                title="Telegram"
              >
                <Send className="w-4 h-4" />
              </a>
            </div>
          </div>

          {/* Col 2: Social Community Groups */}
          <div className="space-y-4">
            <h4 className="text-white font-semibold text-sm uppercase tracking-wider flex items-center gap-1.5 text-rose-400">
              <Heart className="w-4 h-4 fill-rose-500 text-rose-500" /> Community Groups
            </h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li>
                <a href="https://facebook.com/groups/1036267591933452/" target="_blank" rel="noreferrer" className="hover:text-blue-400 transition-colors flex items-center gap-2">
                  <span className="w-5 text-center font-bold text-blue-500">f</span> Facebook Group
                </a>
              </li>
              <li>
                <a href="https://www.instagram.com/channel/AbbCzQrb_rbQL47c/" target="_blank" rel="noreferrer" className="hover:text-pink-400 transition-colors flex items-center gap-2">
                  <Instagram className="w-4 h-4 text-pink-500" /> Insta Broadcast Channel
                </a>
              </li>
              <li>
                <a href="https://t.me/lovetalkpodcast" target="_blank" rel="noreferrer" className="hover:text-sky-400 transition-colors flex items-center gap-2">
                  <Send className="w-4 h-4 text-sky-400" /> Telegram Community
                </a>
              </li>
              <li>
                <a href="https://aratt.ai/@lovetalk_podcast_by_kotarjpawa" target="_blank" rel="noreferrer" className="hover:text-emerald-400 transition-colors flex items-center gap-2">
                  <span className="w-5 text-center font-bold text-emerald-400">@</span> Arattai Community
                </a>
              </li>
              <li>
                <a href="https://youtube.com/@kotarjpawan/community?si=ccqBGKMONX4HK2ug" target="_blank" rel="noreferrer" className="hover:text-red-400 transition-colors flex items-center gap-2">
                  <Youtube className="w-4 h-4 text-red-500" /> YouTube Community Tab
                </a>
              </li>
            </ul>
          </div>

          {/* Col 3: Host & Podcast Pages */}
          <div className="space-y-4">
            <h4 className="text-white font-semibold text-sm uppercase tracking-wider text-brand-400">
              Official Socials
            </h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li>
                <a href="https://youtube.com/playlist?list=PLMMrN7fUIrdSskjkpNFywhhidWODSklsr&si=WTaYQpmlksNBW25G" target="_blank" rel="noreferrer" className="hover:text-red-400 transition-colors flex items-center gap-2">
                  <Youtube className="w-4 h-4 text-red-500" /> YouTube Full Playlist
                </a>
              </li>
              <li>
                <a href="https://www.facebook.com/kotarjpawan" target="_blank" rel="noreferrer" className="hover:text-blue-400 transition-colors flex items-center gap-2">
                  <span className="w-5 text-center font-bold text-blue-500">f</span> Facebook: @kotarjpawan
                </a>
              </li>
              <li>
                <a href="https://www.instagram.com/kotarjpawan?stkn=Y2tkOGhpYTA3enph" target="_blank" rel="noreferrer" className="hover:text-pink-400 transition-colors flex items-center gap-2">
                  <Instagram className="w-4 h-4 text-pink-500" /> Instagram: @kotarjpawan
                </a>
              </li>
              <li>
                <a href="https://x.com/KOTARJPAWAN" target="_blank" rel="noreferrer" className="hover:text-white transition-colors flex items-center gap-2">
                  <span className="w-5 text-center font-bold text-gray-300">𝕏</span> X: @KOTARJPAWAN
                </a>
              </li>
              <li>
                <a href="https://www.linkedin.com/in/kotarjpawan" target="_blank" rel="noreferrer" className="hover:text-sky-400 transition-colors flex items-center gap-2">
                  <span className="w-5 text-center font-bold text-sky-400">in</span> LinkedIn: Kota RJ Pawan
                </a>
              </li>
            </ul>
          </div>

          {/* Col 4: Quick Links & Audio */}
          <div className="space-y-4">
            <h4 className="text-white font-semibold text-sm uppercase tracking-wider text-amber-400">
              Quick Links
            </h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li><Link href="/" className="hover:text-brand-400 transition-colors">{t('navHome')}</Link></li>
              <li><Link href="/episodes" className="hover:text-brand-400 transition-colors">{t('navEpisodes')}</Link></li>
              <li><Link href="/membership" className="hover:text-brand-400 transition-colors">Membership Plans (₹99/₹399)</Link></li>
              <li><Link href="/about" className="hover:text-brand-400 transition-colors">About Kota RJ Pawan</Link></li>
              <li><a href="https://open.spotify.com" target="_blank" rel="noreferrer" className="hover:text-emerald-400 transition-colors flex items-center gap-1.5"><Music className="w-3.5 h-3.5 text-emerald-400" /> Spotify Podcasts</a></li>
              <li><a href="https://podcasts.apple.com" target="_blank" rel="noreferrer" className="hover:text-purple-400 transition-colors flex items-center gap-1.5"><Music className="w-3.5 h-3.5 text-purple-400" /> Apple Podcasts</a></li>
            </ul>
          </div>
        </div>

        <div className="pt-8 border-t border-gray-800 flex flex-col md:flex-row items-center justify-between text-xs text-gray-500 gap-4">
          <p>© {new Date().getFullYear()} Love Talk Podcast By Kota RJ Pawan. All rights reserved.</p>
          <div className="flex gap-6">
            <Link href="/privacy-policy" className="hover:text-brand-400 transition-colors">Privacy Policy</Link>
            <Link href="/terms-of-service" className="hover:text-brand-400 transition-colors">Terms of Service</Link>
            <Link href="/sitemap" className="hover:text-brand-400 transition-colors">Sitemap</Link>
          </div>
        </div>
      </div>
    </footer>
  );
};
