<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * AnswerOption
 *
 * A selectable answer within a Question. Lives inline inside a Question.
 * Carries an optional numeric score used when scoring is enabled on the
 * parent Questionnaire.
 *
 * Maps to: tx_pnquestionnaire_answer_option
 */
class AnswerOption extends AbstractEntity
{
    /**
     * The text shown to the visitor.
     */
    protected string $label = '';

    /**
     * Internal identifier used in conditions and result triggers.
     */
    protected string $value = '';

    /**
     * Optional score (positive or negative, integer or decimal).
     * Only relevant when scoring is enabled on the parent questionnaire.
     */
    protected float $score = 0.0;

    /**
     * Display order within the question.
     */
    protected int $sortOrder = 0;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
