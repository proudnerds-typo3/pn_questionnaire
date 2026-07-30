<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use ProudNerds\PnQuestionnaire\Domain\Model\AdviceBlock;
use ProudNerds\PnQuestionnaire\Domain\Model\AnswerOption;
use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;
use ProudNerds\PnQuestionnaire\Service\ResultResolverService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ResultResolverServiceTest extends UnitTestCase
{
    private ResultResolverService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ResultResolverService();
    }

    // ---------------------------------------------------------------- resolve()

    #[Test]
    public function resolveReturnsNullWhenTheQuestionnaireHasNoResultPages(): void
    {
        self::assertNull($this->subject->resolve(new Questionnaire(), [], 0.0));
    }

    #[Test]
    public function resolveReturnsACatchAllPage(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_CATCH_ALL);

        self::assertSame($page, $this->subject->resolve($this->questionnaire($page), [], 0.0));
    }

    #[Test]
    public function resolveReturnsTheFirstMatchAndIgnoresLaterMatches(): void
    {
        $first = $this->resultPage(ResultPage::TRIGGER_CATCH_ALL);
        $second = $this->resultPage(ResultPage::TRIGGER_CATCH_ALL);

        self::assertSame($first, $this->subject->resolve($this->questionnaire($first, $second), [], 0.0));
    }

    #[Test]
    public function resolveSkipsANonMatchingPageToReachTheCatchAll(): void
    {
        $scored = $this->resultPage(ResultPage::TRIGGER_SCORE_RANGE);
        $scored->setScoreMin(10.0);
        $scored->setScoreMax(20.0);

        $catchAll = $this->resultPage(ResultPage::TRIGGER_CATCH_ALL);

        self::assertSame($catchAll, $this->subject->resolve($this->questionnaire($scored, $catchAll), [], 5.0));
    }

    #[Test]
    public function resolveReturnsNullWhenNoPageMatches(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SCORE_RANGE);
        $page->setScoreMin(10.0);
        $page->setScoreMax(20.0);

        self::assertNull($this->subject->resolve($this->questionnaire($page), [], 5.0));
    }

    #[Test]
    public function resolveReturnsNullForAnUnknownTriggerType(): void
    {
        $page = $this->resultPage('something_else');

        self::assertNull($this->subject->resolve($this->questionnaire($page), [], 0.0));
    }

    /**
     * Both bounds of a score range are inclusive.
     */
    #[Test]
    public function resolveTreatsBothScoreBoundsAsInclusive(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SCORE_RANGE);
        $page->setScoreMin(10.0);
        $page->setScoreMax(20.0);

        $questionnaire = $this->questionnaire($page);

        self::assertSame($page, $this->subject->resolve($questionnaire, [], 10.0), 'lower bound');
        self::assertSame($page, $this->subject->resolve($questionnaire, [], 20.0), 'upper bound');
        self::assertNull($this->subject->resolve($questionnaire, [], 9.99), 'just below');
        self::assertNull($this->subject->resolve($questionnaire, [], 20.01), 'just above');
    }

    #[Test]
    public function resolveMatchesASpecificAnswerRegardlessOfWhichQuestionItBelongsTo(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SPECIFIC_ANSWER);
        $page->setTriggerAnswer($this->answerOption(42));

        self::assertSame(
            $page,
            $this->subject->resolve($this->questionnaire($page), [7 => ['1'], 8 => ['42']], 0.0)
        );
    }

    #[Test]
    public function resolveDoesNotMatchASpecificAnswerThatWasNotSelected(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SPECIFIC_ANSWER);
        $page->setTriggerAnswer($this->answerOption(42));

        self::assertNull($this->subject->resolve($this->questionnaire($page), [8 => ['43']], 0.0));
    }

    #[Test]
    public function resolveDoesNotMatchASpecificAnswerTriggerWithoutATriggerAnswer(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SPECIFIC_ANSWER);

        self::assertNull($this->subject->resolve($this->questionnaire($page), [8 => ['42']], 0.0));
    }

    #[Test]
    public function resolveRequiresBothHalvesOfACombinationTrigger(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_COMBINATION);
        $page->setScoreMin(10.0);
        $page->setScoreMax(20.0);
        $page->setTriggerAnswer($this->answerOption(42));

        $questionnaire = $this->questionnaire($page);

        self::assertSame($page, $this->subject->resolve($questionnaire, [8 => ['42']], 15.0), 'both halves');
        self::assertNull($this->subject->resolve($questionnaire, [8 => ['42']], 5.0), 'score outside range');
        self::assertNull($this->subject->resolve($questionnaire, [8 => ['43']], 15.0), 'answer not selected');
    }

    #[Test]
    public function resolveMatchesAScaleAnswerWithinTheRange(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SCALE_ANSWER);
        $page->setTriggerQuestion($this->question(7));
        $page->setTriggerScaleMin(3);
        $page->setTriggerScaleMax(5);

        $questionnaire = $this->questionnaire($page);

        self::assertSame($page, $this->subject->resolve($questionnaire, [7 => ['3']], 0.0), 'lower bound');
        self::assertSame($page, $this->subject->resolve($questionnaire, [7 => ['5']], 0.0), 'upper bound');
        self::assertNull($this->subject->resolve($questionnaire, [7 => ['2']], 0.0), 'below range');
        self::assertNull($this->subject->resolve($questionnaire, [7 => ['6']], 0.0), 'above range');
    }

    #[Test]
    public function resolveDoesNotMatchAScaleTriggerForAnUnansweredQuestion(): void
    {
        $page = $this->resultPage(ResultPage::TRIGGER_SCALE_ANSWER);
        $page->setTriggerQuestion($this->question(7));
        $page->setTriggerScaleMin(0);
        $page->setTriggerScaleMax(5);

        self::assertNull($this->subject->resolve($this->questionnaire($page), [], 0.0));
    }

    // ------------------------------------------------------- filterAdviceBlocks()

    #[Test]
    public function filterAdviceBlocksReturnsAnEmptyArrayWhenThereAreNoBlocks(): void
    {
        self::assertSame([], $this->subject->filterAdviceBlocks($this->resultPage(), [], 0.0));
    }

    #[Test]
    public function filterAdviceBlocksAlwaysKeepsAnAlwaysBlock(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($this->pageWith($block), [], 0.0));
    }

    #[Test]
    public function filterAdviceBlocksDropsABlockWithAnUnknownConditionType(): void
    {
        $block = $this->adviceBlock('something_else');

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [], 0.0));
    }

    #[Test]
    public function filterAdviceBlocksTreatsBothScoreBoundsAsInclusive(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SCORE_RANGE);
        $block->setScoreMin(10.0);
        $block->setScoreMax(20.0);

        $page = $this->pageWith($block);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($page, [], 10.0), 'lower bound');
        self::assertSame([$block], $this->subject->filterAdviceBlocks($page, [], 20.0), 'upper bound');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [], 9.99), 'just below');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [], 20.01), 'just above');
    }

    #[Test]
    public function filterAdviceBlocksKeepsASpecificAnswerBlockWhenTheAnswerWasSelected(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setTriggerAnswer($this->answerOption(42));

        self::assertSame(
            [$block],
            $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['42']], 0.0)
        );
    }

    /**
     * A checkbox question stores several option uids at once; matching one of
     * them is enough.
     */
    #[Test]
    public function filterAdviceBlocksMatchesOneOptionOutOfAMultipleChoiceAnswer(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setTriggerAnswer($this->answerOption(42));

        self::assertSame(
            [$block],
            $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['40', '42', '44']], 0.0)
        );
    }

    #[Test]
    public function filterAdviceBlocksDropsASpecificAnswerBlockWhenTheAnswerWasNotSelected(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setTriggerAnswer($this->answerOption(42));

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['43']], 0.0));
    }

    #[Test]
    public function filterAdviceBlocksDropsASpecificAnswerBlockWhenNothingWasAnsweredAtAll(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setTriggerAnswer($this->answerOption(42));

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [], 0.0));
    }

    #[Test]
    public function filterAdviceBlocksDropsASpecificAnswerBlockWithoutATriggerAnswer(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['42']], 0.0));
    }

    // ------------------------------------------------- inverted specific answer

    #[Test]
    public function anInvertedBlockIsHiddenWhenTheAnswerWasSelected(): void
    {
        $block = $this->invertedBlock(42);

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['42']], 0.0));
    }

    #[Test]
    public function anInvertedBlockIsShownWhenAnotherAnswerWasSelected(): void
    {
        $block = $this->invertedBlock(42);

        self::assertSame(
            [$block],
            $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['43']], 0.0)
        );
    }

    /**
     * The checkbox question this is built for is optional. Someone who ticks
     * nothing has not told us their choice, so the block belongs on the page —
     * "not ticked" and "not answered" are the same thing here.
     */
    #[Test]
    public function anInvertedBlockIsShownWhenTheQuestionWasLeftBlank(): void
    {
        $block = $this->invertedBlock(42);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($this->pageWith($block), [], 0.0));
    }

    #[Test]
    public function anInvertedBlockIsHiddenWhenTheAnswerIsOneOfSeveralTicked(): void
    {
        $block = $this->invertedBlock(42);

        self::assertSame(
            [],
            $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['40', '42', '44']], 0.0)
        );
    }

    /**
     * A block without a trigger answer is misconfigured. Inverting must not
     * turn that into "show to everyone", so it stays hidden either way.
     */
    #[Test]
    public function anInvertedBlockWithoutATriggerAnswerStaysHidden(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setNegateCondition(true);

        self::assertSame([], $this->subject->filterAdviceBlocks($this->pageWith($block), [8 => ['42']], 0.0));
    }

    /**
     * The checkbox is only offered for `specific_answer`, but hiding a field in
     * FormEngine does not clear its stored value. A leftover 1 on any other
     * condition type must therefore do nothing at all.
     */
    #[Test]
    public function invertingHasNoEffectOnAnAlwaysBlock(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);
        $block->setNegateCondition(true);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($this->pageWith($block), [], 0.0));
    }

    #[Test]
    public function invertingHasNoEffectOnAScoreRangeBlock(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SCORE_RANGE);
        $block->setScoreMin(10.0);
        $block->setScoreMax(20.0);
        $block->setNegateCondition(true);

        $page = $this->pageWith($block);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($page, [], 15.0), 'within range');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [], 5.0), 'outside range');
    }

    #[Test]
    public function invertingHasNoEffectOnAScaleRangeBlock(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SCALE_RANGE);
        $block->setTriggerQuestion($this->question(7));
        $block->setTriggerScaleMin(3);
        $block->setTriggerScaleMax(5);
        $block->setNegateCondition(true);

        $page = $this->pageWith($block);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($page, [7 => ['4']], 0.0), 'within range');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [7 => ['6']], 0.0), 'outside range');
    }

    #[Test]
    public function aBlockIsNotInvertedByDefault(): void
    {
        self::assertFalse($this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER)->isNegateCondition());
    }

    #[Test]
    public function filterAdviceBlocksKeepsAScaleRangeBlockWithinTheRange(): void
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SCALE_RANGE);
        $block->setTriggerQuestion($this->question(7));
        $block->setTriggerScaleMin(3);
        $block->setTriggerScaleMax(5);

        $page = $this->pageWith($block);

        self::assertSame([$block], $this->subject->filterAdviceBlocks($page, [7 => ['4']], 0.0), 'within range');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [7 => ['6']], 0.0), 'above range');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [], 0.0), 'unanswered');
    }

    /**
     * The order the editor gave the blocks is the order the visitor sees.
     */
    #[Test]
    public function filterAdviceBlocksKeepsTheConfiguredOrder(): void
    {
        $first = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);
        $hidden = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $hidden->setTriggerAnswer($this->answerOption(42));
        $last = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);

        self::assertSame(
            [$first, $last],
            $this->subject->filterAdviceBlocks($this->pageWith($first, $hidden, $last), [], 0.0)
        );
    }

    // --------------------------------------------------------------- group headings

    /**
     * A heading has no condition of its own — it rides along on its members.
     */
    #[Test]
    public function filterAdviceBlocksKeepsAGroupHeadingWhenOneOfItsBlocksIsVisible(): void
    {
        $heading = $this->groupHeader(1);
        $member = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);
        $member->setGroupHeader($heading);

        self::assertSame(
            [$heading, $member],
            $this->subject->filterAdviceBlocks($this->pageWith($heading, $member), [], 0.0)
        );
    }

    /**
     * The point of deriving the heading from its members: a group whose blocks are all hidden
     * must not leave an empty heading on the page.
     */
    #[Test]
    public function filterAdviceBlocksDropsAGroupHeadingWhenAllOfItsBlocksAreHidden(): void
    {
        $heading = $this->groupHeader(1);
        $member = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $member->setTriggerAnswer($this->answerOption(42));
        $member->setGroupHeader($heading);

        self::assertSame(
            [],
            $this->subject->filterAdviceBlocks($this->pageWith($heading, $member), [8 => ['43']], 0.0)
        );
    }

    #[Test]
    public function filterAdviceBlocksDropsAGroupHeadingThatNoBlockPointsAt(): void
    {
        $heading = $this->groupHeader(1);
        $unrelated = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);

        self::assertSame(
            [$unrelated],
            $this->subject->filterAdviceBlocks($this->pageWith($heading, $unrelated), [], 0.0)
        );
    }

    /**
     * Belonging to a group changes the heading level, never the visibility: a block still has to
     * satisfy its own condition.
     */
    #[Test]
    public function filterAdviceBlocksStillAppliesTheOwnConditionOfAGroupedBlock(): void
    {
        $heading = $this->groupHeader(1);
        $visible = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $visible->setTriggerAnswer($this->answerOption(42));
        $visible->setGroupHeader($heading);
        $hidden = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $hidden->setTriggerAnswer($this->answerOption(43));
        $hidden->setGroupHeader($heading);

        self::assertSame(
            [$heading, $visible],
            $this->subject->filterAdviceBlocks($this->pageWith($heading, $visible, $hidden), [8 => ['42']], 0.0)
        );
    }

    #[Test]
    public function filterAdviceBlocksKeepsOnlyTheGroupHeadingsThatStillHaveAVisibleBlock(): void
    {
        $firstHeading = $this->groupHeader(1);
        $firstMember = $this->adviceBlock(AdviceBlock::CONDITION_ALWAYS);
        $firstMember->setGroupHeader($firstHeading);
        $secondHeading = $this->groupHeader(2);
        $secondMember = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $secondMember->setTriggerAnswer($this->answerOption(42));
        $secondMember->setGroupHeader($secondHeading);

        $page = $this->pageWith($firstHeading, $firstMember, $secondHeading, $secondMember);

        self::assertSame(
            [$firstHeading, $firstMember],
            $this->subject->filterAdviceBlocks($page, [], 0.0)
        );
        self::assertSame(
            [$firstHeading, $firstMember, $secondHeading, $secondMember],
            $this->subject->filterAdviceBlocks($page, [8 => ['42']], 0.0)
        );
    }

    /**
     * An inverted member counts like any other: it decides the heading through its outcome, not
     * through its configuration.
     */
    #[Test]
    public function filterAdviceBlocksKeepsAGroupHeadingWhoseOnlyVisibleBlockIsInverted(): void
    {
        $heading = $this->groupHeader(1);
        $member = $this->invertedBlock(42);
        $member->setGroupHeader($heading);

        $page = $this->pageWith($heading, $member);

        self::assertSame([$heading, $member], $this->subject->filterAdviceBlocks($page, [], 0.0), 'not given');
        self::assertSame([], $this->subject->filterAdviceBlocks($page, [8 => ['42']], 0.0), 'given');
    }

    // ------------------------------------------------------------------ fixtures

    private function groupHeader(int $uid): AdviceBlock
    {
        $heading = $this->adviceBlock(AdviceBlock::CONDITION_GROUP_HEADER);
        $heading->_setProperty('uid', $uid);

        return $heading;
    }

    private function questionnaire(ResultPage ...$resultPages): Questionnaire
    {
        $questionnaire = new Questionnaire();

        foreach ($resultPages as $resultPage) {
            $questionnaire->addResultPage($resultPage);
        }

        return $questionnaire;
    }

    private function resultPage(string $triggerType = ResultPage::TRIGGER_CATCH_ALL): ResultPage
    {
        $resultPage = new ResultPage();
        $resultPage->setTriggerType($triggerType);

        return $resultPage;
    }

    private function pageWith(AdviceBlock ...$blocks): ResultPage
    {
        $resultPage = $this->resultPage();

        foreach ($blocks as $block) {
            $resultPage->addAdviceBlock($block);
        }

        return $resultPage;
    }

    private function adviceBlock(string $conditionType): AdviceBlock
    {
        $block = new AdviceBlock();
        $block->setConditionType($conditionType);

        return $block;
    }

    private function invertedBlock(int $triggerAnswerUid): AdviceBlock
    {
        $block = $this->adviceBlock(AdviceBlock::CONDITION_SPECIFIC_ANSWER);
        $block->setTriggerAnswer($this->answerOption($triggerAnswerUid));
        $block->setNegateCondition(true);

        return $block;
    }

    private function answerOption(int $uid): AnswerOption
    {
        $option = new AnswerOption();
        $option->_setProperty('uid', $uid);

        return $option;
    }

    private function question(int $uid): Question
    {
        $question = new Question();
        $question->_setProperty('uid', $uid);

        return $question;
    }
}
