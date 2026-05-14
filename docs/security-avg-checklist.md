# Security En AVG Checklist

## Core principes

- Elke mutatie gebruikt nonce-validatie.
- Account- en workspace-mutaties gebruiken account-scope guards.
- Input wordt gesanitized per veldtype.
- Output blijft responsibility van templates en moet escaped worden in Twig/PHP.
- ACF is optioneel en mag geen permissiebron zijn.

## Config mutaties

Admin config voor Fields, Forms en Services vereist `manage_options`.

Controleer per endpoint/form actie:
- nonce aanwezig
- capability check aanwezig
- keys via `sanitize_key`
- labels via `sanitize_text_field`
- textarea via `sanitize_textarea_field`
- URLs via `esc_url_raw`
- account/workspace ownership bij user-facing mutaties

## Publieke workspace

Publieke pagina is alleen zichtbaar als:
- workspace status `published` is
- owner een actieve subscription heeft
- public slug bestaat

## AVG

Bewaar alleen noodzakelijke data in `service_data_json`. Gebruik ACF voor rijke content wanneer dat redactioneel logischer is, maar niet voor autorisatie of subscription checks.
