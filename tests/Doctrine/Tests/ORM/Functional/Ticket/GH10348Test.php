<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\GH10348\GH10348ChildEntity;
use Doctrine\Tests\Models\GH10348\GH10348ParentEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 7.4
 */
final class GH10348Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10348ChildEntity::class,
            GH10348ParentEntity::class,
        ]);
    }

    public function testCanRemoveParentWithChildRelatesToOwnEntity(): void
    {
        $child1 = new GH10348ChildEntity();
        $child2 = new GH10348ChildEntity();
        $child2->setOrigin($child1);

        $parent = new GH10348ParentEntity();
        $parent->addChild($child1)->addChild($child2);

        $this->_em->persist($parent);
        $this->_em->flush();

        $parent = $this->_em->find(GH10348ParentEntity::class, $parent->getId());

        $this->_em->remove($parent);

        $this->_em->flush();

        // No exception thrown
        self::assertNull(null);
    }
}
