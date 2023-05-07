<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10049;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @requires PHP 8.1
 */
class SetInheritedReadOnlyPropertyValueTest extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            ReadOnlyPropertyOwner::class,
            ReadOnlyPropertyInheritor::class
        );
    }

    public function test(): void
    {
        $child = new ReadOnlyPropertyInheritor(10049);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(ReadOnlyPropertyInheritor::class, 10049);

        self::assertInstanceOf(ReadOnlyPropertyInheritor::class, $entity);
        self::assertSame(10049, $entity->id);
    }
}
