<?php
/**
 * Feinspitz - Shop & Kategorie-Archive (feature/shop-archive).
 *
 * Registriert die shop-* Block-Patterns, die von templates/archive-product.html
 * und templates/taxonomy-product_cat.html referenziert werden, und passt einige
 * WooCommerce-Archiv-Ausgaben an, damit die BOLD gestalteten Kopfbereiche des
 * Themes den Titel/Description besitzen (statt der Standard-WooCommerce-Ausgabe).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php).
 * Die Pattern-Dateien liegen in theme/feinspitz/patterns/shop-*.php und werden
 * hier BEWUSST manuell registriert (analog zu inc/homepage.php), damit die
 * Registrierung nachvollziehbar an einer Stelle gebündelt ist. Die Pattern-
 * Dateien enthalten daher KEINEN Datei-Header - reines Block-Markup mit
 * übersetzbaren Strings (Textdomain feinspitz).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern-Kategorie + Shop-Patterns registrieren.
 */
add_action( 'init', function () {

	// Wird auch von inc/homepage.php registriert; erneuter Aufruf ist idempotent
	// und hält feature/shop-archive unabhängig lauffähig.
	register_block_pattern_category(
		'feinspitz',
		array( 'label' => __( 'Feinspitz', 'feinspitz' ) )
	);

	// Slug (ohne Präfix) => Anzeigetitel (übersetzbar).
	$patterns = array(
		'shop-archive-header'  => __( 'Shop: Kopfbereich', 'feinspitz' ),
		'shop-category-header' => __( 'Shop: Kategorie-Kopf', 'feinspitz' ),
		'shop-filter-flags'    => __( 'Shop: Flag-Filter (histamingeprüft/vegan/alkoholfrei)', 'feinspitz' ),
		'shop-grid'            => __( 'Shop: Produkt-Grid (Sortierung + Pagination)', 'feinspitz' ),
	);

	foreach ( $patterns as $slug => $title ) {
		$file = get_template_directory() . '/patterns/' . $slug . '.php';

		if ( ! is_readable( $file ) ) {
			continue;
		}

		// Pattern-Datei ausführen (übersetzt Strings) und Markup einfangen.
		ob_start();
		include $file;
		$content = ob_get_clean();

		register_block_pattern(
			'feinspitz/' . $slug,
			array(
				'title'      => $title,
				'categories' => array( 'feinspitz' ),
				'content'    => $content,
				'inserter'   => true,
			)
		);
	}
} );

/**
 * Der Produkt-Grid rendert über den WooCommerce-"Classic Template"-Block
 * (woocommerce/legacy-template), der Ergebnis-Zähler, Sortierung, Loop und
 * Pagination der Haupt-Query ausgibt. Damit unsere eigenen, BOLD gestalteten
 * Kopfbereiche (shop-archive-header / shop-category-header) den Titel und die
 * Kategorie-Beschreibung besitzen, unterdrücken wir hier die Standard-Ausgaben
 * von WooCommerce - sonst erschiene der Titel doppelt.
 */
add_action( 'init', function () {

	if ( ! function_exists( 'is_woocommerce' ) ) {
		return; // WooCommerce nicht aktiv - nichts zu tun.
	}

	// Standard-<h1> ("Shop" bzw. Kategoriename) der Archiv-Vorlage abschalten.
	add_filter( 'woocommerce_show_page_title', '__return_false' );

	// Standard-Beschreibungen (Shop-Seiteninhalt + Kategorie-Beschreibung)
	// abschalten - wir zeigen die Kategorie-Beschreibung im eigenen Kopf.
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
}, 99 );

/**
 * Aufgeräumte, enduser-freundliche Archiv-Optik.
 *
 * Der Produkt-Grid rendert über den WooCommerce-"Classic Template"-Block, also
 * klassisches WooCommerce-Markup (ul.products > li.product mit Bild, Titel,
 * Preis, "In den Warenkorb"-Button; dazu Ergebnis-Zähler, Sortierung,
 * Breadcrumbs, Pagination). Wir gestalten dieses Markup ausschliesslich über
 * gescopte Inline-Styles, die an das in functions.php registrierte
 * Theme-Stylesheet-Handle "feinspitz-style" gehängt werden - theme.json und
 * style.css bleiben unangetastet.
 *
 * Alle Regeln sind auf .feinspitz-shop-grid (Grid-Pattern) bzw.
 * .feinspitz-shop-filter (Filter-Pattern) gescopt und wirken daher nur auf den
 * Shop-/Kategorie-Archiven. Farben/Radien nutzen die Theme-Tokens
 * (wine/gold/cream/base, runde Buttons).
 */
add_action( 'wp_enqueue_scripts', function () {

	$css = <<<'CSS'
/* ---------- Breadcrumbs + Ergebnis-Kopf ---------- */
.feinspitz-shop-grid .woocommerce-breadcrumb{font-family:var(--wp--preset--font-family--body);font-size:.8rem;letter-spacing:.02em;color:rgba(14,11,8,.55);margin:0 0 1.25rem}
.feinspitz-shop-grid .woocommerce-breadcrumb a{color:var(--wp--preset--color--wine);text-decoration:none}
.feinspitz-shop-grid .woocommerce-breadcrumb a:hover{text-decoration:underline}
.feinspitz-shop-grid .woocommerce-result-count{float:left;margin:.4rem 0 1.25rem;font-family:var(--wp--preset--font-family--body);font-size:.85rem;color:rgba(14,11,8,.6)}
.feinspitz-shop-grid .woocommerce-ordering{float:right;margin:0 0 1.25rem}
.feinspitz-shop-grid .woocommerce-ordering select{font-family:var(--wp--preset--font-family--body);font-size:.85rem;padding:.5rem 1rem;border-radius:999px;border:1px solid rgba(14,11,8,.16);background:var(--wp--preset--color--contrast);color:var(--wp--preset--color--base);cursor:pointer}
@media(max-width:520px){.feinspitz-shop-grid .woocommerce-result-count,.feinspitz-shop-grid .woocommerce-ordering{float:none;display:block;width:100%}.feinspitz-shop-grid .woocommerce-ordering{margin-bottom:1rem}.feinspitz-shop-grid .woocommerce-ordering select{width:100%}}

/* ---------- Produkt-Grid (mobil 2-spaltig) ---------- */
.feinspitz-shop-grid ul.products{display:grid !important;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin:0 !important;padding:0 !important;list-style:none;clear:both}
.feinspitz-shop-grid ul.products::before,.feinspitz-shop-grid ul.products::after{content:none !important;display:none !important}
@media(min-width:640px){.feinspitz-shop-grid ul.products{grid-template-columns:repeat(3,minmax(0,1fr));gap:1.5rem}}
@media(min-width:1000px){.feinspitz-shop-grid ul.products{grid-template-columns:repeat(4,minmax(0,1fr))}}

/* ---------- Einheitliche Produktkarten ---------- */
.feinspitz-shop-grid ul.products li.product{position:relative;width:auto !important;margin:0 !important;padding:0 !important;float:none !important;display:flex;flex-direction:column;background:var(--wp--preset--color--contrast);border:1px solid rgba(14,11,8,.08);border-radius:16px;overflow:hidden;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.feinspitz-shop-grid ul.products li.product:hover{transform:translateY(-4px);box-shadow:0 14px 30px -18px rgba(74,17,25,.55);border-color:rgba(201,162,75,.5)}
.feinspitz-shop-grid li.product a.woocommerce-LoopProduct-link{display:flex;flex-direction:column;flex:1 1 auto;color:inherit;text-decoration:none}
.feinspitz-shop-grid li.product img{width:100%;height:auto;aspect-ratio:4/5;object-fit:contain;display:block;margin:0 0 .9rem;padding:.75rem;background:#f6f1e7}
.feinspitz-shop-grid li.product .woocommerce-loop-product__title{font-family:var(--wp--preset--font-family--heading);font-size:1.02rem;line-height:1.25;font-weight:600;color:var(--wp--preset--color--base);margin:0 1rem .45rem;padding:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.5em}
.feinspitz-shop-grid li.product .star-rating{margin:0 1rem .5rem;font-size:.85rem}
.feinspitz-shop-grid li.product .price{margin:auto 1rem .25rem !important;padding-top:.35rem;font-family:var(--wp--preset--font-family--body);font-weight:700;font-size:1.05rem;color:var(--wp--preset--color--wine)}
.feinspitz-shop-grid li.product .price del{color:rgba(14,11,8,.45);font-weight:400;font-size:.9em;margin-right:.4rem}
.feinspitz-shop-grid li.product .price ins{text-decoration:none}
.feinspitz-shop-grid li.product .onsale{position:absolute;top:.75rem;left:.75rem;z-index:2;margin:0;min-height:0;background:var(--wp--preset--color--gold);color:var(--wp--preset--color--base);border-radius:999px;padding:.3rem .7rem;font-size:.7rem;font-weight:700;line-height:1;text-transform:uppercase;letter-spacing:.08em}

/* ---------- Ausgerichtete "In den Warenkorb"-Buttons ---------- */
.feinspitz-shop-grid li.product .button,.feinspitz-shop-grid li.product a.add_to_cart_button{display:block;text-align:center;margin:.7rem 1rem 1.1rem;padding:.7rem 1rem;border-radius:999px;background:var(--wp--preset--color--wine);color:var(--wp--preset--color--contrast);font-family:var(--wp--preset--font-family--body);font-weight:600;font-size:.9rem;line-height:1.2;text-decoration:none;border:0;box-shadow:none;transition:background .18s ease}
.feinspitz-shop-grid li.product .button:hover,.feinspitz-shop-grid li.product a.add_to_cart_button:hover{background:var(--wp--preset--color--wine-deep)}
.feinspitz-shop-grid li.product .added_to_cart{display:block;text-align:center;margin:-.4rem 1rem 1rem;font-family:var(--wp--preset--font-family--body);font-size:.82rem;font-weight:600;color:var(--wp--preset--color--wine);text-decoration:underline}
.feinspitz-shop-grid li.product .button.loading,.feinspitz-shop-grid li.product a.add_to_cart_button.loading{opacity:.7}

/* ---------- Pagination ---------- */
.feinspitz-shop-grid .woocommerce-pagination{clear:both;margin-top:2.5rem;text-align:center}
.feinspitz-shop-grid .woocommerce-pagination ul{display:inline-flex;flex-wrap:wrap;gap:.4rem;justify-content:center;border:0 !important;margin:0;padding:0;list-style:none}
.feinspitz-shop-grid .woocommerce-pagination ul li{border:0 !important;margin:0}
.feinspitz-shop-grid .woocommerce-pagination ul li a,.feinspitz-shop-grid .woocommerce-pagination ul li span{display:inline-flex;align-items:center;justify-content:center;min-width:2.5rem;height:2.5rem;padding:0 .55rem;border-radius:999px;font-family:var(--wp--preset--font-family--body);font-weight:600;text-decoration:none;color:var(--wp--preset--color--base);background:transparent;border:1px solid rgba(14,11,8,.12)}
.feinspitz-shop-grid .woocommerce-pagination ul li .current{background:var(--wp--preset--color--wine);color:var(--wp--preset--color--contrast);border-color:transparent}
.feinspitz-shop-grid .woocommerce-pagination ul li a:hover{border-color:var(--wp--preset--color--wine);color:var(--wp--preset--color--wine)}

/* ---------- Aufgeräumte Filterleiste (Flags) ---------- */
.feinspitz-shop-filter .wp-block-buttons{gap:.5rem}
.feinspitz-shop-filter .wp-block-button__link{background:transparent;color:var(--wp--preset--color--wine);border:1.5px solid rgba(123,31,43,.35);border-radius:999px;padding:.5rem 1.15rem;font-size:.85rem;font-weight:600;line-height:1.2;box-shadow:none;transition:background .16s ease,color .16s ease,border-color .16s ease}
.feinspitz-shop-filter .wp-block-button__link:hover,.feinspitz-shop-filter .wp-block-button__link:focus-visible{background:rgba(123,31,43,.07);border-color:var(--wp--preset--color--wine)}
.feinspitz-shop-filter .wp-block-button.is-active .wp-block-button__link{background:var(--wp--preset--color--wine);color:var(--wp--preset--color--contrast);border-color:var(--wp--preset--color--wine)}
.feinspitz-shop-filter .wp-block-button.is-active .wp-block-button__link:hover{background:var(--wp--preset--color--wine-deep)}
CSS;

	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-shop-inline', false );
		wp_enqueue_style( 'feinspitz-shop-inline' );
		wp_add_inline_style( 'feinspitz-shop-inline', $css );
	}

	// Aktiven Flag-Filter markieren (Progressive Enhancement). Der Filter selbst
	// funktioniert serverseitig über ?product_tag; dieses Skript hebt lediglich
	// den zur aktuellen URL passenden Button hervor. Nur auf Shop-/Kategorie-
	// Archiven laden - das Markup existiert nur dort.
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$js = <<<'JS'
(function(){
	var bar=document.querySelector('.feinspitz-shop-filter');
	if(!bar)return;
	var current=new URLSearchParams(window.location.search).get('product_tag');
	Array.prototype.forEach.call(bar.querySelectorAll('.wp-block-button__link'),function(a){
		var wrap=a.closest('.wp-block-button');
		if(!wrap)return;
		var tag=null;
		try{tag=new URL(a.href,window.location.origin).searchParams.get('product_tag');}catch(e){}
		var active=(tag||null)===(current||null);
		wrap.classList.toggle('is-active',active);
		if(active){a.setAttribute('aria-current','true');}else{a.removeAttribute('aria-current');}
	});
})();
JS;

		wp_register_script( 'feinspitz-shop', false, array(), null, true );
		wp_enqueue_script( 'feinspitz-shop' );
		wp_add_inline_script( 'feinspitz-shop', $js );
	}
}, 20 );

/**
 * Englische Kategorie-ANZEIGENAMEN auf /en/.
 *
 * Das freie Polylang übersetzt WooCommerce-Produkt-Kategorien (Taxonomie
 * product_cat) NICHT - die Terme existieren nur in Deutsch. Damit die Shop-
 * OBERFLÄCHE auf /en/ dennoch vollständig englisch wirkt, bilden wir die
 * Kategorie-Slugs hier auf englische Anzeigenamen ab und ersetzen den
 * ausgegebenen Kategorie-Titel, wenn die aktuelle Polylang-Sprache "en" ist.
 *
 * Produktnamen und -Beschreibungen bleiben unberührt (per Projekt-Vorgabe
 * deutsch) - hier geht es ausschliesslich um die Kategorie-Überschrift der
 * Archiv-Seiten (/en/produkt-kategorie/<slug>/).
 *
 * Angewendet über drei Filter, die alle Wege abdecken, auf denen der Titel
 * gerendert wird:
 *   - get_the_archive_title : der core/query-title-Block im shop-category-header.
 *   - single_term_title     : WordPress-Kern-Term-Titel (Basis vieler Ausgaben).
 *   - woocommerce_page_title: WooCommerce-eigene Seitentitel-Ausgabe.
 *
 * @return array Slug => englischer Anzeigename.
 */
function feinspitz_shop_category_en_names() {
	return array(
		'weissweine'      => 'White Wines',
		'rotweine'        => 'Red Wines',
		'rose'            => 'Rosé',
		'schaumweine'     => 'Sparkling Wines',
		'suessweine'      => 'Dessert Wines',
		'kulinarium'      => 'Gourmet',
		'senf-gewuerze'   => 'Mustard & Spices',
		'pesto-chutney-co' => 'Pesto, Chutney & Co.',
		'essig-verjus'    => 'Vinegar & Verjus',
		'spirituosen'     => 'Spirits',
		'gin'             => 'Gin',
		'buecher'         => 'Books',
	);
}

/**
 * Englischer Anzeigename der aktuell abgefragten Produkt-Kategorie - oder null.
 *
 * Liefert nur dann einen Wert, wenn (a) Polylang aktiv ist und die aktuelle
 * Sprache "en" ist, (b) ein product_cat-Term abgefragt wird und (c) dessen Slug
 * in der Map steht. In allen anderen Fällen null → Aufrufer behält den
 * Original-Titel (Deutsch bleibt Deutsch).
 *
 * @return string|null
 */
function feinspitz_shop_category_en_title() {
	if ( ! function_exists( 'pll_current_language' ) || 'en' !== pll_current_language() ) {
		return null;
	}

	$obj = get_queried_object();
	if ( ! ( $obj instanceof WP_Term ) || 'product_cat' !== $obj->taxonomy ) {
		return null;
	}

	$map = feinspitz_shop_category_en_names();
	return isset( $map[ $obj->slug ] ) ? $map[ $obj->slug ] : null;
}

add_filter(
	'get_the_archive_title',
	function ( $title ) {
		$en = feinspitz_shop_category_en_title();
		return ( null !== $en ) ? $en : $title;
	},
	99
);

add_filter(
	'single_term_title',
	function ( $title ) {
		$en = feinspitz_shop_category_en_title();
		return ( null !== $en ) ? $en : $title;
	},
	99
);

add_filter(
	'woocommerce_page_title',
	function ( $title ) {
		$en = feinspitz_shop_category_en_title();
		return ( null !== $en ) ? $en : $title;
	},
	99
);
