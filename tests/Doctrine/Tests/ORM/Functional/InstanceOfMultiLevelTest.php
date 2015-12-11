<?php

namespace Doctrine\Tests\ORM\Functional {

    use Doctrine\Tests\OrmFunctionalTestCase;

    class InstanceOfMultiLevelTest extends OrmFunctionalTestCase
    {
        protected function setUp()
        {
            parent::setUp();

            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfMultiLevelTest\Person'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfMultiLevelTest\Employee'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfMultiLevelTest\Engineer'),
            ));
        }

        public function testInstanceOf()
        {
            $this->loadData();

            $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Person p
                    WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Person';
            $query = $this->_em->createQuery($dql);
            $result = $query->getResult();

            $this->assertCount(3, $result);

            foreach ($result as $r) {
                $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Person', $r);
                if ($r instanceof InstanceOfMultiLevelTest\Engineer) {
                    $this->assertEquals('foobar', $r->getName());
                    $this->assertEquals('doctrine', $r->getSpecialization());
                } elseif ($r instanceof InstanceOfMultiLevelTest\Employee) {
                    $this->assertEquals('bar', $r->getName());
                    $this->assertEquals('qux', $r->getDepartement());
                } else {
                    $this->assertEquals('foo', $r->getName());
                }
            }
        }

        private function loadData()
        {
            $person = new InstanceOfMultiLevelTest\Person();
            $person->setName('foo');

            $employee = new InstanceOfMultiLevelTest\Employee();
            $employee->setName('bar');
            $employee->setDepartement('qux');

            $engineer = new InstanceOfMultiLevelTest\Engineer();
            $engineer->setName('foobar');
            $engineer->setDepartement('dep');
            $engineer->setSpecialization('doctrine');

            $this->_em->persist($person);
            $this->_em->persist($employee);
            $this->_em->persist($engineer);

            $this->_em->flush(array($person, $employee, $engineer));
        }
    }
}

namespace Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest {
    /**
     * @Entity()
     * @Table(name="instance_of_multi_level_test_person")
     * @InheritanceType(value="JOINED")
     * @DiscriminatorColumn(name="kind", type="string")
     * @DiscriminatorMap(value={
     *     "person": "Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Person",
     *     "employee": "Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Employee",
     *     "engineer": "Doctrine\Tests\ORM\Functional\InstanceOfMultiLevelTest\Engineer",
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
     * @Table(name="instance_of_multi_level_employee")
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

    /**
     * @Entity()
     * @Table(name="instance_of_multi_level_engineer")
     */
    class Engineer extends Employee
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
}
