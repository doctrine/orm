<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1300
 */
class DDC1300Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1300Foo::class),
                $this->em->getClassMetadata(DDC1300FooLocale::class),
            ]
        );
    }

    public function testIssue()
    {
        $foo = new DDC1300Foo();
        $foo->fooReference = "foo";

        $this->em->persist($foo);
        $this->em->flush();

        $locale = new DDC1300FooLocale();
        $locale->foo = $foo;
        $locale->locale = "en";
        $locale->title = "blub";

        $this->em->persist($locale);
        $this->em->flush();

        $query = $this->em->createQuery('SELECT f, fl FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1300Foo f JOIN f.fooLocaleRefFoo fl');
        $result =  $query->getResult();

        self::assertEquals(1, count($result));
    }
}

/**
 * @Entity
 */
class DDC1300Foo
{
    /**
     * @var int fooID
     * @Column(name="fooID", type="integer", nullable=false)
     * @GeneratedValue(strategy="AUTO")
     * @Id
     */
    public $fooID = null;

    /**
     * @var string fooReference
     * @Column(name="fooReference", type="string", nullable=true, length=45)
     */
    public $fooReference = null;

    /**
     * @OneToMany(targetEntity="DDC1300FooLocale", mappedBy="foo",
     * cascade={"persist"})
     */
    public $fooLocaleRefFoo = null;

    /**
     * Constructor
     *
     * @param array|Zend_Config|null $options
     * @return Bug_Model_Foo
     */
    public function __construct($options = null)
    {
        $this->fooLocaleRefFoo = new \Doctrine\Common\Collections\ArrayCollection();
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
    public $foo = null;

    /**
     * @var string locale
     * @Column(name="locale", type="string", nullable=false, length=5)
     * @Id
     */
    public $locale = null;

    /**
     * @var string title
     * @Column(name="title", type="string", nullable=true, length=150)
     */
    public $title = null;

}
