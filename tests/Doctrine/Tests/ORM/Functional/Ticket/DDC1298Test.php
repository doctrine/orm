<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1298
 */
class DDC1298Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testValidSQL()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1298Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1298Bar'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1298FooBar'),
        ));
        
        $foo1 = new DDC1298Foo();
        $foo1->setFooTitle('foo title 1');
        $foo2 = new DDC1298Foo();
        $foo2->setFooTitle('foo title 2');
        
        $bar1 = new DDC1298Bar();
        $bar1->setBarTitle('bar title 1');
        $bar2 = new DDC1298Bar();
        $bar2->setBarTitle('bar title 2');
        $bar3 = new DDC1298Bar();
        $bar3->setBarTitle('bar title 3');
        
        $foobar1 = new DDC1298FooBar();
        $foobar1->setFoo($foo1);
        $foobar1->setBar($bar1);
        
        $foobar2 = new DDC1298FooBar();
        $foobar2->setFoo($foo1);
        $foobar2->setBar($bar2);
        
        $foobar3 = new DDC1298FooBar();
        $foobar3->setFoo($foo1);
        $foobar3->setBar($bar3);
        
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
        
        $query = $this->_em->createQuery('SELECT 
			f, b, fb 
		FROM 
			Doctrine\Tests\ORM\Functional\Ticket\DDC1298Foo f 
		JOIN 
			f._fooBars fb
		JOIN
			fb._bar b');
        
        $sql = $query->getSQL();
        //$pos = strpos($sql, ', ,');
        $match = preg_match('#,\s+,#', $sql);
        $this->assertEquals($match, 0, "Should not contain 2 commas.");
    }
}


/**
 * @Entity
 * @Table(name="foo")
 */
class DDC1298Foo
{

    /**
     * @Id 
     * @Column(name="fooID", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $_fooID;
    /**
     * @Column(name="fooTitle", type="string")
     */
    protected $_fooTitle;
    /**
     * @OneToMany(targetEntity="DDC1298FooBar", mappedBy="_foo",
     * cascade={"persist"})
     */
    protected $_fooBars;

    public function __construct()
    {
        $this->_fooBars = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return the $fooID
     */
    public function getFooID()
    {
        return $this->_fooID;
    }

    /**
     * @return the $fooTitle
     */
    public function getFooTitle()
    {
        return $this->_fooTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars()
    {
        return $this->_fooBars;
    }

    /**
     * @param field_type $fooID
     */
    public function setFooID($fooID)
    {
        $this->_fooID = $fooID;
    }

    /**
     * @param field_type $fooTitle
     */
    public function setFooTitle($fooTitle)
    {
        $this->_fooTitle = $fooTitle;
    }

    /**
     * @param field_type $fooBars
     */
    public function setFooBars($fooBars)
    {
        $this->_fooBars = $fooBars;
    }

}
/**
 * @Entity
 * @Table(name="bar")
 */
class DDC1298Bar
{

    /**
     * @Id 
     * @Column(name="barID", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $_barID;
    /**
     * @Column(name="barTitle", type="string")
     */
    protected $_barTitle;
    /**
     * @OneToMany(targetEntity="DDC1298FooBar", mappedBy="_bar",
     * cascade={"persist"})
     */
    protected $_fooBars;

    public function __construct()
    {
        $this->_fooBars = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return the $barID
     */
    public function getBarID()
    {
        return $this->_barID;
    }

    /**
     * @return the $barTitle
     */
    public function getBarTitle()
    {
        return $this->_barTitle;
    }

    /**
     * @return the $fooBars
     */
    public function getFooBars()
    {
        return $this->_fooBars;
    }

    /**
     * @param field_type $barID
     */
    public function setBarID($barID)
    {
        $this->_barID = $barID;
    }

    /**
     * @param field_type $barTitle
     */
    public function setBarTitle($barTitle)
    {
        $this->_barTitle = $barTitle;
    }

    /**
     * @param field_type $fooBars
     */
    public function setFooBars($fooBars)
    {
        $this->_fooBars = $fooBars;
    }

}

/**
 * @Table(name="fooBar")
 * @Entity
 */
class DDC1298FooBar
{

    /**
     * @ManyToOne(targetEntity="DDC1298Foo")
     * @JoinColumn(name="fooID", referencedColumnName="fooID")
     * @Id
     */
    protected $_foo = null;
    /**
     * @ManyToOne(targetEntity="DDC1298Bar")
     * @JoinColumn(name="barID", referencedColumnName="barID")
     * @Id
     */
    protected $_bar = null;
    

    /**
     * Retrieve the foo property
     *
     * @return DDC1298Foo
     */
    public function getFoo()
    {
        return $this->_foo;
    }

    /**
     * Set the foo property
     *
     * @param DDC1298Foo $foo
     * @return DDC1298FooBar
     */
    public function setFoo($foo)
    {
        $this->_foo = $foo;
        return $this;
    }

    /**
     * Retrieve the bar property
     *
     * @return DDC1298Bar
     */
    public function getBar()
    {
        return $this->_bar;
    }

    /**
     * Set the bar property
     *
     * @param DDC1298Bar $bar
     * @return DDC1298FooBar
     */
    public function setBar($bar)
    {
        $this->_bar = $bar;
        return $this;
    }

    

}

