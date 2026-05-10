# Services

Service registry via `emonks_register_service_type($key, $definition)`.

## Service module contract
Voor multi-SaaS inzet adviseren we service modules die `ServiceModuleInterface` implementeren:
- `key(): string`
- `definition(): array`

## Ondersteunde definitievelden
- `labels`
- `routes`
- `templates`
- `settings_schema`
- `fields`
- `dashboard_cards`
- `capabilities`
- `supported_features`
- `onboarding_steps`
- `policy`

## Normalisatie
`Services::registerServiceType()` normaliseert ontbrekende velden en sanitizeert arrays zoals capabilities/features/steps.

## Plan-koppeling
Service capabilities kunnen gecombineerd worden met planfeatures via:
- `emonks_plan_has_feature($plan, $feature)`
- `emonks_feature_enabled($feature)`

## Policy en onboarding
Nieuwe helpers:
- `emonks_get_service_policy($serviceType)`
- `emonks_get_service_onboarding_steps($serviceType)`

Doel:
- Dynamische service-specifieke forms
- Capability-based service behavior
- Per-service onboarding flow en publicatievoorwaarden
