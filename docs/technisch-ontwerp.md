# Technisch Ontwerp - Emonks SaaS Core

## 1. Runtime
- Plugin bootstrap -> dependency load -> TimberBridge -> service boot
- Timber 2 via Composer (plugin vendor of theme vendor fallback)

## 2. Plans & pricing
`Plans::getPlans()` combineert defaults met settings overrides.

## 3. Billing provider model (nieuw)
`Billing` resolveert een provider via:
- standaard: `StripeBillingProvider`
- override: filter `emonks_billing_provider`

Contract via `BillingProviderInterface`:
- `createCheckoutSession()`
- `createCustomerPortalSession()`
- `changeSubscriptionPlan()`

## 4. Upgrade/downgrade
Endpoint:
- `admin_post_emonks_billing_change_plan`

Flow:
- validate nonce/login/target plan
- preview + confirm stap
- detect upgrade vs downgrade via planrank
- test mode: lokale update
- live mode: provider subscription update

## 5. Service contract
Service definitie wordt genormaliseerd en ondersteunt extra velden:
- `onboarding_steps`
- `policy`

## 6. Settings overrides
Helper:
- `emonks_get_setting_with_overrides($key, $default, $serviceType, $workspaceId)`

Prioriteit:
- workspace settings
- service settings
- globale plugin settings
