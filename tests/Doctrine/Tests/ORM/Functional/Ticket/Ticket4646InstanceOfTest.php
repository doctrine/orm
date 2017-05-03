<?php

namespace Doctrine\Tests\ORM\Functional\Ticket {

    use Doctrine\Tests\ORM\Functional\InstanceOfTest\Employee;
    use Doctrine\Tests\ORM\Functional\InstanceOfTest\Person;
    use Doctrine\Tests\OrmFunctionalTestCase;

    class Ticket4646InstanceOfTest extends OrmFunctionalTestCase
    {
        protected function setUp()
        {
            parent::setUp();

            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(Person::class),
                $this->_em->getClassMetadata(Employee::class),
            ]);
        }

        public function testInstanceOf()
        {
            $this->loadData();

            $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\InstanceOfTest\Person p
                    WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\InstanceOfTest\Person';
            $query = $this->_em->createQuery($dql);
            $result = $query->getResult();

            $this->assertCount(2, $result);

            foreach ($result as $r) {
                $this->assertInstanceOf(Person::class, $r);
                if ($r instanceof Employee) {
                    $this->assertEquals('bar', $r->getName());
                } else {
                    $this->assertEquals('foo', $r->getName());
                }
            }
        }

        private function loadData()
        {
            $person = new Person();
            $person->setName('foo');

            $employee = new Employee();
            $employee->setName('bar');
            $employee->setDepartement('qux');

            $this->_em->persist($person);
            $this->_em->persist($employee);

            $this->_em->flush(array($person, $employee));
        }
    }
}

namespace Doctrine\Tests\ORM\Functional\InstanceOfTest {
    /**
     * @Entity()
     * @Table(name="instance_of_test_person")
     * @InheritanceType(value="JOINED")
     * @DiscriminatorColumn(name="kind", type="string")
     * @DiscriminatorMap(value={
     *     "person": "Doctrine\Tests\ORM\Functional\InstanceOfTest\Person",
     *     "employee": "Doctrine\Tests\ORM\Functional\InstanceOfTest\Employee"
     * })
     */
    class Person
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
    class Employee extends Person
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
}
