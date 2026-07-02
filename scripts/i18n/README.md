# i18n / Mehrsprachigkeit (DE/EN) — feature/i18n-multilingual

Textdomain: **`feinspitz`**. Sprachen: **Deutsch (Standard)** + **Englisch** (`/en/`-Präfix),
verwaltet über **Polylang** (frei, v3.8.5 auf dem Server). Zugriff HTTP-only.

## Dateien dieses Branches

| Datei | Zweck |
|---|---|
| `scripts/i18n/make-pot.mjs` | Scannt `theme/feinspitz/**/*.php` nach WP-Gettext-Aufrufen und erzeugt `languages/feinspitz.pot` (via `gettext-parser`, **ohne** WP-CLI). |
| `scripts/i18n/translations.en.json` | DE→EN-Tabelle für alle Theme-UI-Strings. |
| `scripts/i18n/make-po.mjs` | Baut `feinspitz-de_DE.po/.mo` (Quelltext) und `feinspitz-en_US.po/.mo` (EN) und kompiliert die `.mo`. |
| `scripts/i18n/pages.en.json` | EN-Übersetzungen der 4 statischen Seiten (Über uns, Kontakt, AGB, Impressum). |
| `scripts/i18n/polylang-content.mjs` | Ordnet Inhalten DE zu und legt/verknüpft die EN-Seiten an (idempotent, Dry-Run-Default). |
| `theme/feinspitz/inc/i18n.php` | Sprachumschalter-Shortcode `[pll_languages]` + gescopte CSS **und** die REST-Brücke für Polylang (Felder `lang`/`pll_translations`). |
| `theme/feinspitz/parts/header.html`, `footer.html` | Einbindung des Sprachumschalters. |

## npm-Skripte

```bash
npm run i18n            # POT neu scannen + de_DE/en_US .po/.mo neu bauen
npm run i18n:pot        # nur POT
npm run i18n:po         # nur .po/.mo
```

Nach jeder Änderung an übersetzbaren Theme-Strings `npm run i18n` laufen lassen und
neue Strings in `translations.en.json` ergänzen (make-po bricht ab, wenn EN fehlt).

## Theme-Übersetzungen (.pot/.po/.mo)

- Deutsch ist die **Quellsprache** → `feinspitz-de_DE` enthält `msgstr = msgid`.
- `load_theme_textdomain('feinspitz', .../languages)` (in `functions.php`, Phase 0)
  lädt automatisch `feinspitz-{locale}.mo`. Polylang setzt das Locale je Sprache
  (`de_DE` bzw. `en_US`), sodass auf `/en/` die englischen Strings greifen.

## Sprachumschalter

`[pll_languages]` (registriert in `inc/i18n.php`) rendert einen kompakten DE/EN-
Umschalter über `pll_the_languages()`; ohne Polylang bleibt er leer (kein Fehler).
Eingebunden per `core/shortcode`-Block in Header (rechts neben der Navigation) und
Footer (zentriert). Alternative: der native Block
`<!-- wp:polylang/language-switcher /-->`.

## Content-Verknüpfung (Runtime, koordiniert)

Freies Polylang bietet **keinen** REST-Weg, um Beiträgen/Seiten eine Sprache
zuzuweisen oder Übersetzungen zu verknüpfen (nur Polylang Pro). Deshalb stellt
`inc/i18n.php` eine **REST-Brücke** bereit: die Felder `lang` und `pll_translations`
auf `post`/`page`/`product`. Sie ist nur aktiv, wenn dieses Theme auf dem Server
läuft.

**Ablauf (in Phase 2, koordiniert mit dem Primary — geteilte Runtime):**

1. **Theme deployen** (bringt die REST-Brücke live):
   ```bash
   npm run theme:deploy
   ```
2. **Diagnose (Dry-Run, nur lesend):**
   ```bash
   node scripts/i18n/polylang-content.mjs
   ```
   Muss „✓ REST-Brücke aktiv" zeigen.
3. **Schreiben:**
   ```bash
   node scripts/i18n/polylang-content.mjs --write
   ```
   Das Skript ist idempotent: es
   - ordnet allen sprachlosen Seiten DE zu,
   - legt die 4 EN-Seiten an (`pages.en.json`) und verknüpft sie mit den DE-Originalen,
     sodass `/en/ueber-uns/`, `/en/kontakt/`, `/en/agb/`, `/en/impressum/` funktionieren.

**Aktueller Server-Stand (Diagnose 2026-07-02):** Sprachen DE (default)+EN vorhanden;
alle 11 Seiten sind noch **sprachlos** (Polylang zeigt sprachlose Inhalte in jeder
Sprache) → DE-Zuordnung + EN-Seiten stehen aus, bis das i18n-Theme deployt ist.

## Offene Folgeaufgaben (NICHT Ziel dieser Phase)

- **Volle Produktübersetzung (171 Produkte):** DE-Zuordnung + EN-Übersetzung aller
  WooCommerce-Produkte. Umsetzung analog zu den Seiten über die REST-Brücke, jedoch
  auf dem `product`-Endpoint (`wp/v2/product` bzw. wc/v3). Erfordert redaktionelle
  EN-Produkttexte (Name/Beschreibung/Kategorien/Tags). Empfehlung: eigener Branch
  `feature/i18n-products` + Übersetzungs-Datenquelle. Alternativ Bulk-DE-Zuordnung
  über wp-admin → Sprachen → „Inhalte ohne Sprache der Standardsprache zuweisen".
- **Kategorien/Tags:** Slugs/Namen der Produktkategorien und Flag-Tags
  (histamingeprüft/vegan/alkoholfrei) EN-seitig übersetzen und verknüpfen.
- **Hauptnavigation:** Die FSE-Navigation ist ein `wp_navigation`-Post. Für
  sprachspezifische Menüs wird pro Sprache ein eigenes `wp_navigation` gepflegt und
  im `wp:navigation`-Block je Sprache referenziert (Polylang steuert die Ausgabe).
  Sobald die Hauptnavigation redaktionell befüllt ist, EN-Pendant anlegen.
- **WooCommerce-Strings** (Cart/Checkout/Buttons): kommen aus WooCommerce selbst und
  werden über dessen eigene Sprachpakete (de_DE/en_US) übersetzt — nicht über dieses
  Theme-POT.
