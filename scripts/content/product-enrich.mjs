// Produkt-Anreicherung — schöne Kurzbeschreibungen + strukturierte Attribute.
//
// Die 171 WooCommerce-Produkte tragen aus dem Jimdo-Scrape rohe, auf ~250 Zeichen
// gekappte Beschreibungen wie:
//   „Weingut Steyrer - 0.375 L Grüner Veltliner, Süss Region: Traisental
//    Histaminrestwert 0.019 mg/l Alkoholgehalt: 11.5 % …"
// Kein schöner Text, keine strukturierten Attribute.
//
// Dieses Skript (HTTP-only via scripts/lib/wp.mjs, Cookie+Nonce):
//   1. PARST je Produkt aus dem Rohtext die sicher erkennbaren Fakten:
//      Weingut · Rebsorte · Region · Jahrgang · Süsse (Geschmack) · Volumen.
//   2. Erzeugt daraus eine natürliche, einheitliche KURZBESCHREIBUNG (1–2 Sätze)
//      und setzt sie als `short_description` (KEIN Datendump).
//   3. Legt die sechs Merkmale als GLOBALE WooCommerce-Attribute (pa_*) an und
//      weist die erkannten Werte dem Produkt zu — globale Attribute, damit der
//      spätere Wein-Finder / die Shop-Filter (Baustein ④) darauf filtern können.
//
// Wichtig zur IDEMPOTENZ:
//   Geparst wird IMMER aus `description` (dem langen Feld); NUR `short_description`
//   und `attributes` werden geschrieben. Der Rohtext in `description` bleibt also
//   als stabile Parse-Quelle erhalten → Zweitlauf ergibt identische Werte, keine
//   Duplikate. Vor jedem Schreiben wird verglichen; unveränderte Produkte werden
//   übersprungen (Ausgabe „=").
//
// Sicherheit: Nur setzen, was zweifelsfrei erkennbar ist. Ob ein Produkt ein Wein
// ist, wird über die KATEGORIE entschieden (Wurzel rotweine/weissweine/suessweine/
// schaumweine/rose), NICHT über den Text — so werden Weinessig, Verjus, Spirituosen,
// Kulinarik und Bücher zuverlässig ausgelassen (auch wenn „Weingut" im Text steht).
//
// Aufruf:
//   node scripts/content/product-enrich.mjs --dry-run   # nur parsen + anzeigen
//   node scripts/content/product-enrich.mjs             # schreiben (idempotent)
//   node scripts/content/product-enrich.mjs --limit 5   # nur die ersten 5
//   node scripts/content/product-enrich.mjs --ids 345,347

import { wc, WP_BASE } from '../lib/wp.mjs';

const DRY_RUN = process.argv.includes('--dry-run');
const LIMIT = numArg('--limit');
const ONLY_IDS = listArg('--ids');

function numArg(flag) {
  const i = process.argv.indexOf(flag);
  return i > -1 && process.argv[i + 1] ? Number(process.argv[i + 1]) : null;
}
function listArg(flag) {
  const i = process.argv.indexOf(flag);
  if (i === -1 || !process.argv[i + 1]) return null;
  return new Set(process.argv[i + 1].split(',').map((s) => Number(s.trim())));
}

// ---------------------------------------------------------------------------
// Attribut-Definitionen (globale WooCommerce-Attribute, Reihenfolge = Anzeige)
// ---------------------------------------------------------------------------

const ATTRIBUTES = [
  { key: 'weingut', name: 'Weingut', slug: 'weingut' },
  { key: 'rebsorte', name: 'Rebsorte', slug: 'rebsorte' },
  { key: 'region', name: 'Region', slug: 'region' },
  { key: 'jahrgang', name: 'Jahrgang', slug: 'jahrgang' },
  { key: 'suesse', name: 'Süsse', slug: 'suesse' },
  { key: 'volumen', name: 'Volumen', slug: 'volumen' },
];

// ---------------------------------------------------------------------------
// Parsing
// ---------------------------------------------------------------------------

/** HTML grob zu Text: Tags entfernen, häufige Entities dekodieren, Whitespace normalisieren. */
function toText(html) {
  if (!html) return '';
  return String(html)
    .replace(/<[^>]+>/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&nbsp;/g, ' ')
    .replace(/&#8230;|…/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

// Bekannte Rebsorten — längste zuerst, damit „Grüner Veltliner" vor „Veltliner"
// und „Cabernet Sauvignon" vor „Sauvignon" greift. Werte = kanonische Anzeigeform.
const GRAPES = [
  'Frühroter Veltliner',
  'Grüner Veltliner',
  'Gelber Muskateller',
  'Gemischter Satz',
  'Müller Thurgau',
  'Cabernet Sauvignon',
  'Cabernet Franc',
  'Sauvignon Blanc',
  'Blauer Wildbacher',
  'Gewürztraminer',
  'Muskat Ottonel',
  'Weissburgunder',
  'Weißburgunder',
  'Welschriesling',
  'Grauburgunder',
  'Blaufränkisch',
  'Blaufraenkisch',
  'Sankt Laurent',
  'St. Laurent',
  'Muskateller',
  'Rotgipfler',
  'Zierfandler',
  'Scheurebe',
  'Blauburger',
  'Zweigelt',
  'Neuburger',
  'Chardonnay',
  'Rösler',
  'Roesler',
  'Morillon',
  'Riesling',
  'Traminer',
  'Muskat',
  'Merlot',
  'Shiraz',
  'Syrah',
];

// Rebsorten, die als eigene Produktkategorie geführt werden (Slug → Anzeige).
// Starkes Fallback-Signal, falls die Rebsorte im gekappten Rohtext fehlt.
const GRAPE_CATEGORIES = {
  blaufraenkisch: 'Blaufränkisch',
  'gruener-veltliner': 'Grüner Veltliner',
  riesling: 'Riesling',
  weissburgunder: 'Weissburgunder',
  zweigelt: 'Zweigelt',
  'st-laurent': 'St. Laurent',
  'gemischter-satz': 'Gemischter Satz',
  cuvee: 'Cuvée',
};

// Österreichische Weinbaugebiete — Fallback, wenn „Region:" im gekappten Rohtext
// fehlt (die Namen sind eindeutige Eigennamen → geringes Falsch-Treffer-Risiko).
const KNOWN_REGIONS = [
  'Wachau', 'Kamptal', 'Kremstal', 'Traisental', 'Wagram', 'Weinviertel',
  'Carnuntum', 'Thermenregion', 'Neusiedlersee', 'Leithaberg', 'Mittelburgenland',
  'Eisenberg', 'Südsteiermark', 'Weststeiermark', 'Vulkanland Steiermark',
  'Rosalia', 'Burgenland',
];

/**
 * Weingut/Produzent: „(Bio )Weingut Steyrer", „Winzerhof Pöchlinger",
 * „Barbara Öhlzelt" — der Rohtext beginnt stets mit dem Produzenten, gefolgt von
 * einem Trenner (Bindestrich, Mittelpunkt, „Jahrgang", „Jg." oder dem Volumen).
 * „Weingut"/„Bio" werden entfernt, da das Attribut bereits „Weingut" heisst.
 */
function parseWeingut(text) {
  const m = text.match(/^\s*(.+?)\s*(?:[-–—]\s|·|,|\bJahrgang\b|\bJg\.?\b|\d+[.,]?\d*\s*L\b|Region:)/i);
  if (!m) return null;
  let name = m[1].replace(/\s+/g, ' ').trim();
  // „Bio", „Bioweingut", „Weingut" am Anfang entfernen (Attribut heisst „Weingut").
  name = name.replace(/^Bio[-\s]*/i, '').replace(/^Weingut\s+/i, '').trim();
  // Zu lang → vermutlich kein sauberer Produzentenname (kein Trenner am Anfang).
  if (!name || name.length > 40) return null;
  return name;
}

/** Volumen „0.375 L" → „0,375 l". */
function parseVolumen(text) {
  const m = text.match(/(\d+(?:[.,]\d+)?)\s*L\b/i);
  if (!m) return null;
  const num = m[1].replace('.', ',');
  return `${num} l`;
}

/** Jahrgang: explizit (Jahrgang/Jg.) → sonst 4-stelliges Jahr im Text → sonst im Namen. */
function parseJahrgang(text, name) {
  const explicit = text.match(/(?:Jahrgang|Jg\.?)\s*([12]\d{3})/i);
  if (explicit) return explicit[1];
  // Rohtext enthält viele Zahlen (Restzucker, Säure, Temperatur), aber keine davon
  // liegt im Jahresbereich 1990–2029 → ein Treffer dort ist zuverlässig ein Jahrgang.
  const bare = text.match(/\b(19[9]\d|20[0-2]\d)\b/);
  if (bare) return bare[1];
  const inName = (name || '').match(/\b(19[9]\d|20[0-2]\d)\b/);
  return inName ? inName[1] : null;
}

// Längste zuerst, damit z. B. „Vulkanland Steiermark" vor „Steiermark" greift.
const REGIONS_LONGEST_FIRST = [...KNOWN_REGIONS].sort((a, b) => b.length - a.length);

/**
 * Region: „Region: <bekanntes Gebiet>" hat Vorrang; sonst wenige Wörter nach dem
 * Label (der Rohtext wurde whitespace-normalisiert, daher greifen Wortgrenzen statt
 * Doppel-Leerzeichen); sonst irgendein bekanntes Gebiet im Text.
 */
function parseRegion(text) {
  const label = text.search(/Region:/i);
  if (label > -1) {
    const after = text.slice(label + 'Region:'.length).trimStart();
    for (const r of REGIONS_LONGEST_FIRST) {
      if (new RegExp(`^${escapeRe(r)}(?![\\p{L}])`, 'iu').test(after)) return r;
    }
    // Unbekanntes Gebiet: bis zum nächsten Schlüsselwort, max. 2 Wörter.
    const m = after.match(
      /^(.+?)(?:\s+Lage:?|\s+Alkoholgehalt|\s+Histamin|\s+Optimale|\s+Restzucker|\s+Säure|\s+Prädikat|$)/i
    );
    if (m) {
      const cand = m[1].replace(/[.,;].*$/, '').trim();
      if (cand && cand.split(/\s+/).length <= 2) return cand;
    }
  }
  for (const r of REGIONS_LONGEST_FIRST) {
    if (new RegExp(`(?:^|[^\\p{L}])${escapeRe(r)}(?![\\p{L}])`, 'iu').test(text)) return r;
  }
  return null;
}

/** Süsse/Geschmack — spezifischste Angabe zuerst. */
function parseSuesse(text) {
  if (/halbtrocken/i.test(text)) return 'Halbtrocken';
  if (/\btrocken\b/i.test(text)) return 'Trocken';
  if (/lieblich/i.test(text)) return 'Lieblich';
  if (/s[üuü]ss|süß/i.test(text)) return 'Süss';
  return null;
}

/**
 * Rebsorte. Ein Verschnitt (Cuvée) hat Vorrang vor einer einzelnen im Text
 * genannten Komponente — „Cuvée (Zweigelt, Merlot)" wird als „Cuvée" geführt,
 * nicht als „Zweigelt". Danach Text-Treffer, dann Kategorie-Signal.
 */
function parseRebsorte(text, categorySlugs) {
  if (/Cuv[eé]{1,2}\b|\bCuvee\b/i.test(text) || categorySlugs.includes('cuvee')) {
    return 'Cuvée';
  }
  for (const grape of GRAPES) {
    const re = new RegExp(`(?:^|[^\\p{L}])${escapeRe(grape)}(?![\\p{L}])`, 'iu');
    if (re.test(text)) return normalizeGrape(grape);
  }
  for (const slug of categorySlugs) {
    if (GRAPE_CATEGORIES[slug]) return GRAPE_CATEGORIES[slug];
  }
  return null;
}

function normalizeGrape(g) {
  if (g === 'Weißburgunder') return 'Weissburgunder';
  if (g === 'Blaufraenkisch') return 'Blaufränkisch';
  if (g === 'Sankt Laurent') return 'St. Laurent';
  if (g === 'Roesler') return 'Rösler';
  return g;
}

function escapeRe(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Wurzelkategorien, die einen Wein ausmachen bzw. ausschliessen.
const WINE_ROOTS = new Set(['rotweine', 'weissweine', 'suessweine', 'schaumweine', 'rose']);
const NONWINE_ROOTS = new Set(['kulinarium', 'spirituosen', 'buecher']);

/**
 * Ist das Produkt ein Wein? Entschieden über die Wurzelkategorie. Steht ein
 * Produkt (z. B. Verjus-Spritz) sowohl unter schaumweine als auch unter
 * kulinarium, gewinnt der Ausschluss.
 */
function isWine(product, rootOf) {
  const roots = (product.categories || []).map((c) => rootOf(c.id));
  if (roots.some((r) => NONWINE_ROOTS.has(r))) return false;
  return roots.some((r) => WINE_ROOTS.has(r));
}

/** Ein Produkt parsen. Gibt die erkannten Fakten zurück. */
function parseProduct(product) {
  const raw = product.description || product.short_description || '';
  const text = toText(raw);
  const categorySlugs = (product.categories || []).map((c) => (c.slug || '').toLowerCase());

  return {
    weingut: parseWeingut(text),
    rebsorte: parseRebsorte(text, categorySlugs),
    region: parseRegion(text),
    jahrgang: parseJahrgang(text, product.name),
    suesse: parseSuesse(text),
    volumen: parseVolumen(text),
  };
}

// ---------------------------------------------------------------------------
// Kurzbeschreibung erzeugen (natürliches Deutsch, 1–2 Sätze)
// ---------------------------------------------------------------------------

// „Er ..." bezieht sich stets auf „der Wein" → grammatikalisch immer korrekt,
// unabhängig vom Genus der Rebsorte.
const TASTE_SENTENCE = {
  Trocken: 'Er zeigt sich trocken und ausgewogen.',
  Halbtrocken: 'Er ist harmonisch halbtrocken.',
  Lieblich: 'Er schmeckt angenehm lieblich.',
  Süss: 'Er ist fruchtig süss.',
};

function buildShortDescription(f) {
  const lead = f.rebsorte || 'Wein';
  // Trägt der Produzentenname bereits einen Betriebstyp (z. B. „Winzerhof"),
  // kein zweites „Weingut" davorsetzen.
  const producer = f.weingut
    ? /^(Winzerhof|Weinhof|Weinbau|Weinkellerei|Kellerei|Weingut)\b/i.test(f.weingut)
      ? `vom ${f.weingut}`
      : `vom Weingut ${f.weingut}`
    : '';
  let s1 = producer ? `${lead} ${producer}` : lead;
  if (f.region) s1 += ` aus der Region ${f.region}`;
  if (f.jahrgang) s1 += `, Jahrgang ${f.jahrgang}`;
  if (f.volumen) s1 += ` (${f.volumen})`;
  s1 += '.';

  const s2 = f.suesse ? TASTE_SENTENCE[f.suesse] : '';
  const text = s2 ? `${s1} ${s2}` : s1;
  return { text, html: `<p>${text}</p>` };
}

// ---------------------------------------------------------------------------
// Globale Attribute + Terme (idempotent, mit Cache)
// ---------------------------------------------------------------------------

/** Stellt die sechs globalen Attribute sicher; gibt Map key → attributeObj zurück. */
async function ensureAttributes() {
  const existing = await wc('/products/attributes', { query: { per_page: 100 } });
  const byName = new Map((existing || []).map((a) => [a.name.toLowerCase(), a]));
  const result = new Map();

  for (const def of ATTRIBUTES) {
    let attr = byName.get(def.name.toLowerCase());
    if (!attr) {
      if (DRY_RUN) {
        console.log(`  [dry-run] würde Attribut anlegen: ${def.name}`);
        attr = { id: `dry-${def.slug}`, name: def.name, slug: `pa_${def.slug}` };
      } else {
        attr = await wc('/products/attributes', {
          method: 'POST',
          body: { name: def.name, slug: def.slug, type: 'select', order_by: 'name', has_archives: false },
        });
        console.log(`  ✓ Attribut angelegt: ${def.name} (id ${attr.id})`);
      }
    }
    result.set(def.key, attr);
  }
  return result;
}

/** Term-Cache je Attribut: Name(lowercased) → termObj. Lädt vorhandene einmalig. */
async function loadTerms(attr) {
  if (String(attr.id).startsWith('dry-')) return new Map();
  const map = new Map();
  for (let page = 1; page < 20; page++) {
    const terms = await wc(`/products/attributes/${attr.id}/terms`, {
      query: { per_page: 100, page },
    });
    if (!Array.isArray(terms) || terms.length === 0) break;
    for (const t of terms) map.set(t.name.toLowerCase(), t);
    if (terms.length < 100) break;
  }
  return map;
}

/** Stellt einen Term sicher (idempotent per Name); gibt den Term-Namen zurück. */
async function ensureTerm(attr, cache, value) {
  const found = cache.get(value.toLowerCase());
  if (found) return found.name;
  if (DRY_RUN) {
    console.log(`  [dry-run] würde Term anlegen: ${attr.name} → ${value}`);
    return value;
  }
  const created = await wc(`/products/attributes/${attr.id}/terms`, {
    method: 'POST',
    body: { name: value },
  });
  cache.set(value.toLowerCase(), created);
  return created.name;
}

// ---------------------------------------------------------------------------
// Ablauf
// ---------------------------------------------------------------------------

async function fetchAllProducts() {
  const all = [];
  for (let page = 1; page < 50; page++) {
    const batch = await wc('/products', { query: { per_page: 100, page, status: 'publish' } });
    if (!Array.isArray(batch) || batch.length === 0) break;
    all.push(...batch);
    if (batch.length < 100) break;
  }
  return all;
}

/** Kategorie-Baum laden und eine Funktion id → Wurzel-Slug zurückgeben. */
async function buildCategoryRootResolver() {
  const cats = [];
  for (let page = 1; page < 10; page++) {
    const batch = await wc('/products/categories', { query: { per_page: 100, page } });
    if (!Array.isArray(batch) || batch.length === 0) break;
    cats.push(...batch);
    if (batch.length < 100) break;
  }
  const byId = new Map(cats.map((c) => [c.id, c]));
  return (id) => {
    let cur = byId.get(id);
    const seen = new Set();
    while (cur && cur.parent && !seen.has(cur.id)) {
      seen.add(cur.id);
      cur = byId.get(cur.parent);
    }
    return cur ? cur.slug : null;
  };
}

/** Aktuelle Attribut-Optionen eines Produkts als Map name→[options] (nur unsere Attribute). */
function currentAttrOptions(product, attrMap) {
  const wantedSlugs = new Set([...attrMap.values()].map((a) => (a.slug || '').toLowerCase()));
  const out = new Map();
  for (const a of product.attributes || []) {
    const slug = (a.slug || '').toLowerCase();
    if (wantedSlugs.has(slug)) out.set(slug, (a.options || []).slice().sort());
  }
  return out;
}

/** Normalisierter Text (für Idempotenz-Vergleich der Kurzbeschreibung). */
function normText(html) {
  return toText(html).toLowerCase();
}

async function main() {
  console.log(`→ Ziel: ${WP_BASE}${DRY_RUN ? '  [DRY-RUN — es wird nichts geschrieben]' : ''}\n`);

  const attrMap = await ensureAttributes();
  const termCaches = new Map();
  for (const [key, attr] of attrMap) termCaches.set(key, await loadTerms(attr));

  const rootOf = await buildCategoryRootResolver();
  let products = await fetchAllProducts();
  console.log(`\n${products.length} Produkte geladen.\n`);
  if (ONLY_IDS) products = products.filter((p) => ONLY_IDS.has(p.id));
  if (LIMIT) products = products.slice(0, LIMIT);

  const stats = { enriched: 0, updated: 0, unchanged: 0, skipped: 0 };
  const attrCounts = Object.fromEntries(ATTRIBUTES.map((a) => [a.key, 0]));

  for (const product of products) {
    if (!isWine(product, rootOf)) {
      stats.skipped++;
      console.log(`— [${product.id}] übersprungen (kein Wein): ${product.name}`);
      continue;
    }
    const facts = parseProduct(product);
    const detected = ATTRIBUTES.filter((a) => facts[a.key]).length;
    if (detected === 0) {
      stats.skipped++;
      console.log(`— [${product.id}] übersprungen (keine Fakten erkennbar): ${product.name}`);
      continue;
    }
    stats.enriched++;

    // Gewünschte Attribute (nur erkannte Werte) + Terme sicherstellen.
    const desiredAttributes = [];
    let position = 0;
    for (const def of ATTRIBUTES) {
      const value = facts[def.key];
      if (!value) continue;
      attrCounts[def.key]++;
      const attr = attrMap.get(def.key);
      const termName = await ensureTerm(attr, termCaches.get(def.key), value);
      desiredAttributes.push({
        id: attr.id,
        name: attr.name,
        slug: attr.slug,
        position: position++,
        visible: false, // Anzeige erfolgt über die gestylte Fakten-Tabelle (inc/product-facts.php).
        variation: false,
        options: [termName],
      });
    }

    const { text, html } = buildShortDescription(facts);

    // Idempotenz-Vergleich.
    const shortChanged = normText(product.short_description) !== normText(html);
    const currentOpts = currentAttrOptions(product, attrMap);
    const desiredOpts = new Map(desiredAttributes.map((a) => [(a.slug || '').toLowerCase(), a.options.slice().sort()]));
    const attrsChanged =
      currentOpts.size !== desiredOpts.size ||
      [...desiredOpts].some(([slug, opts]) => JSON.stringify(currentOpts.get(slug)) !== JSON.stringify(opts));

    const factLine = ATTRIBUTES.filter((a) => facts[a.key])
      .map((a) => `${a.name}=${facts[a.key]}`)
      .join(' · ');

    if (!shortChanged && !attrsChanged) {
      stats.unchanged++;
      console.log(`= [${product.id}] unverändert: ${product.name}`);
      continue;
    }

    console.log(`✓ [${product.id}] ${product.name}`);
    console.log(`    Fakten : ${factLine}`);
    console.log(`    Kurz   : ${text}`);

    if (!DRY_RUN) {
      await wc(`/products/${product.id}`, {
        method: 'PUT',
        body: { short_description: html, attributes: desiredAttributes },
      });
      stats.updated++;
    }
  }

  console.log('\n────────────────────────────────────────────');
  console.log(`Weine erkannt      : ${stats.enriched}`);
  console.log(`Nicht-Wein (skip)  : ${stats.skipped}`);
  if (DRY_RUN) {
    console.log(`Würde aktualisieren: ${stats.enriched - stats.unchanged}`);
  } else {
    console.log(`Aktualisiert       : ${stats.updated}`);
  }
  console.log(`Bereits aktuell    : ${stats.unchanged}`);
  console.log('Attribut-Treffer:');
  for (const def of ATTRIBUTES) console.log(`  ${def.name.padEnd(10)}: ${attrCounts[def.key]}`);
  console.log(DRY_RUN ? '\n(DRY-RUN — nichts geschrieben. Ohne --dry-run erneut ausführen.)' : '\n✓ Fertig (idempotent).');
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
