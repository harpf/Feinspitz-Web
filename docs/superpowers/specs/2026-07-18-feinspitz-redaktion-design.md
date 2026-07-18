# Feinspitz-Redaktion · Geführte Masken für Produkte & Artikel

**Datum:** 2026-07-18
**Status:** Design freigegeben, bereit für Implementierungsplan
**Vorgänger:** `2026-07-02-feinspitz-admin-usability-design.md` (Admin-Dashboard, Produktspalten, Editor-Vorlagen)

## Problem

Der Betreiber von Feinspitz ist **nicht-technisch**. Beide Standard-Oberflächen überfordern:

- Die volle **WooCommerce-Produktseite** (viele Reiter: Attribute, Versand, Lager, Variationen) ist zu komplex, um einen Wein zuverlässig anzulegen. Die bestehende Produkt-Checkliste (`inc/editor-templates.php`) erklärt den Weg nur, nimmt aber keine Arbeit ab.
- Der **Gutenberg-Block-Editor** überfordert beim Schreiben von Ratgeber-/Weinlexikon-Artikeln. Editor-Vorlagen existieren als Block-Patterns, müssen aber manuell eingefügt werden, und die richtige Kategorie muss selbst gesetzt werden.

## Ziel

Eine eigene, geführte **„Feinspitz-Redaktion"** mit zwei einfachen Formular-Seiten — je eine für Produkte und Artikel — die serverseitig über die WP-/WooCommerce-APIs saubere Datensätze anlegen und aktualisieren.

**Grundsatzentscheidungen (mit dem Nutzer abgestimmt):**

1. **Ergänzend, nicht ersetzend:** Die einfachen Masken sind der primäre Weg; die WooCommerce-/Gutenberg-Standardseiten bleiben für Sonderfälle als Fallback erreichbar. Kein Verstecken, kein Rollen-Umbau.
2. **Beide Inhaltstypen** (Produkt + Artikel) im Umfang, symmetrisch aufgebaut.
3. Alles unter dem **bestehenden Feinspitz-Adminmenü**, reiner Theme-PHP-Code.

## Rahmenbedingungen (aus dem Bestand)

- **i18n:** Admin-Strings als **deutsche String-Literale**, KEINE neuen gettext-msgids — der zentrale i18n-Build (`scripts/i18n/make-po.mjs`) verlangt für jede msgid eine EN-Übersetzung. Konvention bereits in `inc/admin.php`.
- **Wein-Attribute** sind globale WooCommerce-Attribute (`pa_weingut`, `pa_rebsorte`, `pa_region`, `pa_suesse`, `pa_jahrgang`, `pa_volumen`), bewusst `visible=false`. `inc/product-facts.php` rendert sie als „Auf einen Blick"-Tabelle; `inc/shop.php`/Wein-Finder filtern darüber. Die Maske muss exakt diese Struktur schreiben.
- **Flags** sind `product_tag`-Terme mit den Slugs `histamingeprueft`, `vegan`, `alkoholfrei` (siehe `inc/product.php`, `inc/admin.php`).
- **Artikel-Kategorien:** `ratgeber` und `weinlexikon` (DE). Der **Auszug** ist inhaltlich wichtig — er erscheint in den Ratgeber-Übersichtskarten und im A–Z-Weinlexikon (`inc/ratgeber.php`, `inc/lexikon.php`).
- **Polylang** ist aktiv; neue Inhalte werden der Standardsprache **DE** zugeordnet.
- **Kein PHP-Test-Harness** (nur Node-Skripte, kein phpcs). Verifikation = `php -l` + manueller Smoke-Test auf `feinspitz.alpenmesh.de`.
- **Deploy:** Theme-PHP via `node scripts/deploy/deploy-theme.mjs` (HTTP-only, kein SSH/Docker).

## Architektur

Drei neue Dateien im Theme (`theme/feinspitz/inc/`), automatisch via `glob inc/*.php` geladen, eine Verantwortung pro Datei:

| Datei | Verantwortung |
|---|---|
| `inc/admin-forms-shared.php` | Gemeinsame Basis: Registrierung der Untermenü-Seiten am Feinspitz-Menü, Feld-Renderer (Text, Zahl, Textarea, Select, Checkbox, Bildwähler, einfacher Editor), Medien-Picker-Enqueue (`wp_enqueue_media` + kleines JS, nur auf den Redaktions-Seiten), Nonce-/Redirect-/Notice-Helfer, Capability-Checks, gescoptes Admin-CSS (Feinspitz-Tokens). |
| `inc/admin-product-form.php` | Geführte Produkt-Maske: Rendern + Speichern über WooCommerce-CRUD-API. |
| `inc/admin-article-form.php` | Geführte Artikel-Maske: Rendern + Speichern über WP-Post-API. |

**Gesamter Code ist Admin-only** (`is_admin()`-Gate bzw. Admin-only-Hooks), keine Frontend-Wirkung.

**Menüstruktur** (Untermenüs am bestehenden Top-Level `feinspitz`):
- Übersicht (bestehend)
- **Neues Produkt** → `feinspitz-produkt-neu`
- **Neuer Artikel** → `feinspitz-artikel-neu`

Die bestehenden Schnell-Aktions-Karten im Dashboard (`inc/admin.php`) werden auf diese neuen Seiten umgebogen (statt auf `post-new.php`).

**Formularverarbeitung:** POST an `admin-post.php` mit Actions `feinspitz_save_product` / `feinspitz_save_article`. Handler validiert Nonce + Capability, schreibt den Datensatz, redirectet zurück auf die Maske mit `?feinspitz_notice=…` (Post/Redirect/Get — kein versehentliches Doppel-Anlegen).

### Datenfluss (Produkt)

```
Formular (GET: leer oder ?id=123 zum Bearbeiten)
  → POST admin-post.php?action=feinspitz_save_product
    → Nonce + current_user_can('edit_products') prüfen
    → new WC_Product_Simple() ODER wc_get_product($id)
    → set_name / set_description / set_regular_price / set_image_id
    → set_category_ids / Flags als product_tag setzen
    → Attribute: je pa_*-Auswahl (inkl. „oder neu:" → wp_insert_term) als
      WC_Product_Attribute (taxonomy, visible=false) → set_attributes()
    → set_status(publish|draft) → save()
  → Redirect: ?page=feinspitz-produkt-neu&id=<neu>&feinspitz_notice=saved
```

### Datenfluss (Artikel)

```
Formular (GET: leer oder ?id=123)
  → POST admin-post.php?action=feinspitz_save_article
    → Nonce + current_user_can('edit_posts') prüfen
    → wp_insert_post / wp_update_post
      (post_type=post, post_content=HTML, post_excerpt=Teaser)
    → Kategorie aus Typ (ratgeber|weinlexikon) via wp_set_post_terms
    → Beitragsbild via set_post_thumbnail
    → Polylang: pll_set_post_language($id,'de') falls Funktion vorhanden
    → post_status=publish|draft
  → Redirect: ?page=feinspitz-artikel-neu&id=<neu>&feinspitz_notice=saved
```

## Produkt-Maske · Felder

| Feld | Eingabe | Pflicht | → Speicherung |
|---|---|---|---|
| Weinname | Text | ja | Produkt-Titel (`set_name`) |
| Beschreibung | Einfacher klassischer Editor (`wp_editor`, reduzierte Toolbar) | nein | `set_description` (`wp_kses_post`) |
| Preis (CHF) | Zahl | ja | `set_regular_price` (`wc_format_decimal`) |
| Produktbild | WP-Medienauswahl (Button + Vorschau, Attachment-ID in Hidden-Feld) | nein | `set_image_id` |
| Kategorie | Dropdown (bestehende `product_cat`) | nein | `set_category_ids` |
| Rebsorte / Weingut / Region / Süsse / Jahrgang / Volumen | je Dropdown aus bestehenden `pa_*`-Termen **+ Textfeld „oder neu:"** | nein | globale Attribute `visible=false` |
| Histamingeprüft / Vegan / Alkoholfrei | 3 Checkboxen | nein | `product_tag` (`histamingeprueft`/`vegan`/`alkoholfrei`) |
| Status | Radio: Veröffentlichen / Entwurf | ja | `set_status` |

**Bewusst weggelassen (YAGNI):** Lagerverwaltung, Variationen, Versandmaße, Aktionspreis, Kurzbeschreibung, mehrere Bilder. Für solche Sonderfälle bleibt die WooCommerce-Standardseite als Fallback erreichbar.

**Attribut-Werte:** Dropdown zeigt bestehende Terme der jeweiligen `pa_*`-Taxonomie (`get_terms`). Freitext „oder neu:" legt bei Bedarf einen neuen Term an (`wp_insert_term`) und weist ihn zu — so bleiben Filter/Wein-Finder konsistent. Attribute werden als globale Taxonomie-Attribute mit `visible=false` gesetzt (deckungsgleich mit `product-facts.php`).

**WooCommerce-Gate:** Die Produkt-Maske erscheint/verarbeitet nur, wenn WooCommerce aktiv ist (`class_exists('WooCommerce')`); sonst freundlicher Hinweis.

## Artikel-Maske · Felder

| Feld | Eingabe | Pflicht | → Speicherung |
|---|---|---|---|
| Typ | Radio: Ratgeber / Weinlexikon | ja | Kategorie `ratgeber`/`weinlexikon` (automatisch — verhindert Kategorie-Fehler) |
| Titel | Text | ja | Post-Titel |
| Teaser / Auszug | Textarea | empfohlen | `post_excerpt` (erscheint in Übersichts-/A–Z-Karten) |
| Beitragsbild | WP-Medienauswahl | nein | Beitragsbild |
| Text | Einfacher klassischer Editor (`wp_editor`, Word-ähnlich) | nein | `post_content` (`wp_kses_post`) |
| Status | Radio: Veröffentlichen / Entwurf | ja | Post-Status |

**Inhalts-Speicherung:** Der klassische Editor liefert HTML. Dieses wird direkt als `post_content` gespeichert; WordPress rendert es im Theme korrekt (im Block-Editor erschiene es als klassischer/Freiform-Block). Bewusst KEINE Block-Markup-Generierung — schlicht und robust.

## Bearbeiten bestehender Inhalte

Zusätzliche Zeilen-Aktion **„Einfach bearbeiten"** in der Produkt-Liste (`product`) und Beitrags-Liste (`post`) via `post_row_actions` / `product`-Pendant → verlinkt auf die jeweilige Maske mit `?id=123`. Der Standard-„Bearbeiten"-Link (WooCommerce/Gutenberg) bleibt daneben bestehen (ergänzend).

Beim Bearbeiten lädt die Maske die vorhandenen Werte vor. Hinweis in der Artikel-Maske: Für alte, in Gutenberg mit vielen Blöcken gebaute Beiträge empfiehlt sich weiterhin der Standard-Editor (der klassische Editor zeigt sonst Block-Kommentar-HTML).

## Sicherheit

- **Nonces** (`wp_nonce_field` / `wp_verify_nonce`) auf jedem Formular.
- **Capability-Checks:** Produkt `current_user_can('edit_products')`, Artikel `current_user_can('edit_posts')`.
- **Eingaben sanitisieren:** `sanitize_text_field` (Titel/Textfelder), `wc_format_decimal` (Preis), `wp_kses_post` (Editor-Inhalte), `absint` (IDs/Attachment-IDs), Whitelist-Prüfung für Typ/Status/Kategorie.
- **Ausgaben escapen:** `esc_html` / `esc_attr` / `esc_url` durchgehend.

## Testing & Verifikation

Kein automatisiertes PHP-Test-Setup vorhanden → pragmatische Verifikation:

1. **Syntax:** `php -l` auf allen drei neuen Dateien (fehlerfrei).
2. **Deploy** auf `feinspitz.alpenmesh.de` via `node scripts/deploy/deploy-theme.mjs`.
3. **Smoke-Test Produkt:** über die Maske einen Test-Wein anlegen → prüfen:
   - Erscheint im Shop, korrekte Kategorie.
   - „Auf einen Blick"-Fakten-Tabelle zeigt die gewählten Attribute.
   - Flag-Badges korrekt.
   - Wein-Finder/Filter finden das Produkt über die Attribute.
   - Produktliste-Spalten (Rebsorte/Region/Süsse/Flags) korrekt gefüllt.
4. **Smoke-Test Artikel:** je einen Ratgeber- und Weinlexikon-Artikel anlegen → prüfen dass er in der jeweiligen Übersicht (Ratgeber-Grid / A–Z-Lexikon) mit Auszug erscheint.
5. **Bearbeiten:** „Einfach bearbeiten" an einem bestehenden Produkt/Artikel → Werte vorgeladen, Speichern aktualisiert korrekt.
6. **Regression:** Standard-WooCommerce-/Gutenberg-Seiten weiterhin unverändert erreichbar; Frontend unverändert.

## Nicht im Umfang (YAGNI)

- Verstecken/Deaktivieren der Standard-Screens oder Rollen-Umbau (bewusst „ergänzend").
- Lager, Variationen, Versand, Aktionspreise in der Produkt-Maske.
- Block-Markup-Generierung für Artikel.
- Zweisprachige Inhaltspflege in der Maske (nur DE; EN-Workflow bleibt wie bestehend über Skripte/Standard).
- Neue gettext-msgids.
