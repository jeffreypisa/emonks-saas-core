# Page Assignments (Settings)

In `Emonks SaaS > Settings > Pages` kun je core routes koppelen aan bestaande WordPress-pagina's.

## Hoe het werkt

- Per route kies je een gepubliceerde pagina.
- De plugin gebruikt daarna de **slug van die pagina** als route-segment (slug override).
- Laat je een route leeg, dan valt de plugin terug op de standaard route.

Ondersteunde route keys:

- `account`
- `workspaces`
- `billing`
- `settings`
- `onboarding`
- `login`
- `register`
- `logout`

## Belangrijk

- Als je page assignment wijzigt, kunnen bestaande URL's veranderen.
- Sla na wijzigingen indien nodig eenmalig permalinks opnieuw op via `Instellingen > Permalinks`.
