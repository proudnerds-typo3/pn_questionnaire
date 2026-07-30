<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\SavedResult;
use ProudNerds\PnQuestionnaire\Domain\Repository\SavedResultRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Stores a completed questionnaire run and retrieves it again by token.
 *
 * A run is stored on every completion rather than on request, so the token is kept
 * in the visitor's session: without it a refresh of the result page would write a
 * second row with a second token. Coming back through the saved link and changing
 * answers therefore updates the existing row and moves its expiry along.
 */
class ResultStorageService
{
    /**
     * Retention period used when no setting provides one. The FlexForm and
     * TypoScript fields carry the same number.
     */
    public const DEFAULT_LIFETIME_DAYS = 365;

    /**
     * 16 random bytes hex-encoded gives a 32 character token — far beyond guessing
     * or enumerating, and it fits the unique varchar(32) column.
     */
    private const TOKEN_BYTES = 16;

    private const SECONDS_PER_DAY = 86400;

    public function __construct(
        private readonly SavedResultRepository $savedResultRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly SessionService $sessionService,
        private readonly Context $context,
    ) {}

    /**
     * Store the run the visitor just completed, or update the row that belongs to
     * the token already in their session.
     *
     * @param array<int|string, array<string>> $answers Map of questionUid → given values
     */
    public function storeForCurrentRun(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        array $answers,
        float $score,
        int $storagePid,
        int $lifetimeDays = self::DEFAULT_LIFETIME_DAYS
    ): SavedResult {
        $sessionToken = $this->sessionService->getResultToken($feUser, $questionnaireUid);
        $savedResult = $this->savedResultRepository->findByTokenIgnoringStoragePage($sessionToken);

        // No row for the session token means it was purged or discarded: start over
        // with a fresh token instead of reviving a row that no longer exists.
        $isNew = $savedResult === null;
        if ($savedResult === null) {
            $savedResult = new SavedResult();
            $savedResult->setToken($this->generateToken());
            $savedResult->setQuestionnaire($questionnaireUid);
            $savedResult->setPid($storagePid);
        }

        $savedResult->setGivenAnswers($answers);
        $savedResult->setScore($score);
        $savedResult->setExpires($this->now() + $lifetimeDays * self::SECONDS_PER_DAY);

        if ($isNew) {
            $this->savedResultRepository->add($savedResult);
        } else {
            $this->savedResultRepository->update($savedResult);
        }
        $this->persistenceManager->persistAll();

        $this->sessionService->storeResultToken($feUser, $questionnaireUid, $savedResult->getToken());

        return $savedResult;
    }

    /**
     * Return the stored run for a token, or null when it is unknown, expired or
     * discarded. Callers must not distinguish between those three, so nothing
     * reveals whether a token ever existed.
     */
    public function findValidByToken(string $token): ?SavedResult
    {
        if (!$this->isWellFormedToken($token)) {
            return null;
        }

        $savedResult = $this->savedResultRepository->findByTokenIgnoringStoragePage($token);

        if ($savedResult === null || $savedResult->isExpired($this->now())) {
            return null;
        }

        return $savedResult;
    }

    /**
     * Whether the given value has the shape generateToken() produces: lowercase
     * hex of a fixed length, the same shape the route enhancer accepts. Anything
     * else is rejected before querying, so a malformed token never reaches the
     * database and never matches through a case-insensitive collation.
     */
    private function isWellFormedToken(string $token): bool
    {
        return preg_match('/^[0-9a-f]{' . self::TOKEN_BYTES * 2 . '}$/', $token) === 1;
    }

    /**
     * Remember the URL an editor can open this run with. Separate from storing,
     * because the URL contains the token and can only be built once it exists.
     */
    public function rememberResultUrl(SavedResult $savedResult, string $resultUrl): void
    {
        if ($resultUrl === '' || $savedResult->getResultUrl() === $resultUrl) {
            return;
        }

        $savedResult->setResultUrl($resultUrl);
        $this->savedResultRepository->update($savedResult);
        $this->persistenceManager->persistAll();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    private function now(): int
    {
        return (int)$this->context->getPropertyFromAspect('date', 'timestamp', time());
    }
}
