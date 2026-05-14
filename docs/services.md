# Services

Services zijn dynamische product- of dienstdefinities. Er zijn geen ingebouwde services.

Een service bevat:
- key
- label
- description
- status: draft, active, archived
- features
- field_source_default: plugin, acf, hybrid
- acf_group_key_default
- form_usages
- render_hints

## Form usages

Een service gebruikt formulieren per context. Voorbeelden:
- workspace_create
- workspace_edit
- public_contact
- intake
- feedback

Een service heeft dus niet een enkel workspace-formulier. Het koppelt herbruikbare form templates aan momenten in de flow.

## Publicatie

Publicatie-instellingen staan in `render_hints`:
- public_enabled
- template
- public_route
- dashboard_label

Het theme mag service-specifieke templates leveren, maar de plugin verplicht geen service-template.
