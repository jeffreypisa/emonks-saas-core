# Services

Service registry via `emonks_register_service_type($key, $definition)`.

Ondersteunde velden:
- `labels`
- `routes`
- `templates`
- `settings_schema`
- `fields`
- `dashboard_cards`
- `capabilities`
- `supported_features`

Plan-koppeling:
- Service capabilities kunnen gecombineerd worden met planfeatures via:
  - `emonks_plan_has_feature($plan, $feature)`
  - `emonks_feature_enabled($feature)`

Doel:
- Dynamische service-specifieke forms
- Dynamische dashboard kaarten
- Capability-based service behavior
