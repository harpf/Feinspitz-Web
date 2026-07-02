// Shared helpers for the feinspitz.ch scraper.
//
// Quelle ist eine Jimdo-Creator-Site (kein WordPress/REST) → reines HTML-Scraping.
// Wir bleiben höflich (User-Agent + Crawl-Delay laut robots.txt = 5s) und parsen
// die bekannte Jimdo-Katalog-Struktur mit gezielten Regexen (keine Zusatz-Deps).

import { setTimeout as sleep } from 'node:timers/promises';

export const SOURCE_BASE = 'https://www.feinspitz.ch';
export const CRAWL_DELAY_MS = 5000; // robots.txt: Crawl-Delay: 5
const USER_AGENT =
  'feinspitz-migration/1.0 (+content migration for feinspitz.ch rebuild; contact automation)';

let lastFetch = 0;

/** Höflicher GET mit fixem Crawl-Delay + einfachem Retry. Gibt Text zurück. */
export async function fetchText(url, opts = {}) {
  const buf = await fetchBuffer(url, opts);
  return buf.toString('utf-8');
}

/**
 * Höflicher GET, gibt Buffer zurück (für Medien-Download).
 * `delayMs` überschreibt den Standard-Crawl-Delay (z. B. für den jimcdn-CDN-Host,
 * der nicht unter dem site-robots.txt Crawl-Delay steht).
 */
export async function fetchBuffer(url, { retries = 2, delayMs = CRAWL_DELAY_MS } = {}) {
  const wait = delayMs - (Date.now() - lastFetch);
  if (wait > 0) await sleep(wait);
  let lastErr;
  for (let attempt = 0; attempt <= retries; attempt++) {
    try {
      const res = await fetch(url, {
        headers: { 'User-Agent': USER_AGENT, 'Accept-Language': 'de-CH,de;q=0.9' },
        redirect: 'follow',
      });
      lastFetch = Date.now();
      if (!res.ok) throw new Error(`HTTP ${res.status} für ${url}`);
      return Buffer.from(await res.arrayBuffer());
    } catch (e) {
      lastErr = e;
      lastFetch = Date.now();
      if (attempt < retries) await sleep(1500 * (attempt + 1));
    }
  }
  throw lastErr;
}

// --- HTML-Helfer -----------------------------------------------------------

const ENTITIES = {
  amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ',
  auml: 'ä', ouml: 'ö', uuml: 'ü', Auml: 'Ä', Ouml: 'Ö', Uuml: 'Ü',
  szlig: 'ß', euro: '€', deg: '°', eacute: 'é', egrave: 'è', agrave: 'à',
  hellip: '…', ndash: '–', mdash: '—', laquo: '«', raquo: '»',
};

/** Dekodiert HTML-Entities (benannt + numerisch). */
export function decodeEntities(str = '') {
  return str
    .replace(/&#x([0-9a-f]+);/gi, (_, h) => String.fromCodePoint(parseInt(h, 16)))
    .replace(/&#(\d+);/g, (_, d) => String.fromCodePoint(parseInt(d, 10)))
    .replace(/&([a-z]+);/gi, (m, n) => (n in ENTITIES ? ENTITIES[n] : m));
}

/** Entfernt Tags, dekodiert Entities, kollabiert Whitespace. */
export function stripHtml(html = '') {
  return decodeEntities(
    html
      .replace(/<(script|style)[\s\S]*?<\/\1>/gi, '')
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<\/(p|div|li|h[1-6]|tr)>/gi, '\n')
      .replace(/<[^>]+>/g, ' '),
  )
    .replace(/[ \t\f\v]+/g, ' ')
    .replace(/\n\s*\n\s*\n+/g, '\n\n')
    .split('\n')
    .map((l) => l.trim())
    .join('\n')
    .trim();
}

/** slugify für Kategorie-/Produkt-Slugs (Umlaute → ae/oe/ue). */
export function slugify(str = '') {
  return str
    .toLowerCase()
    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
    .replace(/[àáâ]/g, 'a').replace(/[èéêë]/g, 'e').replace(/[ìíî]/g, 'i')
    .replace(/[òóô]/g, 'o').replace(/[ùúû]/g, 'u')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');
}

/** Extrahiert Meta-Content (og:*, name=*) aus einem HTML-Dokument. */
export function meta(html, prop) {
  const re = new RegExp(
    `<meta[^>]+(?:property|name)=["']${prop.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}["'][^>]*content=["']([^"']*)["']`,
    'i',
  );
  const m = html.match(re);
  return m ? decodeEntities(m[1]).trim() : '';
}

/**
 * Wandelt eine jimcdn-Bild-URL auf eine größere Auflösung um (Dimension in der
 * transf-Segment-URL neu setzen). Gibt {url, ext} zurück.
 */
export function upscaleJimcdn(url, dim = '1200x1200') {
  const big = url.replace(/dimension=\d+x\d+/i, `dimension=${dim}`);
  const fmt = (big.match(/format=([a-z0-9]+)/i) || [, 'jpg'])[1].toLowerCase();
  const ext = fmt === 'jpeg' ? 'jpg' : fmt;
  return { url: big, ext };
}
