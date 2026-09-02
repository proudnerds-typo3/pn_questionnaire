..  _stored-results:

==================================
Stored results and the result mail
==================================

Both are off by default and are switched on per plugin instance, with
:ref:`confval-flexform-save-enabled` and
:ref:`confval-flexform-mail-enabled`. This chapter covers what happens once
they are on — the editor-facing side as well as the technical one.

What the visitor gets
=====================

With storing on, the result page gains a link the visitor can save or share.
Opening it later brings back the same result, without a login and without the
visitor's session.

Nothing identifying is stored: **no name, no IP address, no e-mail address, not
even in hashed form.** What is stored is the given answers, the score and a
random code that appears in the link. Someone without the link cannot find the
result, and the answers cannot be traced back to a person.

Two consequences worth knowing:

-   The result disappears once the retention period expires. Say so in the text
    around the link if visitors are likely to rely on it.
-   The link is the only key. A visitor who loses it cannot recover the result,
    and neither can an administrator.

Point :ref:`confval-flexform-privacy-link` at the page where your organisation
explains this. The extension only links to it — the text itself is yours to
write, and it should mention the retention period and that no personal data is
stored.

With mailing on, the result page shows a form asking for an e-mail address. The
visitor receives the full result plus the retrieval link. The address is used to
send that one mail and is **not stored** — not in the database, not in a log,
not on the stored result.

..  _stored-results-table:

The table
=========

:sql:`tx_pnquestionnaire_saved_result` holds one row per completed run.

..  list-table::
    :header-rows: 1

    -   -   Column
        -   Purpose
    -   -   `token`
        -   32 hex characters, 128 bits of entropy, from `random_bytes()`.
            Unique index. The only key to the row
    -   -   `result_url`
        -   The generated retrieval URL, cached so the mail and the result page
            do not have to rebuild it
    -   -   `questionnaire`
        -   The questionnaire the run belongs to
    -   -   `answers`
        -   The given answers, as JSON
    -   -   `score`
        -   The calculated score
    -   -   `expires`
        -   Unix timestamp after which the row is due for removal
    -   -   `crdate`, `tstamp`, `deleted`
        -   Standard TYPO3 fields; the table uses soft delete

The :sql:`crdate` timestamp is the only field that could in theory be correlated
with an access log, which is a reason not to extend the retention period beyond
what is needed.

..  _stored-results-route:

The retrieval route
===================

:file:`Configuration/Routing/SavedResult.yaml` provides a route enhancer
producing :samp:`/{plugin-page}/saved-result/{token}`. The site configuration
has to import it; see :ref:`installation-routing`.

Without that import the retrieval link still works, but as a query-string URL.
The first segment follows the site language — ``saved-result``,
``bewaarde-uitslag``, ``gespeichertes-ergebnis``. Add a locale to the
``localeMap`` for another language, or override the whole
``PnQuestionnaireSavedResult`` key after the import to change the wording. The
``token`` requirement is pinned to ``[0-9a-f]{32}`` so the enhancer never claims
an unrelated URL on a page without the plugin.

After a domain change or a change to this route, the stored :sql:`result_url`
values are stale. They can be regenerated from the tokens — the URL is derived
data, not a source.

..  _stored-results-purge:

Retention and purging
=====================

The retention period comes from :ref:`confval-flexform-lifetime-days`, falling
back to :ref:`confval-typoscript-lifetime-days`. It is applied when a result is
stored, by writing :sql:`expires`.

Expired rows are removed by a console command:

..  code-block:: bash

    vendor/bin/typo3 pnquestionnaire:purgesavedresults
    vendor/bin/typo3 pnquestionnaire:purgesavedresults --dry-run

Register it as a scheduler task to keep the table clean. Unlike a backend
deletion, the command removes rows for real rather than flagging them deleted —
a stored result that has expired should not linger in the recycler.

The result mail
===============

The mail is sent as HTML and plain text, using the installation's own
:php:`SystemEmail` layout, so it inherits the styling already configured for
TYPO3 mails.

Rich text inside the mail runs through
:typoscript:`lib.parseFunc_pnQuestionnaireMail`, a copy of
:typoscript:`lib.parseFunc` with :typoscript:`forceAbsoluteUrl` on. A relative
:html:`href` is a dead link in a mail client, and no Fluid ViewHelper can reach
inside stored rich text — :html:`<f:format.html>` hands the HTML straight to
parseFunc. The nested parseFunc references for lists, preformatted text and
table cells are repointed one by one; without that a link inside a list would
still come out relative.

The sender falls back through three levels: the plugin's FlexForm, then
TypoScript, then the installation's default sender. See
:ref:`confval-flexform-mail-from-address`.

..  _stored-results-rate-limit:

The send limit
==============

:file:`Configuration/Services.yaml` defines a rate limiter for the mail form:
**three sends per hour**, counted separately per recipient address and per
client address. It uses the Symfony rate limiter that ships with the core, so
there is no custom counting.

Two things to be aware of:

-   The per-client limit is only as reliable as the proxy configuration. Behind
    a reverse proxy, :php:`reverseProxyIP` has to be configured or every visitor
    looks like the same client.
-   :ref:`confval-flexform-rate-limit-disabled` switches the limit off. It is
    meant for testing an installation and should be off on a live site.

The form's protection against cross-site submission relies on
:php:`FE.cookieSameSite` being ``lax`` or stricter. That is a requirement, not a
detail.

..  _stored-results-counters:

Usage counters
==============

With :ref:`confval-flexform-statistics-enabled` on, the questionnaire record
keeps two tallies: :sql:`starts`, incremented when a visitor begins, and
:sql:`completions`, incremented on a completed run. They are plain counters on
the questionnaire record — no per-visitor data is involved.

Inverted conditions
===================

:sql:`negate_condition` on :sql:`tx_pnquestionnaire_advice_block` inverts a
``specific_answer`` condition. It is applied in :php:`ResultResolverService`, at
the point where the block's trigger answer is matched against the given answers.
An absent answer counts as "not given", so an inverted block also appears for a
question the visitor never answered — which is why
:ref:`editing-step7` recommends making such questions required.
