# pn_questionnaire

**TYPO3 extension for questionnaires, self-assessments, tests and decision trees.**

| | |
|---|---|
| **Extension key** | `pn_questionnaire` |
| **TYPO3 compatibility** | 12.4, 13.4, 14.3 |
| **PHP** | 8.2+ |
| **Author** | ProudNerds |
| **Version** | 1.2.0 |
| **License** | [GPL-2.0-or-later](https://github.com/proudnerds-typo3/pn_questionnaire/blob/main/LICENSE.txt) |

---

## What this extension does

`pn_questionnaire` lets editors build multi-step questionnaires entirely in the TYPO3 backend — no code required. Supported use cases include:

- Step-by-step **decision trees** (beslisbomen) that route visitors to the right service
- **Scored self-assessments** (health checks, sustainability scans, skills tests)
- **Guided quizzes** with a personalised result at the end
- **Multi-path flows** where answers determine which questions appear next

---

## Documentation

| Document | Audience |
|---|---|
| [Editor Guide](Editor.md) | Content editors and site administrators — how to build and manage questionnaires in the backend |
| [Admin Guide](Admin.md) | Administrators, integrators and developers — how to install, configure, theme and extend the extension |

---

## Quick start

1. Load the TypoScript: on TYPO3 v13 and v14 add the Site Set `proudnerds/pn-questionnaire` to your site configuration; on v12 include the static template *Questionnaire / Test / Decision tree*. See *Loading the TypoScript* in the [Admin Guide](Admin.md).
2. Create a **Questionnaire** record in the TYPO3 list module (a dedicated sysfolder is recommended)
3. Add questions, answer options and result pages to the record
4. Place the **Questionnaire / Decision Tree** plugin on a page
5. Select the Questionnaire record in the plugin FlexForm


