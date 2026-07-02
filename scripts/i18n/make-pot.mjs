// scripts/i18n/make-pot.mjs
//
// Erzeugt theme/feinspitz/languages/feinspitz.pot aus allen übersetzbaren Strings
// des Themes — OHNE WP-CLI. Scannt theme/feinspitz/**/*.php nach den WordPress-
// Gettext-Funktionen (Textdomain `feinspitz`) und schreibt ein gültiges POT via
// gettext-parser.
//
// Unterstützte Funktionen (WordPress-i18n):
//   __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e,
//   _x, _ex, esc_html_x, esc_attr_x, _n, _n_noop, _nx, _nx_noop
//
// Nur Aufrufe mit der Textdomain `feinspitz` werden aufgenommen. Aufrufe mit
// nicht-literalen Argumenten (Variablen/Verkettung) werden übersprungen und am
// Ende als Warnung gemeldet.
//
// Aufruf:  node scripts/i18n/make-pot.mjs
// Ausgabe: theme/feinspitz/languages/feinspitz.pot

import { readFileSync, writeFileSync, readdirSync, statSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, relative, sep } from 'node:path';
import gettextParser from 'gettext-parser';

const __dirname = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = join(__dirname, '..', '..');
const THEME_DIR = join(REPO_ROOT, 'theme', 'feinspitz');
const POT_PATH = join(THEME_DIR, 'languages', 'feinspitz.pot');
const TEXTDOMAIN = 'feinspitz';

// --- Funktions-Signaturen (Argument-Positionen, 0-basiert) -------------------
// msgid    : Position des Haupt-Strings
// plural   : Position der Plural-Form (optional)
// context  : Position des Kontexts (optional)
// domain   : Position der Textdomain
const FUNCS = {
  __:          { msgid: 0, domain: 1 },
  _e:          { msgid: 0, domain: 1 },
  esc_html__:  { msgid: 0, domain: 1 },
  esc_html_e:  { msgid: 0, domain: 1 },
  esc_attr__:  { msgid: 0, domain: 1 },
  esc_attr_e:  { msgid: 0, domain: 1 },
  _x:          { msgid: 0, context: 1, domain: 2 },
  _ex:         { msgid: 0, context: 1, domain: 2 },
  esc_html_x:  { msgid: 0, context: 1, domain: 2 },
  esc_attr_x:  { msgid: 0, context: 1, domain: 2 },
  _n:          { msgid: 0, plural: 1, domain: 3 },
  _n_noop:     { msgid: 0, plural: 1, domain: 2 },
  _nx:         { msgid: 0, plural: 1, context: 3, domain: 4 },
  _nx_noop:    { msgid: 0, plural: 1, context: 2, domain: 3 },
};

// Längste Namen zuerst, damit die Alternation z. B. `esc_html_e` nicht als `_e`
// oder `_nx` nicht als `_n` matcht.
const FUNC_NAMES = Object.keys(FUNCS).sort((a, b) => b.length - a.length);
const CALL_RE = new RegExp(
  `(?<![A-Za-z0-9_$>])(${FUNC_NAMES.join('|')})\\s*\\(`,
  'g'
);

// --- Hilfen ------------------------------------------------------------------

/** Alle *.php-Dateien unter dir rekursiv einsammeln (sortiert für stabile Diffs). */
function collectPhp(dir) {
  const out = [];
  for (const name of readdirSync(dir).sort()) {
    const full = join(dir, name);
    const st = statSync(full);
    if (st.isDirectory()) {
      if (name === 'node_modules' || name === 'languages') continue;
      out.push(...collectPhp(full));
    } else if (name.endsWith('.php')) {
      out.push(full);
    }
  }
  return out;
}

/**
 * Liest die (bereits geöffnete) Argumentliste ab Index `i` (erstes Zeichen nach
 * dem `(`), respektiert String-Literale und verschachtelte Klammern, und gibt
 * den Roh-Inhalt sowie den Index hinter dem schließenden `)` zurück.
 */
function readArgList(src, i) {
  let depth = 1;
  const start = i;
  let inS = false;
  let inD = false;
  for (; i < src.length && depth > 0; i++) {
    const c = src[i];
    if (inS) {
      if (c === '\\') i++;
      else if (c === "'") inS = false;
    } else if (inD) {
      if (c === '\\') i++;
      else if (c === '"') inD = false;
    } else if (c === "'") inS = true;
    else if (c === '"') inD = true;
    else if (c === '(') depth++;
    else if (c === ')') depth--;
  }
  return { content: src.slice(start, i - 1), end: i };
}

/** Top-Level-Argumente an Kommas trennen (Quotes & Klammern beachten). */
function splitArgs(content) {
  const args = [];
  let cur = '';
  let depth = 0;
  let inS = false;
  let inD = false;
  for (let i = 0; i < content.length; i++) {
    const c = content[i];
    if (inS) {
      cur += c;
      if (c === '\\') { cur += content[++i] ?? ''; }
      else if (c === "'") inS = false;
    } else if (inD) {
      cur += c;
      if (c === '\\') { cur += content[++i] ?? ''; }
      else if (c === '"') inD = false;
    } else if (c === "'") { inS = true; cur += c; }
    else if (c === '"') { inD = true; cur += c; }
    else if (c === '(' || c === '[') { depth++; cur += c; }
    else if (c === ')' || c === ']') { depth--; cur += c; }
    else if (c === ',' && depth === 0) { args.push(cur.trim()); cur = ''; }
    else cur += c;
  }
  if (cur.trim() !== '') args.push(cur.trim());
  return args;
}

/** Ist der Ausdruck ein einzelnes String-Literal? Dann dekodieren, sonst null. */
function decodeLiteral(expr) {
  if (expr.length < 2) return null;
  const q = expr[0];
  if ((q !== "'" && q !== '"') || expr[expr.length - 1] !== q) return null;
  const body = expr.slice(1, -1);
  let out = '';
  for (let i = 0; i < body.length; i++) {
    const c = body[i];
    if (c === '\\') {
      const n = body[++i];
      if (q === "'") {
        // PHP single-quote: nur \\ und \' sind Escapes.
        if (n === '\\' || n === "'") out += n;
        else out += '\\' + n;
      } else {
        // PHP double-quote: gängige Escapes.
        const map = { n: '\n', t: '\t', r: '\r', '"': '"', '\\': '\\', $: '$' };
        out += map[n] ?? '\\' + n;
      }
    } else {
      out += c;
    }
  }
  return out;
}

function lineOf(src, index) {
  let line = 1;
  for (let i = 0; i < index; i++) if (src[i] === '\n') line++;
  return line;
}

// --- Scan --------------------------------------------------------------------

const files = collectPhp(THEME_DIR);
/** key = `${msgctxt}${msgid}` → entry */
const entries = new Map();
const warnings = [];

for (const file of files) {
  const src = readFileSync(file, 'utf8');
  const rel = relative(THEME_DIR, file).split(sep).join('/');
  CALL_RE.lastIndex = 0;
  let m;
  while ((m = CALL_RE.exec(src)) !== null) {
    const fn = m[1];
    const spec = FUNCS[fn];
    const openParen = m.index + m[0].length; // Index nach '('
    const { content } = readArgList(src, openParen);
    const args = splitArgs(content);

    const msgid = decodeLiteral(args[spec.msgid] ?? '');
    const domain = spec.domain != null ? decodeLiteral(args[spec.domain] ?? '') : null;
    const line = lineOf(src, m.index);

    if (msgid === null) {
      warnings.push(`${rel}:${line}  ${fn}() — nicht-literales msgid, übersprungen`);
      continue;
    }
    if (domain !== TEXTDOMAIN) {
      warnings.push(
        `${rel}:${line}  ${fn}() — Textdomain ${domain === null ? '(nicht literal/fehlt)' : `'${domain}'`} ≠ '${TEXTDOMAIN}', übersprungen`
      );
      continue;
    }

    const msgctxt = spec.context != null ? decodeLiteral(args[spec.context] ?? '') : null;
    const plural = spec.plural != null ? decodeLiteral(args[spec.plural] ?? '') : null;
    const key = `${msgctxt ?? ''}${msgid}`;

    let e = entries.get(key);
    if (!e) {
      e = { msgid, msgctxt: msgctxt ?? undefined, plural: plural ?? undefined, refs: [] };
      entries.set(key, e);
    }
    if (plural && !e.plural) e.plural = plural;
    e.refs.push(`${rel}:${line}`);
  }
}

// --- POT bauen ---------------------------------------------------------------

// Nach erster Referenz sortieren → stabile, review-freundliche Ausgabe.
const sorted = [...entries.values()].sort((a, b) =>
  (a.refs[0] || '').localeCompare(b.refs[0] || '') || a.msgid.localeCompare(b.msgid)
);

const translations = { '': {} };
for (const e of sorted) {
  const ctx = e.msgctxt ?? '';
  if (!translations[ctx]) translations[ctx] = {};
  const node = {
    msgid: e.msgid,
    msgstr: e.plural ? ['', ''] : [''],
    comments: { reference: e.refs.join('\n') },
  };
  if (e.msgctxt) node.msgctxt = e.msgctxt;
  if (e.plural) node.msgid_plural = e.plural;
  translations[ctx][e.msgid] = node;
}

const pot = {
  charset: 'utf-8',
  headers: {
    'Project-Id-Version': 'Feinspitz Theme',
    'Report-Msgid-Bugs-To': '',
    'POT-Creation-Date': new Date().toISOString().replace(/\.\d+Z$/, '+0000').replace('T', ' ').replace(/:\d\d\+/, '+'),
    'MIME-Version': '1.0',
    'Content-Type': 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding': '8bit',
    'X-Generator': 'scripts/i18n/make-pot.mjs',
    'X-Domain': TEXTDOMAIN,
    'X-Poedit-Basepath': '..',
    'X-Poedit-SearchPath-0': '.',
    'X-Poedit-KeywordsList':
      '__;_e;esc_html__;esc_html_e;esc_attr__;esc_attr_e;' +
      '_x:1,2c;_ex:1,2c;esc_html_x:1,2c;esc_attr_x:1,2c;' +
      '_n:1,2;_n_noop:1,2;_nx:1,2,4c;_nx_noop:1,2,3c',
    Language: '',
  },
  translations,
};

const buf = gettextParser.po.compile(pot, { foldLength: 0 });
mkdirSync(dirname(POT_PATH), { recursive: true });
writeFileSync(POT_PATH, buf);

// --- Report ------------------------------------------------------------------

console.log(`POT geschrieben: ${relative(REPO_ROOT, POT_PATH).split(sep).join('/')}`);
console.log(`  Dateien gescannt : ${files.length}`);
console.log(`  Eindeutige Strings: ${entries.size}`);
if (warnings.length) {
  console.log(`\n  Hinweise (${warnings.length}):`);
  for (const w of warnings) console.log('   - ' + w);
}
