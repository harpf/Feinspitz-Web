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
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- %3$s und %6$s sind statische Markup-Literale ohne Benutzerdaten.
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

/**
 * Bildauswahl über die WP-Medienbibliothek (Button + Vorschau + Hidden-ID).
 * Das JS wird über den admin_enqueue_scripts-Hook geladen (nur auf den beiden Formular-Seiten).
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
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- %2$s kommt von wp_get_attachment_image (bereits sicher), %5$s ist ein statisches Inline-Style-Literal.
	printf(
		'<div class="feinspitz-field feinspitz-media"><label>%1$s</label>'
		. '<div class="feinspitz-media-preview">%2$s</div>'
		. '<input type="hidden" class="feinspitz-media-id" name="%3$s" value="%4$d">'
		. '<button type="button" class="button feinspitz-media-pick">Bild wählen</button> '
		. '<button type="button" class="button-link feinspitz-media-remove"%5$s>Entfernen</button></div>',
		esc_html( $label ),
		$preview,
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
