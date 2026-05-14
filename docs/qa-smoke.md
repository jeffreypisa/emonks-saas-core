# QA Smoke Tests

## Verse installatie

1. Activeer plugin.
2. Open `Emonks SaaS > Services`.
3. Verwacht: lege state met knop `Nieuwe service`.
4. Open `Emonks SaaS > SaaS Health`.
5. Verwacht: core checks groen, services als informatief/nog niet ingericht.

## Service aanmaken

1. Maak service `intake_portal`.
2. Verwacht: service verschijnt in lijst.
3. Bewerk service en zet status op active.
4. Koppel `workspace_create` en `workspace_edit` onder Formulieren.

## Fields en Forms

1. Open `Fields` en voeg een plugin veld toe.
2. Open `Forms` en koppel dat veld aan een formulier.
3. Koppel het formulier aan een service context.
4. Maak een workspace met die service.
5. Verwacht: dynamische velden worden opgeslagen in `service_data_json`.

## ACF

1. Activeer ACF Pro.
2. Selecteer een ACF field group bij een service of veld.
3. Verwacht: dropdown toont naam plus key.
4. De plugin mag niet fatalen als ACF uit staat.
