<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1209Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1209_1'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1209_2'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1209_3')
            ));
        } catch(\Exception $e) {
        }
    }

    /**
     * @group DDC-1209
     */
    public function testIdentifierCanHaveCustomType()
    {
        $this->_em->persist(new DDC1209_3());
        $this->_em->flush();
    }

    /**
     * @group DDC-1209
     */
    public function testCompositeIdentifierCanHaveCustomType()
    {
        $future1 = new DDC1209_1();
        $this->_em->persist($future1);

        $this->_em->flush();

        $future2 = new DDC1209_2($future1);
        $this->_em->persist($future2);

        $this->_em->flush();
    }
}

/**
 * @Entity
 */
class DDC1209_1
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class DDC1209_2
{
    /**
     *  @Id
     *  @ManyToOne(targetEntity="DDC1209_1")
     *  @JoinColumn(referencedColumnName="id", nullable=false)
     */
    private $future1;
    /**
     *  @Id
     *  @Column(type="datetime", nullable=false)
     */
    private $starting_datetime;
    /**
     *  @Id
     *  @Column(type="datetime", nullable=false)
     */
    private $during_datetime;
    /**
     *  @Id
     *  @Column(type="datetime", nullable=false)
     */
    private $ending_datetime;

    public function __construct(DDC1209_1 $future1)
    {
        $this->future1 = $future1;
        $this->starting_datetime = new DateTime2();
        $this->during_datetime = new DateTime2();
        $this->ending_datetime = new DateTime2();
    }
}

/**
 * @Entity
 */
class DDC1209_3
{
    /**
     * @Id
     * @Column(type="datetime", name="somedate")
     */
    private $date;

    public function __construct()
    {
        $this->date = new DateTime2();
    }
}

class DateTime2 extends \DateTime
{
    public function __toString()
    {
        return $this->format('Y');
    }
}