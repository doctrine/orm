<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1995
 */
class DDC1995Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testIssue() : void
    {
        $person = new CompanyPerson();
        $person->setName('p1');

        $employee = new CompanyEmployee();
        $employee->setName('Foo');
        $employee->setDepartment('bar');
        $employee->setSalary(1000);

        $this->em->persist($person);
        $this->em->persist($employee);
        $this->em->flush();
        $this->em->clear();

        $dql   = 'SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF ?1';
        $class = $this->em->getClassMetadata(CompanyEmployee::class);

        $result = $this->em->createQuery($dql)
                ->setParameter(1, $class)
                ->getResult();

        self::assertCount(1, $result);
        self::assertInstanceOf(CompanyEmployee::class, $result[0]);
    }

    public function testQueryCache() : void
    {
        $person = new CompanyPerson();
        $person->setName('p1');

        $employee = new CompanyEmployee();
        $employee->setName('Foo');
        $employee->setDepartment('bar');
        $employee->setSalary(1000);

        $this->em->persist($person);
        $this->em->persist($employee);
        $this->em->flush();
        $this->em->clear();

        $dql    = 'SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF :type';
        $class1 = $this->em->getClassMetadata(CompanyEmployee::class);
        $class2 = $this->em->getClassMetadata(CompanyPerson::class);

        $result1 = $this->em->createQuery($dql)
                ->setParameter('type', $class1)
                ->useQueryCache(true)
                ->getResult();

        $result2 = $this->em->createQuery($dql)
                ->setParameter('type', $class2)
                ->useQueryCache(true)
                ->getResult();

        self::assertCount(1, $result1);
        self::assertCount(2, $result2);

        self::assertContainsOnlyInstancesOf(CompanyEmployee::class, $result1);
        self::assertContainsOnlyInstancesOf(CompanyPerson::class, $result2);
    }
}
