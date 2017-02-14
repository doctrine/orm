<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-2645
 */
class DDC2645Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $bar = new DDC2645Bar;
        $bar->id = 123;

        $foo = new DDC2645Foo(1, $bar, 'Foo');
        $foo2 = new DDC2645Foo(1, $bar, 'Bar');

        $this->em->persist($bar);
        $this->em->persist($foo);

        $foo3 = $this->em->merge($foo2);

        self::assertSame($foo, $foo3);
        self::assertEquals('Bar', $foo->name);
    }
}

/** @ORM\Entity */
class DDC2645Foo
{
    /** @ORM\Id @ORM\Column(type="integer") */
    private $id;

    /** @ORM\Id @ORM\ManyToOne(targetEntity="DDC2645Bar") */
    private $bar;

    /** @ORM\Column */
    public $name;

    public function __construct($id, $bar, $name)
    {
        $this->id = $id;
        $this->bar = $bar;
        $this->name = $name;
    }
}

/** @ORM\Entity */
class DDC2645Bar
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="NONE") */
    public $id;
}
