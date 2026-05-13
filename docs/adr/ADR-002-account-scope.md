# ADR-002 Account Scope Model

## Status
Geaccepteerd

## Context
Multiservice binnen 1 WP-installatie vereist harde data-isolatie per klant/account.

## Beslissing
- `emonks_account` is tenant/container
- Memberships via custom table `wp_emonks_account_memberships`
- Elke workspace/service-item heeft verplicht `account_id`
- Autorisatie loopt via account-scope checks en policy service

## Consequenties
- Klanten kunnen geen data buiten accountscope zien
- Author-based checks zijn secundair en niet leidend
- Relationele memberships zijn beter schaalbaar dan user_meta-only
