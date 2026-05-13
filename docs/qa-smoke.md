# QA Smoke Testcases (Fase 2.5)

Korte smoke-suite voor isolatie, module-toggle, onboarding-auto, template-overrides en REST.

## Testsetup

- WordPress ingelogd als admin én als `emonks_customer`.
- Permalinks geflushed.
- Module `client_portal` eerst aan, daarna uit testen.

## 1) Isolatie / account-scope

1. Maak user A en user B.
2. Laat beide users een eigen workspace aanmaken.
3. Probeer als user A URL van workspace-edit van user B te openen.
4. Verwacht: 403 / geen toegang.
5. Maak service-item in account A.
6. Probeer item-detail van account A als user B te openen.
7. Verwacht: 403 / geen toegang.

Pass criteria:
- Geen cross-account read/write mogelijk.

## 2) Module toggle (client_portal)

1. Zet `client_portal` uit in admin settings.
2. Open `/account/services/client-portal/`.
3. Verwacht: 404.
4. Zet module weer aan.
5. Open route opnieuw.
6. Verwacht: route werkt en items zichtbaar (indien aanwezig).

Pass criteria:
- Toggle schakelt zowel route als toegangsgedrag.

## 3) Onboarding auto-check

1. Registreer nieuwe user.
2. Controleer onboarding stap `account_created` = voltooid.
3. Maak eerste workspace.
4. Controleer `first_workspace` = voltooid.
5. Activeer billing (test mode).
6. Controleer `billing_connected` = voltooid.
7. Publiceer workspace.
8. Controleer `workspace_published` = voltooid en progress 100%.

Pass criteria:
- Stappen worden automatisch afgevinkt zonder handmatige toggle.

## 4) Template overrides

1. Kopieer plugin template naar theme override pad:
   - `templates/emonks-saas/account/workspaces.twig`
2. Voeg zichtbare testtekst toe.
3. Herlaad `/account/workspaces/`.
4. Verwacht: theme override wordt gebruikt.
5. Verwijder override bestand.
6. Verwacht: plugin template fallback wordt weer gebruikt.

Pass criteria:
- Override mechanisme werkt bidirectioneel.

## 5) REST smoke

Endpoints:
- `GET /wp-json/emonks/v1/me`
- `GET /wp-json/emonks/v1/workspaces`
- `POST /wp-json/emonks/v1/workspaces`
- `GET /wp-json/emonks/v1/service-items`
- `POST /wp-json/emonks/v1/service-items`

Checks:
- Niet-ingelogd: protected endpoints blokkeren.
- Ingelogd: alleen account-eigen data terug.
- Ongeldige status op service-item create/update geeft validatiefout.
- Workspace publish zonder vereisten geeft nette fout (of draft fallback in form-flow).

Pass criteria:
- Auth, scope en validatie consistent.

## 6) UI regressie quick scan

- Workspaces status badge is zichtbaar (niet faded).
- Flash notifications gebruiken Bootstrap alerts.
- Onboarding cards tonen correcte “Voltooid/Open” badges.
- Browser tab titels aanwezig op account templates.

Pass criteria:
- Kern-UX van fase 1/2 blijft intact.

## 7) Guestbook MVP smoke

1. Maak workspace met `service_type=guestbook`.
2. Vul servicevelden in op workspace create/edit.
3. Zet status op `published` en zorg voor actieve subscription.
4. Open publieke URL `/g/{slug}`.
5. Verwacht: guestbook template render met ingevulde velden.
6. Zet module `guestbook` uit in admin.
7. Open dezelfde URL opnieuw.
8. Verwacht: 404.

## 8) Dynamische form schema smoke

1. Open admin -> Form Builder.
2. Pas label van `auth_login.user_login` aan in form schema JSON.
3. Herlaad loginpagina.
4. Verwacht: aangepast label zichtbaar.
5. Maak schema JSON expres ongeldig en sla op.
6. Verwacht: waarschuwing + bestaande schema blijft actief.
