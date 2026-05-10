# Billing

Emonks SaaS Core gebruikt provider abstractie met Stripe als default source of truth.

## Ondersteunde flows
- Customer create/reuse
- Checkout session create
- Customer portal session create
- Upgrade/downgrade plan flow
- Billing cycle: monthly/yearly

## Provider model
- Interface: `BillingProviderInterface`
- Default: `StripeBillingProvider`
- Override via `emonks_billing_provider`

## Plan change gedrag
- Actieve subscription: `change plan` flow
- Geen actieve subscription: `checkout` flow
- Plan change bevat eerst preview en daarna expliciete confirm submit

## Test mode
- Checkout en planwijzigingen worden lokaal gesimuleerd
- Geen live provider mutaties

## Webhook events (Stripe)
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
