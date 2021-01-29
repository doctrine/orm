<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * @group DDC-1080
 */
class DDC1080Test extends OrmFunctionalTestCase
{
    public function testHydration(): void
    {
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC1080Foo::class),
                $this->_em->getClassMetadata(DDC1080Bar::class),
                $this->_em->getClassMetadata(DDC1080FooBar::class),
            ]
        );

        $foo1 = new DDC1080Foo();
        $foo1->setFooTitle('foo title 1');
        $foo2 = new DDC1080Foo();
        $foo2->setFooTitle('foo title 2');

        $bar1 = new DDC1080Bar();
        $bar1->setBarTitle('bar title 1');
        $bar2 = new DDC1080Bar();
        $bar2->setBarTitle('bar title 2');
        $bar3 = new DDC1080Bar();
        $bar3->setBarTitle('bar title 3');

        $foobar1 = new DDC1080FooBar();
        $foobar1->setFoo($foo1);
        $foobar1->setBar($bar1);
        $foobar1->setOrderNr(0);

        $foobar2 = new DDC1080FooBar();
        $foobar2->setFoo($foo1);
        $foobar2->setBar($bar2);
        $foobar2->setOrderNr(0);

        $foobar3 = new DDC1080FooBar();
        $foobar3->setFoo($foo1);
        $foobar3->setBar($bar3);
        $foobar3->setOrderNr(0);

        $this->_em->persist($foo1);
        $this->_em->persist($foo2);
        $this->_em->persist($bar1);
        $this->_em->persist($bar2);
        $this->_em->persist($bar3);
        $this->_em->flush();

        $this->_em->persist($foobar1);
        $this->_em->persist($foobar2);
        $this->_em->persist($foobar3);
        $this->_em->flush();
        $this->_em->clear();

        $foo     = $this->_em->find(DDC1080Foo::class, $foo1->getFooID());
        $fooBars = $foo->getFooBars();

        $this->assertEquals(3, count($fooBars), 'Should return three foobars.');
    }
}


/**
 * @Entity
 * @Table(name="foo")
 */
class DDC1080Foo
{
    /**
     * @Id
     * @Column(name="fooID", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $_fooID;
    /** @Column(name="fooTitle", type="string") */
    protected $_fooTitle;
    /**
     * @OneToMany(targetEntity="DDC1080FooBar", mappedBy="_foo",
     * cascade={"persist"})
     * @OrderBy({"_orderNr"="ASC"})
     */
    protected $_fooBars;

    public function __construct()
    {
        $this->_fooBars = new ArrayCollection();
    }

    /**
     * @return the $fooID
     */
    public function getFooID(): the
    {
        return $this->_fooID;
    }

    /**
     * @return the $fooTitle
     */
    public function getFooTitle(): the
    {
        return $this->_fooTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars(): the
    {
        return $this->_fooBars;
    }

    public function setFooID(field_type $fooID): void
    {
        $this->_fooID = $fooID;
    }

    public function setFooTitle(field_type $fooTitle): void
    {
        $this->_fooTitle = $fooTitle;
    }

    public function setFooBars(field_type $fooBars): void
    {
        $this->_fooBars = $fooBars;
    }
}
/**
 * @Entity
 * @Table(name="bar")
 */
class DDC1080Bar
{
    /**
     * @Id
     * @Column(name="barID", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $_barID;
    /** @Column(name="barTitle", type="string") */
    protected $_barTitle;
    /**
     * @OneToMany(targetEntity="DDC1080FooBar", mappedBy="_bar",
     * cascade={"persist"})
     * @OrderBy({"_orderNr"="ASC"})
     */
    protected $_fooBars;

    public function __construct()
    {
        $this->_fooBars = new ArrayCollection();
    }

    /**
     * @return the $barID
     */
    public function getBarID(): the
    {
        return $this->_barID;
    }

    /**
     * @return the $barTitle
     */
    public function getBarTitle(): the
    {
        return $this->_barTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars(): the
    {
        return $this->_fooBars;
    }

    public function setBarID(field_type $barID): void
    {
        $this->_barID = $barID;
    }

    public function setBarTitle(field_type $barTitle): void
    {
        $this->_barTitle = $barTitle;
    }

    public function setFooBars(field_type $fooBars): void
    {
        $this->_fooBars = $fooBars;
    }
}

/**
 * @Table(name="fooBar")
 * @Entity
 */
class DDC1080FooBar
{
    /**
     * @ManyToOne(targetEntity="DDC1080Foo")
     * @JoinColumn(name="fooID", referencedColumnName="fooID")
     * @Id
     */
    protected $_foo = null;
    /**
     * @ManyToOne(targetEntity="DDC1080Bar")
     * @JoinColumn(name="barID", referencedColumnName="barID")
     * @Id
     */
    protected $_bar = null;
    /**
     * @var int orderNr
     * @Column(name="orderNr", type="integer", nullable=false)
     */
    protected $_orderNr = null;

    /**
     * Retrieve the foo property
     */
    public function getFoo(): DDC1080Foo
    {
        return $this->_foo;
    }

    /**
     * Set the foo property
     */
    public function setFoo(DDC1080Foo $foo): DDC1080FooBar
    {
        $this->_foo = $foo;

        return $this;
    }

    /**
     * Retrieve the bar property
     */
    public function getBar(): DDC1080Bar
    {
        return $this->_bar;
    }

    /**
     * Set the bar property
     */
    public function setBar(DDC1080Bar $bar): DDC1080FooBar
    {
        $this->_bar = $bar;

        return $this;
    }

    /**
     * Retrieve the orderNr property
     */
    public function getOrderNr(): ?int
    {
        return $this->_orderNr;
    }

    /**
     * Set the orderNr property
     */
    public function setOrderNr(?int $orderNr): DDC1080FooBar
    {
        $this->_orderNr = $orderNr;

        return $this;
    }
}
