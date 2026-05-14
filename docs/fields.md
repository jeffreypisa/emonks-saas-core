# Fields

Fields vormen de centrale veldbibliotheek. Een veld wordt eenmalig gedefinieerd en kan in meerdere formulieren worden gebruikt.

Een veld bevat:
- key
- label
- type
- source: plugin, acf, computed
- required default
- placeholder
- help
- default
- validation rules
- optionele ACF mapping

## Sources

`plugin` wordt door Emonks SaaS opgeslagen.

`acf` wordt via ACF/postmeta beheerd.

`computed` is gereserveerd voor afgeleide waarden.

ACF field groups worden in de UI met naam getoond, maar intern met key opgeslagen.
