// Legt die fehlenden Rechtsseiten als DEUTSCHE STANDARD-VORLAGEN an bzw. füllt sie
// und veröffentlicht sie. WICHTIG: Vorlagen als Ausgangspunkt — vor produktivem
// Einsatz rechtlich prüfen/finalisieren lassen. Idempotent (Abgleich per Slug).
import { wp } from '../lib/wp.mjs';

const NOTE = '<p><em>Hinweis: Diese Seite ist eine Vorlage als Ausgangspunkt und ersetzt keine Rechtsberatung. Bitte vor dem produktiven Einsatz prüfen und anpassen.</em></p>';

const ADDR = 'Feinspitz, Angelika Paierl<br>Bahnhofstrasse 80, CH-8902 Urdorf<br>Telefon: +41 76 588 89 02<br>E-Mail: <a href="mailto:info@feinspitz.ch">info@feinspitz.ch</a>';

const PAGES = [
  {
    slug: 'datenschutzerklaerung',
    title: 'Datenschutzerklärung',
    content: `${NOTE}
<h3>1. Verantwortliche Stelle</h3><p>${ADDR}</p>
<h3>2. Erhebung und Bearbeitung von Personendaten</h3><p>Wir bearbeiten Personendaten, die Sie uns im Rahmen einer Bestellung, Kontaktaufnahme oder Anmeldung mitteilen (z. B. Name, Adresse, E-Mail-Adresse, Telefonnummer, Bestell- und Zahlungsdaten), sowie Daten, die beim Besuch unserer Website automatisch anfallen (z. B. IP-Adresse, Browsertyp, Zugriffszeit).</p>
<h3>3. Zweck und Rechtsgrundlagen</h3><p>Die Bearbeitung erfolgt zur Vertragsabwicklung (Bestellung, Lieferung, Zahlung), zur Kommunikation mit Ihnen, zur Erfüllung gesetzlicher Pflichten sowie zur Sicherstellung von Betrieb und Sicherheit unserer Website. Massgebend sind das Schweizer Datenschutzgesetz (DSG) und, soweit anwendbar, die EU-DSGVO.</p>
<h3>4. Weitergabe an Dritte</h3><p>Personendaten geben wir nur weiter, soweit dies zur Vertragsabwicklung erforderlich ist (z. B. an Logistik- und Zahlungsdienstleister) oder wir gesetzlich dazu verpflichtet sind. Eine Bekanntgabe ins Ausland erfolgt nur unter Einhaltung der gesetzlichen Voraussetzungen.</p>
<h3>5. Cookies</h3><p>Unsere Website verwendet Cookies. Einzelheiten finden Sie in unserer <a href="/cookie-richtlinie/">Cookie-Richtlinie</a>.</p>
<h3>6. Ihre Rechte</h3><p>Sie haben im Rahmen des anwendbaren Rechts das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung und Herausgabe Ihrer Personendaten sowie das Recht, eine erteilte Einwilligung zu widerrufen. Wenden Sie sich dazu an <a href="mailto:info@feinspitz.ch">info@feinspitz.ch</a>.</p>
<h3>7. Datensicherheit</h3><p>Wir treffen angemessene technische und organisatorische Massnahmen, um Ihre Daten vor Verlust, Missbrauch und unberechtigtem Zugriff zu schützen.</p>
<h3>8. Kontakt</h3><p>Bei Fragen zum Datenschutz erreichen Sie uns unter <a href="mailto:info@feinspitz.ch">info@feinspitz.ch</a>.</p>`,
  },
  {
    slug: 'cookie-richtlinie',
    title: 'Cookie-Richtlinie',
    content: `${NOTE}
<h3>Was sind Cookies?</h3><p>Cookies sind kleine Textdateien, die beim Besuch unserer Website auf Ihrem Gerät gespeichert werden. Sie ermöglichen es, Ihr Gerät wiederzuerkennen und bestimmte Funktionen bereitzustellen.</p>
<h3>Welche Cookies verwenden wir?</h3><ul><li><strong>Notwendige Cookies</strong> - erforderlich für den Betrieb der Website (z. B. Warenkorb, Login, Sprachwahl).</li><li><strong>Funktionale Cookies</strong> - speichern Einstellungen und verbessern die Nutzung.</li><li><strong>Analyse-Cookies</strong> - helfen uns zu verstehen, wie die Website genutzt wird (sofern aktiviert).</li></ul>
<h3>Cookies verwalten</h3><p>Sie können Cookies in den Einstellungen Ihres Browsers jederzeit ansehen, blockieren oder löschen. Ohne notwendige Cookies sind einzelne Funktionen der Website möglicherweise nicht verfügbar.</p>
<h3>Weitere Informationen</h3><p>Mehr zur Bearbeitung Ihrer Daten finden Sie in unserer <a href="/datenschutzerklaerung/">Datenschutzerklärung</a>.</p>`,
  },
  {
    slug: 'liefer-und-zahlungsbedingungen',
    title: 'Liefer- und Zahlungsbedingungen',
    content: `${NOTE}
<h3>Bestellmengen</h3><p>Die Mindestbestellmenge bei Wein- und Flaschenversand beträgt 6 Flaschen (= 1 Karton); diese können individuell zusammengestellt werden.</p>
<h3>Lieferung</h3><p>Lieferungen erfolgen innerhalb der Schweiz per Post oder durch unsere eigene Zustellung. Abholungen sind nach Terminvereinbarung an der Bahnhofstrasse 80, CH-8902 Urdorf möglich.</p>
<h3>Versandkosten</h3><p>Die Versandkosten werden im Bestellprozess ausgewiesen.</p>
<h3>Preise</h3><p>Alle Preise verstehen sich in Schweizer Franken (CHF) inklusive der gesetzlichen Mehrwertsteuer.</p>
<h3>Zahlung</h3><p>Die Zahlung erfolgt über die im Bestellprozess angebotenen Zahlungsarten. Bankverbindung: Zürcher Kantonalbank, IBAN CH92 0070 0110 0010 6799 2.</p>
<h3>Altersnachweis</h3><p>Der Verkauf alkoholischer Produkte erfolgt ausschliesslich an Personen ab 18 Jahren. Das Geburtsdatum ist bei Onlinebestellungen ein Pflichtfeld.</p>`,
  },
];

for ( const p of PAGES ) {
  const existing = ( await wp( '/wp/v2/pages', { query: { slug: p.slug, context: 'edit', status: 'any' } } ) )[ 0 ];
  const body = { title: p.title, content: p.content, status: 'publish' };
  if ( existing ) {
    await wp( `/wp/v2/pages/${existing.id}`, { method: 'POST', body } );
    console.log( `✓ ${p.slug}: aktualisiert & veröffentlicht (ID ${existing.id})` );
  } else {
    body.slug = p.slug;
    const created = await wp( '/wp/v2/pages', { method: 'POST', body } );
    console.log( `✓ ${p.slug}: angelegt & veröffentlicht (ID ${created.id})` );
  }
}
console.log( '\nFertig (idempotent). Bitte Rechtstexte prüfen/finalisieren lassen.' );
