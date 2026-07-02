// Weinlexikon (Glossar) — idempotente Inhaltsanlage (HTTP-only via wp/v2, Cookie+Nonce).
//
// Legt an bzw. aktualisiert (idempotent, per Slug erkannt):
//   1. Kategorie „Weinlexikon" (Slug: weinlexikon)
//   2. 16 Glossar-Beiträge (Kategorie Weinlexikon) mit sauberer H2-Struktur +
//      Excerpt (Excerpt dient als Meta-Description; siehe inc/ratgeber.php, das
//      is_singular('post') mit Excerpt abdeckt — gilt auch für Lexikon-Beiträge).
//      Rebsorten, Regionen, Verkostungsbegriffe und Histamin-Begriffe, sinnvoll
//      untereinander verlinkt.
//
// Cross-Links: Im Beitragstext werden Verweise auf andere Einträge als Token
// `%%slug%%` notiert. Nach dem Anlegen sind die echten Permalinks bekannt; ein
// zweiter Pass ersetzt die Token durch die tatsächlichen URLs und schreibt den
// aufgelösten Inhalt zurück. So sind die internen Links immer korrekt — unabhängig
// von der Permalink-Struktur — und der erneute Lauf bleibt gefahrlos (idempotent).
//
// Die Navigation liegt im Theme (inc/navigation.php, feinspitz_nav_items) und wird
// NICHT von hier verändert (der Header nutzt den Shortcode [feinspitz_nav], keine
// wp_navigation). Die Übersicht rendert templates/category-weinlexikon.html via
// [feinspitz_lexikon_index] (inc/lexikon.php).
//
//   node scripts/content/lexikon.mjs

import { wp, WP_BASE } from '../lib/wp.mjs';

// --- kleine Block-Markup-Helfer (spiegeln scripts/content/ratgeber.mjs) ------

const h2 = (t) => `<!-- wp:heading -->\n<h2 class="wp-block-heading">${t}</h2>\n<!-- /wp:heading -->`;
const p = (t) => `<!-- wp:paragraph -->\n<p>${t}</p>\n<!-- /wp:paragraph -->`;
const ul = (items) =>
  `<!-- wp:list -->\n<ul class="wp-block-list">` +
  items.map((i) => `<!-- wp:list-item -->\n<li>${i}</li>\n<!-- /wp:list-item -->`).join('') +
  `</ul>\n<!-- /wp:list -->`;
const blocks = (...parts) => parts.join('\n\n');

// Cross-Link auf einen anderen Lexikon-Eintrag (per Slug). Wird im 2. Pass durch
// den echten Permalink ersetzt. Label frei wählbar.
const lk = (slug, label) => `<a href="%%${slug}%%">${label}</a>`;

// --- Kategorie -------------------------------------------------------------

const CATEGORY = {
  slug: 'weinlexikon',
  name: 'Weinlexikon',
  description:
    'Das Feinspitz-Weinlexikon: Rebsorten, Weinregionen, Verkostungsbegriffe und Histamin-Wissen · kurz und klar erklärt.',
};

// --- Glossar-Einträge ------------------------------------------------------
//
// kind = fachliche Einordnung (nur zur Dokumentation/Sortierung im Skript; die
// Übersicht gruppiert alphabetisch). Titel bewusst als Nachschlagebegriff.

const ENTRIES = [
  // ---------- Rebsorten ----------
  {
    slug: 'gruener-veltliner',
    title: 'Grüner Veltliner',
    kind: 'Rebsorte',
    excerpt:
      'Grüner Veltliner ist die Leitrebsorte Österreichs: würzig-frischer Weisswein mit „Pfefferl", ideal zu Speisen und meist unkompliziert im Genuss.',
    content: blocks(
      p(
        `Der Grüne Veltliner ist die mit Abstand wichtigste Rebsorte Österreichs und prägt vor allem die Weinbaugebiete ${lk('niederoesterreich', 'Niederösterreichs')}. Sein Markenzeichen ist eine frische, würzige Art mit feiner Pfefferwürze – Kenner sprechen liebevoll vom „Pfefferl".`
      ),
      h2('Wie schmeckt Grüner Veltliner?'),
      p(
        'Im Glas zeigt er sich meist trocken, saftig und lebendig, mit Noten von grünem Apfel, Zitrus und weissem Pfeffer. Leichte Varianten sind wunderbar unkomplizierte Speisenbegleiter, während Lagenweine aus Top-Regionen erstaunliche Dichte und Reifepotenzial entwickeln.'
      ),
      h2('Wozu passt er?'),
      p(
        `Als vielseitiger ${lk('trocken-halbtrocken-suess', 'trockener')} Weisswein harmoniert der Grüne Veltliner mit Gemüsegerichten, hellem Fleisch, Fisch und der klassischen österreichischen Küche. Weine aus dem ${lk('traisental', 'Traisental')} gelten als besonders präzise und mineralisch.`
      )
    ),
  },
  {
    slug: 'zweigelt',
    title: 'Zweigelt',
    kind: 'Rebsorte',
    excerpt:
      'Zweigelt ist die meistangebaute Rotweinsorte Österreichs: samtig, fruchtig nach Kirsche, mit weichen Tanninen und angenehm zugänglichem Charakter.',
    content: blocks(
      p(
        `Der Zweigelt ist die meistangebaute rote Rebsorte Österreichs. Sie entstand 1922 als Kreuzung aus ${lk('blaufraenkisch', 'Blaufränkisch')} und ${lk('st-laurent', 'St. Laurent')} und vereint deren Stärken zu einem charmanten, zugänglichen Rotwein.`
      ),
      h2('Typischer Geschmack'),
      p(
        'Zweigelt duftet nach Weichsel und Sauerkirsche, ist samtig am Gaumen und trägt weiche, runde Tannine. Von der leichten, fröhlichen Variante bis zum kräftigen, im Holz gereiften Lagenwein reicht das Spektrum – gemeinsam ist ihnen eine saftige Fruchtigkeit.'
      ),
      h2('Genuss & Verträglichkeit'),
      p(
        `Weil kräftige Rotweine tendenziell mehr ${lk('histamin-im-wein', 'Histamin')} enthalten, lohnt bei Empfindlichkeit der Blick auf ${lk('histamingepruefte-weine', 'histamingeprüfte Weine')}. Zweigelt passt hervorragend zu Gegrilltem, Schmorgerichten und würzigem Käse.`
      )
    ),
  },
  {
    slug: 'blaufraenkisch',
    title: 'Blaufränkisch',
    kind: 'Rebsorte',
    excerpt:
      'Blaufränkisch ist eine kräftige, würzige österreichische Rotweinsorte mit dunkler Frucht, markanter Säure und ausgeprägtem Terroir-Charakter.',
    content: blocks(
      p(
        'Blaufränkisch zählt zu den edelsten roten Rebsorten Österreichs und bringt charaktervolle, lagerfähige Weine hervor. Die Sorte gilt als besonders terroirtreu: Sie spiegelt Boden und Herkunft klar im Glas wider.'
      ),
      h2('Charakter'),
      p(
        'Typisch sind Aromen von dunklen Beeren, Brombeere und Weichsel, dazu würzige, oft pfeffrige Noten und eine kräftige, tragende Säure. Junge Weine wirken saftig und frisch, gereifte entwickeln Tiefe, Struktur und feine Tannine.'
      ),
      h2('Verwandtschaft & Herkunft'),
      p(
        `Als Elternteil des ${lk('zweigelt', 'Zweigelt')} hat Blaufränkisch die österreichische Rotweinlandschaft geprägt. In der ${lk('weststeiermark-schilcher', 'Steiermark')} und im Burgenland findet die Sorte ideale Bedingungen. Zu kräftigen Fleischgerichten ist sie ein verlässlicher Begleiter.`
      )
    ),
  },
  {
    slug: 'riesling',
    title: 'Riesling',
    kind: 'Rebsorte',
    excerpt:
      'Riesling gilt als König der Weissweine: filigran, mineralisch und langlebig, mit rassiger Säure und Aromen von Pfirsich, Zitrus und reifem Apfel.',
    content: blocks(
      p(
        'Riesling gilt vielen als die edelste weisse Rebsorte überhaupt. Er verbindet Finesse mit Ausdruckskraft und bringt sowohl knochentrockene als auch feinsüsse Weine von grosser Langlebigkeit hervor.'
      ),
      h2('Aroma & Stil'),
      p(
        `Charakteristisch sind Aromen von Pfirsich, Aprikose, Zitrus und reifem Apfel, getragen von einer rassigen, belebenden Säure und einer klaren Mineralität. Meist wird Riesling ${lk('trocken-halbtrocken-suess', 'trocken')} ausgebaut; als ${lk('praedikatswein', 'Prädikatswein')} zeigt er jedoch auch grandiose edelsüsse Varianten.`
      ),
      h2('Herkunft'),
      p(
        `In den steilen, mineralischen Lagen ${lk('niederoesterreich', 'Niederösterreichs')} findet Riesling ideale Bedingungen. Als frischer Weisswein weist er tendenziell niedrigere ${lk('histamin-im-wein', 'Histaminwerte')} auf als kräftige Rotweine.`
      )
    ),
  },
  {
    slug: 'st-laurent',
    title: 'St. Laurent',
    kind: 'Rebsorte',
    excerpt:
      'St. Laurent ist eine samtige, burgundisch anmutende Rotweinsorte mit dunkler Kirschfrucht, feiner Würze und eleganter, seidiger Textur.',
    content: blocks(
      p(
        'St. Laurent (auch Sankt Laurent) ist eine anspruchsvolle, aber lohnende rote Rebsorte mit burgundischem Charme. Sie bringt elegante, samtige Weine hervor, die feiner und zurückhaltender wirken als so mancher kräftige Rote.'
      ),
      h2('Geschmacksbild'),
      p(
        'Im Glas dominieren dunkle Kirsche, Weichsel und eine dezente Würze, begleitet von einer seidigen Textur und weichen Tanninen. Gut gereift entwickelt St. Laurent samtige Tiefe und Finesse.'
      ),
      h2('Bedeutung'),
      p(
        `Gemeinsam mit ${lk('blaufraenkisch', 'Blaufränkisch')} ist St. Laurent ein Elternteil des ${lk('zweigelt', 'Zweigelt')} und damit fest in der österreichischen Weingeschichte verankert. Die elegante Frucht macht ihn zum vielseitigen Speisenbegleiter.`
      )
    ),
  },
  {
    slug: 'weissburgunder',
    title: 'Weissburgunder',
    kind: 'Rebsorte',
    excerpt:
      'Weissburgunder (Pinot Blanc) ist ein feiner, dezent-cremiger Weisswein mit milder Säure, Aromen von Apfel und Nuss und angenehm zurückhaltendem Stil.',
    content: blocks(
      p(
        'Der Weissburgunder – international Pinot Blanc – ist ein feiner, eleganter Weisswein für alle, die es harmonisch und unaufdringlich mögen. Seine dezente Art macht ihn zu einem der wandlungsfähigsten Speisenbegleiter.'
      ),
      h2('Wie schmeckt Weissburgunder?'),
      p(
        'Typisch sind zurückhaltende Aromen von reifem Apfel, Birne, feiner Nuss und einem Hauch Brioche. Die milde, gut eingebundene Säure und eine cremige Textur sorgen für ein rundes, angenehmes Mundgefühl.'
      ),
      h2('Genuss'),
      p(
        `Ob als leichter Aperitif oder zu Fisch, Geflügel und cremigen Gerichten – Weissburgunder passt fast immer. Als frischer ${lk('trocken-halbtrocken-suess', 'trockener')} Weisswein ist er oft eine gute Wahl, wenn man auf ${lk('histamin-im-wein', 'Histamin')} achtet.`
      )
    ),
  },

  // ---------- Regionen ----------
  {
    slug: 'traisental',
    title: 'Traisental',
    kind: 'Region',
    excerpt:
      'Das Traisental ist Österreichs kleinstes Weinbaugebiet: kalkgeprägte Böden bringen präzise, mineralische Grüne Veltliner und Rieslinge hervor.',
    content: blocks(
      p(
        `Das Traisental in ${lk('niederoesterreich', 'Niederösterreich')} ist eines der kleinsten und jüngsten eigenständigen Weinbaugebiete Österreichs – und zugleich eines der spannendsten für Weissweinfreunde. Namensgeber ist der Fluss Traisen, der das Tal durchzieht.`
      ),
      h2('Was das Traisental besonders macht'),
      p(
        `Prägend sind die kalk- und schotterreichen Böden. Sie verleihen den Weinen eine unverwechselbare Mineralität und Präzision. Vor allem ${lk('gruener-veltliner', 'Grüner Veltliner')} und ${lk('riesling', 'Riesling')} zeigen hier eine klare, geradlinige Stilistik.`
      ),
      h2('Typische Weine'),
      p(
        'Traisentaler Weissweine gelten als straff, würzig und langlebig. Sie verbinden Frische mit Tiefe und sind ein Musterbeispiel dafür, wie stark der Boden den Charakter eines Weins prägen kann.'
      )
    ),
  },
  {
    slug: 'weststeiermark-schilcher',
    title: 'Weststeiermark & Schilcher',
    kind: 'Region',
    excerpt:
      'Die Weststeiermark ist die Heimat des Schilchers: ein rassig-frischer Roséwein aus der Rebsorte Blauer Wildbacher mit markanter Säure und Beerenaroma.',
    content: blocks(
      p(
        'Die Weststeiermark ist ein kleines, aber unverwechselbares Weinbaugebiet in der Steiermark – berühmt für eine Spezialität, die es so nur hier gibt: den Schilcher.'
      ),
      h2('Was ist Schilcher?'),
      p(
        'Schilcher ist ein rassiger Roséwein aus der autochthonen Rebsorte Blauer Wildbacher. Charakteristisch sind seine kräftige, belebende Säure, die leuchtend zwiebelschalen- bis lachsfarbene Tönung und Aromen von Ribisel (Johannisbeere), Erdbeere und Rhabarber.'
      ),
      h2('Genuss'),
      p(
        `Der Schilcher ist ein erfrischender, unkomplizierter Wein für warme Tage und die gehobene Jause. Seine markante Frische unterscheidet ihn deutlich von den weicheren Rotweinen aus ${lk('blaufraenkisch', 'Blaufränkisch')}, die ebenfalls in der Steiermark gedeihen.`
      )
    ),
  },
  {
    slug: 'niederoesterreich',
    title: 'Niederösterreich',
    kind: 'Region',
    excerpt:
      'Niederösterreich ist das grösste Weinbau-Bundesland Österreichs – Heimat von Grünem Veltliner und Riesling und zahlreicher renommierter Weinbaugebiete.',
    content: blocks(
      p(
        'Niederösterreich ist das grösste und vielfältigste Weinbau-Bundesland Österreichs. Hier wächst der Löwenanteil des österreichischen Weins – von frischen, alltagstauglichen Weissweinen bis zu grossen, lagerfähigen Lagenweinen.'
      ),
      h2('Leitsorten'),
      p(
        `Die weisse Hauptrolle spielen ${lk('gruener-veltliner', 'Grüner Veltliner')} und ${lk('riesling', 'Riesling')}, die auf den unterschiedlichen Böden jeweils eigenständige Ausprägungen finden. Daneben sind auch feine Rotweine und Burgundersorten wie der ${lk('weissburgunder', 'Weissburgunder')} zu Hause.`
      ),
      h2('Weinbaugebiete'),
      p(
        `Zu Niederösterreich zählen renommierte Gebiete – darunter das kalkgeprägte ${lk('traisental', 'Traisental')}. Klima und Böden reichen von kühl-mineralisch bis warm und kräftig, was die grosse stilistische Bandbreite erklärt.`
      )
    ),
  },

  // ---------- Verkostungsbegriffe ----------
  {
    slug: 'trocken-halbtrocken-suess',
    title: 'Trocken, halbtrocken, süss',
    kind: 'Verkostung',
    excerpt:
      'Trocken, halbtrocken, lieblich oder süss: Was die Geschmacksangaben beim Wein bedeuten und wie sich der Restzuckergehalt auf dem Etikett widerspiegelt.',
    content: blocks(
      p(
        'Die Angaben trocken, halbtrocken, lieblich und süss beschreiben, wie viel unvergorener Zucker – der sogenannte Restzucker – im Wein verblieben ist. Sie sind gesetzlich geregelt und die wichtigste Orientierung für den Geschmack.'
      ),
      h2('Die Geschmacksstufen'),
      ul([
        '<strong>Trocken:</strong> nahezu kein spürbarer Zucker (bis ca. 4 g/l, bzw. bis 9 g/l bei entsprechend hoher Säure). Der Wein schmeckt herb und frisch.',
        '<strong>Halbtrocken:</strong> eine dezent wahrnehmbare Süsse (bis ca. 12 g/l), oft weich und zugänglich.',
        '<strong>Lieblich:</strong> deutlich süss (bis ca. 45 g/l).',
        '<strong>Süss:</strong> ausgeprägt süss (über 45 g/l) – typisch für edelsüsse Spezialitäten.',
      ]),
      h2('Süsse ist Geschmackssache'),
      p(
        `Entscheidend ist das Zusammenspiel von Zucker und Säure: Ein Wein mit viel Säure wirkt auch mit etwas Restzucker frisch. Die höchsten Süssegrade erreichen edelsüsse Weine wie ${lk('trockenbeerenauslese', 'Trockenbeerenauslese')} und ${lk('eiswein', 'Eiswein')}, die als ${lk('praedikatswein', 'Prädikatsweine')} eingestuft werden.`
      )
    ),
  },
  {
    slug: 'praedikatswein',
    title: 'Prädikatswein',
    kind: 'Verkostung',
    excerpt:
      'Prädikatswein ist die höchste Qualitätsstufe: Die Prädikate von Spätlese bis Trockenbeerenauslese richten sich nach dem Reifegrad und Zuckergehalt der Trauben.',
    content: blocks(
      p(
        'Prädikatswein ist die höchste Qualitätsstufe im deutschen und österreichischen Weinrecht. Das jeweilige „Prädikat" richtet sich danach, wie reif und zuckerreich die Trauben bei der Ernte waren – gemessen in Grad Öchsle bzw. Grad KMW.'
      ),
      h2('Die Prädikatsstufen'),
      p(
        'Mit steigendem Reifegrad der Trauben steigt das Prädikat – von der Spätlese über die Auslese bis zu den edelsüssen Spitzen:'
      ),
      ul([
        '<strong>Spätlese</strong> – aus vollreifen, spät gelesenen Trauben.',
        '<strong>Auslese</strong> – aus besonders reifen, handverlesenen Trauben.',
        `<strong>Beerenauslese</strong> und <strong>${lk('trockenbeerenauslese', 'Trockenbeerenauslese')}</strong> – aus edelfaulen, hochkonzentrierten Beeren.`,
        `<strong>${lk('eiswein', 'Eiswein')}</strong> – aus gefroren geernteten Trauben.`,
      ]),
      h2('Was das Prädikat aussagt'),
      p(
        'Ein Prädikat sagt etwas über die Reife und Konzentration aus – nicht zwingend über die Süsse. Auch trockene Weine können Prädikate tragen. Die höheren Stufen sind jedoch meist die grossen, edelsüssen Dessertweine.'
      )
    ),
  },
  {
    slug: 'trockenbeerenauslese',
    title: 'Trockenbeerenauslese',
    kind: 'Verkostung',
    excerpt:
      'Die Trockenbeerenauslese (TBA) ist ein seltener, edelsüsser Wein aus rosinenartig eingetrockneten, edelfaulen Beeren – hochkonzentriert und langlebig.',
    content: blocks(
      p(
        `Die Trockenbeerenauslese, kurz TBA, gehört zu den seltensten und kostbarsten Weinen überhaupt. Sie ist die höchste Stufe der ${lk('praedikatswein', 'Prädikatsweine')} und ein Sinnbild für edelsüssen Genuss.`
      ),
      h2('Wie sie entsteht'),
      p(
        'Grundlage sind Beeren, die durch die Edelfäule (Botrytis cinerea) am Stock rosinenartig eintrocknen. Dabei verdunstet Wasser, während Zucker, Säure und Aromen extrem konzentriert werden. Das Auslesen dieser einzelnen Beeren ist reine Handarbeit und äusserst aufwendig.'
      ),
      h2('Geschmack & Genuss'),
      p(
        `Eine TBA ist dicht, honigsüss und vielschichtig, mit Aromen von Dörrobst, Honig und kandierten Früchten – getragen von einer lebendigen Säure, die die Süsse balanciert. Sie ist ein Dessertwein für besondere Momente und verwandt mit dem ebenfalls edelsüssen ${lk('eiswein', 'Eiswein')}.`
      )
    ),
  },
  {
    slug: 'eiswein',
    title: 'Eiswein',
    kind: 'Verkostung',
    excerpt:
      'Eiswein wird aus gefroren geernteten Trauben gekeltert: Nur der konzentrierte Saft wird gepresst – ein seltener, frisch-süsser Wein mit rassiger Säure.',
    content: blocks(
      p(
        `Eiswein ist eine edelsüsse Rarität, die viel Geduld und das richtige Wetter verlangt. Als ${lk('praedikatswein', 'Prädikatswein')} zählt er zu den Spitzen der süssen Weine.`
      ),
      h2('Wie Eiswein entsteht'),
      p(
        'Die Trauben bleiben bis tief in den Winter am Rebstock hängen und werden erst bei mindestens –7 °C gefroren geerntet und gepresst. Weil das Wasser in den Beeren gefroren ist, fliesst nur der hochkonzentrierte, zucker- und säurereiche Saft ab.'
      ),
      h2('Geschmack'),
      p(
        `Eiswein besticht durch eine glasklare, fast rassige Frische, die seine intensive Süsse leicht und lebendig wirken lässt – Aromen von Zitrus, Ananas und reifem Apfel inklusive. Im Vergleich zur ${lk('trockenbeerenauslese', 'Trockenbeerenauslese')} wirkt er filigraner und frischer.`
      )
    ),
  },

  // ---------- Histamin ----------
  {
    slug: 'histamin-im-wein',
    title: 'Histamin im Wein',
    kind: 'Histamin',
    excerpt:
      'Warum Wein Histamin enthält, wie es bei der Gärung entsteht und weshalb Rotweine tendenziell höhere Werte aufweisen als Weiss- und Roséweine.',
    content: blocks(
      p(
        `Histamin ist ein natürlicher Stoff, der in vielen gereiften und vergorenen Lebensmitteln vorkommt – auch im Wein. Es zählt zu den ${lk('biogene-amine', 'biogenen Aminen')} und entsteht als Nebenprodukt der Gärung.`
      ),
      h2('Wie kommt Histamin in den Wein?'),
      p(
        'Während der Gärung und besonders beim biologischen Säureabbau (der malolaktischen Gärung) wandeln Mikroorganismen Aminosäuren in biogene Amine um – darunter Histamin. Wie viel entsteht, hängt von Rebsorte, Ausbau und der Sorgfalt im Keller ab.'
      ),
      h2('Worauf man achten kann'),
      p(
        `Als Faustregel weisen ${lk('riesling', 'Weissweine')} und Roséweine tendenziell niedrigere Werte auf als kräftige Rotweine wie ${lk('zweigelt', 'Zweigelt')}, weil sie seltener einen ausgeprägten Säureabbau durchlaufen. Einen komplett histaminfreien Wein gibt es allerdings nicht. Echte Sicherheit geben ${lk('histamingepruefte-weine', 'histamingeprüfte Weine')}, deren Gehalt im Labor bestimmt wurde.`
      )
    ),
  },
  {
    slug: 'histamingepruefte-weine',
    title: 'Histamingeprüfte Weine',
    kind: 'Histamin',
    excerpt:
      'Histamingeprüfte Weine werden im Labor auf ihren Histamingehalt untersucht – so ist der Wert vor dem Kauf bekannt. Was das bedeutet und was nicht.',
    content: blocks(
      p(
        `Histamingeprüfte Weine sind das Kernthema von Feinspitz. Anders als beim herkömmlichen Wein ist ihr ${lk('histamin-im-wein', 'Histamingehalt')} bekannt, weil er im Labor gemessen wurde – Sie wissen also schon vor dem Kauf, woran Sie sind.`
      ),
      h2('Was „geprüft" bedeutet'),
      p(
        'Der Histamingehalt lässt sich einem Wein nicht ansehen und steht auch nicht auf dem Etikett. Eine Laboranalyse schafft Transparenz: Statt auf Vermutungen zu vertrauen, treffen Sie eine informierte Entscheidung. Das ist besonders wertvoll für alle, die bewusst auf Verträglichkeit achten.'
      ),
      h2('Geprüft ist nicht gleich histaminfrei'),
      p(
        `Wichtig: „Histamingeprüft" heisst, dass der Gehalt bekannt und niedrig ausgewiesen ist – nicht, dass gar kein Histamin enthalten ist. Geringe Mengen entstehen bei der Gärung immer (siehe ${lk('biogene-amine', 'Biogene Amine')}). Bei diagnostizierter Histaminintoleranz halten Sie im Zweifel bitte ärztliche Rücksprache.`
      )
    ),
  },
  {
    slug: 'biogene-amine',
    title: 'Biogene Amine',
    kind: 'Histamin',
    excerpt:
      'Biogene Amine wie Histamin und Tyramin entstehen beim Gären und Reifen. Was sie sind, wie sie in den Wein gelangen und warum manche Menschen empfindlich reagieren.',
    content: blocks(
      p(
        'Biogene Amine sind natürliche Stoffe, die entstehen, wenn Mikroorganismen Aminosäuren umwandeln. Sie kommen in vielen fermentierten und gereiften Lebensmitteln vor – etwa in Käse, Rohwurst, Sauerkraut und eben Wein.'
      ),
      h2('Die wichtigsten Vertreter'),
      p(
        `Zu den bekanntesten biogenen Aminen im Wein zählen ${lk('histamin-im-wein', 'Histamin')}, Tyramin und Putrescin. Sie bilden sich vor allem während der Gärung und beim biologischen Säureabbau. Sauberes, hygienisches Arbeiten im Keller hält ihren Gehalt niedrig.`
      ),
      h2('Warum sie relevant sind'),
      p(
        `Manche Menschen können biogene Amine – insbesondere Histamin – schlechter abbauen und reagieren empfindlich darauf. Für sie ist Transparenz entscheidend: ${lk('histamingepruefte-weine', 'Histamingeprüfte Weine')} machen den Gehalt sichtbar und den Genuss dadurch planbarer.`
      )
    ),
  },
];

// --- REST-Helfer ------------------------------------------------------------

/** Kategorie „Weinlexikon" sicherstellen (idempotent per Slug). Gibt sie zurück. */
async function ensureCategory() {
  const existing = await wp('/wp/v2/categories', { query: { slug: CATEGORY.slug, per_page: 1 } });
  if (Array.isArray(existing) && existing.length) {
    console.log(`= Kategorie „${CATEGORY.name}" existiert (ID ${existing[0].id}).`);
    return existing[0];
  }
  const created = await wp('/wp/v2/categories', {
    method: 'POST',
    body: { name: CATEGORY.name, slug: CATEGORY.slug, description: CATEGORY.description },
  });
  console.log(`✓ Kategorie „${CATEGORY.name}" angelegt (ID ${created.id}).`);
  return created;
}

/** Beitrag per Slug finden (Status any, edit-Kontext für rohe Felder). */
async function findPostBySlug(slug) {
  const list = await wp('/wp/v2/posts', {
    query: { slug, status: 'publish,draft,pending,future,private', per_page: 1, context: 'edit' },
  });
  return Array.isArray(list) && list.length ? list[0] : null;
}

/** Beitrag anlegen/aktualisieren (idempotent per Slug). Gibt {id, link} zurück. */
async function upsertPost(entry, categoryId, content) {
  const body = {
    slug: entry.slug,
    title: entry.title,
    content,
    excerpt: entry.excerpt,
    status: 'publish',
    categories: [categoryId],
    comment_status: 'closed',
  };
  const existing = await findPostBySlug(entry.slug);
  if (existing) {
    const updated = await wp(`/wp/v2/posts/${existing.id}`, { method: 'POST', body });
    return { id: existing.id, link: updated.link || existing.link };
  }
  const created = await wp('/wp/v2/posts', { method: 'POST', body });
  return { id: created.id, link: created.link };
}

/** Token %%slug%% im Inhalt durch die echten Permalinks ersetzen. */
function resolveLinks(content, linkMap) {
  return content.replace(/%%([a-z0-9-]+)%%/g, (m, slug) => {
    const url = linkMap.get(slug);
    if (!url) {
      console.warn(`  ⚠ Kein Permalink für Cross-Link „${slug}" — Token bleibt.`);
      return m;
    }
    return url;
  });
}

// --- Ablauf -----------------------------------------------------------------

async function main() {
  console.log(`→ Ziel: ${WP_BASE}\n`);

  const category = await ensureCategory();

  // Pass 1: alle Beiträge mit (noch Token-behaftetem) Inhalt anlegen/aktualisieren.
  // So sind anschliessend die echten Permalinks aller Einträge bekannt.
  console.log(`\n[Pass 1] ${ENTRIES.length} Einträge anlegen/aktualisieren …`);
  const linkMap = new Map();
  const idMap = new Map();
  for (const entry of ENTRIES) {
    const { id, link } = await upsertPost(entry, category.id, entry.content);
    linkMap.set(entry.slug, link);
    idMap.set(entry.slug, id);
    console.log(`  ✓ „${entry.title}" (ID ${id}) → ${link}`);
  }

  // Pass 2: Cross-Link-Token gegen echte Permalinks auflösen und zurückschreiben.
  console.log(`\n[Pass 2] Cross-Links auflösen …`);
  let linked = 0;
  for (const entry of ENTRIES) {
    if (!entry.content.includes('%%')) continue;
    const resolved = resolveLinks(entry.content, linkMap);
    await wp(`/wp/v2/posts/${idMap.get(entry.slug)}`, { method: 'POST', body: { content: resolved } });
    linked++;
  }
  console.log(`  ✓ ${linked} Beiträge mit aufgelösten internen Links aktualisiert.`);

  const overviewUrl = category.link || `${WP_BASE}/category/${CATEGORY.slug}/`;

  console.log('\n═══ Zusammenfassung ═══');
  console.log(`Kategorie „${CATEGORY.name}": ID ${category.id} (Slug ${CATEGORY.slug})`);
  console.log(`Einträge:                 ${ENTRIES.length}`);
  console.log(`Übersicht-URL:            ${overviewUrl}`);
  console.log('\n✓ Fertig. Erneutes Ausführen ist gefahrlos (idempotent).');
  console.log('Hinweis: Die Übersicht (A–Z, Karten) rendert erst, wenn das Theme mit');
  console.log('inc/lexikon.php + templates/category-weinlexikon.html aktiv ist.');
}

main().catch((err) => {
  console.error('\n✗ Fehler:', err.message);
  if (err.data) console.error(err.data);
  process.exit(1);
});
