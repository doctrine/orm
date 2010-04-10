<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC279Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityXAbstract'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityX'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityY'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityZ'),
        ));
    }

    /**
     * @group DDC-279
     */
    public function testDDC279()
    {
        $x = new DDC279EntityX();
        $y = new DDC279EntityY();
        $z = new DDC279EntityZ();

        $x->data = 'X';
        $y->data = 'Y';
        $z->data = 'Z';

        $x->y = $y;
        $y->z = $z;

        $this->_em->persist($x);
        $this->_em->persist($y);
        $this->_em->persist($z);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery(
            'SELECT x, y, z FROM Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityX x '.
            'INNER JOIN x.y y INNER JOIN y.z z WHERE x.id = ?1'
        )->setParameter(1, $x->id);
        
        $result = $query->getResult();
        
        $expected1 = 'Y';
        $expected2 = 'Z';

        $this->assertEquals(1, count($result));
        
        $this->assertEquals($expected1, $result[0]->y->data);
        $this->assertEquals($expected2, $result[0]->y->z->data);
    }
}


/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"DDC279EntityX" = "DDC279EntityX"})
 */
abstract class DDC279EntityXAbstract
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;

}

/**
 * @Entity
 */
class DDC279EntityX extends DDC279EntityXAbstract
{
    /**
     * @OneToOne(targetEntity="DDC279EntityY")
     * @JoinColumn(name="y_id", referencedColumnName="id")
     */
    public $y;
}

/**
 * @Entity
 */
class DDC279EntityY
{
    /**
     * @Id @GeneratedValue
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;

    /**
     * @OneToOne(targetEntity="DDC279EntityZ")
     * @JoinColumn(name="z_id", referencedColumnName="id")
     */
    public $z;
}

/**
 * @Entity
 */
class DDC279EntityZ
{
    /**
     * @Id @GeneratedValue
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;
}