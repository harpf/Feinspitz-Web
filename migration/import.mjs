// Rahmen für die Content-Migration (feinspitz.ch → Server-WordPress).
// Wird im Branch feature/content-migration befüllt:
//   1) migration/scrape/*.mjs  → migration/data/*.json (+ Medien)
//   2) hier: idempotenter REST-Import (wc/v3 Produkte, wp/v2 Seiten/Medien,
//      Polylang-Verknüpfung DE/EN)
import { wp, WP_BASE } from '../scripts/lib/wp.mjs';

console.log(`Content-Import gegen ${WP_BASE}`);
console.log('TODO (feature/content-migration): Produkte, Kategorien, Seiten & Medien importieren.');

// Beispiel-Abfrage zur Verifikation der Verbindung:
try {
  const me = await wp('/wp/v2/users/me');
  console.log(`✓ Verbindung ok als ${me.name}. Bereit für Import-Implementierung.`);
} catch (e) {
  console.error(`✗ ${e.message}`);
  process.exitCode = 1;
}
