# Security

Toegepaste maatregelen:
- Nonce checks op formulieracties
- Input sanitization (`sanitize_text_field`, `sanitize_key`, `sanitize_email`, `sanitize_title`)
- Ownership checks per workspace
- Admin bypass met capability checks
- Safe redirects (`wp_safe_redirect`)
- REST permission callbacks
- Webhook signature gate + idempotency transient

Nooit gebruiken in uitbreidingen:
- Ongepreparede SQL
- Ongevalideerde POST data
- Ongeescape output in templates
