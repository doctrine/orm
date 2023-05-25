<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\JoinTableMapping;
use PHPUnit\Framework\TestCase;

use function assert;
use function serialize;
use function unserialize;

final class JoinTableMappingTest extends TestCase
{
    public function testItSurvivesSerialization(): void
    {
        $mapping = new JoinTableMapping();

        $mapping->quoted             = true;
        $mapping->joinColumns        = [new JoinColumnMapping('id')];
        $mapping->inverseJoinColumns = [new JoinColumnMapping('id')];
        $mapping->schema             = 'foo';
        $mapping->name               = 'bar';
        $mapping->options            = ['foo' => 'bar'];

        $resurrectedMapping = unserialize(serialize($mapping));
        assert($resurrectedMapping instanceof JoinTableMapping);

        self::assertTrue($resurrectedMapping->quoted);
        self::assertCount(1, $resurrectedMapping->joinColumns);
        self::assertCount(1, $resurrectedMapping->inverseJoinColumns);
        self::assertSame('foo', $resurrectedMapping->schema);
        self::assertSame('bar', $resurrectedMapping->name);
        self::assertSame(['foo' => 'bar'], $resurrectedMapping->options);
    }
}
