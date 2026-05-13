# Modules

## Contract
`ModuleInterface`
- `key()`
- `boot()`
- `isEnabledByDefault()`

## Registratie
Registreer modules via `emonks_register_modules` op de `ModuleRegistry`.

## Toggle
`settings.modules.enabled.{module_key}` bepaalt of module routes/REST/cards actief zijn.

## Builder checklist
1. Maak module class in `includes/Modules/<Name>/`
2. Registreer module in plugin bootstrap
3. Definieer capabilities in policy matrix
4. Voeg routes + context contract toe
5. Maak theme templates in `templates/emonks-saas/modules/<module>/`
6. Voeg REST endpoints toe met account-scope checks
