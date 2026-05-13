# Security & AVG

## Endpoint checklist
- Authenticated user
- Module enabled check
- Capability via policy service
- Account-scope validation
- sanitize/validate input
- minimale response data

## Actie checklist
- Nonce op form submits
- `current_user_can`/policy checks
- Geen cross-account writes
- Geen onnodige PII in logs

## AVG baseline
- Data blijft account-gescopeerd
- Log data is operationeel en beperkt
- Verdere export/erase flows volgen in latere fase
