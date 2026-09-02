..  _changelog:

=========
Changelog
=========

The full changelog, including the upgrade notes per version, is maintained in
the repository:
`CHANGELOG.md <https://github.com/proudnerds-typo3/pn_questionnaire/blob/main/CHANGELOG.md>`__.

The versioning follows `Semantic Versioning <https://semver.org/>`__: a patch
release never changes behaviour, a minor release adds features and may add a
database field, and a major release may break compatibility.

..  note::
    Run the **Database Analyser** in the Install Tool after any update that adds
    a field. The changelog says so per release. See
    :ref:`installation-upgrading`.

Highlights per version
======================

..  list-table::
    :header-rows: 1

    -   -   Version
        -   What changed
    -   -   **1.2**
        -   The progress bar measures answered questions instead of the step
            number, with `progress_mode` to pick the convention; the bar also
            appears on the result page
    -   -   **1.1**
        -   Own button texts per plugin instance, and a text below the start
            button on the introduction screen. Adds the field
            `introduction_footer_text`
    -   -   **1.0**
        -   First public release

