// Prüft die Server-Grundeinrichtung nach der Plugin-Installation und meldet,
// was automatisiert ist und was (Polylang-Sprachen) in Branch feature/i18n-multilingual
// bzw. einmalig per GUI erfolgt.
import { wp, WP_BASE } from './lib/wp.mjs';

const api = await fetch(`${WP_BASE}/?rest_route=/`).then((r) => r.json()).catch(() => ({}));
const ns = api.namespaces || [];
const has = (n) => ns.includes(n);

console.log(`REST-Root: ${WP_BASE}`);
console.log(`Namespaces: ${ns.join(', ') || '(keine)'}`);
console.log(`  WooCommerce (wc/v3): ${has('wc/v3') ? '✓ aktiv' : '✗ fehlt → npm run plugins:install'}`);

// Polylang registriert keinen eigenen REST-Namespace → über Plugin-Status prüfen.
let pllActive = false;
try {
  const plugins = await wp('/wp/v2/plugins', { query: { search: 'polylang' } });
  pllActive = Array.isArray(plugins) && plugins.some((p) => p.textdomain === 'polylang' && p.status === 'active');
} catch { /* ignore */ }
console.log(`  Polylang:            ${pllActive ? '✓ aktiv' : '✗ fehlt → npm run plugins:install'}`);

// WooCommerce-Grundseiten prüfen (werden bei Aktivierung automatisch erzeugt).
if (has('wc/v3')) {
  try {
    const settings = await wp('/wc/v3/settings/advanced', { });
    console.log('  WooCommerce-Settings erreichbar ✓');
  } catch (e) {
    console.log(`  WooCommerce-Settings: ${e.message}`);
  }
}

console.log('\nNächste Schritte:');
console.log('  • Polylang-Sprachen (DE Standard + EN) → feature/i18n-multilingual (scripts/i18n).');
console.log('  • Content-Migration → feature/content-migration (migration/import.mjs).');
