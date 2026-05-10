# Billing

Emonks SaaS Core gebruikt Stripe als source of truth.

## Ondersteunde flows
- Customer create/reuse
- Checkout session create
- Customer portal session create
- Upgrade/downgrade plan flow
- Billing cycle: monthly/yearly

## Test mode
Beschikbaar via:
- WP Admin > Emonks SaaS > Billing > Test mode

Gedrag:
- Checkout en planwijzigingen worden lokaal gesimuleerd
- Geen live Stripe mutaties

## Plan change gedrag
- Actieve subscription: `change plan` flow
- Geen actieve subscription: `checkout` flow
- Upgrade/downgrade bepaald op planvolgorde
- Cycle wordt meegenomen (`monthly` of `yearly`)

## Webhook events
- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

## Security
- Stripe signature parsing (`t`, `v1`)
- HMAC SHA256 verificatie
- timestamp tolerance (5 min)
- idempotency via event-id transient
