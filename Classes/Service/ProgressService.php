<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\Question;

/**
 * ProgressService
 *
 * Calculates the visitor's current position within the visible question list.
 *
 * The total (Y in "Step X of Y") is derived from the number of currently
 * visible questions — this number can change after each answer when conditional
 * questions are revealed or hidden. The note "The number of steps may change
 * based on your answers" in the template reflects this behaviour.
 *
 * Informational questions count as a step (the visitor still navigates past
 * them), even though they carry no input.
 */
class ProgressService
{
    /**
     * Calculate the current step number and total for the progress indicator.
     *
     * @param array<Question> $visibleQuestions Ordered list of visible questions
     *                                          from ConditionEvaluatorService
     * @param int             $currentQuestionUid UID of the question currently shown
     * @return array{current: int, total: int}
     */
    public function calculate(array $visibleQuestions, int $currentQuestionUid): array
    {
        $total   = count($visibleQuestions);
        $current = 1;

        foreach (array_values($visibleQuestions) as $index => $question) {
            if ($question->getUid() === $currentQuestionUid) {
                $current = $index + 1;
                break;
            }
        }

        return ['current' => $current, 'total' => $total];
    }

    /**
     * Return the UID of the next visible question after the given question UID,
     * or null if the given question is the last one.
     *
     * @param array<Question> $visibleQuestions
     */
    public function getNextQuestionUid(array $visibleQuestions, int $currentQuestionUid): ?int
    {
        $reindexed = array_values($visibleQuestions);

        foreach ($reindexed as $index => $question) {
            if ($question->getUid() === $currentQuestionUid) {
                $next = $reindexed[$index + 1] ?? null;
                return $next?->getUid();
            }
        }

        return null;
    }

    /**
     * Return the UID of the previous visible question before the given question UID,
     * or null if the given question is the first one.
     *
     * @param array<Question> $visibleQuestions
     */
    public function getPreviousQuestionUid(array $visibleQuestions, int $currentQuestionUid): ?int
    {
        $reindexed = array_values($visibleQuestions);

        foreach ($reindexed as $index => $question) {
            if ($question->getUid() === $currentQuestionUid) {
                return $index > 0 ? $reindexed[$index - 1]->getUid() : null;
            }
        }

        return null;
    }

    /**
     * Return true when the given question UID is the last visible question.
     *
     * @param array<Question> $visibleQuestions
     */
    public function isLastQuestion(array $visibleQuestions, int $currentQuestionUid): bool
    {
        $last = end($visibleQuestions);
        return $last instanceof Question && $last->getUid() === $currentQuestionUid;
    }
}
