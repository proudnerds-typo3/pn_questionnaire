..  _configuration:

=============
Configuration
=============

The extension is configured on two levels:

..  list-table::
    :header-rows: 1

    -   -   Level
        -   Where
        -   Scope
    -   -   FlexForm
        -   *set in the backend, on the plugin*
        -   Per plugin instance
    -   -   TypoScript
        -   `Configuration/TypoScript/`
        -   Per site or page tree

For every setting that exists on both levels, **the FlexForm value wins** over
the TypoScript default. An empty FlexForm field falls back to TypoScript, and
where TypoScript is empty too, to the installation's own setting. Leaving a
field empty is therefore meaningful: it means "use what is configured one level
up".

All TypoScript lives under
:typoscript:`plugin.tx_pnquestionnaire_questionnaire`.

..  _configuration-view:

View paths
==========

Override the template paths per site package using the constants. You only need
to place the files you want to override; TYPO3 merges these with the extension
defaults using indexed path arrays, where index ``1`` overrides index ``0``.

..  code-block:: typoscript

    plugin.tx_pnquestionnaire_questionnaire {
      view {
        templateRootPath = EXT:your_sitepackage/Resources/Private/Templates/PnQuestionnaire/
        partialRootPath  = EXT:your_sitepackage/Resources/Private/Partials/PnQuestionnaire/
        layoutRootPath   = EXT:your_sitepackage/Resources/Private/Layouts/PnQuestionnaire/
      }
    }

..  confval:: view.templateRootPath
    :name: typoscript-template-root-path
    :type: string
    :Default: EXT:pn_questionnaire/Resources/Private/Templates/

    Where the extension looks for :file:`Questionnaire/Intro.html`,
    :file:`Question.html` and :file:`Result.html`. See :ref:`templates`.

..  confval:: view.partialRootPath
    :name: typoscript-partial-root-path
    :type: string
    :Default: EXT:pn_questionnaire/Resources/Private/Partials/

    Where the answer type partials, the advice block, the progress bar and the
    button label partial are looked up.

..  confval:: view.layoutRootPath
    :name: typoscript-layout-root-path
    :type: string
    :Default: EXT:pn_questionnaire/Resources/Private/Layouts/

    Where :file:`Default.html` is looked up. That layout also loads the
    JavaScript, so an override has to keep the asset tags or replace them.

..  _configuration-typoscript-defaults:

Plugin defaults
===============

These settings have a FlexForm counterpart. The value here is what a plugin
instance uses as long as its own field is untouched.

..  code-block:: typoscript

    plugin.tx_pnquestionnaire_questionnaire.settings {
      show_score          = 0
      show_answer_summary = 0
      introduction_screen = 1
    }

..  confval:: settings.show_score
    :name: typoscript-show-score
    :type: boolean
    :Default: 0

    Show the calculated score on an inline result page. Overridden by
    :ref:`confval-flexform-show-score`.

..  confval:: settings.show_answer_summary
    :name: typoscript-show-answer-summary
    :type: boolean
    :Default: 0

    Show a recap of the given answers on the result page. Overridden by
    :ref:`confval-flexform-show-answer-summary`.

..  confval:: settings.introduction_screen
    :name: typoscript-introduction-screen
    :type: boolean
    :Default: 1

    Show a dedicated introduction screen with a start button before the first
    question. Overridden by :ref:`confval-flexform-introduction-screen`.

..  _configuration-progress:

What the progress bar measures
==============================

..  confval:: settings.progress_mode
    :name: typoscript-progress-mode
    :type: string
    :Default: completed

    Two conventions are in common use for a questionnaire, and the site picks
    one. There is deliberately no FlexForm counterpart: this is a design
    decision for the whole site, and having one questionnaire count differently
    from the next would only confuse visitors.

    ``completed``
        Counts the questions already answered, so the bar never claims to be
        finished while there are still answers to give, and the result page is
        what completes it.

    ``position``
        Follows the step number, the convention many questionnaires use, at the
        cost of a bar that is already full on the closing question.

..  list-table::
    :header-rows: 1

    -   -   Value
        -   Question 1 of 5
        -   Question 5 of 5
        -   Result page
    -   -   `completed` (default)
        -   0%
        -   80%
        -   100%
    -   -   `position`
        -   20%
        -   100%
        -   100%

The counter above the bar reads "step X of Y" either way — that number is the
visitor's position and does not change with this setting. Under ``completed`` it
therefore says "step 1 of 5" next to an empty bar, which is intentional: the
step is the one being answered, the bar is what lies behind it.

..  _configuration-typoscript-saved:

Stored results and mail
=======================

Fallbacks for the fields on the :guilabel:`Storing and mailing the result` tab
of the plugin. Storing and mailing themselves are switched on per plugin
instance only; there is no TypoScript equivalent for the two on/off fields, by
design — a site-wide default that starts writing visitor data would be the wrong
default.

..  code-block:: typoscript

    plugin.tx_pnquestionnaire_questionnaire.settings {
      db_save_result_storage_pid   =
      db_save_result_lifetime_days = 365
      mail_from_address            =
      mail_from_name               =
    }

..  confval:: settings.db_save_result_storage_pid
    :name: typoscript-storage-pid
    :type: page uid
    :Default: *(empty)*

    The folder stored results are written to. Empty means the plugin's own page
    is used, and a warning is written to the log — the FlexForm field is the
    intended place to set this. Overridden by
    :ref:`confval-flexform-storage-pid`.

..  confval:: settings.db_save_result_lifetime_days
    :name: typoscript-lifetime-days
    :type: int
    :Default: 365

    How many days a stored result stays available. Applied when the result is
    stored, by writing the :sql:`expires` timestamp; changing it afterwards does
    not move existing rows. Overridden by
    :ref:`confval-flexform-lifetime-days`.

..  confval:: settings.mail_from_address
    :name: typoscript-mail-from-address
    :type: string
    :Default: *(empty)*

    Sender address of the result mail. Empty falls back to
    :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`.
    Overridden by :ref:`confval-flexform-mail-from-address`.

..  confval:: settings.mail_from_name
    :name: typoscript-mail-from-name
    :type: string
    :Default: *(empty)*

    Sender name of the result mail. Empty falls back to
    :php:`defaultMailFromName` of the installation.

..  _configuration-domain-record:

Domain record outcomes
======================

..  note::
    "Domain record" here refers to a **domain model record** in the Extbase
    sense — a news article, an activity, an ad — not a domain name or HTTP
    address.

The :guilabel:`Domain record` outcome type redirects the visitor to the detail
view of a record from another extension. One target can be configured per site,
and it takes both TypoScript and a TCA override.

Step 1 — TypoScript
-------------------

..  code-block:: typoscript

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

..  confval:: settings.domain_record_target
    :name: typoscript-domain-record-target
    :type: array

    The single record type that may be used as a ``domain_record`` outcome. All
    six keys are required; the service builds the redirect URL from them with
    :php:`UriBuilder`. Leave the whole block out when no result page uses this
    outcome type.

Step 2 — TCA override
---------------------

The extension ships :sql:`record_uid` as a plain number, because it cannot know
which table your site links to. The site package names that table, and turns the
field into a record browser while it is at it:

..  code-block:: php
    :caption: EXT:your_sitepackage/Configuration/TCA/Overrides/tx_pnquestionnaire_result_page.php

    $config = &$GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config'];
    $config['type'] = 'group';
    $config['allowed'] = 'tx_news_domain_model_news';
    $config['foreign_table'] = 'tx_news_domain_model_news';
    $config['size'] = 1;

:php:`type` and :php:`allowed` give you the record browser; :php:`foreign_table`
is what Extbase needs to resolve the record. Both table names must match the
TypoScript target — the service reads :php:`foreign_table` from
:php:`$GLOBALS['TCA']` at runtime, so there is a single source of truth.

..  warning::
    Do not leave :php:`allowed` empty on a :php:`group` field. TYPO3 throws
    :php:`RuntimeException` 1482250512 while compiling the form, which takes
    down the entire result page editing form — not just this one field — even
    for result pages that use a different outcome type. That is why the shipped
    default is a number and not an unconfigured group.

..  _configuration-flexform:

Plugin settings (FlexForm)
==========================

These live on the plugin instance, so the same questionnaire record can behave
differently on different pages. The Editor Guide describes what they do in
practice; the reference below names the fields.

Tab: Settings
-------------

..  confval:: settings.questionnaire
    :name: flexform-questionnaire
    :type: record uid
    :Required: true

    Backend label: :guilabel:`Questionnaire`.

    Which questionnaire record to display. The record is looked up regardless of
    the folder it is stored in.

..  confval:: settings.introduction_screen
    :name: flexform-introduction-screen
    :type: boolean
    :Default: *(from TypoScript: 1)*

    Backend label: :guilabel:`Show introduction screen`.

    Show the introduction screen with a start button before the first question.
    Without it, the visitor lands on question one straight away.

..  confval:: settings.show_score
    :name: flexform-show-score
    :type: boolean
    :Default: *(from TypoScript: 0)*

    Backend label: :guilabel:`Show score on result`.

    Show the calculated score on an inline result page. Advice blocks with a
    score condition work whether or not the score is shown.

..  confval:: settings.show_answer_summary
    :name: flexform-show-answer-summary
    :type: boolean
    :Default: *(from TypoScript: 0)*

    Backend label: :guilabel:`Show answer summary`.

    Show a recap of the visitor's given answers on the result page.

..  confval:: settings.statistics_enabled
    :name: flexform-statistics-enabled
    :type: boolean
    :Default: 0

    Backend label: :guilabel:`Count usage`.

    Keep a tally on the questionnaire record of how often it was started and
    completed. See :ref:`stored-results-counters`.

Tab: Button labels
------------------

Every button the visitor sees carries a translated standard text that follows
the language of the site. A field left empty keeps that standard text, which is
shown as the field's placeholder — there is no need to fill in all of them.

The last two fields only have an effect once their function is switched on:
the copy button belongs to the retrieval link, the send button to the mail form.

..  confval:: settings.button_start
    :name: flexform-button-start
    :type: string
    :Default: *Start*

    Backend label: :guilabel:`Start button`.

    Label of the button on the introduction screen.

..  confval:: settings.button_previous
    :name: flexform-button-previous
    :type: string
    :Default: *Previous*

    Backend label: :guilabel:`Previous button`.

    Label of the button that goes back one question.

..  confval:: settings.button_next
    :name: flexform-button-next
    :type: string
    :Default: *Next*

    Backend label: :guilabel:`Next button`.

    Label of the submit button on every question but the last.

..  confval:: settings.button_finish
    :name: flexform-button-finish
    :type: string
    :Default: *Finish*

    Backend label: :guilabel:`Finish button`.

    Label of the submit button on the last visible question.

..  confval:: settings.button_reset
    :name: flexform-button-reset
    :type: string
    :Default: *Start over*

    Backend label: :guilabel:`Start over button`.

    Label of the button that clears the session and returns to the start. It
    appears once at least one answer has been given.

..  confval:: settings.button_change_answers
    :name: flexform-button-change-answers
    :type: string
    :Default: *Change my answers*

    Backend label: :guilabel:`Change answers button`.

    Label of the button on the result page that returns to the questions with
    the given answers intact.

..  confval:: settings.button_copy_link
    :name: flexform-button-copy-link
    :type: string
    :Default: *Copy link*

    Backend label: :guilabel:`Copy link button`.

    Label of the button that copies the retrieval link to the clipboard.

..  confval:: settings.button_mail_submit
    :name: flexform-button-mail-submit
    :type: string
    :Default: *Send*

    Backend label: :guilabel:`Send mail button`.

    Label of the submit button of the mail-to-self form.

Tab: Storing and mailing the result
-----------------------------------

..  confval:: settings.db_save_result_enabled
    :name: flexform-save-enabled
    :type: boolean
    :Default: 0

    Backend label: :guilabel:`Store the result`.

    Store the result and offer the visitor a link to return to it later. See
    :ref:`stored-results`.

..  confval:: settings.db_save_result_storage_pid
    :name: flexform-storage-pid
    :type: page uid
    :Default: *(from TypoScript, then the current page)*

    Backend label: :guilabel:`Folder for the stored results`.

    The folder the stored results are filed in. Required as soon as storing is
    on.

..  confval:: settings.db_save_result_lifetime_days
    :name: flexform-lifetime-days
    :type: int
    :Default: *(from TypoScript: 365)*

    Backend label: :guilabel:`Keep the result for (days)`.

    After this many days a stored result is due for removal by the purge
    command.

..  confval:: settings.mail_result_enabled
    :name: flexform-mail-enabled
    :type: boolean
    :Default: 0

    Backend label: :guilabel:`Allow mailing the result`.

    Show a form on the result page where the visitor can mail the result to
    themselves.

..  confval:: settings.privacy_link
    :name: flexform-privacy-link
    :type: link
    :Default: *(empty)*

    Backend label: :guilabel:`Link to the privacy statement`.

    The page explaining what is stored and for how long, shown next to the mail
    form. The extension only links to it; the text is yours to write.

..  confval:: settings.mail_from_address
    :name: flexform-mail-from-address
    :type: string
    :Default: *(from TypoScript, then the installation)*

    Backend label: :guilabel:`Sender address of the mail`.

    Sender address of the result mail for this plugin instance.

..  confval:: settings.mail_from_name
    :name: flexform-mail-from-name
    :type: string
    :Default: *(from TypoScript, then the installation)*

    Backend label: :guilabel:`Sender name`.

    Sender name of the result mail for this plugin instance.

..  confval:: settings.mail_intro_text
    :name: flexform-mail-intro-text
    :type: text
    :Default: *(standard text)*

    Backend label: :guilabel:`Opening text of the mail`.

    Replaces the standard opening line of the mail.

..  confval:: settings.mail_footer_text
    :name: flexform-mail-footer-text
    :type: text
    :Default: *(standard text)*

    Backend label: :guilabel:`Closing text`.

    Replaces the standard closing line of the mail.

..  confval:: settings.mail_rate_limit_disabled
    :name: flexform-rate-limit-disabled
    :type: boolean
    :Default: 0

    Backend label: :guilabel:`Switch off the send limit`.

    Switches off the send limit of the mail form.

    ..  caution::
        Leave this off on a live site. The limit is what stops the form from
        being used to send mail to other people's addresses in bulk. It is meant
        for testing an installation, where repeated attempts would otherwise be
        blocked. See :ref:`stored-results-rate-limit`.
