# pn_questionnaire

**TYPO3 extension for questionnaires, self-assessments, tests and decision trees.**

Editors build multi-step questionnaires entirely in the TYPO3 backend — no code required. Answers drive which advice blocks appear on the result page, and the result can be stored anonymously so a visitor can retrieve it through a link or mail it to themselves.

| | |
|---|---|
| **Extension key** | `pn_questionnaire` |
| **Composer** | `proudnerds/pn-questionnaire` |
| **TYPO3** | 12.4, 13.4, 14.3 |
| **PHP** | 8.2+ |
| **License** | [GPL-2.0-or-later](LICENSE.txt) |

## Installation

```bash
composer require proudnerds/pn-questionnaire
```

Then run the **Database Analyser** in the Install Tool to create the tables, and load the TypoScript: on TYPO3 v13 and v14 add the Site Set `proudnerds/pn-questionnaire` to your site configuration, on v12 include the static template *Questionnaire / Test / Decision tree*.

## Documentation

| Document | Audience |
|---|---|
| [Overview](Documentation/README.md) | Start here — what the extension does and a quick start |
| [Editor Guide](Documentation/Editor.md) | Content editors — building and managing questionnaires in the backend |
| [Admin Guide](Documentation/Admin.md) | Administrators, integrators and developers — install, configure, theme and extend |

## Credits

The backend icons in `Resources/Public/Icons/`, `ext_icon.svg` included, come from [Tabler Icons](https://tabler.io/icons) and are used under the **MIT** licence. They are committed to this repository; `download-icons.sh` only exists to refetch them and is excluded from the distribution package.

Author: Jacco van der Post, [Proud Nerds](https://www.proudnerds.com).

## Features

- Multi-step flow with progress indicator, single choice, multiple choice and scale questions
- Result pages selected by score range, by a specific answer, or as a catch-all
- Advice blocks shown conditionally per answer, with the option to invert a condition
- Group headings that give the result a nested heading structure
- Anonymous result storage: no name, no IP address, no e-mail address, not even hashed
- Retrieval link with 128 bits of entropy, and a mail-to-self form with rate limiting
- Scheduler command to purge expired results
- Usage counters per questionnaire
- Accessible frontend, aimed at WCAG 2.2 AA
