<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC6393Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                    [
                        $this->_em->getClassMetadata(A::class),
                        $this->_em->getClassMetadata(B::class)
                    ]
            );
        } catch (\Exception $e) {
            
        }
    }

    /**
     * Test the the version of an entity can be fetched, when the id field and
     * the id column are different.
     */
    public function testFetchVersionValueForDifferentIdFieldAndColumn()
    {
        $a = new A(1);
        $this->_em->persist($a);

        $b = new B($a, 'foo');
        $this->_em->persist($b);        
        $this->_em->flush();
        
        $this->assertSame(1, $b->getVersion());

        $b->setSomething('bar');
        $this->_em->flush();
        
        $this->assertSame(2, $b->getVersion());
    }

}

/**
 * @Entity
 */
class A
{

    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Version
     * @Column(type="integer")
     */
    private $version;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->version;
    }

}

/**
 * @Entity
 */
class B
{

    /**
     * @Id
     * @ManyToOne(targetEntity="A")
     * @JoinColumn(name="aid", referencedColumnName="id")
     */
    private $a;

    /**
     * @Column(type="string")
     */
    private $something;

    /**
     * @Version
     * @Column(type="integer")
     */
    private $version;

    public function __construct($a, $something)
    {
        $this->a = $a;
        $this->something = $something;
    }

    public function getA()
    {
        return $this->a;
    }

    public function getSomething()
    {
        return $this->something;
    }

    public function setSomething($something)
    {
        $this->something = $something;
    }

    public function getVersion()
    {
        return $this->version;
    }

}
