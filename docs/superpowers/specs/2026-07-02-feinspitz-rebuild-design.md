# Feinspitz Web — Rebuild & Modernisierung (Design-Spec)

**Datum:** 2026-07-02
**Status:** Freigegeben (Design), Übergang zu Implementierungsplan

## 1. Ziel

Die bestehende Website **feinspitz.ch** (Schweizer Weinshop mit Spezialisierung auf
histamingeprüfte Weine + Gourmet-Produkte) wird modernisiert und komplett neu als
**WooCommerce-Shop** auf einer lokalen WordPress-Instanz nachgebaut. Ergebnis ist ein
bold neu gestalteter, **zweisprachiger (DE/EN)** Online-Shop, dessen gesamter Zustand
(Theme + Inhalte) reproduzierbar in Git liegt.

### Entscheidungen (bestätigt)

| Thema | Entscheidung |
|---|---|
| Umfang | Voller WooCommerce-Shop (Produkte, Warenkorb, Kasse, Kategorien) |
| Inhalte | Echte Daten von feinspitz.ch migrieren (direkter Zugriff erlaubt) |
| Design | Komplett neu / bold — nur Marke & Inhalte bleiben |
| Tech | Custom Block-Theme (FSE) aus Code |
| Parallelisierung | Ansatz A: isoliertes lokales WordPress pro Workspace |
| Sprachen | Zweisprachig Deutsch + Englisch (Polylang) |
| Zahlung | Test-Zahlung in dieser Phase (keine echte Gateway-Anbindung) |

## 2. Architektur

Repo = Single Source of Truth für **Code und Inhalte** (Inhalte als versionierte
Import-Skripte + Datenexport).

```
Git-Repo (feinspitz-web)              Laufzeit
├── theme/feinspitz/     ─────────┐   Pro DevSwarm-Workspace:
│   (Custom Block-Theme)          │   eigenes wp-env WordPress (Docker,
├── migration/                    ├──▶ eigener Port-Var) = isolierte DB + Theme
│   (Scraper + WP-CLI Import)     │
├── .wp-env.json                  │   feinspitz.alpenmesh.de (192.168.30.10):
├── .devswarm/config.json         └──▶ Deploy-Ziel für gemergtes Ergebnis
└── scripts/deploy
```

- **wp-env** (`@wordpress/env`) startet pro Workspace ein isoliertes WordPress + MySQL
  in Docker auf einem eindeutigen, per DevSwarm-Port-Var zugewiesenen Port.
- **Reproduzierbar:** `wp-env start` + `bash migration/import.sh` → auf jeder Instanz
  identischer Zustand.
- **Deploy:** Theme-Sync + `import.sh` via WP-CLI/SSH gegen den `alpenmesh`-Container.

### Voraussetzungen pro Workspace
- Docker (für wp-env)
- Node.js (für `@wordpress/env` + Scraper)
- WP-CLI (über wp-env verfügbar)

## 3. Repo- & Theme-Struktur

```
theme/feinspitz/
├── theme.json              # Design-Tokens: Farben, Typo, Spacing, Layout
├── style.css               # Theme-Header + minimale globale Styles
├── functions.php           # Enqueue, Theme-Supports, WooCommerce-Hooks, i18n load
├── templates/              # FSE-Templates:
│                           #   front-page, archive-product, taxonomy-product_cat,
│                           #   single-product, cart, checkout, page, 404
├── parts/                  # header.html (inkl. Sprachumschalter), footer.html
├── patterns/               # Block-Patterns (Hero, Produkt-Grid, Feature, CTA, ...)
└── languages/              # feinspitz.pot + de_DE / en_US .po/.mo
migration/
├── scrape/                 # feinspitz.ch → JSON (Produkte, Kategorien, Seiten, Medien)
├── data/                   # Exportierte JSON + Medien
└── import.sh               # Idempotenter WP-CLI-Import (inkl. Polylang-Verknüpfung)
scripts/
└── deploy/                 # Sync + Import gegen alpenmesh-Container
```

## 4. Content-Migration (reproduzierbar & idempotent)

1. **Scrape:** feinspitz.ch → strukturiertes JSON: Produkte (Name, Preis, Beschreibung,
   Bild, Kategorie, Attribute wie „histamingeprüft"/„vegan"/„alkoholfrei"), Kategorie-
   Baum, statische Seiten, Medien.
2. **Import:** `import.sh` legt via WP-CLI Kategorien, Produkte, Attribute, Seiten &
   Medien an — mehrfach ausführbar ohne Duplikate (Abgleich per Slug/SKU).
3. **Sprachen:** DE als Basis-Import; EN-Übersetzungen als verknüpfte Polylang-Objekte
   (initial ggf. maschinell/übernommen, redaktionell verfeinerbar).

## 5. Mehrsprachigkeit (DE/EN)

- Theme vollständig **i18n-ready**: Textdomain `feinspitz`, alle Strings via
  `__()`/`esc_html__()`, `.pot` generiert.
- **Polylang** (frei) für Inhalte/Produkte; Sprachpaare per Import-Skript verknüpft.
- **Sprachumschalter** in Header + Footer.
- URL-Struktur: Sprach-Präfix (z. B. `/en/`), DE als Standard.

## 6. WooCommerce-Setup

- Produkt-Kategorien gemäß Original: Weine (Weiß/Rot/Rosé/Schaumwein/Dessert),
  Gourmet (Essig/Verjus, Senf/Gewürze, Pesto/Chutney, Pasta).
- Produkt-Attribute/Tags: histamingeprüft, vegan, alkoholfrei (als Filter nutzbar).
- Warenkorb & Kasse als WooCommerce-Blöcke im FSE-Theme; Test-Zahlungsmethode.

## 7. Parallelisierung — DevSwarm-Branch-Plan

### Phase 0 — Fundament (sequenziell, 1 Workspace, zuerst nach `main` gemergt)
Repo-Scaffold, `.wp-env.json`, DevSwarm-Port-Vars (`WP_PORT`, `WP_CLI_PORT`),
`.devswarm/config.json`, Theme-Grundgerüst mit `theme.json` (Bold-Design-Tokens),
Header/Footer-Parts inkl. Sprachumschalter, Basis-Pattern-Bibliothek, Polylang- +
WooCommerce-Bootstrap, i18n-Setup, leerer `import.sh`-Rahmen.
**Muss zuerst fertig sein** — verhindert Konflikte an geteilten Dateien.

### Phase 1 — Parallele Branches (jeder besitzt nicht-überlappende Dateien)

| Branch | Verantwortung | Primäre Dateien |
|---|---|---|
| `feature/content-migration` | Scraper + Import-Skripte + Datenexport | `migration/**` |
| `feature/homepage` | Startseiten-Template + Hero/Story/Featured-Patterns | `templates/front-page.html`, `patterns/home-*` |
| `feature/shop-archive` | Shop-/Kategorie-Templates + Filter | `templates/archive-product.html`, `taxonomy-product_cat.html` |
| `feature/product-single` | Einzelprodukt-Template, Galerie, Warenkorb-Button | `templates/single-product.html`, `patterns/product-*` |
| `feature/cart-checkout` | Warenkorb- & Kassen-Templates | `templates/cart.html`, `checkout.html` |
| `feature/content-pages` | Über uns, Kontakt, Weinproben, Catering, Rechtstexte | `templates/page.html`, `patterns/page-*` |
| `feature/i18n-multilingual` | Polylang-Config, Sprachumschalter-Feinschliff, `.pot`/`.po` | `languages/**`, Polylang-Setup |

### Phase 2 — Integration
Alle Branches → `main` mergen, gemeinsamer QA-Durchlauf, Deploy auf `alpenmesh`.

## 8. QA & Deploy

- Pro Branch: visuelle Prüfung im lokalen wp-env, Responsiveness, WooCommerce-Flow
  (Produkt → Warenkorb → Kasse), Sprachumschaltung DE/EN.
- Deploy-Skript: Theme-Sync + `import.sh` gegen den Container (WP-CLI/SSH,
  User `automation`).

## 9. Nicht-Ziele (YAGNI)

- Keine echte Payment-Gateway-Anbindung (nur Test-Zahlung).
- Keine weiteren Sprachen außer DE/EN.
- Keine Übernahme des alten Designs (bewusster Neuaufbau).
- Kein Kundenkonto-/CRM-Sonderfeature über WooCommerce-Standard hinaus.
