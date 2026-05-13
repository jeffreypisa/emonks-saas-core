# Datamodel

## Core
- `emonks_account` (CPT)
- `emonks_workspace` (CPT, service instance)
- `{$wpdb->prefix}emonks_account_memberships` (custom table)

## Workspace verplichte scope
- `account_id`
- `module_key`
- `service_type`

## Module data (fase 2)
- `emonks_service_item` (CPT)
- Meta:
  - `account_id`
  - `workspace_id`
  - `module_key`
  - `status`
  - `updated_at`

## Keuzecriteria opslag
- Gebruik CPT wanneer:
  - item in WP admin beheerd moet worden
  - queryvolumes beperkt zijn
  - standaard WP lifecycle gewenst is
- Gebruik custom table wanneer:
  - veel writes/events verwacht worden
  - relationele queries centraal staan
  - audit/analytics op schaal nodig zijn
- Gebruik ACF/meta wanneer:
  - redactiedata flexibel moet blijven
  - geen zware relationele eisen gelden

## Principes
- Relationele account-user koppeling via custom table
- Alle module-data is account-gescopeerd
- Geen records zonder account-scope
