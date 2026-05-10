# Feature Flags

Globale flags via `emonks_feature_flags` filter:
- `public_pages`
- `qr_codes`
- `custom_domains`
- `translations`

Admin beheer:
- WP Admin > Emonks SaaS > Features
- Niet-aangevinkte flags worden expliciet als `false` opgeslagen.
- Aangevinkte flags worden als `true` opgeslagen.

Helpers:
- `emonks_feature_enabled()`
- `emonks_plan_has_feature()`
- `emonks_service_supports()`
