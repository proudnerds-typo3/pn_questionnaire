<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * InArrayViewHelper
 *
 * Returns true when `needle` exists inside `haystack`.
 * All values are compared as strings to handle mixed int/string UID scenarios.
 *
 * Usage in Fluid:
 * ```
 * xmlns:pnq="http://typo3.org/ns/ProudNerds/PnQuestionnaire/ViewHelpers"
 *
 * <f:if condition="{pnq:inArray(haystack: currentAnswer, needle: option.uid)}">...</f:if>
 * ```
 */
class InArrayViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('haystack', 'array', 'The array to search in', true);
        $this->registerArgument('needle', 'mixed', 'The value to search for', true);
    }

    public function render(): bool
    {
        /** @var array<mixed> $haystack */
        $haystack = $this->arguments['haystack'];

        return in_array(
            (string)$this->arguments['needle'],
            array_map('strval', $haystack),
            true
        );
    }
}
