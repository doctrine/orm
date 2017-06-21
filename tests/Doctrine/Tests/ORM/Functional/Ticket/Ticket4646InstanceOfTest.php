<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(PersonTicket4646::class),
            $this->_em->getClassMetadata(EmployeeTicket4646::class),
        ]);
    }

    public function testInstanceOf()
    {
        $this->loadData();

        $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646 p
                WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646';
        $query = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(2, $result);

        foreach ($result as $r) {
            $this->assertInstanceOf(PersonTicket4646::class, $r);
            if ($r instanceof EmployeeTicket4646) {
                $this->assertEquals('bar', $r->getName());
            } else {
                $this->assertEquals('foo', $r->getName());
            }
        }
    }

    private function loadData()
    {
        $person = new PersonTicket4646();
        $person->setName('foo');

        $employee = new EmployeeTicket4646();
        $employee->setName('bar');
        $employee->setDepartement('qux');

        $this->_em->persist($person);
        $this->_em->persist($employee);

        $this->_em->flush(array($person, $employee));
    }
}

/**
 * @Entity()
 * @Table(name="instance_of_test_person")
 * @InheritanceType(value="JOINED")
 * @DiscriminatorColumn(name="kind", type="string")
 * @DiscriminatorMap(value={
 *     "person": "Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646",
 *     "employee": "Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646"
 * })
 */
class PersonTicket4646
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
 * @Table(name="instance_of_test_employee")
 */
class EmployeeTicket4646 extends PersonTicket4646
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
