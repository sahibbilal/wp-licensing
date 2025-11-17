import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'build',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'admin-app/index.jsx'),
      },
      output: {
        entryFileNames: 'admin-app.js',
        chunkFileNames: 'admin-app-[name].js',
        assetFileNames: 'admin-app.[ext]',
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'admin-app'),
    },
  },
});

