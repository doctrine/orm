<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\ComparatorRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ComparatorRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        ComparatorRegistry::reset();
    }

    protected function tearDown(): void
    {
        ComparatorRegistry::reset();
    }

    #[Test]
    public function compareDateTimeTypes(): void
    {
        ComparatorRegistry::register(\DateTimeInterface::class, function (\DateTimeInterface $a, object $b): ?int {
            if ($b instanceof \DateTimeInterface) {
                return $a <=> $b;
            }
        });

        $nowMutable = new \DateTime();
        $nowImmutable = \DateTimeImmutable::createFromMutable($nowMutable);
        $yesterdayMutable = new \DateTime('yesterday');
        $yesterdayImmutable = \DateTimeImmutable::createFromMutable($yesterdayMutable);

        self::assertSame(null, ComparatorRegistry::compare($nowMutable, new \stdClass()));

        self::assertSame(0, ComparatorRegistry::compare($nowMutable, $nowMutable));
        self::assertSame(0, ComparatorRegistry::compare($nowMutable, $nowImmutable));
        self::assertSame(0, ComparatorRegistry::compare($nowImmutable, $nowMutable));
        self::assertSame(0, ComparatorRegistry::compare($nowImmutable, $nowImmutable));

        self::assertSame(1, ComparatorRegistry::compare($nowMutable, $yesterdayMutable));
        self::assertSame(1, ComparatorRegistry::compare($nowImmutable, $yesterdayMutable));
        self::assertSame(1, ComparatorRegistry::compare($nowMutable, $yesterdayImmutable));
        self::assertSame(1, ComparatorRegistry::compare($nowImmutable, $yesterdayImmutable));

        self::assertSame(-1, ComparatorRegistry::compare($yesterdayMutable, $nowMutable));
        self::assertSame(-1, ComparatorRegistry::compare($yesterdayMutable, $nowImmutable));
        self::assertSame(-1, ComparatorRegistry::compare($yesterdayImmutable, $nowMutable));
        self::assertSame(-1, ComparatorRegistry::compare($yesterdayImmutable, $nowImmutable));
    }
}
