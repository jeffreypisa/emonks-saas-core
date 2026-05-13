# Bootstrap & SCSS (Plugin Templates)

Doel van dit document: vastleggen welke Bootstrap componenten de plugin-templates nodig hebben, zonder dat de plugin styling afdwingt.

## Uitgangspunten

- Plugin levert alleen semantische Bootstrap-markup in Twig templates.
- Theme blijft eigenaar van visuele stijl.
- Geen plugin CSS nodig voor account/module templates (behalve bestaande admin-only inline CSS in WP admin).
- Gebruik bestaande theme tokens/SCSS in plaats van nieuwe losse kleuren/tokens.

## Benodigde Bootstrap componenten/utilities

Gebaseerd op huidige templates in `templates/account/*`, `templates/modules/client-portal/*`, `templates/public/workspace.twig`.

- Layout/utilities:
  - `container`, `row`, `col-*`, `d-flex`, `justify-content-*`, `align-items-*`, `gap-*`, `w-100`
  - spacing utilities: `p-*`, `m-*`, `py-*`, `mb-*`, `mt-*`
- Cards:
  - `card`, `card-body`, `shadow-sm`, `border-0`
- Buttons:
  - `btn`, `btn-primary`, `btn-outline-secondary`, `btn-sm`
- Forms:
  - `form-label`, `form-control`, `form-select`, `form-text`
- Tables:
  - `table`, `table-light`, `table-responsive`, `align-middle`
- Feedback:
  - `alert`, `alert-*`, `alert-dismissible`, `btn-close`
  - `badge`, `text-bg-*`
- Overig:
  - `progress`, `progress-bar` (onboarding)

## Relevante theme SCSS-bestanden

In theme: `/Users/jeffreypisa/Sites/skeletor/wp-content/themes/skeletor/assets/scss`

- `settings/_colors.scss`
- `settings/_buttons.scss`
- `settings/_card.scss`
- `settings/_form.scss`
- `settings/_accordion.scss`
- `settings/_grid.scss`
- `settings/_helper.scss`
- `layout/_section.scss`
- `components/_buttons.scss`
- `components/_theme-preview.scss`

## Aandachtspunten met huidige theme

- `layout/_section.scss` bepaalt section spacing; plugin templates zijn daarom bewust in `<section class="section emonks-saas-section">` gewrapt.
- `settings/_grid.scss` bevat globale grid/gutter overrides; controleer tabellen/cards op smalle schermen.
- `components/_theme-preview.scss` overschrijft veel `btn` variabelen; plugin knoppen erven daardoor automatisch de theme stijl.

## Richtlijnen voor nieuwe plugin templates

- Gebruik primair Bootstrap classes, geen inline styles.
- Geen nieuwe `.emonks-*` visual classes toevoegen voor frontend, tenzij puur functioneel/namespace nodig.
- Statuskleuren via helper (`emonks_get_status_badge_class`) en Bootstrap `text-bg-*`.
- Geen hardcoded kleuren in Twig.
- Houd markup “override-vriendelijk”: simpele structuur, geen diepe wrappers.

## Minimale Bootstrap coverage check (per release)

- [ ] Alerts renderen zichtbaar (`alert-*`, `btn-close`)
- [ ] Form velden leesbaar en focus-states ok
- [ ] Cards en tables tonen correct op mobiel
- [ ] Badges hebben voldoende contrast
- [ ] Buttons erven theme-primary en outline varianten correct
