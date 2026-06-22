import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const src = path.resolve(__dirname, '../backend/public/build/.vite/manifest.json');
const dest = path.resolve(__dirname, '../backend/public/build/manifest.json');

if (fs.existsSync(src)) {
    fs.copyFileSync(src, dest);
    console.log('✅ Copied manifest.json to build root');
} else {
    console.warn('⚠️ .vite/manifest.json not found, skip copy');
}