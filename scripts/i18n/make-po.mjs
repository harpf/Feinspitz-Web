// scripts/i18n/make-po.mjs
//
// Erzeugt aus theme/feinspitz/languages/feinspitz.pot die Sprachdateien:
//   feinspitz-de_DE.po / .mo  — Deutsch ist die Quellsprache: msgstr = msgid.
//   feinspitz-en_US.po / .mo  — Englisch aus scripts/i18n/translations.en.json.
//
// Compiliert die .mo direkt mit gettext-parser (kein WP-CLI/msgfmt nötig).
//
// Aufruf:  node scripts/i18n/make-po.mjs   (setzt voraus, dass make-pot.mjs lief)

import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, relative, sep } from 'node:path';
import gettextParser from 'gettext-parser';

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = join(__dirname, '..', '..');
const LANG_DIR = join(REPO_ROOT, 'theme', 'feinspitz', 'languages');
const POT_PATH = join(LANG_DIR, 'feinspitz.pot');
const EN_MAP_PATH = join(__dirname, 'translations.en.json');

const PLURAL_FORMS = 'nplurals=2; plural=(n != 1);';

/** Übersetzt eine POT-Vorlage in eine konkrete Sprache. */
function buildLocale(pot, { locale, resolve, label }) {
  const missing = [];
  const translations = {};

  for (const [ctx, entries] of Object.entries(pot.translations)) {
    translations[ctx] = {};
    for (const [msgid, entry] of Object.entries(entries)) {
      // Header-Eintrag (leerer msgid) separat behandeln.
      if (ctx === '' && msgid === '') {
        translations[ctx][msgid] = entry;
        continue;
      }
      const isPlural = Boolean(entry.msgid_plural);
      const single = resolve(entry.msgid, ctx);
      if (single === null) missing.push(entry.msgid);

      let msgstr;
      if (isPlural) {
        const plural = resolve(entry.msgid_plural, ctx);
        msgstr = [single ?? '', plural ?? ''];
      } else {
        msgstr = [single ?? ''];
      }
      translations[ctx][msgid] = { ...entry, msgstr };
    }
  }

  const headers = {
    ...pot.headers,
    Language: locale,
    'Plural-Forms': PLURAL_FORMS,
    'X-Generator': 'scripts/i18n/make-po.mjs',
  };
  delete headers['POT-Creation-Date'];
  headers['PO-Revision-Date'] = new Date()
    .toISOString()
    .replace(/\.\d+Z$/, '+0000')
    .replace('T', ' ')
    .replace(/:\d\d\+/, '+');

  const data = { charset: 'utf-8', headers, translations };

  const poPath = join(LANG_DIR, `feinspitz-${locale}.po`);
  const moPath = join(LANG_DIR, `feinspitz-${locale}.mo`);
  writeFileSync(poPath, gettextParser.po.compile(data, { foldLength: 0 }));
  writeFileSync(moPath, gettextParser.mo.compile(data));

  const total = Object.values(translations).reduce(
    (n, e) => n + Object.keys(e).length - (e[''] ? 1 : 0),
    0
  );
  console.log(`${label} (${locale}):`);
  console.log(`  ${relative(REPO_ROOT, poPath).split(sep).join('/')}`);
  console.log(`  ${relative(REPO_ROOT, moPath).split(sep).join('/')}`);
  console.log(`  Strings: ${total}, ohne Übersetzung: ${missing.length}`);
  if (missing.length) {
    for (const m of missing) console.log(`   - FEHLT: ${JSON.stringify(m)}`);
  }
  return missing.length;
}

// --- Ausführung --------------------------------------------------------------

const pot = gettextParser.po.parse(readFileSync(POT_PATH));

const enMapRaw = JSON.parse(readFileSync(EN_MAP_PATH, 'utf8'));
const enMap = new Map(
  Object.entries(enMapRaw).filter(([k]) => !k.startsWith('_'))
);

// Deutsch = Quellsprache: msgstr = msgid (explizit, damit .mo vollständig ist).
const deMissing = buildLocale(pot, {
  locale: 'de_DE',
  label: 'Deutsch',
  resolve: (msgid) => msgid,
});

// Englisch aus der Übersetzungstabelle.
const enMissing = buildLocale(pot, {
  locale: 'en_US',
  label: 'Englisch',
  resolve: (msgid) => (enMap.has(msgid) ? enMap.get(msgid) : null),
});

if (enMissing > 0) {
  console.error(
    `\nFEHLER: ${enMissing} englische Übersetzung(en) fehlen in translations.en.json.`
  );
  process.exit(1);
}
console.log('\nFertig. de_DE + en_US .po/.mo geschrieben.');
