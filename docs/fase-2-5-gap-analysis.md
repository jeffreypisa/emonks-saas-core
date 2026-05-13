# Fase 2.5 Gap Analysis

## Current (na fase 2)
- Account-scope basis staat
- Module registry + eerste module (Client Portal) staat
- Service-items + REST basis staat
- Template overrides werken (plugin default + theme override)

## Gaps richting productiseerbare multiservice basis
1. Context-opbouw is nog verspreid (geen centrale context builder)
2. Capability naming/conventies zijn nog niet overal uniform
3. REST foutformat is nog niet volledig gestandaardiseerd
4. Module lifecycle contract is nog basic (voornamelijk `boot`)
5. Route/capability/security matrix ontbreekt als centraal referentiedoc
6. Security DoD-checklist per endpoint ontbreekt nog
7. Migration-runner patroon ontbreekt (buiten losse schema checks)
8. Bootstrap/SCSS afhankelijkheden niet als expliciete checklist vastgelegd

## Prioriteit (2.5)
- P1: capability/policy uniformering
- P1: REST error standaard
- P1: security checklist + route matrix
- P2: module lifecycle uitbreiding
- P2: context builder patroon
- P3: migration runner formaliseren

## Definition of Done Fase 2.5
- Architectuur- en datakeuzes traceerbaar via ADRs
- Uniform capability + account-scope model gedocumenteerd en toegepast
- REST en security patroon consistent
- Module lifecycle duidelijk genoeg voor tweede module (Guestbook)
