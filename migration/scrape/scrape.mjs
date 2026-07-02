// Scraper: feinspitz.ch (Jimdo) → strukturiertes JSON unter migration/data/.
//
//   node migration/scrape/scrape.mjs [--no-media] [--limit=N]
//
// Erzeugt:
//   migration/data/categories.json  Kategoriebaum (product_cat)
//   migration/data/products.json    Produkte (sku, name, slug, preis, desc, kategorien, flags, bild)
//   migration/data/pages.json       statische Seiten (Über uns, Kontakt, AGB, …)
//   migration/data/media/<sku>.<ext> heruntergeladene Produktbilder
//   migration/data/scrape-report.json  Lauf-Zusammenfassung
//
// Datenquelle-Details siehe migration/scrape/lib.mjs. Beschreibungen sind vom
// Jimdo-Katalog auf 250 Zeichen gekappt (keine Detailseite verfügbar).

import { mkdir, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, extname } from 'node:path';
import {
  SOURCE_BASE, fetchText, fetchBuffer, decodeEntities, stripHtml,
  slugify, meta, upscaleJimcdn,
} from './lib.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const DATA_DIR = join(__dirname, '..', 'data');
const MEDIA_DIR = join(DATA_DIR, 'media');

const args = process.argv.slice(2);
const NO_MEDIA = args.includes('--no-media');
const LIMIT = Number((args.find((a) => a.startsWith('--limit=')) || '').split('=')[1]) || 0;

// Flag-Sektionen: erster Pfad-Segment → WooCommerce-Tag.
const FLAG_SECTIONS = {
  'histamin-geprüftes': 'histamingeprueft',
  'histamin-geprueftes': 'histamingeprueft',
  vegan: 'vegan',
  'zero-alkoholfrei': 'alkoholfrei',
};
// Statische Seiten (Slug-Fragment im Pfad → wird als page übernommen).
const STATIC_PAGES = ['über-uns', 'ueber-uns', 'kontakt', 'agb', 'about'];
const SKIP_PATHS = new Set(['', 'shop', 'suche']); // reine Landing-/Suchseiten

// --- Sitemap ---------------------------------------------------------------

async function loadSitemap() {
  const xml = await fetchText(`${SOURCE_BASE}/sitemap.xml`);
  return [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => decodeEntities(m[1].trim()));
}

/** Zerlegt eine URL in dekodierte Pfad-Segmente. */
function segments(url) {
  const path = new URL(url).pathname;
  return decodeURIComponent(path).split('/').map((s) => s.trim()).filter(Boolean);
}

// --- Produkt-Karten parsen -------------------------------------------------

function parseProducts(html) {
  const parts = html.split(/<div class="cc-webview-product hlisting j-catalog-product/);
  const out = [];
  for (let i = 1; i < parts.length; i++) {
    const chunk = parts[i];
    const id = (chunk.match(/\/webproduct\/goto\/m\/(m[0-9a-f]+)/i) || [])[1];
    if (!id) continue;
    const titleRaw = (chunk.match(/j-catalog-product-title-link"[^>]*>([\s\S]*?)<\/a>/i) || [])[1] || '';
    const name = stripHtml(titleRaw).replace(/\s+/g, ' ').trim();
    if (!name) continue;
    const priceRaw = (chunk.match(/j-catalog-price"[^>]*>([\s\S]*?)<\/strong>/i) || [])[1] || '';
    const priceText = stripHtml(priceRaw);
    const { amount, currency } = parsePrice(priceText);
    const descRaw = (chunk.match(/data-description="([^"]*)"/i) || [])[1] || '';
    let description = decodeEntities(descRaw).replace(/[ \t]+/g, ' ').replace(/\s*\n\s*/g, '\n').trim();
    // Jimdo kappt die Katalog-Beschreibung bei 250 Zeichen → sichtbar machen.
    if (descRaw.length >= 250) description = description.replace(/\s*\S*$/, '') + ' …';

    // Bild: größte URL aus srcset, sonst src.
    let imgUrl = '';
    const srcset = (chunk.match(/srcset="([^"]*)"/i) || [])[1];
    if (srcset) {
      const cands = [...srcset.matchAll(/(https:\/\/image\.jimcdn\.com\/\S+?)\s+(\d+)w/g)]
        .map((m) => ({ url: m[1], w: Number(m[2]) }))
        .sort((a, b) => b.w - a.w);
      if (cands[0]) imgUrl = cands[0].url;
    }
    if (!imgUrl) imgUrl = (chunk.match(/src="(https:\/\/image\.jimcdn\.com\/[^"]+)"/i) || [])[1] || '';

    out.push({ id, name, priceText, amount, currency, description, imgUrl });
  }
  return out;
}

function parsePrice(text = '') {
  const currency = (text.match(/(CHF|EUR|€|USD|\$)/i) || [])[1] || 'CHF';
  const num = text.replace(/[^0-9.,]/g, '').replace(/\.(?=\d{3}\b)/g, '').replace(',', '.');
  const amount = num ? Number(parseFloat(num).toFixed(2)) : null;
  return { amount, currency: currency === '€' ? 'EUR' : currency === '$' ? 'USD' : currency.toUpperCase() };
}

// --- Statische Seite parsen ------------------------------------------------

function parsePage(html) {
  const title = meta(html, 'og:title') || (html.match(/<title>([^<]*)<\/title>/i) || [])[1] || '';
  // Hauptinhalt: Content-Spalte, ohne Header/Footer/Skripte.
  let body = html;
  const main = html.match(/<div[^>]+class="[^"]*cc-m-content[^"]*"[\s\S]*?<\/div>\s*<\/div>/i)
    || html.match(/<main[\s\S]*?<\/main>/i)
    || html.match(/<div[^>]+id="content"[\s\S]*?<\/div>/i);
  if (main) body = main[0];
  return {
    title: decodeEntities(title).replace(/\s*[-|].*$/, '').trim() || title.trim(),
    description: meta(html, 'og:description'),
    content_text: stripHtml(body).slice(0, 8000),
  };
}

// --- Hauptlauf -------------------------------------------------------------

async function main() {
  console.log(`Scrape ${SOURCE_BASE} → migration/data/  (Crawl-Delay aktiv)`);
  await mkdir(MEDIA_DIR, { recursive: true });

  const urls = await loadSitemap();
  console.log(`Sitemap: ${urls.length} URLs`);

  const categories = new Map(); // slug → {slug,name,parent,description,source}
  const products = new Map();   // sku → product
  const pages = [];
  const fallbackCat = new Map(); // sku → leaf slug (falls keine Shop-Kategorie)

  const ensureCategory = (slug, name, parent, source, strong = false) => {
    if (!slug) return;
    const cur = categories.get(slug);
    if (!cur) {
      categories.set(slug, {
        slug, name: name || titleCase(slug), parent: parent || '', description: '',
        source, strong,
      });
    } else {
      // Ein "starker" (og:title-basierter, sauberer) Name überschreibt einen Default.
      if (name && (strong ? !cur.strong : !cur.name || cur.name === titleCase(cur.slug))) {
        cur.name = name; cur.strong = cur.strong || strong;
      }
      if (parent && !cur.parent) cur.parent = parent;
    }
  };

  let pageCount = 0;
  for (const url of urls) {
    const segs = segments(url);
    const first = segs[0] || '';
    const leaf = segs[segs.length - 1] || '';

    // Statische Seiten
    if (STATIC_PAGES.includes(first) && segs.length === 1) {
      const html = await fetchText(url);
      const p = parsePage(html);
      pages.push({ slug: slugify(first), sourceUrl: url, ...p });
      console.log(`  page   ${p.title}`);
      continue;
    }

    const isShop = first === 'shop' && segs.length >= 2;
    const flag = FLAG_SECTIONS[first];
    if (!isShop && !flag) continue; // Landing/Suche/Home überspringen
    if (SKIP_PATHS.has(leaf)) continue;

    if (LIMIT && pageCount >= LIMIT) break;
    pageCount++;

    const html = await fetchText(url);
    const pageName = meta(html, 'og:title') || leaf;

    // Kategorie-Registrierung (nur Shop-Pfade definieren den Baum)
    let leafSlug = '';
    if (isShop) {
      const catSegs = segs.slice(1); // ohne 'shop'
      let parent = '';
      for (let i = 0; i < catSegs.length; i++) {
        const s = slugify(catSegs[i]);
        const isLeaf = i === catSegs.length - 1;
        if (isLeaf) ensureCategory(s, cleanCatName(catSegs[i], pageName), parent, url, true);
        else ensureCategory(s, titleCase(catSegs[i]), parent, url, false);
        parent = s;
        if (isLeaf) leafSlug = s;
      }
    }

    const found = parseProducts(html);
    console.log(`  ${flag ? 'flag ' : 'cat  '} ${segs.join('/')}  (${found.length} Produkte)${flag ? ' ['+flag+']' : ''}`);

    for (const f of found) {
      let prod = products.get(f.id);
      if (!prod) {
        prod = {
          sku: f.id,
          name: f.name,
          slug: '', // später eindeutig vergeben
          price: f.amount,
          currency: f.currency,
          description: f.description,
          categories: new Set(),
          flags: { histamingeprueft: false, vegan: false, alkoholfrei: false },
          image: f.imgUrl ? upscaleJimcdn(f.imgUrl).url : '',
          media: null,
          sourceUrls: new Set(),
        };
        products.set(f.id, prod);
      }
      // Beste (längste) Beschreibung & gültiger Preis gewinnen
      if (f.description && f.description.length > (prod.description || '').length) prod.description = f.description;
      if (prod.price == null && f.amount != null) prod.price = f.amount;
      if (!prod.image && f.imgUrl) prod.image = upscaleJimcdn(f.imgUrl).url;
      prod.sourceUrls.add(url);
      if (isShop && leafSlug) prod.categories.add(leafSlug);
      if (flag) {
        prod.flags[flag] = true;
        if (!fallbackCat.has(f.id)) fallbackCat.set(f.id, slugify(leaf)); // Fallback, falls kein Shop-Cat
      }
    }
  }

  // Fallback-Kategorien für Produkte ohne Shop-Kategorie
  for (const prod of products.values()) {
    if (prod.categories.size === 0) {
      const fb = fallbackCat.get(prod.sku);
      if (fb && !SKIP_PATHS.has(fb)) {
        ensureCategory(fb, titleCase(fb.replace(/-/g, ' ')), '', 'fallback');
        prod.categories.add(fb);
      } else {
        ensureCategory('sonstiges', 'Sonstiges', '', 'fallback');
        prod.categories.add('sonstiges');
      }
    }
  }

  // Eindeutige Slugs vergeben (name-basiert, bei Kollision sku-Suffix)
  const usedSlugs = new Set();
  for (const prod of products.values()) {
    let base = slugify(prod.name) || prod.sku;
    let slug = base;
    if (usedSlugs.has(slug)) slug = `${base}-${prod.sku.slice(-6)}`;
    let n = 2;
    while (usedSlugs.has(slug)) slug = `${base}-${n++}`;
    usedSlugs.add(slug);
    prod.slug = slug;
  }

  // Medien herunterladen
  const productList = [...products.values()];
  if (!NO_MEDIA) {
    console.log(`\nMedien-Download (${productList.length}) …`);
    for (const prod of productList) {
      if (!prod.image) continue;
      const { url: bigUrl, ext } = upscaleJimcdn(prod.image);
      const file = `${prod.sku}.${ext}`;
      const dest = join(MEDIA_DIR, file);
      if (existsSync(dest)) { prod.media = { file, src: bigUrl }; continue; }
      try {
        const buf = await fetchBuffer(bigUrl, { delayMs: 300 });
        await writeFile(dest, buf);
        prod.media = { file, src: bigUrl, bytes: buf.length };
      } catch (e) {
        console.warn(`  ⚠ Bild fehlgeschlagen ${prod.sku}: ${e.message}`);
      }
    }
  }

  // Serialisieren (Sets → Arrays)
  const catArr = [...categories.values()].map((c) => ({
    slug: c.slug, name: c.name, parent: c.parent || null, description: c.description || '',
  }));
  const prodArr = productList.map((p) => ({
    sku: p.sku,
    name: p.name,
    slug: p.slug,
    price: p.price,
    currency: p.currency,
    description: p.description,
    categories: [...p.categories],
    flags: p.flags,
    image: p.image || null,
    media: p.media,
    sourceUrls: [...p.sourceUrls],
  }));

  await writeFile(join(DATA_DIR, 'categories.json'), JSON.stringify(catArr, null, 2) + '\n');
  await writeFile(join(DATA_DIR, 'products.json'), JSON.stringify(prodArr, null, 2) + '\n');
  await writeFile(join(DATA_DIR, 'pages.json'), JSON.stringify(pages, null, 2) + '\n');

  const report = {
    scrapedAt: new Date().toISOString(),
    source: SOURCE_BASE,
    counts: {
      categories: catArr.length,
      products: prodArr.length,
      pages: pages.length,
      withMedia: prodArr.filter((p) => p.media).length,
      withPrice: prodArr.filter((p) => p.price != null).length,
      histamingeprueft: prodArr.filter((p) => p.flags.histamingeprueft).length,
      vegan: prodArr.filter((p) => p.flags.vegan).length,
      alkoholfrei: prodArr.filter((p) => p.flags.alkoholfrei).length,
    },
  };
  await writeFile(join(DATA_DIR, 'scrape-report.json'), JSON.stringify(report, null, 2) + '\n');

  console.log('\n=== Scrape fertig ===');
  console.log(JSON.stringify(report.counts, null, 2));
}

function titleCase(s = '') {
  return decodeEntities(s).replace(/[-_]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()).trim();
}

/** Kategorie-Name: sauberer og:title bevorzugt, sonst titleCase des URL-Segments. */
function cleanCatName(segment, ogTitle = '') {
  const t = (ogTitle || '').trim();
  if (t && t.length <= 35 && !/willkommen|home|feinspitz|^shop$/i.test(t)) return t;
  return titleCase(segment);
}

main().catch((e) => { console.error('Scrape-Fehler:', e); process.exitCode = 1; });
