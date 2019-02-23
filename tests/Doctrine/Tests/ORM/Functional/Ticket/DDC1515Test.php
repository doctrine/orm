<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1515
 */
class DDC1515Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1515Foo::class),
                $this->em->getClassMetadata(DDC1515Bar::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $bar = new DDC1515Bar();
        $this->em->persist($bar);
        $this->em->flush();

        $foo      = new DDC1515Foo();
        $foo->bar = $bar;
        $this->em->persist($foo);
        $this->em->flush();
        $this->em->clear();

        $bar = $this->em->find(DDC1515Bar::class, $bar->id);
        self::assertInstanceOf(DDC1515Foo::class, $bar->foo);
    }
}

/**
 * @ORM\Entity
 */
class DDC1515Foo
{
    /** @ORM\OneToOne(targetEntity=DDC1515Bar::class, inversedBy="foo") @ORM\Id */
    public $bar;
}

/**
 * @ORM\Entity
 */
class DDC1515Bar
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC1515Foo::class, mappedBy="bar") */
    public $foo;
}
