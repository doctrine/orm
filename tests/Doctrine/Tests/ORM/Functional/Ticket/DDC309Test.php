<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;

class DDC309Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC309Country::class, DDC309User::class);
    }

    public function testTwoToIterableHydrations(): void
    {
        $c1 = new DDC309Country();
        $c2 = new DDC309Country();
        $u1 = new DDC309User();
        $u2 = new DDC309User();

        $this->_em->persist($c1);
        $this->_em->persist($c2);
        $this->_em->persist($u1);
        $this->_em->persist($u2);
        $this->_em->flush();
        $this->_em->clear();

        /** @var Generator<int, DDC309Country> $q */
        $q = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309Country c')->toIterable();
        $c = $q->current();

        self::assertEquals(1, $c->id);

        /** @var Generator<int, DDC309User> $r */
        $r = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309User u')->toIterable();
        $u = $r->current();

        self::assertEquals(1, $u->id);

        $q->next();
        $r->next();
        $c = $q->current();
        $u = $r->current();

        self::assertEquals(2, $c->id);
        self::assertEquals(2, $u->id);

        do {
            $q->next();
        } while ($q->valid());

        do {
            $r->next();
        } while ($r->valid());
    }
}

#[Entity]
class DDC309Country
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class DDC309User
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue]
    public $id;
}
