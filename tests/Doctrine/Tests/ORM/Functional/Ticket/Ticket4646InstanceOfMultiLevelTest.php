<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfMultiLevelTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(PersonTicket4646MultiLevel::class),
            $this->_em->getClassMetadata(EmployeeTicket4646MultiLevel::class),
            $this->_em->getClassMetadata(EngineerTicket4646MultiLevel::class),
        ]);
    }

    public function testInstanceOf()
    {
        $this->loadData();

        $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646MultiLevel p
                WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646MultiLevel';
        $query = $this->_em->createQuery($dql);
        $result = $query->getResult();

        $this->assertCount(3, $result);

        foreach ($result as $r) {
            $this->assertInstanceOf(PersonTicket4646MultiLevel::class, $r);
            if ($r instanceof EngineerTicket4646MultiLevel) {
                $this->assertEquals('foobar', $r->getName());
                $this->assertEquals('doctrine', $r->getSpecialization());
            } elseif ($r instanceof EmployeeTicket4646MultiLevel) {
                $this->assertEquals('bar', $r->getName());
                $this->assertEquals('qux', $r->getDepartement());
            } else {
                $this->assertEquals('foo', $r->getName());
            }
        }
    }

    private function loadData()
    {
        $person = new PersonTicket4646MultiLevel();
        $person->setName('foo');

        $employee = new EmployeeTicket4646MultiLevel();
        $employee->setName('bar');
        $employee->setDepartement('qux');

        $engineer = new EngineerTicket4646MultiLevel();
        $engineer->setName('foobar');
        $engineer->setDepartement('dep');
        $engineer->setSpecialization('doctrine');

        $this->_em->persist($person);
        $this->_em->persist($employee);
        $this->_em->persist($engineer);

        $this->_em->flush(array($person, $employee, $engineer));
    }
}

/**
 * @Entity()
 * @Table(name="instance_of_multi_level_test_person")
 * @InheritanceType(value="JOINED")
 * @DiscriminatorColumn(name="kind", type="string")
 * @DiscriminatorMap(value={
 *     "person": "Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646MultiLevel",
 *     "employee": "Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646MultiLevel",
 *     "engineer": "Doctrine\Tests\ORM\Functional\Ticket\EngineerTicket4646MultiLevel",
 * })
 */
class PersonTicket4646MultiLevel
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
 * @Table(name="instance_of_multi_level_employee")
 */
class EmployeeTicket4646MultiLevel extends PersonTicket4646MultiLevel
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

/**
 * @Entity()
 * @Table(name="instance_of_multi_level_engineer")
 */
class EngineerTicket4646MultiLevel extends EmployeeTicket4646MultiLevel
{
    /**
     * @Column(type="string")
     */
    private $specialization;

    public function getSpecialization()
    {
        return $this->specialization;
    }

    public function setSpecialization($specialization)
    {
        $this->specialization = $specialization;
    }
}
