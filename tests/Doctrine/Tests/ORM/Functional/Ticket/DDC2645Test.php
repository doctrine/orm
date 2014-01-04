<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

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

        $this->_em->persist($bar);
        $this->_em->persist($foo);

        $foo3 = $this->_em->merge($foo2);

        $this->assertSame($foo, $foo3);
        $this->assertEquals('Bar', $foo->name);
    }
}

/** @Entity */
class DDC2645Foo
{
    /** @Id @Column(type="integer") */
    private $id;

    /** @Id @ManyToOne(targetEntity="DDC2645Bar") */
    private $bar;

    /** @Column */
    public $name;

    public function __construct($id, $bar, $name)
    {
        $this->id = $id;
        $this->bar = $bar;
        $this->name = $name;
    }
}

/** @Entity */
class DDC2645Bar
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="NONE") */
    public $id;
}
