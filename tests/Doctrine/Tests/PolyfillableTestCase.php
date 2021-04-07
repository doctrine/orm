<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

use function class_alias;
use function preg_match;

/**
 * This provides a forward-compatibility layer so that test cases can always call methods from the
 * latest-supported phpunit version, while still running on older versions.
 */

if (preg_match('/^8\./', Version::series())) {
    class_alias(PHPUnit8PolyfilledTestCase::class, 'Doctrine\Tests\PolyfillableTestCase');
} else {
    // No polyfill required for this PHPUnit version - just alias the default PHPUnit TestCase class
    class_alias(TestCase::class, 'Doctrine\Tests\PolyfillableTestCase');
}
