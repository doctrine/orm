<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2338
 */
class DDC2338Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testCompositeForeignKeyPersist()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2338Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2338Bar'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2338FooBar'),
        ));

        try {
            $foo = new DDC2338Foo();
            $bar = new DDC2338Bar();

            $this->_em->persist($foo);
            $this->_em->persist($bar);

            $fooBar = new DDC2338FooBar();
            $fooBar->setFoo($foo);
            $fooBar->setBar($bar);
            $this->_em->persist($fooBar);
            $this->_em->flush();
        } catch (\Doctrine\ORM\ORMException $exception) {
            $this->fail('Exception raised');
        }

        return;
    }
}


/**
 * @Entity
 * @Table()
 */
class DDC2338FooBar
{

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2338Foo", inversedBy="foobars", cascade={"persist"})
     */
    private $foo;

    /**
     * @Id
     * @ManyToOne(targetEntity="DDC2338Bar", inversedBy="foobars", cascade={"persist"})
     */
    private $bar;

    public function setFoo(DDC2338Foo $foo = null)
    {
        $this->foo = $foo;

        return $this;
    }

    public function setBar(DDC2338Bar $bar = null)
    {
        $this->bar = $bar;

        return $this;
    }

}
/**
 * @Entity
 * @Table()
 */
class DDC2338Foo
{
    /**
     * @Column(name="ID", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC2338FooBar", mappedBy="foo")
     **/
    private $foobars;

    public function __construct()
    {
        $this->foobars = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @Entity
 * @Table()
 */
class DDC2338Bar
{
    /**
     * @Column(name="ID", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC2338FooBar", mappedBy="bar")
     **/
    private $foobars;

    public function __construct()
    {
        $this->foobars = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

