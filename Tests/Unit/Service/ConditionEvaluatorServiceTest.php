<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ProudNerds\PnQuestionnaire\Domain\Model\AnswerOption;
use ProudNerds\PnQuestionnaire\Domain\Model\Condition;
use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use ProudNerds\PnQuestionnaire\Service\ConditionEvaluatorService;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConditionEvaluatorServiceTest extends UnitTestCase
{
    private ConditionEvaluatorService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ConditionEvaluatorService();
    }

    // ---------------------------------------------------------------- isVisible()

    #[Test]
    public function aQuestionWithoutConditionsIsVisible(): void
    {
        self::assertTrue($this->subject->isVisible($this->question(1), []));
    }

    #[Test]
    public function aQuestionIsVisibleWhenItsConditionAnswerWasSelected(): void
    {
        $question = $this->question(2, $this->answerCondition(1, 10));

        self::assertTrue($this->subject->isVisible($question, [1 => ['10']]));
    }

    #[Test]
    public function aQuestionIsHiddenWhenItsConditionAnswerWasNotSelected(): void
    {
        $question = $this->question(2, $this->answerCondition(1, 10));

        self::assertFalse($this->subject->isVisible($question, [1 => ['11']]));
    }

    #[Test]
    public function aQuestionIsHiddenWhileTheQuestionItDependsOnIsUnanswered(): void
    {
        $question = $this->question(2, $this->answerCondition(1, 10));

        self::assertFalse($this->subject->isVisible($question, []));
    }

    /**
     * A condition that lost its references would otherwise hide the question
     * for good, with nothing in the backend explaining why.
     */
    #[Test]
    public function anIncompleteConditionDoesNotHideTheQuestion(): void
    {
        $condition = new Condition();
        $condition->setConditionType(Condition::CONDITION_TYPE_SPECIFIC_ANSWER);

        self::assertTrue($this->subject->isVisible($this->question(2, $condition), []));
    }

    #[Test]
    public function anAndOperatorRequiresBothConditions(): void
    {
        $first = $this->answerCondition(1, 10, 1);
        $second = $this->answerCondition(2, 20, 2, Condition::OPERATOR_AND);

        $question = $this->question(3, $first, $second);

        self::assertTrue($this->subject->isVisible($question, [1 => ['10'], 2 => ['20']]), 'both');
        self::assertFalse($this->subject->isVisible($question, [1 => ['10'], 2 => ['21']]), 'second fails');
        self::assertFalse($this->subject->isVisible($question, [1 => ['11'], 2 => ['20']]), 'first fails');
    }

    #[Test]
    public function anOrOperatorAcceptsEitherCondition(): void
    {
        $first = $this->answerCondition(1, 10, 1);
        $second = $this->answerCondition(2, 20, 2, Condition::OPERATOR_OR);

        $question = $this->question(3, $first, $second);

        self::assertTrue($this->subject->isVisible($question, [1 => ['10'], 2 => ['21']]), 'first only');
        self::assertTrue($this->subject->isVisible($question, [1 => ['11'], 2 => ['20']]), 'second only');
        self::assertFalse($this->subject->isVisible($question, [1 => ['11'], 2 => ['21']]), 'neither');
    }

    /**
     * The operator of the first condition is meaningless — there is nothing
     * before it to combine with — so an AND on it must not hide the question.
     */
    #[Test]
    public function theOperatorOfTheFirstConditionIsIgnored(): void
    {
        $condition = $this->answerCondition(1, 10, 1, Condition::OPERATOR_AND);

        self::assertTrue($this->subject->isVisible($this->question(2, $condition), [1 => ['10']]));
    }

    /**
     * Conditions are combined left to right in sort order, so the order in
     * which the editor put them changes the outcome.
     */
    #[Test]
    public function conditionsAreCombinedInSortOrderAndNotInStorageOrder(): void
    {
        $stored = $this->answerCondition(2, 20, 2, Condition::OPERATOR_AND);
        $first = $this->answerCondition(1, 10, 1, Condition::OPERATOR_OR);

        // Added in reverse: sort_order 2 first, sort_order 1 second.
        $question = $this->question(3, $stored, $first);

        // Evaluated in sort order this is "10 selected AND 20 selected".
        self::assertFalse($this->subject->isVisible($question, [1 => ['10'], 2 => ['21']]));
        self::assertTrue($this->subject->isVisible($question, [1 => ['10'], 2 => ['20']]));
    }

    // ------------------------------------------------------- scale_range conditions

    #[Test]
    #[DataProvider('scaleOperatorProvider')]
    public function aScaleConditionComparesTheAnswerAgainstTheThreshold(
        string $operator,
        string $answer,
        bool $expected
    ): void {
        $condition = new Condition();
        $condition->setConditionType(Condition::CONDITION_TYPE_SCALE_RANGE);
        $condition->setReferenceQuestion($this->question(1));
        $condition->setScaleOperator($operator);
        $condition->setScaleValue(3);

        self::assertSame($expected, $this->subject->isVisible($this->question(2, $condition), [1 => [$answer]]));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function scaleOperatorProvider(): array
    {
        return [
            'greater or equal, on the threshold' => ['>=', '3', true],
            'greater or equal, below'            => ['>=', '2', false],
            'less or equal, on the threshold'    => ['<=', '3', true],
            'less or equal, above'               => ['<=', '4', false],
            'greater than, on the threshold'     => ['>', '3', false],
            'greater than, above'                => ['>', '4', true],
            'less than, on the threshold'        => ['<', '3', false],
            'less than, below'                   => ['<', '2', true],
            'equal, on the threshold'            => ['=', '3', true],
            'equal, off by one'                  => ['=', '4', false],
            'unknown operator never matches'     => ['!=', '3', false],
        ];
    }

    #[Test]
    public function aScaleConditionHidesTheQuestionWhileTheScaleIsUnanswered(): void
    {
        $condition = new Condition();
        $condition->setConditionType(Condition::CONDITION_TYPE_SCALE_RANGE);
        $condition->setReferenceQuestion($this->question(1));
        $condition->setScaleOperator('>=');
        $condition->setScaleValue(0);

        self::assertFalse($this->subject->isVisible($this->question(2, $condition), []));
    }

    #[Test]
    public function aScaleConditionWithoutAReferenceQuestionDoesNotHideTheQuestion(): void
    {
        $condition = new Condition();
        $condition->setConditionType(Condition::CONDITION_TYPE_SCALE_RANGE);
        $condition->setScaleOperator('>=');
        $condition->setScaleValue(3);

        self::assertTrue($this->subject->isVisible($this->question(2, $condition), []));
    }

    // ------------------------------------------------------- getVisibleQuestions()

    #[Test]
    public function getVisibleQuestionsKeepsTheStorageOrderAndDropsHiddenQuestions(): void
    {
        $first = $this->question(1);
        $hidden = $this->question(2, $this->answerCondition(1, 10));
        $last = $this->question(3);

        $questions = new ObjectStorage();
        $questions->attach($first);
        $questions->attach($hidden);
        $questions->attach($last);

        self::assertSame([$first, $last], $this->subject->getVisibleQuestions($questions, []));
        self::assertSame(
            [$first, $hidden, $last],
            $this->subject->getVisibleQuestions($questions, [1 => ['10']])
        );
    }

    #[Test]
    public function getVisibleQuestionsReturnsAnEmptyArrayForAnEmptyStorage(): void
    {
        self::assertSame([], $this->subject->getVisibleQuestions(new ObjectStorage(), []));
    }

    // ------------------------------------------------------------------ fixtures

    private function question(int $uid, Condition ...$conditions): Question
    {
        $question = new Question();
        $question->_setProperty('uid', $uid);

        foreach ($conditions as $condition) {
            $question->addCondition($condition);
        }

        return $question;
    }

    private function answerCondition(
        int $referenceQuestionUid,
        int $referenceAnswerUid,
        int $sortOrder = 1,
        string $operator = Condition::OPERATOR_AND
    ): Condition {
        $answer = new AnswerOption();
        $answer->_setProperty('uid', $referenceAnswerUid);

        $condition = new Condition();
        $condition->setConditionType(Condition::CONDITION_TYPE_SPECIFIC_ANSWER);
        $condition->setReferenceQuestion($this->question($referenceQuestionUid));
        $condition->setReferenceAnswer($answer);
        $condition->setSortOrder($sortOrder);
        $condition->setOperator($operator);

        return $condition;
    }
}
