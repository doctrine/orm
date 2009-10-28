<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * MappedSuperclassTest
 *
 * @author robo
 */
class MappedSuperclassTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\EntitySubClass'),
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testCRUD()
    {
        $e = new EntitySubClass;
        $e->setId(1);
        $e->setName('Roman');
        $e->setMapped1(42);
        $e->setMapped2('bar');
        
        $this->_em->persist($e);
        $this->_em->flush();
        $this->_em->clear();
        
        $e2 = $this->_em->find('Doctrine\Tests\ORM\Functional\EntitySubClass', 1);
        $this->assertEquals(1, $e2->getId());
        $this->assertEquals('Roman', $e2->getName());
        $this->assertNull($e2->getMappedRelated1());
        $this->assertEquals(42, $e2->getMapped1());
        $this->assertEquals('bar', $e2->getMapped2());
    }
}

/** @MappedSuperclass */
class MappedSuperclassBase {
    /** @Column(type="integer") */
    private $mapped1;
    /** @Column(type="string") */
    private $mapped2;
    /**
     * @OneToOne(targetEntity="MappedSuperclassRelated1")
     * @JoinColumn(name="related1_id", referencedColumnName="id")
     */
    private $mappedRelated1;
    private $transient;
    
    public function setMapped1($val) {
        $this->mapped1 = $val;
    }
    
    public function getMapped1() {
        return $this->mapped1;
    }
    
    public function setMapped2($val) {
        $this->mapped2 = $val;
    }
    
    public function getMapped2() {
        return $this->mapped2;
    }
    
    public function getMappedRelated1() {
        return $this->mappedRelated1;
    }
}

/** @Entity */
class MappedSuperclassRelated1 {
    /** @Id @Column(type="integer") */
    private $id;
    /** @Column(type="string") */
    private $name;
}

/** @Entity */
class EntitySubClass extends MappedSuperclassBase {
    /** @Id @Column(type="integer") */
    private $id;
    /** @Column(type="string") */
    private $name;
    
    public function setName($name) {
        $this->name = $name;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
}

