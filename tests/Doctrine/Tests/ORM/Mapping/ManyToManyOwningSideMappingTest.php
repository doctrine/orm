<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\JoinTableMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class ManyToManyOwningSideMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new ManyToManyOwningSideMapping(
            fieldName: 'foo',
            sourceEntity: self::class,
            targetEntity: self::class,
        );

        $mapping->joinTable       = new JoinTableMapping();
        $mapping->joinTable->name = 'bar';

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof ManyToManyOwningSideMapping);

        self::assertSame($resurrectedMapping->joinTable->name, 'bar');
    }
}
