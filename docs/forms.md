# Forms

Forms zijn herbruikbare form templates die velden uit de centrale field library gebruiken.

Een form bevat:
- key
- label
- scope: core, service, account, public
- action
- nonce_action
- submit_label
- field_refs

Een field ref bevat minimaal `field_key` en `order`. Later kunnen overrides zoals label, placeholder en required per formulier worden uitgebreid.

Core forms:
- auth_login
- auth_register
- workspace_create
- workspace_edit

Services koppelen deze of eigen forms via form usages aan contexts.
