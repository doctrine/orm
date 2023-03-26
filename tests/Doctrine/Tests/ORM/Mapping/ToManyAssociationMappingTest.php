<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ToManyAssociationMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ToManyAssociationMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new MyToManyAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->indexBy = 'foo';
        $mapping->orderBy = ['foo' => 'asc'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ToManyAssociationMapping);

        self::assertSame($resurrectedMapping->indexBy, 'foo');
        self::assertSame($resurrectedMapping->orderBy, ['foo' => 'asc']);
    }
}

class MyToManyAssociationMapping extends ToManyAssociationMapping
{
}
