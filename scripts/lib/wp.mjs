// Zentrale REST-/HTTP-Bibliothek für die Feinspitz-WordPress-Automation.
// Zugriff nur über HTTP: WP-REST (wp/v2), WooCommerce (wc/v3), plus cookie-auth
// wp-admin-Upload fürs Theme (REST kennt keinen Custom-Theme-Zip-Upload).
//
// Auth: Application Password (Basic Auth). Das normale Login-Passwort funktioniert
// per REST NICHT — dafür braucht es ein Anwendungspasswort (WP 5.6+).

import { readFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..', '..');

/** Lädt .env.local (KEY=VALUE je Zeile) in ein Objekt — ohne Zusatz-Dependency. */
export function loadEnv() {
  const file = join(ROOT, '.env.local');
  const env = { ...process.env };
  if (existsSync(file)) {
    for (const raw of readFileSync(file, 'utf8').split(/\r?\n/)) {
      const line = raw.trim();
      if (!line || line.startsWith('#')) continue;
      const eq = line.indexOf('=');
      if (eq === -1) continue;
      env[line.slice(0, eq).trim()] = line.slice(eq + 1).trim();
    }
  }
  return env;
}

const ENV = loadEnv();
export const WP_BASE = (ENV.WP_BASE || 'https://feinspitz.alpenmesh.de').replace(/\/+$/, '');
export const WP_USER = ENV.WP_USER || 'automation';
const WP_APP_PASSWORD = (ENV.WP_APP_PASSWORD || '').trim();
const WP_LOGIN_PASSWORD = (ENV.WP_LOGIN_PASSWORD || '').trim();

function authHeader() {
  if (!WP_APP_PASSWORD) {
    throw new Error(
      'WP_APP_PASSWORD fehlt. Lege .env.local an (Vorlage: .env.local.example) und trage das ' +
      'Application Password des Users "automation" ein (wp-admin → Profil → Anwendungspasswörter).'
    );
  }
  const token = Buffer.from(`${WP_USER}:${WP_APP_PASSWORD}`).toString('base64');
  return `Basic ${token}`;
}

/**
 * REST-Call. `route` z. B. "/wp/v2/users/me" oder "/wc/v3/products".
 * Nutzt die ?rest_route=-Form, damit es auch ohne "hübsche" Permalinks funktioniert.
 */
export async function wp(route, { method = 'GET', body, query } = {}) {
  const params = new URLSearchParams({ rest_route: route });
  if (query) for (const [k, v] of Object.entries(query)) params.set(k, String(v));
  const url = `${WP_BASE}/?${params.toString()}`;

  const headers = { Authorization: authHeader() };
  let payload;
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const res = await fetch(url, { method, headers, body: payload });
  const text = await res.text();
  let data;
  try { data = text ? JSON.parse(text) : null; } catch { data = text; }

  if (!res.ok) {
    const msg = data && data.message ? data.message : text;
    const err = new Error(`WP ${method} ${route} → ${res.status}: ${msg}`);
    err.status = res.status;
    err.data = data;
    throw err;
  }
  return data;
}

/** Bequemer WooCommerce-Wrapper (wc/v3). */
export const wc = (route, opts) => wp(`/wc/v3${route}`, opts);

/**
 * Cookie-basierter wp-admin-Login (für Theme-Upload, den REST nicht abdeckt).
 * Gibt { cookie, get, post } zurück. Braucht WP_LOGIN_PASSWORD in .env.local.
 */
export async function wpAdminSession() {
  if (!WP_LOGIN_PASSWORD) {
    throw new Error('WP_LOGIN_PASSWORD fehlt in .env.local (für cookie-auth Theme-Upload nötig).');
  }
  const jar = new Map();
  const store = (res) => {
    for (const c of res.headers.getSetCookie?.() ?? []) {
      const [pair] = c.split(';');
      const eq = pair.indexOf('=');
      if (eq > -1) jar.set(pair.slice(0, eq).trim(), pair.slice(eq + 1).trim());
    }
  };
  const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');

  // 1) Login-Cookie holen.
  const form = new URLSearchParams({
    log: WP_USER,
    pwd: WP_LOGIN_PASSWORD,
    'wp-submit': 'Log In',
    redirect_to: `${WP_BASE}/wp-admin/`,
    testcookie: '1',
  });
  const loginRes = await fetch(`${WP_BASE}/wp-login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Cookie: 'wordpress_test_cookie=WP+Cookie+check' },
    body: form.toString(),
    redirect: 'manual',
  });
  store(loginRes);
  if (![302, 200].includes(loginRes.status) || ![...jar.keys()].some((k) => k.startsWith('wordpress_logged_in'))) {
    throw new Error(`wp-admin Login fehlgeschlagen (Status ${loginRes.status}). Passwort prüfen.`);
  }

  const get = async (path) => {
    const res = await fetch(`${WP_BASE}${path}`, { headers: { Cookie: cookieHeader() } });
    store(res);
    return res.text();
  };
  const post = async (path, formData) => {
    const res = await fetch(`${WP_BASE}${path}`, {
      method: 'POST',
      headers: { Cookie: cookieHeader() },
      body: formData,
      redirect: 'manual',
    });
    store(res);
    return res;
  };
  return { get, post, cookieHeader };
}
