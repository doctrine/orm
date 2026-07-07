<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;

use function iterator_to_array;

final class GH7039Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $employee = new CompanyEmployee();
        $employee->setName('emp');
        $employee->setSalary(1000);
        $employee->setDepartment('IT');
        $employee->setStartDate(new DateTime());

        $manager = new CompanyManager();
        $manager->setName('mgr');
        $manager->setSalary(2000);
        $manager->setDepartment('IT');
        $manager->setStartDate(new DateTime());
        $manager->setTitle('boss');

        $this->_em->persist($employee);
        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testCountWithInstanceOfParameterDefaultWalkers(): void
    {
        $query = $this->_em->createQuery(
            'SELECT p FROM Doctrine\Tests\Models\Company\CompanyEmployee p WHERE p INSTANCE OF :type',
        );
        $query->setParameter('type', $this->_em->getClassMetadata(CompanyManager::class));
        $query->setMaxResults(10);

        $paginator = new Paginator($query, true);

        $items = iterator_to_array($paginator->getIterator());
        self::assertCount(1, $items, 'iteration should find the manager');

        self::assertSame(1, $paginator->count(), 'count() should also find the manager');
    }

    public function testCountWithInstanceOfParameterTreeWalkers(): void
    {
        $query = $this->_em->createQuery(
            'SELECT p FROM Doctrine\Tests\Models\Company\CompanyEmployee p WHERE p INSTANCE OF :type',
        );
        $query->setParameter('type', $this->_em->getClassMetadata(CompanyManager::class));
        $query->setMaxResults(10);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);

        self::assertSame(1, $paginator->count(), 'count() with tree walkers');
    }
}
