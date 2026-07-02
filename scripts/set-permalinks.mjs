// Aktiviert "hübsche" Permalinks (/%postname%/) UND deutsche WooCommerce-URL-Basen
// (/produkt/, /produkt-kategorie/, /produkt-schlagwort/). Voraussetzung für
// funktionierende, deutsche Shop-/Kategorie-/Produkt-/Tag-URLs.
// Ohne WP-CLI via wp-admin-Form (cookie-auth). Idempotent.
import { wpAdminSession, WP_BASE } from './lib/wp.mjs';

const STRUCTURE = '/%postname%/';

const session = await wpAdminSession();
const page = await session.get('/wp-admin/options-permalink.php');
const grab = (name) =>
  (page.match(new RegExp(`name=["']${name}["']\\s+value=["']([^"']*)["']`, 'i')) || [])[1];

const wpNonce = grab('_wpnonce');
const wcNonce = grab('wc-permalinks-nonce');
if (!wpNonce) throw new Error('Konnte _wpnonce für options-permalink nicht finden.');

const form = new URLSearchParams({
  _wpnonce: wpNonce,
  _wp_http_referer: '/wp-admin/options-permalink.php',
  selection: STRUCTURE,
  permalink_structure: STRUCTURE,
  category_base: '',
  tag_base: '',
  // WooCommerce (deutsche Basen)
  'wc-permalinks-nonce': wcNonce || '',
  product_permalink: '/produkt/',
  product_permalink_structure: '/produkt/',
  woocommerce_product_category_slug: 'produkt-kategorie',
  woocommerce_product_tag_slug: 'produkt-schlagwort',
  woocommerce_product_attribute_slug: '',
  submit: 'Änderungen übernehmen',
});

const res = await session.post('/wp-admin/options-permalink.php', form);
if (res.status === 302) await session.get('/wp-admin/options-permalink.php');
console.log('✓ Permalinks + WooCommerce-Basen gesetzt (/%postname%/, /produkt/, /produkt-kategorie/, /produkt-schlagwort/).');

// Verifikation (Redirects folgen).
const probe = async (u) => { const r = await fetch(`${WP_BASE}${u}`); return `${r.status} ${r.redirected ? '→ ' + new URL(r.url).pathname : ''}`; };
console.log('  /shop/                          ', await probe('/shop/'));
console.log('  /produkt-kategorie/weissweine/  ', await probe('/produkt-kategorie/weissweine/'));
console.log('  /produkt-schlagwort/vegan/      ', await probe('/produkt-schlagwort/vegan/'));
console.log('  /produkt/kochbuch-fuer-histaminarme-kueche/ ', await probe('/produkt/kochbuch-fuer-histaminarme-kueche/'));
