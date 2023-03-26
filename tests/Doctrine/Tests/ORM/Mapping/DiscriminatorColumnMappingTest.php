<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DiscriminatorColumnMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class DiscriminatorColumnMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new DiscriminatorColumnMapping(
            type: 'string',
            fieldName: 'discr',
            name: 'discr',
        );

        $mapping->length           = 255;
        $mapping->columnDefinition = 'VARCHAR(255)';
        $mapping->enumType         = 'MyEnum';

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof DiscriminatorColumnMapping);

        self::assertSame($resurrectedMapping->length, 255);
        self::assertSame($resurrectedMapping->columnDefinition, 'VARCHAR(255)');
        self::assertSame($resurrectedMapping->enumType, 'MyEnum');
    }
}
