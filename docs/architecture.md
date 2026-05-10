# Architecture

## Separation of concerns
- Theme = presentatie
- Plugin = logica/framework

## Core modules
- Routing/Auth/Dashboard
- Workspaces/Permissions/Statuses
- Catalog (Plans/Features/Services)
- Billing/Stripe/Webhooks
- REST API
- Onboarding
- Logging
- Admin Settings

## Catalog model
- `emonks_plan` (CPT)
- `emonks_feature` (taxonomy)
- `emonks_service` (taxonomy)
- service term meta: `supported_features`

## Capability model
Feature toegang is 3-laags:
- Service-level capability
- Plan entitlement
- Global operational flag
