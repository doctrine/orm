<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC237Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC237EntityX'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC237EntityY'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC237EntityZ')
        ));
    }

    public function testUninitializedProxyIsInitializedOnFetchJoin()
    {
        $x = new DDC237EntityX;
        $y = new DDC237EntityY;
        $z = new DDC237EntityZ;

        $x->data = 'X';
        $y->data = 'Y';
        $z->data = 'Z';

        $x->y = $y;
        $z->y = $y;

        $this->_em->persist($x);
        $this->_em->persist($y);
        $this->_em->persist($z);

        $this->_em->flush();
        $this->_em->clear();

        $x2 = $this->_em->find(get_class($x), $x->id); // proxy injected for Y
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $x2->y);
        self::assertFalse($x2->y->__isInitialized__);

        // proxy for Y is in identity map

        $z2 = $this->_em->createQuery('select z,y from ' . get_class($z) . ' z join z.y y where z.id = ?1')
                ->setParameter(1, $z->id)
                ->getSingleResult();
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $z2->y);
        self::assertTrue($z2->y->__isInitialized__);
        self::assertEquals('Y', $z2->y->data);
        self::assertEquals($y->id, $z2->y->id);

        // since the Y is the same, the instance from the identity map is
        // used, even if it is a proxy.

        self::assertNotSame($x, $x2);
        self::assertNotSame($z, $z2);
        self::assertSame($z2->y, $x2->y);
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $z2->y);

    }
}


/**
 * @Entity @Table(name="ddc237_x")
 */
class DDC237EntityX
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $data;
    /**
     * @OneToOne(targetEntity="DDC237EntityY")
     * @JoinColumn(name="y_id", referencedColumnName="id")
     */
    public $y;
}


/** @Entity @Table(name="ddc237_y") */
class DDC237EntityY
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $data;
}

/** @Entity @Table(name="ddc237_z") */
class DDC237EntityZ
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column(type="string") */
    public $data;

    /**
     * @OneToOne(targetEntity="DDC237EntityY")
     * @JoinColumn(name="y_id", referencedColumnName="id")
     */
    public $y;
}
