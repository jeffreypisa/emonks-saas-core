# Security & AVG Checklist

Definition of Done checklist voor taak 22.  
Gebruik deze lijst per endpoint/form voordat een feature “done” is.

## 1) Algemene DoD (altijd)

- [ ] Input wordt gesanitized (`sanitize_text_field`, `sanitize_key`, `sanitize_email`, `sanitize_title`, `absint`).
- [ ] Output wordt escaped (`esc_html`, `esc_attr`, `esc_url`) of bewust via veilige templating.
- [ ] Autorisatie staat op endpoint-niveau (`permission_callback` of `current_user_can`/policy checks).
- [ ] Account-scope is expliciet gecontroleerd via `emonks_can_access_entity_account(...)` waar relevant.
- [ ] Nonce aanwezig voor muterende form-acties (`check_admin_referer`).
- [ ] Foutresponses geven geen gevoelige details terug.
- [ ] Alleen noodzakelijke data wordt opgeslagen (dataminimalisatie).
- [ ] Audit/logging bevat geen plaintext secrets of gevoelige payloads.

## 2) REST DoD per endpoint

## `/emonks/v1/health` (`GET`)
- [ ] Alleen technische status, geen gevoelige data.

## `/emonks/v1/me` (`GET`)
- [ ] Alleen minimaal profiel (`id`, `email`, `plan`).

## `/emonks/v1/workspaces` (`GET`, `POST`)
- [ ] `POST` alleen voor ingelogde users met create-recht.
- [ ] Workspace altijd gekoppeld aan account van user.

## `/emonks/v1/workspaces/{id}` (`GET`, `PUT/PATCH`)
- [ ] Read/update alleen bij account-toegang.
- [ ] Publish-gate checkt subscription + onboarding policy.
- [ ] Slug conflicts geven nette 422 zonder SQL/details.

## `/emonks/v1/service-items` (`GET`, `POST`)
- [ ] Module enabled check actief.
- [ ] Policy checks (`cp_view`, `cp_manage`) actief.
- [ ] Status valideert tegen `emonks_status_vocabulary()`.

## `/emonks/v1/service-items/{id}` (`GET`, `PUT/PATCH`)
- [ ] Item access via `emonks_can_access_entity_account('service_item', ...)`.
- [ ] Geen toegang over accountgrenzen heen.

## 3) Form actions DoD

## `emonks_workspace_save`
- [ ] `is_user_logged_in()`
- [ ] nonce `emonks_workspace_save`
- [ ] edit-check op bestaande workspace
- [ ] account-toegang op gekozen account-id
- [ ] publish-gate policies toegepast

## `emonks_billing_checkout` / `emonks_billing_portal` / `emonks_billing_change_plan`
- [ ] `is_user_logged_in()`
- [ ] juiste nonce per actie
- [ ] plan/cycle inputs gesanitized en gevalideerd
- [ ] test-mode pad en live pad beide afgedekt

## `emonks_saas_save_settings`
- [ ] `manage_options` verplicht
- [ ] nonce `emonks_saas_save_settings`
- [ ] settings sanitizer toegepast

## Form Builder config (nieuw)
- [ ] Config REST endpoints alleen voor `manage_options`.
- [ ] JSON schema input wordt gevalideerd op array-structuur vóór opslag.
- [ ] Runtime form validatie blijft server-side (niet vertrouwen op frontend).

## 4) AVG checks (praktisch MVP)

- [ ] Bewaar alleen noodzakelijke persoonsgegevens (nu: account/user links, e-mail via WP user).
- [ ] Definieer retention beleid voor logs (`emonks_saas_logs`) en onboarding/meta.
- [ ] Voeg export/wis-procedure toe op WP user lifecycle (volgende fase).
- [ ] Documenteer doelbinding per meta-veld (account_id, status, onboarding stappen).
- [ ] Vermijd IP-opslag tenzij functioneel noodzakelijk en expliciet gedocumenteerd.

## 5) Release gate (quick pass)

Een release mag door als:
- [ ] route-matrix is bijgewerkt (`docs/routes-matrix.md`)
- [ ] alle muterende acties nonce + auth + scope checks hebben
- [ ] handmatige smoke test gedaan op unauthorized access scenario’s
- [ ] geen debug/secrets zichtbaar in responses of notices
