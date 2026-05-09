<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH9538Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH9538EntityA::class,
        ]);
    }

    public function testCanRemoveEntityWithReadonlyId(): void
    {
        $this->_em->persist($entity = new GH9538EntityA());
        $this->_em->flush();
        $this->_em->remove($entity);
        $this->_em->flush();
        $this->expectNotToPerformAssertions();
    }
}

#[Entity]
class GH9538EntityA
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public readonly int $id;
}
