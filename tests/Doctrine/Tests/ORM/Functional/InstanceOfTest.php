<?php

namespace Doctrine\Tests\ORM\Functional {

    use Doctrine\Tests\OrmFunctionalTestCase;

    class InstanceOfTest extends OrmFunctionalTestCase
    {
        protected function setUp()
        {
            parent::setUp();

            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfTest\Person'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfTest\Employee'),
            ));
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
                $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\InstanceOfTest\Person', $r);
                if ($r instanceof InstanceOfTest\Employee) {
                    $this->assertEquals('bar', $r->getName());
                } else {
                    $this->assertEquals('foo', $r->getName());
                }
            }
        }

        private function loadData()
        {
            $person = new InstanceOfTest\Person();
            $person->setName('foo');

            $employee = new InstanceOfTest\Employee();
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
