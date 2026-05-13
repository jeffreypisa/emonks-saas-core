# ADR-001 Template Strategie

## Status
Geaccepteerd

## Context
De plugin moet direct bruikbaar zijn, maar het theme moet altijd de presentatie kunnen overnemen.

## Beslissing
Template resolutievolgorde:
1. Theme override: `templates/emonks-saas/{template}`
2. Theme legacy override: `templates/{template}`
3. Plugin default: `templates/{template}`

De plugin bevat complete default templates (Bootstrap-vriendelijk, neutraal).
Het theme kan templates kopieren en aanpassen zonder plugin edits.

## Consequenties
- Snelle start met plugin defaults
- Volledige UX-vrijheid in theme
- Minder vendor lock-in op plugin markup
