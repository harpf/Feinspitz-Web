# Feinspitz — Feature-Ausbau (Design-Spec)

**Datum:** 2026-07-02
**Status:** Freigegeben — Runde 4 (Features).

## Ziel

Die Seite attraktiver & verkaufsstärker machen: Anfrage-Formulare, bessere
Produktbeschreibungen + Fakten, ein Weinlexikon und ein Wein-Finder mit Filtern.

## Entscheidungen (bestätigt)

- Alle vier Bausteine umsetzen. Formulare via Plugin, **Fallback auf Theme-Formular** ok.
- **Fakten-Tabelle** auf der Produktseite: ja.
- Reihenfolge: **Produktdaten zuerst** (liefern Attribute), Formulare & Weinlexikon
  parallel, Wein-Finder/Filter danach.

## Bausteine

### ① Produktbeschreibungen + Fakten (`feature/product-descriptions`) — zuerst
- Aus den vorhandenen Rohtexten je Produkt ableiten: schöne, einheitliche
  **Kurzbeschreibung** (1–2 Sätze) und strukturierte **WooCommerce-Attribute**
  (Weingut, Rebsorte, Region, Jahrgang, Süße, Volumen), wo aus dem Text erkennbar.
- Reproduzierbar & idempotent: `scripts/content/product-enrich.mjs` (Parsing der
  Rohdaten → `short_description` + `attributes` via wc/v3).
- **Fakten-Tabelle** auf der Einzelproduktseite (Theme): Shortcode
  `[feinspitz_product_facts]` in `inc/product-facts.php`, eingebunden in
  `templates/single-product.html`; zeigt die Attribute als aufgeräumte Tabelle.
- Dateibesitz: `scripts/content/product-enrich.mjs`, `inc/product-facts.php`,
  `patterns/product-facts.php` (falls nötig), Ergänzung in `single-product.html`.

### ② Anfrage-Formulare (`feature/anfrage-formulare`) — parallel
- **Erster Schritt:** E-Mail-Versand des Servers testen (`wp_mail`); falls kein
  Versand → SMTP-Bedarf dokumentieren.
- Schlankes Formular-Plugin via REST installieren; **3 Formulare**: Kontakt,
  Weinprobe-Buchung (Name/E-Mail/Telefon/Datum/Personen/Nachricht),
  Catering-Anfrage (Name/E-Mail/Telefon/Event/Datum/Gäste/Nachricht) → E-Mail an
  `info@feinspitz.ch`. Im Theme-Stil eingebunden (Kontakt-, Weinproben-, Catering-Seite).
- **Fallback:** Lässt sich das Plugin nicht sauber skripten, ein schlankes,
  integriertes Theme-Formular (`inc/forms.php`, Absenden via admin-ajax + `wp_mail`,
  Spam-Honeypot). Gleiche Funktion.
- Dateibesitz: `inc/forms.php` (Fallback) bzw. Plugin-Setup-Skript in `scripts/`,
  Formular-Einbindung in die Kontakt-/Weinproben-/Catering-Seiten/Patterns.

### ③ Weinlexikon (`feature/weinlexikon`) — parallel
- Glossar-Bereich analog Ratgeber: Einträge zu Rebsorten (Grüner Veltliner,
  Zweigelt, Blaufränkisch, Riesling…), Regionen (Traisental, Weststeiermark…),
  Verkostungs- und **Histamin-Begriffen**.
- Umsetzung als Beiträge in Kategorie „Weinlexikon" (slug `weinlexikon`) oder CPT;
  Übersichts-Template (A–Z / gruppiert) + Einzeleintrag im Bold-Stil; zweisprachig
  (Polylang, EN „Wine Glossary"); Navigations-/Footer-Verlinkung; SEO (saubere
  Struktur, Meta). Inhalte via idempotentem REST-Skript (`scripts/content/lexikon.mjs`).
- Dateibesitz: `scripts/content/lexikon.mjs`, `templates/*` (Lexikon), `patterns/lexikon-*.php`,
  `inc/lexikon.php`.

### ④ Wein-Finder + Filter (`feature/wein-finder-filter`) — nach ①
- **Wein-Finder** (Quiz): wenige Schritte (histaminempfindlich? · Farbe? ·
  Geschmack trocken/süss? · Anlass?) → gefilterte Produktempfehlungen. Als
  Theme-Feature (Shortcode `[feinspitz_wine_finder]`, CSS-/leichtes JS), auf einer
  Seite „Wein-Finder" + optional auf der Startseite.
- **Verbesserte Shop-Filter:** nach Weintyp/Süße/Region + Flags
  (histamingeprüft/vegan/alkoholfrei), gestützt auf die neuen Attribute (①).
- Dateibesitz: `inc/wine-finder.php`, `patterns/wine-finder-*.php`, Ergänzungen an
  `inc/shop.php`/`shop-filter-flags.php` (Filter), Seite „Wein-Finder".

## Querschnitt
- Zweisprachig (DE/EN), Bold-Design-Tokens, saubere Typografie (Mittelpunkt statt
  Striche), i18n-Muster wie etabliert (Locale-Load, Shortcodes für dynamische Teile).
- HTTP-only Deploy/Content wie gehabt; alles reproduzierbar in Git.

## Nicht-Ziele (YAGNI)
- Kein echtes Payment-Gateway; keine Buchungs-/Zahlungsabwicklung im Wein-Finder.
- Keine weiteren Sprachen; keine Produkt-Duplikate für EN (freies Polylang).
