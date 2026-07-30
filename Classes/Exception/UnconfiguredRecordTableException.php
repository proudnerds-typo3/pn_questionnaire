<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Exception;

/**
 * Thrown when a domain record result references a table that has no
 * corresponding TypoScript mapping in `settings.domainRecordTargets`.
 */
class UnconfiguredRecordTableException extends \InvalidArgumentException {}
