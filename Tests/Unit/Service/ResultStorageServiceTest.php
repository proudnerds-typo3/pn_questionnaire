<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use ProudNerds\PnQuestionnaire\Domain\Model\SavedResult;
use ProudNerds\PnQuestionnaire\Domain\Repository\SavedResultRepository;
use ProudNerds\PnQuestionnaire\Service\ResultStorageService;
use ProudNerds\PnQuestionnaire\Service\SessionService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ResultStorageServiceTest extends UnitTestCase
{
    /**
     * Fixed "now" so the expiry maths is checked against a known value.
     */
    private const NOW = 1800000000;

    private const SECONDS_PER_DAY = 86400;

    private SavedResultRepository&MockObject $repository;
    private PersistenceManagerInterface&MockObject $persistenceManager;
    private SessionService&MockObject $sessionService;
    private FrontendUserAuthentication&MockObject $feUser;
    private ResultStorageService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(SavedResultRepository::class);
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->sessionService = $this->createMock(SessionService::class);
        $this->feUser = $this->createMock(FrontendUserAuthentication::class);

        $context = $this->createMock(Context::class);
        $context->method('getPropertyFromAspect')->willReturn(self::NOW);

        $this->subject = new ResultStorageService(
            $this->repository,
            $this->persistenceManager,
            $this->sessionService,
            $context
        );
    }

    #[Test]
    public function storesANewRunWithA32CharacterHexToken(): void
    {
        $savedResult = $this->storeRun();

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $savedResult->getToken());
    }

    #[Test]
    public function givesEveryNewRunItsOwnToken(): void
    {
        $first = $this->storeRun();
        $second = $this->storeRun();

        self::assertNotSame($first->getToken(), $second->getToken());
    }

    #[Test]
    public function derivesTheExpiryFromTheLifetimeInDays(): void
    {
        $savedResult = $this->storeRun(lifetimeDays: 30);

        self::assertSame(self::NOW + 30 * self::SECONDS_PER_DAY, $savedResult->getExpires());
    }

    #[Test]
    public function keepsARunForAYearWhenNoLifetimeIsGiven(): void
    {
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn(null);

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [], 0.0, 20278);

        self::assertSame(self::NOW + 365 * self::SECONDS_PER_DAY, $savedResult->getExpires());
    }

    #[Test]
    public function storesTheGivenAnswersAsAVersionedJsonEnvelope(): void
    {
        $savedResult = $this->storeRun(answers: [12 => ['34'], 13 => ['2', '3']]);

        self::assertSame(
            '{"version":1,"answers":{"12":["34"],"13":["2","3"]}}',
            $savedResult->getAnswers()
        );
        self::assertSame([12 => ['34'], 13 => ['2', '3']], $savedResult->getGivenAnswers());
    }

    #[Test]
    public function putsANewRunInTheStorageFolderGivenByTheCaller(): void
    {
        $savedResult = $this->storeRun(storagePid: 20278);

        self::assertSame(20278, $savedResult->getPid());
    }

    #[Test]
    public function remembersTheTokenInTheSessionSoARefreshCannotCreateASecondRow(): void
    {
        $storedToken = null;
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn(null);
        $this->sessionService->expects(self::once())
            ->method('storeResultToken')
            ->willReturnCallback(
                function (FrontendUserAuthentication $feUser, int $questionnaireUid, string $token) use (&$storedToken): void {
                    $storedToken = $token;
                }
            );

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [], 0.0, 20278);

        self::assertSame($savedResult->getToken(), $storedToken);
    }

    #[Test]
    public function updatesTheExistingRunWhenTheSessionAlreadyHoldsAToken(): void
    {
        $existing = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $this->sessionService->method('getResultToken')->willReturn($existing->getToken());
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn($existing);
        $this->repository->expects(self::never())->method('add');
        $this->repository->expects(self::once())->method('update')->with($existing);

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [14 => ['5']], 2.5, 20278, 365);

        self::assertSame($existing, $savedResult);
        self::assertSame('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4', $savedResult->getToken());
        self::assertSame([14 => ['5']], $savedResult->getGivenAnswers());
        self::assertSame(2.5, $savedResult->getScore());
    }

    #[Test]
    public function movesTheExpiryAlongWhenAnExistingRunIsUpdated(): void
    {
        $existing = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $existing->setExpires(self::NOW - 10);
        $this->sessionService->method('getResultToken')->willReturn($existing->getToken());
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn($existing);

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [], 0.0, 20278, 7);

        self::assertSame(self::NOW + 7 * self::SECONDS_PER_DAY, $savedResult->getExpires());
    }

    #[Test]
    public function leavesTheStorageFolderOfAnExistingRunAlone(): void
    {
        $existing = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $existing->setPid(20278);
        $this->sessionService->method('getResultToken')->willReturn($existing->getToken());
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn($existing);

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [], 0.0, 99999, 365);

        self::assertSame(20278, $savedResult->getPid());
    }

    #[Test]
    public function startsOverWithANewTokenWhenTheTokenInTheSessionNoLongerResolves(): void
    {
        $this->sessionService->method('getResultToken')->willReturn('deadbeefdeadbeefdeadbeefdeadbeef');
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn(null);
        $this->repository->expects(self::once())->method('add');
        $this->repository->expects(self::never())->method('update');

        $savedResult = $this->subject->storeForCurrentRun($this->feUser, 1, [], 0.0, 20278, 365);

        self::assertNotSame('deadbeefdeadbeefdeadbeefdeadbeef', $savedResult->getToken());
    }

    #[Test]
    public function findValidByTokenReturnsTheStoredRun(): void
    {
        $existing = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $existing->setExpires(self::NOW + 1);
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn($existing);

        self::assertSame($existing, $this->subject->findValidByToken($existing->getToken()));
    }

    #[Test]
    public function findValidByTokenReturnsNullForAnExpiredRun(): void
    {
        $existing = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $existing->setExpires(self::NOW - 1);
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn($existing);

        self::assertNull($this->subject->findValidByToken($existing->getToken()));
    }

    #[Test]
    public function findValidByTokenReturnsNullForAnUnknownToken(): void
    {
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn(null);

        self::assertNull($this->subject->findValidByToken('deadbeefdeadbeefdeadbeefdeadbeef'));
    }

    #[Test]
    #[DataProvider('malformedTokenProvider')]
    public function findValidByTokenRejectsAMalformedTokenWithoutQuerying(string $token): void
    {
        $this->repository->expects(self::never())->method('findByTokenIgnoringStoragePage');

        self::assertNull($this->subject->findValidByToken($token));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedTokenProvider(): array
    {
        return [
            'empty'         => [''],
            'too short'     => ['deadbeef'],
            'too long'      => ['deadbeefdeadbeefdeadbeefdeadbeef0'],
            'not hex'       => ['deadbeefdeadbeefdeadbeefdeadbeeg'],
            'uppercase hex' => ['DEADBEEFDEADBEEFDEADBEEFDEADBEEF'],
            'sql injection' => ["' OR 1=1 -- deadbeefdeadbeefde"],
        ];
    }

    #[Test]
    public function rememberResultUrlPersistsTheUrl(): void
    {
        $savedResult = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $this->repository->expects(self::once())->method('update')->with($savedResult);
        $this->persistenceManager->expects(self::once())->method('persistAll');

        $this->subject->rememberResultUrl($savedResult, 'https://example.test/uitslag/a1b2');

        self::assertSame('https://example.test/uitslag/a1b2', $savedResult->getResultUrl());
    }

    #[Test]
    public function rememberResultUrlSkipsAnUnchangedUrl(): void
    {
        $savedResult = $this->existingRun('a1b2c3d4a1b2c3d4a1b2c3d4a1b2c3d4');
        $savedResult->setResultUrl('https://example.test/uitslag/a1b2');
        $this->repository->expects(self::never())->method('update');
        $this->persistenceManager->expects(self::never())->method('persistAll');

        $this->subject->rememberResultUrl($savedResult, 'https://example.test/uitslag/a1b2');

        self::assertSame('https://example.test/uitslag/a1b2', $savedResult->getResultUrl());
    }

    /**
     * @param array<int|string, array<string>> $answers
     */
    private function storeRun(
        array $answers = [],
        float $score = 0.0,
        int $storagePid = 20278,
        int $lifetimeDays = 365
    ): SavedResult {
        $this->repository->method('findByTokenIgnoringStoragePage')->willReturn(null);

        return $this->subject->storeForCurrentRun(
            $this->feUser,
            1,
            $answers,
            $score,
            $storagePid,
            $lifetimeDays
        );
    }

    private function existingRun(string $token): SavedResult
    {
        $savedResult = new SavedResult();
        $savedResult->setToken($token);
        $savedResult->setQuestionnaire(1);

        return $savedResult;
    }
}
