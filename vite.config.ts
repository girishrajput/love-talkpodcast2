import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
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
  define: {
    'process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID': JSON.stringify('294853883487-kpq1sh4j7c417qjdrco4uhk7d9rrm8m5.apps.googleusercontent.com'),
    'process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID': JSON.stringify('rzp_live_YOUR_LIVE_KEY_ID'),
    'process.env.NEXT_PUBLIC_RAZORPAY_PLAN_ID_1': JSON.stringify('plan_TUDEcZ2uLPWkYl'),
    'process.env.NEXT_PUBLIC_RAZORPAY_PLAN_ID_2': JSON.stringify('plan_TUDCZ8dhD0OOmu'),
    'process.env.NEXT_PUBLIC_APP_URL': JSON.stringify('http://localhost/love-talkpodcast2/public'),
  },
});
