# REST API

Core endpoints:

- `GET /emonks/v1/health`
- `GET /emonks/v1/me`
- `GET /emonks/v1/services`
- `GET /emonks/v1/workspaces`
- `POST /emonks/v1/workspaces`
- `GET /emonks/v1/workspaces/{id}`
- `PUT/PATCH /emonks/v1/workspaces/{id}`
- `GET/PUT /emonks/v1/config/forms/{key}`
- `GET/PUT /emonks/v1/config/services/{key}`
- `GET/PUT /emonks/v1/config/emails/{key}`

Er zijn geen hardcoded service-module endpoints. Nieuwe servicegedrag hoort via dynamische service/form configuratie of expliciete custom extensies te lopen.
