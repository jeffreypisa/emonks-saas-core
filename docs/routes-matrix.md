# Routes Matrix

Deze matrix is de centrale referentie voor taak 21: **route -> required capability -> account-scope guard**.

## Frontend routes (rewrite -> template)

| Route key | URL pattern | Vereist | Capability/Policy | Account-scope guard | Afhandeling |
|---|---|---|---|---|---|
| `auth_login` | `/login/` | geen login vereist | n.v.t. | n.v.t. | `Auth::handleLoginRoute()` |
| `auth_register` | `/register/` | geen login vereist | n.v.t. | n.v.t. | `Auth::handleRegisterRoute()` |
| `auth_logout` | `/logout/` | ingelogd user (impliciet) | n.v.t. | n.v.t. | `Auth::handleLogoutRoute()` |
| `public_workspace` | `/g/{slug}/` | publiek | n.v.t. | status + subscription check op owner | `Dashboard::renderPublicWorkspace()` |
| `account_dashboard` | `/account/` | `is_user_logged_in()` | n.v.t. | context op user accounts | `Dashboard::handleAccountRoute()` |
| `account_workspaces` | `/account/workspaces/` | `is_user_logged_in()` | n.v.t. | lijst via user account filter | `Dashboard::handleAccountRoute()` |
| `account_workspaces_new` | `/account/workspaces/new/` | `is_user_logged_in()` | n.v.t. | create beperkt via plan/subscription | `Dashboard::handleAccountRoute()` |
| `account_workspace_edit` | `/account/workspaces/{id}/edit/` | `is_user_logged_in()` | n.v.t. | `emonks_user_can_access_workspace()` -> `emonks_can_access_entity_account('workspace', ...)` | `Dashboard::handleAccountRoute()` |
| `account_billing` | `/account/billing/` | `is_user_logged_in()` | n.v.t. | user-context | `Dashboard::handleAccountRoute()` |
| `account_settings` | `/account/settings/` | `is_user_logged_in()` | n.v.t. | user-context | `Dashboard::handleAccountRoute()` |
| `account_onboarding` | `/account/onboarding/` | `is_user_logged_in()` | n.v.t. | user-context | `Dashboard::handleAccountRoute()` |
| `account_client_portal` | `/account/services/client-portal/` | `is_user_logged_in()` + module enabled | `Policy::can(user, 'cp_view', accountId)` | account-id uit huidige context | `Dashboard::handleAccountRoute()` |
| `account_client_portal_item` | `/account/services/client-portal/{id}/` | `is_user_logged_in()` + module enabled | `Policy::can(user, 'cp_view', accountId)` | `emonks_can_access_entity_account('service_item', ...)` | `Dashboard::handleAccountRoute()` |

## REST routes (`/wp-json/emonks/v1/*`)

| Method | Route | Permission callback | Capability/Policy | Account-scope guard |
|---|---|---|---|---|
| `GET` | `/health` | `__return_true` | n.v.t. | n.v.t. |
| `GET` | `/me` | `canReadCurrentUser` | `is_user_logged_in()` | n.v.t. |
| `GET` | `/services` | `canReadCurrentUser` | `is_user_logged_in()` | n.v.t. |
| `GET` | `/workspaces` | `canListWorkspaces` | `is_user_logged_in()` | lijst via account-filter |
| `POST` | `/workspaces` | `canCreateWorkspace` | `is_user_logged_in()` + plan/subscription gate | account wordt primair account van user |
| `GET` | `/workspaces/{id}` | `canReadWorkspace` | `is_user_logged_in()` | `emonks_user_can_access_workspace()` |
| `PUT/PATCH` | `/workspaces/{id}` | `canUpdateWorkspace` | idem read + publish policy check | `emonks_user_can_access_workspace()` |
| `GET` | `/service-items` | `canViewClientPortal` | `Policy::can(...,'cp_view',...)` | account-scope via `listByAccount()` |
| `POST` | `/service-items` | `canManageClientPortal` | `Policy::can(...,'cp_manage',...)` | workspace account-id wordt gebruikt |
| `GET` | `/service-items/{id}` | `canViewClientPortalItem` | `Policy::can(...,'cp_view',...)` | `emonks_can_access_entity_account('service_item', ...)` |
| `PUT/PATCH` | `/service-items/{id}` | `canManageClientPortalItem` | `Policy::can(...,'cp_manage',...)` | `emonks_can_access_entity_account('service_item', ...)` |
| `GET` | `/config/forms/{key}` | `canManageConfig` | `manage_options` | admin-only |
| `PUT/PATCH` | `/config/forms/{key}` | `canManageConfig` | `manage_options` | admin-only |
| `GET` | `/config/services/{key}` | `canManageConfig` | `manage_options` | admin-only |
| `PUT/PATCH` | `/config/services/{key}` | `canManageConfig` | `manage_options` | admin-only |

## Form actions (`admin-post.php`)

| Action | Endpoint | Vereist | Nonce | Account-scope guard |
|---|---|---|---|---|
| `emonks_workspace_save` | `POST /wp-admin/admin-post.php` | ingelogd | `emonks_workspace_save` | workspace/account checks incl. `emonks_user_can_access_workspace()` |
| `emonks_billing_checkout` | idem | ingelogd | `emonks_billing_checkout` | user-context |
| `emonks_billing_portal` | idem | ingelogd | `emonks_billing_portal` | user-context |
| `emonks_billing_change_plan` | idem | ingelogd | `emonks_billing_change_plan` | user-context |
| `emonks_saas_save_settings` | idem | `manage_options` | `emonks_saas_save_settings` | admin-only |

## Opmerking

- Centrale account-scope guard: `emonks_can_access_entity_account($entityType, $entityId, $userId)`.
- Voor nieuwe modules: eerst hier matrix uitbreiden, daarna pas routes/REST live zetten.
