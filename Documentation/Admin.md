# Admin Guide — pn_questionnaire

This guide covers installation, TypoScript configuration, theming, and extending the extension.

---

## Table of Contents

1. [Installation](#1-installation)
2. [Loading the TypoScript](#2-loading-the-typoscript)
3. [TypoScript configuration](#3-typoscript-configuration)
4. [Configuring domain record outcomes](#4-configuring-domain-record-outcomes)
4a. [Stored results and the result mail](#4a-stored-results-and-the-result-mail)
5. [Overriding templates](#5-overriding-templates)
6. [Styling](#6-styling)
7. [JavaScript](#7-javascript)
8. [Adding a language](#8-adding-a-language)
9. [Architecture overview](#9-architecture-overview)
10. [Service layer](#10-service-layer)
11. [Session data format](#11-session-data-format)
12. [Extending the extension](#12-extending-the-extension)

---

## 1. Installation

### Composer

```bash
composer require proudnerds/pn-questionnaire
```

TYPO3 picks the extension up automatically; there is nothing to activate by hand.

### Classic mode

Install **Questionnaire / Test / Decision tree** from the TYPO3 Extension Repository through
**Admin Tools → Extensions**, then activate it there.

### Both routes

After the first install, run the **Database Analyser** in the TYPO3 Install Tool to create the
seven tables, and load the TypoScript as described in the next section. The same applies after an
update that adds a field — the changelog says so per release.

---

## 2. Loading the TypoScript

The TypoScript itself lives in `Configuration/TypoScript/{setup,constants}.typoscript` — a location every supported TYPO3 version understands. It is offered through two routes, both pointing at those same two files:

| TYPO3 version | Route |
|---|---|
| 14.3 | Site Set `proudnerds/pn-questionnaire` (preferred) |
| 13.4 | Site Set `proudnerds/pn-questionnaire` (preferred) |
| 12.4 | Static template *Questionnaire / Test / Decision tree* |

Site Sets were introduced in TYPO3 v13.1 and are ignored by v12, which is why the static template exists. `Configuration/Sets/PnQuestionnaire/{setup,constants}.typoscript` contain nothing but an `@import` of the files above.

### On TYPO3 v13 and v14 — Site Set

Add the set to any site that should use the questionnaire plugin by adding it to `dependencies` in the site's `config.yaml`:

```yaml
# config/sites/your-site/config.yaml
dependencies:
  - proudnerds/pn-questionnaire
```

The set name is defined in `Configuration/Sets/PnQuestionnaire/config.yaml`:

```yaml
name: proudnerds/pn-questionnaire
label: Questionnaire / Test / Decision tree
```

TYPO3 automatically includes `setup.typoscript` and `constants.typoscript` from the set directory when the dependency is active.

> **Troubleshooting — TypoScript from the set is not loaded**
>
> If the set is listed in `dependencies` but the TypoScript is still not applied, check the **root TypoScript template** in the TYPO3 backend:
>
> 1. Go to **Web → Template**, select the root page, open the template record.
> 2. On the **Options** tab, make sure **"Clear constants"** and **"Clear setup"** are both **unchecked**.
>
> When either flag is checked, TYPO3 discards all TypoScript that was loaded by Site Sets *before* evaluating the template's own content — effectively wiping `setup.typoscript` entirely. Unchecking both flags lets the Site Set TypoScript survive and be extended by the template.

### On TYPO3 v12 — static template

The extension registers `Configuration/TypoScript/` as a selectable static template in `Configuration/TCA/Overrides/sys_template.php`. Go to **Web → Template**, open the root template record and add **Questionnaire / Test / Decision tree (pn_questionnaire)** to *Include static (from extensions)*.

Include it **before** any site package that overrides the view path constants, otherwise those overrides are undone again.

Do not combine the static template with a site package that already imports `EXT:pn_questionnaire/Configuration/TypoScript/setup.typoscript` directly — the values are identical so `setup` stays correct, but a second pass over the constants can override a site package's view path overrides depending on include order.

### Backend search on TYPO3 v14

On v12 and v13 the records of this extension declare their backend search fields explicitly. TYPO3 v14 dropped that mechanism and derives searchability from the field type instead, so on v14 a search in the list module covers every text-like field of these tables rather than a curated subset. The extension follows each version's own convention; nothing needs configuring.

---

## 3. TypoScript configuration

All settings live under `plugin.tx_pnquestionnaire_questionnaire`.

### View path overrides

Override the default template paths per site package using the constants:

```typoscript
plugin.tx_pnquestionnaire_questionnaire {
  view {
    templateRootPath = EXT:your_sitepackage/Resources/Private/Templates/PnQuestionnaire/
    partialRootPath  = EXT:your_sitepackage/Resources/Private/Partials/PnQuestionnaire/
    layoutRootPath   = EXT:your_sitepackage/Resources/Private/Layouts/PnQuestionnaire/
  }
}
```

TYPO3 merges these with the extension defaults using indexed path arrays (index `1` overrides index `0`). You only need to place the files you want to override.

### Default FlexForm values

These defaults apply when a new plugin instance is inserted. Editors can override them per placement.

```typoscript
plugin.tx_pnquestionnaire_questionnaire.settings {
  show_score          = 0   # 0 = hidden, 1 = visible
  show_answer_summary = 0
  introduction_screen = 1
}
```

### What the progress bar measures

Two conventions are in common use for a questionnaire, and the site picks one:

```typoscript
plugin.tx_pnquestionnaire_questionnaire.settings {
  progress_mode = completed
}
```

| Value | Question 1 of 5 | Question 5 of 5 | Result page |
|---|---|---|---|
| `completed` (default) | 0% | 80% | 100% |
| `position` | 20% | 100% | 100% |

`completed` counts the questions already answered, so the bar never claims to be finished while
there are still answers to give, and the result page is what completes it. `position` follows the
step number, the convention many questionnaires use, at the cost of a bar that is already full
on the closing question.

The counter above the bar reads "step X of Y" either way — that number is the visitor's position
and does not change with this setting. Under `completed` it therefore says "step 1 of 5" next to
an empty bar, which is intentional: the step is the one being answered, the bar is what lies
behind it.

This is a TypoScript setting rather than a FlexForm field on purpose. It is a design decision for
the whole site; having one questionnaire count differently from the next on the same site would
only confuse visitors.

---

## 4. Configuring domain record outcomes

> **Note:** "Domain record" here refers to a **domain model record** in the Extbase/DDD sense (e.g. a news article, an activity, an ad) — not a domain name or HTTP address.

The **Domain record** outcome type redirects the visitor to the detail view of a record from another extension. One target can be configured per site via TypoScript and a TCA override.

### Step 1 — TypoScript

```typoscript
plugin.tx_pnquestionnaire_questionnaire.settings {
  domain_record_target {
    pageUid    = 42    # UID of the page that hosts the detail plugin
    extension  = News  # Extension name (CamelCase, no vendor prefix)
    controller = News
    action     = detail
    argument   = news  # Argument name the action expects
    plugin     = Pi1   # Plugin name as registered with ExtensionUtility
  }
}
```

### Step 2 — TCA override

The extension ships `record_uid` as a plain number, because it cannot know which table your site links to. The site package names that table, and turns the field into a record browser while it is at it. Create `Configuration/TCA/Overrides/tx_pnquestionnaire_result_page.php` in your site package:

```php
$config = &$GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config'];
$config['type'] = 'group';
$config['allowed'] = 'tx_news_domain_model_news';
$config['foreign_table'] = 'tx_news_domain_model_news';
$config['size'] = 1;
```

`type` and `allowed` give you the record browser; `foreign_table` is what Extbase needs to resolve the record. Both table names must match the TypoScript target.

Do not leave `allowed` empty on a `group` field. TYPO3 throws `RuntimeException` 1482250512 while compiling the form, which takes down the entire result page editing form — not just this one field — even for result pages that use a different outcome type. That is why the shipped default is a number and not an unconfigured group.

The `DomainRecordResolverService` reads the `foreign_table` value from `$GLOBALS['TCA']` at runtime to derive the table name — so a single source of truth is maintained between TypoScript and TCA.

---

## 4a. Stored results and the result mail

Both are off by default and are switched on per plugin instance. See the Editor Guide for what an editor sees; this section covers the technical side.

### The table

`tx_pnquestionnaire_saved_result` holds one row per completed run.

| Column | Purpose |
|---|---|
| `token` | 32 hex characters, 128 bits of entropy, from `random_bytes()`. Unique index. This is the only key to the row |
| `result_url` | The generated retrieval URL, cached so the mail and the result page do not have to rebuild it |
| `questionnaire` | The questionnaire the run belongs to |
| `answers` | The given answers, as JSON |
| `score` | The calculated score |
| `expires` | Unix timestamp after which the row is due for removal |
| `crdate`, `tstamp`, `deleted` | Standard TYPO3 fields; the table uses soft delete |

**No identifying data is stored** — no name, no IP address, no e-mail address, not even hashed. The `crdate` timestamp is the only field that could in theory be correlated with an access log, which is a reason not to extend the retention period beyond what is needed.

### The retrieval route

`Configuration/Routing/SavedResult.yaml` provides a route enhancer producing `/<plugin-page>/saved-result/<token>`. An extension cannot register route enhancers itself, so the site configuration has to import it:

```yaml
# config/sites/your-site/config.yaml
imports:
  - resource: 'EXT:pn_questionnaire/Configuration/Routing/SavedResult.yaml'
```

Without that import the retrieval link still works, but as a query-string URL. The first segment follows the site language (`saved-result`, `bewaarde-uitslag`, `gespeichertes-ergebnis`); add a locale to the `localeMap` for another language, or override the whole `PnQuestionnaireSavedResult` key after the import to change the wording. The `token` requirement is pinned to `[0-9a-f]{32}` so the enhancer never claims an unrelated URL on a page without the plugin.

After a domain change or a change to this route, the stored `result_url` values are stale. They can be regenerated from the tokens — the URL is derived data, not a source.

### Retention and purging

The retention period is `db_save_result_lifetime_days` in TypoScript, default 365, overridable per plugin instance through the FlexForm. It is applied when a result is stored, by writing `expires`.

Expired rows are removed by a console command:

```bash
vendor/bin/typo3 pnquestionnaire:purgesavedresults
vendor/bin/typo3 pnquestionnaire:purgesavedresults --dry-run
```

Register it as a scheduler task to keep the table clean. Unlike a backend deletion, the command removes rows for real rather than flagging them deleted — a stored result that has expired should not linger in the recycler.

### The result mail

The mail is sent as HTML and plain text, using the installation's own `SystemEmail` layout, so it inherits the styling already configured for TYPO3 mails.

Rich text inside the mail runs through `lib.parseFunc_pnQuestionnaireMail`, a copy of `lib.parseFunc` with `forceAbsoluteUrl` on. A relative `href` is a dead link in a mail client, and no Fluid ViewHelper can reach inside stored rich text — `f:format.html` hands the HTML straight to parseFunc. The nested parseFunc references for lists, preformatted text and table cells are repointed one by one; without that a link inside a list would still come out relative.

The sender falls back through three levels: the plugin's FlexForm, then TypoScript (`mail_from_address` / `mail_from_name`), then the installation's default sender.

### The send limit

`Configuration/Services.yaml` defines a rate limiter for the mail form: **three sends per hour**, counted separately per recipient address and per client address. It uses the Symfony rate limiter that ships with the core, so there is no custom counting.

Two things to be aware of. The per-client limit is only as reliable as the proxy configuration — if the site runs behind a reverse proxy, `reverseProxyIP` has to be configured or every visitor looks like the same client. And the FlexForm has a **Switch off the send limit** checkbox intended for testing an installation; it should be off on a live site, because the limit is what prevents the form from being used to send mail to other people's addresses in bulk.

The form's protection against cross-site submission relies on `FE.cookieSameSite` being `lax` or stricter. That is a requirement, not a detail.

### Usage counters

With **Count usage** on, the questionnaire record keeps two tallies: `starts`, incremented when a visitor begins, and `completions`, incremented on a completed run. They are plain counters on the questionnaire record — no per-visitor data is involved.

### Inverted conditions

`negate_condition` on `tx_pnquestionnaire_advice_block` inverts a `specific_answer` condition. It is applied in `ResultResolverService`, at the point where the block's trigger answer is matched against the given answers. An absent answer counts as "not given", so an inverted block also appears for a question the visitor never answered — which is why the Editor Guide recommends making such questions required.

---

## 5. Overriding templates

The extension uses standard TYPO3 Fluid template path merging. Copy any file from the extension's `Resources/Private/` tree into your site package at the configured override path and TYPO3 will use your version instead.

### Default paths (from extension)

```
Resources/Private/
├── Layouts/
│   └── Default.html
├── Templates/
│   └── Questionnaire/
│       ├── Intro.html
│       ├── Question.html
│       └── Result.html
└── Partials/
    ├── AnswerTypes/
    │   ├── SingleChoice.html
    │   ├── MultipleChoice.html
    │   ├── YesNo.html
    │   ├── Scale.html
    │   └── Informational.html
    └── AdviceBlock.html
```

### Custom ViewHelper namespace

The extension provides one custom ViewHelper. Declare its namespace in any template that uses it:

```html
<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
      xmlns:pnq="http://typo3.org/ns/ProudNerds/PnQuestionnaire/ViewHelpers"
      data-namespace-typo3-fluid="true">
```

Available ViewHelpers:

| ViewHelper | Description |
|---|---|
| `pnq:inArray(haystack: array, needle: value)` | Returns `true` when `needle` exists in `haystack` (string-cast comparison). Used in answer type partials to restore the checked state of previously given answers. |

### Key template variables

#### `Question.html`

| Variable | Type | Description |
|---|---|---|
| `questionnaire` | `Questionnaire` | The questionnaire record |
| `question` | `Question` | The current question |
| `progress` | `array{current: int, total: int}` | Step X of Y |
| `progressPercentage` | `int` | 0–100: the share of the questionnaire behind the visitor. The question on screen is not counted yet, so the first question is 0% and the closing question is 80%. Only the result page renders 100% |
| `prevQuestionUid` | `int\|null` | UID of the previous question, or `null` if first |
| `currentAnswer` | `string[]` | Previously stored answer values for this question |
| `hasAnswers` | `bool` | `true` when at least one answer is stored in the session — used to conditionally show the Reset button |
| `answerTypePartial` | `string` | Partial path, e.g. `AnswerTypes/SingleChoice` |
| `scaleRange` | `int[]` | Array from `scaleMin` to `scaleMax` (radio scale only) |
| `scaleDisplay` | `string` | `'radio'` or `'range'` — from the question's **Scale display** field |
| `scaleMiddle` | `int` | Midpoint of the scale range: `round((scaleMin + scaleMax) / 2)` — used as the default slider position |

#### `Result.html`

| Variable | Type | Description |
|---|---|---|
| `questionnaire` | `Questionnaire` | The questionnaire record |
| `resultPage` | `ResultPage\|null` | The matched result page (null = no catch-all configured) |
| `adviceBlocks` | `AdviceBlock[]` | Pre-filtered visible advice blocks |
| `totalScore` | `float` | Calculated score (0.0 when no scores are set) |
| `showScore` | `bool` | From FlexForm setting |
| `showAnswerSummary` | `bool` | From FlexForm setting |
| `answerSummary` | `array[]` | Pre-built list of `{question, answers[]}` for the summary |

### Form field naming

Answer inputs must use this exact `name` attribute for Extbase to map them correctly to `processAction(array $answers)`:

```html
name="tx_pnquestionnaire_questionnaire[answers][]"
```

The hidden question UID field:

```html
name="tx_pnquestionnaire_questionnaire[questionUid]"
```

### Heading levels, and why the RTE needs Heading 4 and Heading 5

The result page builds a heading hierarchy from three sources, only two of which the templates control:

| Level | Rendered by | Content |
|---|---|---|
| `h2` | `Result.html` | Result page headline |
| `h3` | `AdviceBlock.html` | A group heading (`condition_type = group_header`), or a block title that is not in a group |
| `h4` | `AdviceBlock.html` | The title of a block that points at a group heading through `group_header` |
| `h5` | The editor, inside `body_text` | A sub-heading within a block |

That last row is the catch: the deepest level comes out of a rich text field, so it is the RTE
configuration — not the extension — that decides whether an editor can produce a valid document.
An installation therefore needs **both Heading 4 and Heading 5** available in the RTE preset used
for `tx_pnquestionnaire_advice_block.body_text`:

- **Heading 5** for a sub-heading inside a *grouped* block, whose title is already an `h4`.
- **Heading 4** for a sub-heading inside an *ungrouped* block, whose title is an `h3`.

Which one an editor needs depends on whether the block sits in a group, and that can change after
the fact — moving a block into a group shifts its title from `h3` to `h4`, so any sub-heading in its
body has to move down a level too. Offer both and the editor can always pick the level one step
below the block title.

If the preset stops at Heading 3, every sub-heading an editor creates lands at the same level as —
or above — the title of the block it belongs to, which breaks the document outline (WCAG 1.3.1).
The extension deliberately does not set `richtextConfiguration` on the field to force this: that
would override whatever preset the site assigns through page TSconfig, for every installation.

Add the missing levels to the `heading.options` list of the preset your site already uses:

```yaml
editor:
  config:
    heading:
      options:
        - { model: 'paragraph', title: 'Paragraph' }
        - { model: 'heading2', view: 'h2', title: 'Heading 2' }
        - { model: 'heading3', view: 'h3', title: 'Heading 3' }
        - { model: 'heading4', view: 'h4', title: 'Heading 4' }
        - { model: 'heading5', view: 'h5', title: 'Heading 5' }
```

Note that CKEditor replaces this list as a whole rather than merging it, so if you override it in a
separate file, repeat the entries you want to keep. Also check that `h5` is present in the
`allowTags` list of the processing configuration — otherwise the tag is stripped on save. The
default `EXT:rte_ckeditor/Configuration/RTE/Processing.yaml` allows `h1` through `h6`.

---

## 6. Styling

The extension ships a minimal CSS file (`Resources/Public/Css/Questionnaire.css`) that only defines the progress bar. Everything else is unstyled — apply all visual design in your site package.

### BEM class reference

| Class | Element |
|---|---|
| `.pn-questionnaire` | Root wrapper (also has `data-questionnaire` attribute) |
| `.pn-questionnaire__intro` | Intro screen container |
| `.pn-questionnaire__intro-text` | Introduction text area |
| `.pn-questionnaire__intro-actions` | Start button wrapper |
| `.pn-questionnaire__intro-footer` | Text below the start button |
| `.pn-questionnaire__step` | Question step container (has `data-active-step` attribute) |
| `.pn-questionnaire__progress` | Progress indicator wrapper (rendered by the `Progress` partial, shared by the question steps and the result page) |
| `.pn-questionnaire__progress-bar` | Grey track of the progress bar |
| `.pn-questionnaire__progress-fill` | Coloured fill (width set inline via `progressPercentage`) |
| `.pn-questionnaire__progress-text` | "Step X of Y" on a question, "Completed" on the result page |
| `.pn-questionnaire__progress-note` | Dynamic steps note below the bar |
| `.pn-questionnaire__context-content` | Embedded `tt_content` element |
| `.pn-questionnaire__question-text` | Question text area |
| `.pn-questionnaire__help-text` | Help text below question |
| `.pn-questionnaire__form` | The answer form |
| `.pn-questionnaire__answers` | Fieldset wrapping all answer inputs |
| `.pn-questionnaire__answer-option` | Wrapper for one answer option |
| `.pn-questionnaire__answer-option--radio` | Modifier for radio inputs |
| `.pn-questionnaire__answer-option--checkbox` | Modifier for checkbox inputs |
| `.pn-questionnaire__answer-option--yes-no` | Modifier for yes/no inputs |
| `.pn-questionnaire__radio` | Radio input |
| `.pn-questionnaire__checkbox` | Checkbox input |
| `.pn-questionnaire__answer-label` | Label for an answer option |
| `.pn-questionnaire__scale` | Scale question wrapper |
| `.pn-questionnaire__scale--radio` | Modifier: radio button display |
| `.pn-questionnaire__scale--range` | Modifier: range slider display |
| `.pn-questionnaire__scale-options` | Row of scale radio buttons (radio display only) |
| `.pn-questionnaire__scale-option` | One scale value (radio display only) |
| `.pn-questionnaire__scale-label` | Label for one radio scale value |
| `.pn-questionnaire__scale-range-track` | Wrapper for the slider input and output (range display only) |
| `.pn-questionnaire__scale-range-label--min` | Min endpoint label (range display only) |
| `.pn-questionnaire__scale-range-label--max` | Max endpoint label (range display only) |
| `.pn-questionnaire__range` | The `<input type="range">` element |
| `.pn-questionnaire__range-value` | The `<output>` element showing the live selected value |
| `.pn-questionnaire__error` | Client-side validation error message (`hidden` by default) |
| `.pn-questionnaire__required-mark` | Asterisk rendered inline after the question text for required questions |
| `.pn-questionnaire__nav` | Previous / Next / Reset button wrapper |
| `.pn-questionnaire__btn` | Base button class |
| `.pn-questionnaire__btn--primary` | Primary action button |
| `.pn-questionnaire__btn--secondary` | Secondary action button (Previous) |
| `.pn-questionnaire__btn--reset` | Reset / Start over button |
| `.pn-questionnaire__result` | Result container |
| `.pn-questionnaire__result--error` | Modifier when no result page matched |
| `.pn-questionnaire__result-headline` | Result headline |
| `.pn-questionnaire__result-score` | Score display |
| `.pn-questionnaire__result-body` | Result body text |
| `.pn-questionnaire__result-cta` | CTA button wrapper |
| `.pn-questionnaire__result-reset` | Reset button on result page |
| `.pn-questionnaire__advice-blocks` | Advice blocks container |
| `.pn-questionnaire__advice-block` | One advice block |
| `.pn-questionnaire__advice-block--always` | Modifier for always-visible blocks |
| `.pn-questionnaire__advice-block--score_range` | Modifier for score-range blocks |
| `.pn-questionnaire__advice-block--specific_answer` | Modifier for specific-answer blocks |
| `.pn-questionnaire__advice-block--scale_range` | Modifier for scale-range blocks |
| `.pn-questionnaire__advice-block-headline` | Advice block headline |
| `.pn-questionnaire__advice-block-body` | Advice block body text |
| `.pn-questionnaire__answer-summary` | Answer summary container |
| `.pn-questionnaire__summary-item` | One question/answer pair in the summary |
| `.pn-questionnaire__summary-question` | Question text in summary |
| `.pn-questionnaire__summary-answers` | Answer text in summary |

### Overriding the progress bar colour

The fill uses `currentColor`, which inherits from the nearest element with an explicit `color` value:

```css
/* In your site package */
.pn-questionnaire__progress-fill {
    color: #your-brand-color;
}
```

---

## 7. JavaScript

The extension ships two JavaScript classes.

### `Questionnaire.js`

Loaded via `f:asset.script` in `Layouts/Default.html` and initialises on `DOMContentLoaded` by looking for `[data-questionnaire]` in the DOM.

| Feature | Description |
|---|---|
| **Scroll to container** | On each page load, scrolls the questionnaire into view when a step is active (`[data-active-step]` present) |
| **Required validation** | Checks for `[data-required]` flag inside the form; validates that at least one non-hidden input is filled/checked before allowing submission |
| **Error message** | Reads the error text from `data-required-error` attribute on the form (set from `locallang.xlf` in the template) |
| **Submit lock** | Disables the submit button after a valid submission to prevent double-posting |

`<input type="range">` always has a value, so required validation passes automatically for scale slider questions.

### `ScaleRange.js`

Loaded **conditionally** via `f:asset.script` only when a scale question renders as a range slider (`scale_display = range`). TYPO3 deduplicates it automatically — safe to use across multiple scale questions on the same page.

| Feature | Description |
|---|---|
| **Live value display** | Keeps the `<output>` element next to the slider in sync with the selected value as the visitor drags |
| **Initial sync** | Syncs on load in case the browser normalises the server-rendered `value` attribute (e.g. out-of-range values) |

The `<output>` element is server-rendered with the correct initial value, so the slider is usable without JavaScript.

All behaviour is **progressive enhancement** — the questionnaire works without JavaScript through standard HTML form submissions.

### Replacing the JavaScript

Point `f:asset.script` in your layout override to a different file, or load your own script that initialises a class targeting `[data-questionnaire]`.

---

## 8. Adding a language

1. Copy `Resources/Private/Language/locallang.xlf` to a new file named `{language-code}.locallang.xlf` in the same directory
2. Add `target-language="{language-code}"` to the `<file>` element
3. Add `<target>` elements with the translated strings for each `<trans-unit>`

TYPO3 picks up the translation automatically based on the active frontend language.

The existing Dutch translation (`nl.locallang.xlf`) can be used as a reference.

---

## 9. Architecture overview

```
QuestionnaireController
    │
    ├── QuestionnaireRepository   → loads the configured questionnaire
    ├── SessionService            → stores / retrieves visitor answers
    ├── ConditionEvaluatorService → filters questions to visible subset
    ├── ProgressService           → calculates step X of Y, prev/next UIDs
    ├── ScoringService            → sums answer option scores
    ├── ResultResolverService     → picks the first matching result page
    │                               and filters advice blocks
    └── DomainRecordResolverService → builds redirect URL for record outcomes
```

### Action flow

```
GET  /page  →  introAction()      render intro screen
GET  /page  →  questionAction()   render current question
POST /page  →  processAction()    store answer, redirect to next or result
GET  /page  →  resultAction()     resolve result, render or redirect
POST /page  →  resetAction()      clear session, redirect to intro
```

All five actions are registered as uncached in `ext_localconf.php`.

---

## 10. Service layer

All business logic lives in `Classes/Service/`. Services are autowired via `Configuration/Services.yaml`.

| Service | Responsibility |
|---|---|
| `SessionService` | Read/write visitor answers via `FrontendUserAuthentication`. Key: `tx_pnquestionnaire`, namespaced per questionnaire UID. |
| `ConditionEvaluatorService` | Iterates all questions, evaluates each condition set, returns visible questions in sort order. |
| `ProgressService` | `calculate()`, `getNextQuestionUid()`, `getPreviousQuestionUid()`. All operate on the visible question array. |
| `ScoringService` | Traverses visible questions → answer options, sums scores. Returns `0.0` when no scores are set. |
| `ResultResolverService` | Iterates result pages top-to-bottom, returns first match. Also provides `filterAdviceBlocks()`. |
| `DomainRecordResolverService` | Uses `UriBuilder` to build the redirect URL for `domain_record` outcomes. Reads TypoScript `domain_record_target` and `foreign_table` from `$GLOBALS['TCA']`. |

### Condition evaluation logic

Each condition has a `condition_type` that determines how it is evaluated:

**`specific_answer`** (default) — passes when the visitor selected a specific answer option for the reference question:

```
result = evaluate(condition_1)
for each condition_2, condition_3, ...:
    if operator == AND → result = result && evaluate(condition)
    if operator == OR  → result = result || evaluate(condition)
```

**`scale_range`** — passes when the visitor's numeric answer for the reference question satisfies the configured operator and threshold:

```
stored_value [operator] scale_value
e.g. 7 >= 5  → true
```

Supported operators: `>=`, `<=`, `>`, `<`, `=`

If the scale question has not been answered yet, the condition returns `false` (the dependent question stays hidden).

A question is shown when the final combined `result` is `true`.

### Result page selection

```
foreach resultPage in questionnaire.resultPages (ordered by sort_order):
    if matches(resultPage, sessionAnswers, totalScore):
        return resultPage  ← first match wins
return null  ← no catch-all configured
```

Trigger type matching:

| Trigger | Match condition |
|---|---|
| `catch_all` | Always |
| `score_range` | `scoreMin <= totalScore <= scoreMax` |
| `specific_answer` | A specific answer option UID appears in `sessionAnswers` |
| `combination` | Both `score_range` AND `specific_answer` match |
| `scale_answer` | The visitor's numeric answer for `triggerQuestion` is within `[triggerScaleMin, triggerScaleMax]` |

---

## 11. Session data format

Answers are stored in the TYPO3 frontend session under the root key `tx_pnquestionnaire`.

```
tx_pnquestionnaire
└── q_{questionnaireUid}
    └── answers
        ├── "{questionUid}" → ["{answerOptionUid}"]          ← single choice
        ├── "{questionUid}" → ["{uid1}", "{uid2}"]           ← multiple choice
        └── "{questionUid}" → ["{scaleValue}"]               ← scale (raw number)
```

All values are stored as strings. The `SessionService` methods handle the conversion.

---

## 12. Extending the extension

### Adding a new question type

1. Add the new type value to the `type` select field in `Configuration/TCA/tx_pnquestionnaire_question.php` (both the `items` array and the `types` array)
2. Add the type constant to `Classes/Domain/Model/Question.php`
3. Create `Resources/Private/Partials/AnswerTypes/YourType.html`
4. Add the mapping in `QuestionnaireController::questionAction()` in `$answerTypePartialMap`

No other changes are required.

### Adding a new outcome type

1. Add the new outcome value to the `outcome_type` select field in the result page TCA
2. Add the outcome constant to `Classes/Domain/Model/ResultPage.php`
3. Handle the new outcome in `QuestionnaireController::handleRedirectOutcome()` or `resultAction()`

### Custom result resolver logic

To replace or extend the result matching logic, create a service that extends or wraps `ResultResolverService` and reconfigure it in `Configuration/Services.yaml`.

### Custom scoring logic

Replace `ScoringService` with your own implementation via `Configuration/Services.yaml` service aliasing. Your service only needs to implement the same method signature:

```php
public function calculateTotal(array $visibleQuestions, array $sessionAnswers): float
```

### Template-only customisation

For purely visual changes — copy any Fluid template from `Resources/Private/` into your site package at the configured override path. TYPO3's path merging ensures your version takes precedence. No PHP changes needed.

