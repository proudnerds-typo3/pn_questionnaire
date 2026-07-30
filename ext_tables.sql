CREATE TABLE tx_pnquestionnaire_questionnaire
(
    title             varchar(255)        DEFAULT ''  NOT NULL,
    introduction_text text,
    questions         int(11) unsigned    DEFAULT '0' NOT NULL,
    result_pages      int(11) unsigned    DEFAULT '0' NOT NULL,
    starts            int(11) unsigned    DEFAULT '0' NOT NULL,
    completions       int(11) unsigned    DEFAULT '0' NOT NULL
);

CREATE TABLE tx_pnquestionnaire_question
(
    questionnaire  int(11) unsigned    DEFAULT '0'             NOT NULL,
    question_text  text,
    help_text      text,
    tt_content_uid int(11) unsigned    DEFAULT '0'             NOT NULL,
    type           varchar(50)         DEFAULT 'single_choice' NOT NULL,
    scale_min      int(11)             DEFAULT '1'             NOT NULL,
    scale_max      int(11)             DEFAULT '10'            NOT NULL,
    scale_display  varchar(10)         DEFAULT 'range'         NOT NULL,
    required       tinyint(1) unsigned DEFAULT '0'             NOT NULL,
    sort_order     int(11)             DEFAULT '0'             NOT NULL,
    answer_options int(11) unsigned    DEFAULT '0'             NOT NULL,
    conditions     int(11) unsigned    DEFAULT '0'             NOT NULL,
    KEY            questionnaire_idx (questionnaire)
);

CREATE TABLE tx_pnquestionnaire_answer_option
(
    question   int(11) unsigned DEFAULT '0'    NOT NULL,
    label      varchar(255)     DEFAULT ''     NOT NULL,
    value      varchar(255)     DEFAULT ''     NOT NULL,
    score      decimal(10, 2)   DEFAULT '0.00' NOT NULL,
    sort_order int(11)          DEFAULT '0'    NOT NULL,
    KEY        question_idx (question)
);

CREATE TABLE tx_pnquestionnaire_condition
(
    question           int(11) unsigned DEFAULT '0'   NOT NULL,
    reference_question int(11) unsigned DEFAULT '0'              NOT NULL,
    reference_answer   int(11) unsigned DEFAULT '0'              NOT NULL,
    condition_type     varchar(20)      DEFAULT 'specific_answer' NOT NULL,
    scale_operator     varchar(5)       DEFAULT '>='             NOT NULL,
    scale_value        int(11)          DEFAULT '0'              NOT NULL,
    operator           varchar(10)      DEFAULT 'AND'            NOT NULL,
    sort_order         int(11)          DEFAULT '0'   NOT NULL,
    KEY                question_idx (question)
);

CREATE TABLE tx_pnquestionnaire_result_page
(
    questionnaire  int(11) unsigned    DEFAULT '0'          NOT NULL,
    title          varchar(255)        DEFAULT ''            NOT NULL,
    sort_order     int(11)             DEFAULT '0'           NOT NULL,
    trigger_type   varchar(50)         DEFAULT 'catch_all'   NOT NULL,
    score_min      decimal(10, 2)      DEFAULT '0.00'        NOT NULL,
    score_max      decimal(10, 2)      DEFAULT '0.00'        NOT NULL,
    trigger_answer    int(11) unsigned    DEFAULT '0'           NOT NULL,
    trigger_question  int(11) unsigned    DEFAULT '0'           NOT NULL,
    trigger_scale_min int(11)             DEFAULT '0'           NOT NULL,
    trigger_scale_max int(11)             DEFAULT '0'           NOT NULL,
    outcome_type   varchar(50)         DEFAULT 'inline'      NOT NULL,
    headline       varchar(255)        DEFAULT ''            NOT NULL,
    body_text      text,
    cta_label      varchar(255)        DEFAULT ''            NOT NULL,
    cta_link       varchar(2048)       DEFAULT ''            NOT NULL,
    page_uid       int(11) unsigned    DEFAULT '0'           NOT NULL,
    external_url   varchar(2048)       DEFAULT ''            NOT NULL,
    record_uid     int(11) unsigned    DEFAULT '0'           NOT NULL,
    advice_blocks  int(11) unsigned    DEFAULT '0'           NOT NULL,
    KEY            questionnaire_idx (questionnaire)
);

CREATE TABLE tx_pnquestionnaire_advice_block
(
    result_page    int(11) unsigned    DEFAULT '0'      NOT NULL,
    headline       varchar(255)        DEFAULT ''       NOT NULL,
    body_text      text,
    condition_type varchar(50)         DEFAULT 'always' NOT NULL,
    -- Self-relation to another advice block of type `group_header`, which then renders as the
    -- heading above this block. Optional: 0 means the block is not part of a group.
    group_header   int(11) unsigned    DEFAULT '0'      NOT NULL,
    score_min      decimal(10, 2)      DEFAULT '0.00'   NOT NULL,
    score_max      decimal(10, 2)      DEFAULT '0.00'   NOT NULL,
    trigger_answer int(11) unsigned    DEFAULT '0'      NOT NULL,
    negate_condition  smallint(5) unsigned DEFAULT '0'  NOT NULL,
    trigger_question  int(11) unsigned DEFAULT '0'      NOT NULL,
    trigger_scale_min int(11)          DEFAULT '0'      NOT NULL,
    trigger_scale_max int(11)          DEFAULT '0'      NOT NULL,
    sort_order     int(11)             DEFAULT '0'      NOT NULL,
    KEY            result_page_idx (result_page)
);

-- Bewaarde uitslag van een bezoeker. Bevat bewust geen identificerend gegeven:
-- geen IP, e-mailadres, user-agent of FE-user-relatie, ook niet gehasht.
-- `answers` staat hier expliciet als text en wordt dus niet door de core als json-kolom
-- aangemaakt: op een Doctrine-json-kolom encodeert Connection::insert() de waarde nog een
-- keer, en Extbase levert een string aan omdat het geen array-properties mapt. De TCA houdt
-- type=json voor de leesbare weergave in de backend.
CREATE TABLE tx_pnquestionnaire_saved_result
(
    token         varchar(32)      DEFAULT ''     NOT NULL,
    result_url    varchar(2048)    DEFAULT ''     NOT NULL,
    questionnaire int(11) unsigned DEFAULT '0'    NOT NULL,
    answers       text,
    score         decimal(10, 2)   DEFAULT '0.00' NOT NULL,
    expires       int(11) unsigned DEFAULT '0'    NOT NULL,
    UNIQUE KEY    token (token),
    KEY           expires_idx (expires),
    KEY           questionnaire_idx (questionnaire)
);

