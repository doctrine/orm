<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1080
 */
class DDC1080Test extends OrmFunctionalTestCase
{
    public function testHydration()
    {
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1080Foo::class),
                $this->em->getClassMetadata(DDC1080Bar::class),
                $this->em->getClassMetadata(DDC1080FooBar::class),
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

        $this->em->persist($foo1);
        $this->em->persist($foo2);
        $this->em->persist($bar1);
        $this->em->persist($bar2);
        $this->em->persist($bar3);
        $this->em->flush();

        $this->em->persist($foobar1);
        $this->em->persist($foobar2);
        $this->em->persist($foobar3);
        $this->em->flush();
        $this->em->clear();

        $foo = $this->em->find(DDC1080Foo::class, $foo1->getFooID());
        
        $fooBars = $foo->getFooBars();

        self::assertEquals(3, count($fooBars), "Should return three foobars.");
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="foo")
 */
class DDC1080Foo
{
    /**
     * @ORM\Id
     * @ORM\Column(name="fooID", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $fooID;
    
    /**
     * @ORM\Column(name="fooTitle", type="string")
     */
    
    protected $fooTitle;
    /**
     * @ORM\OneToMany(targetEntity="DDC1080FooBar", mappedBy="foo", cascade={"persist"})
     * @ORM\OrderBy({"orderNr"="ASC"})
     */
    protected $fooBars;

    public function __construct()
    {
        $this->fooBars = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return the $fooID
     */
    public function getFooID()
    {
        return $this->fooID;
    }

    /**
     * @return the $fooTitle
     */
    public function getFooTitle()
    {
        return $this->fooTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars()
    {
        return $this->fooBars;
    }

    /**
     * @param field_type $fooID
     */
    public function setFooID($fooID)
    {
        $this->fooID = $fooID;
    }

    /**
     * @param field_type $fooTitle
     */
    public function setFooTitle($fooTitle)
    {
        $this->fooTitle = $fooTitle;
    }

    /**
     * @param field_type $fooBars
     */
    public function setFooBars($fooBars)
    {
        $this->fooBars = $fooBars;
    }

}
/**
 * @ORM\Entity
 * @ORM\Table(name="bar")
 */
class DDC1080Bar
{
    /**
     * @ORM\Id
     * @ORM\Column(name="barID", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $barID;
    
    /**
     * @ORM\Column(name="barTitle", type="string")
     */
    protected $barTitle;
    
    /**
     * @ORM\OneToMany(targetEntity="DDC1080FooBar", mappedBy="bar", cascade={"persist"})
     * @ORM\OrderBy({"orderNr"="ASC"})
     */
    protected $fooBars;

    public function __construct()
    {
        $this->fooBars = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return the $barID
     */
    public function getBarID()
    {
        return $this->barID;
    }

    /**
     * @return the $barTitle
     */
    public function getBarTitle()
    {
        return $this->barTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars()
    {
        return $this->fooBars;
    }

    /**
     * @param field_type $barID
     */
    public function setBarID($barID)
    {
        $this->barID = $barID;
    }

    /**
     * @param field_type $barTitle
     */
    public function setBarTitle($barTitle)
    {
        $this->barTitle = $barTitle;
    }

    /**
     * @param field_type $fooBars
     */
    public function setFooBars($fooBars)
    {
        $this->fooBars = $fooBars;
    }

}

/**
 * @ORM\Table(name="fooBar")
 * @ORM\Entity
 */
class DDC1080FooBar
{
    /**
     * @ORM\ManyToOne(targetEntity="DDC1080Foo")
     * @ORM\JoinColumn(name="fooID", referencedColumnName="fooID")
     * @ORM\Id
     */
    protected $foo = null;
    /**
     * @ORM\ManyToOne(targetEntity="DDC1080Bar")
     * @ORM\JoinColumn(name="barID", referencedColumnName="barID")
     * @ORM\Id
     */
    protected $bar = null;
    /**
     * @var int orderNr
     * @ORM\Column(name="orderNr", type="integer", nullable=false)
     */
    protected $orderNr = null;

    /**
     * Retrieve the foo property
     *
     * @return DDC1080Foo
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * Set the foo property
     *
     * @param DDC1080Foo $foo
     * @return DDC1080FooBar
     */
    public function setFoo($foo)
    {
        $this->foo = $foo;
        
        return $this;
    }

    /**
     * Retrieve the bar property
     *
     * @return DDC1080Bar
     */
    public function getBar()
    {
        return $this->bar;
    }

    /**
     * Set the bar property
     *
     * @param DDC1080Bar $bar
     * @return DDC1080FooBar
     */
    public function setBar($bar)
    {
        $this->bar = $bar;
        
        return $this;
    }

    /**
     * Retrieve the orderNr property
     *
     * @return int|null
     */
    public function getOrderNr()
    {
        return $this->orderNr;
    }

    /**
     * Set the orderNr property
     *
     * @param integer|null $orderNr
     * @return DDC1080FooBar
     */
    public function setOrderNr($orderNr)
    {
        $this->orderNr = $orderNr;
        
        return $this;
    }
}

