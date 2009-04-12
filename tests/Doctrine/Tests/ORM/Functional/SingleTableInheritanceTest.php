<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional tests for the Single Table Inheritance mapping strategy.
 *
 * @author robo
 */
class SingleTableInheritanceTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\ParentEntity'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\ChildEntity')
        ));
    }

    public function testInsert()
    {
        
        $parent = new ParentEntity;
        $parent->setData('foobar');

        $this->_em->save($parent);
        
        $child = new ChildEntity;
        $child->setData('thedata');
        $child->setNumber(1234);

        $this->_em->save($child);
        
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\ParentEntity e");

        $entities = $query->getResultList();

        $this->assertEquals(2, count($entities));
        $this->assertTrue($entities[0] instanceof ParentEntity);
        $this->assertTrue($entities[1] instanceof ChildEntity);
        $this->assertEquals('foobar', $entities[0]->getData());
        $this->assertEquals('thedata', $entities[1]->getData());
        $this->assertEquals(1234, $entities[1]->getNumber());
    }
}

/**
 * @DoctrineEntity
 * @DoctrineInheritanceType("singleTable")
 * @DoctrineDiscriminatorColumn(name="discr", type="varchar")
 * @DoctrineSubClasses({"Doctrine\Tests\ORM\Functional\ChildEntity"})
 * @DoctrineDiscriminatorValue("parent")
 */
class ParentEntity {
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    private $id;

    /**
     * @DoctrineColumn(type="varchar")
     */
    private $data;

    public function getId() {
        return $this->id;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }
}

/**
 * @DoctrineEntity
 * @DoctrineDiscriminatorValue("child")
 */
class ChildEntity extends ParentEntity {
    /**
     * @DoctrineColumn(type="integer", nullable=true)
     */
    private $number;

    public function getNumber() {
        return $this->number;
    }

    public function setNumber($number) {
        $this->number = $number;
    }
}

