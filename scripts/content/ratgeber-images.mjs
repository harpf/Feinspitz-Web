// Setzt passende Beitragsbilder (Featured Images) für die Ratgeber-Artikel (DE+EN).
// Bilder stammen aus dem Theme-Asset-Pool (Eigentum des Betreibers) und werden
// idempotent in die Mediathek geladen (Abgleich per Slug) und als featured_media
// gesetzt. HTTP-only via scripts/lib/wp.mjs.
import { readFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { wp, WP_BASE, wpAdminSession } from '../lib/wp.mjs';

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const ASSETS = join( __dirname, '..', '..', 'theme', 'feinspitz', 'assets', 'images' );

// DE-Slug → [Bilddatei, Alt-Text]
const MAP = {
  'histaminarm-geniessen-worauf-es-bei-wein-ankommt': [ 'shop-histamin.jpg', 'Histamingeprüfte Weine im Feinspitz-Laden' ],
  'histamin-und-wein-einfach-erklaert': [ 'steyrer-weine.jpg', 'Ausgezeichnete Weine vom Weingut Steyrer' ],
  'vegane-weine-was-bedeutet-das': [ 'natur-winzer.jpg', 'Weinberg und Winzer - im Einklang mit der Natur' ],
  'weingenuss-tipps-temperatur-glas-kombination': [ 'weinprobe-messe.jpg', 'Weinprobe an einem Feinspitz-Stand' ],
};

let session = null, nonce = null;
async function ensureSession() {
  if ( session ) return;
  session = await wpAdminSession();
  nonce = ( await session.get( '/wp-admin/admin-ajax.php?action=rest-nonce' ) ).trim();
}

/** Lädt ein Asset-Bild idempotent in die Mediathek; gibt media-id zurück. */
async function ensureMedia( file, alt ) {
  const slug = 'ratgeber-' + file.replace( /\.[a-z]+$/i, '' );
  const found = await wp( '/wp/v2/media', { query: { search: slug, per_page: 10 } } ).catch( () => [] );
  const hit = Array.isArray( found ) ? found.find( ( m ) => m.slug === slug || m.slug.startsWith( slug ) ) : null;
  if ( hit ) return hit.id;

  const path = join( ASSETS, file );
  if ( ! existsSync( path ) ) throw new Error( 'Asset fehlt: ' + path );
  await ensureSession();
  const res = await fetch( `${WP_BASE}/?rest_route=/wp/v2/media`, {
    method: 'POST',
    headers: {
      Cookie: session.cookieHeader(), 'X-WP-Nonce': nonce,
      'Content-Type': 'image/jpeg', 'Content-Disposition': `attachment; filename="${slug}.jpg"`,
    },
    body: readFileSync( path ),
  } );
  const text = await res.text();
  if ( ! res.ok ) throw new Error( `Media-Upload ${file} → ${res.status}: ${text.slice( 0, 120 )}` );
  const media = JSON.parse( text );
  await wp( `/wp/v2/media/${media.id}`, { method: 'POST', body: { alt_text: alt, title: alt } } ).catch( () => {} );
  return media.id;
}

for ( const [ slug, [ file, alt ] ] of Object.entries( MAP ) ) {
  const de = ( await wp( '/wp/v2/posts', { query: { slug, context: 'edit', status: 'any' } } ) )[ 0 ];
  if ( ! de ) { console.log( `- ${slug}: nicht gefunden` ); continue; }

  const mediaId = await ensureMedia( file, alt );

  const ids = new Set( [ de.id ] );
  if ( de.pll_translations ) for ( const id of Object.values( de.pll_translations ) ) ids.add( id );

  for ( const id of ids ) {
    const post = await wp( `/wp/v2/posts/${id}`, { query: { context: 'edit' } } );
    if ( Number( post.featured_media ) === Number( mediaId ) ) {
      console.log( `= Post ${id} (${post.slug}): Bild bereits gesetzt` );
      continue;
    }
    await wp( `/wp/v2/posts/${id}`, { method: 'POST', body: { featured_media: mediaId } } );
    console.log( `✓ Post ${id} (${post.slug}, ${post.lang || '?'}): Bild ${file} (media ${mediaId})` );
  }
}
console.log( '\nFertig (idempotent).' );
