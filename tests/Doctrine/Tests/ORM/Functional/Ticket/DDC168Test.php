<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Company\CompanyEmployee;

class DDC168Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $oldMetadata;

    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();

        $this->oldMetadata = $this->em->getClassMetadata(CompanyEmployee::class);

        $metadata = clone $this->oldMetadata;
        ksort($metadata->reflFields);
        $this->em->getMetadataFactory()->setMetadataFor(CompanyEmployee::class, $metadata);
    }

    public function tearDown()
    {
        $this->em->getMetadataFactory()->setMetadataFor(CompanyEmployee::class, $this->oldMetadata);
        parent::tearDown();
    }

    /**
     * @group DDC-168
     */
    public function testJoinedSubclassPersisterRequiresSpecificOrderOfMetadataReflFieldsArray()
    {
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $spouse = new CompanyEmployee;
        $spouse->setName("Blub");
        $spouse->setDepartment("Accounting");
        $spouse->setSalary(500);

        $employee = new CompanyEmployee;
        $employee->setName("Foo");
        $employee->setDepartment("bar");
        $employee->setSalary(1000);
        $employee->setSpouse($spouse);

        $this->em->persist($spouse);
        $this->em->persist($employee);

        $this->em->flush();
        $this->em->clear();

        $q = $this->em->createQuery("SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e WHERE e.name = ?1");
        $q->setParameter(1, "Foo");
        $theEmployee = $q->getSingleResult();

        self::assertEquals("bar", $theEmployee->getDepartment());
        self::assertEquals("Foo", $theEmployee->getName());
        self::assertEquals(1000, $theEmployee->getSalary());
        self::assertInstanceOf(CompanyEmployee::class, $theEmployee);
        self::assertInstanceOf(CompanyEmployee::class, $theEmployee->getSpouse());
    }
}
