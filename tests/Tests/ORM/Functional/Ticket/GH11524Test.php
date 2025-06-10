<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\Tests\Models\GH11524\GH11524Entity;
use Doctrine\Tests\Models\GH11524\GH11524Listener;
use Doctrine\Tests\Models\GH11524\GH11524Relation;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11524Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH11524Entity::class,
            GH11524Relation::class,
        );

        $this->_em->getEventManager()->addEventListener(Events::postLoad, new GH11524Listener());
    }

    public function testPostLoadCalledOnProxy(): void
    {
        $relation       = new GH11524Relation();
        $relation->name = 'test';
        $this->_em->persist($relation);

        $entity           = new GH11524Entity();
        $entity->relation = $relation;

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->clear();

        $reloadedEntity = $this->_em->find(GH11524Entity::class, $entity->id);

        $reloadedRelation = $reloadedEntity->relation;

        $this->assertTrue($this->isUninitializedObject($reloadedRelation));

        $this->assertSame('fake', $reloadedRelation->getTranslation(), 'The property set by the postLoad listener must get initialized on usage.');
    }
}
