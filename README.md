# Emonks SaaS Core

Generieke, uitbreidbare SaaS core plugin voor WordPress.

## Architectuur
- Theme: UI, Twig, Bootstrap markup, styling, labels, servicepresentatie.
- Plugin: auth, routing, workspaces, subscriptions, Stripe billing sync, permissions, plans, feature flags, REST, onboarding, logging.
- Interne kerntermen: `workspace`, `service`, `subscription`, `plan`, `account`, `owner`.

## Installatie
1. Plaats in `/wp-content/plugins/emonks-saas-core/`
2. Activeer plugin
3. Run Composer in pluginmap
4. Sla permalinks op

## Vereisten
- PHP 8+
- Timber 2 via Composer (geen losse Timber plugin vereist)

## Timber 2 setup
In pluginmap:
```bash
composer install
```

Dependency:
- `timber/timber:^2.0`

## WP Admin Omgeving
Belangrijkste admin secties:
- `Emonks SaaS > Dashboard`
- `Emonks SaaS > Services`
- `Emonks SaaS > Fields`
- `Emonks SaaS > Forms`
- `Emonks SaaS > Features`
- `Emonks SaaS > Plans`
- `Emonks SaaS > Billing`
- `Emonks SaaS > SaaS Health`
- `Emonks SaaS > Logs`
- `Emonks SaaS > Settings`

`Emonks SaaS > Settings` bevat tabs voor:
- `Algemeen`
- `Shortcodes`
- `Profielmenu`

## Stripe setup
Voeg constants toe in `wp-config.php`:
```php
define('EMONKS_STRIPE_SECRET_KEY', 'sk_test_...');
define('EMONKS_STRIPE_WEBHOOK_SECRET', 'whsec_...');
define('EMONKS_STRIPE_PRICE_STARTER', 'price_...');
define('EMONKS_STRIPE_PRICE_PLUS', 'price_...');
define('EMONKS_STRIPE_PRICE_PRO', 'price_...');
```

## Billing test mode
Beschikbaar in:
- `WP Admin > Emonks SaaS > Billing`

Gedrag:
- simuleert checkout en planwijzigingen zonder live Stripe mutaties
- werkt voor snelle QA van onboarding/billing UX

## Plans en prijzen
Planbeheer in:
- `WP Admin > Emonks SaaS > Plans`

Per plan bewerkbaar:
- `label`
- `max_workspaces`
- `price_monthly`
- `price_yearly`
- `currency`
- `enabled_features`
- `stripe_price_constant`
- `stripe_price_id_monthly`
- `stripe_price_id_yearly`

## Monthly / yearly cycle
Ondersteund in billingflow:
- `monthly`
- `yearly`

Cycle wordt meegenomen in:
- checkout metadata
- subscription update metadata
- user meta (`emonks_subscription_cycle`)

## Upgrade / downgrade logic
- Zonder actieve subscription: checkout flow
- Met actieve subscription: planwijziging flow
- Endpoint: `admin_post_emonks_billing_change_plan`
- Upgrade/downgrade bepaald op planvolgorde
- In live mode: Stripe subscription update
- In test mode: lokale simulatie

## Routes
- `/account`
- `/account/workspaces`
- `/account/workspaces/new`
- `/account/workspaces/{id}/edit`
- `/account/billing`
- `/account/settings`
- `/account/onboarding`
- `/login`
- `/register`
- `/logout`
- `/g/{public_slug}`

## REST API
Namespace: `/wp-json/emonks/v1/`
- `GET /health`
- `GET /me`
- `GET /services`
- `GET /workspaces`
- `POST /workspaces`
- `GET /workspaces/{id}`
- `PUT|PATCH /workspaces/{id}`
- `POST /webhooks/stripe`

## Feature flags
In `WP Admin > Emonks SaaS > Features`.

Flags:
- `public_pages`
- `qr_codes`
- `custom_domains`
- `translations`

Save gedrag:
- niet-aangevinkte checkboxes worden expliciet als `false` opgeslagen
- aangevinkte checkboxes als `true`

## Theme overrides
Templates worden geladen in volgorde:
1. Theme: `/templates/...`
2. Plugin fallback: `/wp-content/plugins/emonks-saas-core/templates/...`

Voorbeeld overrides:
- `/templates/account/login.twig`
- `/templates/account/register.twig`

## Belangrijke helpers (theme if/else)
- `emonks_get_plans()`
- `emonks_get_plan($plan)`
- `emonks_get_current_user_plan()`
- `emonks_get_current_user_billing_cycle()`
- `emonks_get_current_user_workspace_count()`
- `emonks_get_plan_limit($plan, 'max_workspaces')`
- `emonks_plan_has_feature($plan, $feature)`
- `emonks_feature_enabled($feature)`
- `emonks_is_billing_test_mode()`

## Security
- nonce checks
- sanitize/validate input
- ownership checks
- capability checks
- safe redirects
- Stripe webhook signature verificatie + idempotency

## Documentatie
Uitgebreide docs:
- `/docs/billing.md`
- `/docs/feature-flags.md`
- `/docs/rest-api.md`
- `/docs/routes.md`
- `/docs/security.md`
- `/docs/services.md`
- `/docs/theme-overrides.md`
- `/docs/user-menu.md`

## Onderhoudsafspraak docs
- Werk docs direct bij bij functionele wijzigingen.
- Verwijder verouderde tekst zodra gedrag/flows wijzigen.
- Raadpleeg docs eerst bij nieuwe wijzigingen, zodat implementatie en documentatie in sync blijven.
