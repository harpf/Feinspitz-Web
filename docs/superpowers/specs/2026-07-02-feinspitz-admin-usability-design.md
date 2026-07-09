# Feinspitz — Einfachere Verwaltung (Design-Spec)

**Datum:** 2026-07-02
**Status:** Freigegeben — Runde 6 (Admin-Usability).

## Ziel

Das WordPress-Backend so anpassen, dass der Administrator **Produkte anlegen** und
**Artikel schreiben** (Ratgeber / Weinlexikon) deutlich einfacher erledigen kann.
Umsetzung als git-versionierter Theme-Code, kein Zusatz-Plugin.

## Ausgangslage (geprüft)
- Wein-Attribute sind bereits **globale Attribute mit Termen** (pa_rebsorte, pa_weingut,
  pa_region, pa_suesse, pa_jahrgang, pa_volumen) → Dropdown-Auswahl beim Produkt.
- 19 Block-Patterns im Editor, aber **keine Artikel-Vorlage** (Ratgeber/Lexikon).
- Kein zentrales Admin-Dashboard; Anfragen-CPT (`feinspitz_anfrage`) existiert.

## Bausteine

### ① Feinspitz-Admin-Dashboard (`feature/admin-dashboard`)
- Neues Top-Level-Adminmenü „Feinspitz" mit Startseite: **Schnell-Aktionen**
  (Neues Produkt, Neuer Ratgeber-Artikel, Neuer Weinlexikon-Eintrag, Anfragen ansehen)
  + kurze „So geht's"-Anleitung.
- **Produktliste:** eigene Spalten (Rebsorte · Region · Süße · Flags) für schnellen Überblick.
- **Anfragen (`feinspitz_anfrage`):** lesbare Admin-Liste mit Spalten (Name · E-Mail ·
  Typ · Datum); nicht editierbar nötig, aber gut lesbar; im Feinspitz-Menü verlinkt.
- **Aufräumen:** irrelevante Dashboard-Widgets/Hinweise reduzieren; klare deutsche
  Bezeichnungen.
- Dateibesitz: `theme/feinspitz/inc/admin.php`.

### ② Editor-Vorlagen + Produkt-Hilfe (`feature/editor-templates`)
- **Editor-Block-Patterns** (inserter=true, Kategorie Feinspitz):
  „Ratgeber-Artikel (Vorlage)" und „Weinlexikon-Eintrag (Vorlage)" mit fertiger
  Struktur (Einleitung, H2/H3-Abschnitte, Tipp-/Hinweisbox, Fazit).
- Optional **Standard-Block-Template** für neue Beiträge (Post-Editor startet mit der
  Struktur) — nur wenn konfliktfrei.
- **Produkt-Hilfe:** die 6 Wein-Attribute beim Neuanlegen leicht verfügbar machen
  (z. B. via Filter vorbereiten) + eine kurze **Checkliste**/Hinweisbox auf der
  Produkt-Bearbeitungsseite.
- Dateibesitz: `theme/feinspitz/inc/editor-templates.php`, `theme/feinspitz/patterns/editor-*.php`.

## Querschnitt / Nicht-Ziele
- Kein zusätzliches Plugin; alles im Theme (Admin-Code läuft via functions.php
  `glob(inc/*.php)`, wirkt nur im Admin über `is_admin()`-Gates).
- Keine Änderung an Frontend-Rendering; keine neuen gettext-msgids (i18n-Build stabil).
- „Blog" = Ratgeber/Weinlexikon-Artikel (bestätigt implizit); die Editor-Vorlagen
  funktionieren auch für einen späteren allgemeinen Blog.
- Fehler-Check: kurzer Durchgang, offensichtliche Kleinigkeiten beheben.
