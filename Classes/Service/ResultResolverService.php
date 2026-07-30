<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\AdviceBlock;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;

/**
 * ResultResolverService
 *
 * Determines which result page to show after the questionnaire is completed.
 *
 * Result pages are evaluated **top to bottom** (sort_order ascending) — the
 * first page whose trigger condition matches is returned. A `catch_all` result
 * should therefore always be placed last as a fallback.
 *
 * Trigger types
 * ─────────────
 * - `catch_all`       — always matches; used as fallback
 * - `score_range`     — matches when `scoreMin <= totalScore <= scoreMax`
 * - `specific_answer` — matches when a specific answer option was selected
 * - `combination`     — matches when both score_range AND specific_answer match
 * - `scale_answer`    — matches when a scale question answer falls within [triggerScaleMin, triggerScaleMax]
 *
 * Advice block filtering
 * ───────────────────────
 * After resolving the result page, call `filterAdviceBlocks()` to get only
 * the advice blocks that should be shown to the visitor given their score and
 * answers.
 *
 * Session answers format (from SessionService):
 * `array<int, string[]>` — questionUid → selected answerOption UIDs[]
 */
class ResultResolverService
{
    /**
     * Evaluate the result pages of a questionnaire and return the first match.
     *
     * @param Questionnaire           $questionnaire  Used to access ordered result pages
     * @param array<int, string[]> $sessionAnswers Stored answers from SessionService
     * @param float                   $totalScore     From ScoringService (0.0 when scoring disabled)
     */
    public function resolve(
        Questionnaire $questionnaire,
        array $sessionAnswers,
        float $totalScore
    ): ?ResultPage {

        foreach ($questionnaire->getResultPages() as $resultPage) {
            if ($this->matches($resultPage, $sessionAnswers, $totalScore)) {
                return $resultPage;
            }
        }

        return null;
    }

    /**
     * Filter the advice blocks of a resolved inline result page down to only
     * those that are visible given the visitor's score and answers.
     *
     * A group heading has no condition of its own: it is shown when at least one of the blocks
     * pointing at it survives the filter, so a group whose members are all hidden leaves no empty
     * heading behind. That takes two passes — the members decide the heading, not the other way
     * around. The original order is preserved, so a heading appears wherever the editor sorted it.
     *
     * @param array<int, string[]> $sessionAnswers
     * @return array<AdviceBlock>
     */
    public function filterAdviceBlocks(
        ResultPage $resultPage,
        array $sessionAnswers,
        float $totalScore
    ): array {
        $blocks = [];

        foreach ($resultPage->getAdviceBlocks() as $block) {
            $blocks[] = $block;
        }

        $visibleIndexes = [];
        $usedHeaderUids = [];

        foreach ($blocks as $index => $block) {
            if ($block->isGroupHeader()) {
                continue;
            }

            if (!$this->isAdviceBlockVisible($block, $sessionAnswers, $totalScore)) {
                continue;
            }

            $visibleIndexes[$index] = true;
            $header = $block->getGroupHeader();

            if ($header !== null) {
                $usedHeaderUids[(int)$header->getUid()] = true;
            }
        }

        $visible = [];

        foreach ($blocks as $index => $block) {
            if ($block->isGroupHeader()) {
                if (isset($usedHeaderUids[(int)$block->getUid()])) {
                    $visible[] = $block;
                }

                continue;
            }

            if (isset($visibleIndexes[$index])) {
                $visible[] = $block;
            }
        }

        return $visible;
    }

    /**
     * Test whether a result page's trigger condition is satisfied.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function matches(
        ResultPage $resultPage,
        array $sessionAnswers,
        float $totalScore
    ): bool {
        return match ($resultPage->getTriggerType()) {
            ResultPage::TRIGGER_CATCH_ALL      => true,
            ResultPage::TRIGGER_SCORE_RANGE    => $this->matchesScoreRange($resultPage, $totalScore),
            ResultPage::TRIGGER_SPECIFIC_ANSWER => $this->matchesSpecificAnswer($resultPage, $sessionAnswers),
            ResultPage::TRIGGER_COMBINATION    => $this->matchesScoreRange($resultPage, $totalScore)
                                               && $this->matchesSpecificAnswer($resultPage, $sessionAnswers),
            ResultPage::TRIGGER_SCALE_ANSWER   => $this->matchesScaleAnswer($resultPage, $sessionAnswers),
            default                            => false,
        };
    }

    /**
     * Check whether `scoreMin <= totalScore <= scoreMax`.
     */
    private function matchesScoreRange(ResultPage $resultPage, float $totalScore): bool
    {
        return $totalScore >= $resultPage->getScoreMin()
            && $totalScore <= $resultPage->getScoreMax();
    }

    /**
     * Check whether the result page's trigger answer was selected by the visitor.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function matchesSpecificAnswer(ResultPage $resultPage, array $sessionAnswers): bool
    {
        $triggerAnswer = $resultPage->getTriggerAnswer();

        if ($triggerAnswer === null) {
            return false;
        }

        foreach ($sessionAnswers as $selectedValues) {
            if (in_array((string)$triggerAnswer->getUid(), $selectedValues, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the visitor's scale answer for the trigger question falls
     * within [triggerScaleMin, triggerScaleMax] (both inclusive).
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function matchesScaleAnswer(ResultPage $resultPage, array $sessionAnswers): bool
    {
        $question = $resultPage->getTriggerQuestion();

        if ($question === null) {
            return false;
        }

        $selectedValues = $sessionAnswers[(int)$question->getUid()] ?? [];

        if ($selectedValues === []) {
            return false;
        }

        $value = (float)($selectedValues[0] ?? 0);

        return $value >= $resultPage->getTriggerScaleMin()
            && $value <= $resultPage->getTriggerScaleMax();
    }

    /**
     * Determine whether an advice block should be displayed.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function isAdviceBlockVisible(
        AdviceBlock $block,
        array $sessionAnswers,
        float $totalScore
    ): bool {
        return match ($block->getConditionType()) {
            AdviceBlock::CONDITION_ALWAYS          => true,
            AdviceBlock::CONDITION_SCORE_RANGE     => $totalScore >= $block->getScoreMin()
                                                   && $totalScore <= $block->getScoreMax(),
            AdviceBlock::CONDITION_SPECIFIC_ANSWER => $this->adviceBlockAnswerMatches($block, $sessionAnswers),
            AdviceBlock::CONDITION_SCALE_RANGE     => $this->adviceBlockScaleRangeMatches($block, $sessionAnswers),
            // A group heading is never decided here: filterAdviceBlocks() derives it from the
            // members. Returning false keeps a heading out of any other code path that asks.
            AdviceBlock::CONDITION_GROUP_HEADER    => false,
            default                                => false,
        };
    }

    /**
     * Check whether the advice block's trigger answer was selected.
     *
     * @param array<int, string[]> $sessionAnswers
     */
    /**
     * Check whether the advice block's trigger answer was selected.
     *
     * An inverted block flips that outcome, so it shows to everyone who did not
     * give the answer — including visitors who left the question blank, which is
     * what an optional checkbox question needs. A block without a trigger answer
     * is misconfigured and stays hidden either way; inverting must not turn that
     * into "show to everyone".
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function adviceBlockAnswerMatches(AdviceBlock $block, array $sessionAnswers): bool
    {
        $triggerAnswer = $block->getTriggerAnswer();

        if ($triggerAnswer === null) {
            return false;
        }

        $selected = false;

        foreach ($sessionAnswers as $selectedValues) {
            if (in_array((string)$triggerAnswer->getUid(), $selectedValues, true)) {
                $selected = true;
                break;
            }
        }

        return $block->isNegateCondition() ? !$selected : $selected;
    }

    /**
     * Check whether the visitor's scale answer for the block's trigger question
     * falls within [triggerScaleMin, triggerScaleMax] (both inclusive).
     *
     * @param array<int, string[]> $sessionAnswers
     */
    private function adviceBlockScaleRangeMatches(AdviceBlock $block, array $sessionAnswers): bool
    {
        $question = $block->getTriggerQuestion();

        if ($question === null) {
            return false;
        }

        $selectedValues = $sessionAnswers[(int)$question->getUid()] ?? [];

        if ($selectedValues === []) {
            return false;
        }

        $value = (float)($selectedValues[0] ?? 0);

        return $value >= $block->getTriggerScaleMin()
            && $value <= $block->getTriggerScaleMax();
    }
}
