# Template Overrides

Het theme blijft verantwoordelijk voor presentatie.

De plugin geeft Twig context door met onder andere:
- service
- service_schemas
- field_library
- form_templates
- resolved forms
- workspace service_data
- workspace acf_data

Template resolution:
1. theme override
2. plugin template

Service-specifieke templates zijn optioneel en worden via service `render_hints.template` gekoppeld.
