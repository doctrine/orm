<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

final class GH10063Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH10063Entity::class);
    }

    /**
     * @requires PHP 8.1
     */
    public function testArrayOfEnums(): void
    {
        $entity = (new GH10063Entity())->setColors([GH10063Enum::Red, GH10063Enum::Green]);

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(GH10063Entity::class, $entity->id);
        assert($entity instanceof GH10063Entity);
        self::assertEquals([GH10063Enum::Red, GH10063Enum::Green], $entity->getColors());
    }
}
