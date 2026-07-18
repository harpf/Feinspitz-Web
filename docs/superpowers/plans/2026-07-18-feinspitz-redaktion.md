# Feinspitz-Redaktion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zwei geführte Admin-Masken („Neues Produkt", „Neuer Artikel") im Feinspitz-Menü, die ergänzend zu WooCommerce/Gutenberg einen einfachen Weg für den nicht-technischen Betreiber bieten.

**Architecture:** Reiner Admin-PHP-Code im Theme, in drei neuen `inc/`-Dateien (via `glob inc/*.php` auto-geladen). Eine gemeinsame Basis-Datei liefert Menü-Registrierung, Feld-Renderer, Medien-Picker und Helfer; je eine Datei rendert und speichert Produkt bzw. Artikel. Formulare posten an `admin-post.php` (Post/Redirect/Get). Produkte werden über die WooCommerce-CRUD-API geschrieben, Artikel über die WP-Post-API.

**Tech Stack:** WordPress (Block-Theme „feinspitz"), WooCommerce CRUD (`WC_Product_Simple`, `WC_Product_Attribute`), PHP 7.4+, `wp.media` (JS), jQuery (im Admin verfügbar).

Spec: `docs/superpowers/specs/2026-07-18-feinspitz-redaktion-design.md`

## Global Constraints

- **i18n:** Admin-Strings als **deutsche String-Literale**, KEINE neuen gettext-msgids (der i18n-Build verlangt für jede msgid eine EN-Übersetzung). Konvention aus `inc/admin.php`.
- **Admin-only:** Jede neue Datei beginnt nach dem `ABSPATH`-Guard mit `if ( ! is_admin() ) { return; }`. Keine Frontend-Wirkung.
- **Wein-Attribute** sind globale Taxonomien `pa_weingut`, `pa_rebsorte`, `pa_jahrgang`, `pa_region`, `pa_suesse`, `pa_volumen`, gespeichert mit `visible=false` (deckungsgleich mit `inc/product-facts.php`).
- **Flags** sind `product_tag`-Terme mit den Slugs `histamingeprueft`, `vegan`, `alkoholfrei` — beim Setzen immer über die **Term-ID** auflösen (`get_term_by('slug', …)`), nie den Slug-String direkt an `wp_set_object_terms` geben (sonst wird ein neuer Term angelegt).
- **Artikel-Kategorien:** Slugs `ratgeber` und `weinlexikon` (Taxonomie `category`).
- **Sicherheit:** Jedes Formular mit `wp_nonce_field` + `check_admin_referer`; Capability-Checks (`edit_products` / `edit_posts`); Eingaben sanitisieren (`sanitize_text_field`, `sanitize_textarea_field`, `wc_format_decimal`, `wp_kses_post`, `absint`, Whitelist via `feinspitz_forms_choice`); Ausgaben escapen (`esc_html`/`esc_attr`/`esc_url`).
- **Verifikations-Ansatz:** Das Projekt hat **kein automatisiertes PHP-Test-Setup** (nur Node-Skripte, kein phpcs; HTTP-only-Deploy ohne WP-Testumgebung). Verifikation je Task = `php -l` (Syntax) + Selbstprüfung gegen die angegebene Checkliste. Ein finaler Task deployt und führt einen manuellen Smoke-Test auf `feinspitz.alpenmesh.de` durch. Es wird **bewusst kein** Test-Framework eingeführt (Konsistenz mit dem Bestand, YAGNI).
- **Deploy:** `node scripts/deploy/deploy-theme.mjs` (HTTP-only). Nur `theme/feinspitz/**` wird ausgeliefert.

---

### Task 1: Gemeinsame Basis (`inc/admin-forms-shared.php`)

**Files:**
- Create: `theme/feinspitz/inc/admin-forms-shared.php`

**Interfaces:**
- Consumes: `FEINSPITZ_ADMIN_SLUG` (Konstante aus `inc/admin.php`, zur Hook-Zeit definiert).
- Produces (von Task 2–4 genutzt):
  - Konstanten `FEINSPITZ_PRODUCT_FORM_SLUG = 'feinspitz-produkt-neu'`, `FEINSPITZ_ARTICLE_FORM_SLUG = 'feinspitz-artikel-neu'`
  - `feinspitz_forms_choice( $value, array $allowed, string $default ): string`
  - `feinspitz_forms_notice(): void`
  - `feinspitz_forms_open( string $action, string $nonce_action, array $hidden = array() ): void`
  - `feinspitz_forms_close(): void` (schliesst `</form>`)
  - `feinspitz_forms_text( string $name, string $label, string $value = '', bool $required = false, string $type = 'text' ): void`
  - `feinspitz_forms_textarea( string $name, string $label, string $value = '', int $rows = 3 ): void`
  - `feinspitz_forms_select( string $name, string $label, array $options, string $selected = '', string $prompt = '— bitte wählen —' ): void` (`$options` = `id => label`)
  - `feinspitz_forms_radio( string $name, string $label, array $options, string $selected = '' ): void` (`$options` = `value => label`)
  - `feinspitz_forms_checkbox( string $name, string $label, bool $checked = false ): void`
  - `feinspitz_forms_image( string $name, string $label, int $attachment_id = 0 ): void`
  - `feinspitz_forms_editor( string $name, string $content = '' ): void`
  - `feinspitz_forms_redirect( string $slug, array $args ): void` (redirect zur Formularseite, dann `exit`)
  - Render-Callbacks `feinspitz_product_form_render` / `feinspitz_article_form_render` werden hier per `add_submenu_page` registriert, aber in Task 2/3 definiert.

- [ ] **Step 1: Datei-Kopf, Guards, Konstanten, Menü-Registrierung**

Create `theme/feinspitz/inc/admin-forms-shared.php`:

```php
<?php
/**
 * Feinspitz · Redaktion — gemeinsame Basis der geführten Masken.
 *
 * Liefert Menü-Registrierung, Feld-Renderer, Medien-Picker und Helfer für die
 * beiden geführten Formular-Seiten (inc/admin-product-form.php,
 * inc/admin-article-form.php). Reiner Admin-Code (is_admin()-Gate), keine
 * Frontend-Wirkung. Deutsche String-Literale (keine neuen gettext-msgids).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

if ( ! defined( 'FEINSPITZ_PRODUCT_FORM_SLUG' ) ) {
	define( 'FEINSPITZ_PRODUCT_FORM_SLUG', 'feinspitz-produkt-neu' );
}
if ( ! defined( 'FEINSPITZ_ARTICLE_FORM_SLUG' ) ) {
	define( 'FEINSPITZ_ARTICLE_FORM_SLUG', 'feinspitz-artikel-neu' );
}

/**
 * Untermenü-Seiten am Feinspitz-Menü registrieren. Die Render-Callbacks liegen
 * in inc/admin-product-form.php bzw. inc/admin-article-form.php (zur Hook-Zeit
 * definiert). Parent-Slug aus inc/admin.php mit sicherem Fallback.
 */
add_action( 'admin_menu', function () {
	$parent = defined( 'FEINSPITZ_ADMIN_SLUG' ) ? FEINSPITZ_ADMIN_SLUG : 'feinspitz';

	add_submenu_page(
		$parent,
		'Neues Produkt',
		'Neues Produkt',
		'edit_products',
		FEINSPITZ_PRODUCT_FORM_SLUG,
		'feinspitz_product_form_render'
	);
	add_submenu_page(
		$parent,
		'Neuer Artikel',
		'Neuer Artikel',
		'edit_posts',
		FEINSPITZ_ARTICLE_FORM_SLUG,
		'feinspitz_article_form_render'
	);
}, 20 );
```

- [ ] **Step 2: Whitelist-Helfer, Notice, Redirect**

Append:

```php
/**
 * Einen Wert gegen eine Whitelist prüfen (für Typ/Status/Kategorie-Slugs).
 *
 * @param mixed  $value   Roher Wert.
 * @param array  $allowed Erlaubte Werte.
 * @param string $default Rückfall.
 * @return string
 */
function feinspitz_forms_choice( $value, array $allowed, $default ) {
	$value = is_string( $value ) ? sanitize_key( $value ) : '';
	return in_array( $value, $allowed, true ) ? $value : $default;
}

/**
 * Admin-Notice aus ?feinspitz_notice rendern (nach Redirect).
 */
function feinspitz_forms_notice() {
	if ( empty( $_GET['feinspitz_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$notice = sanitize_key( wp_unslash( $_GET['feinspitz_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$map    = array(
		'saved' => array( 'success', 'Gespeichert.' ),
		'error' => array( 'error', 'Es ist ein Fehler aufgetreten. Bitte prüfen Sie Ihre Eingaben.' ),
		'nowc'  => array( 'error', 'WooCommerce ist nicht aktiv — Produkte können derzeit nicht angelegt werden.' ),
	);
	if ( ! isset( $map[ $notice ] ) ) {
		return;
	}
	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $map[ $notice ][0] ),
		esc_html( $map[ $notice ][1] )
	);
}

/**
 * Zur Formularseite zurückleiten (Post/Redirect/Get) und beenden.
 *
 * @param string $slug Seiten-Slug (admin.php?page=…).
 * @param array  $args Query-Argumente (z. B. id, feinspitz_notice).
 */
function feinspitz_forms_redirect( $slug, array $args ) {
	$args['page'] = $slug;
	wp_safe_redirect( admin_url( 'admin.php?' . http_build_query( $args ) ) );
	exit;
}
```

- [ ] **Step 3: Formular-Rahmen + einfache Felder (open/close/text/textarea/radio/checkbox/select)**

Append:

```php
/**
 * Formular öffnen: <form> an admin-post.php + versteckte action/nonce/Hidden-Felder.
 *
 * @param string $action       admin_post-Action-Suffix (ohne „admin_post_").
 * @param string $nonce_action Nonce-Aktion.
 * @param array  $hidden       Zusätzliche versteckte Felder (name => value).
 */
function feinspitz_forms_open( $action, $nonce_action, array $hidden = array() ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="feinspitz-form">';
	echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( $nonce_action );
	foreach ( $hidden as $key => $value ) {
		echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '">';
	}
}

/**
 * Formular schliessen.
 */
function feinspitz_forms_close() {
	echo '</form>';
}

/**
 * Text-/Zahlfeld.
 */
function feinspitz_forms_text( $name, $label, $value = '', $required = false, $type = 'text' ) {
	printf(
		'<p class="feinspitz-field"><label for="%1$s">%2$s%3$s</label>'
		. '<input type="%4$s" id="%1$s" name="%1$s" value="%5$s" class="regular-text"%6$s></p>',
		esc_attr( $name ),
		esc_html( $label ),
		$required ? ' <span class="feinspitz-req">*</span>' : '',
		esc_attr( $type ),
		esc_attr( (string) $value ),
		$required ? ' required' : ''
	);
}

/**
 * Mehrzeiliges Textfeld.
 */
function feinspitz_forms_textarea( $name, $label, $value = '', $rows = 3 ) {
	printf(
		'<p class="feinspitz-field"><label for="%1$s">%2$s</label>'
		. '<textarea id="%1$s" name="%1$s" rows="%3$d" class="large-text">%4$s</textarea></p>',
		esc_attr( $name ),
		esc_html( $label ),
		(int) $rows,
		esc_textarea( (string) $value )
	);
}

/**
 * Radio-Gruppe.
 *
 * @param array $options value => label.
 */
function feinspitz_forms_radio( $name, $label, array $options, $selected = '' ) {
	echo '<fieldset class="feinspitz-field"><legend>' . esc_html( $label ) . '</legend>';
	foreach ( $options as $value => $text ) {
		printf(
			'<label class="feinspitz-radio"><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label>',
			esc_attr( $name ),
			esc_attr( (string) $value ),
			checked( (string) $value, (string) $selected, false ),
			esc_html( $text )
		);
	}
	echo '</fieldset>';
}

/**
 * Einzelne Checkbox.
 */
function feinspitz_forms_checkbox( $name, $label, $checked = false ) {
	printf(
		'<p class="feinspitz-field feinspitz-field--check"><label><input type="checkbox" name="%1$s" value="1"%2$s> %3$s</label></p>',
		esc_attr( $name ),
		checked( (bool) $checked, true, false ),
		esc_html( $label )
	);
}

/**
 * Select mit optionalem „bitte wählen".
 *
 * @param array $options id => label.
 */
function feinspitz_forms_select( $name, $label, array $options, $selected = '', $prompt = '— bitte wählen —' ) {
	printf( '<p class="feinspitz-field"><label for="%1$s">%2$s</label><select id="%1$s" name="%1$s">', esc_attr( $name ), esc_html( $label ) );
	if ( '' !== $prompt ) {
		printf( '<option value="">%s</option>', esc_html( $prompt ) );
	}
	foreach ( $options as $value => $text ) {
		printf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( (string) $value ),
			selected( (string) $value, (string) $selected, false ),
			esc_html( $text )
		);
	}
	echo '</select></p>';
}
```

- [ ] **Step 4: Bildwähler + Editor-Feld**

Append:

```php
/**
 * Bildauswahl über die WP-Medienbibliothek (Button + Vorschau + Hidden-ID).
 * Das JS liegt in feinspitz_forms_enqueue() (nur auf den Formular-Seiten geladen).
 */
function feinspitz_forms_image( $name, $label, $attachment_id = 0 ) {
	$attachment_id = absint( $attachment_id );
	$preview       = '';
	if ( $attachment_id ) {
		$img = wp_get_attachment_image( $attachment_id, 'thumbnail' );
		if ( $img ) {
			$preview = $img;
		}
	}
	printf(
		'<div class="feinspitz-field feinspitz-media"><label>%1$s</label>'
		. '<div class="feinspitz-media-preview">%2$s</div>'
		. '<input type="hidden" class="feinspitz-media-id" name="%3$s" value="%4$d">'
		. '<button type="button" class="button feinspitz-media-pick">Bild wählen</button> '
		. '<button type="button" class="button-link feinspitz-media-remove"%5$s>Entfernen</button></div>',
		esc_html( $label ),
		$preview, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wp_get_attachment_image ist bereits sicher.
		esc_attr( $name ),
		$attachment_id,
		$attachment_id ? '' : ' style="display:none"'
	);
}

/**
 * Einfacher (klassischer) Editor mit reduzierter Toolbar.
 *
 * @param string $name    Textarea-Name (Formularfeld).
 * @param string $content Vorbelegter HTML-Inhalt.
 */
function feinspitz_forms_editor( $name, $content = '' ) {
	$editor_id = preg_replace( '/[^a-z0-9]/', '', strtolower( $name ) );
	wp_editor(
		$content,
		$editor_id,
		array(
			'textarea_name' => $name,
			'media_buttons' => false,
			'teeny'         => true,
			'textarea_rows' => 12,
			'quicktags'     => false,
		)
	);
}
```

- [ ] **Step 5: Medien-Picker-JS + gescoptes CSS (nur auf den Formular-Seiten)**

Append:

```php
/**
 * Assets nur auf den beiden Formular-Seiten laden: Medienbibliothek + Picker-JS.
 *
 * @param string $hook Aktueller Admin-Hook-Suffix.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$pages = array(
		'feinspitz_page_' . FEINSPITZ_PRODUCT_FORM_SLUG,
		'feinspitz_page_' . FEINSPITZ_ARTICLE_FORM_SLUG,
	);
	if ( ! in_array( $hook, $pages, true ) ) {
		return;
	}

	wp_enqueue_media();

	$js = <<<'JS'
(function($){
  $(document).on('click','.feinspitz-media-pick',function(e){
    e.preventDefault();
    var $wrap=$(this).closest('.feinspitz-media');
    var frame=wp.media({title:'Bild wählen',button:{text:'Übernehmen'},multiple:false});
    frame.on('select',function(){
      var a=frame.state().get('selection').first().toJSON();
      $wrap.find('.feinspitz-media-id').val(a.id);
      var url=(a.sizes&&a.sizes.thumbnail)?a.sizes.thumbnail.url:a.url;
      $wrap.find('.feinspitz-media-preview').html('<img src="'+url+'" alt="">');
      $wrap.find('.feinspitz-media-remove').show();
    });
    frame.open();
  });
  $(document).on('click','.feinspitz-media-remove',function(e){
    e.preventDefault();
    var $wrap=$(this).closest('.feinspitz-media');
    $wrap.find('.feinspitz-media-id').val('');
    $wrap.find('.feinspitz-media-preview').empty();
    $(this).hide();
  });
})(jQuery);
JS;
	wp_add_inline_script( 'jquery-core', $js );
} );

/**
 * Gescoptes Formular-CSS (nur auf den Redaktions-Seiten).
 */
add_action( 'admin_head', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return;
	}
	$ids = array(
		'feinspitz_page_' . FEINSPITZ_PRODUCT_FORM_SLUG,
		'feinspitz_page_' . FEINSPITZ_ARTICLE_FORM_SLUG,
	);
	if ( ! in_array( $screen->id, $ids, true ) ) {
		return;
	}
	?>
	<style>
	.feinspitz-form{max-width:720px;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:1.25rem 1.5rem;margin-top:1rem}
	.feinspitz-form h2{margin:1.5rem 0 .5rem;font-size:1.05rem;color:#7b1f2b}
	.feinspitz-form h2:first-of-type{margin-top:0}
	.feinspitz-field{margin:0 0 1rem}
	.feinspitz-field > label,.feinspitz-field > legend{display:block;font-weight:600;margin-bottom:.25rem}
	.feinspitz-field input.regular-text,.feinspitz-field select{width:100%;max-width:100%}
	.feinspitz-field textarea{width:100%}
	.feinspitz-field--check label{font-weight:400}
	.feinspitz-radio{display:inline-block;margin-right:1.25rem;font-weight:400}
	.feinspitz-req{color:#b32d2e}
	.feinspitz-attr-row{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;align-items:end}
	.feinspitz-media-preview img{max-width:120px;height:auto;display:block;margin:.25rem 0;border:1px solid #dcdcde;border-radius:6px}
	.feinspitz-submit{margin-top:1.25rem;display:flex;gap:.5rem}
	</style>
	<?php
} );
```

- [ ] **Step 6: Syntax prüfen**

Run: `php -l theme/feinspitz/inc/admin-forms-shared.php`
Expected: `No syntax errors detected in theme/feinspitz/inc/admin-forms-shared.php`

- [ ] **Step 7: Selbstprüfung gegen Checkliste**

Prüfe manuell: (a) alle in „Produces" gelisteten Funktionen/Konstanten existieren mit exakt diesen Namen/Signaturen; (b) alle Ausgaben sind escaped (ausser dem bewusst kommentierten `wp_get_attachment_image`); (c) `is_admin()`-Gate und `ABSPATH`-Guard vorhanden; (d) keine `__()/_e()`-Aufrufe mit neuen Strings (nur deutsche Literale).

- [ ] **Step 8: Commit**

```bash
git add theme/feinspitz/inc/admin-forms-shared.php
git commit -m "feat(admin): Redaktion — gemeinsame Basis (Menue, Felder, Medien-Picker)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Produkt-Maske (`inc/admin-product-form.php`)

**Files:**
- Create: `theme/feinspitz/inc/admin-product-form.php`

**Interfaces:**
- Consumes: alle `feinspitz_forms_*`-Helfer + `FEINSPITZ_PRODUCT_FORM_SLUG` aus Task 1; WooCommerce (`WC_Product_Simple`, `WC_Product_Attribute`, `wc_get_product`, `wc_format_decimal`, `wc_attribute_taxonomy_id_by_name`).
- Produces: `feinspitz_product_form_render(): void` (Menü-Callback aus Task 1); `add_action('admin_post_feinspitz_save_product', 'feinspitz_product_form_save')`.

- [ ] **Step 1: Datei-Kopf, Guards, Attribut-Taxonomie-Liste**

Create `theme/feinspitz/inc/admin-product-form.php`:

```php
<?php
/**
 * Feinspitz · Redaktion — geführte Produkt-Maske.
 *
 * Rendert ein einfaches Formular zum Anlegen/Bearbeiten eines Weins und
 * speichert es über die WooCommerce-CRUD-API. Ergänzt die WooCommerce-
 * Standardseite (die als Fallback erreichbar bleibt). Reiner Admin-Code.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

/**
 * Wein-Attribut-Taxonomien in Anzeige-Reihenfolge (mit deutschem Label).
 * Schlüssel = Feld-Suffix (ohne pa_), Wert = [Taxonomie, Label].
 *
 * @return array<string,array{0:string,1:string}>
 */
function feinspitz_product_attributes() {
	return array(
		'weingut'  => array( 'pa_weingut', 'Weingut' ),
		'rebsorte' => array( 'pa_rebsorte', 'Rebsorte' ),
		'jahrgang' => array( 'pa_jahrgang', 'Jahrgang' ),
		'region'   => array( 'pa_region', 'Region' ),
		'suesse'   => array( 'pa_suesse', 'Süsse' ),
		'volumen'  => array( 'pa_volumen', 'Volumen' ),
	);
}

/**
 * Flag-Slugs (product_tag) mit deutschem Label.
 *
 * @return array<string,string>
 */
function feinspitz_product_flags() {
	return array(
		'histamingeprueft' => 'Histamingeprüft',
		'vegan'            => 'Vegan',
		'alkoholfrei'      => 'Alkoholfrei',
	);
}
```

- [ ] **Step 2: Render-Funktion**

Append:

```php
/**
 * Produkt-Maske rendern (leer = neu, ?id=… = bearbeiten).
 */
function feinspitz_product_form_render() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}

	echo '<div class="wrap"><h1>Neues Produkt</h1>';
	feinspitz_forms_notice();

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		echo '<p>WooCommerce ist nicht aktiv — Produkte können derzeit nicht angelegt werden.</p></div>';
		return;
	}

	$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$product = $id ? wc_get_product( $id ) : null;

	// Vorbelegung.
	$name        = $product ? $product->get_name() : '';
	$description = $product ? $product->get_description() : '';
	$price       = $product ? $product->get_regular_price() : '';
	$image_id    = $product ? (int) $product->get_image_id() : 0;
	$cats        = $product ? $product->get_category_ids() : array();
	$cat_id      = $cats ? (string) $cats[0] : '';
	$status      = $product ? $product->get_status() : 'publish';

	if ( $product ) {
		echo '<p>Sie bearbeiten: <strong>' . esc_html( $name ) . '</strong></p>';
	}

	feinspitz_forms_open( 'feinspitz_save_product', 'feinspitz_save_product', array( 'feinspitz_id' => $id ) );

	echo '<h2>Wein</h2>';
	feinspitz_forms_text( 'feinspitz_name', 'Weinname', $name, true );
	feinspitz_forms_text( 'feinspitz_price', 'Preis (CHF)', (string) $price, true, 'number' );

	echo '<h2>Beschreibung</h2>';
	feinspitz_forms_editor( 'feinspitz_description', $description );

	echo '<h2>Bild</h2>';
	feinspitz_forms_image( 'feinspitz_image', 'Produktbild', $image_id );

	echo '<h2>Einordnung</h2>';
	// Kategorie-Dropdown.
	$cat_options = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
		if ( ! is_wp_error( $term ) ) {
			$cat_options[ (string) $term->term_id ] = $term->name;
		}
	}
	feinspitz_forms_select( 'feinspitz_category', 'Kategorie', $cat_options, $cat_id );

	// Wein-Attribute: je Select aus vorhandenen Termen + Freitext „oder neu".
	foreach ( feinspitz_product_attributes() as $suffix => $meta ) {
		list( $taxonomy, $label ) = $meta;
		$options  = array();
		$selected = '';
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
			if ( ! is_wp_error( $term ) ) {
				$options[ (string) $term->term_id ] = $term->name;
			}
		}
		if ( $product ) {
			$current = get_the_terms( $product->get_id(), $taxonomy );
			if ( $current && ! is_wp_error( $current ) ) {
				$selected = (string) $current[0]->term_id;
			}
		}
		echo '<div class="feinspitz-attr-row">';
		feinspitz_forms_select( 'feinspitz_attr_' . $suffix, $label, $options, $selected );
		feinspitz_forms_text( 'feinspitz_attr_' . $suffix . '_new', 'oder neu:', '' );
		echo '</div>';
	}

	echo '<h2>Merkmale</h2>';
	foreach ( feinspitz_product_flags() as $slug => $label ) {
		$checked = $product ? has_term( $slug, 'product_tag', $product->get_id() ) : false;
		feinspitz_forms_checkbox( 'feinspitz_flag_' . $slug, $label, $checked );
	}

	echo '<h2>Veröffentlichung</h2>';
	feinspitz_forms_radio(
		'feinspitz_status',
		'Status',
		array( 'publish' => 'Veröffentlichen', 'draft' => 'Als Entwurf speichern' ),
		$status === 'draft' ? 'draft' : 'publish'
	);

	echo '<div class="feinspitz-submit"><button type="submit" class="button button-primary button-hero">Speichern</button></div>';
	feinspitz_forms_close();
	echo '</div>';
}
```

- [ ] **Step 3: Attribut-Builder + Flag-Anwendung**

Append:

```php
/**
 * Attribut-Objekte aus dem POST bauen (Select-Term-ID oder neuer Freitext-Term).
 *
 * @param array $post $_POST (unslashed durch Aufrufer).
 * @return WC_Product_Attribute[]
 */
function feinspitz_product_build_attributes( array $post ) {
	$attributes = array();
	$position   = 0;

	foreach ( feinspitz_product_attributes() as $suffix => $meta ) {
		$taxonomy = $meta[0];
		$field    = 'feinspitz_attr_' . $suffix;

		$term_id = isset( $post[ $field ] ) ? absint( $post[ $field ] ) : 0;
		$new_val = isset( $post[ $field . '_new' ] ) ? sanitize_text_field( wp_unslash( $post[ $field . '_new' ] ) ) : '';

		// Freitext hat Vorrang: bestehenden Term nehmen oder neu anlegen.
		if ( '' !== $new_val ) {
			$existing = term_exists( $new_val, $taxonomy );
			if ( $existing && ! is_wp_error( $existing ) ) {
				$term_id = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
			} else {
				$created = wp_insert_term( $new_val, $taxonomy );
				if ( ! is_wp_error( $created ) ) {
					$term_id = (int) $created['term_id'];
				}
			}
		}

		if ( ! $term_id ) {
			continue;
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term_id ) );
		$attribute->set_position( $position++ );
		$attribute->set_visible( false );
		$attribute->set_variation( false );
		$attributes[] = $attribute;
	}

	return $attributes;
}

/**
 * Flags (product_tag) setzen — immer über Term-ID (Slug-String würde einen neuen
 * Term anlegen). Fehlende Häkchen entfernen das Tag (append=false).
 *
 * @param int   $product_id Produkt-ID.
 * @param array $post       $_POST.
 */
function feinspitz_product_apply_flags( $product_id, array $post ) {
	$assign = array();
	foreach ( array_keys( feinspitz_product_flags() ) as $slug ) {
		if ( ! empty( $post[ 'feinspitz_flag_' . $slug ] ) ) {
			$term = get_term_by( 'slug', $slug, 'product_tag' );
			if ( $term ) {
				$assign[] = (int) $term->term_id;
			}
		}
	}
	wp_set_object_terms( $product_id, $assign, 'product_tag', false );
}
```

- [ ] **Step 4: Save-Handler**

Append:

```php
/**
 * Produkt speichern (admin-post.php).
 */
add_action( 'admin_post_feinspitz_save_product', 'feinspitz_product_form_save' );
function feinspitz_product_form_save() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	check_admin_referer( 'feinspitz_save_product' );

	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'feinspitz_notice' => 'nowc' ) );
	}

	$id      = isset( $_POST['feinspitz_id'] ) ? absint( $_POST['feinspitz_id'] ) : 0;
	$product = $id ? wc_get_product( $id ) : null;
	if ( ! $product ) {
		$product = new WC_Product_Simple();
	}

	$name = isset( $_POST['feinspitz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['feinspitz_name'] ) ) : '';
	if ( '' === $name ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	$product->set_name( $name );
	$product->set_description( isset( $_POST['feinspitz_description'] ) ? wp_kses_post( wp_unslash( $_POST['feinspitz_description'] ) ) : '' );
	$product->set_regular_price( isset( $_POST['feinspitz_price'] ) ? wc_format_decimal( wp_unslash( $_POST['feinspitz_price'] ) ) : '' );

	$image_id = isset( $_POST['feinspitz_image'] ) ? absint( $_POST['feinspitz_image'] ) : 0;
	$product->set_image_id( $image_id ? $image_id : '' );

	$cat_id = isset( $_POST['feinspitz_category'] ) ? absint( $_POST['feinspitz_category'] ) : 0;
	$product->set_category_ids( $cat_id ? array( $cat_id ) : array() );

	$status = feinspitz_forms_choice( isset( $_POST['feinspitz_status'] ) ? wp_unslash( $_POST['feinspitz_status'] ) : '', array( 'publish', 'draft' ), 'draft' );
	$product->set_status( $status );

	$product->set_attributes( feinspitz_product_build_attributes( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing — Nonce oben geprüft.

	$product_id = $product->save();
	if ( ! $product_id ) {
		feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	feinspitz_product_apply_flags( $product_id, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	feinspitz_forms_redirect( FEINSPITZ_PRODUCT_FORM_SLUG, array( 'id' => $product_id, 'feinspitz_notice' => 'saved' ) );
}
```

- [ ] **Step 5: Syntax prüfen**

Run: `php -l theme/feinspitz/inc/admin-product-form.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Selbstprüfung gegen Checkliste**

Prüfe: (a) `feinspitz_product_form_render` und `admin_post_feinspitz_save_product` existieren; (b) Attribute werden mit `set_visible(false)` und `set_id(wc_attribute_taxonomy_id_by_name(...))` gebaut; (c) Flags über Term-ID (nicht Slug-String) gesetzt; (d) Nonce + Capability geprüft; (e) alle Eingaben sanitisiert, alle Ausgaben escaped; (f) Redirect nach jedem Pfad (kein Weiterlaufen nach `feinspitz_forms_redirect`, da es `exit`).

- [ ] **Step 7: Commit**

```bash
git add theme/feinspitz/inc/admin-product-form.php
git commit -m "feat(admin): gefuehrte Produkt-Maske (anlegen/bearbeiten via WC-CRUD)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Artikel-Maske (`inc/admin-article-form.php`)

**Files:**
- Create: `theme/feinspitz/inc/admin-article-form.php`

**Interfaces:**
- Consumes: `feinspitz_forms_*`-Helfer + `FEINSPITZ_ARTICLE_FORM_SLUG` aus Task 1.
- Produces: `feinspitz_article_form_render(): void`; `add_action('admin_post_feinspitz_save_article', 'feinspitz_article_form_save')`.

- [ ] **Step 1: Datei-Kopf, Guards, Typ-Liste**

Create `theme/feinspitz/inc/admin-article-form.php`:

```php
<?php
/**
 * Feinspitz · Redaktion — geführte Artikel-Maske (Ratgeber / Weinlexikon).
 *
 * Rendert ein einfaches Formular zum Schreiben/Bearbeiten eines Beitrags und
 * speichert es über die WP-Post-API. Die Kategorie wird automatisch aus dem
 * gewählten Typ gesetzt (kein Kategorie-Fehler mehr). Ergänzt den Gutenberg-
 * Editor (der als Fallback erreichbar bleibt). Reiner Admin-Code.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

/**
 * Artikel-Typen: Kategorie-Slug => deutsches Label.
 *
 * @return array<string,string>
 */
function feinspitz_article_types() {
	return array(
		'ratgeber'    => 'Ratgeber',
		'weinlexikon' => 'Weinlexikon',
	);
}
```

- [ ] **Step 2: Render-Funktion**

Append:

```php
/**
 * Artikel-Maske rendern (leer = neu, ?id=… = bearbeiten).
 */
function feinspitz_article_form_render() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}

	echo '<div class="wrap"><h1>Neuer Artikel</h1>';
	feinspitz_forms_notice();

	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post = $id ? get_post( $id ) : null;

	$title   = $post ? $post->post_title : '';
	$excerpt = $post ? $post->post_excerpt : '';
	$content = $post ? $post->post_content : '';
	$image   = $post ? (int) get_post_thumbnail_id( $post ) : 0;
	$status  = $post ? $post->post_status : 'publish';

	// Aktuellen Typ aus zugewiesener Kategorie ableiten.
	$type = 'ratgeber';
	if ( $post ) {
		foreach ( array_keys( feinspitz_article_types() ) as $slug ) {
			if ( has_term( $slug, 'category', $post ) ) {
				$type = $slug;
				break;
			}
		}
	}

	if ( $post ) {
		echo '<p>Sie bearbeiten: <strong>' . esc_html( $title ) . '</strong></p>';
		if ( strpos( $content, '<!-- wp:' ) !== false ) {
			echo '<div class="notice notice-info inline"><p>Dieser Beitrag wurde mit dem Block-Editor erstellt. '
				. 'Für komplexe Layouts empfiehlt sich weiterhin der Standard-Editor.</p></div>';
		}
	}

	feinspitz_forms_open( 'feinspitz_save_article', 'feinspitz_save_article', array( 'feinspitz_id' => $id ) );

	echo '<h2>Art des Artikels</h2>';
	feinspitz_forms_radio( 'feinspitz_type', 'Typ', feinspitz_article_types(), $type );

	echo '<h2>Inhalt</h2>';
	feinspitz_forms_text( 'feinspitz_title', 'Titel', $title, true );
	feinspitz_forms_textarea( 'feinspitz_excerpt', 'Teaser / Kurzbeschreibung (erscheint in der Übersicht)', $excerpt, 3 );

	echo '<h2>Beitragsbild</h2>';
	feinspitz_forms_image( 'feinspitz_image', 'Bild', $image );

	echo '<h2>Text</h2>';
	feinspitz_forms_editor( 'feinspitz_content', $content );

	echo '<h2>Veröffentlichung</h2>';
	feinspitz_forms_radio(
		'feinspitz_status',
		'Status',
		array( 'publish' => 'Veröffentlichen', 'draft' => 'Als Entwurf speichern' ),
		$status === 'draft' ? 'draft' : 'publish'
	);

	echo '<div class="feinspitz-submit"><button type="submit" class="button button-primary button-hero">Speichern</button></div>';
	feinspitz_forms_close();
	echo '</div>';
}
```

- [ ] **Step 3: Save-Handler**

Append:

```php
/**
 * Artikel speichern (admin-post.php).
 */
add_action( 'admin_post_feinspitz_save_article', 'feinspitz_article_form_save' );
function feinspitz_article_form_save() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	check_admin_referer( 'feinspitz_save_article' );

	$id      = isset( $_POST['feinspitz_id'] ) ? absint( $_POST['feinspitz_id'] ) : 0;
	$type    = feinspitz_forms_choice( isset( $_POST['feinspitz_type'] ) ? wp_unslash( $_POST['feinspitz_type'] ) : '', array_keys( feinspitz_article_types() ), 'ratgeber' );
	$title   = isset( $_POST['feinspitz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['feinspitz_title'] ) ) : '';
	$excerpt = isset( $_POST['feinspitz_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feinspitz_excerpt'] ) ) : '';
	$content = isset( $_POST['feinspitz_content'] ) ? wp_kses_post( wp_unslash( $_POST['feinspitz_content'] ) ) : '';
	$status  = feinspitz_forms_choice( isset( $_POST['feinspitz_status'] ) ? wp_unslash( $_POST['feinspitz_status'] ) : '', array( 'publish', 'draft' ), 'draft' );
	$image   = isset( $_POST['feinspitz_image'] ) ? absint( $_POST['feinspitz_image'] ) : 0;

	if ( '' === $title ) {
		feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	$postarr = array(
		'post_type'    => 'post',
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => $status,
	);

	if ( $id ) {
		$postarr['ID'] = $id;
		$post_id       = wp_update_post( $postarr, true );
	} else {
		$post_id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $id, 'feinspitz_notice' => 'error' ) );
	}

	// Kategorie aus Typ (über Term-ID).
	$term = get_term_by( 'slug', $type, 'category' );
	if ( $term ) {
		wp_set_post_terms( $post_id, array( (int) $term->term_id ), 'category', false );
	}

	// Beitragsbild.
	if ( $image ) {
		set_post_thumbnail( $post_id, $image );
	} else {
		delete_post_thumbnail( $post_id );
	}

	// Polylang: neue Beiträge der Standardsprache DE zuordnen.
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, 'de' );
	}

	feinspitz_forms_redirect( FEINSPITZ_ARTICLE_FORM_SLUG, array( 'id' => $post_id, 'feinspitz_notice' => 'saved' ) );
}
```

- [ ] **Step 4: Syntax prüfen**

Run: `php -l theme/feinspitz/inc/admin-article-form.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Selbstprüfung gegen Checkliste**

Prüfe: (a) `feinspitz_article_form_render` + `admin_post_feinspitz_save_article` existieren; (b) Kategorie über Term-ID aus `$type` gesetzt; (c) `$type`/`$status` per `feinspitz_forms_choice` whitelisted; (d) Nonce + Capability; (e) Sanitizing/Escaping durchgehend; (f) Polylang-Aufruf `function_exists`-gegatet.

- [ ] **Step 6: Commit**

```bash
git add theme/feinspitz/inc/admin-article-form.php
git commit -m "feat(admin): gefuehrte Artikel-Maske (Ratgeber/Weinlexikon, Kategorie automatisch)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: „Einfach bearbeiten"-Zeilenaktionen + Dashboard-Verlinkung

**Files:**
- Create: `theme/feinspitz/inc/admin-forms-rowlinks.php`
- Modify: `theme/feinspitz/inc/admin.php` (Dashboard-Karten + Widget-Links auf die neuen Masken umbiegen)

**Interfaces:**
- Consumes: `FEINSPITZ_PRODUCT_FORM_SLUG`, `FEINSPITZ_ARTICLE_FORM_SLUG` aus Task 1.
- Produces: keine (nur Hooks + geänderte URLs).

- [ ] **Step 1: Zeilenaktionen anlegen**

Create `theme/feinspitz/inc/admin-forms-rowlinks.php`:

```php
<?php
/**
 * Feinspitz · Redaktion — „Einfach bearbeiten"-Zeilenaktionen.
 *
 * Ergänzt die Produkt- und Beitragsliste um einen Link in die jeweilige
 * geführte Feinspitz-Maske. Der Standard-„Bearbeiten"-Link bleibt bestehen.
 * Reiner Admin-Code.
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

/**
 * „Einfach bearbeiten" an Produkt- und Beitragszeilen anhängen.
 *
 * @param array   $actions Vorhandene Zeilenaktionen.
 * @param WP_Post $post    Aktueller Beitrag/Produkt.
 * @return array
 */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'post' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
		$url                       = admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG . '&id=' . $post->ID );
		$actions['feinspitz_edit'] = sprintf( '<a href="%s">Einfach bearbeiten</a>', esc_url( $url ) );
	}
	if ( 'product' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
		$url                       = admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG . '&id=' . $post->ID );
		$actions['feinspitz_edit'] = sprintf( '<a href="%s">Einfach bearbeiten</a>', esc_url( $url ) );
	}
	return $actions;
}, 10, 2 );
```

- [ ] **Step 2: Syntax prüfen**

Run: `php -l theme/feinspitz/inc/admin-forms-rowlinks.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Dashboard-Karten auf die neuen Masken umbiegen**

In `theme/feinspitz/inc/admin.php` die beiden Zeilen in `feinspitz_admin_render_dashboard()` (aktuell ~Zeile 123–124) ersetzen.

Alt:
```php
	$new_product = admin_url( 'post-new.php?post_type=product' );
	$new_post    = admin_url( 'post-new.php?post_type=post' );
```
Neu:
```php
	$new_product = defined( 'FEINSPITZ_PRODUCT_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG ) : admin_url( 'post-new.php?post_type=product' );
	$new_post    = defined( 'FEINSPITZ_ARTICLE_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG ) : admin_url( 'post-new.php?post_type=post' );
```

- [ ] **Step 4: Übersichts-Widget-Links umbiegen**

In `theme/feinspitz/inc/admin.php` in `feinspitz_admin_render_overview_widget()` (aktuell ~Zeile 476–477) dieselbe Ersetzung vornehmen.

Alt:
```php
	$new_product = admin_url( 'post-new.php?post_type=product' );
	$new_post    = admin_url( 'post-new.php?post_type=post' );
```
Neu:
```php
	$new_product = defined( 'FEINSPITZ_PRODUCT_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG ) : admin_url( 'post-new.php?post_type=product' );
	$new_post    = defined( 'FEINSPITZ_ARTICLE_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG ) : admin_url( 'post-new.php?post_type=post' );
```

- [ ] **Step 5: Syntax prüfen**

Run: `php -l theme/feinspitz/inc/admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Selbstprüfung**

Prüfe: (a) beide `$new_product`/`$new_post`-Ersetzungen an beiden Stellen (Dashboard + Widget) vorgenommen; (b) Zeilenaktion für `post` UND `product`; (c) `defined()`-Fallbacks vorhanden.

- [ ] **Step 7: Commit**

```bash
git add theme/feinspitz/inc/admin-forms-rowlinks.php theme/feinspitz/inc/admin.php
git commit -m "feat(admin): Zeilenaktion \"Einfach bearbeiten\" + Dashboard-Links auf Masken

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Integrations-Verifikation & Deploy

**Files:** keine (nur Deploy + manueller Smoke-Test).

- [ ] **Step 1: Alle neuen/geänderten Dateien final linten**

Run:
```bash
php -l theme/feinspitz/inc/admin-forms-shared.php
php -l theme/feinspitz/inc/admin-product-form.php
php -l theme/feinspitz/inc/admin-article-form.php
php -l theme/feinspitz/inc/admin-forms-rowlinks.php
php -l theme/feinspitz/inc/admin.php
```
Expected: jeweils `No syntax errors detected`.

- [ ] **Step 2: Theme deployen**

Run: `node scripts/deploy/deploy-theme.mjs`
Expected: erfolgreicher Upload (siehe Skript-Ausgabe „Theme aktiviert/aktualisiert").

- [ ] **Step 3: Smoke-Test Produkt anlegen**

Im Backend `Feinspitz → Neues Produkt` öffnen. Test-Wein anlegen (Name, Preis, Bild, Kategorie, mind. Rebsorte + Region, ein Flag), veröffentlichen. Prüfen:
- Erfolgs-Notice erscheint, `?id=` gesetzt (Bearbeiten-Modus).
- Produkt erscheint im Shop-Frontend in der gewählten Kategorie.
- „Auf einen Blick"-Fakten-Tabelle zeigt die gesetzten Attribute (Rebsorte/Region).
- Flag-Badge korrekt sichtbar.
- Produktliste (`Produkte`) zeigt die Attribut-Spalten (Rebsorte/Region/Süsse) und das Flag gefüllt.
- Wein-Finder/Shop-Filter finden das Produkt über die Attribute.

- [ ] **Step 4: Smoke-Test „oder neu"-Attribut**

Neues Produkt anlegen und bei einem Attribut (z. B. Weingut) das Feld „oder neu:" mit einem neuen Wert füllen. Nach dem Speichern prüfen, dass der neue Term angelegt und dem Produkt zugewiesen ist (Fakten-Tabelle + Filter zeigen ihn).

- [ ] **Step 5: Smoke-Test Artikel anlegen**

`Feinspitz → Neuer Artikel`: je einen Ratgeber- und einen Weinlexikon-Artikel (Titel, Teaser, Bild, etwas Text) veröffentlichen. Prüfen:
- Ratgeber-Artikel erscheint in der Ratgeber-Übersicht (mit Teaser).
- Weinlexikon-Artikel erscheint im A–Z-Weinlexikon unter korrektem Anfangsbuchstaben (mit Teaser).

- [ ] **Step 6: Smoke-Test „Einfach bearbeiten"**

In der Produkt- und Beitragsliste je „Einfach bearbeiten" klicken → Werte sind vorbefüllt. Eine Kleinigkeit ändern, speichern → Änderung übernommen.

- [ ] **Step 7: Regressions-Check**

Prüfen, dass die Standard-Wege unverändert funktionieren: `Produkte → Erstellen` (WooCommerce) und `Beiträge → Erstellen` (Gutenberg) öffnen normal; das Frontend ist unverändert.

- [ ] **Step 8: Abschluss festhalten**

Ergebnis des Smoke-Tests notieren. Bei Auffälligkeiten: `superpowers:systematic-debugging` verwenden, Fix als eigenen Commit, dann erneut deployen/prüfen.

---

## Self-Review (vom Plan-Autor durchgeführt)

**Spec-Abdeckung:**
- Architektur/Dateien (Spec §Architektur) → Task 1–4 legen `admin-forms-shared.php`, `admin-product-form.php`, `admin-article-form.php`, `admin-forms-rowlinks.php` an. ✓
- Produkt-Maske alle Felder (Spec §Produkt-Maske) → Task 2 Render + Save (Name, Preis, Bild, Kategorie, 6 Attribute + „oder neu", 3 Flags, Status). ✓
- Artikel-Maske alle Felder (Spec §Artikel-Maske) → Task 3 (Typ→Kategorie, Titel, Teaser, Bild, Text, Status). ✓
- Bearbeiten bestehender Inhalte (Spec §Bearbeiten) → Task 2/3 `?id=`-Vorbelegung + Task 4 Zeilenaktionen; Gutenberg-Hinweis in Task 3 Render. ✓
- Sicherheit (Spec §Sicherheit) → Nonce/Cap/Sanitize/Escape in jedem Handler; Global Constraints. ✓
- Testing (Spec §Testing) → Task 5 deckt php -l, Deploy, alle Smoke-Test-Punkte inkl. Fakten-Tabelle/Wein-Finder/Übersichten. ✓
- i18n / keine msgids (Spec §Rahmenbedingungen) → Global Constraints + deutsche Literale durchgehend. ✓
- Dashboard-Karten auf Masken (Spec §Architektur „Schnell-Aktions-Karten … umgebogen") → Task 4 Step 3–4. ✓

**Platzhalter-Scan:** Keine TBD/TODO; alle Code-Schritte enthalten vollständigen Code. ✓

**Typ-Konsistenz:** `feinspitz_forms_*`-Signaturen in Task 1 „Produces" stimmen mit den Aufrufen in Task 2/3 überein; `FEINSPITZ_PRODUCT_FORM_SLUG`/`FEINSPITZ_ARTICLE_FORM_SLUG` in Task 1 definiert, in Task 2–4 konsumiert; Attribut-Feldnamen (`feinspitz_attr_<suffix>` / `_new`) im Render (Task 2 Step 2) und Builder (Task 2 Step 3) identisch; Flag-Feldnamen (`feinspitz_flag_<slug>`) in Render und `feinspitz_product_apply_flags` identisch. ✓
