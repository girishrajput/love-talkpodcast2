'use client';

import React, { useState, useEffect, useMemo } from 'react';
import { Search, Filter, Globe, LayoutGrid, List, SlidersHorizontal, Loader2, ChevronLeft, ChevronRight } from 'lucide-react';
import { fetchEpisodes } from '@/lib/api';
import { Episode, CategoryTag } from '@/lib/types';
import { useLanguage } from '@/context/LanguageContext';
import { EpisodeCard } from '@/components/EpisodeCard';

export default function EpisodesPage() {
  const { t } = useLanguage();
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedLang, setSelectedLang] = useState('all');
  const [selectedTag, setSelectedTag] = useState<string>('All');
  const [sortOrder, setSortOrder] = useState<'newest' | 'oldest' | 'popular'>('newest');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [episodes, setEpisodes] = useState<Episode[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 12;

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const data = await fetchEpisodes({
        search: searchQuery,
        tag: selectedTag,
        language: selectedLang
      });
      setEpisodes(data);
      setIsLoading(false);
      setCurrentPage(1);
    }
    const timer = setTimeout(load, 300);
    return () => clearTimeout(timer);
  }, [searchQuery, selectedTag, selectedLang]);

  const categories: CategoryTag[] = [
    'All',
    'Self-Love',
    'Dating & Relationships',
    'Communication',
    'Emotional Wellness',
    'Marriage & Future',
    'Hindi Special'
  ];

  // Filtering & Sorting
  const filtered = useMemo(() => {
    const list = episodes.filter((ep) => {
      const q = searchQuery.toLowerCase().trim();
      const matchesQuery = !q || 
        ep.title.toLowerCase().includes(q) ||
        ep.description.toLowerCase().includes(q) ||
        (ep.tags && ep.tags.some(t => t.toLowerCase().includes(q)));

      const matchesLang = selectedLang === 'all' || ep.language === selectedLang;
      const matchesTag = selectedTag === 'All' || (ep.tags && ep.tags.includes(selectedTag));

      return matchesQuery && matchesLang && matchesTag;
    });

    if (sortOrder === 'newest') {
      list.sort((a, b) => new Date(b.publish_date).getTime() - new Date(a.publish_date).getTime());
    } else if (sortOrder === 'oldest') {
      list.sort((a, b) => new Date(a.publish_date).getTime() - new Date(b.publish_date).getTime());
    } else if (sortOrder === 'popular') {
      list.sort((a, b) => b.listens_count - a.listens_count);
    }
    return list;
  }, [episodes, searchQuery, selectedLang, selectedTag, sortOrder]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / itemsPerPage));
  const paginatedEpisodes = useMemo(() => {
    const start = (currentPage - 1) * itemsPerPage;
    return filtered.slice(start, start + itemsPerPage);
  }, [filtered, currentPage, itemsPerPage]);

  return (
    <div className="space-y-8 pb-16">
      
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-brand-950 via-gray-900 to-gray-950 p-8 sm:p-12 rounded-3xl text-white border border-brand-800/40 space-y-4">
        <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
          Episode Archive
        </h1>
        <p className="text-gray-300 text-sm sm:text-base max-w-2xl">
          Browse all episodes of Love Talk Podcast hosted By Kota RJ Pawan. Filter by topic, language (English / Hindi), or search for specific relationship advice.
        </p>
      </div>

      {/* Control Toolbar */}
      <div className="bg-white dark:bg-gray-900 p-6 rounded-3xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm space-y-4">
        
        {/* Top Row: Search & View Mode */}
        <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div className="relative w-full md:w-96">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search episodes, topics, tags..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-11 pr-4 py-3 rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-gray-900 dark:text-white"
            />
          </div>

          <div className="flex items-center gap-4 w-full md:w-auto justify-between">
            {/* Sort Select */}
            <div className="flex items-center gap-2 text-xs font-semibold text-gray-500">
              <SlidersHorizontal className="w-4 h-4" />
              <span>Sort:</span>
              <select
                value={sortOrder}
                onChange={(e) => setSortOrder(e.target.value as any)}
                className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white font-medium focus:outline-none"
              >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="popular">Most Popular</option>
              </select>
            </div>

            {/* View Mode */}
            <div className="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
              <button
                onClick={() => setViewMode('grid')}
                className={`p-2 rounded-lg transition-colors ${viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow text-brand-600 dark:text-white' : 'text-gray-400'}`}
                title="Grid View"
              >
                <LayoutGrid className="w-4 h-4" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={`p-2 rounded-lg transition-colors ${viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow text-brand-600 dark:text-white' : 'text-gray-400'}`}
                title="List View"
              >
                <List className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        {/* Bottom Row: Language Pills */}
        <div className="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs">
          <div className="flex items-center gap-2">
            <span className="font-semibold text-gray-500">Language:</span>
            <button
              onClick={() => setSelectedLang('all')}
              className={`px-3 py-1.5 rounded-xl font-bold transition-all ${selectedLang === 'all' ? 'bg-brand-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'}`}
            >
              All ({episodes.length})
            </button>
            <button
              onClick={() => setSelectedLang('english')}
              className={`px-3 py-1.5 rounded-xl font-bold transition-all ${selectedLang === 'english' ? 'bg-brand-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'}`}
            >
              English
            </button>
            <button
              onClick={() => setSelectedLang('hindi')}
              className={`px-3 py-1.5 rounded-xl font-bold transition-all ${selectedLang === 'hindi' ? 'bg-brand-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'}`}
            >
              हिंदी Special
            </button>
            <button
              onClick={() => setSelectedLang('bilingual')}
              className={`px-3 py-1.5 rounded-xl font-bold transition-all ${selectedLang === 'bilingual' ? 'bg-brand-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'}`}
            >
              Bilingual
            </button>
          </div>

          <span className="text-gray-400 font-mono">
            Showing {filtered.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1} - {Math.min(currentPage * itemsPerPage, filtered.length)} of {filtered.length} episodes
          </span>
        </div>

      </div>

      {/* Grid or List View */}
      {filtered.length === 0 ? (
        <div className="p-16 text-center bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 space-y-3">
          <Search className="w-10 h-10 text-gray-400 mx-auto" />
          <h3 className="font-bold text-gray-900 dark:text-white text-lg">No episodes match your search criteria</h3>
          <p className="text-xs text-gray-500">Try clearing filters or searching for different keywords.</p>
        </div>
      ) : (
        <div className={viewMode === 'grid' ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" : "space-y-4"}>
          {paginatedEpisodes.map((ep) => (
            <EpisodeCard key={ep.id} episode={ep} />
          ))}
        </div>
      )}

      {/* Pagination Controls */}
      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-2 pt-8">
          <button
            onClick={() => { setCurrentPage(p => Math.max(1, p - 1)); window.scrollTo({ top: 300, behavior: 'smooth' }); }}
            disabled={currentPage === 1}
            className="px-4 py-2 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5 shadow-sm"
          >
            <ChevronLeft className="w-4 h-4" /> Previous
          </button>

          <span className="px-4 py-2 text-xs font-semibold text-gray-500">
            Page {currentPage} of {totalPages}
          </span>

          <button
            onClick={() => { setCurrentPage(p => Math.min(totalPages, p + 1)); window.scrollTo({ top: 300, behavior: 'smooth' }); }}
            disabled={currentPage === totalPages}
            className="px-4 py-2 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5 shadow-sm"
          >
            Next <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      )}

    </div>
  );
}
