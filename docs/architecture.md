# Architecture

## Scheiding
- Plugin: data, routing, permissies, modules, REST
- Theme: alle frontend rendering (Twig) en styling

## Mapgrenzen
- `includes/Core/`: generieke platformregels (accounts, permissions, policy, routing)
- `includes/Modules/`: dienstspecifieke modules (Client Portal, later Guestbook)
- `includes/Infrastructure/`: technische diensten (template loader, settings, logging, webhooks, rest wiring)
- `templates/`: plugin defaults die door theme overschreven kunnen worden
- `docs/`: architectuur, ADRs, security, API, implementatiekeuzes

## Core services (huidig)
- Accounts + memberships
- Workspaces als service instances
- Policy service voor capability checks
- Module registry voor lifecycle/toggles
- Service items domein voor module-data

## Rendering
Plugin levert default templates.
Theme overrides via `templates/emonks-saas/...` hebben prioriteit.

## Module model
- Contract via `ModuleInterface`
- Registratie via `emonks_register_modules`
- Enabled/disabled via settings
