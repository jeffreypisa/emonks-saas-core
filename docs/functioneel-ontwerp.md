# Functioneel Ontwerp - Emonks SaaS Core

## 1. Doel en Scope
Emonks SaaS Core is een generieke WordPress plugin die SaaS-kernlogica levert, terwijl het actieve theme volledig eigenaar blijft van presentatie, UX, layouts en styling.

## 2. Belangrijkste functionele onderdelen
- Account/auth/routing
- Workspace lifecycle en ownership
- Plans, pricing en limieten
- Billing met Stripe + test mode
- Upgrade/downgrade planlogica
- Feature flags
- Onboarding
- REST basis
- Logging en admin beheer

## 3. Planbeheer
In WP Admin > Emonks SaaS > Plans zijn plannen configureerbaar:
- label
- max_workspaces
- price_monthly
- price_yearly
- currency
- enabled_features
- stripe_price_constant
- stripe_price_id_monthly
- stripe_price_id_yearly

Theme kan hiermee direct werken via helpers:
- `emonks_get_plans()`
- `emonks_get_plan($plan)`
- `emonks_get_current_user_plan()`
- `emonks_get_plan_limit($plan, 'max_workspaces')`
- `emonks_get_current_user_workspace_count()`

## 4. Billing en cycles
In WP Admin > Emonks SaaS > Billing:
- billing test mode
- success/cancel URL settings

Ondersteunde cycles:
- monthly
- yearly

Gedrag:
- zonder actieve subscription: checkout flow
- met actieve subscription: change plan flow (upgrade/downgrade)

## 5. Feature flags gedrag
In WP Admin > Emonks SaaS > Features:
- Niet-aangevinkt = expliciet `false`
- Aangevinkt = `true`

Dit voorkomt dat uitgezette flags onbedoeld terug op actief springen.

## 6. Template en UX
Plugin biedt volledige fallback templates voor alle account/public routes.
Theme overrides blijven leidend.

## 7. Security
- Nonce checks
- Sanitization
- Ownership checks
- Capability checks
- Webhook signature verify
- Idempotency
