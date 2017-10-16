<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC1209Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC1209_1::class),
                    $this->_em->getClassMetadata(DDC1209_2::class),
                    $this->_em->getClassMetadata(DDC1209_3::class)
                ]
            );
        } catch(\Exception $e) {
        }
    }

    /**
     * @group DDC-1209
     */
    public function testIdentifierCanHaveCustomType()
    {
        $entity = new DDC1209_3();

        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertSame($entity, $this->_em->find(DDC1209_3::class, $entity->date));
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

        self::assertSame(
            $future2,
            $this->_em->find(
                DDC1209_2::class,
                [
                    'future1'           => $future1,
                    'starting_datetime' => $future2->starting_datetime,
                    'during_datetime'   => $future2->during_datetime,
                    'ending_datetime'   => $future2->ending_datetime,
                ]
            )
        );
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
    public $starting_datetime;

    /**
     *  @Id
     *  @Column(type="datetime", nullable=false)
     */
    public $during_datetime;

    /**
     *  @Id
     *  @Column(type="datetime", nullable=false)
     */
    public $ending_datetime;

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
    public $date;

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
