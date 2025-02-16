<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10049;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH10049Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            ReadOnlyPropertyOwner::class,
            ReadOnlyPropertyInheritor::class,
        );
    }

    /** @doesNotPerformAssertions */
    public function testInheritedReadOnlyPropertyValueCanBeSet(): void
    {
        $child = new ReadOnlyPropertyInheritor(10049);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->find(ReadOnlyPropertyInheritor::class, 10049);
    }
}
