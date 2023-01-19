<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmFunctionalTestCase;

use function ksort;

class DDC168Test extends OrmFunctionalTestCase
{
    /** @var ClassMetadata */
    protected $oldMetadata;

    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->oldMetadata = $this->_em->getClassMetadata(CompanyEmployee::class);

        $metadata = clone $this->oldMetadata;
        ksort($metadata->reflFields);
        $this->_em->getMetadataFactory()->setMetadataFor(CompanyEmployee::class, $metadata);
    }

    public function tearDown(): void
    {
        $this->_em->getMetadataFactory()->setMetadataFor(CompanyEmployee::class, $this->oldMetadata);

        parent::tearDown();
    }

    /** @group DDC-168 */
    public function testJoinedSubclassPersisterRequiresSpecificOrderOfMetadataReflFieldsArray(): void
    {
        $spouse = new CompanyEmployee();
        $spouse->setName('Blub');
        $spouse->setDepartment('Accounting');
        $spouse->setSalary(500);

        $employee = new CompanyEmployee();
        $employee->setName('Foo');
        $employee->setDepartment('bar');
        $employee->setSalary(1000);
        $employee->setSpouse($spouse);

        $this->_em->persist($spouse);
        $this->_em->persist($employee);

        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT e FROM Doctrine\Tests\Models\Company\CompanyEmployee e WHERE e.name = ?1');
        $q->setParameter(1, 'Foo');
        $theEmployee = $q->getSingleResult();

        self::assertEquals('bar', $theEmployee->getDepartment());
        self::assertEquals('Foo', $theEmployee->getName());
        self::assertEquals(1000, $theEmployee->getSalary());
        self::assertInstanceOf(CompanyEmployee::class, $theEmployee);
        self::assertInstanceOf(CompanyEmployee::class, $theEmployee->getSpouse());
    }
}
