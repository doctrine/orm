<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping\Table;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

final class TableMappingTest extends TestCase
{
    use VerifyDeprecations;

    #[WithoutErrorHandler]
    public function testDeprecationOnIndexesPropertyIsTriggered(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/11357');

        new Table(indexes: []);
    }

    #[WithoutErrorHandler]
    public function testDeprecationOnUniqueConstraintsPropertyIsTriggered(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/11357');

        new Table(uniqueConstraints: []);
    }
}
