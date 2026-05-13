# ADR-003 Module Lifecycle

## Status
Geaccepteerd

## Context
Core moet generiek blijven terwijl modules eigen gedrag/routes/capabilities toevoegen.

## Beslissing
- Modules registreren via `ModuleRegistry`
- Module toggles via settings (`settings.modules.enabled.{key}`)
- Basismethodes: `key()`, `boot()`, `isEnabledByDefault()`
- Uitbreiding richting lifecycle-methodes in fase 2.5 (`registerRoutes`, `registerRest`, `registerCapabilities`, `registerDashboardCards`)

## Consequenties
- Lage instap voor nieuwe modules
- Core blijft compact
- Geleidelijke uitbreiding mogelijk zonder over-engineering
