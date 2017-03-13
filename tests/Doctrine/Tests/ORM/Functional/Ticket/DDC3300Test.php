<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;

/**
 * @group DDC-3300
 */
class DDC3300Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testResolveTargetEntitiesChangesDiscriminatorMapValues()
    {
        $resolveTargetEntity = new ResolveTargetEntityListener();

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300BossInterface::class,
            DDC3300Boss::class
        );

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300EmployeeInterface::class,
            DDC3300Employee::class
        );

        $this->em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(DDC3300Person::class),
        ]);

        $boss     = new DDC3300Boss();
        $employee = new DDC3300Employee();

        $this->em->persist($boss);
        $this->em->persist($employee);

        $this->em->flush();
        $this->em->clear();

        self::assertEquals($boss, $this->em->find(DDC3300BossInterface::class, $boss->id));
        self::assertEquals($employee, $this->em->find(DDC3300EmployeeInterface::class, $employee->id));
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "boss"     = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300BossInterface",
 *      "employee" = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300EmployeeInterface"
 * })
 */
abstract class DDC3300Person
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}

interface DDC3300BossInterface
{
}

/** @ORM\Entity */
class DDC3300Boss extends DDC3300Person implements DDC3300BossInterface
{
}

interface DDC3300EmployeeInterface
{
}

/** @ORM\Entity */
class DDC3300Employee extends DDC3300Person implements DDC3300EmployeeInterface
{
}
