<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Repository;

use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * QuestionnaireRepository
 *
 * Repository for Questionnaire domain objects.
 * Questionnaires are managed independently in the TYPO3 list module and
 * referenced by plugin instances via FlexForm selection.
 */
class QuestionnaireRepository extends Repository
{
    /**
     * Find all questionnaires ordered by title.
     *
     * @return QueryResultInterface|array
     */
    public function findAllOrderedByTitle(): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->setOrderings(['title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        return $query->execute();
    }

    /**
     * Find a questionnaire by its UID, ignoring storage page restrictions.
     * This is needed because questionnaires are typically stored on a dedicated
     * sysfolder while the plugin lives on a different page.
     */
    public function findByUidIgnoringStoragePage(int $uid): ?Questionnaire
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));
        /** @var Questionnaire|null $result */
        $result = $query->execute()->getFirst();
        return $result;
    }
}
