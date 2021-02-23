<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\OrmFunctionalTestCase;
use function iterator_to_array;

/**
 * Functional tests for paginator with collection order
 */
class DDC3330Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                DDC3330Building::class,
                DDC3330Hall::class,
            ]
        );
    }

    public function testIssueCollectionOrderWithPaginator() : void
    {
        $this->createBuildingAndHalls();
        $this->createBuildingAndHalls();
        $this->createBuildingAndHalls();

        $this->em->clear();

        $query = $this->em->createQuery(
            'SELECT b, h' .
            ' FROM Doctrine\Tests\ORM\Functional\Ticket\DDC3330Building b' .
            ' LEFT JOIN b.halls h' .
            ' ORDER BY b.id ASC, h.name DESC'
        )
        ->setMaxResults(3);

        $paginator = new Paginator($query, true);

        self::assertCount(3, iterator_to_array($paginator), 'Count is not correct for pagination');
    }

    /**
     * Create a building and 10 halls
     */
    private function createBuildingAndHalls()
    {
        $building = new DDC3330Building();

        for ($i = 0; $i < 10; $i++) {
            $hall       = new DDC3330Hall();
            $hall->name = 'HALL-' . $i;
            $building->addHall($hall);
        }

        $this->em->persist($building);
        $this->em->flush();
    }
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3330_building")
 */
class DDC3330Building
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\OneToMany(targetEntity=DDC3330Hall::class, mappedBy="building", cascade={"persist"}) */
    public $halls;

    public function addHall(DDC3330Hall $hall)
    {
        $this->halls[]  = $hall;
        $hall->building = $this;
    }
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3330_hall")
 */
class DDC3330Hall
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @ORM\ManyToOne(targetEntity=DDC3330Building::class, inversedBy="halls") */
    public $building;

    /** @ORM\Column(type="string", length=100) */
    public $name;
}
