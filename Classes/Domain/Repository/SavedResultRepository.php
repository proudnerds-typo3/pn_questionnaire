<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Repository;

use ProudNerds\PnQuestionnaire\Domain\Model\SavedResult;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<SavedResult>
 */
class SavedResultRepository extends Repository
{
    /**
     * Find a stored run by its token, ignoring storage page restrictions: the runs
     * live in their own folder while the plugin sits on a content page.
     *
     * Soft-deleted rows stay hidden by the default query restrictions, so a run an
     * editor discarded reads as "not found" — which is the wanted behaviour.
     */
    public function findByTokenIgnoringStoragePage(string $token): ?SavedResult
    {
        if ($token === '') {
            return null;
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('token', $token));

        /** @var SavedResult|null $result */
        $result = $query->execute()->getFirst();

        return $result;
    }
}
