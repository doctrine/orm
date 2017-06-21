<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfAbstractTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(PersonTicket4646Abstract::class),
            $this->_em->getClassMetadata(EmployeeTicket4646Abstract::class),
        ]);
    }

    public function testInstanceOf()
    {
        $this->loadData();

        $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Abstract p
                WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Abstract';
        $query = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(1, $result);

        foreach ($result as $r) {
            $this->assertInstanceOf(PersonTicket4646Abstract::class, $r);
            $this->assertInstanceOf(EmployeeTicket4646Abstract::class, $r);
            $this->assertSame('bar', $r->getName());
        }
    }

    private function loadData()
    {
        $employee = new EmployeeTicket4646Abstract();
        $employee->setName('bar');
        $employee->setDepartement('qux');

        $this->_em->persist($employee);

        $this->_em->flush($employee);
    }
}

/**
 * @Entity()
 * @Table(name="instance_of_abstract_test_person")
 * @InheritanceType(value="JOINED")
 * @DiscriminatorColumn(name="kind", type="string")
 * @DiscriminatorMap(value={
 *     "employee": EmployeeTicket4646Abstract::class
 * })
 */
abstract class PersonTicket4646Abstract
{
    /**
     * @Id()
     * @GeneratedValue()
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity()
 * @Table(name="instance_of_abstract_test_employee")
 */
class EmployeeTicket4646Abstract extends PersonTicket4646Abstract
{
    /**
     * @Column(type="string")
     */
    private $departement;

    public function getDepartement()
    {
        return $this->departement;
    }

    public function setDepartement($departement)
    {
        $this->departement = $departement;
    }
}
