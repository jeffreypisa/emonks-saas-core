# Fase 2.5 Changelog

Laatst bijgewerkt: 13 mei 2026

## Doel fase 2.5

Core aanscherpen voor een stabiele multiservice basis met:
- uniforme account-scope guards
- betere module health observability
- gestandaardiseerde status- en securitypatronen
- voorbereid migratiepad voor schema changes

## Afgerond

## 15. Module health uitgebreid
- Admin overzicht toont nu per module:
  - enabled
  - routes actief
  - REST actief
  - templates gevonden
- Detailkolom toegevoegd met route/rest/template checks.

## 16. `emonks_template_exists($template)`
- Helper toegevoegd voor consistente template health checks.

## 17. `emonks_can_access_entity_account(...)`
- Centrale account-scope guard toegevoegd voor:
  - `account`
  - `workspace`
  - `service_item`

## 18. Refactor access checks
- Workspace- en service-item access checks lopen nu via centrale guard.
- Doorgetrokken in Dashboard, REST en Permissions flow.

## 19. `emonks_status_vocabulary()`
- Generieke status vocabulary toegevoegd met labels + Bootstrap badge class mapping.

## 20. Client Portal status op vocabulary
- Service item status wordt genormaliseerd op create/update.
- Templates tonen status-label + consistente badge.
- REST payload bevat nu ook `status_label`.

## 21. `docs/routes-matrix.md`
- Volledige matrix toegevoegd voor:
  - frontend routes
  - REST routes
  - admin-post acties
- Inclusief capability/policy en account-scope guard.

## 22. `docs/security-avg-checklist.md`
- DoD checklist toegevoegd per endpoint/form.
- AVG baseline en release-gate opgenomen.

## 23. `docs/bootstrap-scss.md`
- Vastgelegd welke Bootstrap componenten/utilities plugin templates gebruiken.
- Relevante theme SCSS-bestanden en richtlijnen toegevoegd.

## 24. `docs/qa-smoke.md`
- Smoke testset toegevoegd voor:
  - isolatie/account-scope
  - module toggle
  - onboarding auto-check
  - template overrides
  - REST + UI regressie

## 25. `includes/MigrationRunner.php`
- Nieuwe migration runner met:
  - schema version option
  - migration history option
  - idempotent registry-based run

## 26. Memberships schema-check verplaatst
- Memberships schema provisioning loopt nu via MigrationRunner i.p.v. losse schema-check in Memberships boot.

## Open / vervolg (na 2.5)

- Route/REST module health checks data-driven maken vanuit één module-declaratie (nu deels hardcoded per module).
- Extra migraties toevoegen in runner zodra nieuwe tabellen/schema’s volgen.
- QA smoke-tests als geautomatiseerde test-suite formaliseren (nu documentair + handmatig).
- Guestbook module pas starten na formele “fase 3” go/no-go.
