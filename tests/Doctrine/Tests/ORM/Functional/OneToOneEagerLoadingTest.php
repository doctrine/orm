<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-952
 */
class OneToOneEagerLoadingTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        try {
            $schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Train'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\TrainDriver'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Waggon'),
            ));
        } catch(\Exception $e) {}
    }

    public function testEagerLoadOneToOneOwningSide()
    {
        $train = new Train();
        $driver = new TrainDriver("Benjamin");
        $waggon = new Waggon();
        
        $train->setDriver($driver);
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $train = $this->_em->find(get_class($train), $train->id);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $train->driver);
        $this->assertEquals("Benjamin", $train->driver->name);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    public function testEagerLoadOneToOneNullOwningSide()
    {
        $train = new Train();

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $train = $this->_em->find(get_class($train), $train->id);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $train->driver);
        $this->assertNull($train->driver);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    public function testEagerLoadOneToOneInverseSide()
    {
        $train = new Train();
        $driver = new TrainDriver("Benjamin");
        $train->setDriver($driver);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $driver = $this->_em->find(get_class($driver), $driver->id);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $driver->train);
        $this->assertNotNull($driver->train);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    public function testEagerLoadOneToOneNullInverseSide()
    {
        $driver = new TrainDriver("Dagny Taggert");

        $this->_em->persist($driver);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertNull($driver->train);

        $sqlCount = count($this->_sqlLoggerStack->queries);

        $driver = $this->_em->find(get_class($driver), $driver->id);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $driver->train);
        $this->assertNull($driver->train);

        $this->assertEquals($sqlCount + 1, count($this->_sqlLoggerStack->queries));
    }

    public function testEagerLoadManyToOne()
    {
        $train = new Train();
        $waggon = new Waggon();
        $train->addWaggon($waggon);

        $this->_em->persist($train); // cascades
        $this->_em->flush();
        $this->_em->clear();

        $waggon = $this->_em->find(get_class($waggon), $waggon->id);
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $waggon->train);
        $this->assertNotNull($waggon->train);
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
     */
    public $driver;
    /**
     * @oneToMany(targetEntity="Waggon", mappedBy="train", cascade={"persist"})
     */
    public $waggons;

    public function __construct()
    {
        $this->waggons = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setDriver(TrainDriver $driver)
    {
        $this->driver = $driver;
        $driver->setTrain($this);
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
class Waggon
{
    /** @id @generatedValue @column(type="integer") */
    public $id;
    /** @ManyToOne(targetEntity="Train", inversedBy="waggons", fetch="EAGER") */
    public $train;

    public function setTrain($train)
    {
        $this->train = $train;
    }
}