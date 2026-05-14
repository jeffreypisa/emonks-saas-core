# Routes Matrix

Core frontend routes:

| Route | Pad | Guard |
|---|---|---|
| auth_login | `/login/` | guest/current user handling |
| auth_register | `/register/` | guest/current user handling |
| account_dashboard | `/account/` | logged in |
| account_workspaces | `/account/workspaces/` | logged in + account-scope |
| account_workspace_edit | `/account/workspaces/{id}/edit/` | logged in + workspace account-scope |
| account_billing | `/account/billing/` | logged in |
| account_settings | `/account/settings/` | logged in |
| account_onboarding | `/account/onboarding/` | logged in |
| public_workspace | `/g/{slug}/` | published + billing gate |

Er zijn geen hardcoded service routes. Service-specifieke publicatie loopt via workspace public routing en service render hints.
