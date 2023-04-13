<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ToOneAssociationMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ToOneAssociationMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new MyToOneAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->sourceToTargetKeyColumns = ['foo' => 'bar'];
        $mapping->targetToSourceKeyColumns = ['bar' => 'foo'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ToOneAssociationMapping);

        self::assertSame(['foo' => 'bar'], $resurrectedMapping->sourceToTargetKeyColumns);
        self::assertSame(['bar' => 'foo'], $resurrectedMapping->targetToSourceKeyColumns);
    }
}

class MyToOneAssociationMapping extends ToOneAssociationMapping
{
}
