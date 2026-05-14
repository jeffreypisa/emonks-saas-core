# Datamodel

Belangrijkste settings in `emonks_saas_settings`:

- `field_library`
- `form_templates`
- `service_schemas`
- `feature_flags`
- `billing`
- `branding`

`service_schemas` gebruikt `config_model = dynamic_services_v1`. Oudere service settings zonder dit model worden bewust genegeerd, zodat vaste legacy services niet terugkomen.

Verwijderd uit het actieve model:
- oude form schema opslag
- oude module toggles
- inline service fields
- hardcoded module registry

Workspace data:
- core data in post fields en postmeta
- plugin service values in `service_data_json`
- ACF values in ACF/postmeta

Services zijn leeg bij verse installatie en worden via `Emonks SaaS > Services` aangemaakt.
