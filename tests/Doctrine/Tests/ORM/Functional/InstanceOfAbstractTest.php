<?php

namespace Doctrine\Tests\ORM\Functional {

    use Doctrine\Tests\OrmFunctionalTestCase;

    class InstanceOfAbstractTest extends OrmFunctionalTestCase
    {
        protected function setUp()
        {
            parent::setUp();

            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfAbstractTest\Person'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\InstanceOfAbstractTest\Employee'),
            ));
        }

        public function testInstanceOf()
        {
            $this->loadData();

            $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest\Person p
                    WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest\Person';
            $query = $this->_em->createQuery($dql);
            $result = $query->getResult();

            $this->assertCount(1, $result);

            foreach ($result as $r) {
                $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest\Person', $r);
                $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest\Employee', $r);
                $this->assertEquals('bar', $r->getName());
            }
        }

        private function loadData()
        {
            $employee = new InstanceOfAbstractTest\Employee();
            $employee->setName('bar');
            $employee->setDepartement('qux');

            $this->_em->persist($employee);

            $this->_em->flush($employee);
        }
    }
}

namespace Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest {

    /**
     * @Entity()
     * @Table(name="instance_of_abstract_test_person")
     * @InheritanceType(value="JOINED")
     * @DiscriminatorColumn(name="kind", type="string")
     * @DiscriminatorMap(value={
     *     "employee": "Doctrine\Tests\ORM\Functional\InstanceOfAbstractTest\Employee"
     * })
     */
    abstract class Person
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
