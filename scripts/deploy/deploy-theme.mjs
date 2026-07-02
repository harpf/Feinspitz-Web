// Deployt theme/feinspitz auf den Server — HTTP-only, ohne SSH/WP-CLI.
// REST kennt keinen Custom-Theme-Zip-Upload, daher cookie-authentifizierter Upload
// über wp-admin/update.php?action=upload-theme, danach Aktivierung über themes.php.
//
// Voraussetzungen in .env.local: WP_USER, WP_LOGIN_PASSWORD (für Cookie-Login).
// Ablauf: (1) Zip bauen  (2) Login  (3) Nonce holen  (4) Upload (mit Overwrite)
//         (5) Theme aktivieren.
//
// HINWEIS: Der Overwrite-Flow von update.php ist versionsabhängig; dieses Skript
// behandelt die Bestätigungsseite generisch (Formular auto-resubmit). Beim ersten
// echten Lauf gegen den Server ggf. feinjustieren.

import { readFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { wpAdminSession, WP_BASE } from '../lib/wp.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..', '..');
const ZIP = join(ROOT, 'dist', 'feinspitz.zip');

// 1) Zip sicherstellen.
if (!existsSync(ZIP)) {
  console.log('… dist/feinspitz.zip fehlt, baue es.');
  await import('./build-zip.mjs');
}

function extractNonce(html, action = 'theme-upload') {
  // Feld: <input ... name="_wpnonce" value="XXXX">
  const m = html.match(/name=["']_wpnonce["']\s+value=["']([^"']+)["']/i);
  return m ? m[1] : null;
}

function extractHiddenFields(html) {
  const fields = {};
  const re = /<input[^>]+type=["']hidden["'][^>]*>/gi;
  for (const tag of html.match(re) || []) {
    const n = tag.match(/name=["']([^"']+)["']/i);
    const v = tag.match(/value=["']([^"']*)["']/i);
    if (n) fields[n[1]] = v ? v[1] : '';
  }
  return fields;
}

const session = await wpAdminSession();
console.log('✓ Bei wp-admin eingeloggt.');

// 3) Nonce von der Upload-Seite holen.
const uploadPage = await session.get('/wp-admin/theme-install.php');
const nonce = extractNonce(uploadPage) ||
  extractNonce(await session.get('/wp-admin/update.php?action=upload-theme'));
if (!nonce) throw new Error('Konnte _wpnonce für Theme-Upload nicht finden.');

// 4) Multipart-Upload.
const zipBytes = readFileSync(ZIP);
const fd = new FormData();
fd.set('_wpnonce', nonce);
fd.set('_wp_http_referer', '/wp-admin/theme-install.php');
fd.set('install-theme-submit', 'Jetzt installieren');
fd.set('themezip', new Blob([zipBytes], { type: 'application/zip' }), 'feinspitz.zip');

let res = await session.post('/wp-admin/update.php?action=upload-theme', fd);
let html = await res.text();

// Overwrite-Bestätigung? -> Formular generisch erneut absenden.
if (/bereits installiert|already installed|überschreiben|overwrite|replace/i.test(html)) {
  console.log('… Theme existiert bereits — bestätige Overwrite.');
  const hidden = extractHiddenFields(html);
  const overwriteForm = new URLSearchParams(hidden);
  // WordPress markiert den Overwrite-Submit üblicherweise so:
  if (!overwriteForm.has('overwrite')) overwriteForm.set('overwrite', 'update-theme');
  const res2 = await session.post('/wp-admin/update.php?action=upload-theme', overwriteForm);
  html = await res2.text();
}

if (/erfolgreich|successfully|Theme installiert|Theme updated/i.test(html)) {
  console.log('✓ Theme hochgeladen.');
} else {
  console.warn('⚠ Upload-Ergebnis unklar — bitte wp-admin prüfen. Antwort-Auszug:');
  console.warn(html.replace(/\s+/g, ' ').slice(0, 400));
}

// 5) Aktivieren (cookie-auth themes.php).
const themesPage = await session.get('/wp-admin/themes.php');
const actNonceMatch = themesPage.match(/themes\.php\?action=activate&(?:amp;)?stylesheet=feinspitz&(?:amp;)?_wpnonce=([a-z0-9]+)/i);
if (actNonceMatch) {
  await session.get(`/wp-admin/themes.php?action=activate&stylesheet=feinspitz&_wpnonce=${actNonceMatch[1]}`);
  console.log('✓ Theme "feinspitz" aktiviert.');
} else {
  console.log('ℹ Aktivierungs-Link nicht gefunden — evtl. schon aktiv. Prüfe wp-admin → Design.');
}

console.log(`Fertig. Seite: ${WP_BASE}`);
