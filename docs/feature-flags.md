# Feature Flags

Er zijn drie lagen:

## 1) Service Features (technisch)
- Geconfigureerd op service-term (`supported_features`)
- Bepaalt of een feature technisch bestaat binnen die service

## 2) Plan Features (commercieel)
- Beheer als taxonomie-termen (`emonks_feature`)
- Koppel features aan plannen
- Bepaalt entitlement per plan

## 3) Global Feature Flags (operationeel)
- Platformbrede kill-switches in `Emonks SaaS > Features`
- Niet-aangevinkt = expliciet `false`
- Aangevinkt = `true`

## Definitieve beschikbaarheid
`beschikbaar = service_supports(feature) && plan_has_feature(feature) && global_flag_enabled(feature)`

Helper:
- `emonks_feature_available_for_service_and_plan($serviceType, $plan, $feature)`
