# Feinspitz Rebuild — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernisierten, zweisprachigen (DE/EN) WooCommerce-Nachbau von feinspitz.ch als Custom Block-Theme aufbauen — reproduzierbar in Git, parallel entwickelbar über DevSwarm-Workspaces.

**Architecture:** Ansatz A — jeder DevSwarm-Workspace startet über `@wordpress/env` ein isoliertes WordPress+MySQL in Docker (eigener Port via DevSwarm-Port-Var). Theme-Code und Content (als idempotente WP-CLI-Import-Skripte) liegen komplett in Git. Der Container `feinspitz.alpenmesh.de` (192.168.30.10) ist reines Deploy-Ziel.

**Tech Stack:** WordPress (FSE Block-Theme), WooCommerce, Polylang (DE/EN), `@wordpress/env`, WP-CLI, Node.js (Scraper), Docker.

## Global Constraints

- Textdomain für alle übersetzbaren Strings: `feinspitz`.
- Sprachen: nur Deutsch (Standard) + Englisch (`/en/`-Präfix). Keine weiteren.
- Keine echte Payment-Gateway-Anbindung — nur WooCommerce-Test-/Nachnahme-Zahlung.
- Theme-Slug/Ordner: `feinspitz`. WordPress ≥ 6.5, WooCommerce ≥ 8.x.
- Import-Skripte müssen idempotent sein (Abgleich per Slug/SKU, keine Duplikate).
- Bold-Neudesign — keine Übernahme des alten Layouts; nur Marke & Inhalte bleiben.
- Jede Phase-1-Aufgabe fasst nur ihre eigenen, nicht-überlappenden Dateien an (siehe Dateibesitz je Branch), um Merge-Konflikte zu vermeiden.

---

## Dateibesitz (Merge-Konflikt-Vermeidung)

| Branch | Exklusiv besessene Dateien |
|---|---|
| Phase 0 (main) | `theme/feinspitz/{theme.json,style.css,functions.php}`, `theme/feinspitz/parts/*`, `.wp-env.json`, `.devswarm/config.json`, `scripts/deploy/*` |
| `feature/content-migration` | `migration/**` |
| `feature/homepage` | `theme/feinspitz/templates/front-page.html`, `theme/feinspitz/patterns/home-*.php` |
| `feature/shop-archive` | `theme/feinspitz/templates/{archive-product,taxonomy-product_cat}.html`, `patterns/shop-*.php` |
| `feature/product-single` | `theme/feinspitz/templates/single-product.html`, `patterns/product-*.php` |
| `feature/cart-checkout` | `theme/feinspitz/templates/{cart,checkout}.html`, `patterns/checkout-*.php` |
| `feature/content-pages` | `theme/feinspitz/templates/page.html`, `patterns/page-*.php` |
| `feature/i18n-multilingual` | `theme/feinspitz/languages/**`, Polylang-Config-Skript in `scripts/i18n/*` |

> Konfliktzone: `functions.php`, `theme.json`. Diese werden in **Phase 0 vollständig fertiggestellt** und danach in Phase 1 möglichst nicht mehr geändert. Falls ein Branch dort etwas braucht (z. B. Pattern-Registrierung), erfolgt das über **separate Include-Dateien** (`theme/feinspitz/inc/<branch>.php`), die `functions.php` per `glob()` auto-lädt — so besitzt jeder Branch seine eigene Datei.

---

## PHASE 0 — Fundament (sequenziell, in `main`, zuerst gemergt)

Ziel: Lauffähiges, leeres Bold-Theme + isolierte Dev-Umgebung + DevSwarm-Config, sodass Phase-1-Branches sofort parallel starten können.

### Task 0.1: Voraussetzungen & Dev-Umgebung prüfen

**Files:** keine (Verifikation).

- [ ] **Step 1:** Prüfen, dass Docker & Node verfügbar sind.
  Run: `docker --version && node --version && npm --version`
  Expected: je eine Versionsnummer (Docker läuft, Node ≥ 18).
- [ ] **Step 2:** Prüfen, dass der Deploy-Container erreichbar ist (nur Info, kein Zugriff nötig).
  Run: `ping -n 1 192.168.30.10` (Windows) — Expected: Antwort oder „nicht erreichbar" dokumentieren.

### Task 0.2: `@wordpress/env`-Setup mit DevSwarm-Port-Var

**Files:**
- Create: `.wp-env.json`
- Create: `package.json`
- Create: `.devswarm/config.json`

**Interfaces:**
- Produces: Port-Var `WP_PORT` (WordPress-Frontend), `WP_TESTS_PORT` (Test-Instanz) — pro Workspace eindeutig durch DevSwarm.

- [ ] **Step 1: Port-Var bei DevSwarm registrieren**
  Run: `devswarm repo port-vars add WP_PORT` und `devswarm repo port-vars add WP_TESTS_PORT`
- [ ] **Step 2: `package.json` anlegen**
```json
{
  "name": "feinspitz-web",
  "private": true,
  "scripts": {
    "wp:start": "wp-env start",
    "wp:stop": "wp-env stop",
    "wp:cli": "wp-env run cli wp",
    "wp:import": "bash migration/import.sh"
  },
  "devDependencies": { "@wordpress/env": "^10.0.0" }
}
```
- [ ] **Step 3: `.wp-env.json` anlegen** (Port aus Env-Var, Theme gemountet, WooCommerce + Polylang aktiv)
```json
{
  "core": null,
  "phpVersion": "8.2",
  "themes": ["./theme/feinspitz"],
  "plugins": ["https://downloads.wordpress.org/plugin/woocommerce.zip",
              "https://downloads.wordpress.org/plugin/polylang.zip"],
  "port": "${WP_PORT}",
  "testsPort": "${WP_TESTS_PORT}",
  "config": { "WP_DEBUG": true, "SCRIPT_DEBUG": true }
}
```
- [ ] **Step 4:** `npm install` ausführen.
  Run: `npm install` — Expected: `@wordpress/env` installiert.
- [ ] **Step 5:** DevSwarm-Setup-Skript setzen, damit neue Workspaces automatisch booten.
  Run: `devswarm repo scripts set setup "npm install && npm run wp:start"`
- [ ] **Step 6: Commit**
  `git add -A && git commit -m "chore: wp-env + DevSwarm port-var setup"`

### Task 0.3: Theme-Grundgerüst (aktivierbares leeres Block-Theme)

**Files:**
- Create: `theme/feinspitz/style.css`
- Create: `theme/feinspitz/theme.json`
- Create: `theme/feinspitz/functions.php`
- Create: `theme/feinspitz/templates/index.html`
- Create: `theme/feinspitz/parts/header.html`, `theme/feinspitz/parts/footer.html`
- Create: `theme/feinspitz/inc/.gitkeep`

**Interfaces:**
- Produces: aktivierbares Theme `feinspitz`; `functions.php` auto-lädt alle `inc/*.php` (Erweiterungspunkt für Phase-1-Branches); Design-Tokens in `theme.json` (Farb-/Typo-Palette „Bold").

- [ ] **Step 1: `style.css` (Theme-Header)**
```css
/*
Theme Name: Feinspitz
Theme URI: https://feinspitz.ch
Description: Bold, zweisprachiges WooCommerce Block-Theme für Feinspitz Weine & Genuss.
Version: 0.1.0
Text Domain: feinspitz
Requires at least: 6.5
Tested up to: 6.7
*/
```
- [ ] **Step 2: `theme.json` mit Bold-Design-Tokens** (Farbpalette, Typo, Spacing — konkrete Startwerte; wird von Phase-1-Branches nicht überschrieben, nur konsumiert)
```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "layout": { "contentSize": "760px", "wideSize": "1280px" },
    "color": { "palette": [
      { "slug": "base", "color": "#0e0b08", "name": "Base" },
      { "slug": "contrast", "color": "#f6f1e7", "name": "Contrast" },
      { "slug": "wine", "color": "#7b1f2b", "name": "Wine" },
      { "slug": "gold", "color": "#c9a24b", "name": "Gold" }
    ]},
    "typography": { "fluid": true, "fontFamilies": [
      { "slug": "heading", "name": "Heading", "fontFamily": "\"Fraunces\", serif" },
      { "slug": "body", "name": "Body", "fontFamily": "\"Inter\", sans-serif" }
    ]}
  },
  "styles": {
    "color": { "background": "var(--wp--preset--color--contrast)", "text": "var(--wp--preset--color--base)" },
    "typography": { "fontFamily": "var(--wp--preset--font-family--body)" }
  }
}
```
- [ ] **Step 3: `functions.php` (i18n-Load + Auto-Include + WooCommerce-Support)**
```php
<?php
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'feinspitz', get_template_directory() . '/languages' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
} );
// Erweiterungspunkt: jeder Phase-1-Branch legt seine eigene inc/<branch>.php an.
foreach ( glob( get_template_directory() . '/inc/*.php' ) as $inc ) { require_once $inc; }
```
- [ ] **Step 4: minimale Templates & Parts**
  `templates/index.html`:
```html
<!-- wp:template-part {"slug":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group"><!-- wp:post-content /--></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer"} /-->
```
  `parts/header.html`:
```html
<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:site-title /--><!-- wp:navigation /--></div>
<!-- /wp:group -->
```
  `parts/footer.html`:
```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph --><p>© Feinspitz</p><!-- /wp:paragraph --></div>
<!-- /wp:group -->
```
- [ ] **Step 5: Theme starten & aktivieren, verifizieren**
  Run: `npm run wp:start` dann `npm run wp:cli theme activate feinspitz`
  Expected: `wp theme list` zeigt `feinspitz` als `active`; Startseite lädt ohne Fehler auf `http://localhost:$WP_PORT`.
- [ ] **Step 6: Commit**
  `git add -A && git commit -m "feat: bootstrap feinspitz block theme (empty, activatable)"`

### Task 0.4: WooCommerce- & Polylang-Bootstrap-Skript

**Files:**
- Create: `scripts/bootstrap.sh`

**Interfaces:**
- Produces: `scripts/bootstrap.sh` — richtet Polylang (DE Standard + EN) und WooCommerce-Grundseiten idempotent ein; wird von `import.sh` vorausgesetzt.

- [ ] **Step 1: `scripts/bootstrap.sh`** (idempotent; nutzt `wp-env run cli`)
```bash
#!/usr/bin/env bash
set -euo pipefail
WP="npm run --silent wp:cli --"
$WP plugin activate woocommerce polylang || true
# Sprachen: DE (Standard) + EN
$WP pll lang list --format=csv 2>/dev/null | grep -q de || $WP pll lang create Deutsch de de_DE || true
$WP pll lang list --format=csv 2>/dev/null | grep -q en || $WP pll lang create English en en_US || true
# WooCommerce-Grundseiten sicherstellen
$WP wc --user=admin tool run install_pages 2>/dev/null || true
echo "bootstrap done"
```
- [ ] **Step 2:** ausführbar machen & laufen lassen.
  Run: `bash scripts/bootstrap.sh`
  Expected: „bootstrap done"; `wp pll lang list` zeigt `de` und `en`.
- [ ] **Step 3: Commit**
  `git add -A && git commit -m "feat: WooCommerce + Polylang bootstrap script"`

### Task 0.5: Leerer `import.sh`-Rahmen + Deploy-Skript-Stub

**Files:**
- Create: `migration/import.sh` (Rahmen mit Aufruf von bootstrap)
- Create: `scripts/deploy/deploy.sh` (Stub mit dokumentierten Schritten)

- [ ] **Step 1: `migration/import.sh`**
```bash
#!/usr/bin/env bash
set -euo pipefail
bash "$(dirname "$0")/../scripts/bootstrap.sh"
# Produkte/Seiten-Import wird von feature/content-migration befüllt.
echo "import: bootstrap complete; content import TODO in feature/content-migration"
```
- [ ] **Step 2: `scripts/deploy/deploy.sh`** (dokumentierter Stub)
```bash
#!/usr/bin/env bash
set -euo pipefail
# 1) Theme via rsync/scp -> Container (user automation @ 192.168.30.10)
# 2) wp theme activate feinspitz auf Container
# 3) bash migration/import.sh gegen Container-WP-CLI
echo "deploy stub — wird in Phase 2 vervollständigt"
```
- [ ] **Step 3: Commit**
  `git add -A && git commit -m "chore: import.sh + deploy stub"`

### Task 0.6: DevSwarm-Config committen & Fundament abschließen

- [ ] **Step 1:** `devswarm repo validate` — Expected: valid.
- [ ] **Step 2:** `devswarm repo refresh` und angezeigte Init-Datei sourcen; `echo $WP_PORT` zeigt eindeutigen Port.
- [ ] **Step 3: Commit** `.devswarm/config.json`
  `git add -A && git commit -m "chore: commit DevSwarm config (port-vars + setup script)"`

**➡️ Nach Phase 0: `main` enthält lauffähiges leeres Theme + Dev-Umgebung. Jetzt Phase-1-Branches parallel spawnen.**

---

## PHASE 1 — Parallele Branches (DevSwarm-Workspaces)

Jeder Branch wird als eigener DevSwarm-Workspace erstellt und mit dem untenstehenden Briefing beauftragt. Jeder Workspace erstellt bei Bedarf seinen **eigenen** detaillierten Sub-Plan (writing-plans) für seine Dateien. Alle bauen auf dem gemergten Phase-0-`main` auf.

### Branch A — `feature/content-migration`
**Deliverable:** Scraper + idempotenter Import echter Produkte/Kategorien/Seiten/Medien.
**Files:** `migration/scrape/*.mjs`, `migration/data/*.json`, `migration/import.sh` (befüllen).
**Tasks:**
1. Node-Scraper: feinspitz.ch → `migration/data/products.json` (Name, Slug, Preis, Beschreibung, Bild-URLs, Kategorie, Flags histamingeprüft/vegan/alkoholfrei) + `categories.json` + `pages.json`.
2. Medien-Download nach `migration/data/media/`.
3. `import.sh` erweitern: `wp wc product create` / `wp term create product_cat` / `wp post create` per Slug/SKU idempotent; Medien via `wp media import`.
4. Verifikation: `wp wc product list` zeigt migrierte Produkte; keine Duplikate bei 2× Lauf.
**Briefing-Verweis:** Spec §4, §6.

### Branch B — `feature/homepage`
**Deliverable:** Bold-Startseite.
**Files:** `theme/feinspitz/templates/front-page.html`, `patterns/home-*.php`, `inc/homepage.php` (Pattern-Registrierung).
**Tasks:** Hero-Pattern (Marke, USP „1. histamingeprüfte Weine CH"), Featured-Produkte-Grid (Query-Loop), Story-/Über-uns-Teaser, Weinproben/Catering-CTA. Nutzt `theme.json`-Tokens. Verifikation: Startseite rendert, responsiv, DE/EN-fähig (Strings über `feinspitz`-Textdomain).

### Branch C — `feature/shop-archive`
**Deliverable:** Shop- & Kategorie-Ansichten + Filter.
**Files:** `templates/archive-product.html`, `templates/taxonomy-product_cat.html`, `patterns/shop-*.php`, `inc/shop.php`.
**Tasks:** Produkt-Grid via WooCommerce-Blöcke, Facetten/Filter für histamingeprüft/vegan/alkoholfrei (Produkt-Attribute), Sortierung, Pagination. Verifikation: Filter grenzen Ergebnismenge korrekt ein.

### Branch D — `feature/product-single`
**Deliverable:** Einzelprodukt-Seite.
**Files:** `templates/single-product.html`, `patterns/product-*.php`, `inc/product.php`.
**Tasks:** Galerie, Titel/Preis/Beschreibung, Attribut-Badges (histamingeprüft etc.), „In den Warenkorb", verwandte Produkte. Verifikation: Add-to-Cart funktioniert.

### Branch E — `feature/cart-checkout`
**Deliverable:** Warenkorb & Kasse.
**Files:** `templates/cart.html`, `templates/checkout.html`, `patterns/checkout-*.php`, `inc/checkout.php`.
**Tasks:** WooCommerce Cart-/Checkout-Blöcke im Bold-Layout, Test-Zahlungsmethode aktiv. Verifikation: kompletter Kauf-Flow bis Bestellbestätigung mit Test-Zahlung.

### Branch F — `feature/content-pages`
**Deliverable:** Statische Seiten.
**Files:** `templates/page.html`, `patterns/page-*.php`, `inc/pages.php`.
**Tasks:** Über uns, Kontakt (Adresse Urdorf, Tel, E-Mail), Weinproben, Catering, Rechtstexte (AGB, Lieferung, Datenschutz, Impressum). Verifikation: alle Seiten erreichbar & verlinkt.

### Branch G — `feature/i18n-multilingual`
**Deliverable:** DE/EN durchgängig.
**Files:** `theme/feinspitz/languages/feinspitz.pot` + `de_DE`/`en_US` `.po`/`.mo`, `scripts/i18n/setup-polylang.sh`, `inc/i18n.php` (Sprachumschalter-Block/Shortcode in Header/Footer).
**Tasks:** `.pot` aus Theme extrahieren (`wp i18n make-pot`), EN-Übersetzungen, Polylang-Sprachverknüpfung der importierten Inhalte, Sprachumschalter in Header/Footer. Verifikation: Umschalten DE↔EN wechselt Inhalte & UI-Strings; `/en/`-URLs funktionieren.

---

## PHASE 2 — Integration & Deploy

### Task 2.1: Merge & QA
- [ ] Alle `feature/*`-Branches nach `main` mergen (Reihenfolge: content-migration zuerst, dann Templates, dann i18n).
- [ ] Frischer `wp-env` + `import.sh`: kompletter Durchlauf ohne Fehler.
- [ ] QA-Matrix: Startseite, Shop, Produkt, Warenkorb→Kasse, statische Seiten, DE/EN, Responsiveness (Mobile/Desktop).

### Task 2.2: Deploy auf `alpenmesh`-Container
- [ ] `scripts/deploy/deploy.sh` vervollständigen: Theme via rsync/scp zu `automation@192.168.30.10`, `wp theme activate feinspitz`, `import.sh` gegen Container-WP-CLI.
- [ ] Deploy ausführen, `https://feinspitz.alpenmesh.de/` visuell abnehmen.
- [ ] Aikido-Security-Scan über generierten Code (`/aikido:scan`).

---

## Self-Review (gegen Spec)

- **Spec §2 Architektur** → Phase 0 Task 0.2 (wp-env + Port-Var), 0.6 (DevSwarm-Config). ✅
- **Spec §3 Theme-Struktur** → Task 0.3. ✅
- **Spec §4 Migration** → Branch A. ✅
- **Spec §5 Mehrsprachigkeit** → Task 0.3 (i18n-load), Branch G. ✅
- **Spec §6 WooCommerce** → Task 0.4 (bootstrap), Branch C/D/E. ✅
- **Spec §7 Branch-Plan** → Phase 1 A–G. ✅
- **Spec §8 QA & Deploy** → Phase 2. ✅
- Platzhalter-Scan: keine TBD/TODO in ausführbaren Schritten (Content-Import-TODO ist bewusst Branch A zugewiesen). ✅
- Typ-/Namenskonsistenz: `WP_PORT`/`WP_TESTS_PORT`, Textdomain `feinspitz`, `inc/*.php`-Auto-Load durchgängig. ✅
