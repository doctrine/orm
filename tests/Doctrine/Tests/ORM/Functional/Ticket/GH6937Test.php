<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 6937
 */
final class GH6937Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH6937Person::class, GH6937Employee::class, GH6937Manager::class]);
    }

    public function testPhoneNumberIsPopulatedWithFind() : void
    {
        $manager              = new GH6937Manager();
        $manager->name        = 'Kevin';
        $manager->phoneNumber = '555-5555';
        $manager->department  = 'Accounting';

        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();

        $persistedManager = $this->_em->find(GH6937Person::class, $manager->id);

        self::assertSame('Kevin', $persistedManager->name);
        self::assertSame('555-5555', $persistedManager->phoneNumber);
        self::assertSame('Accounting', $persistedManager->department);
    }

    public function testPhoneNumberIsPopulatedWithQueryBuilderUsingSimpleObjectHydrator() : void
    {
        $manager              = new GH6937Manager();
        $manager->name        = 'Kevin';
        $manager->phoneNumber = '555-5555';
        $manager->department  = 'Accounting';

        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();

        $persistedManager = $this->_em->getRepository(GH6937Person::class)
                                      ->createQueryBuilder('e')
                                      ->where('e.id = :id')
                                      ->setParameter('id', $manager->id)
                                      ->getQuery()
                                      ->getOneOrNullResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);

        self::assertSame('Kevin', $persistedManager->name);
        self::assertSame('555-5555', $persistedManager->phoneNumber);
        self::assertSame('Accounting', $persistedManager->department);
    }

    public function testPhoneNumberIsPopulatedWithQueryBuilder() : void
    {
        $manager              = new GH6937Manager();
        $manager->name        = 'Kevin';
        $manager->phoneNumber = '555-5555';
        $manager->department  = 'Accounting';

        $this->_em->persist($manager);
        $this->_em->flush();
        $this->_em->clear();

        $persistedManager = $this->_em->getRepository(GH6937Person::class)
                                      ->createQueryBuilder('e')
                                      ->where('e.id = :id')
                                      ->setParameter('id', $manager->id)
                                      ->getQuery()
                                      ->getOneOrNullResult();

        self::assertSame('Kevin', $persistedManager->name);
        self::assertSame('555-5555', $persistedManager->phoneNumber);
        self::assertSame('Accounting', $persistedManager->department);
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"employee"=GH6937Employee::class, "manager"=GH6937Manager::class})
 */
abstract class GH6937Person
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;
}

/**
 * @Entity
 */
abstract class GH6937Employee extends GH6937Person
{
    /** @Column(type="string") */
    public $phoneNumber;
}

/**
 * @Entity
 */
class GH6937Manager extends GH6937Employee
{
    /** @Column(type="string") */
    public $department;
}
