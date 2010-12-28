<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyEmployee;

require_once __DIR__ . '/../../../TestInit.php';

class DDC168Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $oldMetadata;

    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();

        $this->oldMetadata = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyEmployee');
        
        $metadata = clone $this->oldMetadata;
        ksort($metadata->reflFields);
        $this->_em->getMetadataFactory()->setMetadataFor('Doctrine\Tests\Models\Company\CompanyEmployee', $metadata);
    }

    public function tearDown()
    {
        $this->_em->getMetadataFactory()->setMetadataFor('Doctrine\Tests\Models\Company\CompanyEmployee', $this->oldMetadata);
        parent::tearDown();
    }
    
    /**
     * @group DDC-168
     */
    public function testJoinedSubclassPersisterRequiresSpecificOrderOfMetadataReflFieldsArray()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $spouse = new CompanyEmployee;
        $spouse->setName("Blub");
        $spouse->setDepartment("Accounting");
        $spouse->setSalary(500);

        $employee = new CompanyEmployee;
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
        $this->assertEquals("Foo", $theEmployee->getName());
        $this->assertEquals(1000, $theEmployee->getSalary());
        $this->assertTrue($theEmployee instanceof CompanyEmployee);
        $this->assertTrue($theEmployee->getSpouse() instanceof CompanyEmployee);
    }
}