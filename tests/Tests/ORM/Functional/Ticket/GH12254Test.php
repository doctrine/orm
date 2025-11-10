<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH12254Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH12254EntityA::class,
        ]);

        $this->_em->persist(new GH12254EntityA());
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testFindByEmptyArrayShouldReturnEmptyArray(): void
    {
        // pretend we are starting afresh
        $this->_em = $this->getEntityManager();
        $result    = $this->_em->getRepository(GH12254EntityA::class)->findBy(['id' => []]);
        $this->assertEmpty($result);
    }

    public function testFindByInNullableField(): void
    {
        $this->_em = $this->getEntityManager();
        $result    = $this->_em->getRepository(GH12254EntityA::class)->findBy(['name' => []]);
        $this->assertEmpty($result);
    }
}

#[Entity]
class GH12254EntityA
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', nullable: true)]
    public string|null $name = null;
}
