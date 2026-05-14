# ACF Bridge

ACF Pro is optioneel. De plugin mag ACF gebruiken voor rijke contentvelden, maar SaaS-logica blijft in Emonks SaaS.

ACF wordt gebruikt voor:
- rijke contentvelden
- images/files/links/gallery/flexible content
- vertrouwde WordPress admin editing

ACF wordt niet gebruikt voor:
- account-scope
- capabilities
- subscription gates
- plan/feature entitlement

Helpers:
- `emonks_acf_available()`
- `emonks_get_acf_field_groups()`
- `emonks_get_acf_field_group_options()`
- `emonks_get_acf_field_options($groupKey)`
- `emonks_get_workspace_acf_data($workspaceId, $serviceKey)`
