# Template Overrides

## Resolutievolgorde
1. Theme override: `templates/emonks-saas/{template}`
2. Theme override (legacy): `templates/{template}`
3. Plugin default: `wp-content/plugins/emonks-saas-core/templates/{template}`

## Werkwijze
- Plugin bevat alle standaard templates.
- Wil je custom output: kopieer template naar je theme en pas daar aan.
- Theme override wint altijd van plugin template.

## Client Portal templates
- Plugin defaults:
  - `templates/modules/client-portal/index.twig`
  - `templates/modules/client-portal/item.twig`
- Theme overrides:
  - `templates/emonks-saas/modules/client-portal/index.twig`
  - `templates/emonks-saas/modules/client-portal/item.twig`

## Context keys
- `current_account_id`
- `current_account_ids`
- `cp_items` (index)
- `cp_item` + `cp_item_status` (detail)
- `dashboard_cards`
