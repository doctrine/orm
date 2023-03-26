<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ManyToManyAssociationMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ManyToManyAssociationMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new ManyToManyAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->relationToSourceKeyColumns = ['foo' => 'bar'];
        $mapping->relationToTargetKeyColumns = ['bar' => 'baz'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ManyToManyAssociationMapping);

        self::assertSame($resurrectedMapping->relationToSourceKeyColumns, ['foo' => 'bar']);
        self::assertSame($resurrectedMapping->relationToTargetKeyColumns, ['bar' => 'baz']);
    }
}
