# Feinspitz — Feinschliff & Ausbau (Design-Spec)

**Datum:** 2026-07-02
**Status:** Freigegeben (Design) — Runde 2 (Refinement) auf dem bestehenden Live-Build.

## Ziel

Der funktionierende, zweisprachige WooCommerce-Rebuild wird gestalterisch verfeinert,
enduser-freundlicher gemacht und um SEO-wirksamen Inhalt erweitert.

### Entscheidungen (bestätigt)

| Thema | Entscheidung |
|---|---|
| Logo | Neue, hochwertige **Bold-Wortmarke als SVG** (Fraunces, Wine/Gold); als WP-Custom-Logo |
| SEO-Inhalt | **Ratgeber-/FAQ-Bereich** (kein klassischer Blog) inkl. **FAQ-JSON-LD** (Rich Snippets) |
| Persönliche Note | Aus **echter Story** getextet: Familienweingut Steyrer (Traisental), Mission histaminarme Weine, Slogan „Histamingeprüfte Weine mit mehr Vertrauen und Genuss erleben – für mehr Lebensqualität!" |
| Bilder | **Originalmotive** von feinspitz.ch (Weinberg/Wein/Lifestyle) für Hero/Sektionen; Produktbilder vereinheitlichen |
| Header/Footer | Deutlich **verschlanken** (kompakter, sticky Header; niedrigerer, aufgeräumter Footer) |
| Shop | **Aufräumen**: einheitliche Karten, ausgerichtete Titel/Preise/Buttons, Text-Fit, tidy Filter |

## Umfang & Nicht-Ziele

- **Nicht-Ziele (YAGNI):** kein klassischer Blog, kein schweres SEO-Plugin (FAQ-Schema +
  saubere Semantik genügen), keine neuen Sprachen, kein Payment-Gateway.

## Zentrale Vorbereitung (main, zuerst)

- `theme/feinspitz/assets/images/` anlegen: ausgewählte Original-Bildmotive herunterladen,
  optimieren, committen (Eigentum des Betreibers, vom Nutzer freigegeben).
- `theme/feinspitz/assets/logo.svg`: neue Bold-Wortmarke.
- Logo als WordPress-**Custom-Logo** setzen (Server, via REST/Options) → `core/site-logo`
  zieht es überall. `add_theme_support('custom-logo')` in functions.php (falls nötig).

## Parallele Branches (DevSwarm, fester Dateibesitz)

### `refine/branding-header-footer`
- Neues Logo in Header/Footer (`core/site-logo`).
- **Header verschlanken:** kompakte Höhe/Padding, sticky, Logo + Navigation + Warenkorb-Icon
  + kompakter Sprachumschalter, mobiler Toggle.
- **Footer verschlanken:** niedriger, mehrspaltig aber ruhig; Kontakt/Links/Sprache.
- Dateibesitz: `parts/header.html`, `parts/footer.html`, `inc/branding.php`, `assets/logo.svg`.

### `refine/shop`
- Einheitliche Produktkarten (feste Bild-Ratio, gleiche Höhen), Titel mit Zeilen-Clamp
  (**Text-Überläufe fix**), ausgerichtete Preise/Buttons; aufgeräumte Filterleiste;
  Ergebnis-Anzahl; Breadcrumbs; Mobile.
- Dateibesitz: `templates/archive-product.html`, `templates/taxonomy-product_cat.html`,
  `patterns/shop-*.php`, `inc/shop.php`.

### `feature/ratgeber-faq`
- **Ratgeber:** Übersicht + Artikel-Template + 3–5 SEO-Artikel (histaminarm geniessen,
  Histamin & Wein, Weinwissen/Genuss). Umsetzung als WordPress-Beiträge (Kategorie „Ratgeber")
  oder CPT; Inhalte via REST anlegen (idempotent, Skript in `scripts/content/`).
- **FAQ:** Seite mit Akkordeon (Details/Summary-Blöcke) + **FAQ-JSON-LD-Schema** (`inc/ratgeber.php`).
- Navigationseinträge (Ratgeber, FAQ).
- Dateibesitz: `templates/*` für Ratgeber (z. B. `home.html`/`category-ratgeber` bzw. CPT-Templates),
  `patterns/ratgeber-*.php`, `patterns/faq-*.php`, `inc/ratgeber.php`, `scripts/content/*`.

### `refine/homepage-story`
- **Hero mit echtem Bild** (Cover-Block, Originalmotiv).
- **Persönliche Story-Sektion:** Familie Steyrer, Traisental, Gründungs-Mission, persönliches
  **Zitat + Signatur**; warmer, authentischer Ton.
- Sektionsbilder; **Text-Fit** auf der Startseite; klare CTAs.
- Dateibesitz: `templates/front-page.html`, `patterns/home-*.php`, `patterns/page-about.php`,
  `inc/homepage.php`.

## Querschnitt (in jedem Branch im eigenen Dateibereich)

- **Enduser-Freundlichkeit:** klare CTAs, gute Lesbarkeit/Kontrast, Mobile-first, sichtbare
  Navigation, Trust-Signale.
- **Text-Fit:** Zeilen-Clamp/`min-height`/fluide Schriftgrößen gegen Überläufe.
- **Bilder/Logo:** aus dem zentralen Asset-Pool referenzieren (`get_template_directory_uri`).

## QA & Deploy

- Pro Branch lokal als Code entwickeln (kein Selbst-Deploy); Primary koordiniert Deploys.
- Integrierter Deploy + QA-Sweep (DE/EN, Mobile, Text-Fit, Shop-Karten, FAQ-Rich-Snippet-Test,
  Ratgeber-Artikel, Story-Sektion, Header/Footer-Höhe).
