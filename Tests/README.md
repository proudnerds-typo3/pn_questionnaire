# Tests — pn_questionnaire

De testsuite draait op `typo3/testing-framework ^8.3` (PHPUnit 10.5), passend bij
TYPO3 v12.4. De extensie ondersteunt v12.4 en v13.4; op andere versies hoort een
bijpassende `typo3/testing-framework` (zie de CI-matrix).

**Geen TDD.** Tests worden naast en na de implementatie geschreven, niet als falende
test vooraf.

De commando's hieronder gaan uit van een monorepo waarin de extensie in `packages/` staat. Is de extensie een eigen repository, dan is het pad naar `phpunit.xml` gewoon `phpunit.xml` en staat PHPUnit in de eigen `vendor/bin/`. `Tests/bootstrap.php` zoekt de vendor-map in beide opstellingen zelf, dus de configuratie hoeft niet aangepast te worden.

## Snel draaien (composer-script, vanuit de repo-root)

```bash
ddev composer test:questionnaire             # unit-tests (met --testdox)
```

## Direct via PHPUnit

```bash
ddev exec vendor/bin/phpunit -c packages/pn_questionnaire/phpunit.xml --testsuite unit
```

### Eén test(class) of -methode

```bash
# Alleen één testclass
ddev exec vendor/bin/phpunit -c packages/pn_questionnaire/phpunit.xml --testsuite unit --filter ResultStorageServiceTest

# Eén methode
ddev exec vendor/bin/phpunit -c packages/pn_questionnaire/phpunit.xml --testsuite unit --filter derivesTheExpiryFromTheLifetimeInDays
```

## Structuur

```
Tests/
└── Unit/         # pure logica: services, zonder database en zonder TYPO3-bootstrap
```

## Wat wel en niet getest wordt

Het doel is dat **alle pure logica in de extensie** gedekt is: geen database,
geen fixtures, geen TYPO3-bootstrap. Alles wat een service van buiten nodig
heeft — repository, persistence manager, sessie, `Context` — gaat als mock naar
binnen. Alleen de repositories en de controller blijven er beargumenteerd buiten.

De extensie is met nul tests geïmporteerd, dus dit is een inhaalslag die nog
loopt. Wat er staat en wat nog moet:

| Unit | Te dekken | Stand |
|---|---|---|
| `ResultStorageService` | Tokenvorm, verloopdatum, bijwerken in plaats van een nieuwe rij | ✅ Gedekt |
| `ResultResolverService` | De vijf trigger-types van een resultaatpagina en de vier advice-blockcondities, inclusief de omgekeerde conditie met én zonder gegeven antwoord | ⬜ Nog te schrijven |
| `ConditionEvaluatorService` | Geen condities, één conditie, AND, OR, de vijf scale-operators, incomplete condities, en de sortering op `sort_order` | ⬜ Nog te schrijven |
| `ScoringService` | Som over meerdere vragen, scale- en informational-vragen overslaan, onbeantwoorde vragen, meervoudige keuze | ⬜ Nog te schrijven |
| `ProgressService` | Huidige stap en totaal, volgende en vorige vraag, laatste vraag, en het gedrag bij een onbekende vraag-uid | ⬜ Nog te schrijven |
| `SessionService` | Opslaan en teruglezen, meerdere vragenlijsten naast elkaar, leegmaken, het gedrag bij een lege sessie, en de tokenopslag | ⬜ Nog te schrijven |
| `DomainRecordResolverService` | Alleen de twee exception-paden; de rest leest `$GLOBALS['TCA']` en gebruikt de Extbase `UriBuilder` | ⬜ Nog te schrijven |

Bewust buiten de unit-tests:

| Niet | Waarom |
|---|---|
| De drie repositories | Query-opbouw vraagt functional tests met database-fixtures. Dat is een veelvoud aan werk plus structureel onderhoud, terwijl de browserverificatie van de frontend-flow diezelfde query's al raakt |
| `QuestionnaireController` | Bindt zich direct aan TYPO3-state (request, sessie, TypoScript); de flow wordt in de browser geverifieerd |
| Rate limiting, de gebruiksteller | Gedrag dat alleen over meerdere requests zichtbaar is — verifieerbaar met `curl`-loops en een handmatige doorloop, niet met een unit-test |

## Aandachtspunt: de opslagvorm van de antwoorden

`SavedResult` bewaart de antwoorden als JSON-envelop met een `version`-veld:

```json
{"version":1,"answers":{"12":["34"],"13":["2","3"]}}
```

Twee dingen om te weten wanneer je daaraan werkt:

1. **Het is met opzet een string-property, geen array.** Extbase mapt geen
   `array`-properties — `DataMapper::thawProperties()` heeft daar van TYPO3 v12 tot
   en met v14 `// Not supported, yet!` staan. De rauwe JSON staat daarom in
   `$answers`, met `setGivenAnswers()` / `getGivenAnswers()` eromheen.
2. **De kolom is met opzet `text` en geen json-kolom.** Op een
   Doctrine-`Types::JSON`-kolom encodeert `Connection::insert()` de waarde nóg een
   keer (`ensureDatabaseValueTypes()`), en omdat Extbase een string aanlevert kwam de
   JSON dubbel-geëncodeerd in de database te staan. Daarom staat `answers` expliciet
   in `ext_tables.sql`; de TCA houdt `type => 'json'` voor de leesbare weergave in de
   backend.

Verandert de structuur van de envelop, bump dan `SavedResult::ANSWERS_VERSION` en
regel wat er met bestaande rijen gebeurt.

## Conventies

- `#[Test]`-attribuut (geen `@test`-docblock); testnamen als volzin.
- Altijd `parent::setUp()` aanroepen; asserts via `self::assert*()`.
- Mock-properties typeren als `Type&MockObject` (PHPStan L5).
- Tijdsafhankelijke code: `Context` mocken en één vast "nu" gebruiken, zodat de
  verwachte verloopdatum uit te rekenen is in plaats van te benaderen.

## Kwaliteitspoorten vóór commit (alleen deze extensie)

```bash
ddev exec vendor/bin/phpstan analyse --memory-limit=1G packages/pn_questionnaire
ddev exec php-cs-fixer fix --dry-run --diff packages/pn_questionnaire/Classes/<bestand>.php
```
