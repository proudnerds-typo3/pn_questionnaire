..  _architecture:

============
Architecture
============

How the parts fit together
==========================

..  code-block:: text

    QuestionnaireController
        │
        ├── QuestionnaireRepository     → loads the configured questionnaire
        ├── SessionService              → stores / retrieves visitor answers
        ├── ConditionEvaluatorService   → filters questions to the visible subset
        ├── ProgressService             → calculates step X of Y, prev/next uids
        ├── ScoringService              → sums answer option scores
        ├── ResultResolverService       → picks the first matching result page
        │                                 and filters the advice blocks
        └── DomainRecordResolverService → builds the redirect URL for record outcomes

Action flow
-----------

..  code-block:: text

    GET  /page  →  introAction()      render intro screen
    GET  /page  →  questionAction()   render current question
    POST /page  →  processAction()    store answer, redirect to next or result
    GET  /page  →  resultAction()     resolve result, render or redirect
    POST /page  →  resetAction()      clear session, redirect to intro

All five actions are registered as uncached in :file:`ext_localconf.php`.

Service layer
=============

All business logic lives in :file:`Classes/Service/`. The services are autowired
through :file:`Configuration/Services.yaml`.

..  list-table::
    :header-rows: 1

    -   -   Service
        -   Responsibility
    -   -   `SessionService`
        -   Reads and writes visitor answers through
            `FrontendUserAuthentication`. Key `tx_pnquestionnaire`, namespaced
            per questionnaire uid
    -   -   `ConditionEvaluatorService`
        -   Iterates all questions, evaluates each condition set, returns the
            visible questions in sort order
    -   -   `ProgressService`
        -   `calculate()`, `getNextQuestionUid()`, `getPreviousQuestionUid()`,
            all operating on the visible questions
    -   -   `ScoringService`
        -   Traverses visible questions and their answer options, sums the
            scores. Returns `0.0` when no scores are set
    -   -   `ResultResolverService`
        -   Iterates result pages top to bottom, returns the first match. Also
            provides `filterAdviceBlocks()`
    -   -   `DomainRecordResolverService`
        -   Builds the redirect URL for `domain_record` outcomes with
            `UriBuilder`, reading the TypoScript target and `foreign_table` from
            `$GLOBALS['TCA']`
    -   -   `ResultStorageService`
        -   Writes and retrieves a stored result, and generates its token
    -   -   `ResultMailService`
        -   Composes and sends the result mail, HTML and plain text
    -   -   `MailRateLimitService`
        -   Wraps the Symfony rate limiter for the mail form
    -   -   `StatisticsService`
        -   Increments the start and completion counters

..  _architecture-conditions:

Condition evaluation
====================

Each condition has a ``condition_type`` that determines how it is evaluated.

``specific_answer`` (default) passes when the visitor selected a specific answer
option for the reference question. Multiple conditions are folded together in
list order:

..  code-block:: text

    result = evaluate(condition_1)
    for each condition_2, condition_3, ...:
        if operator == AND → result = result && evaluate(condition)
        if operator == OR  → result = result || evaluate(condition)

``scale_range`` passes when the numeric answer for the reference question
satisfies the configured operator and threshold:

..  code-block:: text

    stored_value [operator] scale_value
    e.g. 7 >= 5  → true

Supported operators are ``>=``, ``<=``, ``>``, ``<`` and ``=``. When the scale
question has not been answered yet the condition returns ``false``, so the
dependent question stays hidden.

A question is shown when the final combined result is ``true``.

Result page selection
=====================

..  code-block:: text

    foreach resultPage in questionnaire.resultPages (ordered by sort_order):
        if matches(resultPage, sessionAnswers, totalScore):
            return resultPage  ← first match wins
    return null  ← no catch-all configured

..  list-table::
    :header-rows: 1

    -   -   Trigger
        -   Match condition
    -   -   `catch_all`
        -   Always
    -   -   `score_range`
        -   `scoreMin <= totalScore <= scoreMax`
    -   -   `specific_answer`
        -   A specific answer option uid appears in the session answers
    -   -   `combination`
        -   Both `score_range` and `specific_answer` match
    -   -   `scale_answer`
        -   The numeric answer for the trigger question is within
            `[triggerScaleMin, triggerScaleMax]`

Session data format
===================

Answers are stored in the TYPO3 frontend session under the root key
``tx_pnquestionnaire``.

..  code-block:: text

    tx_pnquestionnaire
    └── q_{questionnaireUid}
        └── answers
            ├── "{questionUid}" → ["{answerOptionUid}"]   ← single choice
            ├── "{questionUid}" → ["{uid1}", "{uid2}"]    ← multiple choice
            └── "{questionUid}" → ["{scaleValue}"]        ← scale (raw number)

All values are stored as strings; the :php:`SessionService` methods handle the
conversion.

Extending the extension
=======================

Adding a question type
----------------------

#.  Add the new value to the ``type`` select field in
    :file:`Configuration/TCA/tx_pnquestionnaire_question.php` — both the
    ``items`` array and the ``types`` array.
#.  Add the type constant to :file:`Classes/Domain/Model/Question.php`.
#.  Create :file:`Resources/Private/Partials/AnswerTypes/YourType.html`.
#.  Add the mapping to ``$answerTypePartialMap`` in
    :php:`QuestionnaireController::questionAction()`.

No other changes are required.

Adding an outcome type
----------------------

#.  Add the new value to the ``outcome_type`` select field in the result page
    TCA.
#.  Add the outcome constant to :file:`Classes/Domain/Model/ResultPage.php`.
#.  Handle it in :php:`QuestionnaireController::handleRedirectOutcome()` or
    :php:`resultAction()`.

Replacing a service
-------------------

To replace or extend the result matching, create a service that extends or wraps
:php:`ResultResolverService` and reconfigure it in
:file:`Configuration/Services.yaml`. The same applies to scoring: your service
only needs the same method signature.

..  code-block:: php

    public function calculateTotal(array $visibleQuestions, array $sessionAnswers): float

For purely visual changes no PHP is needed at all — copy the Fluid template into
your site package, see :ref:`templates`.
