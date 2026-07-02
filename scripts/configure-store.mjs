// Reproduzierbare WooCommerce-Store-Grundkonfiguration (idempotent, HTTP-only):
//   • Währung CHF
//   • Shop öffentlich (Coming-Soon aus)
// Ergänzt scripts/set-permalinks.mjs (URL-Basen) und scripts/bootstrap.mjs (Plugins).
import { wc, wp, WP_BASE } from './lib/wp.mjs';

// 1) Währung CHF.
const cur = await wc('/settings/general/woocommerce_currency');
if (cur.value !== 'CHF') {
  await wc('/settings/general/woocommerce_currency', { method: 'PUT', body: { value: 'CHF' } });
  console.log('✓ Währung auf CHF gesetzt (war ' + cur.value + ').');
} else {
  console.log('= Währung bereits CHF.');
}

// 2) Coming-Soon aus (Shop live).
const vis = await wp('/wc-admin/options', { query: { options: 'woocommerce_coming_soon' } }).catch(() => ({}));
if (vis.woocommerce_coming_soon !== 'no') {
  await wp('/wc-admin/options', { method: 'POST', body: { woocommerce_coming_soon: 'no' } });
  console.log('✓ Coming-Soon deaktiviert (Shop live).');
} else {
  console.log('= Shop bereits live (Coming-Soon aus).');
}

// Verifikation.
const shop = await fetch(`${WP_BASE}/shop/`).then((r) => r.text());
console.log(`Frontend /shop/: coming-soon ${/wp-block-woocommerce-coming-soon/.test(shop) ? 'NOCH da ✗' : 'aus ✓'}, Produkte ${new Set(shop.match(/\/produkt\/[a-z0-9-]+/g) || []).size}`);
