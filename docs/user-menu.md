# User Menu (Profielmenu)

## Doel
Een dynamisch accountmenu voor ingelogde gebruikers, configureerbaar via:
- `Emonks SaaS > Profielmenu`

## Instellingen
- `enabled`: profielmenu aan/uit.
- `show_avatar`: avatar tonen.
- `name_mode`: `full`, `first`, `display`.
- `link_type`: `link` of `button` (altijd `btn-sm`).
- `button_style`: `btn-light`, `btn-outline-contrast`, `btn-dark`, `btn-primary`, `btn-gradient-light`, `btn-gradient-dark`.
- `links`: toggles voor `account`, `workspaces`, `settings`, `billing`, `logout`.

## Plaatsen in menu
Er zijn 2 paden:
1. `Weergave > Menu's` via metabox `Emonks Profielmenu`.
2. Fallback in `Emonks SaaS > Profielmenu`: "Direct toevoegen aan bestaand menu".

De fallback voegt het item server-side toe en omzeilt eventuele JS issues in `nav-menus.php`.

## Shortcode
- `[emonks_user_menu]`

Te gebruiken buiten reguliere menu-locaties, met eigen dropdown styling/JS vanuit plugin assets.

## Skeletor compatibiliteit (zonder theme edits)
Skeletor mapt in menu partials `link_stijl` op:
- `link`
- `btn-primary`
- `btn-secondary`

Daarom wordt plugin button style in menu-context gemapt naar:
- `btn-primary` -> `btn-primary`
- alle andere button styles -> `btn-secondary`
- `link_type=link` -> `link`

Voor shortcode-rendering blijven de gekozen button classes wel direct van kracht.

## Zichtbaarheid
- Uitgelogd: item wordt niet gerenderd.
- Ingelogd: parent item toont gebruikersnaam, children bevatten geconfigureerde links.
