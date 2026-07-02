// Packt theme/feinspitz → dist/feinspitz.zip (für den wp-admin-Theme-Upload).
import AdmZip from 'adm-zip';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { mkdirSync, existsSync } from 'node:fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..', '..');
const THEME_DIR = join(ROOT, 'theme', 'feinspitz');
const DIST = join(ROOT, 'dist');
const OUT = join(DIST, 'feinspitz.zip');

if (!existsSync(THEME_DIR)) {
  console.error(`✗ Theme-Ordner fehlt: ${THEME_DIR}`);
  process.exit(1);
}

mkdirSync(DIST, { recursive: true });
const zip = new AdmZip();
// Als Unterordner "feinspitz/" packen (WordPress erwartet Theme im Ordner).
zip.addLocalFolder(THEME_DIR, 'feinspitz');
zip.writeZip(OUT);
console.log(`✓ Theme gepackt → ${OUT}`);
