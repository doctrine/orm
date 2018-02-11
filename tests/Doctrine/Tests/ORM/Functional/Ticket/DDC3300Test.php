<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3300
 */
class DDC3300Test extends OrmFunctionalTestCase
{
    public function testResolveTargetEntitiesChangesDiscriminatorMapValues()
    {
        $resolveTargetEntity = new ResolveTargetEntityListener();

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300Boss::class,
            DDC3300ConcreteBoss::class
        );

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300Employee::class,
            DDC3300ConcreteEmployee::class
        );

        $this->em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(DDC3300Person::class),
        ]);

        $boss     = new DDC3300ConcreteBoss();
        $employee = new DDC3300ConcreteEmployee();

        $this->em->persist($boss);
        $this->em->persist($employee);

        $this->em->flush();
        $this->em->clear();

        self::assertEquals($boss, $this->em->find(DDC3300Boss::class, $boss->id));
        self::assertEquals($employee, $this->em->find(DDC3300Employee::class, $employee->id));
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "boss"     = DDC3300Boss::class,
 *      "employee" = DDC3300Employee::class
 * })
 */
abstract class DDC3300Person
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}

interface DDC3300Boss
{
}

/** @ORM\Entity */
class DDC3300ConcreteBoss extends DDC3300Person implements DDC3300Boss
{
}

interface DDC3300Employee
{
}

/** @ORM\Entity */
class DDC3300ConcreteEmployee extends DDC3300Person implements DDC3300Employee
{
}
