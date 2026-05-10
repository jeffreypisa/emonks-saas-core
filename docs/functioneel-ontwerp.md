# Functioneel Ontwerp - Emonks SaaS Core

## 1. Doel en Scope
Emonks SaaS Core is een generieke WordPress plugin die SaaS-kernlogica levert, terwijl het actieve theme eigenaar blijft van presentatie en branding.

## 2. Belangrijkste functionele onderdelen
- Account/auth/routing
- Workspace lifecycle en ownership
- Plans, pricing en limieten
- Billing met provider abstractie (default Stripe)
- Upgrade/downgrade planlogica met confirm-preview
- Feature flags
- Dynamische onboarding
- REST basis + validatie
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

## 4. Servicebeheer
In WP Admin > Emonks SaaS > Services zie je service-registry metadata:
- capabilities
- supported features
- onboarding steps
- policy referentie

## 5. Billing en cycles
Ondersteunde cycles:
- monthly
- yearly

Gedrag:
- zonder actieve subscription: checkout flow
- met actieve subscription: change plan flow
- plan change gebruikt eerst preview, daarna bevestiging

## 6. Feature flags gedrag
In WP Admin > Emonks SaaS > Features:
- Niet-aangevinkt = expliciet `false`
- Aangevinkt = `true`

## 7. Onboarding
Stappen zijn filterbaar en evaluator-driven:
- `emonks_onboarding_checklist`
- `emonks_onboarding_step_evaluators`

## 8. Security
- Nonce checks
- Sanitization
- Ownership checks
- Capability checks
- REST permission callbacks
- Webhook signature verify
- Idempotency
