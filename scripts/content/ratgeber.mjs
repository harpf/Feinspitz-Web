// Ratgeber & FAQ — idempotente Inhaltsanlage (HTTP-only via wp/v2, Cookie+Nonce).
//
// Legt an bzw. aktualisiert (idempotent, per Slug erkannt):
//   1. Kategorie „Ratgeber" (Slug: ratgeber)
//   2. 4 SEO-Ratgeber-Beiträge (Kategorie Ratgeber) mit H2/H3-Struktur + Excerpt
//      (Excerpt dient als Meta-Description; siehe inc/ratgeber.php)
//   3. FAQ-Seite (Slug: faq) — Intro + Pattern-Referenz feinspitz/faq-accordion
//      (das FAQPage-JSON-LD rendert inc/ratgeber.php serverseitig aus derselben Quelle)
//   4. Navigation: fügt Ratgeber + FAQ zur Hauptnavigation hinzu, sofern eine
//      wp_navigation existiert; sonst wird eine klare Anleitung ausgegeben.
//
// Erneut ausführbar ohne Duplikate. Standardmässig werden alle vier Schritte
// ausgeführt. Mit `--skip-nav` bleibt die Navigation unangetastet (nur Doku).
//
//   node scripts/content/ratgeber.mjs [--skip-nav]

import { wp, WP_BASE } from '../lib/wp.mjs';

const SKIP_NAV = process.argv.includes('--skip-nav');

// --- kleine Block-Markup-Helfer -------------------------------------------

const h2 = (t) => `<!-- wp:heading -->\n<h2 class="wp-block-heading">${t}</h2>\n<!-- /wp:heading -->`;
const h3 = (t) => `<!-- wp:heading {"level":3} -->\n<h3 class="wp-block-heading">${t}</h3>\n<!-- /wp:heading -->`;
const p = (t) => `<!-- wp:paragraph -->\n<p>${t}</p>\n<!-- /wp:paragraph -->`;
const ul = (items) =>
  `<!-- wp:list -->\n<ul class="wp-block-list">` +
  items.map((i) => `<!-- wp:list-item -->\n<li>${i}</li>\n<!-- /wp:list-item -->`).join('') +
  `</ul>\n<!-- /wp:list -->`;
const blocks = (...parts) => parts.join('\n\n');

// --- Inhalte ---------------------------------------------------------------

const ARTICLES = [
  {
    slug: 'histaminarm-geniessen-worauf-es-bei-wein-ankommt',
    title: 'Histaminarm geniessen: Worauf es bei Wein ankommt',
    excerpt:
      'Wie Sie Wein histaminarm geniessen: worauf es bei Auswahl, Rebsorte und Ausbau ankommt und wie histamingeprüfte Weine für mehr Sicherheit sorgen.',
    content: blocks(
      p(
        'Wer empfindlich auf Histamin reagiert, muss auf Wein nicht verzichten – es kommt vor allem auf die richtige Auswahl an. Histamin entsteht im Wein als natürliches Nebenprodukt der Gärung, doch der Gehalt schwankt stark von Wein zu Wein. Wer weiss, worauf zu achten ist, findet auch mit sensibler Verträglichkeit Weine, die Freude machen.'
      ),
      h2('Warum Wein überhaupt Histamin enthält'),
      p(
        'Histamin ist ein sogenanntes biogenes Amin. Es bildet sich, wenn Mikroorganismen während der Gärung bestimmte Aminosäuren umwandeln. Besonders der biologische Säureabbau (die malolaktische Gärung), der einem Wein Weichheit verleiht, kann den Histamingehalt erhöhen. Deshalb ist der Gehalt keine Frage von „gut" oder „schlecht", sondern eine Frage von Rebsorte, Ausbau und Sorgfalt im Keller.'
      ),
      h2('Worauf Sie bei der Auswahl achten können'),
      p(
        'Ein paar Anhaltspunkte helfen bei der Orientierung – ohne dass Sie zum Labor-Experten werden müssen:'
      ),
      ul([
        '<strong>Weisswein und Roséwein</strong> weisen tendenziell niedrigere Histaminwerte auf als kräftige Rotweine, weil sie seltener einen ausgeprägten biologischen Säureabbau durchlaufen.',
        '<strong>Junge, frische Weine</strong> sind oft eine gute Wahl, wenn Sie es unkompliziert mögen.',
        '<strong>Sauberer Kellerausbau</strong> zählt: Winzer, die hygienisch und kontrolliert arbeiten, halten biogene Amine niedrig.',
        '<strong>Transparenz</strong> ist der wichtigste Punkt – nur ein geprüfter Wert gibt Ihnen echte Sicherheit.',
      ]),
      h2('Der Vorteil histamingeprüfter Weine'),
      p(
        'Anhand des Etiketts allein lässt sich der Histamingehalt nicht ablesen. Genau hier setzen histamingeprüfte Weine an: Sie werden im Labor auf ihren Histamingehalt untersucht, sodass der Wert vor dem Kauf bekannt ist. Statt auf Vermutungen zu vertrauen, treffen Sie eine informierte Entscheidung – das ist der Kern der Idee hinter Feinspitz.'
      ),
      h3('Histaminarm ist nicht histaminfrei'),
      p(
        'Ein wichtiger Hinweis: Einen komplett histaminfreien Wein gibt es nicht, denn geringe Mengen entstehen bei der Gärung immer. „Histaminarm" oder „geprüft" bedeutet, dass der Gehalt niedrig und – vor allem – bekannt ist. Wenn bei Ihnen eine Histaminintoleranz diagnostiziert wurde, besprechen Sie Ihren individuellen Genuss im Zweifel mit Ihrer Ärztin oder Ihrem Arzt.'
      ),
      h2('So geniessen Sie entspannt'),
      p(
        'Beginnen Sie mit kleinen Mengen eines geprüften Weins, achten Sie auf gute Begleitung durch eine Mahlzeit und trinken Sie bewusst. So verbinden Sie Genuss mit Verträglichkeit – und entdecken Schritt für Schritt, welche Weine Ihnen besonders guttun.'
      )
    ),
  },
  {
    slug: 'histamin-und-wein-einfach-erklaert',
    title: 'Histamin & Wein einfach erklärt',
    excerpt:
      'Was ist Histamin, warum steckt es im Wein und was bedeutet eine Histaminunverträglichkeit? Verständlich erklärt – mit praktischen Tipps für den Genuss.',
    content: blocks(
      p(
        'Rund um Histamin und Wein kursieren viele Halbwahrheiten. Dabei lässt sich das Thema gut verständlich erklären. Dieser Überblick zeigt, was Histamin ist, wie es in den Wein gelangt und warum manche Menschen empfindlich darauf reagieren.'
      ),
      h2('Was ist Histamin?'),
      p(
        'Histamin ist ein natürlicher Botenstoff, der auch im menschlichen Körper vorkommt und wichtige Aufgaben übernimmt – etwa im Immunsystem und bei der Verdauung. Gleichzeitig ist Histamin in vielen Lebensmitteln enthalten, besonders in gereiften und fermentierten Produkten wie Käse, Rohwurst, Sauerkraut – und eben Wein.'
      ),
      h2('Wie kommt Histamin in den Wein?'),
      p(
        'Wein ist ein Gärungsprodukt. Während der Gärung und insbesondere beim biologischen Säureabbau wandeln Mikroorganismen Aminosäuren in biogene Amine um – darunter Histamin. Wie viel entsteht, hängt von mehreren Faktoren ab:'
      ),
      ul([
        'der Rebsorte und dem Weintyp (Rotwein tendenziell höher als Weiss- oder Roséwein),',
        'dem Ausbau im Keller und der Hygiene bei der Verarbeitung,',
        'der Dauer und Art der Reifung.',
      ]),
      h2('Was bedeutet eine Histaminunverträglichkeit?'),
      p(
        'Bei einer Histaminintoleranz steht die Menge an aufgenommenem Histamin und die Fähigkeit des Körpers, es abzubauen, in einem Ungleichgewicht. Verantwortlich ist meist das Enzym Diaminoxidase (DAO), das Histamin abbaut. Ist zu wenig davon aktiv, können auch kleine Mengen Beschwerden auslösen. Die Empfindlichkeit ist individuell sehr unterschiedlich.'
      ),
      h3('Wichtig einzuordnen'),
      p(
        'Eine Histaminintoleranz ist keine Allergie, sondern eine Verträglichkeitsfrage. Ob und wie stark jemand reagiert, ist persönlich verschieden. Dieser Artikel ersetzt keine medizinische Beratung – bei anhaltenden Beschwerden ist eine ärztliche Abklärung der richtige Weg.'
      ),
      h2('Was hilft beim Weingenuss?'),
      p('Wer auf Verträglichkeit achtet, kann mit ein paar einfachen Grundsätzen viel erreichen:'),
      ul([
        '<strong>Geprüfte Weine wählen:</strong> Bei histamingeprüften Weinen ist der Gehalt bekannt und niedrig ausgewiesen.',
        '<strong>Bewusst dosieren:</strong> Kleine Mengen und gutes Essen dazu machen einen Unterschied.',
        '<strong>Auf den eigenen Körper hören:</strong> Notieren Sie, welche Weine Ihnen guttun.',
      ]),
      p(
        'So wird aus einem oft diffusen Thema eine klare, alltagstaugliche Entscheidung – und Wein bleibt das, was er sein soll: ein Stück Lebensqualität.'
      )
    ),
  },
  {
    slug: 'vegane-weine-was-bedeutet-das',
    title: 'Vegane Weine – was bedeutet das?',
    excerpt:
      'Warum ist nicht jeder Wein vegan? Wir erklären die Schönung mit tierischen Hilfsmitteln, vegane Alternativen und wie Sie vegane Weine sicher erkennen.',
    content: blocks(
      p(
        'Wein besteht aus Trauben – trotzdem ist nicht jeder Wein automatisch vegan. Der Grund liegt nicht in den Zutaten des fertigen Weins, sondern in einem Verarbeitungsschritt, den viele nicht auf dem Schirm haben: der Schönung.'
      ),
      h2('Warum ist nicht jeder Wein vegan?'),
      p(
        'Nach der Gärung ist Jungwein oft trüb, weil feine Schwebstoffe wie Trübungen, Gerbstoffe oder Eiweisse enthalten sind. Um den Wein zu klären und geschmacklich abzurunden, wird er „geschönt". Dabei bindet ein Hilfsmittel diese Partikel, sodass sie sich absetzen und entfernt werden können. Traditionell kommen dafür tierische Stoffe zum Einsatz:'
      ),
      ul([
        '<strong>Gelatine</strong> (aus tierischem Bindegewebe),',
        '<strong>Hausenblase</strong> (aus Fischblasen),',
        '<strong>Eiklar / Albumin</strong> (aus Eiern),',
        '<strong>Kasein</strong> (aus Milch).',
      ]),
      p(
        'Diese Hilfsmittel verbleiben nicht im fertigen Wein – sie werden mit den gebundenen Partikeln herausgefiltert. Weil im Prozess aber tierische Erzeugnisse verwendet werden, gilt ein so behandelter Wein nicht als vegan.'
      ),
      h2('Wie werden vegane Weine geklärt?'),
      p(
        'Bei veganen Weinen wird auf pflanzliche oder mineralische Alternativen gesetzt – oder ganz auf die Schönung verzichtet:'
      ),
      ul([
        '<strong>Pflanzliche Proteine</strong> (etwa aus Erbse oder Kartoffel),',
        '<strong>Bentonit</strong>, eine mineralische Tonerde,',
        '<strong>Aktivkohle</strong> für bestimmte Zwecke,',
        '<strong>natürliche Klärung</strong> durch Zeit und Absetzenlassen.',
      ]),
      p('Am Ergebnis im Glas ändert das nichts – veganer Wein steht konventionellem in nichts nach.'),
      h2('Vegan und histaminarm zugleich?'),
      p(
        'Beides schliesst sich nicht aus. „Vegan" beschreibt die Art der Schönung, „histamingeprüft" den bekannten Histamingehalt. Ein Wein kann also beides sein. Wer auf mehrere Kriterien achtet, kombiniert die Merkmale einfach bei der Auswahl.'
      ),
      h2('So erkennen Sie vegane Weine'),
      p(
        'Im Feinspitz-Shop sind vegane Weine mit dem Merkmal „vegan" gekennzeichnet und lassen sich über die Filter gezielt anzeigen. So finden Sie mit wenigen Klicks genau die Weine, die zu Ihren Ansprüchen passen – transparent und ohne Rätselraten.'
      )
    ),
  },
  {
    slug: 'weingenuss-tipps-temperatur-glas-kombination',
    title: 'Weingenuss-Tipps: Temperatur, Glas, Kombination',
    excerpt:
      'Die richtige Trinktemperatur, das passende Glas und gelungene Speisekombinationen: praktische Tipps, mit denen jeder Wein sein bestes Aroma zeigt.',
    content: blocks(
      p(
        'Ein guter Wein kann noch besser schmecken – oder deutlich unter seinen Möglichkeiten bleiben. Entscheidend sind drei einfache Stellschrauben: Temperatur, Glas und Begleitung. Mit ein wenig Aufmerksamkeit holen Sie aus jeder Flasche das Beste heraus.'
      ),
      h2('Die richtige Trinktemperatur'),
      p(
        'Temperatur ist der wohl unterschätzteste Faktor. Zu warm serviert wirkt Wein plump und alkoholisch, zu kalt verschliesst er seine Aromen. Als Orientierung:'
      ),
      ul([
        '<strong>Leichte Weissweine und Rosé:</strong> etwa 8–10 °C,',
        '<strong>Kräftige Weissweine:</strong> etwa 10–12 °C,',
        '<strong>Leichte Rotweine:</strong> etwa 14–16 °C,',
        '<strong>Kräftige Rotweine:</strong> etwa 16–18 °C – also kühler als die übliche Zimmertemperatur.',
      ]),
      p(
        'Ein praktischer Tipp: Nehmen Sie Weisswein einige Minuten vor dem Trinken aus dem Kühlschrank und stellen Sie Rotwein im Sommer kurz hinein. Die letzten Grad machen den Unterschied.'
      ),
      h2('Das passende Glas'),
      p(
        'Ein gutes Glas muss nicht teuer sein, aber es sollte die Aromen bündeln. Achten Sie auf ein ausreichend grosses Glas mit sich leicht verjüngendem Rand, das sich nur zu einem knappen Drittel füllen lässt – so bleibt Platz zum Schwenken, und der Duft entfaltet sich. Halten Sie das Glas am Stiel, damit der Wein nicht durch die Handwärme aufheizt.'
      ),
      h3('Dekantieren – ja oder nein?'),
      p(
        'Junge, kräftige Rotweine profitieren oft davon, wenn sie vor dem Genuss etwas „Luft" bekommen. Schon das Umfüllen in eine Karaffe oder das frühzeitige Öffnen kann die Aromen öffnen. Sehr alte Weine hingegen behandelt man behutsam.'
      ),
      h2('Wein und Speisen kombinieren'),
      p('Bei der Kombination gilt: Wein und Gericht sollten sich auf Augenhöhe begegnen.'),
      ul([
        '<strong>Ähnliches zu Ähnlichem:</strong> Kräftige Gerichte vertragen kräftige Weine, feine Speisen leichte Weine.',
        '<strong>Kontraste nutzen:</strong> Ein frischer, säurebetonter Weisswein bringt Leichtigkeit zu Gebratenem oder Fettigem.',
        '<strong>Regionales passt zusammen:</strong> Was aus derselben Ecke stammt, harmoniert oft von selbst.',
      ]),
      p(
        'Und der wichtigste Tipp zum Schluss: Der beste Wein ist der, der Ihnen schmeckt. Nutzen Sie diese Empfehlungen als Ausgangspunkt und vertrauen Sie Ihrem eigenen Geschmack – genau darum geht es beim Geniessen.'
      )
    ),
  },
];

const FAQ_PAGE = {
  slug: 'faq',
  title: 'Häufige Fragen (FAQ)',
  // Intro (statisch) + Akkordeon per Pattern-Referenz. Das Akkordeon UND das
  // FAQPage-JSON-LD rendert inc/ratgeber.php aus feinspitz_faq_items() (eine Quelle).
  content: blocks(
    `<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained","contentSize":"820px"}} -->
<div class="wp-block-group alignfull has-contrast-color has-base-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.28em","fontWeight":"600"}},"textColor":"gold","fontSize":"small"} -->
<p class="has-text-align-center has-gold-color has-text-color has-small-font-size" style="text-transform:uppercase;letter-spacing:0.28em;font-weight:600">FAQ</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"lineHeight":"1.05"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}},"fontSize":"x-large"} -->
<h1 class="wp-block-heading has-text-align-center has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30);line-height:1.05">Häufige Fragen</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->
<p class="has-text-align-center has-medium-font-size">Antworten rund um histamingeprüfte Weine, Versand innerhalb der Schweiz, Abholung in Urdorf sowie vegane und alkoholfreie Weine. Ist Ihre Frage nicht dabei? Wir helfen persönlich weiter.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->`,
    `<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
<!-- wp:pattern {"slug":"feinspitz/faq-accordion"} /-->
</div>
<!-- /wp:group -->`
  ),
};

// --- REST-Helfer ------------------------------------------------------------

/** Kategorie „Ratgeber" sicherstellen (idempotent per Slug). Gibt die ID zurück. */
async function ensureRatgeberCategory() {
  const existing = await wp('/wp/v2/categories', { query: { slug: 'ratgeber', per_page: 1 } });
  if (Array.isArray(existing) && existing.length) {
    console.log(`= Kategorie „Ratgeber" existiert (ID ${existing[0].id}).`);
    return existing[0];
  }
  const created = await wp('/wp/v2/categories', {
    method: 'POST',
    body: {
      name: 'Ratgeber',
      slug: 'ratgeber',
      description:
        'Fundiertes Wissen rund um histaminarmen Weingenuss, vegane Weine und Weingenuss-Tipps von Feinspitz.',
    },
  });
  console.log(`✓ Kategorie „Ratgeber" angelegt (ID ${created.id}).`);
  return created;
}

/** Beitrag/Seite per Slug finden (Status any, edit-Kontext für rohes Feld). */
async function findBySlug(type, slug) {
  const list = await wp(`/wp/v2/${type}`, {
    query: { slug, status: 'publish,draft,pending,future,private', per_page: 1, context: 'edit' },
  });
  return Array.isArray(list) && list.length ? list[0] : null;
}

/** Beitrag anlegen/aktualisieren (idempotent per Slug). */
async function upsertPost(article, categoryId) {
  const body = {
    slug: article.slug,
    title: article.title,
    content: article.content,
    excerpt: article.excerpt,
    status: 'publish',
    categories: [categoryId],
    comment_status: 'closed',
  };
  const existing = await findBySlug('posts', article.slug);
  if (existing) {
    await wp(`/wp/v2/posts/${existing.id}`, { method: 'POST', body });
    console.log(`✓ Beitrag aktualisiert: „${article.title}" (ID ${existing.id}).`);
    return existing;
  }
  const created = await wp('/wp/v2/posts', { method: 'POST', body });
  console.log(`✓ Beitrag angelegt: „${article.title}" (ID ${created.id}).`);
  return created;
}

/** FAQ-Seite anlegen/aktualisieren (idempotent per Slug). */
async function upsertFaqPage() {
  const body = {
    slug: FAQ_PAGE.slug,
    title: FAQ_PAGE.title,
    content: FAQ_PAGE.content,
    status: 'publish',
  };
  const existing = await findBySlug('pages', FAQ_PAGE.slug);
  if (existing) {
    await wp(`/wp/v2/pages/${existing.id}`, { method: 'POST', body });
    console.log(`✓ FAQ-Seite aktualisiert (ID ${existing.id}).`);
    return existing;
  }
  const created = await wp('/wp/v2/pages', { method: 'POST', body });
  console.log(`✓ FAQ-Seite angelegt (ID ${created.id}).`);
  return created;
}

// --- Navigation -------------------------------------------------------------

function navLink(label, url) {
  return `<!-- wp:navigation-link {"label":"${label}","url":"${url}","kind":"custom","isTopLevelLink":true} /-->`;
}

/**
 * Ratgeber + FAQ zur Hauptnavigation hinzufügen (idempotent).
 * Gibt es keine wp_navigation, wird nur dokumentiert.
 */
async function updateNavigation(ratgeberUrl, faqUrl) {
  const navs = await wp('/wp/v2/navigation', {
    query: { per_page: 100, status: 'publish,draft', context: 'edit' },
  }).catch(() => []);

  if (!Array.isArray(navs) || navs.length === 0) {
    documentNavigation(ratgeberUrl, faqUrl, 'Es wurde keine wp_navigation gefunden.');
    return;
  }

  // Ziel wie WordPress-Fallback: die zuletzt angelegte (höchste ID) veröffentlichte Navigation.
  const published = navs.filter((n) => n.status === 'publish');
  const pool = published.length ? published : navs;
  const target = pool.reduce((a, b) => (b.id > a.id ? b : a));

  let content = (target.content && (target.content.raw ?? target.content)) || '';
  if (typeof content !== 'string') content = '';

  const additions = [];
  if (!content.includes('>Ratgeber<') && !content.includes(ratgeberUrl)) {
    additions.push(navLink('Ratgeber', ratgeberUrl));
  }
  if (!content.includes('>FAQ<') && !content.includes(faqUrl)) {
    additions.push(navLink('FAQ', faqUrl));
  }

  if (additions.length === 0) {
    console.log(`= Navigation (ID ${target.id}) enthält Ratgeber + FAQ bereits.`);
    return;
  }

  const newContent = `${content.trim()}\n\n${additions.join('\n\n')}\n`;
  try {
    await wp(`/wp/v2/navigation/${target.id}`, {
      method: 'POST',
      body: { content: newContent },
    });
    console.log(`✓ Navigation (ID ${target.id}) ergänzt: ${additions.length} Eintrag/Einträge.`);
  } catch (err) {
    documentNavigation(ratgeberUrl, faqUrl, `Automatisches Update fehlgeschlagen: ${err.message}`);
  }
}

function documentNavigation(ratgeberUrl, faqUrl, reason) {
  console.log('\n──────────────────────────────────────────────────────────────');
  console.log('NAVIGATION — manuell zu ergänzen (für den Integrator):');
  if (reason) console.log(`Grund: ${reason}`);
  console.log('Bitte im Editor unter Design → Navigation zwei Einträge hinzufügen:');
  console.log(`  • Ratgeber → ${ratgeberUrl}`);
  console.log(`  • FAQ      → ${faqUrl}`);
  console.log('Alternativ als Blöcke in der wp_navigation:');
  console.log('  ' + navLink('Ratgeber', ratgeberUrl));
  console.log('  ' + navLink('FAQ', faqUrl));
  console.log('──────────────────────────────────────────────────────────────\n');
}

// --- Ablauf -----------------------------------------------------------------

async function main() {
  console.log(`→ Ziel: ${WP_BASE}\n`);

  const category = await ensureRatgeberCategory();
  for (const article of ARTICLES) {
    await upsertPost(article, category.id);
  }
  const faqPage = await upsertFaqPage();

  const ratgeberUrl = category.link || `${WP_BASE}/category/ratgeber/`;
  const faqUrl = faqPage.link || `${WP_BASE}/faq/`;
  console.log(`\nÜbersicht-URL (Ratgeber): ${ratgeberUrl}`);
  console.log(`FAQ-URL:                  ${faqUrl}`);

  if (SKIP_NAV) {
    documentNavigation(ratgeberUrl, faqUrl, '--skip-nav gesetzt (Navigation bewusst unangetastet).');
  } else {
    await updateNavigation(ratgeberUrl, faqUrl);
  }

  console.log('\n✓ Fertig. Erneutes Ausführen ist gefahrlos (idempotent).');
  console.log('Hinweis: Akkordeon & FAQ-JSON-LD rendern erst, wenn das Theme mit inc/ratgeber.php aktiv ist.');
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
