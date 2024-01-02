<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\GH10336\GH10336Entity;
use Doctrine\Tests\Models\GH10336\GH10336Relation;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 7.4
 */
final class GH10336Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10336Entity::class,
            GH10336Relation::class
        );
    }

    public function testCanAccessRelationPropertyAfterClear(): void
    {
        $relation         = new GH10336Relation();
        $relation->value  = 'foo';
        $entity           = new GH10336Entity();
        $entity->relation = $relation;

        $this->_em->persist($entity);
        $this->_em->persist($relation);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(GH10336Entity::class, 1);

        $this->_em->clear();

        $this->assertSame('foo', $entity->relation->value);
    }
}
