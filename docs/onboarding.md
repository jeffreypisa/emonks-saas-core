# Onboarding

Onboarding bevat:
- status (`started`/`completed`)
- checklist
- progress percentage
- stap-completion endpoint (`admin_post_emonks_onboarding_step`)

## Standaard stappen
1. account created
2. first workspace
3. billing connected
4. workspace published

## Dynamisch model
Checklist en evaluatie zijn uitbreidbaar via:
- `emonks_onboarding_checklist`
- `emonks_onboarding_step_evaluators`
- `emonks_service_onboarding_steps`

## Automatische progressie
Plugin markeert stappen ook op events zoals workspace create/update en billing updates.
