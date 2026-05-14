# Architectuur

Emonks SaaS is nu een config-first SaaS fundament zonder vaste service-modules.

De plugin levert:
- accounts, memberships en workspaces
- service builder
- centrale field library
- herbruikbare form templates
- features, plans en billing hooks
- routing, permissies, health checks en genormaliseerde theme-context

De plugin levert niet:
- hardcoded diensten zoals client portal of guestbook
- module toggles
- service-specifieke templates als verplicht onderdeel
- pagebuilder-achtige frontend styling

## Lagen

1. Core: accounts, workspaces, plans, billing, permissions, routing.
2. Config: fields, forms, services, features.
3. Runtime: resolved forms per service-context en workspace-data.
4. Theme: Twig/Bootstrap-presentatie en optionele service-specifieke overrides.

## Verse installatie

Bij een verse installatie zijn er geen services. Dat is bewust. De gebruiker start in `Emonks SaaS > Setup` en maakt daarna zelf services aan.
