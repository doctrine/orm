<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

class DDC656Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC656Entity::class)
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testRecomputeSingleEntityChangeSet_PreservesFieldOrder()
    {
        $entity = new DDC656Entity();
        $entity->setName('test1');
        $entity->setType('type1');
        $this->em->persist($entity);

        $this->em->getUnitOfWork()->computeChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
        $data1 = $this->em->getUnitOfWork()->getEntityChangeSet($entity);
        $entity->setType('type2');
        $this->em->getUnitOfWork()->recomputeSingleEntityChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
        $data2 = $this->em->getUnitOfWork()->getEntityChangeSet($entity);

        self::assertEquals(array_keys($data1), array_keys($data2));

        $this->em->flush();
        $this->em->clear();

        $persistedEntity = $this->em->find(get_class($entity), $entity->specificationId);
        self::assertEquals('type2', $persistedEntity->getType());
        self::assertEquals('test1', $persistedEntity->getName());
    }
}

/**
 * @ORM\Entity
 */
class DDC656Entity
{
    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\Column(type="string")
     */
    public $type;

    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
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
