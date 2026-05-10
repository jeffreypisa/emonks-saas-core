# Services

## Nieuw model (dynamisch)
Services worden beheerd als losse taxonomie-termen (`emonks_service`) en aan plannen gekoppeld.

Er zijn geen hardcoded default services meer.

## Service-level features (technische grens)
Elke service-term heeft nu `Supported Features`.
Deze lijst bepaalt welke features technisch mogelijk zijn binnen die service.

Beheer:
- `Emonks SaaS > Services`
- Open service-term
- Selecteer `Supported Features`

## Runtime gedrag
- Service-termen worden automatisch geregistreerd als service types in `Services::all()`
- `supported_features` wordt uit term meta gelezen

## Relatie met plannen
Per plan koppel je services en features.
Een feature is pas echt beschikbaar als:
- service ondersteunt feature
- plan bevat feature
- global feature flag staat aan
