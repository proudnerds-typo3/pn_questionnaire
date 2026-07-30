<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Questionnaire
 *
 * Top-level container holding all questions, settings and result pages.
 * Multiple plugin instances can point to the same Questionnaire record,
 * allowing reuse across pages with different display settings per placement.
 *
 * Maps to: tx_pnquestionnaire_questionnaire
 */
class Questionnaire extends AbstractEntity
{
    /**
     * Internal editorial label — not shown to visitors.
     */
    protected string $title = '';

    /**
     * Shown on the optional intro screen before Q1.
     */
    protected string $introductionText = '';

    /**
     * Ordered list of question records (drag-and-drop sortable).
     *
     * @var ObjectStorage<Question>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $questions;

    /**
     * Ordered list of result page records (evaluated top to bottom, first match wins).
     *
     * @var ObjectStorage<ResultPage>
     */
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $resultPages;

    public function __construct()
    {
        $this->questions = new ObjectStorage();
        $this->resultPages = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getIntroductionText(): string
    {
        return $this->introductionText;
    }

    public function setIntroductionText(string $introductionText): void
    {
        $this->introductionText = $introductionText;
    }

    /**
     * @return ObjectStorage<Question>
     */
    public function getQuestions(): ObjectStorage
    {
        return $this->questions;
    }

    /**
     * @param ObjectStorage<Question> $questions
     */
    public function setQuestions(ObjectStorage $questions): void
    {
        $this->questions = $questions;
    }

    public function addQuestion(Question $question): void
    {
        $this->questions->attach($question);
    }

    public function removeQuestion(Question $question): void
    {
        $this->questions->detach($question);
    }

    /**
     * @return ObjectStorage<ResultPage>
     */
    public function getResultPages(): ObjectStorage
    {
        return $this->resultPages;
    }

    /**
     * @param ObjectStorage<ResultPage> $resultPages
     */
    public function setResultPages(ObjectStorage $resultPages): void
    {
        $this->resultPages = $resultPages;
    }

    public function addResultPage(ResultPage $resultPage): void
    {
        $this->resultPages->attach($resultPage);
    }

    public function removeResultPage(ResultPage $resultPage): void
    {
        $this->resultPages->detach($resultPage);
    }
}
