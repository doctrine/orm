<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Id;

use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\OrmTestCase;

use function assert;

class SequenceGeneratorTest extends OrmTestCase
{
    public function testGeneration(): void
    {
        $entityManager     = $this->getTestEntityManager();
        $sequenceGenerator = new SequenceGenerator('seq', 10);
        $connection        = $entityManager->getConnection();
        assert($connection instanceof ConnectionMock);

        for ($i = 0; $i < 42; ++$i) {
            if ($i % 10 === 0) {
                $connection->setQueryResult(ArrayResultFactory::createFromArray([[(int) ($i / 10) * 10]]));
            }

            $id = $sequenceGenerator->generate($entityManager, null);

            self::assertSame($i, $id);
            self::assertSame((int) ($i / 10) * 10 + 10, $sequenceGenerator->getCurrentMaxValue());
            self::assertSame($i + 1, $sequenceGenerator->getNextValue());
        }
    }
}
