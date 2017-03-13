<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
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
        $schemaTool = new SchemaTool($this->em);
        try {
            $schemaTool->createSchema(
                [
                $this->em->getClassMetadata(Train::class),
                $this->em->getClassMetadata(TrainDriver::class),
                $this->em->getClassMetadata(TrainOwner::class),
                $this->em->getClassMetadata(Waggon::class),
                $this->em->getClassMetadata(TrainOrder::class),
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

        $this->em->persist($train); // cascades
        $this->em->flush();
        $this->em->clear();

        $sqlCount = count($this->sqlLoggerStack->queries);

        $train = $this->em->find(get_class($train), $train->id);
        self::assertNotInstanceOf(Proxy::class, $train->driver);
        self::assertEquals("Benjamin", $train->driver->name);

        self::assertEquals($sqlCount + 1, count($this->sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneNullOwningSide()
    {
        $train = new Train(new TrainOwner("Alexander"));

        $this->em->persist($train); // cascades
        $this->em->flush();
        $this->em->clear();

        $sqlCount = count($this->sqlLoggerStack->queries);

        $train = $this->em->find(get_class($train), $train->id);
        self::assertNotInstanceOf(Proxy::class, $train->driver);
        self::assertNull($train->driver);

        self::assertEquals($sqlCount + 1, count($this->sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneInverseSide()
    {
        $owner = new TrainOwner("Alexander");
        $train = new Train($owner);

        $this->em->persist($train); // cascades
        $this->em->flush();
        $this->em->clear();

        $sqlCount = count($this->sqlLoggerStack->queries);

        $driver = $this->em->find(get_class($owner), $owner->id);
        self::assertNotInstanceOf(Proxy::class, $owner->train);
        self::assertNotNull($owner->train);

        self::assertEquals($sqlCount + 1, count($this->sqlLoggerStack->queries));
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadOneToOneNullInverseSide()
    {
        $driver = new TrainDriver("Dagny Taggert");

        $this->em->persist($driver);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($driver->train);

        $sqlCount = count($this->sqlLoggerStack->queries);

        $driver = $this->em->find(get_class($driver), $driver->id);
        self::assertNotInstanceOf(Proxy::class, $driver->train);
        self::assertNull($driver->train);

        self::assertEquals($sqlCount + 1, count($this->sqlLoggerStack->queries));
    }

    public function testEagerLoadManyToOne()
    {
        $train = new Train(new TrainOwner("Alexander"));
        $waggon = new Waggon();
        $train->addWaggon($waggon);

        $this->em->persist($train); // cascades
        $this->em->flush();
        $this->em->clear();

        $waggon = $this->em->find(get_class($waggon), $waggon->id);
        self::assertNotInstanceOf(Proxy::class, $waggon->train);
        self::assertNotNull($waggon->train);
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadWithNullableColumnsGeneratesLeftJoinOnBothSides()
    {
        $train = new Train(new TrainOwner("Alexander"));
        $driver = new TrainDriver("Benjamin");
        $train->setDriver($driver);

        $this->em->persist($train);
        $this->em->flush();
        $this->em->clear();

        $this->em->find(get_class($train), $train->id);

        self::assertSQLEquals(
            'SELECT t0."id" AS c1, t0."driver_id" AS c2, t4."id" AS c3, t4."name" AS c5, t0."owner_id" AS c6, t8."id" AS c7, t8."name" AS c9 FROM "Train" t0 LEFT JOIN "TrainDriver" t4 ON t0."driver_id" = t4."id" INNER JOIN "TrainOwner" t8 ON t0."owner_id" = t8."id" WHERE t0."id" = ?',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery]['sql']
        );

        $this->em->clear();

        $this->em->find(get_class($driver), $driver->id);

        self::assertSQLEquals(
            'SELECT t0."id" AS c1, t0."name" AS c2, t4."id" AS c3, t4."driver_id" AS c5, t4."owner_id" AS c6 FROM "TrainOwner" t0 LEFT JOIN "Train" t4 ON t4."owner_id" = t0."id" WHERE t0."id" IN (?)',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery]['sql']
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

        $this->em->persist($train);
        $this->em->flush();
        $this->em->clear();

        $this->em->find(get_class($waggon), $waggon->id);

        // The last query is the eager loading of the owner of the train
        self::assertSQLEquals(
            'SELECT t0."id" AS c1, t0."name" AS c2, t4."id" AS c3, t4."driver_id" AS c5, t4."owner_id" AS c6 FROM "TrainOwner" t0 LEFT JOIN "Train" t4 ON t4."owner_id" = t0."id" WHERE t0."id" IN (?)',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery]['sql']
        );

        // The one before is the fetching of the waggon and train
        self::assertSQLEquals(
            'SELECT t0."id" AS c1, t0."train_id" AS c2, t4."id" AS c3, t4."driver_id" AS c5, t4."owner_id" AS c6 FROM "Waggon" t0 INNER JOIN "Train" t4 ON t0."train_id" = t4."id" WHERE t0."id" = ?',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery - 1]['sql']
        );
    }

    /**
     * @group non-cacheable
     */
    public function testEagerLoadWithNonNullableColumnsGeneratesLeftJoinOnNonOwningSide()
    {
        $owner = new TrainOwner('Alexander');
        $train = new Train($owner);

        $this->em->persist($train);
        $this->em->flush();
        $this->em->clear();

        $this->em->find(get_class($owner), $owner->id);

        self::assertSQLEquals(
            'SELECT t0."id" AS c1, t0."name" AS c2, t4."id" AS c3, t4."driver_id" AS c5, t4."owner_id" AS c6 FROM "TrainOwner" t0 LEFT JOIN "Train" t4 ON t4."owner_id" = t0."id" WHERE t0."id" = ?',
            $this->sqlLoggerStack->queries[$this->sqlLoggerStack->currentQuery]['sql']
        );
    }

    /**
     * @group DDC-1946
     */
    public function testEagerLoadingDoesNotBreakRefresh()
    {
        $train = new Train(new TrainOwner('Johannes'));
        $order = new TrainOrder($train);

        $this->em->persist($train);
        $this->em->persist($order);
        $this->em->flush();

        $this->em->getConnection()->exec("UPDATE TrainOrder SET train_id = NULL");

        self::assertSame($train, $order->train);

        $this->em->refresh($order);

        self::assertTrue($order->train === null, "Train reference was not refreshed to NULL.");
    }
}

/**
 * @ORM\Entity
 */
class Train
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     * @var int
     */
    public $id;
    /**
     * Owning side
     * @ORM\OneToOne(targetEntity="TrainDriver", inversedBy="train", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    public $driver;
    /**
     * Owning side
     * @ORM\OneToOne(targetEntity="TrainOwner", inversedBy="train", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    public $owner;
    /**
     * @ORM\OneToMany(targetEntity="Waggon", mappedBy="train", cascade={"persist"})
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
 * @ORM\Entity
 */
class TrainDriver
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column(type="string") */
    public $name;
    /**
     * Inverse side
     * @ORM\OneToOne(targetEntity="Train", mappedBy="driver", fetch="EAGER")
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
 * @ORM\Entity
 */
class TrainOwner
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column(type="string") */
    public $name;
    /**
     * Inverse side
     * @ORM\OneToOne(targetEntity="Train", mappedBy="owner", fetch="EAGER")
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
 * @ORM\Entity
 */
class Waggon
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;
    /**
     * @ORM\ManyToOne(targetEntity="Train", inversedBy="waggons", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    public $train;

    public function setTrain($train)
    {
        $this->train = $train;
    }
}

/**
 * @ORM\Entity
 */
class TrainOrder
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /** @ORM\OneToOne(targetEntity = "Train", fetch = "EAGER") */
    public $train;

    public function __construct(Train $train)
    {
        $this->train = $train;
    }
}
