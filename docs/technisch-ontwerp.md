# Technisch Ontwerp - Emonks SaaS Core

## 1. Runtime
- Plugin bootstrap -> dependency load -> TimberBridge -> service boot
- Timber 2 via Composer (plugin vendor of theme vendor fallback)

## 2. Plans & pricing
`Plans::getPlans()` combineert defaults met settings overrides.
Per plan:
- `label`
- `max_workspaces`
- `enabled_features`
- `stripe_price_constant`
- `stripe_price_id_monthly`
- `stripe_price_id_yearly`
- `price_monthly`
- `price_yearly`
- `currency`

## 3. Billing cycle model
Cycle in gebruik:
- `monthly`
- `yearly`

User meta:
- `emonks_subscription_cycle`

Stripe metadata:
- `metadata[cycle]`

## 4. Upgrade/downgrade
Endpoint:
- `admin_post_emonks_billing_change_plan`

Flow:
- validate nonce/login/target plan
- detect upgrade vs downgrade via planrank
- test mode: lokale update
- live mode: Stripe subscription update met cycle price-id

## 5. Feature flags save fix
Probleem opgelost:
- unchecked checkboxes sturen geen POST

Implementatie:
- bij `tab=features` eerst alle flags op false initialiseren
- daarna submitted flags op true zetten

Resultaat:
- custom_domains/translations kunnen betrouwbaar uitgezet worden.
