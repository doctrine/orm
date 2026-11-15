<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\OrmTestCase;

class NativeQueryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
    }

    public function testValuesAreNotBeingResolvedForSpecifiedParameterTypes(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $this->entityManager->setUnitOfWork($unitOfWork);

        $unitOfWork
            ->expects(self::never())
            ->method('getSingleIdentifierValue');

        $rsm = new ResultSetMapping();

        $query = $this->entityManager->createNativeQuery('SELECT d.* FROM date_time_model d WHERE d.datetime = :value', $rsm);

        $query->setParameter('value', new DateTimeImmutable(), Types::DATETIME_IMMUTABLE);

        self::assertEmpty($query->getResult());
    }
}
