<?php

declare(strict_types=1);

// The vendor directory sits in a different place depending on how the extension is checked
// out: at the extension root when it is its own repository, two levels up when it lives in
// a monorepo's packages/ folder. PHPUnit's bootstrap attribute takes a single fixed path,
// so the lookup happens here instead.
$candidates = [
    dirname(__DIR__) . '/vendor',
    dirname(__DIR__, 3) . '/vendor',
];

foreach ($candidates as $vendorDir) {
    $bootstrap = $vendorDir . '/typo3/testing-framework/Resources/Core/Build/UnitTestsBootstrap.php';
    if (file_exists($bootstrap)) {
        require $bootstrap;

        return;
    }
}

throw new RuntimeException(
    'Could not locate typo3/testing-framework. Looked in: ' . implode(', ', $candidates)
    . '. Run composer install first.',
    1785600000
);
