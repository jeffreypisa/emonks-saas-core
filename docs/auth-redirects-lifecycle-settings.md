# Auth, Redirects en Lifecycle Settings

Nieuwe settings-tabbladen in `Emonks SaaS > Settings`:

- `Auth`
- `Redirects`
- `Lifecycle`

## Auth

- `auth.registration.status`: `auto_approve`, `email_verification`, `admin_approval`
- `auth.login.wp_admin_block_customers`: blokkeert wp-admin voor `emonks_customer`
- `auth.login.rate_limit.max_attempts`
- `auth.login.rate_limit.lockout_minutes`
- `auth.password.min_length`
- `auth.password.require_uppercase`
- `auth.password.require_number`
- `auth.password.require_symbol`
- `auth.register.required_fields`

## Redirects

- `redirects.after_register.type|target`
- `redirects.after_login.type|target`
- `redirects.after_login.by_role.emonks_customer`
- `redirects.after_logout.type|target`

Types:

- `default`
- `route`
- `page`
- `custom_url`

## Lifecycle

- `lifecycle.account_delete.action`: `soft_delete|hard_delete`
- `lifecycle.account_delete.grace_days`
- `lifecycle.account_delete.retention_days`
- `pages.conflict_guard.enabled`

`soft_delete` markeert gebruiker met metadata en hook `emonks_account_soft_delete_marked`.  
`hard_delete` verwijdert gebruiker direct via `wp_delete_user()`.
