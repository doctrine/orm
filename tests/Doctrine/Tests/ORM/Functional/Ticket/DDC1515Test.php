<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;


/**
 * @group DDC-1515
 */
class DDC1515Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1515Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1515Bar'),
        ));
    }

    public function testIssue()
    {
        $bar = new DDC1515Bar();
        $this->_em->persist($bar);
        $this->_em->flush();

        $foo = new DDC1515Foo();
        $foo->bar = $bar;
        $this->_em->persist($foo);
        $this->_em->flush();
        $this->_em->clear();

        $bar = $this->_em->find(__NAMESPACE__ . '\DDC1515Bar', $bar->id);
        $this->assertInstanceOf(__NAMESPACE__.'\DDC1515Foo', $bar->foo);
    }
}

/**
 * @Entity
 */
class DDC1515Foo
{
    /**
     * @OneToOne(targetEntity="DDC1515Bar", inversedBy="foo") @Id
     */
    public $bar;
}

/**
 * @Entity
 */
class DDC1515Bar
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1515Foo", mappedBy="bar")
     */
    public $foo;
}


