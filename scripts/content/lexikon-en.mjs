// Weinlexikon (Wine Glossary) — englische Übersetzungen (HTTP-only via wp/v2, Cookie+Nonce).
//
// Zweck: Die wichtigsten deutschen Glossar-Beiträge der Kategorie „Weinlexikon"
// (Slug weinlexikon) werden auf Englisch bereitgestellt und via Polylang mit den
// DE-Originalen verknüpft. Übersetzt werden die verkaufs- und markenrelevantesten
// Einträge (Histamin-Begriffe + die wichtigsten Rebsorten) — nicht zwingend alle.
//
// Vorgehen (analog scripts/content/ratgeber-en.mjs):
//   1. Sprachen prüfen (de default + en) und die REST-Brücke aus
//      theme/feinspitz/inc/i18n.php feature-detekten (Felder `lang` /
//      `pll_translations` auf Beiträgen UND Kategorie-Termen).
//   2. DE-Kategorie „Weinlexikon" (Slug weinlexikon) holen; EN-Kategorie
//      „Wine Glossary" (Slug glossary) sicherstellen, lang=en setzen und mit der
//      DE-Kategorie verknüpfen (pll_translations {de,en}).
//   3. Die DE-Beiträge holen und für jene mit hinterlegter Übersetzung (indexiert
//      nach DE-Slug) einen EN-Beitrag anlegen (publish, lang=en, Kategorie=glossary)
//      und via pll_translations mit dem DE-Original verknüpfen.
//   4. Cross-Links (Token %%en-slug%%) gegen die echten EN-Permalinks auflösen.
//   5. Verifikation: EN-Beiträge tragen lang=en + korrekte Verknüpfung; die
//      EN-Übersicht (Glossary-Kategorie) ist unter /en/ erreichbar.
//
// Idempotent: EN-Kategorie/Beiträge per Verknüpfung bzw. Slug erkannt, nur
// aktualisiert — kein Duplikat bei Zweitlauf.
//
// Voraussetzung: Das Theme mit inc/i18n.php (REST-Brücke) muss auf dem Server
// AKTIV sein. Das Skript erkennt das und bricht sonst mit klarer Meldung ab.
//
//   node scripts/content/lexikon-en.mjs

import { wp, WP_BASE } from '../lib/wp.mjs';

// --- kleine Block-Markup-Helfer (spiegeln scripts/content/lexikon.mjs) -------

const h2 = (t) => `<!-- wp:heading -->\n<h2 class="wp-block-heading">${t}</h2>\n<!-- /wp:heading -->`;
const p = (t) => `<!-- wp:paragraph -->\n<p>${t}</p>\n<!-- /wp:paragraph -->`;
const ul = (items) =>
  `<!-- wp:list -->\n<ul class="wp-block-list">` +
  items.map((i) => `<!-- wp:list-item -->\n<li>${i}</li>\n<!-- /wp:list-item -->`).join('') +
  `</ul>\n<!-- /wp:list -->`;
const blocks = (...parts) => parts.join('\n\n');

// Cross-Link auf einen anderen EN-Eintrag (per EN-Slug). Im 2. Pass ersetzt.
const lk = (enSlug, label) => `<a href="%%${enSlug}%%">${label}</a>`;

// --- EN-Kategorie „Wine Glossary" ------------------------------------------

const GLOSSARY_CATEGORY = {
  slug: 'glossary',
  name: 'Wine Glossary',
  description:
    'The Feinspitz wine glossary: grape varieties, wine regions, tasting terms and histamine knowledge · explained clearly and concisely.',
};

// --- Übersetzungen, indexiert nach DE-Slug ----------------------------------
//
// EN-Slugs bewusst distinkt zu den DE-Slugs (kein „-2"-Suffix nötig). Cross-Links
// verweisen nur auf ebenfalls übersetzte Einträge.

const TRANSLATIONS = {
  'histamin-im-wein': {
    slug: 'histamine-in-wine',
    title: 'Histamine in wine',
    excerpt:
      'Why wine contains histamine, how it forms during fermentation, and why red wines tend to show higher levels than white and rosé wines.',
    content: blocks(
      p(
        `Histamine is a natural substance found in many matured and fermented foods – wine among them. It belongs to the ${lk('biogenic-amines', 'biogenic amines')} and forms as a by-product of fermentation.`
      ),
      h2('How does histamine get into wine?'),
      p(
        'During fermentation, and especially during the biological breakdown of acidity (malolactic fermentation), micro-organisms convert amino acids into biogenic amines – histamine included. How much forms depends on the grape variety, the ageing and the care taken in the cellar.'
      ),
      h2('What to look for'),
      p(
        `As a rule of thumb, white and rosé wines tend to show lower levels than powerful reds such as ${lk('zweigelt', 'Zweigelt')}, because they less often undergo a pronounced malolactic fermentation. There is, however, no such thing as a completely histamine-free wine. Real reassurance comes from ${lk('histamine-tested-wines', 'histamine-tested wines')}, whose content has been determined in the laboratory.`
      )
    ),
  },

  'histamingepruefte-weine': {
    slug: 'histamine-tested-wines',
    title: 'Histamine-tested wines',
    excerpt:
      'Histamine-tested wines are analysed in the laboratory for their histamine content – so the value is known before you buy. What that means, and what it doesn’t.',
    content: blocks(
      p(
        `Histamine-tested wines are at the heart of what Feinspitz does. Unlike ordinary wine, their ${lk('histamine-in-wine', 'histamine content')} is known because it has been measured in the laboratory – so you know where you stand before you buy.`
      ),
      h2('What “tested” means'),
      p(
        'The histamine content cannot be seen in a wine and is not printed on the label. A laboratory analysis creates transparency: instead of relying on guesswork, you make an informed decision. That is especially valuable for anyone who consciously cares about tolerance.'
      ),
      h2('Tested is not the same as histamine-free'),
      p(
        `Important: “histamine-tested” means the level is known and declared as low – not that no histamine is present at all. Small amounts always arise during fermentation (see ${lk('biogenic-amines', 'biogenic amines')}). If you have been diagnosed with a histamine intolerance, please consult your doctor when in doubt.`
      )
    ),
  },

  'biogene-amine': {
    slug: 'biogenic-amines',
    title: 'Biogenic amines',
    excerpt:
      'Biogenic amines such as histamine and tyramine form during fermentation and maturation. What they are, how they get into wine, and why some people react sensitively.',
    content: blocks(
      p(
        'Biogenic amines are natural substances that form when micro-organisms convert amino acids. They occur in many fermented and matured foods – such as cheese, cured sausage, sauerkraut and, indeed, wine.'
      ),
      h2('The most important ones'),
      p(
        `The best-known biogenic amines in wine include ${lk('histamine-in-wine', 'histamine')}, tyramine and putrescine. They form above all during fermentation and the biological breakdown of acidity. Clean, hygienic work in the cellar keeps their level low.`
      ),
      h2('Why they matter'),
      p(
        `Some people are less able to break down biogenic amines – histamine in particular – and react sensitively to them. For them, transparency is decisive: ${lk('histamine-tested-wines', 'histamine-tested wines')} make the content visible and enjoyment more predictable.`
      )
    ),
  },

  'gruener-veltliner': {
    slug: 'gruener-veltliner',
    title: 'Grüner Veltliner',
    excerpt:
      'Grüner Veltliner is Austria’s signature grape: a spicy, fresh white wine with a peppery note, a great companion to food and usually easy to enjoy.',
    content: blocks(
      p(
        'Grüner Veltliner is by far the most important grape variety in Austria and shapes the country’s Lower Austrian wine regions in particular. Its hallmark is a fresh, spicy character with a fine peppery note – connoisseurs fondly call it the “Pfefferl”.'
      ),
      h2('How does Grüner Veltliner taste?'),
      p(
        'In the glass it usually shows dry, juicy and lively, with notes of green apple, citrus and white pepper. Lighter versions are wonderfully uncomplicated food companions, while single-vineyard wines from top regions develop remarkable density and ageing potential.'
      ),
      h2('What does it go with?'),
      p(
        `As a versatile dry white, Grüner Veltliner harmonises with vegetable dishes, white meat, fish and classic Austrian cuisine. As a fresh white wine it also tends to show lower ${lk('histamine-in-wine', 'histamine values')} than powerful reds.`
      )
    ),
  },

  zweigelt: {
    slug: 'zweigelt',
    title: 'Zweigelt',
    excerpt:
      'Zweigelt is Austria’s most-planted red grape: velvety and fruity with cherry notes, soft tannins and a pleasantly approachable character.',
    content: blocks(
      p(
        `Zweigelt is the most widely planted red grape variety in Austria. It was bred in 1922 as a cross of Blaufränkisch and St. Laurent, combining their strengths into a charming, approachable red wine.`
      ),
      h2('Typical flavour'),
      p(
        'Zweigelt smells of sour cherry and morello, is velvety on the palate and carries soft, round tannins. The spectrum ranges from the light, cheerful style to the powerful, oak-aged single-vineyard wine – what they share is a juicy fruitiness.'
      ),
      h2('Enjoyment & tolerance'),
      p(
        `Because powerful reds tend to contain more ${lk('histamine-in-wine', 'histamine')}, it is worth looking out for ${lk('histamine-tested-wines', 'histamine-tested wines')} if you are sensitive. Zweigelt pairs excellently with grilled food, braised dishes and spicy cheese.`
      )
    ),
  },

  blaufraenkisch: {
    slug: 'blaufraenkisch',
    title: 'Blaufränkisch',
    excerpt:
      'Blaufränkisch is a powerful, spicy Austrian red grape with dark fruit, marked acidity and a pronounced sense of terroir.',
    content: blocks(
      p(
        'Blaufränkisch is among the noblest red grape varieties in Austria and produces characterful, age-worthy wines. The variety is considered especially true to its terroir: it clearly mirrors soil and origin in the glass.'
      ),
      h2('Character'),
      p(
        'Typical are aromas of dark berries, blackberry and morello cherry, along with spicy, often peppery notes and a firm, carrying acidity. Young wines seem juicy and fresh; matured ones develop depth, structure and fine tannins.'
      ),
      h2('Kinship & origin'),
      p(
        `As a parent of ${lk('zweigelt', 'Zweigelt')}, Blaufränkisch has shaped the Austrian red-wine landscape. It finds ideal conditions in Styria and Burgenland and is a reliable companion to hearty meat dishes.`
      )
    ),
  },

  riesling: {
    slug: 'riesling',
    title: 'Riesling',
    excerpt:
      'Riesling is regarded as the king of white wines: delicate, mineral and long-lived, with racy acidity and aromas of peach, citrus and ripe apple.',
    content: blocks(
      p(
        'To many, Riesling is the noblest white grape of all. It combines finesse with expressive power and produces both bone-dry and gently sweet wines of great longevity.'
      ),
      h2('Aroma & style'),
      p(
        'Characteristic are aromas of peach, apricot, citrus and ripe apple, carried by a racy, invigorating acidity and a clear minerality. Riesling is most often made dry, though as a Prädikat wine it also offers magnificent noble-sweet versions.'
      ),
      h2('Origin'),
      p(
        `In the steep, mineral sites of Lower Austria, Riesling finds ideal conditions. As a fresh white wine it tends to show lower ${lk('histamine-in-wine', 'histamine values')} than powerful reds.`
      )
    ),
  },
};

// --- REST-Helfer ------------------------------------------------------------

async function findPostBySlug(slug) {
  const list = await wp('/wp/v2/posts', {
    query: { slug, status: 'publish,draft,pending,future,private', per_page: 5, context: 'edit' },
  });
  return Array.isArray(list) ? list : [];
}

async function getCategory(id) {
  return wp(`/wp/v2/categories/${id}`, { query: { context: 'edit' } });
}

/** EN-Kategorie „Wine Glossary" sicherstellen (idempotent) + verknüpfen. */
async function ensureGlossaryCategory(deCategory) {
  const linkedEnId = deCategory.pll_translations?.en;
  let glossary = null;

  if (linkedEnId && linkedEnId !== deCategory.id) {
    glossary = await getCategory(linkedEnId).catch(() => null);
    if (glossary) console.log(`= EN-Kategorie via Verknüpfung gefunden (ID ${glossary.id}).`);
  }

  if (!glossary) {
    const bySlug = await wp('/wp/v2/categories', {
      query: { slug: GLOSSARY_CATEGORY.slug, per_page: 5, context: 'edit' },
    });
    if (Array.isArray(bySlug) && bySlug.length) {
      glossary = bySlug[0];
      console.log(`= EN-Kategorie „Wine Glossary" per Slug gefunden (ID ${glossary.id}).`);
    }
  }

  if (!glossary) {
    glossary = await wp('/wp/v2/categories', {
      method: 'POST',
      body: {
        name: GLOSSARY_CATEGORY.name,
        slug: GLOSSARY_CATEGORY.slug,
        description: GLOSSARY_CATEGORY.description,
      },
    });
    console.log(`✓ EN-Kategorie „Wine Glossary" angelegt (ID ${glossary.id}).`);
  }

  if (glossary.lang !== 'en') {
    await wp(`/wp/v2/categories/${glossary.id}`, { method: 'POST', body: { lang: 'en' } });
    console.log(`✓ EN-Kategorie ${glossary.id} → lang=en.`);
  }

  const wantLink = { de: deCategory.id, en: glossary.id };
  const haveLink = deCategory.pll_translations || {};
  if (haveLink.de !== wantLink.de || haveLink.en !== wantLink.en) {
    await wp(`/wp/v2/categories/${glossary.id}`, { method: 'POST', body: { pll_translations: wantLink } });
    console.log(`✓ Kategorie-Verknüpfung gesetzt: de=${deCategory.id} ↔ en=${glossary.id}.`);
  } else {
    console.log(`= Kategorie-Verknüpfung bereits gesetzt (de=${deCategory.id} ↔ en=${glossary.id}).`);
  }

  return getCategory(glossary.id);
}

/**
 * EN-Beitrag sicherstellen (idempotent) + verknüpfen. Schreibt zunächst den
 * Token-behafteten Inhalt; die Cross-Links werden im 2. Pass aufgelöst.
 * Gibt das EN-Beitrag-Objekt zurück.
 */
async function ensureEnPost(dePost, tr, glossaryId) {
  let en = null;

  const linkedEnId = dePost.pll_translations?.en;
  if (linkedEnId && linkedEnId !== dePost.id) {
    en = await wp(`/wp/v2/posts/${linkedEnId}`, { query: { context: 'edit' } }).catch(() => null);
    if (en) console.log(`  = EN-Beitrag via Verknüpfung gefunden (ID ${en.id}).`);
  }

  if (!en) {
    const candidates = await findPostBySlug(tr.slug);
    en = candidates.find((c) => c.lang === 'en') || candidates.find((c) => c.id !== dePost.id) || null;
    if (en) console.log(`  = EN-Beitrag per Slug „${tr.slug}" gefunden (ID ${en.id}).`);
  }

  const body = {
    slug: tr.slug,
    title: tr.title,
    content: tr.content,
    excerpt: tr.excerpt,
    status: 'publish',
    categories: [glossaryId],
    comment_status: 'closed',
    lang: 'en',
  };

  if (en) {
    en = await wp(`/wp/v2/posts/${en.id}`, { method: 'POST', body });
    console.log(`  ✓ EN-Beitrag aktualisiert: „${tr.title}" (ID ${en.id}).`);
  } else {
    en = await wp('/wp/v2/posts', { method: 'POST', body });
    console.log(`  ✓ EN-Beitrag angelegt: „${tr.title}" (ID ${en.id}).`);
  }

  const wantLink = { de: dePost.id, en: en.id };
  const haveLink = en.pll_translations || {};
  if (haveLink.de !== wantLink.de || haveLink.en !== wantLink.en) {
    await wp(`/wp/v2/posts/${en.id}`, { method: 'POST', body: { pll_translations: wantLink } });
    console.log(`  ✓ Beitrags-Verknüpfung gesetzt: de=${dePost.id} ↔ en=${en.id}.`);
  } else {
    console.log(`  = Beitrags-Verknüpfung bereits gesetzt (de=${dePost.id} ↔ en=${en.id}).`);
  }

  return en;
}

/** Token %%en-slug%% durch echte EN-Permalinks ersetzen. */
function resolveLinks(content, linkMap) {
  return content.replace(/%%([a-z0-9-]+)%%/g, (m, slug) => {
    const url = linkMap.get(slug);
    if (!url) {
      console.warn(`  ⚠ Kein EN-Permalink für Cross-Link „${slug}" — Token bleibt.`);
      return m;
    }
    return url;
  });
}

// --- Verifikation -----------------------------------------------------------

async function verify(links) {
  console.log('\n[Verifikation]');
  let ok = true;
  for (const { de, en } of links) {
    const fresh = await wp(`/wp/v2/posts/${en.id}`, { query: { context: 'edit' } });
    const langOk = fresh.lang === 'en';
    const linkOk = fresh.pll_translations?.de === de.id && fresh.pll_translations?.en === en.id;
    console.log(
      `  ${langOk && linkOk ? '✓' : '✗'} EN ${en.id} „${fresh.title?.raw || fresh.slug}" ` +
        `lang=${fresh.lang} tr=${JSON.stringify(fresh.pll_translations)} (DE ${de.id})`
    );
    if (!langOk || !linkOk) ok = false;
  }
  return ok;
}

async function verifyEnOverview(glossary) {
  console.log('\n[EN-Übersicht]');
  const link = glossary.link || `${WP_BASE}/en/category/${GLOSSARY_CATEGORY.slug}/`;
  const underEn = /\/en\//.test(link);
  let status = 0;
  try {
    const res = await fetch(link, { redirect: 'follow' });
    status = res.status;
  } catch (e) {
    console.log(`  ✗ Abruf fehlgeschlagen: ${e.message}`);
    return { link, ok: false };
  }
  const ok = status >= 200 && status < 400 && underEn;
  console.log(`  ${ok ? '✓' : '✗'} ${link} → HTTP ${status}${underEn ? '' : ' (URL nicht unter /en/ !)'}`);
  return { link, ok };
}

// --- Ablauf -----------------------------------------------------------------

async function main() {
  console.log(`→ Ziel: ${WP_BASE}\n`);

  // 1) Sprachen + REST-Brücke
  let langs;
  try {
    langs = await wp('/pll/v1/languages');
  } catch (e) {
    console.error(`✗ Polylang-REST nicht erreichbar (${e.status || e.message}). Abbruch.`);
    process.exit(1);
  }
  const bySlug = Object.fromEntries(langs.map((l) => [l.slug, l]));
  if (!bySlug.de || !bySlug.en) {
    console.error('✗ Sprachen DE und/oder EN fehlen auf dem Server. Abbruch.');
    process.exit(1);
  }
  console.log(`✓ Sprachen: ${langs.map((l) => `${l.slug}${l.is_default ? '(default)' : ''}`).join(', ')}`);

  // 2) DE-Kategorie „Weinlexikon" holen
  const deCats = await wp('/wp/v2/categories', {
    query: { slug: 'weinlexikon', per_page: 5, context: 'edit' },
  });
  if (!Array.isArray(deCats) || !deCats.length) {
    console.error('✗ DE-Kategorie „Weinlexikon" (Slug weinlexikon) nicht gefunden. Zuerst lexikon.mjs ausführen. Abbruch.');
    process.exit(1);
  }
  const deCategory = deCats[0];
  if (!Object.prototype.hasOwnProperty.call(deCategory, 'lang')) {
    console.error(
      '✗ REST-Brücke NICHT aktiv (Kategorie-Objekt ohne `lang`-Feld).\n' +
        '  Das Theme mit theme/feinspitz/inc/i18n.php ist auf dem Server nicht aktiv.\n' +
        '  Zuerst dieses i18n-Theme deployen, dann erneut ausführen.'
    );
    process.exit(2);
  }
  console.log(`✓ DE-Kategorie „Weinlexikon" (ID ${deCategory.id}, lang=${deCategory.lang}).`);

  // 3) EN-Kategorie „Wine Glossary" sicherstellen + verknüpfen
  const glossary = await ensureGlossaryCategory(deCategory);

  // 4) DE-Beiträge der Kategorie holen
  const dePosts = await wp('/wp/v2/posts', {
    query: { categories: deCategory.id, per_page: 100, status: 'publish', context: 'edit' },
  });
  console.log(`\n✓ ${dePosts.length} DE-Beitrag/Beiträge in „Weinlexikon" gefunden.`);

  // 5) EN-Beiträge sicherstellen (Pass 1: Token-Inhalt)
  const links = [];
  for (const de of dePosts) {
    const tr = TRANSLATIONS[de.slug];
    if (!tr) continue; // nur die hinterlegten (wichtigsten) Einträge übersetzen
    console.log(`\n• DE ${de.id} „${de.title?.raw || de.slug}" (${de.slug})`);
    const en = await ensureEnPost(de, tr, glossary.id);
    links.push({ de, en, tr });
  }

  if (!links.length) {
    console.warn('\n⚠ Keine übersetzbaren DE-Beiträge gefunden (Slugs stimmen nicht?). Abbruch.');
    process.exit(3);
  }

  // 6) Cross-Links auflösen (Pass 2)
  console.log(`\n[Pass 2] EN-Cross-Links auflösen …`);
  const enLinkMap = new Map();
  for (const { en, tr } of links) {
    enLinkMap.set(tr.slug, en.link);
  }
  let linked = 0;
  for (const { en, tr } of links) {
    if (!tr.content.includes('%%')) continue;
    const resolved = resolveLinks(tr.content, enLinkMap);
    await wp(`/wp/v2/posts/${en.id}`, { method: 'POST', body: { content: resolved } });
    linked++;
  }
  console.log(`  ✓ ${linked} EN-Beiträge mit aufgelösten internen Links aktualisiert.`);

  // 7) Verifikation
  const postsOk = await verify(links);
  const overview = await verifyEnOverview(glossary);

  console.log('\n═══ Zusammenfassung ═══');
  console.log(`DE-Kategorie weinlexikon: ${deCategory.id}`);
  console.log(`EN-Kategorie glossary:    ${glossary.id} (lang=${glossary.lang})`);
  console.log(`EN-Beiträge (DE → EN):    ${links.length}`);
  for (const { de, en } of links) {
    console.log(`  ${de.id} → ${en.id}  ${en.link || ''}`);
  }
  console.log(`EN-Übersicht: ${overview.link}`);

  if (postsOk && overview.ok) {
    console.log('\n✓ Fertig — alle EN-Beiträge verknüpft & verifiziert. Erneutes Ausführen ist gefahrlos (idempotent).');
  } else {
    console.log('\n⚠ Fertig mit Warnungen — bitte die Verifikations-Ausgabe oben prüfen.');
    process.exitCode = 3;
  }
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
