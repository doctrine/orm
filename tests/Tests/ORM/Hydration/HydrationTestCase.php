<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\Mocks\ArrayResultFactory;
use Doctrine\Tests\OrmTestCase;

class HydrationTestCase extends OrmTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getTestEntityManager();
    }

    protected function createResultMock(array $resultSet): Result
    {
        return ArrayResultFactory::createWrapperResultFromArray($resultSet, $this->entityManager->getConnection());
    }
}
