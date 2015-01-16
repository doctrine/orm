<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\ResolveTargetEntityListener;

/**
 * @group DDC-3300
 */
class DDC3300Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $resolveTargetEntity = new ResolveTargetEntityListener();

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300BossInterface::INTERFACENAME,
            DDC3300Boss::CLASSNAME,
            array()
        );

        $resolveTargetEntity->addResolveTargetEntity(
            DDC3300EmployeeInterface::INTERFACENAME,
            DDC3300Employee::CLASSNAME,
            array()
        );

        $this->_em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3300Person::CLASSNAME),
        ));

        $this->_em->persist(new DDC3300Boss());
        $this->_em->persist(new DDC3300Employee());

        $this->_em->flush();
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
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;
}

interface DDC3300BossInterface
{
    const INTERFACENAME = __CLASS__;
}

/** @Entity */
class DDC3300Boss extends DDC3300Person implements DDC3300BossInterface
{
    const CLASSNAME = __CLASS__;
}

interface DDC3300EmployeeInterface
{
    const INTERFACENAME = __CLASS__;
}

/** @Entity */
class DDC3300Employee extends DDC3300Person implements DDC3300EmployeeInterface
{
    const CLASSNAME = __CLASS__;
}
