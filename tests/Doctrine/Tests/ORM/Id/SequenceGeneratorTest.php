<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Id;

use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\OrmTestCase;

class SequenceGeneratorTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SequenceGenerator */
    private $sequenceGenerator;

    /** @var ConnectionMock */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager     = $this->getTestEntityManager();
        $this->sequenceGenerator = new SequenceGenerator('seq', 10);
        $this->connection        = $this->entityManager->getConnection();

        self::assertInstanceOf(ConnectionMock::class, $this->connection);
    }

    public function testGeneration(): void
    {
        $this->connection->setFetchOneException(new BadMethodCallException(
            'Fetch* method used. Query method should be used instead, '
            . 'as NEXTVAL should be run on a master server in master-slave setup.'
        ));

        for ($i = 0; $i < 42; ++$i) {
            if ($i % 10 === 0) {
                $this->connection->setQueryResult(ArrayResultFactory::createFromArray([[(int) ($i / 10) * 10]]));
            }

            $id = $this->sequenceGenerator->generateId($this->entityManager, null);

            self::assertSame($i, $id);
            self::assertSame((int) ($i / 10) * 10 + 10, $this->sequenceGenerator->getCurrentMaxValue());
            self::assertSame($i + 1, $this->sequenceGenerator->getNextValue());
        }
    }
}
