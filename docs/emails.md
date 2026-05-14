# Emails

Emails zijn dynamische templates per actie/event.

Belangrijkste settings in `email_templates`:
- `schema_version`
- `templates`

Per template:
- `key`
- `label`
- `enabled`
- `trigger`
- `recipients.targets` (`current_user`, `account_owner`, `site_admin`)
- `recipients.extra` (extra emailadressen)
- `subject`
- `body_html`
- `body_text`
- `from_name`
- `from_email`
- `reply_to`
- `conditions`
- `updated_at`

## Standaard triggers
- `emonks_account_registered`
- `emonks_workspace_created`
- `emonks_workspace_updated`
- `emonks_billing_plan_changed`
- `emonks_auth_login_failed` (default uit)

## Slimme velden (tokens)
Gebruik `{{ ... }}` in subject/body.

- `{{ user.id }}`
- `{{ user.email }}`
- `{{ user.display_name }}`
- `{{ user.first_name }}`
- `{{ user.last_name }}`
- `{{ workspace.id }}`
- `{{ workspace.title }}`
- `{{ workspace.status }}`
- `{{ workspace.public_slug }}`
- `{{ workspace.url }}`
- `{{ account.id }}`
- `{{ account.name }}`
- `{{ billing.current_plan }}`
- `{{ billing.target_plan }}`
- `{{ billing.cycle }}`
- `{{ billing.is_upgrade }}`
- `{{ system.site_name }}`
- `{{ system.site_url }}`
- `{{ system.today }}`

Onbekende tokens renderen leeg.
