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
     * Given the following class structure it is possible to fetch a Developer when using the repository form the second level node.
     *
     * - Person
     *   - Manager
     *   - Employee
     *     - Staff
     *     - Developer
     */
    public function testFetchUsingRepoFromSecondLevelNode()
    {
        $developer = new DDC6558Developer();
        $developer->name = "Jeroen";
        $developer->number = 1337;
        $developer->emailAddress = "email@address.com";

        $this->_em->persist($developer);
        $this->_em->flush();
        $this->_em->clear();

        $persistedDeveloper = $this->_em->find(DDC6558Employee::class, $developer->id);

        $this->assertSame($persistedDeveloper->emailAddress, $developer->emailAddress);
    }

    /**
     * Given the following class structure it is NOT possible to fetch a Developer when using the repository form the root node.
     *
     * - Person
     *   - Manager
     *   - Employee
     *     - Staff
     *     - Developer
     *
     * The issue is that an Employee is being instantiated, which of course fails since it is an abstract class.
     */
    public function testFetchUsingRepoFromRootNode()
    {
        $developer = new DDC6558Developer();
        $developer->name = "Jeroen";
        $developer->number = 1337;
        $developer->emailAddress = "email@address.com";

        $this->_em->persist($developer);
        $this->_em->flush();
        $this->_em->clear();

        $persistedDeveloper = $this->_em->find(DDC6558Person::class, $developer->id);

        $this->assertSame($persistedDeveloper->emailAddress, $developer->emailAddress);
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"manager" = "DDC6558Manager", "staff" = "DDC6558Employee", "developer" = "DDC6558Employee"})
 */
abstract class DDC6558Person
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;
}

/** @Entity */
class DDC6558Manager extends DDC6558Person
{
    /** @Column(type="integer") */
    public $parkingLotNumber;
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"staff" = "DDC6558Staff", "developer" = "DDC6558Developer"})
 */
abstract class DDC6558Employee extends DDC6558Person
{
    /** @Column(type="integer") */
    public $number;
}

/** @Entity */
class DDC6558Staff extends DDC6558Employee
{
    /** @Column(type="string") */
    public $phoneNumber;
}

/** @Entity */
class DDC6558Developer extends DDC6558Employee
{
    /** @Column(type="string") */
    public $emailAddress;
}

