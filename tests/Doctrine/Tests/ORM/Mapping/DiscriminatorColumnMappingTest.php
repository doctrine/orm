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
        $mapping->options          = ['foo' => 'bar'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof DiscriminatorColumnMapping);

        self::assertSame(255, $resurrectedMapping->length);
        self::assertSame('VARCHAR(255)', $resurrectedMapping->columnDefinition);
        self::assertSame('MyEnum', $resurrectedMapping->enumType);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->options);
    }
}
