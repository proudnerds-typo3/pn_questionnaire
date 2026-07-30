<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\Condition;
use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * ConditionEvaluatorService
 *
 * Determines which questions are visible for the current visitor based on
 * their stored session answers and the condition rules attached to each question.
 *
 * Evaluation rules
 * ─────────────────
 * - A question with **no conditions** is always shown.
 * - A question with conditions is shown only when the combined result
 *   of all its conditions evaluates to true.
 * - Conditions are evaluated in `sort_order` ascending.
 * - The **first** condition in the list is evaluated as-is (its operator is the
 *   starting value; no previous result to combine with).
 * - Each subsequent condition uses its own `operator` to combine with the
 *   running result:
 *     - `AND` → running result && this condition result
 *     - `OR`  → running result || this condition result
 *
 * A single condition evaluates to true when the visitor's stored answers
 * for the `referenceQuestion` contain the `referenceAnswer` option UID.
 * Conditions with a null reference question or answer are ignored (treated as true).
 *
 * Session answers format (from SessionService):
 * `array<int, string[]>` — questionUid → selected values[]
 */
class ConditionEvaluatorService
{
    /**
     * Filter an ObjectStorage of questions down to only those that are
     * currently visible given the visitor's session answers.
     *
     * Preserves the original sort order of the ObjectStorage.
     *
     * @param ObjectStorage<Question> $questions All questions of the questionnaire
     * @param array<int, string[]> $sessionAnswers Current answers from SessionService
     * @return array<Question> Visible questions in original sort order
     */
    public function getVisibleQuestions(ObjectStorage $questions, array $sessionAnswers): array
    {
        $visible = [];

        foreach ($questions as $question) {
            if ($this->isVisible($question, $sessionAnswers)) {
                $visible[] = $question;
            }
        }

        return $visible;
    }

    /**
     * Check whether a single question is visible given the current answers.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    public function isVisible(Question $question, array $sessionAnswers): bool
    {
        $conditions = $question->getConditions();

        if ($conditions->count() === 0) {
            return true;
        }

        // Collect and sort conditions by sort_order
        $sorted = $this->sortConditions($conditions);

        $result = null;

        foreach ($sorted as $condition) {
            $conditionResult = $this->evaluateCondition($condition, $sessionAnswers);

            if ($result === null) {
                // First condition — no previous result to combine with
                $result = $conditionResult;
            } elseif ($condition->getOperator() === Condition::OPERATOR_AND) {
                $result = $result && $conditionResult;
            } else {
                $result = $result || $conditionResult;
            }
        }

        return $result ?? true;
    }

    /**
     * Evaluate a single condition against the visitor's session answers.
     *
     * Returns true when the visitor selected the required answer option for
     * the referenced question. Returns true (skip / always visible) when the
     * condition references are incomplete.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function evaluateCondition(Condition $condition, array $sessionAnswers): bool
    {
        if ($condition->isScaleRange()) {
            return $this->evaluateScaleRangeCondition($condition, $sessionAnswers);
        }

        $refQuestion = $condition->getReferenceQuestion();
        $refAnswer   = $condition->getReferenceAnswer();

        if ($refQuestion === null || $refAnswer === null) {
            // Incomplete condition — treat as passing so it does not block visibility
            return true;
        }

        $selectedValues = $sessionAnswers[(int)$refQuestion->getUid()] ?? [];

        return in_array((string)$refAnswer->getUid(), $selectedValues, true);
    }

    /**
     * Evaluate a scale_range condition: compare the visitor's numeric scale
     * answer for the reference question against the configured threshold.
     *
     * Returns false (question hidden) when the question has not been answered yet.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function evaluateScaleRangeCondition(Condition $condition, array $sessionAnswers): bool
    {
        $refQuestion = $condition->getReferenceQuestion();

        if ($refQuestion === null) {
            return true; // Incomplete — skip
        }

        $selectedValues = $sessionAnswers[(int)$refQuestion->getUid()] ?? [];

        if ($selectedValues === []) {
            return false; // Not yet answered → hide dependent question
        }

        $selected = (float)($selectedValues[0] ?? 0);
        $threshold = (float)$condition->getScaleValue();

        return match ($condition->getScaleOperator()) {
            '>=' => $selected >= $threshold,
            '<=' => $selected <= $threshold,
            '>'  => $selected > $threshold,
            '<'  => $selected < $threshold,
            '='  => $selected == $threshold,
            default => false,
        };
    }

    /**
     * Sort a Condition ObjectStorage by sort_order ascending and return as array.
     *
     * @param ObjectStorage<Condition> $conditions
     * @return array<Condition>
     */
    private function sortConditions(ObjectStorage $conditions): array
    {
        $arr = iterator_to_array($conditions, false);

        usort(
            $arr,
            static fn(Condition $a, Condition $b): int =>
            $a->getSortOrder() <=> $b->getSortOrder()
        );

        return $arr;
    }
}
