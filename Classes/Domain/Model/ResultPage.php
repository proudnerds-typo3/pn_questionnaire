<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * ResultPage
 *
 * Defines what happens when a questionnaire is completed. Result pages are
 * evaluated in sort order — the first match wins. A catch-all result should
 * always be placed last as a fallback.
 *
 * Two fields act as displayCond drivers:
 *   - `triggerType` controls which trigger fields are shown/relevant
 *   - `outcomeType` controls which outcome fields are shown/relevant
 *
 * Maps to: tx_pnquestionnaire_result_page
 */
class ResultPage extends AbstractEntity
{
    // --- Trigger type constants ---
    public const TRIGGER_CATCH_ALL = 'catch_all';
    public const TRIGGER_SCORE_RANGE = 'score_range';
    public const TRIGGER_SPECIFIC_ANSWER = 'specific_answer';
    public const TRIGGER_COMBINATION = 'combination';
    public const TRIGGER_SCALE_ANSWER = 'scale_answer';

    // --- Outcome type constants ---
    public const OUTCOME_INLINE = 'inline';
    public const OUTCOME_INTERNAL_PAGE = 'internal_page';
    public const OUTCOME_EXTERNAL_URL = 'external_url';
    public const OUTCOME_DOMAIN_RECORD = 'domain_record';

    /**
     * Internal editorial label — not shown to visitors.
     */
    protected string $title = '';

    /**
     * Evaluation order (first match wins).
     */
    protected int $sortOrder = 0;

    // --- Trigger fields ---

    /**
     * What condition triggers this result page.
     * catch_all | score_range | specific_answer | combination
     */
    protected string $triggerType = self::TRIGGER_CATCH_ALL;

    /**
     * Minimum score (inclusive) — relevant for score_range and combination.
     */
    protected float $scoreMin = 0.0;

    /**
     * Maximum score (inclusive) — relevant for score_range and combination.
     */
    protected float $scoreMax = 0.0;

    /**
     * The specific answer option that triggers this result — relevant for specific_answer and combination.
     */
    protected ?AnswerOption $triggerAnswer = null;

    /**
     * The scale question whose value is checked — relevant for scale_answer.
     */
    protected ?Question $triggerQuestion = null;

    /**
     * Minimum scale value (inclusive) — relevant for scale_answer.
     */
    protected int $triggerScaleMin = 0;

    /**
     * Maximum scale value (inclusive) — relevant for scale_answer.
     */
    protected int $triggerScaleMax = 0;

    // --- Outcome fields ---

    /**
     * How the result is presented to the visitor.
     * inline | internal_page | external_url | domain_record
     */
    protected string $outcomeType = self::OUTCOME_INLINE;

    /**
     * Result headline shown to visitor (inline outcome only).
     */
    protected string $headline = '';

    /**
     * Main result content — RTE (inline outcome only).
     */
    protected string $bodyText = '';

    /**
     * Call-to-action button label (inline outcome only).
     */
    protected string $ctaLabel = '';

    /**
     * Call-to-action button target URL (inline outcome only).
     */
    protected string $ctaLink = '';

    /**
     * Dynamic content sections shown conditionally within the inline result.
     *
     * @var ObjectStorage<AdviceBlock>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $adviceBlocks;

    /**
     * Target TYPO3 page UID to redirect to (internal_page outcome only).
     */
    protected int $pageUid = 0;

    /**
     * Target URL to redirect to (external_url outcome only).
     */
    protected string $externalUrl = '';

    /**
     * UID of the specific record to resolve (domain_record outcome only).
     */
    protected int $recordUid = 0;

    public function __construct()
    {
        $this->adviceBlocks = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getTriggerType(): string
    {
        return $this->triggerType;
    }

    public function setTriggerType(string $triggerType): void
    {
        $this->triggerType = $triggerType;
    }

    public function isCatchAll(): bool
    {
        return $this->triggerType === self::TRIGGER_CATCH_ALL;
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

    public function getOutcomeType(): string
    {
        return $this->outcomeType;
    }

    public function setOutcomeType(string $outcomeType): void
    {
        $this->outcomeType = $outcomeType;
    }

    public function isInlineOutcome(): bool
    {
        return $this->outcomeType === self::OUTCOME_INLINE;
    }

    public function isRedirectOutcome(): bool
    {
        return in_array($this->outcomeType, [
            self::OUTCOME_INTERNAL_PAGE,
            self::OUTCOME_EXTERNAL_URL,
            self::OUTCOME_DOMAIN_RECORD,
        ], true);
    }

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

    public function getCtaLabel(): string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(string $ctaLabel): void
    {
        $this->ctaLabel = $ctaLabel;
    }

    public function getCtaLink(): string
    {
        return $this->ctaLink;
    }

    public function setCtaLink(string $ctaLink): void
    {
        $this->ctaLink = $ctaLink;
    }

    /**
     * @return ObjectStorage<AdviceBlock>
     */
    public function getAdviceBlocks(): ObjectStorage
    {
        return $this->adviceBlocks;
    }

    /**
     * @param ObjectStorage<AdviceBlock> $adviceBlocks
     */
    public function setAdviceBlocks(ObjectStorage $adviceBlocks): void
    {
        $this->adviceBlocks = $adviceBlocks;
    }

    public function addAdviceBlock(AdviceBlock $adviceBlock): void
    {
        $this->adviceBlocks->attach($adviceBlock);
    }

    public function removeAdviceBlock(AdviceBlock $adviceBlock): void
    {
        $this->adviceBlocks->detach($adviceBlock);
    }

    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    public function setPageUid(int $pageUid): void
    {
        $this->pageUid = $pageUid;
    }

    public function getExternalUrl(): string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(string $externalUrl): void
    {
        $this->externalUrl = $externalUrl;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function setRecordUid(int $recordUid): void
    {
        $this->recordUid = $recordUid;
    }
}
