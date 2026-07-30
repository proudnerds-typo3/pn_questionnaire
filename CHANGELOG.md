# Changelog

All notable changes to this extension are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/proudnerds-typo3/pn_questionnaire/compare/1.0.0...HEAD
[1.0.0]: https://github.com/proudnerds-typo3/pn_questionnaire/releases/tag/1.0.0
