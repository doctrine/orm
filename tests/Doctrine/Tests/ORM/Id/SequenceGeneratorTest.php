<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Id;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\PHPUnitCompatibility\MockBuilderCompatibilityTools;

class SequenceGeneratorTest extends OrmTestCase
{
    use MockBuilderCompatibilityTools;

    public function testGeneration(): void
    {
        $sequenceGenerator = new SequenceGenerator('seq', 10);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('getSequenceNextValSQL')
            ->willReturn('');

        $connection = $this->getMockBuilderWithOnlyMethods(Connection::class, ['fetchOne', 'getDatabasePlatform'])
            ->setConstructorArgs([[], $this->createMock(Driver::class)])
            ->getMock();
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        // Sequence values should be generated once per ten identifiers
        $connection->expects($this->exactly(5))
            ->method('fetchOne')
            ->willReturnCallback(static function () use (&$i) {
                self::assertEquals(0, $i % 10);

                return $i;
            });

        $entityManager = $this->getTestEntityManager($connection);

        for ($i = 0; $i < 42; ++$i) {
            $id = $sequenceGenerator->generateId($entityManager, null);

            self::assertSame($i, $id);
            self::assertSame((int) ($i / 10) * 10 + 10, $sequenceGenerator->getCurrentMaxValue());
            self::assertSame($i + 1, $sequenceGenerator->getNextValue());
        }
    }
}
