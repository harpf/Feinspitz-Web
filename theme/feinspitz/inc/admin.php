<?php
/**
 * Feinspitz · Admin-Usability (feature/admin-dashboard).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Admin-Branch. Sie vereinfacht das WordPress-Backend, damit
 * der Administrator Produkte anlegen und Artikel (Ratgeber/Weinlexikon) schreiben
 * kann, ohne sich durch die WooCommerce-/WP-Standardoberfläche kämpfen zu müssen.
 *
 * Bausteine (siehe docs/superpowers/specs/2026-07-02-feinspitz-admin-usability-design.md):
 *   1. Top-Level-Adminmenü „Feinspitz" mit Startseite: grosse Schnell-Aktionen
 *      (Neues Produkt, Neuer Artikel, Anfragen) + kurze deutsche „So geht's"-Hilfe.
 *   2. Produktliste: eigene Spalten (Rebsorte · Region · Süsse · Flags) aus den
 *      globalen pa_-Attributen und den product_tag-Flags.
 *   3. Anfragen-Liste (CPT feinspitz_anfrage): lesbare Spalten (Name · E-Mail ·
 *      Typ · Datum), gelesen aus dem Postmeta, das inc/forms.php schreibt.
 *   4. Aufräumen: irrelevante WP-Dashboard-Standardwidgets reduzieren + kleines
 *      Feinspitz-Übersichts-Widget mit Kennzahlen und Schnell-Links.
 *
 * Alles ist reiner Admin-Code: die gesamte Datei ist per is_admin() gegatet, wirkt
 * also NICHT im Frontend und ändert kein Frontend-Rendering. Bewusst KEINE neuen
 * gettext-msgids (die Admin-Sprache ist Deutsch, als Literale) — sonst bräche der
 * zentrale i18n-Build (make-po verlangt für jede msgid eine EN-Übersetzung).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gesamte Datei ist Admin-only. Die Hooks unten feuern ohnehin nur im Backend,
// aber der frühe is_admin()-Gate hält den Frontend-Request komplett frei.
if ( ! is_admin() ) {
	return;
}

/**
 * Slug der Feinspitz-Dashboard-Seite (Top-Level-Menü).
 */
if ( ! defined( 'FEINSPITZ_ADMIN_SLUG' ) ) {
	define( 'FEINSPITZ_ADMIN_SLUG', 'feinspitz' );
}

/**
 * product_tag-Slugs der Wein-Flags → deutsche Kurz-Labels für die Produktliste.
 * Slugs identisch zu inc/shop.php (Flag-Filter).
 *
 * @return array<string,string>
 */
function feinspitz_admin_flag_labels() {
	return array(
		'histamingeprueft' => 'Histamingeprüft',
		'vegan'            => 'Vegan',
		'alkoholfrei'      => 'Alkoholfrei',
	);
}

/**
 * Wein-Glas-Icon als data-URI (dashicons hat kein Weinglas). Für add_menu_page.
 *
 * @return string
 */
function feinspitz_admin_menu_icon() {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M5 2h10l-.7 5.2A4.5 4.5 0 0 1 11 11.4V16.5h2.5a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1H9v-5.1A4.5 4.5 0 0 1 5.7 7.2L5 2Zm1.15 1 .2 1.5h7.3l.2-1.5H6.15Z"/></svg>';
	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

/* -------------------------------------------------------------------------
 * 1) Top-Level-Adminmenü „Feinspitz" + Startseite.
 * ---------------------------------------------------------------------- */

/**
 * Menü registrieren: Top-Level „Feinspitz" + Unterpunkt „Übersicht".
 */
add_action( 'admin_menu', function () {
	add_menu_page(
		'Feinspitz',                 // Seitentitel.
		'Feinspitz',                 // Menütitel.
		'edit_posts',                // Capability: wer Beiträge bearbeiten darf.
		FEINSPITZ_ADMIN_SLUG,        // Slug.
		'feinspitz_admin_render_dashboard',
		feinspitz_admin_menu_icon(),
		3                            // Position: direkt unter „Dashboard".
	);

	// Ersten Unterpunkt umbenennen (statt doppeltem „Feinspitz").
	add_submenu_page(
		FEINSPITZ_ADMIN_SLUG,
		'Feinspitz · Übersicht',
		'Übersicht',
		'edit_posts',
		FEINSPITZ_ADMIN_SLUG,
		'feinspitz_admin_render_dashboard'
	);
} );

/**
 * Eine Schnell-Aktions-Karte rendern.
 *
 * @param string $url   Ziel-URL (bereits admin_url()).
 * @param string $icon  Dashicon-Klasse (z. B. „dashicons-cart").
 * @param string $title Titel.
 * @param string $desc  Kurzbeschreibung.
 * @return string
 */
function feinspitz_admin_action_card( $url, $icon, $title, $desc ) {
	return sprintf(
		'<a class="feinspitz-card" href="%1$s"><span class="feinspitz-card__icon dashicons %2$s" aria-hidden="true"></span>'
		. '<span class="feinspitz-card__body"><span class="feinspitz-card__title">%3$s</span>'
		. '<span class="feinspitz-card__desc">%4$s</span></span></a>',
		esc_url( $url ),
		esc_attr( $icon ),
		esc_html( $title ),
		esc_html( $desc )
	);
}

/**
 * Startseite des Feinspitz-Menüs rendern.
 */
function feinspitz_admin_render_dashboard() {
	$new_product = defined( 'FEINSPITZ_PRODUCT_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG ) : admin_url( 'post-new.php?post_type=product' );
	$new_post    = defined( 'FEINSPITZ_ARTICLE_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG ) : admin_url( 'post-new.php?post_type=post' );
	$anfragen    = admin_url( 'edit.php?post_type=feinspitz_anfrage' );
	$products    = admin_url( 'edit.php?post_type=product' );

	// Kennzahlen.
	$count_products = (int) wp_count_posts( 'product' )->publish;
	$count_posts    = (int) wp_count_posts( 'post' )->publish;
	$count_anfragen = feinspitz_admin_count_anfragen();
	?>
	<div class="wrap feinspitz-admin">
		<h1 class="feinspitz-admin__h1">
			<span class="dashicons dashicons-store" aria-hidden="true"></span>
			Feinspitz · Verwaltung
		</h1>
		<p class="feinspitz-admin__lead">
			Willkommen! Hier erledigen Sie die häufigsten Aufgaben mit einem Klick.
			Wählen Sie eine Schnell-Aktion oder folgen Sie der kurzen Anleitung darunter.
		</p>

		<div class="feinspitz-admin__stats">
			<div class="feinspitz-stat"><span class="feinspitz-stat__num"><?php echo esc_html( (string) $count_products ); ?></span><span class="feinspitz-stat__label">Produkte</span></div>
			<div class="feinspitz-stat"><span class="feinspitz-stat__num"><?php echo esc_html( (string) $count_posts ); ?></span><span class="feinspitz-stat__label">Artikel</span></div>
			<div class="feinspitz-stat"><span class="feinspitz-stat__num"><?php echo esc_html( (string) $count_anfragen ); ?></span><span class="feinspitz-stat__label">Anfragen</span></div>
		</div>

		<h2 class="feinspitz-admin__h2">Schnell-Aktionen</h2>
		<div class="feinspitz-cards">
			<?php
			echo feinspitz_admin_action_card( $new_product, 'dashicons-cart', 'Neues Produkt', 'Einen Wein oder ein Genuss-Produkt in den Shop aufnehmen.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo feinspitz_admin_action_card( $new_post, 'dashicons-edit', 'Neuer Artikel', 'Ratgeber-Beitrag oder Weinlexikon-Eintrag schreiben (Kategorie wählen!).' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo feinspitz_admin_action_card( $anfragen, 'dashicons-email-alt', 'Anfragen ansehen', 'Eingegangene Kontakt-, Weinproben- und Catering-Anfragen lesen.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo feinspitz_admin_action_card( $products, 'dashicons-list-view', 'Produkte verwalten', 'Bestehende Produkte bearbeiten — mit Übersicht zu Rebsorte, Region & Süsse.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>

		<h2 class="feinspitz-admin__h2">So geht's</h2>
		<div class="feinspitz-guide">
			<div class="feinspitz-guide__col">
				<h3><span class="dashicons dashicons-cart" aria-hidden="true"></span> Ein Produkt anlegen</h3>
				<ol>
					<li>Auf <strong>„Neues Produkt"</strong> klicken.</li>
					<li><strong>Weinname</strong>, <strong>Preis (CHF)</strong> und eine <strong>Beschreibung</strong> eintragen.</li>
					<li>Über <strong>„Bild wählen"</strong> ein Produktbild setzen.</li>
					<li><strong>Kategorie</strong> wählen und die Wein-Angaben
						(<em>Rebsorte, Weingut, Region, Süsse, Jahrgang, Volumen</em>) aus den Listen auswählen —
						fehlt ein Wert, im Feld <strong>„oder neu:"</strong> eintragen.</li>
					<li>Zutreffende <strong>Merkmale</strong> ankreuzen (Histamingeprüft / Vegan / Alkoholfrei).</li>
					<li>Unten auf <strong>„Speichern"</strong> klicken — fertig.</li>
				</ol>
			</div>
			<div class="feinspitz-guide__col">
				<h3><span class="dashicons dashicons-edit" aria-hidden="true"></span> Einen Artikel schreiben</h3>
				<ol>
					<li>Auf <strong>„Neuer Artikel"</strong> klicken.</li>
					<li>Oben den <strong>Typ</strong> wählen: <strong>„Ratgeber"</strong> oder
						<strong>„Weinlexikon"</strong> — die Kategorie wird automatisch gesetzt.</li>
					<li><strong>Titel</strong>, einen kurzen <strong>Teaser</strong> und den <strong>Text</strong> verfassen.</li>
					<li>Ein <strong>Beitragsbild</strong> setzen (macht die Übersicht schöner).</li>
					<li>Unten auf <strong>„Speichern"</strong> klicken — fertig.</li>
				</ol>
			</div>
		</div>

		<p class="feinspitz-admin__foot">
			Tipp: Die <a href="<?php echo esc_url( $products ); ?>">Produktliste</a> zeigt jetzt
			Rebsorte, Region, Süsse und die Flags direkt als Spalten — so behalten Sie den Überblick.
		</p>
	</div>
	<?php
}

/**
 * Gescoptes Admin-CSS · nur auf der Feinspitz-Startseite.
 */
add_action( 'admin_head', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'toplevel_page_' . FEINSPITZ_ADMIN_SLUG !== $screen->id ) {
		return;
	}
	?>
	<style>
	.feinspitz-admin{--ff-wine:#7b1f2b;--ff-gold:#c9a24b;--ff-ink:#0e0b08;max-width:1100px}
	.feinspitz-admin__h1{display:flex;align-items:center;gap:.5rem;font-size:1.6rem}
	.feinspitz-admin__h1 .dashicons{color:var(--ff-wine);font-size:1.6rem;width:1.6rem;height:1.6rem}
	.feinspitz-admin__h2{margin:2rem 0 .75rem;font-size:1.2rem}
	.feinspitz-admin__lead{font-size:1rem;max-width:70ch;color:#50575e}
	.feinspitz-admin__stats{display:flex;gap:1rem;flex-wrap:wrap;margin:1.25rem 0}
	.feinspitz-stat{background:#fff;border:1px solid #dcdcde;border-left:4px solid var(--ff-gold);border-radius:8px;padding:.75rem 1.25rem;min-width:120px}
	.feinspitz-stat__num{display:block;font-size:1.8rem;font-weight:700;line-height:1;color:var(--ff-wine)}
	.feinspitz-stat__label{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:#646970;margin-top:.35rem}
	.feinspitz-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem}
	.feinspitz-card{display:flex;gap:1rem;align-items:flex-start;background:#fff;border:1px solid #dcdcde;border-top:3px solid var(--ff-wine);border-radius:10px;padding:1.1rem 1.2rem;text-decoration:none;color:inherit;transition:box-shadow .15s ease,transform .15s ease}
	.feinspitz-card:hover{box-shadow:0 8px 24px -14px rgba(14,11,8,.55);transform:translateY(-2px)}
	.feinspitz-card:focus{outline:2px solid var(--ff-gold);outline-offset:2px}
	.feinspitz-card__icon{color:var(--ff-wine);font-size:1.9rem;width:1.9rem;height:1.9rem;flex:0 0 auto}
	.feinspitz-card__title{display:block;font-weight:600;font-size:1.02rem;color:var(--ff-ink)}
	.feinspitz-card__desc{display:block;font-size:.85rem;color:#646970;margin-top:.2rem;line-height:1.45}
	.feinspitz-guide{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.25rem}
	.feinspitz-guide__col{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:1.1rem 1.4rem}
	.feinspitz-guide__col h3{display:flex;align-items:center;gap:.4rem;margin:.2rem 0 .6rem;color:var(--ff-wine)}
	.feinspitz-guide__col ol{margin:0;padding-left:1.2rem;line-height:1.7}
	.feinspitz-guide__col li{margin-bottom:.35rem}
	.feinspitz-admin__foot{margin-top:1.5rem;color:#50575e}
	</style>
	<?php
} );

/* -------------------------------------------------------------------------
 * 2) Produktliste: eigene Spalten (Rebsorte · Region · Süsse · Flags).
 * ---------------------------------------------------------------------- */

/**
 * Spalten in der WooCommerce-Produktliste ergänzen (nach dem Namen einfügen).
 *
 * @param array<string,string> $columns Vorhandene Spalten.
 * @return array<string,string>
 */
add_filter( 'manage_edit-product_columns', function ( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'name' === $key ) {
			$new['feinspitz_rebsorte'] = 'Rebsorte';
			$new['feinspitz_region']   = 'Region';
			$new['feinspitz_suesse']   = 'Süsse';
			$new['feinspitz_flags']    = 'Flags';
		}
	}
	// Falls es keine „name"-Spalte gab (Fallback): einfach anhängen.
	if ( ! isset( $new['feinspitz_rebsorte'] ) ) {
		$new['feinspitz_rebsorte'] = 'Rebsorte';
		$new['feinspitz_region']   = 'Region';
		$new['feinspitz_suesse']   = 'Süsse';
		$new['feinspitz_flags']    = 'Flags';
	}
	return $new;
}, 20 );

/**
 * Attribut-Termnamen eines Produkts als Text zusammenfassen.
 *
 * @param int    $post_id  Produkt-ID.
 * @param string $taxonomy Taxonomie (z. B. „pa_rebsorte").
 * @return string Komma-getrennte Namen oder „—".
 */
function feinspitz_admin_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '—';
	}
	$names = wp_list_pluck( $terms, 'name' );
	return implode( ', ', array_map( 'sanitize_text_field', $names ) );
}

/**
 * Inhalt der eigenen Produkt-Spalten rendern.
 *
 * @param string $column  Spaltenschlüssel.
 * @param int    $post_id Produkt-ID.
 * @return void
 */
add_action( 'manage_product_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'feinspitz_rebsorte':
			echo esc_html( feinspitz_admin_term_names( $post_id, 'pa_rebsorte' ) );
			break;
		case 'feinspitz_region':
			echo esc_html( feinspitz_admin_term_names( $post_id, 'pa_region' ) );
			break;
		case 'feinspitz_suesse':
			echo esc_html( feinspitz_admin_term_names( $post_id, 'pa_suesse' ) );
			break;
		case 'feinspitz_flags':
			$out = array();
			foreach ( feinspitz_admin_flag_labels() as $slug => $label ) {
				if ( has_term( $slug, 'product_tag', $post_id ) ) {
					$out[] = '<span class="feinspitz-flag">' . esc_html( $label ) . '</span>';
				}
			}
			echo $out ? implode( ' ', $out ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			break;
	}
}, 10, 2 );

/**
 * Kleines Styling für die Flag-Badges in der Produktliste.
 */
add_action( 'admin_head', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	?>
	<style>
	.column-feinspitz_rebsorte,.column-feinspitz_region,.column-feinspitz_suesse{width:9%}
	.column-feinspitz_flags{width:14%}
	.feinspitz-flag{display:inline-block;background:#f0e6ec;color:#7b1f2b;border:1px solid #e0c9d3;border-radius:999px;padding:.05rem .55rem;font-size:.72rem;line-height:1.6;white-space:nowrap;margin:1px 0}
	</style>
	<?php
} );

/* -------------------------------------------------------------------------
 * 3) Anfragen-Liste (CPT feinspitz_anfrage): lesbare Spalten.
 * ---------------------------------------------------------------------- */

/**
 * Deutsche Anzeige-Labels je Anfrage-Typ (Slug wie in inc/forms.php).
 *
 * @param string $type Typ-Slug.
 * @return string
 */
function feinspitz_admin_anfrage_typ_label( $type ) {
	$map = array(
		'kontakt'   => 'Kontakt',
		'weinprobe' => 'Weinprobe',
		'catering'  => 'Catering',
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : ( '' !== $type ? ucfirst( $type ) : '—' );
}

/**
 * Spalten der Anfragen-Liste definieren (Titel-Spalte ersetzen durch klare Felder).
 *
 * @param array<string,string> $columns Vorhandene Spalten.
 * @return array<string,string>
 */
add_filter( 'manage_edit-feinspitz_anfrage_columns', function ( $columns ) {
	return array(
		'cb'               => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
		'feinspitz_name'   => 'Name',
		'feinspitz_email'  => 'E-Mail',
		'feinspitz_typ'    => 'Typ',
		'feinspitz_datum'  => 'Datum',
	);
} );

/**
 * Inhalt der Anfragen-Spalten rendern (Werte aus dem Postmeta von inc/forms.php).
 *
 * @param string $column  Spaltenschlüssel.
 * @param int    $post_id Anfrage-ID.
 * @return void
 */
add_action( 'manage_feinspitz_anfrage_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'feinspitz_name':
			$name = get_post_meta( $post_id, '_feinspitz_name', true );
			$link = get_edit_post_link( $post_id );
			$text = '' !== $name ? $name : get_the_title( $post_id );
			if ( $link ) {
				printf( '<a href="%1$s"><strong>%2$s</strong></a>', esc_url( $link ), esc_html( $text ) );
			} else {
				echo '<strong>' . esc_html( $text ) . '</strong>';
			}
			break;
		case 'feinspitz_email':
			$email = get_post_meta( $post_id, '_feinspitz_email', true );
			if ( '' !== $email ) {
				printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
			} else {
				echo '—';
			}
			break;
		case 'feinspitz_typ':
			$type = get_post_meta( $post_id, '_feinspitz_type', true );
			echo esc_html( feinspitz_admin_anfrage_typ_label( (string) $type ) );
			break;
		case 'feinspitz_datum':
			echo esc_html( get_the_date( 'd.m.Y H:i', $post_id ) );
			break;
	}
}, 10, 2 );

/**
 * Datum-Spalte der Anfragen sortierbar machen.
 *
 * @param array<string,string> $columns Sortierbare Spalten.
 * @return array<string,string>
 */
add_filter( 'manage_edit-feinspitz_anfrage_sortable_columns', function ( $columns ) {
	$columns['feinspitz_datum'] = 'date';
	return $columns;
} );

/* -------------------------------------------------------------------------
 * 4) Aufräumen: Standard-Dashboard-Widgets reduzieren + Feinspitz-Widget.
 * ---------------------------------------------------------------------- */

/**
 * Anzahl gespeicherter Anfragen (CPT feinspitz_anfrage, Status private) zählen.
 *
 * @return int
 */
function feinspitz_admin_count_anfragen() {
	$counts = wp_count_posts( 'feinspitz_anfrage' );
	if ( ! $counts ) {
		return 0;
	}
	// Anfragen werden von inc/forms.php als „private" gespeichert.
	$total = 0;
	foreach ( array( 'private', 'publish', 'draft', 'pending' ) as $status ) {
		if ( isset( $counts->$status ) ) {
			$total += (int) $counts->$status;
		}
	}
	return $total;
}

/**
 * Dashboard aufräumen: laute Standard-Widgets entfernen + eigenes Widget setzen.
 */
add_action( 'wp_dashboard_setup', function () {
	global $wp_meta_boxes;

	// Irrelevante Standard-Widgets entfernen (WordPress-News, Events, Begrüssung etc.).
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );    // „WordPress-Veranstaltungen und Neuigkeiten".
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );  // Sekundäre News.
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' ); // „Schneller Entwurf".
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );

	// Feinspitz-Übersichts-Widget hinzufügen (oben, links).
	add_meta_box(
		'feinspitz_overview',
		'Feinspitz · Übersicht',
		'feinspitz_admin_render_overview_widget',
		'dashboard',
		'normal',
		'high'
	);

	// Eigenes Widget an den Anfang der normalen Spalte ziehen.
	if ( isset( $wp_meta_boxes['dashboard']['normal']['high']['feinspitz_overview'] ) ) {
		$widget = $wp_meta_boxes['dashboard']['normal']['high']['feinspitz_overview'];
		unset( $wp_meta_boxes['dashboard']['normal']['high']['feinspitz_overview'] );
		$wp_meta_boxes['dashboard']['normal']['high'] = array_merge(
			array( 'feinspitz_overview' => $widget ),
			$wp_meta_boxes['dashboard']['normal']['high']
		);
	}
} );

/**
 * Inhalt des Feinspitz-Übersichts-Widgets: Kennzahlen + Schnell-Links.
 */
function feinspitz_admin_render_overview_widget() {
	$count_products = (int) wp_count_posts( 'product' )->publish;
	$count_posts    = (int) wp_count_posts( 'post' )->publish;
	$count_anfragen = feinspitz_admin_count_anfragen();

	$dashboard   = admin_url( 'admin.php?page=' . FEINSPITZ_ADMIN_SLUG );
	$new_product = defined( 'FEINSPITZ_PRODUCT_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_PRODUCT_FORM_SLUG ) : admin_url( 'post-new.php?post_type=product' );
	$new_post    = defined( 'FEINSPITZ_ARTICLE_FORM_SLUG' ) ? admin_url( 'admin.php?page=' . FEINSPITZ_ARTICLE_FORM_SLUG ) : admin_url( 'post-new.php?post_type=post' );
	$anfragen    = admin_url( 'edit.php?post_type=feinspitz_anfrage' );
	?>
	<div class="feinspitz-widget">
		<ul class="feinspitz-widget__stats">
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><span class="feinspitz-widget__num"><?php echo esc_html( (string) $count_products ); ?></span> Produkte</a></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"><span class="feinspitz-widget__num"><?php echo esc_html( (string) $count_posts ); ?></span> Artikel</a></li>
			<li><a href="<?php echo esc_url( $anfragen ); ?>"><span class="feinspitz-widget__num"><?php echo esc_html( (string) $count_anfragen ); ?></span> Anfragen</a></li>
		</ul>
		<p class="feinspitz-widget__links">
			<a class="button button-primary" href="<?php echo esc_url( $new_product ); ?>">Neues Produkt</a>
			<a class="button" href="<?php echo esc_url( $new_post ); ?>">Neuer Artikel</a>
			<a class="button" href="<?php echo esc_url( $dashboard ); ?>">Zur Feinspitz-Übersicht</a>
		</p>
	</div>
	<style>
	.feinspitz-widget__stats{display:flex;gap:1rem;flex-wrap:wrap;margin:0 0 1rem;padding:0;list-style:none}
	.feinspitz-widget__stats li{flex:1 1 90px}
	.feinspitz-widget__stats a{display:block;text-decoration:none;color:#1d2327;background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:.6rem .75rem;text-align:center}
	.feinspitz-widget__stats a:hover{background:#f0f0f1}
	.feinspitz-widget__num{display:block;font-size:1.5rem;font-weight:700;color:#7b1f2b;line-height:1.1}
	.feinspitz-widget__links{display:flex;gap:.5rem;flex-wrap:wrap;margin:0}
	</style>
	<?php
}
