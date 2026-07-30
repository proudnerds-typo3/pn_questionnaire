<?php

declare(strict_types=1);

/**
 * Extbase Persistence Class Mapping
 *
 * Maps domain model classes to their database tables.
 * Required because our table names use the pattern tx_pnquestionnaire_*
 * instead of the Extbase default tx_pnquestionnaire_domain_model_*.
 *
 * Automatically loaded by TYPO3 from Configuration/Extbase/Persistence/Classes.php.
 */

use ProudNerds\PnQuestionnaire\Domain\Model\AdviceBlock;
use ProudNerds\PnQuestionnaire\Domain\Model\AnswerOption;
use ProudNerds\PnQuestionnaire\Domain\Model\Condition;
use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;
use ProudNerds\PnQuestionnaire\Domain\Model\SavedResult;

return [
    Questionnaire::class => [
        'tableName' => 'tx_pnquestionnaire_questionnaire',
    ],
    Question::class => [
        'tableName' => 'tx_pnquestionnaire_question',
    ],
    AnswerOption::class => [
        'tableName' => 'tx_pnquestionnaire_answer_option',
    ],
    Condition::class => [
        'tableName' => 'tx_pnquestionnaire_condition',
    ],
    ResultPage::class => [
        'tableName' => 'tx_pnquestionnaire_result_page',
    ],
    AdviceBlock::class => [
        'tableName' => 'tx_pnquestionnaire_advice_block',
    ],
    SavedResult::class => [
        'tableName' => 'tx_pnquestionnaire_saved_result',
    ],
];
