<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveDiscriminatorMapListener;

/**
 * @group DDC-3300
 */
class DDC3300Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $this
            ->_em
            ->getEventManager()
            ->addEventListener(
                Events::loadClassMetadata,
                new ResolveDiscriminatorMapListener(array(
                    'Doctrine\Tests\ORM\Functional\Ticket\DDC3300BossInterface'     => 'Doctrine\Tests\ORM\Functional\Ticket\DDC3300Boss',
                    'Doctrine\Tests\ORM\Functional\Ticket\DDC3300EmployeeInterface' => 'Doctrine\Tests\ORM\Functional\Ticket\DDC3300Employee',
                ))
            );

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC3300Person'),
        ));

        $boss = new DDC3300Boss();
        $this->_em->persist($boss);

        $employee = new DDC3300Employee();
        $this->_em->persist($employee);

        $this->_em->flush();
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DdiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "boss" = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300BossInterface",
 *      "employee" = "Doctrine\Tests\ORM\Functional\Ticket\DDC3300EmployeeInterface"
 * })
 */
abstract class DDC3300Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

interface DDC3300BossInterface
{

}

/**
 * @Entity
 */
class DDC3300Boss extends DDC3300Person implements DDC3300BossInterface
{
}

interface DDC3300EmployeeInterface
{

}

/**
 * @Entity
 */
class DDC3300Employee extends DDC3300Person implements DDC3300EmployeeInterface
{
}
 