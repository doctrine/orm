<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1300
 */
class DDC1300Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1300Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1300FooLocale'),
        ));
    }

    public function testIssue()
    {
        $foo = new DDC1300Foo();
        $foo->_fooReference = "foo";

        $this->_em->persist($foo);
        $this->_em->flush();

        $locale = new DDC1300FooLocale();
        $locale->_foo = $foo;
        $locale->_locale = "en";
        $locale->_title = "blub";

        $this->_em->persist($locale);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT f, fl FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1300Foo f JOIN f._fooLocaleRefFoo fl');
        $result =  $query->getResult();

        $this->assertEquals(1, count($result));
    }
}

/**
 * @Entity
 */
class DDC1300Foo
{
    /**
     * @var integer fooID
     * @Column(name="fooID", type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     * @Id
     */
    public $_fooID = null;

    /**
     * @var string fooReference
     * @Column(name="fooReference", type="string", nullable=true, length=45)
     */
    public $_fooReference = null;

    /**
     * @OneToMany(targetEntity="DDC1300FooLocale", mappedBy="_foo",
     * cascade={"persist"})
     */
    public $_fooLocaleRefFoo = null;

    /**
     * Constructor
     *
     * @param array|Zend_Config|null $options
     * @return Bug_Model_Foo
     */
    public function __construct($options = null)
    {
        $this->_fooLocaleRefFoo = new \Doctrine\Common\Collections\ArrayCollection();
    }

}

/**
 * @Entity
 */
class DDC1300FooLocale
{

    /**
     * @ManyToOne(targetEntity="DDC1300Foo")
     * @JoinColumn(name="fooID", referencedColumnName="fooID")
     * @Id
     */
    public $_foo = null;

    /**
     * @var string locale
     * @Column(name="locale", type="string", nullable=false, length=5)
     * @Id
     */
    public $_locale = null;

    /**
     * @var string title
     * @Column(name="title", type="string", nullable=true, length=150)
     */
    public $_title = null;

}