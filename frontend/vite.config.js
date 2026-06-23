import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import fs from 'fs';
console.log('>>> laravel plugin loaded:', typeof laravel);

// Tự copy .vite/manifest.json -> manifest.json sau MỖI lần build
// (thay cho move-manifest.js, chạy được cả ở watch mode)
function copyManifestPlugin() {
    return {
        name: 'copy-manifest-to-build-root',
        closeBundle() {
            const src = path.resolve(__dirname, '../backend/public/build/.vite/manifest.json');
            const dest = path.resolve(__dirname, '../backend/public/build/manifest.json');

            if (fs.existsSync(src)) {
                fs.copyFileSync(src, dest);
                console.log('✅ Copied manifest.json to build root');
            } else {
                console.warn('⚠️ .vite/manifest.json not found, skip copy');
            }
        },
    };
}

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
        copyManifestPlugin(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        https: {
            key: fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2-key.pem')),
            cert: fs.readFileSync(path.resolve(__dirname, 'certs/localhost+2.pem'))
        },
        cors: true,
        origin: 'https://magnetism-obsessive-emit.ngrok-free.dev',
        allowedHosts: ['magnetism-obsessive-emit.ngrok-free.dev'],
        hmr: {
            host: 'magnetism-obsessive-emit.ngrok-free.dev',
            protocol: 'wss',
            clientPort: 443,
        },
    },
    build: {
        outDir: '../backend/public/build',
        manifest: true,
        emptyOutDir: true,
    },
});