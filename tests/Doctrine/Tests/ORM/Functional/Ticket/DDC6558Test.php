<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-6558
 */
class DDC6558Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC6558Person::class),
            $this->_em->getClassMetadata(DDC6558Employee::class),
            $this->_em->getClassMetadata(DDC6558Staff::class),
            $this->_em->getClassMetadata(DDC6558Developer::class),
            $this->_em->getClassMetadata(DDC6558Manager::class),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(
            [
                $this->_em->getClassMetadata(DDC6558Person::class),
                $this->_em->getClassMetadata(DDC6558Employee::class),
                $this->_em->getClassMetadata(DDC6558Staff::class),
                $this->_em->getClassMetadata(DDC6558Developer::class),
                $this->_em->getClassMetadata(DDC6558Manager::class),
            ]
        );
    }

    /**
     * When using discrimination over multiple levels the properties of the 'middle layer' are not populated.
     */
    public function testEmployeeIsPopulated()
    {
        $developer = new DDC6558Developer();
        $developer->phoneNumber = 1231231231;
        $developer->emailAddress = "email@address.com";

        $this->_em->persist($developer);
        $this->_em->flush();
        $this->_em->clear();

        $persistedDeveloper = $this->_em->find(DDC6558Person::class, $developer->id);

        self::assertNotNull($persistedDeveloper->phoneNumber);
        self::assertNotNull($persistedDeveloper->emailAddress);
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"manager" = "DDC6558Manager", "staff" = "DDC6558Staff", "developer" = "DDC6558Developer"})
 */
abstract class DDC6558Person
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity */
class DDC6558Manager extends DDC6558Person
{
}

/**
 * @Entity
 */
abstract class DDC6558Employee extends DDC6558Person
{
    /** @Column(type="string") */
    public $phoneNumber;
}

/** @Entity */
class DDC6558Staff extends DDC6558Employee
{
}

/** @Entity */
class DDC6558Developer extends DDC6558Employee
{
    /** @Column(type="string") */
    public $emailAddress;
}

