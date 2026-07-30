<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Repository;

use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * ResultPageRepository
 *
 * Repository for ResultPage domain objects.
 *
 * Note: In most flows result pages are loaded via the inline ObjectStorage on the
 * parent Questionnaire and evaluated in memory. These standalone finders are
 * provided for cases where result pages need to be queried independently.
 */
class ResultPageRepository extends Repository
{
    /**
     * Find all result pages for a questionnaire, ordered by sort_order (evaluation order).
     * The result resolver service iterates these in order — first match wins.
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
     * Find all result pages for a questionnaire UID, ordered by sort_order.
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
     * Find catch-all result pages for a questionnaire (fallback when no other result matches).
     *
     * @param Questionnaire $questionnaire
     * @return QueryResultInterface|array
     */
    public function findCatchAllByQuestionnaire(Questionnaire $questionnaire): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('questionnaire', $questionnaire->getUid()),
                $query->equals('triggerType', ResultPage::TRIGGER_CATCH_ALL)
            )
        );
        $query->setOrderings(['sortOrder' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }
}
