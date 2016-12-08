<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

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
            DDC3300Boss::class,
            []
        );

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300EmployeeInterface::class,
            DDC3300Employee::class,
            []
        );

        $this->_em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC3300Person::class),
            ]
        );

        $boss     = new DDC3300Boss();
        $employee = new DDC3300Employee();

        $this->_em->persist($boss);
        $this->_em->persist($employee);

        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals($boss, $this->_em->find(DDC3300BossInterface::class, $boss->id));
        $this->assertEquals($employee, $this->_em->find(DDC3300EmployeeInterface::class, $employee->id));
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DdiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "boss"     = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300BossInterface",
 *      "employee" = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300EmployeeInterface"
 * })
 */
abstract class DDC3300Person
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}

interface DDC3300BossInterface
{
}

/** @Entity */
class DDC3300Boss extends DDC3300Person implements DDC3300BossInterface
{
}

interface DDC3300EmployeeInterface
{
}

/** @Entity */
class DDC3300Employee extends DDC3300Person implements DDC3300EmployeeInterface
{
}
