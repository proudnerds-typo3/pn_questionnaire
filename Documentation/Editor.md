# Editor Guide — pn_questionnaire

This guide explains how to build and manage questionnaires in the TYPO3 backend.
No technical knowledge is required.

---

## Table of Contents

1. [Concepts](#1-concepts)
2. [Step 1 — Create a Questionnaire record](#2-step-1--create-a-questionnaire-record)
3. [Step 2 — Place the plugin on a page](#3-step-2--place-the-plugin-on-a-page)
4. [Step 3 — Add questions](#4-step-3--add-questions)
5. [Step 4 — Add answer options](#5-step-4--add-answer-options)
6. [Step 5 — Add conditions (branching)](#6-step-5--add-conditions-branching)
7. [Step 6 — Add result pages](#7-step-6--add-result-pages)
8. [Step 7 — Add advice blocks](#8-step-7--add-advice-blocks)
9. [Plugin settings (FlexForm)](#9-plugin-settings-flexform)
10. [Question types reference](#10-question-types-reference)
11. [Result page trigger types](#11-result-page-trigger-types)
12. [Result page outcome types](#12-result-page-outcome-types)
13. [Tips and rules of thumb](#13-tips-and-rules-of-thumb)

---

## 1. Concepts

Before building a questionnaire it helps to understand the main building blocks.

| Term | What it is |
|---|---|
| **Questionnaire** | The top-level record that holds everything — questions, settings and result pages |
| **Question** | A single step shown to the visitor (one per screen) |
| **Answer option** | A selectable choice within a question |
| **Condition** | A rule that shows or skips a question based on a previous answer |
| **Result page** | What happens when the visitor finishes — shown inline or as a redirect |
| **Advice block** | An optional content section within an inline result, conditionally visible |
| **Score** | A numeric value on an answer option, summed into a total at the end |

---

## 2. Step 1 — Create a Questionnaire record

1. Open the **List** module and navigate to the folder where questionnaire records should be stored (ask your developer to set up a dedicated sysfolder)
2. Create a new **Questionnaire** record
3. Fill in the **Title** — this is an internal label, never shown to the visitor
4. Optionally add an **Introduction text** — this appears on the intro screen before the first question (if the intro screen is enabled in the plugin settings)
5. Save the record

---

## 3. Step 2 — Place the plugin on a page

1. Edit the page where the questionnaire should appear
2. Add a new content element and select **Questionnaire / Decision Tree** from the plugins tab
3. In the **Configuration** tab, select the Questionnaire record you just created in the **Questionnaire** field
4. Adjust the remaining FlexForm settings if needed — see [§9](#9-plugin-settings-flexform)
5. Save

> **One questionnaire, multiple pages**
> The same questionnaire record can be placed on multiple pages. Each placement can have different display settings (show score, show answer summary, etc.) because those settings are stored on the plugin instance, not the questionnaire record itself.

---

## 4. Step 3 — Add questions

Inside the Questionnaire record, open the **Questions** tab and click **Create new Question**.

| Field | Description |
|---|---|
| **Question** | The question text shown to the visitor (supports rich text) |
| **Help text** | Optional short instruction below the question |
| **Context content element** | Optional link to an existing `tt_content` record — useful for adding images, video or formatted text as context |
| **Type** | The answer type — see [§10](#10-question-types-reference) |
| **Required** | Whether the visitor must answer before proceeding |
| **Scale minimum / maximum** | Lower and upper bound of the numeric scale (Scale / Rating type only) |
| **Scale display** | How the scale is shown: **Radio buttons** (individual numbered options) or **Range slider** (draggable slider, default). Scale / Rating type only |

Questions are shown in the order they appear in the list. Drag and drop to reorder.

> **Informational questions**
> Use the **Informational** type to insert a screen with text or media but no user input — for example, an intermediate explanation or a section break. The visitor just clicks Next to continue.

---

## 5. Step 4 — Add answer options

For **Single choice**, **Multiple choice** and **Yes / No** questions, open the **Answer Options** tab of the question and add one option per selectable answer.

| Field | Description |
|---|---|
| **Label** | The text shown to the visitor |
| **Value** | Internal identifier — used in conditions and result triggers. Use something descriptive, e.g. `employed`, `yes`, `option_a` |
| **Score** | Optional numeric score (positive or negative, decimal allowed) — only relevant when you use score-based results |

Drag and drop answer options to reorder them.

---

## 6. Step 5 — Add conditions (branching)

A condition makes a question **only visible when a specific previous question was answered in a specific way**. This is how you build a decision tree.

There are two condition types:

### Condition type: Specific answer

1. Open the **Visibility Conditions** tab of the question that should be conditionally shown
2. Click **Create new Condition**
3. Set **Condition type** to **Specific answer**
4. Select the **Reference question** — the earlier question whose answer determines visibility
5. Select the **Reference answer** — the specific answer option that must have been chosen
6. Save

### Condition type: Scale value

Use this when the reference question is a **Scale / Rating** question and you want to show or hide based on the numeric value the visitor selected.

1. Open the **Visibility Conditions** tab
2. Click **Create new Condition**
3. Set **Condition type** to **Scale value**
4. Select the **Reference question** (must be a scale question)
5. Choose an **Operator**: `>=`, `<=`, `>`, `<`, or `=`
6. Enter a **Value** to compare against
7. Save

**Example:** Show a follow-up question only if the visitor rated satisfaction **>= 8**.

### Multiple conditions

You can add more than one condition per question. Each condition has an **Operator** field:

| Operator | Meaning |
|---|---|
| **AND** | This condition must also pass (all AND conditions must be met) |
| **OR** | Passing this condition is enough on its own |

Conditions are evaluated in the order they appear in the list (top to bottom, by sort order).

### Rules

- Conditions can only reference questions that appear **earlier** in the questionnaire. It is your responsibility to ensure this.
- A question with **no conditions** is always shown.
- Skipped questions do not count toward progress or score.
- For **Scale value** conditions: if the referenced question has not been answered yet, the condition fails and the dependent question stays hidden.

### Example

```
Q1: What is your situation?
    → "Employed"           → show Q2a (work questions)
    → "Self-employed"      → show Q2b (entrepreneur questions)
    → "Looking for work"   → show Q2c (job-seeker questions)
```

Q2a has a condition: type = Specific answer, reference question = Q1, reference answer = "Employed".
Q2b has a condition: type = Specific answer, reference question = Q1, reference answer = "Self-employed".
Q2c has a condition: type = Specific answer, reference question = Q1, reference answer = "Looking for work".

---

## 7. Step 6 — Add result pages

Open the **Result Pages** tab of the Questionnaire and click **Create new Result Page**.

Result pages are evaluated **top to bottom — the first match wins**. Always put specific conditions first and a **catch-all** result last as a fallback.

Each result page has two sections:

### Trigger — when does this result apply?

See [§11](#11-result-page-trigger-types) for all trigger types.

### Outcome — what happens when it applies?

See [§12](#12-result-page-outcome-types) for all outcome types.

---

## 8. Step 7 — Add advice blocks

Advice blocks are **conditional content sections** within an inline result page. They let you show personalised advice depending on the visitor's score or a specific answer — within the same result page.

Only available when **Outcome type** is set to **Inline**.

| Field | Description |
|---|---|
| **Condition type** | When to show this block: Always / Score range / Specific answer / Scale answer range / Group heading above other blocks |
| **Score min / max** | Visible score range (only for Score range condition) |
| **Trigger answer** | The answer that must have been given (only for Specific answer condition) |
| **Invert condition** | Shows the block when the chosen answer was **not** given — see [Inverting a condition](#inverting-a-condition) |
| **Scale question** | The scale question whose value is checked (only for Scale answer range condition) |
| **Minimum value / Maximum value** | Inclusive range the scale answer must fall within (only for Scale answer range condition) |
| **Part of group** | Places this block under a group heading — see [Grouping blocks under a heading](#grouping-blocks-under-a-heading) |
| **Headline** | Block heading |
| **Body text** | Block content (rich text) |

Advice blocks are shown in the order they appear in the list. Only blocks whose condition is met are rendered.

> **Use case example**
> Result page for all visitors with score 10–20. Within it:
> — Advice block A (always shown): general recommendation
> — Advice block B (specific answer: "I work outdoors"): specific outdoor tip
> — Advice block C (score range 15–20): extra warning for higher scores
> — Advice block D (scale answer range, Q3 satisfaction >= 8): positive reinforcement message

### Inverting a condition

**Invert condition** turns a *Specific answer* condition around: the block appears when the visitor did **not** choose that answer.

This is useful for advice that only applies to people who skipped something. Take a question about organ donation with one option "I have already registered my choice". Attach a block to that option, tick **Invert condition**, and everyone who did *not* register gets the reminder — while the people who did are spared a message that does not apply to them.

> **Watch out: an unanswered question counts as "not given".**
> If the question is optional, or the visitor never reached it, the answer is absent — so an inverted block will appear. Whether that is what you want depends on the text. A reminder ("don't forget to arrange this") is fine for someone who skipped the question. A statement that assumes a situation ("since you have not arranged this yet") is not. Make the question **required** if you need the distinction to be reliable.

### Grouping blocks under a heading

A long result page reads better in sections. Set **Condition type** to **Group heading above other blocks** to turn a block into a heading, then set **Part of group** on every block that belongs underneath it.

What to keep in mind:

- A group heading has **no condition of its own**. It appears automatically when at least one block in its group is visible, and disappears when none are. You never have to maintain the heading's visibility by hand.
- A group heading has **no body text** — only a headline.
- The headline of a grouped block moves **one heading level deeper** than an ungrouped one. That matters for the rich text in the body: sub-headings inside a grouped block belong on **Heading 5**, not Heading 4. Using the wrong level skips a level in the document outline, which screen readers and search engines both read as an error.
- Blocks that are not part of any group keep their normal level and can sit before, between or after the groups.

---

## 9. Plugin settings (FlexForm)

These settings live on the **plugin instance** (the content element on the page), not the questionnaire record. This means the same questionnaire can behave differently on different pages.

They are split over two tabs.

### Tab: Settings

| Setting | Description | Default |
|---|---|---|
| **Questionnaire** | Which questionnaire record to display | _(required)_ |
| **Show introduction screen** | Show a dedicated intro screen with a Start button before Q1 | On |
| **Show score on result** | Show the calculated score on an inline result page | Off |
| **Show answer summary** | Show a recap of the visitor's given answers on the result page | Off |
| **Count usage** | Keep a tally on the questionnaire record of how often it was started and completed | Off |

### Tab: Storing and mailing the result

| Setting | Description | Default |
|---|---|---|
| **Store the result** | Lets the visitor keep the result and return to it later through a link | Off |
| **Folder for the stored results** | Where the stored results are filed. Required as soon as storing is on | _(falls back to the current page)_ |
| **Keep the result for (days)** | After this many days a stored result is removed | 365 |
| **Allow mailing the result** | Shows a form on the result page where the visitor can mail the result to themselves | Off |
| **Link to the privacy statement** | The page explaining what is stored and for how long. Shown next to the mail form | _(empty)_ |
| **Sender address of the mail** / **Sender name** | Overrides the installation's default sender for this plugin | _(installation default)_ |
| **Opening text of the mail** / **Closing text** | Replaces the standard opening and closing lines of the mail | _(standard text)_ |
| **Switch off the send limit** | Testing only — see the warning below | Off |

Leaving a field empty is meaningful here: the extension then falls back to its own standard text or to the installation's setting. You only fill in what you want to deviate from.

### Keeping and sharing the result

With **Store the result** on, the result page gains a link the visitor can save or share. Opening it later brings back the same result, without a login and without the visitor's session.

Nothing identifying is stored: **no name, no IP address, no e-mail address, not even in hashed form.** What is stored is the given answers, the score and a random code that appears in the link. Someone who does not have the link cannot find the result, and the answers cannot be traced back to a person.

Two consequences worth knowing:

- The result disappears once the retention period expires. Say so in the text around the link if visitors are likely to rely on it.
- The link is the only key. A visitor who loses it cannot recover the result, and neither can an administrator.

Point **Link to the privacy statement** at the page where your organisation explains this. The extension only links to it — the text itself is yours to write, and it should mention the retention period and that no personal data is stored.

### Mailing the result

With **Allow mailing the result** on, the result page shows a form asking for an e-mail address. The visitor receives the full result plus the retrieval link.

The address is used to send that one mail and is **not stored** — not in the database, not in a log, not on the stored result.

The mail uses the layout of your TYPO3 installation and is sent as both HTML and plain text. **Opening text of the mail** and **Closing text** let you replace the standard lines; leave them empty to keep the defaults.

> **Leave "Switch off the send limit" off on a live site.**
> The limit is what stops the form from being used to send mail to other people's addresses in bulk. Switching it off is meant for testing an installation, where repeated attempts would otherwise be blocked.

---

## 10. Question types reference

| Type | Input | Use for |
|---|---|---|
| **Single choice** | Radio buttons — one answer | Most questions |
| **Multiple choice** | Checkboxes — one or more answers | "Select all that apply" |
| **Yes / No** | Two radio buttons (Yes and No — you define the labels) | Binary questions |
| **Scale / Rating** | Numbered scale (min–max, e.g. 1–10) — displayed as radio buttons or a range slider | Assessments, NPS, ratings |
| **Informational** | No input — Next button only | Intermediate explanation, section break |

### Scale display

Scale questions have a **Scale display** field that controls how the input is rendered:

| Option | Description |
|---|---|
| **Range slider** (default) | A draggable `<input type="range">` showing the selected value live. Defaults to the midpoint of the range when no previous answer is stored. |
| **Radio buttons** | Individual numbered radio inputs, one per step in the range |

> Scale and informational questions are score-neutral — they never add to the total score.

---

## 11. Result page trigger types

| Trigger type | When it matches |
|---|---|
| **Catch-all** | Always — use as the last result page as a fallback |
| **Score range** | When the visitor's total score is between Score min and Score max (inclusive) |
| **Specific answer** | When the visitor selected a specific answer option (anywhere in the questionnaire) |
| **Combination** | When both the score range AND the specific answer match |
| **Scale answer range** | When the visitor's numeric answer for a specific scale question falls within the configured min–max range (inclusive) |

When **Scale answer range** is selected, three extra fields appear:

| Field | Description |
|---|---|
| **Scale question** | The scale question whose answer is checked (only scale questions are listed) |
| **Minimum value** | Lower bound (inclusive) |
| **Maximum value** | Upper bound (inclusive) |

> **Order matters.** Result pages are evaluated top to bottom. The first match wins. Place the most specific triggers first and catch-all last.

---

## 12. Result page outcome types

| Outcome type | What happens                                                                                                                                                                                         |
|---|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Inline** | Result content (headline, body text, advice blocks, CTA) is shown within the questionnaire on the same page                                                                                          |
| **Internal page** | Visitor is redirected to a TYPO3 page you select from the page tree                                                                                                                                  |
| **External URL** | Visitor is redirected to an external URL you enter                                                                                                                                                   |
| **Domain record** | Visitor is sent to the detail view of a specific record from another extension (e.g. news article, event, vacancy, ad or activity). Requires developer configuration in TypoScript and overrides TCA |

> **Tip — redirects and visitor expectations**
> When the outcome is **Internal page**, **External URL** or **Domain record**, the visitor is silently redirected away from the questionnaire page the moment they submit their last answer. They may not understand why they suddenly land on a different page.
>
> Consider adding an **Informational** question as the very last step before the result. Use it to tell the visitor what will happen next — for example:
>
> *"Based on your answers we have found a suitable match for you. Click Next to view your result."*
>
> This gives the visitor context and makes the redirect feel intentional rather than unexpected.

### Inline outcome fields

| Field | Description |
|---|---|
| **Headline** | Result headline |
| **Body text** | Main result text (rich text — you can add links here) |
| **CTA label** | Call-to-action button text (optional) |
| **CTA link** | Call-to-action button target URL (optional) |
| **Advice blocks** | Conditional content sections — see [§8](#8-step-7--add-advice-blocks) |

> **Multiple links in a result?**
> The result page supports one CTA button. If you need multiple links, add them as hyperlinks inside the Body text field.

---

## 13. Tips and rules of thumb

### Questionnaire structure
- Start with a **sketch on paper** of the question flow and the possible outcomes before building in the backend
- Use descriptive **internal titles** on questionnaire and result page records — editors will thank you later
- Store questionnaire records in a **dedicated sysfolder** to keep the list module tidy

### Conditions
- Only reference questions that appear **above** the current question in the list
- Test the branching by going through the questionnaire as a visitor — condition mistakes are easy to miss in the backend
- Use the **Start over** button during testing to reset the session — it only appears after the first answer has been submitted

### Result pages
- Always add a **catch-all** result as the last entry — without it, visitors who don't match any specific trigger will see an error message
- For score-based questionnaires, make sure your score ranges **cover all possible totals** with no gaps

### Scoring
- Scores can be **positive or negative** (e.g. +2 for a healthy choice, -1 for a risk factor)
- Score is only displayed to the visitor if **Show score on result** is enabled in the plugin settings
- Advice blocks with score-range conditions work regardless of whether the score is displayed

### Reuse
- The same questionnaire record can be placed on **multiple pages** — useful for embedding the same tool in different contexts
- Each placement has its own FlexForm settings, so you can show the score on one page but not another

