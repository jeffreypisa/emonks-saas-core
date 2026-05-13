# REST API

Namespace: `emonks/v1`

## Core
- `GET /health`
- `GET /me`
- `GET /services`
- `GET /workspaces`
- `POST /workspaces`
- `GET /workspaces/{id}`
- `PUT|PATCH /workspaces/{id}`

## Client Portal module
- `GET /service-items`
- `POST /service-items`
- `GET /service-items/{id}`
- `PUT|PATCH /service-items/{id}`

### POST /service-items payload
- `workspace_id` (int, required)
- `title` (string, required)
- `status` (string, optional)

### Security
- Module endpoints alleen actief als module enabled is
- Account-scope + policy checks verplicht
