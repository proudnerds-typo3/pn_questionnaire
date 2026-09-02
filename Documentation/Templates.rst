..  _templates:

=================================
Templates, styling and JavaScript
=================================

Overriding templates
====================

The extension uses standard TYPO3 Fluid template path merging. Copy any file
from the extension's :file:`Resources/Private/` tree into your site package at
the path configured in :ref:`configuration-view`, and TYPO3 uses your version
instead. For purely visual changes no PHP is involved at all.

..  code-block:: text
    :caption: EXT:pn_questionnaire/Resources/Private/

    Layouts/
    └── Default.html
    Templates/
    └── Questionnaire/
        ├── Intro.html
        ├── Question.html
        └── Result.html
    Partials/
    ├── AnswerTypes/
    │   ├── SingleChoice.html
    │   ├── MultipleChoice.html
    │   ├── YesNo.html
    │   ├── Scale.html
    │   └── Informational.html
    ├── AdviceBlock.html
    ├── ButtonLabel.html
    └── Progress.html

:file:`ButtonLabel.html` resolves one button label: it renders the FlexForm
override when it is filled and the translated standard text otherwise.
:file:`Progress.html` renders the progress bar and is shared by the question
steps and the result page.

Custom ViewHelper namespace
===========================

The extension provides one custom ViewHelper. Declare its namespace in any
template that uses it:

..  code-block:: html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:pnq="http://typo3.org/ns/ProudNerds/PnQuestionnaire/ViewHelpers"
          data-namespace-typo3-fluid="true">

..  list-table::
    :header-rows: 1

    -   -   ViewHelper
        -   Description
    -   -   `pnq:inArray(haystack: array, needle: value)`
        -   Returns `true` when `needle` exists in `haystack`, comparing as
            strings. Used in the answer type partials to restore the checked
            state of previously given answers

Key template variables
======================

Question.html
-------------

..  list-table::
    :header-rows: 1

    -   -   Variable
        -   Type
        -   Description
    -   -   `questionnaire`
        -   `Questionnaire`
        -   The questionnaire record
    -   -   `question`
        -   `Question`
        -   The current question
    -   -   `progress`
        -   `array{current, total}`
        -   Step X of Y
    -   -   `progressPercentage`
        -   `int`
        -   0–100: the share of the questionnaire behind the visitor, see
            [progress_mode](#confval-typoscript-progress-mode)
    -   -   `prevQuestionUid`
        -   `int
        -   null`
        -   UID of the previous question, `null` on the first
    -   -   `currentAnswer`
        -   `string[]`
        -   Previously stored answer values for this question
    -   -   `hasAnswers`
        -   `bool`
        -   `true` when at least one answer is in the session — shows the Start
            over button
    -   -   `answerTypePartial`
        -   `string`
        -   Partial path, e.g. `AnswerTypes/SingleChoice`
    -   -   `scaleRange`
        -   `int[]`
        -   Array from scale min to max (radio scale only)
    -   -   `scaleDisplay`
        -   `string`
        -   `radio` or `range`, from the question's Scale display field
    -   -   `scaleMiddle`
        -   `int`
        -   Midpoint of the scale range, used as the default slider position

Result.html
-----------

..  list-table::
    :header-rows: 1

    -   -   Variable
        -   Type
        -   Description
    -   -   `questionnaire`
        -   `Questionnaire`
        -   The questionnaire record
    -   -   `resultPage`
        -   `ResultPage
        -   null`
        -   The matched result page; `null` means no catch-all is configured
    -   -   `adviceBlocks`
        -   `AdviceBlock[]`
        -   The visible advice blocks, already filtered
    -   -   `totalScore`
        -   `float`
        -   The calculated score, `0.0` when no scores are set
    -   -   `showScore`
        -   `bool`
        -   From the plugin settings
    -   -   `showAnswerSummary`
        -   `bool`
        -   From the plugin settings
    -   -   `answerSummary`
        -   `array[]`
        -   Pre-built list of question and answers for the summary

Form field naming
=================

Answer inputs must use this exact :html:`name` attribute for Extbase to map them
to :php:`processAction(array $answers)`:

..  code-block:: html

    name="tx_pnquestionnaire_questionnaire[answers][]"

And the hidden question UID field:

..  code-block:: html

    name="tx_pnquestionnaire_questionnaire[questionUid]"

..  _templates-headings:

Heading levels, and why the RTE needs Heading 4 and Heading 5
=============================================================

The result page builds a heading hierarchy from three sources, only two of which
the templates control:

..  list-table::
    :header-rows: 1

    -   -   Level
        -   Rendered by
        -   Content
    -   -   `h2`
        -   `Result.html`
        -   Result page headline
    -   -   `h3`
        -   `AdviceBlock.html`
        -   A group heading (`condition_type = group_header`), or a block title
            that is not in a group
    -   -   `h4`
        -   `AdviceBlock.html`
        -   The title of a block that points at a group heading
    -   -   `h5`
        -   The editor, inside the content field
        -   A sub-heading within a block

That last row is the catch: the deepest level comes out of a rich text field, so
it is the RTE configuration — not the extension — that decides whether an editor
can produce a valid document. An installation therefore needs **both Heading 4
and Heading 5** available in the preset used for
:sql:`tx_pnquestionnaire_advice_block.body_text`:

-   **Heading 5** for a sub-heading inside a *grouped* block, whose title is
    already an ``h4``.
-   **Heading 4** for a sub-heading inside an *ungrouped* block, whose title is
    an ``h3``.

Which one an editor needs depends on whether the block sits in a group, and that
can change after the fact — moving a block into a group shifts its title from
``h3`` to ``h4``, so any sub-heading in its body has to move down a level too.
Offer both and the editor can always pick the level one step below the block
title.

If the preset stops at Heading 3, every sub-heading an editor creates lands at
the same level as — or above — the title of the block it belongs to, which
breaks the document outline (WCAG 1.3.1). The extension deliberately does not
set :php:`richtextConfiguration` on the field to force this: that would override
whatever preset the site assigns through page TSconfig, for every installation.

..  code-block:: yaml
    :caption: Add the missing levels to the preset your site already uses

    editor:
      config:
        heading:
          options:
            - { model: 'paragraph', title: 'Paragraph' }
            - { model: 'heading2', view: 'h2', title: 'Heading 2' }
            - { model: 'heading3', view: 'h3', title: 'Heading 3' }
            - { model: 'heading4', view: 'h4', title: 'Heading 4' }
            - { model: 'heading5', view: 'h5', title: 'Heading 5' }

..  note::
    CKEditor replaces this list as a whole rather than merging it, so when
    overriding it in a separate file, repeat the entries you want to keep. Also
    check that ``h5`` is present in the ``allowTags`` list of the processing
    configuration — otherwise the tag is stripped on save. The default
    :file:`EXT:rte_ckeditor/Configuration/RTE/Processing.yaml` allows ``h1``
    through ``h6``.

..  _templates-styling:

Styling
=======

The extension ships a minimal CSS file
(:file:`Resources/Public/Css/Questionnaire.css`) that only defines the progress
bar. Everything else is unstyled — apply all visual design in your site package.

The progress bar fill uses ``currentColor``, which inherits from the nearest
element with an explicit ``color`` value:

..  code-block:: css
    :caption: In your site package

    .pn-questionnaire__progress-fill {
        color: #your-brand-color;
    }

BEM class reference
-------------------

..  list-table::
    :header-rows: 1

    -   -   Class
        -   Element
    -   -   `.pn-questionnaire`
        -   Root wrapper, also carries the `data-questionnaire` attribute
    -   -   `.pn-questionnaire__intro`
        -   Intro screen container
    -   -   `.pn-questionnaire__intro-text`
        -   Introduction text area
    -   -   `.pn-questionnaire__intro-actions`
        -   Start button wrapper
    -   -   `.pn-questionnaire__intro-footer`
        -   Text below the start button
    -   -   `.pn-questionnaire__step`
        -   Question step container, carries `data-active-step`
    -   -   `.pn-questionnaire__progress`
        -   Progress indicator wrapper, from the `Progress` partial
    -   -   `.pn-questionnaire__progress-bar`
        -   Grey track of the progress bar
    -   -   `.pn-questionnaire__progress-fill`
        -   Coloured fill; width set inline
    -   -   `.pn-questionnaire__progress-text`
        -   "Step X of Y" on a question, "Completed" on the result page
    -   -   `.pn-questionnaire__progress-note`
        -   Dynamic steps note below the bar
    -   -   `.pn-questionnaire__context-content`
        -   Embedded `tt_content` element
    -   -   `.pn-questionnaire__question-text`
        -   Question text area
    -   -   `.pn-questionnaire__help-text`
        -   Help text below the question
    -   -   `.pn-questionnaire__form`
        -   The answer form
    -   -   `.pn-questionnaire__answers`
        -   Fieldset wrapping all answer inputs
    -   -   `.pn-questionnaire__answer-option`
        -   Wrapper for one answer option
    -   -   `.pn-questionnaire__answer-option--radio`
        -   Modifier for radio inputs
    -   -   `.pn-questionnaire__answer-option--checkbox`
        -   Modifier for checkbox inputs
    -   -   `.pn-questionnaire__answer-option--yes-no`
        -   Modifier for yes/no inputs
    -   -   `.pn-questionnaire__radio`
        -   Radio input
    -   -   `.pn-questionnaire__checkbox`
        -   Checkbox input
    -   -   `.pn-questionnaire__answer-label`
        -   Label of an answer option
    -   -   `.pn-questionnaire__scale`
        -   Scale question wrapper
    -   -   `.pn-questionnaire__scale--radio`
        -   Modifier: radio button display
    -   -   `.pn-questionnaire__scale--range`
        -   Modifier: range slider display
    -   -   `.pn-questionnaire__scale-options`
        -   Row of scale radio buttons
    -   -   `.pn-questionnaire__scale-option`
        -   One scale value
    -   -   `.pn-questionnaire__scale-label`
        -   Label of one radio scale value
    -   -   `.pn-questionnaire__scale-range-track`
        -   Wrapper for slider input and output
    -   -   `.pn-questionnaire__scale-range-label--min`
        -   Min endpoint label
    -   -   `.pn-questionnaire__scale-range-label--max`
        -   Max endpoint label
    -   -   `.pn-questionnaire__range`
        -   The range input element
    -   -   `.pn-questionnaire__range-value`
        -   The output element showing the live value
    -   -   `.pn-questionnaire__error`
        -   Client-side validation message, hidden by default
    -   -   `.pn-questionnaire__required-mark`
        -   Asterisk after the text of a required question
    -   -   `.pn-questionnaire__nav`
        -   Previous / Next / Start over wrapper
    -   -   `.pn-questionnaire__btn`
        -   Base button class
    -   -   `.pn-questionnaire__btn--primary`
        -   Primary action button
    -   -   `.pn-questionnaire__btn--secondary`
        -   Secondary action button
    -   -   `.pn-questionnaire__btn--reset`
        -   Start over button
    -   -   `.pn-questionnaire__result`
        -   Result container
    -   -   `.pn-questionnaire__result--error`
        -   Modifier when no result page matched
    -   -   `.pn-questionnaire__result-headline`
        -   Result headline
    -   -   `.pn-questionnaire__result-score`
        -   Score display
    -   -   `.pn-questionnaire__result-body`
        -   Result body text
    -   -   `.pn-questionnaire__result-cta`
        -   Call-to-action button wrapper
    -   -   `.pn-questionnaire__result-reset`
        -   Start over button on the result page
    -   -   `.pn-questionnaire__advice-blocks`
        -   Advice blocks container
    -   -   `.pn-questionnaire__advice-block`
        -   One advice block
    -   -   `.pn-questionnaire__advice-block--always`
        -   Modifier for always-visible blocks
    -   -   `.pn-questionnaire__advice-block--score_range`
        -   Modifier for score-range blocks
    -   -   `.pn-questionnaire__advice-block--specific_answer`
        -   Modifier for specific-answer blocks
    -   -   `.pn-questionnaire__advice-block--scale_range`
        -   Modifier for scale-range blocks
    -   -   `.pn-questionnaire__advice-block-headline`
        -   Advice block headline
    -   -   `.pn-questionnaire__advice-block-body`
        -   Advice block body text
    -   -   `.pn-questionnaire__answer-summary`
        -   Answer summary container
    -   -   `.pn-questionnaire__summary-item`
        -   One question and answer pair
    -   -   `.pn-questionnaire__summary-question`
        -   Question text in the summary
    -   -   `.pn-questionnaire__summary-answers`
        -   Answer text in the summary
    -   -   `.pn-questionnaire__saved-result-copylink`
        -   Copy button next to the retrieval link
    -   -   `.pn-questionnaire__saved-result-status`
        -   Live region reporting the copy result

JavaScript
==========

All behaviour is **progressive enhancement**: the questionnaire works without
JavaScript through standard HTML form submissions.

Questionnaire.js
----------------

Loaded via :html:`<f:asset.script>` in :file:`Layouts/Default.html` and
initialised on ``DOMContentLoaded`` by looking for ``[data-questionnaire]``.

..  list-table::
    :header-rows: 1

    -   -   Feature
        -   Description
    -   -   **Scroll to container**
        -   Scrolls the questionnaire into view when a step is active
    -   -   **Required validation**
        -   Validates that at least one non-hidden input is filled before
            allowing submission
    -   -   **Error message**
        -   Reads the error text from the `data-required-error` attribute on the
            form
    -   -   **Submit lock**
        -   Disables the submit button after a valid submission, preventing
            double posts

A range input always has a value, so required validation passes automatically
for scale slider questions.

ScaleRange.js
-------------

Loaded **conditionally**, only when a scale question renders as a range slider.
TYPO3 deduplicates the asset, so it is safe with multiple scale questions on one
page.

..  list-table::
    :header-rows: 1

    -   -   Feature
        -   Description
    -   -   **Live value display**
        -   Keeps the output element next to the slider in sync while the
            visitor drags
    -   -   **Initial sync**
        -   Syncs on load in case the browser normalises the server-rendered
            value

The output element is server-rendered with the correct initial value, so the
slider is usable without JavaScript.

To replace the JavaScript, point :html:`<f:asset.script>` in your layout
override at a different file, or load your own script that initialises a class
targeting ``[data-questionnaire]``.

..  _templates-language:

Adding a language
=================

#.  Copy :file:`Resources/Private/Language/locallang.xlf` to a new file named
    :samp:`{language-code}.locallang.xlf` in the same directory.
#.  Add :xml:`target-language="{language-code}"` to the :xml:`<file>` element.
#.  Add a :xml:`<target>` element with the translation for each
    :xml:`<trans-unit>`.

TYPO3 picks the translation up automatically, based on the active frontend
language. The Dutch translation (:file:`nl.locallang.xlf`) can serve as a
reference; German (:file:`de.locallang.xlf`) is shipped as well.

The backend labels live in :file:`locallang_be.xlf` and follow the same pattern.
