<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-952')]
class OneToOneEagerLoadingTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Train::class,
            TrainDriver::class,
            TrainOwner::class,
            Waggon::class,
            TrainOrder::class,
        );
    }

    #[Group('non-cacheable')]
    public function testEagerLoadOneToOneOwningSide(): void
    {
        $train  = new Train(new TrainOwner('Alexander'));
        $driver = new TrainDriver('Benjamin');
        $waggon = new Waggon();

        $train->setDriver($driver);
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $train = $this->_em->find($train::class, $train->id);
        self::assertFalse($this->isUninitializedObject($train->driver));
        self::assertEquals('Benjamin', $train->driver->name);

        $this->assertQueryCount(1);
    }

    #[Group('non-cacheable')]
    public function testEagerLoadOneToOneNullOwningSide(): void
    {
        $train = new Train(new TrainOwner('Alexander'));

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $train = $this->_em->find($train::class, $train->id);
        self::assertNull($train->driver);

        $this->assertQueryCount(1);
    }

    #[Group('non-cacheable')]
    public function testEagerLoadOneToOneInverseSide(): void
    {
        $owner = new TrainOwner('Alexander');
        $train = new Train($owner);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $this->getQueryLog()->reset()->enable();

        $this->_em->find($owner::class, $owner->id);
        self::assertFalse($this->isUninitializedObject($owner->train));
        self::assertInstanceOf(Train::class, $owner->train);

        $this->assertQueryCount(1);
    }

    #[Group('non-cacheable')]
    public function testEagerLoadOneToOneNullInverseSide(): void
    {
        $driver = new TrainDriver('Dagny Taggert');

        $this->_em->persist($driver);
        $this->_em->flush();
        $this->_em->clear();

        self::assertNull($driver->train);

        $this->getQueryLog()->reset()->enable();

        $driver = $this->_em->find($driver::class, $driver->id);
        self::assertNull($driver->train);

        $this->assertQueryCount(1);
    }

    public function testEagerLoadManyToOne(): void
    {
        $train  = new Train(new TrainOwner('Alexander'));
        $waggon = new Waggon();
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find($waggon::class, $waggon->id);
        self::assertFalse($this->isUninitializedObject($waggon->train));
        self::assertInstanceOf(Train::class, $waggon->train);
    }

    #[Group('non-cacheable')]
    public function testEagerLoadWithNullableColumnsGeneratesLeftJoinOnBothSides(): void
    {
        $train  = new Train(new TrainOwner('Alexander'));
        $driver = new TrainDriver('Benjamin');

        $this->_em->persist($train);
        $this->_em->persist($driver);
        $this->_em->flush();
        $trainId  = $train->id;
        $driverId = $driver->id;
        $this->_em->clear();

        $train = $this->_em->find($train::class, $train->id);
        self::assertNotNull($train, 'It should be possible to find the train even though it has no driver');
        self::assertSame($trainId, $train->id);

        $this->_em->clear();
        $driver = $this->_em->find($driver::class, $driver->id);
        self::assertNotNull($driver, 'It should be possible to find the driver even though they drive no train');
        self::assertSame($driverId, $driver->id);
    }

    #[Group('non-cacheable')]
    public function testEagerLoadWithNonNullableColumnsGeneratesInnerJoinOnOwningSide(): void
    {
        $waggon = new Waggon();

        // It should have a train
        $train = new Train(new TrainOwner('Alexander'));
        $train->addWaggon($waggon);

        $this->_em->persist($train);
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find($waggon::class, $waggon->id);

        // The last query is the eager loading of the owner of the train
        $this->assertSQLEquals(
            'SELECT t0.id AS id_1, t0.name AS name_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM TrainOwner t0 LEFT JOIN Train t3 ON t3.owner_id = t0.id WHERE t0.id IN (?)',
            $this->getLastLoggedQuery()['sql'],
        );

        // The one before is the fetching of the waggon and train
        $this->assertSQLEquals(
            'SELECT t0.id AS id_1, t0.train_id AS train_id_2, t3.id AS id_4, t3.driver_id AS driver_id_5, t3.owner_id AS owner_id_6 FROM Waggon t0 INNER JOIN Train t3 ON t0.train_id = t3.id WHERE t0.id = ?',
            $this->getLastLoggedQuery(1)['sql'],
        );
    }

    #[Group('non-cacheable')]
    public function testEagerLoadWithNonNullableColumnsGeneratesLeftJoinOnNonOwningSide(): void
    {
        $owner = new TrainOwner('Alexander');
        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $owner = $this->_em->find($owner::class, $owner->id);
        self::assertNotNull($owner, 'An owner without a train should be able to exist.');
    }

    #[Group('DDC-1946')]
    public function testEagerLoadingDoesNotBreakRefresh(): void
    {
        $train = new Train(new TrainOwner('Johannes'));
        $order = new TrainOrder($train);
        $this->_em->persist($train);
        $this->_em->persist($order);
        $this->_em->flush();

        $this->_em->getConnection()->executeStatement('UPDATE TrainOrder SET train_id = NULL');

        self::assertSame($train, $order->train);
        $this->_em->refresh($order);
        self::assertNull($order->train, 'Train reference was not refreshed to NULL.');
    }
}

#[Entity]
class Train
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /**
     * Owning side
     *
     * @var TrainDriver
     */
    #[OneToOne(targetEntity: 'TrainDriver', inversedBy: 'train', fetch: 'EAGER', cascade: ['persist'])]
    #[JoinColumn(nullable: true)]
    public $driver;

    /**
     * Owning side
     *
     * @var TrainOwner
     */
    #[OneToOne(targetEntity: 'TrainOwner', inversedBy: 'train', fetch: 'EAGER', cascade: ['persist'])]
    #[JoinColumn(nullable: false)]
    public $owner;

    /** @phpstan-var Collection<int, Waggon> */
    #[OneToMany(targetEntity: 'Waggon', mappedBy: 'train', cascade: ['persist'])]
    public $waggons;

    public function __construct(TrainOwner $owner)
    {
        $this->waggons = new ArrayCollection();
        $this->setOwner($owner);
    }

    public function setDriver(TrainDriver $driver): void
    {
        $this->driver = $driver;
        $driver->setTrain($this);
    }

    public function setOwner(TrainOwner $owner): void
    {
        $this->owner = $owner;
        $owner->setTrain($this);
    }

    public function addWaggon(Waggon $w): void
    {
        $w->setTrain($this);
        $this->waggons[] = $w;
    }
}

#[Entity]
class TrainDriver
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /**
     * Inverse side
     *
     * @var Train
     */
    #[OneToOne(targetEntity: 'Train', mappedBy: 'driver', fetch: 'EAGER')]
    public $train;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        public string $name,
    ) {
    }

    public function setTrain(Train $t): void
    {
        $this->train = $t;
    }
}

#[Entity]
class TrainOwner
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /**
     * Inverse side
     *
     * @var Train
     */
    #[OneToOne(targetEntity: 'Train', mappedBy: 'owner', fetch: 'EAGER')]
    public $train;

    public function __construct(
        #[Column(type: 'string', length: 255)]
        public string $name,
    ) {
    }

    public function setTrain(Train $t): void
    {
        $this->train = $t;
    }
}

#[Entity]
class Waggon
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;
    /** @var Train */
    #[ManyToOne(targetEntity: 'Train', inversedBy: 'waggons', fetch: 'EAGER')]
    #[JoinColumn(nullable: false)]
    public $train;

    public function setTrain($train): void
    {
        $this->train = $train;
    }
}

#[Entity]
class TrainOrder
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    public function __construct(
        #[OneToOne(targetEntity: 'Train', fetch: 'EAGER')]
        public Train|null $train = null,
    ) {
    }
}
