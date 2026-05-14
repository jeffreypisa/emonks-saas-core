# ADR-003: Module Lifecycle Vervangen Door Dynamic Services

## Status

Vervangen.

## Besluit

Het oude module lifecycle model is verwijderd. Emonks SaaS gebruikt nu dynamische services die via pluginconfiguratie worden aangemaakt.

## Reden

De plugin moet een generiek SaaS fundament zijn voor meerdere websites en projecten. Hardcoded modules zoals client portal of guestbook maken de core te product-specifiek.

## Gevolg

- Geen `ModuleRegistry`.
- Geen module toggles.
- Geen vaste service routes.
- SaaS Health controleert core en dynamische configuratie.
