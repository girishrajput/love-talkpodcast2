import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

// Public (browser-safe) values the src/ code reads via process.env.NEXT_PUBLIC_*.
// They come from .env so local and production builds can differ without code edits.
const PUBLIC_ENV_KEYS = [
  'NEXT_PUBLIC_GOOGLE_CLIENT_ID',
  'NEXT_PUBLIC_RAZORPAY_KEY_ID',
  'NEXT_PUBLIC_RAZORPAY_PLAN_ID_1',
  'NEXT_PUBLIC_RAZORPAY_PLAN_ID_2',
  'NEXT_PUBLIC_APP_URL',
];

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');

  return {
    plugins: [
      laravel({
        input: ['resources/js/app.tsx'],
        refresh: true,
      }),
      react(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src'),
        'next/link': path.resolve(__dirname, 'resources/js/shims/next-link.tsx'),
        'next/image': path.resolve(__dirname, 'resources/js/shims/next-image.tsx'),
        'next/navigation': path.resolve(__dirname, 'resources/js/shims/next-navigation.ts'),
      },
    },
    define: Object.fromEntries(
      PUBLIC_ENV_KEYS.map((key) => [`process.env.${key}`, JSON.stringify(env[key] ?? '')])
    ),
  };
});
