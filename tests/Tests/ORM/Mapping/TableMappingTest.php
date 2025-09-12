<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

final class TableMappingTest extends TestCase
{
    use VerifyDeprecations;

    #[IgnoreDeprecations]
    public function testDeprecationOnIndexesPropertyIsTriggered(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/11357');

        new Table(indexes: []);
    }

    #[IgnoreDeprecations]
    public function testDeprecationOnUniqueConstraintsPropertyIsTriggered(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/11357');

        new Table(uniqueConstraints: []);
    }
}
