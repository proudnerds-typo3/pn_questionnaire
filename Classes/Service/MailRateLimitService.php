<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Limits how often the result mail may be sent, so the public form cannot be used as
 * a spam relay.
 *
 * Counted on the recipient as well as on the client address: the first keeps one
 * visitor from flooding a stranger's inbox, the second keeps one visitor from sending
 * to endless different addresses. The window itself lives in the caching framework and
 * expires on its own, so nothing is stored beyond it.
 */
class MailRateLimitService
{
    public function __construct(
        private readonly RateLimiterFactory $rateLimiterFactory,
    ) {}

    /**
     * Consumes one token for both counters. Returns false as soon as either is spent,
     * in which case the caller must not send.
     */
    public function isWithinLimit(string $recipient, ServerRequestInterface $request): bool
    {
        $keys = [
            'recipient-' . $this->fingerprint($recipient),
            'client-' . $this->fingerprint($this->resolveClientAddress($request)),
        ];

        foreach ($keys as $key) {
            if (!$this->rateLimiterFactory->create($key)->consume()->isAccepted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * A plain hash, not an HMAC: this is a cache key and not a token to be verified, so
     * there is no secret to bind it to — and it keeps working the same on v12 through
     * v14, where the HMAC helpers of the core have moved. What matters is that neither
     * the address nor the IP ends up readable in the cache.
     */
    private function fingerprint(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }

    private function resolveClientAddress(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        return $normalizedParams instanceof NormalizedParams
            ? $normalizedParams->getRemoteAddress()
            : '';
    }
}
