<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * AdviceBlock
 *
 * A conditional content section within an inline Result Page.
 *
 * conditionType drives which fields are relevant:
 *   - always          → always shown
 *   - score_range     → shown when total score is within scoreMin–scoreMax
 *   - specific_answer → shown when the visitor selected triggerAnswer
 *   - scale_range     → shown when a scale question answer is within triggerScaleMin–triggerScaleMax
 *   - group_header    → a heading above the blocks that point to it through groupHeader; shown
 *                       only when at least one of those blocks is visible
 *
 * Maps to: tx_pnquestionnaire_advice_block
 */
class AdviceBlock extends AbstractEntity
{
    public const CONDITION_ALWAYS = 'always';
    public const CONDITION_SCORE_RANGE = 'score_range';
    public const CONDITION_SPECIFIC_ANSWER = 'specific_answer';
    public const CONDITION_SCALE_RANGE = 'scale_range';
    public const CONDITION_GROUP_HEADER = 'group_header';

    protected string $headline = '';
    protected string $bodyText = '';
    protected string $conditionType = self::CONDITION_ALWAYS;
    protected ?AdviceBlock $groupHeader = null;
    protected float $scoreMin = 0.0;
    protected float $scoreMax = 0.0;
    protected ?AnswerOption $triggerAnswer = null;
    protected bool $negateCondition = false;
    protected ?Question $triggerQuestion = null;
    protected int $triggerScaleMin = 0;
    protected int $triggerScaleMax = 0;
    protected int $sortOrder = 0;

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): void
    {
        $this->headline = $headline;
    }

    public function getBodyText(): string
    {
        return $this->bodyText;
    }

    public function setBodyText(string $bodyText): void
    {
        $this->bodyText = $bodyText;
    }

    public function getConditionType(): string
    {
        return $this->conditionType;
    }

    public function setConditionType(string $conditionType): void
    {
        $this->conditionType = $conditionType;
    }

    public function isAlways(): bool
    {
        return $this->conditionType === self::CONDITION_ALWAYS;
    }

    public function isScoreRange(): bool
    {
        return $this->conditionType === self::CONDITION_SCORE_RANGE;
    }

    public function isSpecificAnswer(): bool
    {
        return $this->conditionType === self::CONDITION_SPECIFIC_ANSWER;
    }

    public function isScaleRange(): bool
    {
        return $this->conditionType === self::CONDITION_SCALE_RANGE;
    }

    public function isGroupHeader(): bool
    {
        return $this->conditionType === self::CONDITION_GROUP_HEADER;
    }

    /**
     * The group heading this block belongs to, or null when it stands on its own.
     * A block inside a group renders one heading level deeper than the heading itself.
     */
    public function getGroupHeader(): ?AdviceBlock
    {
        return $this->groupHeader;
    }

    public function setGroupHeader(?AdviceBlock $groupHeader): void
    {
        $this->groupHeader = $groupHeader;
    }

    public function getScoreMin(): float
    {
        return $this->scoreMin;
    }

    public function setScoreMin(float $scoreMin): void
    {
        $this->scoreMin = $scoreMin;
    }

    public function getScoreMax(): float
    {
        return $this->scoreMax;
    }

    public function setScoreMax(float $scoreMax): void
    {
        $this->scoreMax = $scoreMax;
    }

    public function getTriggerAnswer(): ?AnswerOption
    {
        return $this->triggerAnswer;
    }

    public function setTriggerAnswer(?AnswerOption $triggerAnswer): void
    {
        $this->triggerAnswer = $triggerAnswer;
    }

    /**
     * Whether the `specific_answer` condition is inverted: show the block to
     * everyone who did *not* give that answer. Ignored for the other condition
     * types.
     */
    public function isNegateCondition(): bool
    {
        return $this->negateCondition;
    }

    public function setNegateCondition(bool $negateCondition): void
    {
        $this->negateCondition = $negateCondition;
    }

    public function getTriggerQuestion(): ?Question
    {
        return $this->triggerQuestion;
    }

    public function setTriggerQuestion(?Question $triggerQuestion): void
    {
        $this->triggerQuestion = $triggerQuestion;
    }

    public function getTriggerScaleMin(): int
    {
        return $this->triggerScaleMin;
    }

    public function setTriggerScaleMin(int $triggerScaleMin): void
    {
        $this->triggerScaleMin = $triggerScaleMin;
    }

    public function getTriggerScaleMax(): int
    {
        return $this->triggerScaleMax;
    }

    public function setTriggerScaleMax(int $triggerScaleMax): void
    {
        $this->triggerScaleMax = $triggerScaleMax;
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
