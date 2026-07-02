# Feinspitz Rebuild — Implementation Plan (HTTP-only Architektur)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernisierten, zweisprachigen (DE/EN) WooCommerce-Nachbau von feinspitz.ch als Custom Block-Theme aufbauen — Theme-Code + Migrations-Skripte reproduzierbar in Git, parallel entwickelbar über DevSwarm-Branches, ausgerollt auf den bestehenden Server-Container über HTTP.

**Architecture (angepasst):** Kein lokales Docker, kein SSH. Es gibt **eine** Laufzeit: das bestehende WordPress im Container auf `feinspitz.alpenmesh.de` (192.168.30.10). Zugriff ausschließlich über **HTTP**: WordPress-REST (`wp/v2`), WooCommerce-REST (`wc/v3`) und — für den Custom-Theme-Upload, den REST nicht abdeckt — ein cookie-authentifizierter POST an `wp-admin/update.php?action=upload-theme`. Der Theme-Code und alle Content-Import-Skripte leben in Git; DevSwarm parallelisiert die **Code-Branches**. Content-Migration läuft **einmal zentral** gegen den Server (kein Per-Workspace-Runtime möglich).

**Tech Stack:** WordPress (FSE Block-Theme), WooCommerce, Polylang (DE/EN), REST-API (Application Password Auth), Node.js (Scraper + REST-Client), PowerShell/Node (Deploy). Kein Docker/wp-env/WP-CLI.

## Global Constraints

- Textdomain für alle übersetzbaren Strings: `feinspitz`.
- Sprachen: nur Deutsch (Standard) + Englisch (`/en/`-Präfix). Keine weiteren.
- Keine echte Payment-Gateway-Anbindung — nur WooCommerce-Test-/Nachnahme-Zahlung.
- Theme-Slug/Ordner: `feinspitz`. WordPress ≥ 6.5, WooCommerce ≥ 8.x.
- Import-Skripte idempotent (Abgleich per Slug/SKU, keine Duplikate).
- Bold-Neudesign — keine Übernahme des alten Layouts; nur Marke & Inhalte bleiben.
- **Zugriff nur über HTTP.** Server-Automation braucht ein **Application Password** des Users `automation` (Login-Passwort funktioniert per REST nicht). Secrets NICHT committen — in `.env.local` (gitignored), von Skripten gelesen.
- Ziel-Runtime ist geteilt (ein Server). Live-QA konkurrierender Branches wird koordiniert; parallel ist die **Code**-Entwicklung, nicht der Laufzeit-Zustand.

---

## Dateibesitz (Merge-Konflikt-Vermeidung)

| Branch | Exklusiv besessene Dateien |
|---|---|
| Phase 0 (main) | `theme/feinspitz/{theme.json,style.css,functions.php}`, `theme/feinspitz/parts/*`, `theme/feinspitz/inc/.gitkeep`, `scripts/lib/*`, `scripts/deploy/*`, `package.json`, `.env.local.example`, `.gitignore` |
| `feature/content-migration` | `migration/**` |
| `feature/homepage` | `theme/feinspitz/templates/front-page.html`, `patterns/home-*.php`, `inc/homepage.php` |
| `feature/shop-archive` | `theme/feinspitz/templates/{archive-product,taxonomy-product_cat}.html`, `patterns/shop-*.php`, `inc/shop.php` |
| `feature/product-single` | `theme/feinspitz/templates/single-product.html`, `patterns/product-*.php`, `inc/product.php` |
| `feature/cart-checkout` | `theme/feinspitz/templates/{cart,checkout}.html`, `patterns/checkout-*.php`, `inc/checkout.php` |
| `feature/content-pages` | `theme/feinspitz/templates/page.html`, `patterns/page-*.php`, `inc/pages.php` |
| `feature/i18n-multilingual` | `theme/feinspitz/languages/**`, `scripts/i18n/*`, `inc/i18n.php` |

> `functions.php` & `theme.json` werden in **Phase 0 finalisiert** und in Phase 1 nicht mehr angefasst. Erweiterungen laufen über eigene `theme/feinspitz/inc/<branch>.php`, die `functions.php` per `glob()` auto-lädt.

---

## PHASE 0 — Fundament (sequenziell, in `main`, zuerst gemergt)

### Task 0.1: Repo-Struktur, Secrets-Handling, Node-Client

**Files:**
- Create: `package.json`, `.gitignore` (ergänzen), `.env.local.example`
- Create: `scripts/lib/wp.mjs` (REST-Client: Auth-Header, GET/POST/PUT Helper)

**Interfaces:**
- Produces: `scripts/lib/wp.mjs` exportiert `wp(path, {method, body})` (REST via `?rest_route=`, Basic-Auth aus `WP_USER`/`WP_APP_PASSWORD`) und `wpAdmin(...)` (cookie-basierter Login + nonce für wp-admin-Uploads).

- [ ] **Step 1:** `.env.local.example` mit `WP_BASE=https://feinspitz.alpenmesh.de`, `WP_USER=automation`, `WP_APP_PASSWORD=`. Reale `.env.local` in `.gitignore`.
- [ ] **Step 2:** `package.json` (type module) mit Skripten `plugins:install`, `theme:deploy`, `content:import`.
- [ ] **Step 3:** `scripts/lib/wp.mjs` — REST-Client mit Application-Password Basic-Auth; Helper für Fehlerbehandlung.
- [ ] **Step 4: Verifikation** `node -e "import('./scripts/lib/wp.mjs')"` lädt ohne Fehler; mit gesetztem App-Password gibt `GET /wp/v2/users/me` `roles: [administrator]` zurück.
- [ ] **Step 5: Commit.**

### Task 0.2: Plugin-Installation über REST

**Files:** Create `scripts/plugins-install.mjs`
- [ ] **Step 1:** Skript: `POST /wp/v2/plugins {slug:'woocommerce', status:'active'}` und dito `polylang`. Idempotent (409/„exists" tolerieren, dann aktivieren).
- [ ] **Step 2: Verifikation:** `GET /?rest_route=/` listet danach Namespace `wc/v3`; `GET /wp/v2/plugins` zeigt beide `active`.
- [ ] **Step 3:** Fallback dokumentieren, falls `wp/v2/plugins` durch `DISALLOW_FILE_MODS` gesperrt ist → manueller GUI-Install (2 Klicks).
- [ ] **Step 4: Commit.**

### Task 0.3: Theme-Grundgerüst (aktivierbares leeres Block-Theme)

**Files:** `theme/feinspitz/{style.css,theme.json,functions.php}`, `templates/index.html`, `parts/{header,footer}.html`, `inc/.gitkeep`
- [ ] **Step 1–4:** Dateien wie im Spec §3 (style.css-Header, theme.json Bold-Tokens, functions.php mit i18n-load + `glob(inc/*.php)`-Auto-Load + WooCommerce-Support, minimale Templates/Parts).
- [ ] **Step 5: Commit.**

### Task 0.4: Theme-Deploy über HTTP (cookie-auth Upload)

**Files:** Create `scripts/deploy/build-zip.mjs`, `scripts/deploy/deploy-theme.mjs`
- [ ] **Step 1:** `build-zip.mjs` packt `theme/feinspitz` → `dist/feinspitz.zip`.
- [ ] **Step 2:** `deploy-theme.mjs`: Login-POST an `wp-login.php` (Cookies), Nonce von `themes.php` scrapen, Multipart-POST `wp-admin/update.php?action=upload-theme` mit `overwrite=true`, dann Theme via `POST /wp/v2/themes`-Alternative bzw. `update.php` aktivieren.
- [ ] **Step 3: Verifikation:** Nach Deploy zeigt `GET /wp/v2/themes?status=active` `feinspitz`; Startseite lädt.
- [ ] **Step 4: Commit.**

### Task 0.5: Bootstrap-Skript (Polylang-Sprachen + WooCommerce-Seiten)

**Files:** Create `scripts/bootstrap.mjs`
- [ ] **Step 1:** Über REST/Options: Polylang DE (Standard) + EN anlegen; WooCommerce-Grundseiten sicherstellen; Permalinks auf „post name" setzen (damit `wp-json/` sauber läuft).
- [ ] **Step 2: Verifikation:** `pll`-Sprachen vorhanden; WooCommerce Shop/Cart/Checkout-Seiten existieren.
- [ ] **Step 3: Commit.**

### Task 0.6: DevSwarm-Config + `import.sh/mjs`-Rahmen
- [ ] **Step 1:** `.devswarm/config.json`: file-pattern `.env.local` (in neue Workspaces kopieren), Setup-Skript `npm install`.
- [ ] **Step 2:** `migration/import.mjs`-Rahmen ruft `bootstrap.mjs` und meldet „content import TODO (feature/content-migration)".
- [ ] **Step 3:** `devswarm repo validate`; Commit.

**➡️ Nach Phase 0: `main` hat lauffähiges leeres Theme auf dem Server + komplette HTTP-Automation. Phase-1-Branches parallel spawnen.**

---

## PHASE 1 — Parallele Branches (DevSwarm-Workspaces)

Wie zuvor (Branches A–G). Anpassung durch HTTP-only:
- **Live-Test** eines Branches = `npm run theme:deploy` gegen den Server; da Runtime geteilt ist, **koordiniert** (ein Branch deployt zur QA, dann nächster). Code-Entwicklung bleibt voll parallel.
- **Branch A (content-migration)** läuft zentral gegen den Server und wird als Erstes gemergt, damit die anderen Branches echte Daten sehen.

| Branch | Deliverable | Dateien |
|---|---|---|
| `feature/content-migration` | Scraper (feinspitz.ch→JSON) + REST-Import (wc/v3 Produkte, wp/v2 Seiten/Medien, Polylang-Verknüpfung), idempotent | `migration/**` |
| `feature/homepage` | Bold-Startseite (Hero, Featured-Grid, Story, CTA) | `templates/front-page.html`, `patterns/home-*.php`, `inc/homepage.php` |
| `feature/shop-archive` | Shop-/Kategorie-Templates + Filter (histamingeprüft/vegan/alkoholfrei) | `templates/{archive-product,taxonomy-product_cat}.html`, `patterns/shop-*.php`, `inc/shop.php` |
| `feature/product-single` | Einzelprodukt (Galerie, Badges, Add-to-Cart, verwandte) | `templates/single-product.html`, `patterns/product-*.php`, `inc/product.php` |
| `feature/cart-checkout` | Warenkorb & Kasse (WooCommerce-Blöcke, Test-Zahlung) | `templates/{cart,checkout}.html`, `patterns/checkout-*.php`, `inc/checkout.php` |
| `feature/content-pages` | Über uns, Kontakt, Weinproben, Catering, Rechtstexte | `templates/page.html`, `patterns/page-*.php`, `inc/pages.php` |
| `feature/i18n-multilingual` | `.pot`/`.po`/`.mo`, Polylang-Verknüpfung, Sprachumschalter | `languages/**`, `scripts/i18n/*`, `inc/i18n.php` |

---

## PHASE 2 — Integration & Deploy
- [ ] Branches → `main` mergen (content-migration zuerst, dann Templates, dann i18n).
- [ ] Voller Deploy: `theme:deploy` + `content:import` gegen Server; QA-Matrix (Start/Shop/Produkt/Cart→Checkout/Seiten/DE-EN/Responsive).
- [ ] Aikido-Security-Scan (`/aikido:scan`) über generierten Code.

## Self-Review (gegen Spec)
- §2 Architektur → Phase 0 (HTTP-only Anpassung dokumentiert). ✅
- §3 Theme → Task 0.3. ✅  · §4 Migration → Branch A. ✅ · §5 i18n → Branch G. ✅
- §6 WooCommerce → Task 0.2/0.5 + Branch C/D/E. ✅ · §7 Branches → Phase 1. ✅ · §8 QA/Deploy → Phase 2. ✅
- Secrets: App-Password nur in gitignored `.env.local`. ✅
