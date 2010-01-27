<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC279Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityX'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityY'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC279EntityZ')
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

        $result = $this->_em->createQuery(
            'SELECT x, y, z FROM Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityX x '.
            'INNER JOIN x.y y INNER JOIN y.z z WHERE x.id = 1'
        )->getArrayResult();

        $expected = array(
            0 => array(
                'id' => 1,
                'data' => 'X',
                'y' => array(
                    'id' => 1,
                    'data' => 'Y',
                    'z' => array(
                        'id' => 1,
                        'data' => 'Z',
                    )
                ),
            ),
        );

        $this->assertEquals($expected, $result);
    }
}

/**
 * @Entity
 */
class DDC279EntityX
{
    /**
     * @Id
     * @generatedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityY")
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
     * @Id
     * @generatedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC279EntityZ")
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
     * @Id
     * @generatedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    public $id;

    /**
     * @column(type="string")
     */
    public $data;
}