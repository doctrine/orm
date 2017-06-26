<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\OneToOneForeignEntityPrimaryKey\Entity;
use Doctrine\Tests\Models\OneToOneForeignEntityPrimaryKey\ForeignEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping where the foreign entity has the entity as primary key.
 */
class OneToOneForeignEntityPrimaryKey extends OrmFunctionalTestCase
{

    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var ForeignEntity
     */
    private $foreignEntity;

    protected function setUp()
    {
        $this->useModelSet('issue6526');
        parent::setUp();

        $this->entity = new Entity();
        $this->foreignEntity = new ForeignEntity();
    }

    /**
     * @group #6526
     */
    public function testCanCascadePersist()
    {
        $entity = $this->entity;
        $entity->setForeignEntity($this->foreignEntity);

        $em = $this->_em;
        $em->persist($entity);
    }
}
