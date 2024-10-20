<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\OwningSideMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class OwningSideMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new MyOwningAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->inversedBy = 'bar';

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof OwningSideMapping);

        self::assertSame('bar', $resurrectedMapping->inversedBy);
    }
}

class MyOwningAssociationMapping extends OwningSideMapping
{
}
