<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A completed questionnaire run, stored so the visitor can retrieve it later
 * through an unguessable link.
 *
 * Holds nothing that identifies the visitor: no IP address, no e-mail address,
 * no user agent and no frontend user relation — not even hashed. A hash would be
 * pseudonymisation rather than anonymisation, so the data is simply never stored.
 */
class SavedResult extends AbstractEntity
{
    /**
     * Shape of the JSON envelope in $answers. Bump this when the structure
     * changes, so a row written by an older version stays recognisable.
     */
    public const ANSWERS_VERSION = 1;

    /**
     * Random, unguessable identifier the visitor uses to retrieve this run.
     */
    protected string $token = '';

    /**
     * Absolute URL to this stored run. A denormalisation so an editor can open a
     * run straight from the list view; the token remains the leading value. After
     * a domain or route change the URLs can be regenerated from the tokens.
     */
    protected string $resultUrl = '';

    /**
     * UID of the questionnaire this run belongs to. A plain integer rather than a
     * relation, because retrieving a run loads the questionnaire through
     * QuestionnaireRepository::findByUidIgnoringStoragePage() regardless.
     */
    protected int $questionnaire = 0;

    /**
     * The given answers as a JSON envelope. Extbase maps no array properties (see
     * DataMapper::thawProperties(), unchanged from v12 through v14), so the raw
     * JSON lives here and setGivenAnswers()/getGivenAnswers() wrap it.
     */
    protected string $answers = '{}';

    protected float $score = 0.0;

    /**
     * Unix timestamp after which the purge command removes this row.
     */
    protected int $expires = 0;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getResultUrl(): string
    {
        return $this->resultUrl;
    }

    public function setResultUrl(string $resultUrl): void
    {
        $this->resultUrl = $resultUrl;
    }

    public function getQuestionnaire(): int
    {
        return $this->questionnaire;
    }

    public function setQuestionnaire(int $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
    }

    public function getAnswers(): string
    {
        return $this->answers;
    }

    public function setAnswers(string $answers): void
    {
        $this->answers = $answers;
    }

    /**
     * Store the session answers as a versioned JSON envelope.
     *
     * @param array<int|string, array<string>> $givenAnswers Map of questionUid → given values
     */
    public function setGivenAnswers(array $givenAnswers): void
    {
        $this->answers = (string)json_encode(
            [
                'version' => self::ANSWERS_VERSION,
                'answers' => $givenAnswers,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Return the answers in the same shape SessionService hands them out, so the
     * stored run can be re-evaluated against the questionnaire as it is today.
     *
     * @return array<int|string, array<string>> Map of questionUid → given values
     */
    public function getGivenAnswers(): array
    {
        $answers = $this->decodeAnswers()['answers'] ?? [];

        return is_array($answers) ? $answers : [];
    }

    public function getAnswersVersion(): int
    {
        return (int)($this->decodeAnswers()['version'] ?? 0);
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function setExpires(int $expires): void
    {
        $this->expires = $expires;
    }

    public function isExpired(int $now): bool
    {
        return $this->expires > 0 && $this->expires < $now;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAnswers(): array
    {
        $decoded = json_decode($this->answers, true);

        return is_array($decoded) ? $decoded : [];
    }
}
