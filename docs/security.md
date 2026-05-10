# Security

Toegepaste maatregelen:
- Nonce checks op formulieracties
- Input sanitization (`sanitize_text_field`, `sanitize_key`, `sanitize_email`, `sanitize_title`)
- Ownership checks per workspace
- Admin bypass met capability checks
- Safe redirects (`wp_safe_redirect` + `exit`)
- REST permission callbacks + arg validatie
- Webhook signature gate + idempotency transient
- Basis rate limiting op login/register

Nooit gebruiken in uitbreidingen:
- Ongepreparede SQL
- Ongevalideerde POST data
- Ongeescape output in templates
