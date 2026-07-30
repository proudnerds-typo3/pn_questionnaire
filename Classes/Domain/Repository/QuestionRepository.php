<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Repository;

use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * QuestionRepository
 *
 * Repository for Question domain objects.
 *
 * Note: In most flows questions are loaded via the inline ObjectStorage on the
 * parent Questionnaire. These standalone finders are provided for cases where
 * questions need to be queried independently (e.g. condition evaluation, progress
 * calculation) without traversing the full object graph.
 */
class QuestionRepository extends Repository
{
    /**
     * Find all questions belonging to a questionnaire, ordered by sort_order.
     *
     * @param Questionnaire $questionnaire
     * @return QueryResultInterface|array
     */
    public function findByQuestionnaire(Questionnaire $questionnaire): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('questionnaire', $questionnaire->getUid()));
        $query->setOrderings(['sortOrder' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Find all questions for a questionnaire UID, ordered by sort_order.
     * Use this variant when you only have the UID available.
     *
     * @param int $questionnaireUid
     * @return QueryResultInterface|array
     */
    public function findByQuestionnaireUid(int $questionnaireUid): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('questionnaire', $questionnaireUid));
        $query->setOrderings(['sortOrder' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Find a single question by UID, respecting language and workspace overlays.
     */
    public function findByUidStrict(int $uid): ?Question
    {
        $query = $this->createQuery();
        $query->matching($query->equals('uid', $uid));
        /** @var Question|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }
}
