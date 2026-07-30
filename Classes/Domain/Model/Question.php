<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Question
 *
 * A single question/step within a Questionnaire. One record per question,
 * living inline inside a Questionnaire. Supports five answer types controlled
 * by the `type` field.
 *
 * Maps to: tx_pnquestionnaire_question
 */
class Question extends AbstractEntity
{
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_SCALE = 'scale';
    public const TYPE_INFORMATIONAL = 'informational';

    /**
     * The question shown to the visitor.
     */
    protected string $questionText = '';

    /**
     * Optional short description or instruction below the question.
     */
    protected string $helpText = '';

    /**
     * Optional rich content element for additional context (UID of tt_content record).
     * Kept as int because TYPO3 has no Extbase model for tt_content.
     */
    protected int $ttContentUid = 0;

    /**
     * Answer type: single_choice | multiple_choice | yes_no | scale | informational.
     */
    protected string $type = self::TYPE_SINGLE_CHOICE;

    /**
     * Lower bound for scale questions (e.g. 1).
     */
    protected int $scaleMin = 1;

    /**
     * Upper bound for scale questions (e.g. 10).
     */
    protected int $scaleMax = 10;

    /**
     * How the scale is rendered on the frontend: 'radio' (default) or 'range'.
     */
    protected string $scaleDisplay = 'range';

    /**
     * Whether the visitor must answer this question before proceeding.
     */
    protected bool $required = false;

    /**
     * Position within the questionnaire.
     */
    protected int $sortOrder = 0;

    /**
     * Selectable answers (not relevant for informational type).
     *
     * @var ObjectStorage<AnswerOption>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $answerOptions;

    /**
     * Visibility rules — empty means always show.
     *
     * @var ObjectStorage<Condition>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $conditions;

    public function __construct()
    {
        $this->answerOptions = new ObjectStorage();
        $this->conditions = new ObjectStorage();
    }

    public function getQuestionText(): string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): void
    {
        $this->questionText = $questionText;
    }

    public function getHelpText(): string
    {
        return $this->helpText;
    }

    public function setHelpText(string $helpText): void
    {
        $this->helpText = $helpText;
    }

    public function getTtContentUid(): int
    {
        return $this->ttContentUid;
    }

    public function setTtContentUid(int $ttContentUid): void
    {
        $this->ttContentUid = $ttContentUid;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isInformational(): bool
    {
        return $this->type === self::TYPE_INFORMATIONAL;
    }

    public function isScale(): bool
    {
        return $this->type === self::TYPE_SCALE;
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function getScaleMin(): int
    {
        return $this->scaleMin;
    }

    public function setScaleMin(int $scaleMin): void
    {
        $this->scaleMin = $scaleMin;
    }

    public function getScaleMax(): int
    {
        return $this->scaleMax;
    }

    public function setScaleMax(int $scaleMax): void
    {
        $this->scaleMax = $scaleMax;
    }

    public function getScaleDisplay(): string
    {
        return $this->scaleDisplay;
    }

    public function setScaleDisplay(string $scaleDisplay): void
    {
        $this->scaleDisplay = $scaleDisplay;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return ObjectStorage<AnswerOption>
     */
    public function getAnswerOptions(): ObjectStorage
    {
        return $this->answerOptions;
    }

    /**
     * @param ObjectStorage<AnswerOption> $answerOptions
     */
    public function setAnswerOptions(ObjectStorage $answerOptions): void
    {
        $this->answerOptions = $answerOptions;
    }

    public function addAnswerOption(AnswerOption $answerOption): void
    {
        $this->answerOptions->attach($answerOption);
    }

    public function removeAnswerOption(AnswerOption $answerOption): void
    {
        $this->answerOptions->detach($answerOption);
    }

    /**
     * @return ObjectStorage<Condition>
     */
    public function getConditions(): ObjectStorage
    {
        return $this->conditions;
    }

    /**
     * @param ObjectStorage<Condition> $conditions
     */
    public function setConditions(ObjectStorage $conditions): void
    {
        $this->conditions = $conditions;
    }

    public function addCondition(Condition $condition): void
    {
        $this->conditions->attach($condition);
    }

    public function removeCondition(Condition $condition): void
    {
        $this->conditions->detach($condition);
    }

    /**
     * Returns true when this question has at least one visibility condition.
     */
    public function hasConditions(): bool
    {
        return $this->conditions->count() > 0;
    }
}
