<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\Question;

/**
 * ScoringService
 *
 * Calculates the total score from all answers given by the visitor.
 *
 * Scoring rules
 * ─────────────
 * - Only **visible** questions contribute to the score (skipped questions
 *   do not count).
 * - `Informational` questions never have answer options → skipped.
 * - `Scale` questions do not use answer option records → skipped.
 * - For single-choice, multiple-choice and yes/no: every selected answer
 *   option's `score` property is added to the total.
 * - Scores may be positive or negative (decimal).
 * - Whether the score is shown to the visitor is controlled separately
 *   by the `show_score` FlexForm setting on the plugin instance.
 *
 * Session answers format (from SessionService):
 * `array<int, string[]>` — questionUid → selected answerOption UIDs[]
 */
class ScoringService
{
    /**
     * Calculate the total score for a completed questionnaire.
     *
     * @param array<Question> $visibleQuestions Filtered questions from ConditionEvaluatorService
     * @param array<int, string[]> $sessionAnswers Stored answers from SessionService
     */
    public function calculateTotal(
        array $visibleQuestions,
        array $sessionAnswers
    ): float {

        $total = 0.0;

        foreach ($visibleQuestions as $question) {
            // Scale and informational questions have no scoreable answer options
            if ($question->isScale() || $question->isInformational()) {
                continue;
            }

            $selectedValues = $sessionAnswers[(int)$question->getUid()] ?? [];

            if ($selectedValues === []) {
                continue;
            }

            foreach ($question->getAnswerOptions() as $answerOption) {
                if (in_array((string)$answerOption->getUid(), $selectedValues, true)) {
                    $total += $answerOption->getScore();
                }
            }
        }

        return $total;
    }
}
