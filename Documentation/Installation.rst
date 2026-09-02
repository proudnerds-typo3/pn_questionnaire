..  _installation:

============
Installation
============

..  _installation-quickstart:

Quick start
===========

#.  Install the extension (see :ref:`installation-composer` below) and run the
    **Database Analyser** in the Install Tool.
#.  Load the TypoScript: on TYPO3 v13 and v14 add the Site Set
    :yaml:`proudnerds/pn-questionnaire` to the site configuration, on v12
    include the static template *Questionnaire / Test / Decision tree*. See
    :ref:`installation-typoscript`.
#.  Create a **Questionnaire** record in the List module — a dedicated sysfolder
    keeps things tidy.
#.  Add questions, answer options and at least one result page to that record.
    See :ref:`editing`.
#.  Place the **Questionnaire / Decision Tree** plugin on a page and select the
    questionnaire record in its FlexForm.

..  _installation-composer:

Composer
========

..  code-block:: bash

    composer require proudnerds/pn-questionnaire

TYPO3 picks the extension up automatically; there is nothing to activate by
hand.

Classic mode
============

Install **Questionnaire / Test / Decision tree** from the TYPO3 Extension
Repository through :guilabel:`Admin Tools > Extensions`, then activate it there.

Both routes
===========

After the first install, run the **Database Analyser** in the TYPO3 Install Tool
to create the seven tables, and load the TypoScript as described below.

..  _installation-upgrading:

Upgrading
=========

Run the **Database Analyser** again after any update that adds a field; the
:ref:`changelog <changelog>` says so per release. Version 1.1.0 for instance
added :sql:`introduction_footer_text` to the questionnaire table. Existing
records and plugin settings keep working as they are — no migration wizard is
needed.

..  _installation-typoscript:

Loading the TypoScript
======================

The TypoScript itself lives in
:file:`Configuration/TypoScript/setup.typoscript` and
:file:`constants.typoscript` — a location every supported TYPO3 version
understands. It is offered through two routes, both pointing at those same two
files:

..  list-table::
    :header-rows: 1

    -   -   TYPO3 version
        -   Route
    -   -   14.3
        -   Site Set `proudnerds/pn-questionnaire` (preferred)
    -   -   13.4
        -   Site Set `proudnerds/pn-questionnaire` (preferred)
    -   -   12.4
        -   Static template *Questionnaire / Test / Decision tree*

Site Sets were introduced in TYPO3 v13.1 and are ignored by v12, which is why
the static template exists. The files in
:file:`Configuration/Sets/PnQuestionnaire/` contain nothing but an
:typoscript:`@import` of the files above.

On TYPO3 v13 and v14 — Site Set
-------------------------------

Add the set to any site that should use the questionnaire plugin:

..  code-block:: yaml
    :caption: config/sites/your-site/config.yaml

    dependencies:
      - proudnerds/pn-questionnaire

The set name is defined in
:file:`Configuration/Sets/PnQuestionnaire/config.yaml`:

..  code-block:: yaml
    :caption: EXT:pn_questionnaire/Configuration/Sets/PnQuestionnaire/config.yaml

    name: proudnerds/pn-questionnaire
    label: Questionnaire / Test / Decision tree

TYPO3 automatically includes :file:`setup.typoscript` and
:file:`constants.typoscript` from the set directory when the dependency is
active.

..  tip::
    **Troubleshooting — TypoScript from the set is not loaded**

    If the set is listed in :yaml:`dependencies` but the TypoScript is still not
    applied, check the **root TypoScript template** in the TYPO3 backend:

    #.  Go to :guilabel:`Web > Template`, select the root page, open the
        template record.
    #.  On the :guilabel:`Options` tab, make sure :guilabel:`Clear constants`
        and :guilabel:`Clear setup` are both **unchecked**.

    When either flag is checked, TYPO3 discards all TypoScript that was loaded
    by Site Sets *before* evaluating the template's own content — effectively
    wiping :file:`setup.typoscript` entirely. Unchecking both flags lets the
    Site Set TypoScript survive and be extended by the template.

On TYPO3 v12 — static template
------------------------------

The extension registers :file:`Configuration/TypoScript/` as a selectable
static template in
:file:`Configuration/TCA/Overrides/sys_template.php`. Go to
:guilabel:`Web > Template`, open the root template record and add
:guilabel:`Questionnaire / Test / Decision tree (pn_questionnaire)` to
*Include static (from extensions)*.

Include it **before** any site package that overrides the view path constants,
otherwise those overrides are undone again.

Do not combine the static template with a site package that already imports
:file:`EXT:pn_questionnaire/Configuration/TypoScript/setup.typoscript`
directly — the values are identical so :typoscript:`setup` stays correct, but a
second pass over the constants can override a site package's view path
overrides depending on include order.

..  _installation-routing:

The retrieval route
===================

Storing results produces a link of the form
:samp:`/{plugin-page}/saved-result/{token}`. An extension cannot register route
enhancers itself, so the site configuration has to import the shipped one:

..  code-block:: yaml
    :caption: config/sites/your-site/config.yaml

    imports:
      - resource: 'EXT:pn_questionnaire/Configuration/Routing/SavedResult.yaml'

Without that import the retrieval link still works, but as a query-string URL.
See :ref:`stored-results-route` for the details.

Backend search on TYPO3 v14
===========================

On v12 and v13 the records of this extension declare their backend search
fields explicitly. TYPO3 v14 dropped that mechanism and derives searchability
from the field type instead, so on v14 a search in the list module covers every
text-like field of these tables rather than a curated subset. The extension
follows each version's own convention; nothing needs configuring.
