<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\InverseSideMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class InverseSideMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new MyInverseAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->mappedBy = 'bar';

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof InverseSideMapping);

        self::assertSame('bar', $resurrectedMapping->mappedBy);
    }
}

class MyInverseAssociationMapping extends InverseSideMapping
{
}
