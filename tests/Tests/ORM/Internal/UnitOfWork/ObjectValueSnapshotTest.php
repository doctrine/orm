<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal\UnitOfWork;

use Doctrine\ORM\Internal\UnitOfWork\ObjectValueSnapshot;
use PHPUnit\Framework\TestCase;
use stdClass;

use function str_repeat;

class ObjectValueSnapshotTest extends TestCase
{
    public function testShortDatabaseValueIsStoredAsIs(): void
    {
        $object   = new stdClass();
        $snapshot = ObjectValueSnapshot::fromDatabaseValue($object, 'short');

        self::assertSame($object, $snapshot->object);
        self::assertTrue($snapshot->matchesDatabaseValue(new stdClass(), 'short'));
        self::assertFalse($snapshot->matchesDatabaseValue(new stdClass(), 'other'));
    }

    public function testSameObjectAlwaysMatches(): void
    {
        $object   = new stdClass();
        $snapshot = ObjectValueSnapshot::fromDatabaseValue($object, 'short');

        self::assertTrue($snapshot->matchesDatabaseValue($object, 'other'));
    }

    public function testValueAtThresholdIsStoredAsIs(): void
    {
        $databaseValue = str_repeat('a', ObjectValueSnapshot::HASH_THRESHOLD);
        $snapshot      = ObjectValueSnapshot::fromDatabaseValue(new stdClass(), $databaseValue);

        self::assertTrue($snapshot->matchesDatabaseValue(new stdClass(), $databaseValue));
        self::assertFalse($snapshot->matchesDatabaseValue(new stdClass(), $databaseValue . 'b'));
    }

    public function testLongDatabaseValueIsComparedByHash(): void
    {
        $databaseValue = str_repeat('a', ObjectValueSnapshot::HASH_THRESHOLD + 1);
        $snapshot      = ObjectValueSnapshot::fromDatabaseValue(new stdClass(), $databaseValue);

        self::assertTrue($snapshot->matchesDatabaseValue(new stdClass(), $databaseValue));
        self::assertFalse($snapshot->matchesDatabaseValue(new stdClass(), $databaseValue . 'b'));
    }
}
