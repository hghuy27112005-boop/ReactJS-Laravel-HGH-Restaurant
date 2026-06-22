import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import fs from 'fs';
console.log('>>> laravel plugin loaded:', typeof laravel);

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
        },
    },
    plugins: [
        laravel({
            input: ['src/app.jsx'],
            publicDirectory: '../backend/public',
            buildDirectory: 'build',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        port: 5173,
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2-key.pem')),
            cert: fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2.pem'))
        }
    },
    build: {
        outDir: '../backend/public/build',
        manifest: true,
        emptyOutDir: true,
    },
});