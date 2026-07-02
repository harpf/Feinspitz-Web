// scripts/i18n/polylang-content.mjs
//
// Polylang-Content-Verknüpfung (HTTP-only, idempotent):
//   1. Prüft DE (Standard) + EN Sprachen.
//   2. Feature-Detection der REST-Brücke aus theme/feinspitz/inc/i18n.php
//      (REST-Felder `lang` / `pll_translations` auf Seiten/Beiträgen).
//   3. Ordnet allen sprachlosen Seiten (und Beiträgen) die Sprache DE zu.
//   4. Legt EN-Übersetzungen der 4 statischen Seiten an (scripts/i18n/pages.en.json)
//      und verknüpft sie mit den DE-Originalen — damit /en/<slug>/ funktioniert.
//
// Sicherheit: STANDARD ist Dry-Run (nur Lesezugriffe). Schreiben nur mit --write.
// Voraussetzung fürs Schreiben: das Theme mit inc/i18n.php (REST-Brücke) muss auf
// dem Server AKTIV sein — sonst kann keine Sprache zugewiesen werden (freies
// Polylang bietet dafür keinen REST-Weg). Das Skript erkennt das und bricht mit
// klarer Anleitung ab.
//
// Aufruf:
//   node scripts/i18n/polylang-content.mjs            # Dry-Run + Diagnose
//   node scripts/i18n/polylang-content.mjs --write    # Änderungen ausführen

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { wp } from '../lib/wp.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const WRITE = process.argv.includes('--write');
const PAGES_EN = JSON.parse(readFileSync(join(__dirname, 'pages.en.json'), 'utf8'));

const log = (...a) => console.log(...a);
const tag = WRITE ? '' : '[dry] ';

// Slug → Ziel-Sprache-Reihenfolge der 4 statischen Seiten (DE-Slugs).
const STATIC_SLUGS = ['ueber-uns', 'kontakt', 'agb', 'about'];

async function getAllPages() {
  const out = [];
  for (let page = 1; page <= 20; page++) {
    const batch = await wp('/wp/v2/pages', {
      query: { per_page: 100, page, status: 'any', context: 'edit' },
    });
    out.push(...batch);
    if (batch.length < 100) break;
  }
  return out;
}

async function main() {
  log(`\n=== Polylang-Content-Verknüpfung ${WRITE ? '(SCHREIBEN)' : '(DRY-RUN)'} ===\n`);

  // 1) Sprachen -------------------------------------------------------------
  let langs;
  try {
    langs = await wp('/pll/v1/languages');
  } catch (e) {
    log(`✖ Polylang-REST nicht erreichbar (${e.status || e.message}). Abbruch.`);
    process.exit(1);
  }
  const bySlug = Object.fromEntries(langs.map((l) => [l.slug, l]));
  if (!bySlug.de || !bySlug.en) {
    log('✖ Sprachen DE und/oder EN fehlen. Zuerst `npm run bootstrap` bzw. import ausführen.');
    process.exit(1);
  }
  log(`✓ Sprachen: ${langs.map((l) => `${l.slug}${l.is_default ? '(default)' : ''}`).join(', ')}`);

  // 2) REST-Brücke erkennen -------------------------------------------------
  const pages = await getAllPages();
  const sample = pages[0];
  const bridge = sample && Object.prototype.hasOwnProperty.call(sample, 'lang');
  if (!bridge) {
    log('\n⚠ REST-Brücke NICHT aktiv: das wp/v2-Antwortobjekt hat kein `lang`-Feld.');
    log('  Ursache: das Theme mit theme/feinspitz/inc/i18n.php ist auf dem Server');
    log('  noch nicht aktiv (freies Polylang exponiert `lang` nicht selbst).');
    log('  → Zuerst dieses i18n-Theme deployen (npm run theme:deploy, koordiniert),');
    log('    danach dieses Skript erneut ausführen.');
    if (WRITE) {
      log('\n✖ Ohne Brücke ist keine Sprachzuweisung über REST möglich. Abbruch.');
      process.exit(2);
    }
    log('\n(Dry-Run wird zur Diagnose fortgesetzt, es werden KEINE Schreibzugriffe versucht.)');
  } else {
    log('✓ REST-Brücke aktiv (`lang`/`pll_translations` verfügbar).');
  }

  // 3) DE-Zuordnung sprachloser Seiten -------------------------------------
  const langless = pages.filter((p) => !p.lang);
  log(`\n[DE-Zuordnung] Seiten ohne Sprache: ${langless.length}/${pages.length}`);
  for (const p of langless) {
    log(`  ${tag}Seite ${p.id} "${(p.title?.raw || p.slug)}" → de`);
    if (WRITE && bridge) {
      await wp(`/wp/v2/pages/${p.id}`, { method: 'POST', body: { lang: 'de' } });
    }
  }

  // 4) EN-Übersetzungen der 4 statischen Seiten ----------------------------
  log('\n[EN-Seiten] statische Seiten übersetzen & verknüpfen:');
  const bySlugPage = Object.fromEntries(pages.map((p) => [`${p.lang || 'de'}:${p.slug}`, p]));
  for (const slug of STATIC_SLUGS) {
    const de = pages.find((p) => p.slug === slug && (!p.lang || p.lang === 'de'));
    const tr = PAGES_EN[slug];
    if (!de) { log(`  ⚠ DE-Seite "${slug}" nicht gefunden — übersprungen.`); continue; }
    if (!tr) { log(`  ⚠ Keine EN-Übersetzung für "${slug}" in pages.en.json — übersprungen.`); continue; }

    // existiert bereits eine verknüpfte EN-Übersetzung?
    const existingEnId = de.pll_translations?.en;
    const existingEn =
      (existingEnId && pages.find((p) => p.id === existingEnId)) ||
      bySlugPage[`en:${slug}`];

    if (existingEn) {
      log(`  = EN "${slug}" existiert bereits (Seite ${existingEn.id}) — ok`);
      continue;
    }

    log(`  ${tag}EN "${slug}" anlegen: "${tr.title}" + verknüpfen mit DE-Seite ${de.id}`);
    if (WRITE && bridge) {
      const created = await wp('/wp/v2/pages', {
        method: 'POST',
        body: {
          title: tr.title,
          content: tr.content,
          slug,
          status: de.status === 'publish' ? 'publish' : 'draft',
          lang: 'en',
        },
      });
      // DE↔EN verknüpfen (auf der DE-Seite, inkl. sich selbst).
      await wp(`/wp/v2/pages/${de.id}`, {
        method: 'POST',
        body: { pll_translations: { de: de.id, en: created.id } },
      });
      log(`    → angelegt als Seite ${created.id} und verknüpft.`);
    }
  }

  // 5) Hinweise -------------------------------------------------------------
  log('\n[Hinweise]');
  log('  • Produkte (171): DE-Zuordnung/EN-Übersetzung sind NICHT Teil dieser Phase.');
  log('    Siehe scripts/i18n/README.md (Folgeaufgabe).');
  log('  • Hauptnavigation: In diesem FSE-Theme ist die Navigation ein wp_navigation-');
  log('    Block. Sprachspezifische Menülabels werden pro Sprache gepflegt — Details/');
  log('    Vorgehen in scripts/i18n/README.md. Theme-eigene UI-Strings sind über');
  log('    feinspitz-en_US.po bereits übersetzt.');

  log(`\n=== fertig ${WRITE ? '' : '(Dry-Run — nichts geschrieben)'} ===\n`);
}

main().catch((e) => {
  console.error('FEHLER:', e.status || '', e.message);
  process.exit(1);
});
