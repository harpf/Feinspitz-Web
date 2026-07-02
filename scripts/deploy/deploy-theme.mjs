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

// 1) Zip IMMER frisch bauen (sonst wird versehentlich ein veralteter Stand deployt).
await import('./build-zip.mjs');

function extractNonce(html, action = 'theme-upload') {
  // Feld: <input ... name="_wpnonce" value="XXXX">
  const m = html.match(/name=["']_wpnonce["']\s+value=["']([^"']+)["']/i);
  return m ? m[1] : null;
}

function decodeEntities(s) {
  return s.replace(/&amp;/g, '&').replace(/&#0?38;/g, '&');
}

/** Findet den "Installiertes durch Hochgeladenes ersetzen"-Link (Overwrite). */
function extractOverwritePath(html) {
  const m = html.match(/class="[^"]*update-from-upload-overwrite[^"]*"\s+href="([^"]+)"/i);
  if (!m) return null;
  let href = decodeEntities(m[1]).replace(/^\/?wp-admin\//, '');
  return `/wp-admin/${href}`;
}

const isSuccess = (html) =>
  /erfolgreich (installiert|aktualisiert)|successfully (installed|updated)|Theme (aktualisiert|updated|installiert)/i.test(html);

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

const res = await session.post('/wp-admin/update.php?action=upload-theme', fd);
let html = await res.text();

// Existiert das Theme schon, zeigt WordPress einen Overwrite-Link ("… ersetzen").
const overwritePath = extractOverwritePath(html);
if (overwritePath) {
  console.log('… Theme existiert bereits — ersetze bestehende Version.');
  html = await session.get(overwritePath);
}

if (isSuccess(html)) {
  console.log('✓ Theme hochgeladen/aktualisiert.');
} else if (/Der Zielordner existiert bereits|destination folder already exists/i.test(html)) {
  console.warn('⚠ Overwrite-Bestätigung konnte nicht gefolgt werden — bitte wp-admin prüfen.');
  process.exitCode = 1;
} else {
  console.warn('⚠ Upload-Ergebnis unklar — Antwort-Auszug:');
  console.warn(html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 300));
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
