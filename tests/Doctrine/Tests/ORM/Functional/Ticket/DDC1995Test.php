<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\Company\CompanyEmployee;

/**
 * @group DDC-1995
 */
class DDC1995Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testIssue()
    {
        $person = new CompanyPerson;
        $person->setName('p1');

        $employee = new CompanyEmployee;
        $employee->setName('Foo');
        $employee->setDepartment('bar');
        $employee->setSalary(1000);

        $this->_em->persist($person);
        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        $dql    = 'SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF ?1';
        $class  = $this->_em->getClassMetadata(CompanyEmployee::class);

        $result = $this->_em->createQuery($dql)
                ->setParameter(1, $class)
                ->getResult();

        $this->assertCount(1, $result);
        $this->assertInstanceOf(CompanyEmployee::class, $result[0]);
    }

    public function testQueryCache()
    {
        $person = new CompanyPerson;
        $person->setName('p1');

        $employee = new CompanyEmployee;
        $employee->setName('Foo');
        $employee->setDepartment('bar');
        $employee->setSalary(1000);

        $this->_em->persist($person);
        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        $dql     = 'SELECT u FROM Doctrine\Tests\Models\Company\CompanyPerson u WHERE u INSTANCE OF :type';
        $class1  = $this->_em->getClassMetadata(CompanyEmployee::class);
        $class2  = $this->_em->getClassMetadata(CompanyPerson::class);

        $result1 = $this->_em->createQuery($dql)
                ->setParameter('type', $class1)
                ->useQueryCache(true)
                ->getResult();

        $result2 = $this->_em->createQuery($dql)
                ->setParameter('type', $class2)
                ->useQueryCache(true)
                ->getResult();

        $this->assertCount(1, $result1);
        $this->assertCount(2, $result2);

        $this->assertInstanceOf(CompanyEmployee::class, $result1[0]);
        $this->assertContainsOnlyInstancesOf(CompanyPerson::class, $result2);
    }
}
