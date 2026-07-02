// Zentrale REST-/HTTP-Bibliothek für die Feinspitz-WordPress-Automation.
// Zugriff nur über HTTP.
//
// WICHTIG: Der Server entfernt den Authorization-Header, bevor er PHP erreicht
// (Basic-Auth mit Application Password → rest_not_logged_in). Daher authentifizieren
// wir über den WordPress-Cookie-Login + X-WP-Nonce (funktioniert für wp/v2 UND wc/v3
// bei eingeloggten Admins). Braucht WP_USER + WP_LOGIN_PASSWORD in .env.local.

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
const WP_LOGIN_PASSWORD = (ENV.WP_LOGIN_PASSWORD || '').trim();

// --- Cookie-Session (Singleton) -------------------------------------------

const jar = new Map();
let restNonce = null;
let loggedIn = false;

function store(res) {
  for (const c of res.headers.getSetCookie?.() ?? []) {
    const [pair] = c.split(';');
    const eq = pair.indexOf('=');
    if (eq > -1) jar.set(pair.slice(0, eq).trim(), pair.slice(eq + 1).trim());
  }
}
const cookieHeader = () => [...jar].map(([k, v]) => `${k}=${v}`).join('; ');

async function login() {
  if (loggedIn) return;
  if (!WP_LOGIN_PASSWORD) {
    throw new Error(
      'WP_LOGIN_PASSWORD fehlt in .env.local. Für die HTTP-Automation (Cookie+Nonce) ' +
      'wird der wp-admin-Login des Users "' + WP_USER + '" benötigt.'
    );
  }
  const form = new URLSearchParams({
    log: WP_USER,
    pwd: WP_LOGIN_PASSWORD,
    'wp-submit': 'Log In',
    redirect_to: `${WP_BASE}/wp-admin/`,
    testcookie: '1',
  });
  const res = await fetch(`${WP_BASE}/wp-login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Cookie: 'wordpress_test_cookie=WP+Cookie+check' },
    body: form.toString(),
    redirect: 'manual',
  });
  store(res);
  if (![...jar.keys()].some((k) => k.startsWith('wordpress_logged_in'))) {
    throw new Error(`wp-admin Login fehlgeschlagen (Status ${res.status}). WP_LOGIN_PASSWORD prüfen.`);
  }
  loggedIn = true;
}

async function ensureNonce() {
  await login();
  if (restNonce) return restNonce;
  const res = await fetch(`${WP_BASE}/wp-admin/admin-ajax.php?action=rest-nonce`, {
    headers: { Cookie: cookieHeader() },
  });
  store(res);
  restNonce = (await res.text()).trim();
  if (!/^[a-z0-9]+$/i.test(restNonce)) throw new Error('Konnte REST-Nonce nicht ermitteln.');
  return restNonce;
}

// --- REST ------------------------------------------------------------------

/**
 * REST-Call via Cookie+Nonce. `route` z. B. "/wp/v2/users/me" oder "/wc/v3/products".
 * Nutzt ?rest_route= (funktioniert ohne "hübsche" Permalinks).
 */
export async function wp(route, { method = 'GET', body, query } = {}) {
  const nonce = await ensureNonce();
  const params = new URLSearchParams({ rest_route: route });
  if (query) for (const [k, v] of Object.entries(query)) params.set(k, String(v));
  const url = `${WP_BASE}/?${params.toString()}`;

  const headers = { Cookie: cookieHeader(), 'X-WP-Nonce': nonce };
  let payload;
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  const res = await fetch(url, { method, headers, body: payload });
  store(res);
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
 * Cookie-basierte wp-admin-Session (für Formular-Uploads wie den Theme-Upload,
 * den REST nicht abdeckt). Teilt sich Cookie-Jar & Login mit dem REST-Client.
 */
export async function wpAdminSession() {
  await login();
  const get = async (path) => {
    const res = await fetch(`${WP_BASE}${path}`, { headers: { Cookie: cookieHeader() } });
    store(res);
    return res.text();
  };
  const post = async (path, bodyData) => {
    const headers = { Cookie: cookieHeader() };
    let body = bodyData;
    if (bodyData instanceof URLSearchParams) headers['Content-Type'] = 'application/x-www-form-urlencoded';
    const res = await fetch(`${WP_BASE}${path}`, { method: 'POST', headers, body, redirect: 'manual' });
    store(res);
    return res;
  };
  return { get, post, cookieHeader };
}
