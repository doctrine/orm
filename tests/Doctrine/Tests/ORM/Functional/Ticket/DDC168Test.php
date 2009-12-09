<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC168Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();
    }
    
    /**
     * @group DDC-168
     */
    public function testJoinedSubclassPersisterRequiresSpecificOrderOfMetadataReflFieldsArray()
    {
        $metadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyEmployee');
        ksort($metadata->reflFields);

        $spouse = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $spouse->setName("Blub");

        $employee = new \Doctrine\Tests\Models\Company\CompanyEmployee();
        $employee->setName("Foo");
        $employee->setDepartment("bar");
        $employee->setSalary(1000);
        $employee->setSpouse($spouse);

        $this->_em->persist($spouse);
        $this->_em->persist($employee);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery("SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e WHERE e.name = ?1");
        $q->setParameter(1, "Foo");
        $theEmployee = $q->getSingleResult();

        $this->assertEquals("bar", $theEmployee->getDepartment());
    }
}