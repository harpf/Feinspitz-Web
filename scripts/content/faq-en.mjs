// Englische FAQ-Seite — idempotente Inhaltsanlage (HTTP-only via wp/v2, Cookie+Nonce).
//
// Legt an bzw. aktualisiert (idempotent, per Slug/Polylang-Verknüpfung erkannt):
//   • eine englische FAQ-Seite (status publish, lang=en), verknüpft via
//     pll_translations mit der deutschen FAQ-Seite (Slug „faq").
//
// Warum literale core/details-Blöcke statt der Pattern-Referenz?
//   Die deutsche FAQ-Seite bindet das Akkordeon per <!-- wp:pattern
//   {"slug":"feinspitz/faq-accordion"} /--> ein. Dieses Pattern rendert
//   serverseitig aus feinspitz_faq_items() (Deutsch). Würde die EN-Seite dasselbe
//   Pattern referenzieren, erschiene das Akkordeon auf Englisch NUR, wenn alle
//   Strings in der .mo vorliegen — unzuverlässig. Darum schreibt dieses Skript die
//   englischen Fragen/Antworten als LITERALE core/details-Blöcke direkt in den
//   Seiteninhalt (Akkordeon-Struktur + „feinspitz-faq"-Wrapper bleiben erhalten,
//   damit die gescopten Theme-Styles greifen). So rendert /en/faq/ deterministisch
//   Englisch — und inc/ratgeber.php leitet das FAQPage-JSON-LD aus genau diesem
//   Seiteninhalt ab (siehe dort).
//
// Voraussetzung: Das Theme mit der REST-Brücke (theme/feinspitz/inc/i18n.php) muss
// auf dem Server AKTIV sein (Felder lang / pll_translations) — freies Polylang
// bietet dafür keinen REST-Weg. Das Skript erkennt das und bricht mit klarer
// Anleitung ab.
//
//   node scripts/content/faq-en.mjs
//
// Erneut ausführbar ohne Duplikate.

import { wp, WP_BASE } from '../lib/wp.mjs';

const DE_SLUG = 'faq';

// --- Übersetzte Inhalte (Quelle: feinspitz_faq_items() in inc/ratgeber.php) ----
// Eine Aussage pro Antwort → sauberes strukturiertes Markup (FAQPage-JSON-LD).

const FAQ_EN = {
  slug: 'faq',
  title: 'Frequently Asked Questions (FAQ)',
  eyebrow: 'FAQ',
  heading: 'Frequently Asked Questions',
  lead:
    "Answers about histamine-tested wines, shipping within Switzerland, collection in Urdorf, and vegan and alcohol-free wines. Can't find your question? We're happy to help you personally.",
  items: [
    {
      q: 'What does “histamine-tested” mean for a wine?',
      a: 'Histamine-tested wines are analysed in the laboratory for their histamine content. That way you know where you stand before you buy — ideal for anyone who pays attention to tolerability and wants to enjoy wine consciously.',
    },
    {
      q: 'Are histamine-tested wines automatically histamine-free?',
      a: 'No. Wine naturally contains small amounts of histamine — there is no such thing as a histamine-free wine. “Tested” means the content is known and declared as low. If you have been diagnosed with histamine intolerance, please consult your doctor when in doubt.',
    },
    {
      q: 'How do I recognise vegan and alcohol-free wines in the shop?',
      a: 'Suitable wines are labelled with the “vegan” or “alcohol-free” attributes and can be shown specifically using the shop filters. Vegan wines are made without animal-based fining agents; alcohol-free wines are a gentle alternative for carefree enjoyment.',
    },
    {
      q: 'Where does Feinspitz deliver and what does shipping cost?',
      a: 'We ship throughout Switzerland with Swiss delivery partners. Your order is packed carefully in shock-proof wine boxes. You can see the current shipping costs and any free-shipping thresholds transparently in the cart before you order.',
    },
    {
      q: 'How long does delivery within Switzerland take?',
      a: 'We usually process orders within one to two working days. After that, your wine typically reaches you within a few working days — depending on the shipping method and region.',
    },
    {
      q: 'Can I collect my order in Urdorf?',
      a: 'Yes. By arrangement, you can collect your order from us in Urdorf and save the shipping costs. Please wait for our collection confirmation before you come by, so that everything is ready for you.',
    },
    {
      q: 'What is the minimum age to order wine?',
      a: 'We supply alcoholic wines exclusively to people aged 18 and over. By placing your order, you confirm that you are of legal age.',
    },
    {
      q: 'Does Feinspitz also advise me personally with my selection?',
      a: 'With pleasure. We taste our range ourselves and advise you honestly — whether on tolerability, food pairings or the right wine for an occasion. Simply get in touch with us via the contact page.',
    },
  ],
};

// --- Block-Markup-Helfer ----------------------------------------------------

/** HTML-Text escapen (sichtbarer Inhalt in <summary>/<p>). */
const esc = (s) =>
  String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/**
 * Ein core/details-Block (Frage/Antwort). Spiegelt die Struktur aus
 * feinspitz_faq_accordion_markup() (inc/ratgeber.php): Summary als Block-Attribut
 * UND sichtbar, Antwort als einzelner Paragraph.
 */
function detailsBlock({ q, a }) {
  const attrs = JSON.stringify({ summary: q }); // JS escapt keine „/" — passt.
  return (
    `<!-- wp:details ${attrs} -->\n` +
    `<details class="wp-block-details"><summary>${esc(q)}</summary>` +
    `<!-- wp:paragraph -->\n<p>${esc(a)}</p>\n<!-- /wp:paragraph --></details>\n` +
    `<!-- /wp:details -->`
  );
}

/** Vollständiges Seiten-Markup: Intro-Gruppe + Akkordeon (literale details). */
function buildEnContent() {
  const intro =
    `<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained","contentSize":"820px"}} -->\n` +
    `<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">\n` +
    `<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->\n` +
    `<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600">${esc(FAQ_EN.eyebrow)}</p>\n` +
    `<!-- /wp:paragraph -->\n` +
    `<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"lineHeight":"1.05"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"fontSize":"x-large"} -->\n` +
    `<h1 class="wp-block-heading has-text-align-center has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);line-height:1.05">${esc(FAQ_EN.heading)}</h1>\n` +
    `<!-- /wp:heading -->\n` +
    `<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->\n` +
    `<p class="has-text-align-center has-medium-font-size">${esc(FAQ_EN.lead)}</p>\n` +
    `<!-- /wp:paragraph -->\n` +
    `</div>\n` +
    `<!-- /wp:group -->`;

  const details = FAQ_EN.items.map(detailsBlock).join('\n\n');
  const accordion =
    `<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->\n` +
    `<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">\n` +
    `<!-- wp:group {"className":"feinspitz-faq","layout":{"type":"constrained"}} -->\n` +
    `<div class="wp-block-group feinspitz-faq">\n` +
    `${details}\n` +
    `</div>\n` +
    `<!-- /wp:group -->\n` +
    `</div>\n` +
    `<!-- /wp:group -->`;

  return `${intro}\n\n${accordion}`;
}

// --- REST-Helfer ------------------------------------------------------------

/** Seiten mit einem bestimmten Slug (alle Status, edit-Kontext für rohe Felder). */
async function pagesBySlug(slug) {
  const list = await wp('/wp/v2/pages', {
    query: { slug, status: 'publish,draft,pending,future,private', per_page: 20, context: 'edit' },
  });
  return Array.isArray(list) ? list : [];
}

/** Prüft, ob die REST-Brücke (inc/i18n.php) aktiv ist (Feld `lang` vorhanden). */
function bridgeActive(page) {
  return page && Object.prototype.hasOwnProperty.call(page, 'lang');
}

// --- Ablauf -----------------------------------------------------------------

async function main() {
  console.log(`→ Ziel: ${WP_BASE}\n`);

  // 1) Deutsche FAQ-Seite finden -------------------------------------------
  const dePages = await pagesBySlug(DE_SLUG);
  const de = dePages.find((p) => (p.lang || 'de') === 'de') || dePages[0];
  if (!de) {
    throw new Error(
      `Keine deutsche FAQ-Seite (Slug „${DE_SLUG}") gefunden. Zuerst \`npm run content:ratgeber\` ausführen.`
    );
  }
  console.log(`= DE-FAQ-Seite gefunden: ID ${de.id}, lang=${de.lang || '(keine)'}.`);

  // REST-Brücke ist Voraussetzung für Sprachzuweisung/Verknüpfung.
  if (!bridgeActive(de)) {
    console.error(
      '\n✖ REST-Brücke NICHT aktiv (Feld `lang` fehlt in der wp/v2-Antwort).\n' +
        '  Ursache: das Theme mit theme/feinspitz/inc/i18n.php ist auf dem Server noch\n' +
        '  nicht aktiv. → Zuerst dieses Theme deployen (koordiniert), dann erneut ausführen.'
    );
    process.exit(2);
  }

  // 2) Bereits vorhandene EN-Seite ermitteln (Idempotenz) -------------------
  let en = null;
  const linkedEnId = de.pll_translations && de.pll_translations.en;
  if (linkedEnId && linkedEnId !== de.id) {
    en = await wp(`/wp/v2/pages/${linkedEnId}`, { query: { context: 'edit' } }).catch(() => null);
    if (en) console.log(`= Verknüpfte EN-Seite laut Polylang: ID ${en.id}.`);
  }
  if (!en) {
    // Fallback: nach Slug suchen (Polylang kann bei Kollision „faq-2" vergeben).
    const candidates = [...dePages, ...(await pagesBySlug('faq-2').catch(() => []))];
    en = candidates.find((p) => p.lang === 'en') || null;
    if (en) console.log(`= EN-Seite über Slug/Sprache gefunden: ID ${en.id} (Slug „${en.slug}").`);
  }

  // 3) EN-Seite anlegen bzw. aktualisieren ---------------------------------
  const content = buildEnContent();
  const body = {
    slug: FAQ_EN.slug,
    title: FAQ_EN.title,
    content,
    status: 'publish',
    lang: 'en',
  };

  if (en) {
    const updated = await wp(`/wp/v2/pages/${en.id}`, { method: 'POST', body });
    en = updated || en;
    console.log(`✓ EN-FAQ-Seite aktualisiert (ID ${en.id}).`);
  } else {
    en = await wp('/wp/v2/pages', { method: 'POST', body });
    console.log(`✓ EN-FAQ-Seite angelegt (ID ${en.id}, Slug „${en.slug}").`);
  }

  // 4) DE ↔ EN verknüpfen (idempotent; Polylang teilt eine Übersetzungsgruppe) --
  await wp(`/wp/v2/pages/${de.id}`, {
    method: 'POST',
    body: { pll_translations: { de: de.id, en: en.id } },
  });
  console.log(`✓ Verknüpft: DE ${de.id} ↔ EN ${en.id}.`);

  // 5) Slug auf „faq" normalisieren (falls Polylang bei der Anlage „faq-2"
  //    vergeben hat — jetzt ist die Sprache gesetzt, sodass der identische Slug
  //    pro Sprache erlaubt ist). Best-effort; sauberes /en/faq/ als Ziel.
  if (en.slug !== FAQ_EN.slug) {
    const normalized = await wp(`/wp/v2/pages/${en.id}`, {
      method: 'POST',
      body: { slug: FAQ_EN.slug, lang: 'en' },
    }).catch(() => null);
    if (normalized) en = normalized;
    if (en.slug === FAQ_EN.slug) {
      console.log(`✓ EN-Slug auf „${FAQ_EN.slug}" normalisiert.`);
    } else {
      console.log(`⚠ EN-Slug bleibt „${en.slug}" (Polylang-Kollision). Seite funktioniert dennoch.`);
    }
  }

  const enUrl = en.link || `${WP_BASE}/en/${en.slug}/`;
  console.log(`\nEN-FAQ-URL: ${enUrl}`);
  console.log('\n✓ Fertig. Erneutes Ausführen ist gefahrlos (idempotent).');
  console.log(
    'Hinweis: Das FAQPage-JSON-LD leitet inc/ratgeber.php aus dem Seiteninhalt ab —\n' +
      'englische Rich-Snippet-Ausgabe erscheint erst nach einem Theme-Deploy (koordiniert).'
  );
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
