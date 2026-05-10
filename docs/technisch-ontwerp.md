# Technisch Ontwerp - Emonks SaaS Core

## 1. Catalogus architectuur
Component: `Catalog`
- registreert `emonks_plan` post type
- registreert `emonks_feature` taxonomie
- registreert `emonks_service` taxonomie
- beheert plan-meta via metabox
- beheert service `supported_features` via term-meta velden

## 2. Plan data source
`Plans::getPlans()` leest uitsluitend uit plan catalogus (`emonks_plan` + tax relaties).

## 3. Service runtime
`Services` registreert service-termen uit `emonks_service`.
Per service wordt `supported_features` uit term meta geladen.

## 4. Feature-availability helper
`emonks_feature_available_for_service_and_plan($serviceType, $plan, $feature)`

Logica:
- `emonks_service_supports(...)`
- `emonks_plan_has_feature(...)`
- `emonks_feature_enabled(...)`
