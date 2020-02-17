<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Annotation as ORM;
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

        $this->em->persist($manager);
        $this->em->flush();
        $this->em->clear();

        $persistedManager = $this->em->find(GH6937Person::class, $manager->id);

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

        $this->em->persist($manager);
        $this->em->flush();
        $this->em->clear();

        $persistedManager = $this
            ->em
            ->createQueryBuilder()
            ->select('e')
            ->from(GH6937Person::class, 'e')
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

        $this->em->persist($manager);
        $this->em->flush();
        $this->em->clear();

        $persistedManager = $this
            ->em
            ->createQueryBuilder()
            ->select('e')
            ->from(GH6937Person::class, 'e')
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
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"employee"=GH6937Employee::class, "manager"=GH6937Manager::class})
 */
abstract class GH6937Person
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\Column(type="string") */
    public $name;
}

/**
 * @ORM\Entity
 */
abstract class GH6937Employee extends GH6937Person
{
    /** @ORM\Column(type="string") */
    public $phoneNumber;
}

/**
 * @ORM\Entity
 */
class GH6937Manager extends GH6937Employee
{
    /** @ORM\Column(type="string") */
    public $department;
}
