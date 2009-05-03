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
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\ChildEntity'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\RelatedEntity')
        ));
    }

    public function testCRUD()
    {
        
        $parent = new ParentEntity;
        $parent->setData('foobar');

        $this->_em->save($parent);

        $child = new ChildEntity;
        $child->setData('thedata');
        $child->setNumber(1234);
        //$child->setRelatedEntity($relatedEntity);

        $this->_em->save($child);

        $relatedEntity = new RelatedEntity;
        $relatedEntity->setName('theRelatedOne');
        $relatedEntity->setOwner($child);

        $this->_em->save($relatedEntity);
        
        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\ParentEntity e");

        $entities = $query->getResultList();

        $this->assertEquals(2, count($entities));
        $this->assertTrue($entities[0] instanceof ParentEntity);
        $this->assertTrue($entities[1] instanceof ChildEntity);
        $this->assertTrue(is_numeric($entities[0]->getId()));
        $this->assertTrue(is_numeric($entities[1]->getId()));
        $this->assertEquals('foobar', $entities[0]->getData());
        $this->assertEquals('thedata', $entities[1]->getData());
        $this->assertEquals(1234, $entities[1]->getNumber());

        $this->_em->clear();

        $query = $this->_em->createQuery("select e from Doctrine\Tests\ORM\Functional\ChildEntity e");

        $entities = $query->getResultList();
        $this->assertEquals(1, count($entities));
        $this->assertTrue($entities[0] instanceof ChildEntity);
        $this->assertTrue(is_numeric($entities[0]->getId()));
        $this->assertEquals('thedata', $entities[0]->getData());
        $this->assertEquals(1234, $entities[0]->getNumber());

        $this->_em->clear();

        $query = $this->_em->createQuery("select r,o from Doctrine\Tests\ORM\Functional\RelatedEntity r join r.owner o");

        $entities = $query->getResultList();
        $this->assertEquals(1, count($entities));
        $this->assertTrue($entities[0] instanceof RelatedEntity);
        $this->assertTrue(is_numeric($entities[0]->getId()));
        $this->assertEquals('theRelatedOne', $entities[0]->getName());
        $this->assertTrue($entities[0]->getOwner() instanceof ChildEntity);
        $this->assertEquals('thedata', $entities[0]->getOwner()->getData());
        $this->assertSame($entities[0], $entities[0]->getOwner()->getRelatedEntity());

        $query = $this->_em->createQuery("update Doctrine\Tests\ORM\Functional\ChildEntity e set e.data = 'newdata'");

        $affected = $query->execute();
        $this->assertEquals(1, $affected);

        $query = $this->_em->createQuery("delete Doctrine\Tests\ORM\Functional\ParentEntity e");

        $affected = $query->execute();
        $this->assertEquals(2, $affected);
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
    /**
     * @DoctrineOneToOne(targetEntity="RelatedEntity")
     * @DoctrineJoinColumn(name="related_entity_id", referencedColumnName="id")
     */
    private $relatedEntity;

    public function getNumber() {
        return $this->number;
    }

    public function setNumber($number) {
        $this->number = $number;
    }

    public function getRelatedEntity() {
        return $this->relatedEntity;
    }

    public function setRelatedEntity($relatedEntity) {
        $this->relatedEntity = $relatedEntity;
        $relatedEntity->setOwner($this);
    }
}

/**
 * @DoctrineEntity
 */
class RelatedEntity {
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineGeneratedValue(strategy="auto")
     */
    private $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    private $name;
    /**
     * @DoctrineOneToOne(targetEntity="ChildEntity", mappedBy="relatedEntity")
     */
    private $owner;

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getOwner() {
        return $this->owner;
    }

    public function setOwner($owner) {
        $this->owner = $owner;
        if ($owner->getRelatedEntity() !== $this) {
            $owner->setRelatedEntity($this);
        }
    }
}
