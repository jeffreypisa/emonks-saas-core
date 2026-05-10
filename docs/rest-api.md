# REST API

Namespace: `/wp-json/emonks/v1/`

## Core endpoints
- `GET /health`
- `GET /me`

## Service endpoints
- `GET /services`

## Workspace endpoints
- `GET /workspaces`
- `POST /workspaces`
- `GET /workspaces/{id}`
- `PUT|PATCH /workspaces/{id}`

Alle endpoints gebruiken permission callbacks en ownership checks waar relevant.
