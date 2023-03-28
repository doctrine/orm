<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ManyToOneAssociationMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new ManyToOneAssociationMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->joinColumns = [new JoinColumnMapping()];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ManyToOneAssociationMapping);

        self::assertCount(1, $resurrectedMapping->joinColumns);
    }
}
