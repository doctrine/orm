<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-952
 */
class OneToOneEagerLoadingTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $schemaTool = new SchemaTool($this->_em);
        try {
            $schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(Train::class),
                $this->_em->getClassMetadata(TrainDriver::class),
                $this->_em->getClassMetadata(TrainOwner::class),
                $this->_em->getClassMetadata(Waggon::class),
                $this->_em->getClassMetadata(TrainOrder::class),
                ]
            );
        } catch(\Exception $e) {}
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneOwningSide()
    {
        $train = new Train(new TrainOwner("Alexander"));
        $driver = new TrainDriver("Benjamin");
        $waggon = new Waggon();

        $train->setDriver($driver);
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $train = $this->_em->find(get_class($train), $train->id);
        $this->assertNotInstanceOf(Proxy::class, $train->driver);
        $this->assertEquals("Benjamin", $train->driver->name);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneNullOwningSide()
    {
        $train = new Train(new TrainOwner("Alexander"));

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $train = $this->_em->find(get_class($train), $train->id);
        $this->assertNotInstanceOf(Proxy::class, $train->driver);
        $this->assertNull($train->driver);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneInverseSide()
    {
        $owner = new TrainOwner("Alexander");
        $train = new Train($owner);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $driver = $this->_em->find(get_class($owner), $owner->id);
        $this->assertNotInstanceOf(Proxy::class, $owner->train);
        $this->assertNotNull($owner->train);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneNullInverseSide()
    {
        $driver = new TrainDriver("Dagny Taggert");

        $this->_em->persist($driver);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($driver->train);

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $driver = $this->_em->find(get_class($driver), $driver->id);
        $this->assertNotInstanceOf(Proxy::class, $driver->train);
        $this->assertNull($driver->train);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    public function testEagerLoadManyToOne()
    {
        $train = new Train(new TrainOwner("Alexander"));
        $waggon = new Waggon();
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find(get_class($waggon), $waggon->id);
        $this->assertNotInstanceOf(Proxy::class, $waggon->train);
        $this->assertNotNull($waggon->train);
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadWithNullableColumnsGeneratesLeftJoinOnBothSides()
    {
        $train = new Train(new TrainOwner("Alexander"));
        $driver = new TrainDriver("Benjamin");
        $train->setDriver($driver);

        $this->_em->persist($train);
        $this->_em->flush();
        $this->_em->clear();

        $train = $this->_em->find(get_class($train), $train->id);
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.driver_id AS driver_id_2, t3.id AS id_4, t3.name AS name_5, t0.owner_id AS owner_id_6, t7.id AS id_8, t7.name AS name_9 FROM Train t0 LEFT JOIN TrainDriver t3 ON t0.driver_id = t3.id INNER JOIN TrainOwner t7 ON t0.owner_id = t7.id WHERE t0.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        $this->_em->clear();
        $driver = $this->_em->find(get_class($driver), $driver->id);
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM TrainOwner t0 LEFT JOIN Train t3 ON t3.owner_id = t0.id WHERE t0.id IN (?)",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadWithNonNullableColumnsGeneratesInnerJoinOnOwningSide()
    {
        $waggon = new Waggon();

        // It should have a train
        $train = new Train(new TrainOwner("Alexander"));
        $train->addWaggon($waggon);

        $this->_em->persist($train);
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find(get_class($waggon), $waggon->id);

        // The last query is the eager loading of the owner of the train
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM TrainOwner t0 LEFT JOIN Train t3 ON t3.owner_id = t0.id WHERE t0.id IN (?)",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        // The one before is the fetching of the waggon and train
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.train_id AS train_id_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM Waggon t0 INNER JOIN Train t3 ON t0.train_id = t3.id WHERE t0.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery - 1]['sql']
        );
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadWithNonNullableColumnsGeneratesLeftJoinOnNonOwningSide()
    {
        $owner = new TrainOwner('Alexander');
        $train = new Train($owner);
        $this->_em->persist($train);
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find(get_class($owner), $owner->id);
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.name AS name_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM TrainOwner t0 LEFT JOIN Train t3 ON t3.owner_id = t0.id WHERE t0.id = ?",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );
    }

    /**
     * @group DDC-1946
     */
    public function testEagerLoadingDoesNotBreakRefresh()
    {
        $train = new Train(new TrainOwner('Johannes'));
        $order = new TrainOrder($train);
        $this->_em->persist($train);
        $this->_em->persist($order);
        $this->_em->flush();

        $this->_em->getConnection()->exec("UPDATE TrainOrder SET train_id = NULL");

        $this->assertSame($train, $order->train);
        $this->_em->refresh($order);
        $this->assertTrue($order->train === null, "Train reference was not refreshed to NULL.");
    }
}

/**
 * @Entity
 */
class Train
{
    /**
     * @id @column(type="integer") @generatedValue
     * @var int
     */
    public $id;
    /**
     * Owning side
     * @OneToOne(targetEntity="TrainDriver", inversedBy="train", fetch="EAGER", cascade={"persist"})
     * @JoinColumn(nullable=true)
     */
    public $driver;
    /**
     * Owning side
     * @OneToOne(targetEntity="TrainOwner", inversedBy="train", fetch="EAGER", cascade={"persist"})
     * @JoinColumn(nullable=false)
     */
    public $owner;
    /**
     * @oneToMany(targetEntity="Waggon", mappedBy="train", cascade={"persist"})
     */
    public $waggons;

    public function __construct(TrainOwner $owner)
    {
        $this->waggons = new ArrayCollection();
        $this->setOwner($owner);
    }

    public function setDriver(TrainDriver $driver)
    {
        $this->driver = $driver;
        $driver->setTrain($this);
    }

    public function setOwner(TrainOwner $owner)
    {
        $this->owner = $owner;
        $owner->setTrain($this);
    }

    public function addWaggon(Waggon $w)
    {
        $w->setTrain($this);
        $this->waggons[] = $w;
    }
}

/**
 * @Entity
 */
class TrainDriver
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @column(type="string") */
    public $name;
    /**
     * Inverse side
     * @OneToOne(targetEntity="Train", mappedBy="driver", fetch="EAGER")
     */
    public $train;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setTrain(Train $t)
    {
        $this->train = $t;
    }
}

/**
 * @Entity
 */
class TrainOwner
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @column(type="string") */
    public $name;
    /**
     * Inverse side
     * @OneToOne(targetEntity="Train", mappedBy="owner", fetch="EAGER")
     */
    public $train;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setTrain(Train $t)
    {
        $this->train = $t;
    }
}

/**
 * @Entity
 */
class Waggon
{
    /** @id @generatedValue @column(type="integer") */
    public $id;
    /**
     * @ManyToOne(targetEntity="Train", inversedBy="waggons", fetch="EAGER")
     * @JoinColumn(nullable=false)
     */
    public $train;

    public function setTrain($train)
    {
        $this->train = $train;
    }
}

/**
 * @Entity
 */
class TrainOrder
{
    /** @id @generatedValue @column(type="integer") */
    public $id;

    /** @OneToOne(targetEntity = "Train", fetch = "EAGER") */
    public $train;

    public function __construct(Train $train)
    {
        $this->train = $train;
    }
}
