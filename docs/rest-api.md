# REST API

Namespace: `emonks/v1`

## Endpoints
- `GET /health`
- `GET /me`
- `GET /services`
- `GET /workspaces`
- `POST /workspaces`
- `GET /workspaces/{id}`
- `PUT|PATCH /workspaces/{id}`

## Validatie
Routes gebruiken `args`, sanitize callbacks en validate callbacks voor o.a.:
- `service_type`
- `workspace_status`
- `public_slug`

## Authorisatie
Per endpoint dedicated permission callbacks.

## Gedrag publicatie/slugs
- `published` status vereist policy-voorwaarden (billing/onboarding)
- slug conflicts geven 422 + suggesties
