<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2090
 * @group non-cacheable
 */
class DDC2090Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testIssue(): void
    {
        $date1     = new DateTime('2011-11-11 11:11:11');
        $date2     = new DateTime('2012-12-12 12:12:12');
        $employee1 = new CompanyEmployee();
        $employee2 = new CompanyEmployee();

        $employee1->setName('Fabio B. Silva');
        $employee1->setStartDate(new DateTime('yesterday'));
        $employee1->setDepartment('R&D');
        $employee1->setSalary(100);

        $employee2->setName('Doctrine Bot');
        $employee1->setStartDate(new DateTime('yesterday'));
        $employee2->setDepartment('QA');
        $employee2->setSalary(100);

        $this->_em->persist($employee1);
        $this->_em->persist($employee2);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->createQueryBuilder()
            ->update(CompanyEmployee::class, 'e')
            ->set('e.startDate', ':date')
            ->set('e.salary', ':salary')
            ->where('e = :e')
            ->setParameters(
                [
                    'e'      => $employee1,
                    'date'   => $date1,
                    'salary' => 101,
                ]
            )
            ->getQuery()
            ->useQueryCache(true)
            ->execute();

        $this->_em->createQueryBuilder()
            ->update(CompanyEmployee::class, 'e')
            ->set('e.startDate', ':date')
            ->set('e.salary', ':salary')
            ->where('e = :e')
            ->setParameters(
                [
                    'e'      => $employee2,
                    'date'   => $date2,
                    'salary' => 102,
                ]
            )
            ->getQuery()
            ->useQueryCache(true)
            ->execute();

        $this->_em->clear();

        $e1 = $this->_em->find(CompanyEmployee::class, $employee1->getId());
        $e2 = $this->_em->find(CompanyEmployee::class, $employee2->getId());

        $this->assertEquals(101, $e1->getSalary());
        $this->assertEquals(102, $e2->getSalary());
        $this->assertEquals($date1, $e1->getStartDate());
        $this->assertEquals($date2, $e2->getStartDate());

        $this->_em->createQueryBuilder()
            ->update(CompanyEmployee::class, 'e')
            ->set('e.startDate', '?1')
            ->set('e.salary', '?2')
            ->where('e = ?0')
            ->setParameters([$employee1, $date1, 101])
            ->getQuery()
            ->useQueryCache(true)
            ->execute();

        $this->_em->createQueryBuilder()
            ->update(CompanyEmployee::class, 'e')
            ->set('e.startDate', '?1')
            ->set('e.salary', '?2')
            ->where('e = ?0')
            ->setParameters([$employee2, $date2, 102])
            ->getQuery()
            ->useQueryCache(true)
            ->execute();

        $this->_em->clear();

        $e1 = $this->_em->find(CompanyEmployee::class, $employee1->getId());
        $e2 = $this->_em->find(CompanyEmployee::class, $employee2->getId());

        $this->assertEquals(101, $e1->getSalary());
        $this->assertEquals(102, $e2->getSalary());
        $this->assertEquals($date1, $e1->getStartDate());
        $this->assertEquals($date2, $e2->getStartDate());
    }
}
