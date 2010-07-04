<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;

require_once __DIR__ . '/../../../TestInit.php';

class DDC656Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC656Entity')
            ));
        } catch(\Exception $e) {
            
        }
    }

    public function testRecomputeSingleEntityChangeSet_PreservesFieldOrder()
    {
        $entity = new DDC656Entity();
        $entity->setName('test1');
        $entity->setType('type1');
        $this->_em->persist($entity);

        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata(get_class($entity)), $entity);
        $data1 = $this->_em->getUnitOfWork()->getEntityChangeset($entity);
        $entity->setType('type2');
        $this->_em->getUnitOfWork()->recomputeSingleEntityChangeSet($this->_em->getClassMetadata(get_class($entity)), $entity);
        $data2 = $this->_em->getUnitOfWork()->getEntityChangeset($entity);

        $this->assertEquals(array_keys($data1), array_keys($data2));

        $this->_em->flush();
        $this->_em->clear();

        $persistedEntity = $this->_em->find(get_class($entity), $entity->specificationId);
        $this->assertEquals('type2', $persistedEntity->getType());
        $this->assertEquals('test1', $persistedEntity->getName());
    }
}

/**
 * @Entity
 */
class DDC656Entity
{
    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @Column(type="string")
     */
    public $type;

    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $specificationId;

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }
}