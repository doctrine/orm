<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

use function array_keys;
use function get_class;

class DDC656Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC656Entity::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    public function testRecomputeSingleEntityChangeSet_PreservesFieldOrder(): void
    {
        $entity = new DDC656Entity();
        $entity->setName('test1');
        $entity->setType('type1');
        $this->_em->persist($entity);

        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata(get_class($entity)), $entity);
        $data1 = $this->_em->getUnitOfWork()->getEntityChangeSet($entity);
        $entity->setType('type2');
        $this->_em->getUnitOfWork()->recomputeSingleEntityChangeSet($this->_em->getClassMetadata(get_class($entity)), $entity);
        $data2 = $this->_em->getUnitOfWork()->getEntityChangeSet($entity);

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
    /** @Column(type="string") */
    public $name;

    /** @Column(type="string") */
    public $type;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $specificationId;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }
}
