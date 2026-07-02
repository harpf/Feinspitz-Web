<?php
/**
 * Feinspitz · Anfrage-Formulare (feature/anfrage-formulare).
 *
 * Diese Datei wird von functions.php automatisch geladen (glob inc/*.php) und
 * gehört exklusiv dem Formular-Branch. Sie stellt ein schlankes, voll in das Theme
 * integriertes Anfrage-Formular bereit — bewusst OHNE Formular-Plugin, weil:
 *
 *   1. Plugin-Formulare liegen in Plugin-eigenen Tabellen (FluentForm) bzw. in einer
 *      nicht per REST exponierten CPT (Contact Form 7) → in unserem HTTP-only-Setup
 *      NICHT reproduzierbar per Skript befüllbar (der Kern-Grund für den Theme-Ansatz
 *      des gesamten Projekts).
 *   2. Der Server versendet aktuell KEINE Mails (wp_mail() schlägt fehl — SMTP fehlt);
 *      ein Plugin würde daran nichts ändern. Deshalb speichert dieses Formular jede
 *      Anfrage ZUSÄTZLICH als privaten Beitrag (CPT feinspitz_anfrage), damit keine
 *      Anfrage verloren geht, solange kein SMTP eingerichtet ist. Sobald SMTP steht,
 *      funktioniert der wp_mail()-Versand an info@feinspitz.ch ohne weitere Änderung.
 *
 * Bereitgestellt wird:
 *   - Shortcode [feinspitz_form type="kontakt|weinprobe|catering"] — rendert das
 *     jeweilige Formular im Theme-Stil, sprachbewusst (DE/EN via pll_current_language()).
 *   - Absenden per admin-ajax (action=feinspitz_form_submit), CSRF-Schutz per Nonce,
 *     Honeypot- + Zeit-Spamschutz. Funktioniert OHNE JavaScript (POST → Redirect mit
 *     Status) und mit leichter, progressiver JS-Verbesserung (Inline-Feedback).
 *   - Versand per wp_mail() an info@feinspitz.ch (filterbar) mit Reply-To des Absenders.
 *   - Gescopte Inline-Styles (an das Theme-Stylesheet gehängt, style.css bleibt Phase-0).
 *
 * Sprach-Strategie: Bewusst KEINE gettext-msgids für die Formular-Labels, sondern
 * eine kleine DE/EN-Inline-Auswahl (feinspitz_form_t) — analog zur EN-Kategorie-Map
 * in inc/shop.php. So bleibt der Formular-Wortschatz vollständig in DIESER Datei
 * (Dateibesitz) und bricht nicht die zentrale i18n-Build-Pipeline (make-po verlangt
 * für jede neue msgid eine EN-Übersetzung in translations.en.json).
 *
 * @package Feinspitz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standard-Empfänger aller Anfragen. Über den Filter 'feinspitz_form_recipient'
 * anpassbar.
 */
if ( ! defined( 'FEINSPITZ_FORM_RECIPIENT' ) ) {
	define( 'FEINSPITZ_FORM_RECIPIENT', 'info@feinspitz.ch' );
}

/**
 * Sprachbewusste Textauswahl (DE als Standard, EN auf englischen Polylang-Seiten).
 *
 * @param string $de Deutscher Text.
 * @param string $en Englischer Text.
 * @return string
 */
function feinspitz_form_t( $de, $en ) {
	if ( function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' ) ) {
		return $en;
	}
	return $de;
}

/**
 * Feld- und Textdefinition je Formulartyp — die EINE Quelle für Rendering,
 * Validierung und E-Mail-Aufbereitung.
 *
 * Jedes Feld: key, type (text|email|tel|date|number|textarea), required (bool),
 * Labels de/en, optional autocomplete/min/inputmode.
 *
 * @return array<string,array>
 */
function feinspitz_form_definitions() {
	$name  = array( 'key' => 'name', 'type' => 'text', 'required' => true, 'de' => 'Name', 'en' => 'Name', 'autocomplete' => 'name' );
	$email = array( 'key' => 'email', 'type' => 'email', 'required' => true, 'de' => 'E-Mail', 'en' => 'Email', 'autocomplete' => 'email' );
	$phone = array( 'key' => 'phone', 'type' => 'tel', 'required' => true, 'de' => 'Telefon', 'en' => 'Phone', 'autocomplete' => 'tel' );
	$date  = array( 'key' => 'date', 'type' => 'date', 'required' => true, 'de' => 'Wunschdatum', 'en' => 'Preferred date' );
	$msg_r = array( 'key' => 'message', 'type' => 'textarea', 'required' => true, 'de' => 'Nachricht', 'en' => 'Message' );
	$msg_o = array( 'key' => 'message', 'type' => 'textarea', 'required' => false, 'de' => 'Nachricht (optional)', 'en' => 'Message (optional)' );

	return array(
		'kontakt'   => array(
			'de'     => array(
				'eyebrow' => 'Kontakt',
				'title'   => 'Schreiben Sie uns',
				'intro'   => 'Fragen zu Weinen, Bestellungen oder Verträglichkeit? Wir melden uns persönlich bei Ihnen.',
				'submit'  => 'Nachricht senden',
			),
			'en'     => array(
				'eyebrow' => 'Contact',
				'title'   => 'Write to us',
				'intro'   => 'Questions about wines, orders or tolerance? We will get back to you personally.',
				'submit'  => 'Send message',
			),
			'fields' => array( $name, $email, $msg_r ),
		),
		'weinprobe' => array(
			'de'     => array(
				'eyebrow' => 'Weinprobe',
				'title'   => 'Weinprobe anfragen',
				'intro'   => 'Sagen Sie uns, wann und für wie viele Personen — wir stellen die passende Verkostung zusammen.',
				'submit'  => 'Weinprobe anfragen',
			),
			'en'     => array(
				'eyebrow' => 'Wine tasting',
				'title'   => 'Request a tasting',
				'intro'   => 'Tell us when and for how many guests — we will put together the right tasting for you.',
				'submit'  => 'Request tasting',
			),
			'fields' => array(
				$name,
				$email,
				$phone,
				$date,
				array( 'key' => 'guests', 'type' => 'number', 'required' => true, 'de' => 'Personenzahl', 'en' => 'Number of guests', 'min' => 1, 'inputmode' => 'numeric' ),
				$msg_o,
			),
		),
		'catering'  => array(
			'de'     => array(
				'eyebrow' => 'Catering',
				'title'   => 'Catering anfragen',
				'intro'   => 'Vom Apéro bis zum Grillevent — beschreiben Sie Ihren Anlass, wir gestalten ihn kulinarisch.',
				'submit'  => 'Catering anfragen',
			),
			'en'     => array(
				'eyebrow' => 'Catering',
				'title'   => 'Request catering',
				'intro'   => 'From apéro to grill event — describe your occasion and we will design it culinarily.',
				'submit'  => 'Request catering',
			),
			'fields' => array(
				$name,
				$email,
				$phone,
				array( 'key' => 'event', 'type' => 'text', 'required' => true, 'de' => 'Art des Events', 'en' => 'Type of event' ),
				array( 'key' => 'date', 'type' => 'date', 'required' => true, 'de' => 'Datum', 'en' => 'Date' ),
				array( 'key' => 'guests', 'type' => 'number', 'required' => true, 'de' => 'Anzahl Gäste', 'en' => 'Number of guests', 'min' => 1, 'inputmode' => 'numeric' ),
				$msg_o,
			),
		),
	);
}

/**
 * Definition eines Typs holen; unbekannte Typen fallen auf "kontakt" zurück.
 *
 * @param string $type Formulartyp.
 * @return array{type:string,config:array}
 */
function feinspitz_form_config( $type ) {
	$defs = feinspitz_form_definitions();
	$type = is_string( $type ) ? sanitize_key( $type ) : '';
	if ( ! isset( $defs[ $type ] ) ) {
		$type = 'kontakt';
	}
	return array( 'type' => $type, 'config' => $defs[ $type ] );
}

/**
 * Shortcode [feinspitz_form type="kontakt|weinprobe|catering"].
 *
 * @param array $atts Attribute.
 * @return string HTML.
 */
function feinspitz_form_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'kontakt' ), $atts, 'feinspitz_form' );
	list( $type, $config ) = array_values( feinspitz_form_config( $atts['type'] ) );

	$lang = feinspitz_form_t( 'de', 'en' );
	$t    = $config[ $lang ];
	$dom  = 'feinspitz-form-' . $type;

	// Merken, dass ein Formular gerendert wurde → Footer-JS ausgeben.
	$GLOBALS['feinspitz_form_rendered'] = true;

	$out  = '<section id="' . esc_attr( $dom ) . '" class="feinspitz-form feinspitz-form--' . esc_attr( $type ) . '">';
	$out .= '<div class="feinspitz-form__inner">';

	// Status-Meldung (No-JS-Redirect-Rückweg).
	$out .= feinspitz_form_notice( $type );

	$out .= '<p class="feinspitz-form__eyebrow">' . esc_html( $t['eyebrow'] ) . '</p>';
	$out .= '<h2 class="feinspitz-form__title">' . esc_html( $t['title'] ) . '</h2>';
	$out .= '<p class="feinspitz-form__intro">' . esc_html( $t['intro'] ) . '</p>';

	$redirect = feinspitz_form_current_url() . '#' . $dom;

	$out .= '<form class="feinspitz-form__form" method="post" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" novalidate>';
	$out .= '<input type="hidden" name="action" value="feinspitz_form_submit">';
	$out .= '<input type="hidden" name="feinspitz_type" value="' . esc_attr( $type ) . '">';
	$out .= '<input type="hidden" name="feinspitz_redirect" value="' . esc_url( $redirect ) . '">';
	$out .= '<input type="hidden" name="feinspitz_ts" value="' . esc_attr( (string) time() ) . '">';
	$out .= wp_nonce_field( 'feinspitz_form', 'feinspitz_nonce', false, false );

	// Honeypot: für Menschen unsichtbar (CSS + aria-hidden + tabindex). Bots füllen es.
	$out .= '<div class="feinspitz-form__hp" aria-hidden="true">';
	$out .= '<label>' . esc_html( feinspitz_form_t( 'Ihre Website (bitte leer lassen)', 'Your website (please leave empty)' ) ) . '</label>';
	$out .= '<input type="text" name="feinspitz_url" tabindex="-1" autocomplete="off" value="">';
	$out .= '</div>';

	$out .= '<div class="feinspitz-form__grid">';
	foreach ( $config['fields'] as $field ) {
		$out .= feinspitz_form_field_html( $field, $lang, $dom );
	}
	$out .= '</div>';

	$out .= '<div class="feinspitz-form__actions">';
	$out .= '<button type="submit" class="feinspitz-form__submit">' . esc_html( $t['submit'] ) . '</button>';
	$out .= '<span class="feinspitz-form__spinner" aria-hidden="true"></span>';
	$out .= '</div>';

	$out .= '</form>';
	$out .= '</div></section>';

	return $out;
}

/**
 * Ein einzelnes Feld als Label + Eingabe rendern.
 *
 * @param array  $field Felddefinition.
 * @param string $lang  'de' | 'en'.
 * @param string $dom   ID-Präfix (pro Formular eindeutig).
 * @return string
 */
function feinspitz_form_field_html( $field, $lang, $dom ) {
	$key      = $field['key'];
	$id       = $dom . '-' . $key;
	$label    = $field[ $lang ];
	$required = ! empty( $field['required'] );
	$wide     = in_array( $field['type'], array( 'textarea' ), true ) || 'event' === $key;

	$attrs  = 'id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '"';
	$attrs .= $required ? ' required' : '';
	if ( ! empty( $field['autocomplete'] ) ) {
		$attrs .= ' autocomplete="' . esc_attr( $field['autocomplete'] ) . '"';
	}
	if ( isset( $field['min'] ) ) {
		$attrs .= ' min="' . esc_attr( (string) $field['min'] ) . '"';
	}
	if ( ! empty( $field['inputmode'] ) ) {
		$attrs .= ' inputmode="' . esc_attr( $field['inputmode'] ) . '"';
	}

	$req_mark = $required ? ' <span class="feinspitz-form__req" aria-hidden="true">*</span>' : '';

	$html  = '<div class="feinspitz-form__field' . ( $wide ? ' feinspitz-form__field--wide' : '' ) . '">';
	$html .= '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . $req_mark . '</label>';

	if ( 'textarea' === $field['type'] ) {
		$html .= '<textarea ' . $attrs . ' rows="5"></textarea>';
	} else {
		$html .= '<input type="' . esc_attr( $field['type'] ) . '" ' . $attrs . '>';
	}

	$html .= '</div>';
	return $html;
}

/**
 * Aktuelle Frontend-URL (für den Redirect-Rückweg) ermitteln.
 *
 * @return string
 */
function feinspitz_form_current_url() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( '' === $host || '' === $uri ) {
		return home_url( '/' );
	}
	$scheme = is_ssl() ? 'https' : 'http';
	// Query-Parameter des Formular-Status entfernen, damit die URL sauber bleibt.
	$uri = remove_query_arg( array( 'feinspitz_sent', 'feinspitz_error' ), $scheme . '://' . $host . $uri );
	return $uri;
}

/**
 * Erfolgs-/Fehlermeldung nach No-JS-Redirect rendern (per Typ gescoped).
 *
 * @param string $type Formulartyp.
 * @return string
 */
function feinspitz_form_notice( $type ) {
	$sent  = isset( $_GET['feinspitz_sent'] ) ? sanitize_key( wp_unslash( $_GET['feinspitz_sent'] ) ) : '';
	$error = isset( $_GET['feinspitz_error'] ) ? sanitize_key( wp_unslash( $_GET['feinspitz_error'] ) ) : '';

	if ( $sent === $type ) {
		return '<div class="feinspitz-form__notice feinspitz-form__notice--ok" role="status">'
			. esc_html( feinspitz_form_t(
				'Vielen Dank! Ihre Anfrage ist bei uns eingegangen — wir melden uns zeitnah bei Ihnen.',
				'Thank you! We have received your request and will get back to you shortly.'
			) )
			. '</div>';
	}
	if ( $error === $type ) {
		return '<div class="feinspitz-form__notice feinspitz-form__notice--err" role="alert">'
			. esc_html( feinspitz_form_t(
				'Bitte prüfen Sie Ihre Eingaben — einige Pflichtfelder fehlen oder sind ungültig.',
				'Please check your entries — some required fields are missing or invalid.'
			) )
			. '</div>';
	}
	return '';
}

/**
 * Shortcode registrieren.
 */
add_action( 'init', function () {
	add_shortcode( 'feinspitz_form', 'feinspitz_form_shortcode' );
} );

/**
 * CPT feinspitz_anfrage — speichert jede Anfrage als privaten Beitrag, damit auch
 * ohne funktionierenden Mailversand keine Anfrage verloren geht. Nur im Backend
 * sichtbar (public=false, show_ui=true).
 */
add_action( 'init', function () {
	register_post_type(
		'feinspitz_anfrage',
		array(
			'labels'              => array(
				'name'          => __( 'Anfragen', 'feinspitz' ),
				'singular_name' => __( 'Anfrage', 'feinspitz' ),
				'menu_name'     => __( 'Anfragen', 'feinspitz' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);
} );

/**
 * admin-ajax-Handler (eingeloggt + nicht eingeloggt).
 */
add_action( 'wp_ajax_feinspitz_form_submit', 'feinspitz_form_handle_submit' );
add_action( 'wp_ajax_nopriv_feinspitz_form_submit', 'feinspitz_form_handle_submit' );

/**
 * Formular verarbeiten: Nonce + Spam prüfen, validieren, speichern, mailen.
 *
 * Antwortet bei AJAX (feinspitz_ajax=1) als JSON, sonst per Redirect zurück auf die
 * Seite (No-JS-Fallback).
 */
function feinspitz_form_handle_submit() {
	$is_ajax = ! empty( $_POST['feinspitz_ajax'] );

	$type_raw          = isset( $_POST['feinspitz_type'] ) ? sanitize_key( wp_unslash( $_POST['feinspitz_type'] ) ) : 'kontakt';
	list( $type, $config ) = array_values( feinspitz_form_config( $type_raw ) );

	$redirect = isset( $_POST['feinspitz_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['feinspitz_redirect'] ) ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

	// Nonce.
	$nonce = isset( $_POST['feinspitz_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['feinspitz_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'feinspitz_form' ) ) {
		return feinspitz_form_respond( $is_ajax, false, $type, $redirect, __( 'Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'feinspitz' ) );
	}

	// Spamschutz: Honeypot befüllt ODER zu schnell abgeschickt → still als „ok"
	// behandeln (Bot nicht informieren), aber NICHTS senden/speichern.
	$hp = isset( $_POST['feinspitz_url'] ) ? trim( (string) wp_unslash( $_POST['feinspitz_url'] ) ) : '';
	$ts = isset( $_POST['feinspitz_ts'] ) ? (int) $_POST['feinspitz_ts'] : 0;
	if ( '' !== $hp || ( $ts > 0 && ( time() - $ts ) < 2 ) ) {
		return feinspitz_form_respond( $is_ajax, true, $type, $redirect, '' );
	}

	// Felder einsammeln + validieren.
	$values = array();
	$errors = array();
	foreach ( $config['fields'] as $field ) {
		$key = $field['key'];
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';

		if ( 'email' === $field['type'] ) {
			$val = sanitize_email( $raw );
			if ( ! empty( $field['required'] ) && ( '' === $val || ! is_email( $val ) ) ) {
				$errors[] = $key;
			}
		} elseif ( 'textarea' === $field['type'] ) {
			$val = sanitize_textarea_field( $raw );
			if ( ! empty( $field['required'] ) && '' === trim( $val ) ) {
				$errors[] = $key;
			}
		} else {
			$val = sanitize_text_field( $raw );
			if ( ! empty( $field['required'] ) && '' === trim( $val ) ) {
				$errors[] = $key;
			}
		}
		$values[ $key ] = $val;
	}

	if ( ! empty( $errors ) ) {
		return feinspitz_form_respond( $is_ajax, false, $type, $redirect, __( 'Bitte prüfen Sie Ihre Eingaben.', 'feinspitz' ) );
	}

	// Persistieren (Resilienz gegen fehlenden Mailversand) + mailen.
	feinspitz_form_store( $type, $config, $values );
	feinspitz_form_send_mail( $type, $config, $values );

	return feinspitz_form_respond( $is_ajax, true, $type, $redirect, '' );
}

/**
 * Anfrage als privaten CPT-Beitrag speichern.
 *
 * @param string $type   Typ.
 * @param array  $config Konfiguration.
 * @param array  $values Sanitisierte Werte.
 * @return void
 */
function feinspitz_form_store( $type, $config, $values ) {
	$labels = feinspitz_form_labels( $config, 'de' );
	$name   = isset( $values['name'] ) ? $values['name'] : '';

	$lines = array();
	foreach ( $config['fields'] as $field ) {
		$k = $field['key'];
		$lines[] = $labels[ $k ] . ': ' . ( isset( $values[ $k ] ) ? $values[ $k ] : '' );
	}

	$title = sprintf(
		'%s · %s · %s',
		ucfirst( $type ),
		$name !== '' ? $name : __( 'Anfrage', 'feinspitz' ),
		wp_date( 'Y-m-d H:i' )
	);

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'feinspitz_anfrage',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => implode( "\n", $lines ),
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_feinspitz_type', $type );
		foreach ( $values as $k => $v ) {
			update_post_meta( $post_id, '_feinspitz_' . $k, $v );
		}
	}
}

/**
 * Anfrage per wp_mail an den Empfänger senden.
 *
 * @param string $type   Typ.
 * @param array  $config Konfiguration.
 * @param array  $values Sanitisierte Werte.
 * @return bool Ob wp_mail() den Versand angenommen hat.
 */
function feinspitz_form_send_mail( $type, $config, $values ) {
	$recipient = apply_filters( 'feinspitz_form_recipient', FEINSPITZ_FORM_RECIPIENT, $type );
	$labels    = feinspitz_form_labels( $config, 'de' );

	$subjects = array(
		'kontakt'   => 'Neue Kontaktanfrage über feinspitz.ch',
		'weinprobe' => 'Neue Weinprobe-Anfrage über feinspitz.ch',
		'catering'  => 'Neue Catering-Anfrage über feinspitz.ch',
	);
	$subject = isset( $subjects[ $type ] ) ? $subjects[ $type ] : 'Neue Anfrage über feinspitz.ch';

	$lines = array( $subject, str_repeat( '=', strlen( $subject ) ), '' );
	foreach ( $config['fields'] as $field ) {
		$k       = $field['key'];
		$lines[] = $labels[ $k ] . ': ' . ( isset( $values[ $k ] ) ? $values[ $k ] : '' );
	}
	$lines[] = '';
	$lines[] = '— Gesendet über das Anfrage-Formular auf ' . home_url( '/' );
	$body    = implode( "\n", $lines );

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $values['email'] ) && is_email( $values['email'] ) ) {
		$reply_name = ! empty( $values['name'] ) ? $values['name'] : $values['email'];
		$headers[]  = sprintf( 'Reply-To: %s <%s>', $reply_name, $values['email'] );
	}

	return wp_mail( $recipient, $subject, $body, $headers );
}

/**
 * Deutsche Feld-Labels als key=>label-Map (für E-Mail/Speicherung).
 *
 * @param array  $config Konfiguration.
 * @param string $lang   Sprache.
 * @return array<string,string>
 */
function feinspitz_form_labels( $config, $lang = 'de' ) {
	$map = array();
	foreach ( $config['fields'] as $field ) {
		$map[ $field['key'] ] = isset( $field[ $lang ] ) ? $field[ $lang ] : $field['key'];
	}
	return $map;
}

/**
 * Einheitliche Antwort: JSON (AJAX) oder Redirect (No-JS).
 *
 * @param bool   $is_ajax Ob per AJAX angefragt.
 * @param bool   $ok      Erfolg.
 * @param string $type    Formulartyp.
 * @param string $redirect Ziel-URL (bereits validiert).
 * @param string $message  Fehlermeldung (nur bei Fehler relevant).
 * @return void
 */
function feinspitz_form_respond( $is_ajax, $ok, $type, $redirect, $message ) {
	if ( $is_ajax ) {
		if ( $ok ) {
			wp_send_json_success( array( 'type' => $type ) );
		} else {
			wp_send_json_error( array( 'type' => $type, 'message' => $message ) );
		}
	}

	$arg      = $ok ? 'feinspitz_sent' : 'feinspitz_error';
	$base     = remove_query_arg( array( 'feinspitz_sent', 'feinspitz_error' ), $redirect );
	$fragment = '';
	if ( false !== strpos( $base, '#' ) ) {
		list( $base, $fragment ) = explode( '#', $base, 2 );
	}
	$target = add_query_arg( $arg, $type, $base );
	if ( '' !== $fragment ) {
		$target .= '#' . $fragment;
	}
	wp_safe_redirect( $target );
	exit;
}

/**
 * Progressive JS-Verbesserung: Formular per fetch absenden und Inline-Feedback zeigen.
 * Nur ausgegeben, wenn auf der Seite ein Formular gerendert wurde. Ohne JS bleibt der
 * normale POST→Redirect-Weg voll funktionsfähig.
 */
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['feinspitz_form_rendered'] ) ) {
		return;
	}
	$ok_msg  = esc_js( feinspitz_form_t(
		'Vielen Dank! Ihre Anfrage ist bei uns eingegangen — wir melden uns zeitnah bei Ihnen.',
		'Thank you! We have received your request and will get back to you shortly.'
	) );
	$err_msg = esc_js( feinspitz_form_t(
		'Bitte prüfen Sie Ihre Eingaben — einige Pflichtfelder fehlen oder sind ungültig.',
		'Please check your entries — some required fields are missing or invalid.'
	) );
	?>
<script>
( function () {
	var forms = document.querySelectorAll( '.feinspitz-form__form' );
	forms.forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			if ( ! window.fetch || ! window.FormData ) { return; } // Fallback: normaler POST.
			e.preventDefault();
			var section = form.closest( '.feinspitz-form' );
			var btn = form.querySelector( '.feinspitz-form__submit' );
			var data = new FormData( form );
			data.append( 'feinspitz_ajax', '1' );
			if ( btn ) { btn.disabled = true; }
			if ( section ) { section.classList.add( 'is-sending' ); }
			fetch( form.getAttribute( 'action' ), { method: 'POST', body: data, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success ) {
						var ok = document.createElement( 'div' );
						ok.className = 'feinspitz-form__notice feinspitz-form__notice--ok';
						ok.setAttribute( 'role', 'status' );
						ok.textContent = '<?php echo $ok_msg; ?>';
						form.replaceWith( ok );
					} else {
						showError();
					}
				} )
				.catch( showError );
			function showError() {
				if ( btn ) { btn.disabled = false; }
				if ( section ) { section.classList.remove( 'is-sending' ); }
				var existing = form.parentNode.querySelector( '.feinspitz-form__notice--err' );
				if ( existing ) { return; }
				var err = document.createElement( 'div' );
				err.className = 'feinspitz-form__notice feinspitz-form__notice--err';
				err.setAttribute( 'role', 'alert' );
				err.textContent = '<?php echo $err_msg; ?>';
				form.parentNode.insertBefore( err, form );
			}
		} );
	} );
}() );
</script>
	<?php
}, 30 );

/**
 * Gescopte Formular-Styles · an das Theme-Stylesheet gehängt (style.css bleibt
 * Phase-0-unangetastet), mit Fallback-Handle. Bold-Design-Tokens des Themes.
 */
add_action( 'wp_enqueue_scripts', function () {
	$css = '
.feinspitz-form{--ff-wine:var(--wp--preset--color--wine,#7b1f2b);--ff-gold:var(--wp--preset--color--gold,#c9a24b);--ff-ink:var(--wp--preset--color--base,#0e0b08);background:#fff;border:1px solid rgba(14,11,8,.1);border-top:4px solid var(--ff-wine);border-radius:16px;padding:clamp(1.5rem,4vw,2.75rem);margin:2rem auto;max-width:760px;box-shadow:0 24px 60px -48px rgba(14,11,8,.6)}
.feinspitz-form__eyebrow{text-transform:uppercase;letter-spacing:.28em;font-weight:600;font-size:.72rem;color:var(--ff-wine);margin:0 0 .5rem}
.feinspitz-form__title{font-family:var(--wp--preset--font-family--heading,serif);font-weight:600;line-height:1.1;font-size:clamp(1.5rem,4vw,2rem);margin:0 0 .5rem;color:var(--ff-ink)}
.feinspitz-form__intro{color:rgba(14,11,8,.72);margin:0 0 1.75rem;font-size:1rem;line-height:1.6}
.feinspitz-form__grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1.1rem 1.25rem}
.feinspitz-form__field{display:flex;flex-direction:column;gap:.4rem;min-width:0}
.feinspitz-form__field--wide{grid-column:1/-1}
.feinspitz-form__field label{font-weight:600;font-size:.82rem;letter-spacing:.02em;color:var(--ff-ink)}
.feinspitz-form__req{color:var(--ff-wine)}
.feinspitz-form__field input,.feinspitz-form__field textarea{font:inherit;color:var(--ff-ink);background:#faf8f5;border:1px solid rgba(14,11,8,.18);border-radius:10px;padding:.7rem .85rem;width:100%;transition:border-color .15s ease,box-shadow .15s ease}
.feinspitz-form__field input:focus,.feinspitz-form__field textarea:focus{outline:none;border-color:var(--ff-gold);box-shadow:0 0 0 3px rgba(201,162,75,.22)}
.feinspitz-form__field textarea{resize:vertical;min-height:7rem}
.feinspitz-form__hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}
.feinspitz-form__actions{display:flex;align-items:center;gap:1rem;margin-top:1.6rem}
.feinspitz-form__submit{font:inherit;font-weight:600;letter-spacing:.02em;cursor:pointer;background:var(--ff-wine);color:#fff;border:0;border-radius:999px;padding:.85rem 1.9rem;transition:transform .15s ease,background .15s ease,opacity .15s ease}
.feinspitz-form__submit:hover{background:#611620;transform:translateY(-1px)}
.feinspitz-form__submit:disabled{opacity:.6;cursor:progress}
.feinspitz-form.is-sending .feinspitz-form__spinner{width:1.1rem;height:1.1rem;border-radius:50%;border:2px solid rgba(123,31,43,.25);border-top-color:var(--ff-wine);display:inline-block;animation:feinspitz-spin .7s linear infinite}
@keyframes feinspitz-spin{to{transform:rotate(360deg)}}
.feinspitz-form__notice{border-radius:12px;padding:.9rem 1.1rem;margin:0 0 1.5rem;font-size:.95rem;line-height:1.5}
.feinspitz-form__notice--ok{background:rgba(122,143,106,.16);border:1px solid rgba(122,143,106,.5);color:#2f4022}
.feinspitz-form__notice--err{background:rgba(123,31,43,.08);border:1px solid rgba(123,31,43,.4);color:var(--ff-wine)}
@media (max-width:600px){.feinspitz-form__grid{grid-template-columns:1fr}}
';
	if ( wp_style_is( 'feinspitz-style', 'registered' ) || wp_style_is( 'feinspitz-style', 'enqueued' ) ) {
		wp_add_inline_style( 'feinspitz-style', $css );
	} else {
		wp_register_style( 'feinspitz-forms-inline', false );
		wp_enqueue_style( 'feinspitz-forms-inline' );
		wp_add_inline_style( 'feinspitz-forms-inline', $css );
	}
}, 20 );
