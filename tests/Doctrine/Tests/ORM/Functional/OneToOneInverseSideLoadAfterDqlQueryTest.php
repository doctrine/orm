<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

class OneToOneInverseSideLoadAfterDqlQueryTest extends OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        $schemaTool = new SchemaTool($this->_em);
        try {
            $schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(Bus::class),
                    $this->_em->getClassMetadata(BusOwner::class),
                ]
            );
        } catch(\Exception $e) {}
    }

    public function testInverseSideOneToOneLoadedAfterDqlQuery(): void
    {
        $owner = new BusOwner('Alexander');
        $bus = new Bus($owner);

        $this->_em->persist($bus);
        $this->_em->flush();
        $this->_em->clear();

        $bus = $this->_em->createQueryBuilder()
            ->select('to')
            ->from(BusOwner::class, 'to')
            ->andWhere('to.id = :id')
            ->setParameter('id', $owner->id)
            ->getQuery()
            ->getResult();

        $this->assertSQLEquals(
            "SELECT b0_.id AS id_0, b0_.name AS name_1 FROM BusOwner b0_ WHERE b0_.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery - 1]['sql']
        );

        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.owner AS owner_2 FROM Bus t0 WHERE t0.owner = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );
    }

}


/**
 * @Entity
 */
class Bus
{
    /**
     * @id @column(type="integer") @generatedValue
     * @var int
     */
    public $id;
    /**
     * Owning side
     * @OneToOne(targetEntity="BusOwner", inversedBy="bus", cascade={"persist"})
     * @JoinColumn(nullable=false, name="owner")
     */
    public $owner;

    public function __construct(BusOwner $owner)
    {
        $this->owner = $owner;
    }

}

/**
 * @Entity
 */
class BusOwner
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /** @column(type="string") */
    public $name;
    /**
     * Inverse side
     * @OneToOne(targetEntity="Bus", mappedBy="owner")
     */
    public $bus;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setBus(Bus $t)
    {
        $this->bus = $t;
    }
}
