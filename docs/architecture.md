# Architecture

## Separation of concerns
- Theme = presentatie
- Plugin = logica/framework

## Module overzicht
- Routing/Auth/Dashboard
- Workspaces/Permissions/Statuses
- Plans/Features/Services
- Billing/Stripe/Webhooks
- REST API
- Onboarding
- Logging
- Admin Settings omgeving
- Custom domain architectuurlaag

## Custom Domain concept
- Hostname mapping opslag
- Ownership verification token
- SSL status tracking
- Hostname lookup -> workspace context
- Voorbereid op reverse proxy/edge integratie
