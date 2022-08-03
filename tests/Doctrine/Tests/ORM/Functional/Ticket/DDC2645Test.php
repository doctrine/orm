<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-2645
 */
class DDC2645Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    public function testIssue(): void
    {
        $bar     = new DDC2645Bar();
        $bar->id = 123;

        $foo  = new DDC2645Foo(1, $bar, 'Foo');
        $foo2 = new DDC2645Foo(1, $bar, 'Bar');

        $this->_em->persist($bar);
        $this->_em->persist($foo);

        $foo3 = $this->_em->merge($foo2);

        $this->assertSame($foo, $foo3);
        $this->assertEquals('Bar', $foo->name);
        $this->assertHasDeprecationMessages();
    }
}

/** @Entity */
class DDC2645Foo
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @var DDC2645Bar
     * @Id
     * @ManyToOne(targetEntity="DDC2645Bar")
     */
    private $bar;

    /**
     * @var string
     * @Column
     */
    public $name;

    public function __construct(int $id, DDC2645Bar $bar, string $name)
    {
        $this->id   = $id;
        $this->bar  = $bar;
        $this->name = $name;
    }
}

/** @Entity */
class DDC2645Bar
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="NONE")
     */
    public $id;
}
