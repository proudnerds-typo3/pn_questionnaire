<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Condition
 *
 * Visibility rule attached to a Question. Defines that the parent Question
 * is only shown when the visitor has previously selected a specific answer
 * to a reference question.
 *
 * Multiple conditions on the same question are combined using their `operator`
 * (AND / OR). When no conditions are present the question is always shown.
 *
 * Maps to: tx_pnquestionnaire_condition
 */
class Condition extends AbstractEntity
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR = 'OR';

    public const CONDITION_TYPE_SPECIFIC_ANSWER = 'specific_answer';
    public const CONDITION_TYPE_SCALE_RANGE = 'scale_range';

    /**
     * The earlier question whose answer is being checked.
     * Only questions appearing before the parent question in sort order are valid.
     */
    protected ?Question $referenceQuestion = null;

    /**
     * The specific answer option that must have been selected.
     */
    protected ?AnswerOption $referenceAnswer = null;

    /**
     * How this condition combines with sibling conditions on the same question.
     * AND = all conditions must pass; OR = at least one condition must pass.
     */
    protected string $operator = self::OPERATOR_AND;

    /**
     * Evaluation order when multiple conditions are present.
     */
    protected int $sortOrder = 0;

    /**
     * Type of condition: specific_answer (default) or scale_range.
     */
    protected string $conditionType = self::CONDITION_TYPE_SPECIFIC_ANSWER;

    /**
     * Comparison operator for scale_range conditions (>=, <=, >, <, =).
     */
    protected string $scaleOperator = '>=';

    /**
     * Numeric threshold for scale_range conditions.
     */
    protected int $scaleValue = 0;

    public function getReferenceQuestion(): ?Question
    {
        return $this->referenceQuestion;
    }

    public function setReferenceQuestion(?Question $referenceQuestion): void
    {
        $this->referenceQuestion = $referenceQuestion;
    }

    public function getReferenceAnswer(): ?AnswerOption
    {
        return $this->referenceAnswer;
    }

    public function setReferenceAnswer(?AnswerOption $referenceAnswer): void
    {
        $this->referenceAnswer = $referenceAnswer;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    public function isAndOperator(): bool
    {
        return $this->operator === self::OPERATOR_AND;
    }

    public function isOrOperator(): bool
    {
        return $this->operator === self::OPERATOR_OR;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    public function setConditionType(string $conditionType): void
    {
        $this->conditionType = $conditionType;
    }

    public function isScaleRange(): bool
    {
        return $this->conditionType === self::CONDITION_TYPE_SCALE_RANGE;
    }

    public function getScaleOperator(): string
    {
        return $this->scaleOperator;
    }

    public function setScaleOperator(string $scaleOperator): void
    {
        $this->scaleOperator = $scaleOperator;
    }

    public function getScaleValue(): int
    {
        return $this->scaleValue;
    }

    public function setScaleValue(int $scaleValue): void
    {
        $this->scaleValue = $scaleValue;
    }
}
