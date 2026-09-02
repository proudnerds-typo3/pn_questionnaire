..  _introduction:

============
Introduction
============

What it does
============

`pn_questionnaire` lets editors build multi-step questionnaires entirely in the
TYPO3 backend. Every part of a questionnaire is a record: the questionnaire
itself, its questions, the answer options, the visibility conditions between
them, the result pages and the advice blocks on those pages. Answers decide
which questions appear next and which advice the visitor ends up with.

Typical use cases:

-   Step-by-step **decision trees** that route a visitor to the right service
-   **Scored self-assessments** — health checks, sustainability scans, skills tests
-   **Guided quizzes** with a personalised result at the end
-   **Multi-path flows** where earlier answers determine which questions follow

Features
========

-   Five question types: single choice, multiple choice, yes/no, scale (radio
    buttons or a range slider) and informational screens without input
-   Visibility conditions per question, on a specific answer or on the value of
    a scale question, combined with AND/OR
-   Result pages selected by score range, by a specific answer, by a scale
    answer, by a combination, or as a catch-all
-   Advice blocks shown conditionally per answer or score, with the option to
    invert a condition so a block appears when an answer was *not* given
-   Group headings that give a long result a nested heading structure; a heading
    appears only when at least one block in its group is visible
-   Outcomes rendered inline, or as a redirect to a page, an external URL or the
    detail view of a record from another extension
-   Anonymous result storage with a retrieval link, and a mail-to-self form with
    a rate limit
-   Usage counters per questionnaire for starts and completions
-   A frontend aimed at WCAG 2.2 AA, working without JavaScript

Integration
===========

-   Configured per plugin instance through a FlexForm; TypoScript sets the
    site-wide defaults
-   Fluid templates, partials and layouts overridable from a site package
-   BEM class names on semantic markup — no design imposed
-   English, Dutch and German labels included

..  _introduction-compatibility:

Compatibility
=============

..  list-table::
    :header-rows: 1

    -   -   TYPO3
        -   PHP
        -   Extension
    -   -   14.3
        -   8.2 – 8.4
        -   1.0 and up
    -   -   13.4
        -   8.2 – 8.4
        -   1.0 and up
    -   -   12.4
        -   8.2 – 8.4
        -   1.0 and up

One codebase serves all three versions; each is verified with a full
run-through. Privacy is a design constraint rather than a feature: a stored
result holds no name, no IP address and no e-mail address, not even hashed.
