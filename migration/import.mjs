// Idempotenter Content-Import: migration/data/*.json → Server-WordPress (HTTP-only).
//
//   npm run content:import              # voller Import
//   node migration/import.mjs --dry-run # nur lesen/planen, nichts schreiben
//   node migration/import.mjs --no-media
//
// Abgleich per SKU (Produkte) bzw. Slug (Kategorien, Tags, Seiten, Medien) →
// Mehrfachlauf erzeugt KEINE Duplikate. Nutzt scripts/lib/wp.mjs:
//   wc()  für /wc/v3/products, /products/categories, /products/tags
//   wp()  für /wp/v2/media, /wp/v2/pages, /pll/v1/languages
// Medien-Upload (Binär + X-WP-Nonce) über die geteilte Cookie-Session.

import { readFile } from 'node:fs/promises';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, extname } from 'node:path';
import { wp, wc, WP_BASE, wpAdminSession } from '../scripts/lib/wp.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const DATA_DIR = join(__dirname, 'data');
const MEDIA_DIR = join(DATA_DIR, 'media');

const args = process.argv.slice(2);
const DRY = args.includes('--dry-run');
const NO_MEDIA = args.includes('--no-media');

const MIME = { png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg', gif: 'image/gif', webp: 'image/webp' };
const FLAG_TAGS = {
  histamingeprueft: { name: 'histamingeprüft', slug: 'histamingeprueft' },
  vegan: { name: 'vegan', slug: 'vegan' },
  alkoholfrei: { name: 'alkoholfrei', slug: 'alkoholfrei' },
};

const log = (...a) => console.log(...a);
async function readJson(name) {
  const p = join(DATA_DIR, name);
  if (!existsSync(p)) return null;
  return JSON.parse(await readFile(p, 'utf-8'));
}
/** Holt alle Seiten einer paginierten WC/WP-Collection. */
async function fetchAll(fn) {
  const out = [];
  for (let page = 1; page <= 50; page++) {
    const batch = await fn(page);
    if (!Array.isArray(batch) || batch.length === 0) break;
    out.push(...batch);
    if (batch.length < 100) break;
  }
  return out;
}

// --- Polylang-Sprachen -----------------------------------------------------

async function ensureLanguages() {
  let langs = [];
  try { langs = await wp('/pll/v1/languages'); } catch (e) {
    log(`  ⚠ Polylang-REST nicht erreichbar (${e.status || e.message}) — Sprachen übersprungen.`);
    return;
  }
  const have = new Set((langs || []).map((l) => l.slug));
  const want = [
    { name: 'Deutsch', locale: 'de_DE', slug: 'de', flag: 'de', rtl: false, term_group: 0 },
    { name: 'English', locale: 'en_US', slug: 'en', flag: 'us', rtl: false, term_group: 1 },
  ];
  for (const l of want) {
    if (have.has(l.slug)) { log(`  = Sprache ${l.slug} vorhanden`); continue; }
    if (DRY) { log(`  + [dry] Sprache ${l.slug} würde angelegt`); continue; }
    try {
      await wp('/pll/v1/languages', { method: 'POST', body: l });
      log(`  + Sprache ${l.slug} angelegt`);
    } catch (e) {
      log(`  ⚠ Sprache ${l.slug} konnte nicht per REST angelegt werden (${e.status || ''} ${e.message}).`);
      log('    → Polylang (frei) erlaubt evtl. kein REST-Anlegen. Einmalig unter ' +
          'wp-admin → Sprachen anlegen: Deutsch (Standard) + English.');
      return;
    }
  }
}

// --- Product-Tags (Flags) --------------------------------------------------

async function ensureTags() {
  const existing = await fetchAll((page) => wc('/products/tags', { query: { per_page: 100, page } }));
  const bySlug = new Map(existing.map((t) => [t.slug, t.id]));
  const ids = {};
  for (const [flag, def] of Object.entries(FLAG_TAGS)) {
    if (bySlug.has(def.slug)) { ids[flag] = bySlug.get(def.slug); continue; }
    if (DRY) { log(`  + [dry] Tag ${def.slug}`); continue; }
    const t = await wc('/products/tags', { method: 'POST', body: { name: def.name, slug: def.slug } });
    ids[flag] = t.id;
    log(`  + Tag ${def.name}`);
  }
  return ids; // flag → tagId
}

// --- Kategorien ------------------------------------------------------------

async function ensureCategories(cats) {
  const existing = await fetchAll((page) => wc('/products/categories', { query: { per_page: 100, page } }));
  const bySlug = new Map(existing.map((c) => [c.slug, c]));
  const idBySlug = new Map(existing.map((c) => [c.slug, c.id]));

  // Nach Tiefe sortieren (Eltern zuerst), damit parent-IDs verfügbar sind.
  const depth = (c) => {
    let d = 0, cur = c;
    const map = new Map(cats.map((x) => [x.slug, x]));
    while (cur && cur.parent) { d++; cur = map.get(cur.parent); if (d > 10) break; }
    return d;
  };
  const ordered = [...cats].sort((a, b) => depth(a) - depth(b));

  for (const c of ordered) {
    const parentId = c.parent ? idBySlug.get(c.parent) || 0 : 0;
    if (bySlug.has(c.slug)) {
      const cur = bySlug.get(c.slug);
      idBySlug.set(c.slug, cur.id);
      // Name/Parent bei Bedarf angleichen
      if (!DRY && (cur.name !== c.name || (cur.parent || 0) !== parentId)) {
        await wc(`/products/categories/${cur.id}`, { method: 'PUT', body: { name: c.name, parent: parentId } });
      }
      continue;
    }
    if (DRY) { log(`  + [dry] Kategorie ${c.slug} (parent ${c.parent || '-'})`); idBySlug.set(c.slug, -1); continue; }
    const created = await wc('/products/categories', {
      method: 'POST', body: { name: c.name, slug: c.slug, parent: parentId, description: c.description || '' },
    });
    idBySlug.set(c.slug, created.id);
    log(`  + Kategorie ${c.name}`);
  }
  return idBySlug; // slug → id
}

// --- Medien ----------------------------------------------------------------

let _session = null, _nonce = null;
async function session() {
  if (_session) return { sess: _session, nonce: _nonce };
  _session = await wpAdminSession();
  _nonce = (await _session.get('/wp-admin/admin-ajax.php?action=rest-nonce')).trim();
  if (!/^[a-z0-9]+$/i.test(_nonce)) throw new Error('REST-Nonce für Medien-Upload nicht ermittelbar.');
  return { sess: _session, nonce: _nonce };
}

/** Lädt (idempotent) ein Produktbild hoch. Gibt media-id zurück oder null. */
async function ensureMedia(product) {
  if (!product.media || !product.media.file) return null;
  const file = product.media.file;
  const dest = join(MEDIA_DIR, file);
  if (!existsSync(dest)) return null;
  const slug = `feinspitz-${product.sku}`;

  // Vorhandenes Medium per Slug finden → keine Duplikate
  const found = await wp('/wp/v2/media', { query: { search: slug, per_page: 30 } });
  const hit = Array.isArray(found) ? found.find((m) => m.slug === slug || m.slug.startsWith(slug)) : null;
  if (hit) return hit.id;
  if (DRY) return -1;

  const ext = extname(file).slice(1).toLowerCase();
  const mime = MIME[ext] || 'application/octet-stream';
  const buf = readFileSync(dest);
  const { sess, nonce } = await session();
  const res = await fetch(`${WP_BASE}/?rest_route=/wp/v2/media`, {
    method: 'POST',
    headers: {
      Cookie: sess.cookieHeader(), 'X-WP-Nonce': nonce,
      'Content-Type': mime, 'Content-Disposition': `attachment; filename="${slug}.${ext}"`,
    },
    body: buf,
  });
  const text = await res.text();
  if (!res.ok) throw new Error(`Medien-Upload ${product.sku} → ${res.status}: ${text.slice(0, 160)}`);
  const media = JSON.parse(text);
  // Titel/Alt setzen (best effort)
  try { await wp(`/wp/v2/media/${media.id}`, { method: 'POST', body: { title: product.name, alt_text: product.name } }); } catch { /* egal */ }
  return media.id;
}

// --- Produkte --------------------------------------------------------------

async function importProducts(products, catIds, tagIds) {
  const stats = { created: 0, updated: 0, failed: 0, media: 0 };
  for (const p of products) {
    try {
      let mediaId = null;
      if (!NO_MEDIA) { mediaId = await ensureMedia(p); if (mediaId && mediaId > 0) stats.media++; }

      const categories = (p.categories || []).map((s) => catIds.get(s)).filter((id) => id && id > 0).map((id) => ({ id }));
      const tags = Object.entries(p.flags || {}).filter(([, on]) => on)
        .map(([flag]) => tagIds[flag]).filter((id) => id && id > 0).map((id) => ({ id }));
      const images = mediaId && mediaId > 0 ? [{ id: mediaId, alt: p.name }]
        : (p.image ? [{ src: p.image, alt: p.name }] : []);

      const body = {
        name: p.name,
        slug: p.slug,
        type: 'simple',
        status: 'publish',
        sku: p.sku,
        regular_price: p.price != null ? Number(p.price).toFixed(2) : '',
        description: p.description || '',
        short_description: (p.description || '').split('\n')[0].slice(0, 300),
        categories,
        tags,
        images,
        catalog_visibility: 'visible',
      };

      const match = await wc('/products', { query: { sku: p.sku } });
      const found = Array.isArray(match) ? match.find((m) => m.sku === p.sku) : null;
      if (found) {
        if (!DRY) await wc(`/products/${found.id}`, { method: 'PUT', body });
        stats.updated++;
      } else {
        if (DRY) { log(`  + [dry] Produkt ${p.name}`); stats.created++; continue; }
        await wc('/products', { method: 'POST', body });
        stats.created++;
      }
    } catch (e) {
      stats.failed++;
      log(`  ✗ Produkt ${p.sku} ${p.name}: ${e.message}`);
    }
  }
  return stats;
}

// --- Statische Seiten ------------------------------------------------------

async function importPages(pages) {
  const stats = { created: 0, updated: 0, failed: 0 };
  for (const pg of pages) {
    try {
      const contentHtml = pg.content_html
        || (pg.content_text || '').split(/\n{2,}/)
          .map((para) => `<p>${escapeHtml(para).replace(/\n/g, '<br>')}</p>`).join('\n');
      const body = { title: pg.title, slug: pg.slug, status: 'publish', content: contentHtml };
      const existing = await wp('/wp/v2/pages', { query: { slug: pg.slug, status: 'publish,draft' } });
      const found = Array.isArray(existing) ? existing.find((x) => x.slug === pg.slug) : null;
      if (found) {
        if (!DRY) await wp(`/wp/v2/pages/${found.id}`, { method: 'POST', body });
        stats.updated++;
      } else {
        if (DRY) { log(`  + [dry] Seite ${pg.title}`); stats.created++; continue; }
        await wp('/wp/v2/pages', { method: 'POST', body });
        stats.created++;
      }
    } catch (e) {
      stats.failed++;
      log(`  ✗ Seite ${pg.slug}: ${e.message}`);
    }
  }
  return stats;
}
const escapeHtml = (s = '') => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

// --- Main ------------------------------------------------------------------

async function main() {
  log(`Content-Import → ${WP_BASE}${DRY ? '  [DRY-RUN]' : ''}`);
  const cats = await readJson('categories.json');
  const products = await readJson('products.json');
  const pages = await readJson('pages.json');
  if (!cats || !products) {
    log('✗ Keine migration/data/*.json gefunden. Zuerst scrapen: node migration/scrape/scrape.mjs');
    process.exitCode = 1;
    return;
  }
  log(`Daten: ${cats.length} Kategorien, ${products.length} Produkte, ${(pages || []).length} Seiten`);

  log('\n[1/5] Polylang-Sprachen (DE/EN) …'); await ensureLanguages();
  log('[2/5] Product-Tags (Flags) …');       const tagIds = await ensureTags();
  log('[3/5] Kategorien …');                 const catIds = await ensureCategories(cats);
  log('[4/5] Produkte (+ Medien) …');        const pStats = await importProducts(products, catIds, tagIds);
  log('[5/5] Statische Seiten …');           const pgStats = await importPages(pages || []);

  log('\n=== Import fertig ===');
  log(`Produkte:  +${pStats.created} neu, ~${pStats.updated} aktualisiert, ${pStats.failed} Fehler, ${pStats.media} Bilder`);
  log(`Seiten:    +${pgStats.created} neu, ~${pgStats.updated} aktualisiert, ${pgStats.failed} Fehler`);
  if (pStats.failed || pgStats.failed) process.exitCode = 1;
}

main().catch((e) => { console.error('Import-Fehler:', e); process.exitCode = 1; });
