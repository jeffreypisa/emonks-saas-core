# Functioneel Ontwerp - Emonks SaaS Core

## 1. Doel en Scope
Emonks SaaS Core is een generieke WordPress plugin die SaaS-kernlogica levert, met dynamische backend-configuratie voor meerdere diensten.

## 2. Kernmodel
- **Plans**: custom post type (`emonks_plan`)
- **Features**: taxonomie (`emonks_feature`)
- **Services**: taxonomie (`emonks_service`)

Relaties:
- Plan -> meerdere features
- Plan -> meerdere services
- Service -> meerdere supported features (technische grens)

## 3. Beschikbaarheidsmodel
Een feature is beschikbaar voor gebruiker/workspace als alle lagen akkoord geven:
1. Service ondersteunt de feature
2. Plan bevat de feature
3. Global flag staat op enabled

## 4. Beheerflow
1. Maak features aan
2. Maak services aan en configureer supported features
3. Maak 1 of meer plannen aan
4. Koppel features/services aan elk plan
5. Stel pricing/limits in op planniveau
