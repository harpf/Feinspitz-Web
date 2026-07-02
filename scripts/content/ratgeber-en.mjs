// Ratgeber (Guide) — englische Übersetzungen (HTTP-only via wp/v2, Cookie+Nonce).
//
// Zweck: Die 4 deutschen Ratgeber-Beiträge der Kategorie „Ratgeber" (Slug ratgeber)
// werden auf Englisch bereitgestellt und via Polylang mit den DE-Originalen
// verknüpft. Vorgehen:
//
//   1. Sprachen prüfen (de default + en) und die REST-Brücke aus
//      theme/feinspitz/inc/i18n.php feature-detekten (Felder `lang` /
//      `pll_translations` auf Beiträgen UND Kategorie-Termen).
//   2. DE-Kategorie „Ratgeber" (Slug ratgeber) holen; EN-Kategorie „Guide"
//      (Slug guide) sicherstellen, ihr lang=en setzen und mit der DE-Kategorie
//      verknüpfen (pll_translations {de:<ratgeberId>, en:<guideId>}).
//   3. Die 4 DE-Beiträge der Kategorie holen und für jeden — anhand einer
//      hand­gepflegten, natürlichen englischen Übersetzung (kein Maschinen-
//      Kauderwelsch; Fachbegriffe wie „histamine" korrekt) — einen EN-Beitrag
//      anlegen (status publish, lang=en, Kategorie=guide) und via
//      pll_translations mit dem DE-Original verknüpfen.
//   4. Verifikation: GET der EN-Beiträge zeigt lang=en und die Verknüpfung; die
//      EN-Ratgeber-Übersicht ist unter /en/ erreichbar.
//
// Idempotent: EN-Kategorie/Beiträge werden per bestehender Verknüpfung
// (pll_translations.en) bzw. per Slug erkannt und nur aktualisiert — kein
// Duplikat bei Zweitlauf.
//
// Voraussetzung: Das Theme mit inc/i18n.php (REST-Brücke) muss auf dem Server
// AKTIV sein — sonst bietet das freie Polylang keinen REST-Weg zur
// Sprachzuweisung. Das Skript erkennt das und bricht mit klarer Meldung ab.
//
//   node scripts/content/ratgeber-en.mjs
//
// Die Übersetzungen sind nach DE-Slug indexiert. So bleibt die Zuordnung stabil,
// egal in welcher Reihenfolge die Beiträge vom Server kommen.

import { wp, WP_BASE } from '../lib/wp.mjs';

// --- kleine Block-Markup-Helfer (spiegeln scripts/content/ratgeber.mjs) -----

const h2 = (t) => `<!-- wp:heading -->\n<h2 class="wp-block-heading">${t}</h2>\n<!-- /wp:heading -->`;
const h3 = (t) => `<!-- wp:heading {"level":3} -->\n<h3 class="wp-block-heading">${t}</h3>\n<!-- /wp:heading -->`;
const p = (t) => `<!-- wp:paragraph -->\n<p>${t}</p>\n<!-- /wp:paragraph -->`;
const ul = (items) =>
  `<!-- wp:list -->\n<ul class="wp-block-list">` +
  items.map((i) => `<!-- wp:list-item -->\n<li>${i}</li>\n<!-- /wp:list-item -->`).join('') +
  `</ul>\n<!-- /wp:list -->`;
const blocks = (...parts) => parts.join('\n\n');

// --- EN-Kategorie „Guide" ---------------------------------------------------

const GUIDE_CATEGORY = {
  slug: 'guide',
  name: 'Guide',
  description:
    'Well-founded knowledge on enjoying low-histamine wine, vegan wines and practical tasting tips from Feinspitz.',
};

// --- Übersetzungen, indexiert nach DE-Slug ----------------------------------
//
// Jeder Eintrag liefert den EN-Slug, Titel, Excerpt (Meta-Description) und den
// vollständigen Block-Inhalt in natürlichem Englisch. Fachbegriffe (histamine,
// biogenic amines, malolactic fermentation, diamine oxidase, fining) sind
// korrekt übersetzt.

const TRANSLATIONS = {
  'histaminarm-geniessen-worauf-es-bei-wein-ankommt': {
    slug: 'enjoying-low-histamine-wine-what-matters',
    title: 'Enjoying low-histamine wine: what matters',
    excerpt:
      'How to enjoy wine with less histamine: what matters when it comes to selection, grape variety and ageing, and how histamine-tested wines add reassurance.',
    content: blocks(
      p(
        'If you react sensitively to histamine, you don’t have to give up wine – above all it comes down to the right selection. Histamine forms in wine as a natural by-product of fermentation, yet the level varies widely from one wine to the next. Once you know what to look for, even a sensitive tolerance leaves plenty of wines to enjoy.'
      ),
      h2('Why wine contains histamine at all'),
      p(
        'Histamine is what’s known as a biogenic amine. It develops when micro-organisms convert certain amino acids during fermentation. Malolactic fermentation in particular – the biological breakdown of acidity that gives a wine its softness – can raise the histamine level. That’s why the level isn’t a matter of „good“ or „bad“, but a question of grape variety, ageing and care in the cellar.'
      ),
      h2('What to look for when choosing'),
      p(
        'A few pointers help with orientation – without you having to become a lab expert:'
      ),
      ul([
        '<strong>White and rosé wines</strong> tend to show lower histamine values than powerful reds, because they less often undergo a pronounced malolactic fermentation.',
        '<strong>Young, fresh wines</strong> are often a good choice if you like things uncomplicated.',
        '<strong>Clean cellar work</strong> counts: winemakers who work hygienically and under control keep biogenic amines low.',
        '<strong>Transparency</strong> is the most important point – only a tested value gives you real reassurance.',
      ]),
      h2('The advantage of histamine-tested wines'),
      p(
        'The histamine content cannot be read from the label alone. This is exactly where histamine-tested wines come in: they are analysed in the laboratory for their histamine content, so the value is known before you buy. Instead of relying on guesswork, you make an informed decision – that is the heart of the idea behind Feinspitz.'
      ),
      h3('Low-histamine is not histamine-free'),
      p(
        'One important note: there is no such thing as a completely histamine-free wine, because small amounts always arise during fermentation. „Low-histamine“ or „tested“ means that the level is low and – above all – known. If you have been diagnosed with a histamine intolerance, discuss your individual enjoyment with your doctor when in doubt.'
      ),
      h2('How to enjoy with peace of mind'),
      p(
        'Start with small amounts of a tested wine, make sure it is well accompanied by a meal, and drink mindfully. That way you combine enjoyment with tolerance – and discover, step by step, which wines suit you best.'
      )
    ),
  },

  'histamin-und-wein-einfach-erklaert': {
    slug: 'histamine-and-wine-explained-simply',
    title: 'Histamine & wine explained simply',
    excerpt:
      'What is histamine, why is it in wine and what does a histamine intolerance mean? Explained clearly – with practical tips for enjoyment.',
    content: blocks(
      p(
        'Plenty of half-truths circulate around histamine and wine. Yet the topic can be explained clearly. This overview shows what histamine is, how it gets into wine and why some people react sensitively to it.'
      ),
      h2('What is histamine?'),
      p(
        'Histamine is a natural messenger substance that also occurs in the human body and performs important tasks – for instance in the immune system and in digestion. At the same time, histamine is present in many foods, especially in matured and fermented products such as cheese, cured sausage, sauerkraut – and, indeed, wine.'
      ),
      h2('How does histamine get into wine?'),
      p(
        'Wine is a product of fermentation. During fermentation, and especially during malolactic fermentation, micro-organisms convert amino acids into biogenic amines – histamine among them. How much forms depends on several factors:'
      ),
      ul([
        'the grape variety and the wine type (red wine tends to be higher than white or rosé),',
        'the ageing in the cellar and the hygiene during processing,',
        'the duration and manner of maturation.',
      ]),
      h2('What does a histamine intolerance mean?'),
      p(
        'With a histamine intolerance, the amount of histamine taken in and the body’s ability to break it down are out of balance. The enzyme usually responsible is diamine oxidase (DAO), which breaks histamine down. If too little of it is active, even small amounts can trigger symptoms. Sensitivity varies greatly from person to person.'
      ),
      h3('Important to put in perspective'),
      p(
        'A histamine intolerance is not an allergy, but a matter of tolerance. Whether and how strongly someone reacts differs individually. This article is no substitute for medical advice – with persistent symptoms, a medical check-up is the right course.'
      ),
      h2('What helps when enjoying wine?'),
      p('Anyone mindful of tolerance can achieve a lot with a few simple principles:'),
      ul([
        '<strong>Choose tested wines:</strong> with histamine-tested wines the level is known and declared as low.',
        '<strong>Dose mindfully:</strong> small amounts and good food alongside make a difference.',
        '<strong>Listen to your own body:</strong> note down which wines agree with you.',
      ]),
      p(
        'That turns an often vague topic into a clear, everyday decision – and wine stays what it is meant to be: a piece of quality of life.'
      )
    ),
  },

  'vegane-weine-was-bedeutet-das': {
    slug: 'vegan-wines-what-does-that-mean',
    title: 'Vegan wines – what does that mean?',
    excerpt:
      'Why isn’t every wine vegan? We explain fining with animal agents, vegan alternatives and how to identify vegan wines with confidence.',
    content: blocks(
      p(
        'Wine is made from grapes – and yet not every wine is automatically vegan. The reason lies not in the ingredients of the finished wine, but in a processing step many people aren’t aware of: fining.'
      ),
      h2('Why isn’t every wine vegan?'),
      p(
        'After fermentation, young wine is often cloudy because it contains fine suspended matter such as haze particles, tannins or proteins. To clarify the wine and round it off in flavour, it is „fined“. In this step an agent binds these particles so they settle out and can be removed. Traditionally, animal-based substances are used for this:'
      ),
      ul([
        '<strong>Gelatine</strong> (from animal connective tissue),',
        '<strong>Isinglass</strong> (from fish bladders),',
        '<strong>Egg white / albumin</strong> (from eggs),',
        '<strong>Casein</strong> (from milk).',
      ]),
      p(
        'These agents do not remain in the finished wine – they are filtered out together with the bound particles. But because animal products are used in the process, a wine treated this way does not count as vegan.'
      ),
      h2('How are vegan wines clarified?'),
      p(
        'Vegan wines rely on plant-based or mineral alternatives – or forgo fining altogether:'
      ),
      ul([
        '<strong>Plant proteins</strong> (for example from pea or potato),',
        '<strong>Bentonite</strong>, a mineral clay,',
        '<strong>Activated charcoal</strong> for specific purposes,',
        '<strong>natural clarification</strong> through time and settling.',
      ]),
      p('None of this changes the result in the glass – vegan wine is in no way inferior to conventional wine.'),
      h2('Vegan and low-histamine at the same time?'),
      p(
        'The two are not mutually exclusive. „Vegan“ describes the type of fining, „histamine-tested“ the known histamine content. So a wine can be both. If you care about several criteria, you simply combine the features when choosing.'
      ),
      h2('How to identify vegan wines'),
      p(
        'In the Feinspitz shop, vegan wines are marked with the „vegan“ attribute and can be displayed specifically through the filters. That way you find exactly the wines that match your requirements with just a few clicks – transparently and without guesswork.'
      )
    ),
  },

  'weingenuss-tipps-temperatur-glas-kombination': {
    slug: 'wine-enjoyment-tips-temperature-glass-pairing',
    title: 'Wine enjoyment tips: temperature, glass, pairing',
    excerpt:
      'The right serving temperature, the fitting glass and successful food pairings: practical tips that let every wine show its best aroma.',
    content: blocks(
      p(
        'A good wine can taste even better – or fall well short of its potential. Three simple levers make the difference: temperature, glass and accompaniment. With a little attention you get the best out of every bottle.'
      ),
      h2('The right serving temperature'),
      p(
        'Temperature is probably the most underrated factor. Served too warm, wine seems clumsy and alcoholic; too cold, it locks its aromas away. As a rough guide:'
      ),
      ul([
        '<strong>Light white wines and rosé:</strong> about 8–10 °C,',
        '<strong>Full-bodied white wines:</strong> about 10–12 °C,',
        '<strong>Light red wines:</strong> about 14–16 °C,',
        '<strong>Full-bodied red wines:</strong> about 16–18 °C – that is, cooler than usual room temperature.',
      ]),
      p(
        'A practical tip: take white wine out of the fridge a few minutes before drinking, and in summer put red wine in briefly. The last few degrees make the difference.'
      ),
      h2('The fitting glass'),
      p(
        'A good glass need not be expensive, but it should concentrate the aromas. Look for a sufficiently large glass with a slightly tapering rim that can only be filled to a scant third – that leaves room for swirling and lets the bouquet unfold. Hold the glass by the stem so the wine isn’t warmed by the heat of your hand.'
      ),
      h3('To decant or not?'),
      p(
        'Young, powerful red wines often benefit from getting a little „air“ before you enjoy them. Simply pouring into a carafe or opening the bottle early can open up the aromas. Very old wines, on the other hand, are handled gently.'
      ),
      h2('Pairing wine and food'),
      p('When it comes to pairing, the rule is: wine and dish should meet as equals.'),
      ul([
        '<strong>Like with like:</strong> hearty dishes go with powerful wines, delicate dishes with light ones.',
        '<strong>Use contrasts:</strong> a fresh, acidity-driven white brings lightness to fried or fatty food.',
        '<strong>Regional goes together:</strong> what comes from the same corner often harmonises by itself.',
      ]),
      p(
        'And the most important tip at the end: the best wine is the one you enjoy. Use these recommendations as a starting point and trust your own taste – that is exactly what enjoyment is all about.'
      )
    ),
  },
};

// --- REST-Helfer ------------------------------------------------------------

/** Beitrag per Slug + Sprache finden (edit-Kontext für rohe Felder + lang). */
async function findPostBySlug(slug) {
  const list = await wp('/wp/v2/posts', {
    query: { slug, status: 'publish,draft,pending,future,private', per_page: 5, context: 'edit' },
  });
  return Array.isArray(list) ? list : [];
}

/** Kategorie per ID holen (edit-Kontext). */
async function getCategory(id) {
  return wp(`/wp/v2/categories/${id}`, { query: { context: 'edit' } });
}

/**
 * EN-Kategorie „Guide" sicherstellen (idempotent):
 *   - bevorzugt über die bestehende Verknüpfung der DE-Kategorie erkannt,
 *   - sonst per Slug „guide",
 *   - sonst neu angelegt.
 * Danach lang=en setzen und DE↔EN verknüpfen. Gibt das EN-Kategorie-Objekt zurück.
 */
async function ensureGuideCategory(deCategory) {
  // 1) bereits verknüpft?
  const linkedEnId = deCategory.pll_translations?.en;
  let guide = null;

  if (linkedEnId && linkedEnId !== deCategory.id) {
    guide = await getCategory(linkedEnId).catch(() => null);
    if (guide) console.log(`= EN-Kategorie via Verknüpfung gefunden (ID ${guide.id}).`);
  }

  // 2) per Slug
  if (!guide) {
    const bySlug = await wp('/wp/v2/categories', {
      query: { slug: GUIDE_CATEGORY.slug, per_page: 5, context: 'edit' },
    });
    if (Array.isArray(bySlug) && bySlug.length) {
      guide = bySlug[0];
      console.log(`= EN-Kategorie „Guide" per Slug gefunden (ID ${guide.id}).`);
    }
  }

  // 3) neu anlegen
  if (!guide) {
    guide = await wp('/wp/v2/categories', {
      method: 'POST',
      body: {
        name: GUIDE_CATEGORY.name,
        slug: GUIDE_CATEGORY.slug,
        description: GUIDE_CATEGORY.description,
      },
    });
    console.log(`✓ EN-Kategorie „Guide" angelegt (ID ${guide.id}).`);
  }

  // lang=en setzen (idempotent — schadet bei erneutem Setzen nicht).
  if (guide.lang !== 'en') {
    await wp(`/wp/v2/categories/${guide.id}`, { method: 'POST', body: { lang: 'en' } });
    console.log(`✓ EN-Kategorie ${guide.id} → lang=en.`);
  }

  // DE↔EN verknüpfen (Term-Übersetzungen).
  const wantLink = { de: deCategory.id, en: guide.id };
  const haveLink = deCategory.pll_translations || {};
  if (haveLink.de !== wantLink.de || haveLink.en !== wantLink.en) {
    await wp(`/wp/v2/categories/${guide.id}`, {
      method: 'POST',
      body: { pll_translations: wantLink },
    });
    console.log(`✓ Kategorie-Verknüpfung gesetzt: de=${deCategory.id} ↔ en=${guide.id}.`);
  } else {
    console.log(`= Kategorie-Verknüpfung bereits gesetzt (de=${deCategory.id} ↔ en=${guide.id}).`);
  }

  // frisch holen, damit lang/tr aktuell sind.
  return getCategory(guide.id);
}

/**
 * EN-Beitrag zu einem DE-Beitrag sicherstellen (idempotent):
 *   - bevorzugt über die bestehende Verknüpfung des DE-Beitrags erkannt,
 *   - sonst per EN-Slug,
 *   - sonst neu angelegt.
 * Danach Inhalt aktualisieren, lang=en + Kategorie=guide setzen und DE↔EN
 * verknüpfen. Gibt das EN-Beitrag-Objekt zurück.
 */
async function ensureEnPost(dePost, tr, guideId) {
  let en = null;

  // 1) bereits verknüpft?
  const linkedEnId = dePost.pll_translations?.en;
  if (linkedEnId && linkedEnId !== dePost.id) {
    en = await wp(`/wp/v2/posts/${linkedEnId}`, { query: { context: 'edit' } }).catch(() => null);
    if (en) console.log(`  = EN-Beitrag via Verknüpfung gefunden (ID ${en.id}).`);
  }

  // 2) per EN-Slug (nur EN-Sprache, um DE nicht zu treffen).
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
    categories: [guideId],
    comment_status: 'closed',
    lang: 'en',
  };

  // 3) anlegen oder aktualisieren.
  if (en) {
    en = await wp(`/wp/v2/posts/${en.id}`, { method: 'POST', body });
    console.log(`  ✓ EN-Beitrag aktualisiert: „${tr.title}" (ID ${en.id}).`);
  } else {
    en = await wp('/wp/v2/posts', { method: 'POST', body });
    console.log(`  ✓ EN-Beitrag angelegt: „${tr.title}" (ID ${en.id}).`);
  }

  // DE↔EN verknüpfen (auf dem EN-Beitrag; Map umfasst beide Seiten).
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

// --- Verifikation -----------------------------------------------------------

/** GET jedes EN-Beitrags und prüfen: lang=en + korrekte Verknüpfung zum DE-Original. */
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

/** Prüft, ob die EN-Ratgeber-Übersicht (Guide-Kategorie) unter /en/ erreichbar ist. */
async function verifyEnOverview(guide) {
  console.log('\n[EN-Übersicht]');
  const link = guide.link || `${WP_BASE}/en/category/${GUIDE_CATEGORY.slug}/`;
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
  console.log(
    `  ${ok ? '✓' : '✗'} ${link} → HTTP ${status}${underEn ? '' : ' (URL nicht unter /en/ !)'}`
  );
  return { link, ok };
}

// --- Ablauf -----------------------------------------------------------------

async function main() {
  console.log(`→ Ziel: ${WP_BASE}\n`);

  // 1) Sprachen + REST-Brücke ------------------------------------------------
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

  // 2) DE-Kategorie „Ratgeber" holen ----------------------------------------
  const deCats = await wp('/wp/v2/categories', {
    query: { slug: 'ratgeber', per_page: 5, context: 'edit' },
  });
  if (!Array.isArray(deCats) || !deCats.length) {
    console.error('✗ DE-Kategorie „Ratgeber" (Slug ratgeber) nicht gefunden. Abbruch.');
    process.exit(1);
  }
  const deCategory = deCats[0];
  // Brücke feature-detekten: das Kategorie-Objekt muss `lang` tragen.
  if (!Object.prototype.hasOwnProperty.call(deCategory, 'lang')) {
    console.error(
      '✗ REST-Brücke NICHT aktiv (Kategorie-Objekt ohne `lang`-Feld).\n' +
        '  Das Theme mit theme/feinspitz/inc/i18n.php ist auf dem Server nicht aktiv.\n' +
        '  Zuerst dieses i18n-Theme deployen, dann erneut ausführen.'
    );
    process.exit(2);
  }
  console.log(`✓ DE-Kategorie „Ratgeber" (ID ${deCategory.id}, lang=${deCategory.lang}).`);

  // 3) EN-Kategorie „Guide" sicherstellen + verknüpfen ----------------------
  const guide = await ensureGuideCategory(deCategory);

  // 4) DE-Beiträge der Kategorie holen --------------------------------------
  const dePosts = await wp('/wp/v2/posts', {
    query: { categories: deCategory.id, per_page: 100, status: 'publish', context: 'edit' },
  });
  console.log(`\n✓ ${dePosts.length} DE-Beitrag/Beiträge in „Ratgeber" gefunden.`);

  // 5) Für jeden DE-Beitrag den EN-Beitrag sicherstellen + verknüpfen -------
  const links = [];
  for (const de of dePosts) {
    const tr = TRANSLATIONS[de.slug];
    console.log(`\n• DE ${de.id} „${de.title?.raw || de.slug}" (${de.slug})`);
    if (!tr) {
      console.warn(`  ⚠ Keine Übersetzung für Slug „${de.slug}" hinterlegt — übersprungen.`);
      continue;
    }
    const en = await ensureEnPost(de, tr, guide.id);
    links.push({ de, en });
  }

  // 6) Verifikation ----------------------------------------------------------
  const postsOk = await verify(links);
  const overview = await verifyEnOverview(guide);

  // Zusammenfassung ----------------------------------------------------------
  console.log('\n═══ Zusammenfassung ═══');
  console.log(`DE-Kategorie ratgeber: ${deCategory.id}`);
  console.log(`EN-Kategorie guide:    ${guide.id} (lang=${guide.lang})`);
  console.log('Beiträge (DE → EN):');
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
