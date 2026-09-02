# Changelog

All notable changes to this extension are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1] - 2026-09-02

### Changed

- The extension opts out of the automatic TER import from Packagist (`extra.typo3/cms.skip-ter-packagist-import`). The listing at `extensions.typo3.org/package/proudnerds/pn-questionnaire` came into being automatically; opting out is the documented prerequisite for claiming the extension key `pn_questionnaire` in the TER and publishing releases there. Nothing changes for Composer installations

### Fixed

- Documentation: notes and warnings are now GitHub-style admonitions, so they render as highlighted boxes on docs.typo3.org instead of plain quotes; plain-text diagrams no longer get PHP syntax highlighting; and `guides.xml` no longer opens with an XML declaration, which the TYPO3 documentation team advises against

## [1.2.0] - 2026-08-28

### Added

- TypoScript setting `progress_mode` to pick what the progress bar measures: `completed` (the default) counts the questions already answered, `position` follows the step number the way many questionnaires do. A site-wide setting rather than a FlexForm field, because having one questionnaire count differently from the next on the same site only confuses visitors

### Changed

- The progress bar now shows how much of the questionnaire is behind the visitor instead of which step they are on. The question being answered no longer counts, so the first question sits at 0% and the closing question at 80%; the bar no longer claims to be finished while there are still answers to give
- The bar also appears on the result page, at 100% and labelled "Completed" — the one place where the run really is over
- `aria-valuemin` and `aria-valuemax` are the percentage scale now, so a screen reader announces the same figure the bar shows. They used to be the step numbers, which made a screen reader say 0% while the bar showed 20%

### Fixed

- The install instructions described a Composer path repository inside a monorepo, which is not how anyone installs this extension. They now cover `composer require` and the Extension Manager in classic mode

## [1.1.0] - 2026-08-28

### Added

- **Text below the start button** on the questionnaire record — a rich text field for a closing remark on the introduction screen, such as how long the questionnaire takes. The introduction fields moved to their own **Introduction screen** tab
- Own button texts per plugin instance, on a new **Button labels** tab in the FlexForm. Every button the visitor sees can be given a text of its own — start, previous, next, finish, start over, change answers, copy link and send mail. A field left empty keeps the translated standard text, which is shown as the field's placeholder

### Upgrading from 1.0.0

- Run the **Database Analyser** in the Install Tool: the questionnaire table gains the column `introduction_footer_text`. Nothing else changes; existing records and plugin settings keep working as they are

## [1.0.0]

First public release.

### Added

- Multi-step questionnaire flow with progress indicator, built entirely from backend records: questionnaire, questions, answer options, conditions, result pages and advice blocks
- Question types single choice, multiple choice and scale, with optional branching on a given answer or a scale range
- Result pages triggered by score range, by a specific answer or as a catch-all, rendered inline, as a redirect to a page or external URL, or from a domain record
- Advice blocks shown conditionally per answer or score, with an option to invert a condition so a block appears when an answer was *not* given
- Group headings for advice blocks, giving the result a nested heading structure; a heading appears only when at least one block in its group is visible
- Anonymous result storage with a retrieval link of 128 bits of entropy. No name, IP address or e-mail address is stored, not even hashed
- Mail-to-self form on the result page, sending the full result plus the retrieval link in HTML and plain text, rate limited to three sends per hour per recipient and per client address
- Console command `pnquestionnaire:purgesavedresults` to remove stored results past their retention period, schedulable as a task
- Usage counters per questionnaire for starts and completions
- Frontend aimed at WCAG 2.2 AA, with a Lighthouse accessibility score of 99
- Dutch, German and English translations
- Editor and administrator documentation

### Compatibility

- TYPO3 12.4, 13.4 and 14.3 from a single codebase, each version verified with a full run-through
- PHP 8.2 and up

[Unreleased]: https://github.com/proudnerds-typo3/pn_questionnaire/compare/1.2.1...HEAD
[1.2.1]: https://github.com/proudnerds-typo3/pn_questionnaire/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/proudnerds-typo3/pn_questionnaire/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/proudnerds-typo3/pn_questionnaire/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/proudnerds-typo3/pn_questionnaire/releases/tag/1.0.0
