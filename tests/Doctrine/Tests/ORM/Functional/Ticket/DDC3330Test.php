<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for paginator with collection order
 *
 * @author Lallement Thomas <thomas.lallement@9online.fr>
 */
class DDC3330Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
            DDC3330_Building::class,
            DDC3330_Hall::class,
            ]
        );
    }

    public function testIssueCollectionOrderWithPaginator()
    {
        $this->createBuildingAndHalls();
        $this->createBuildingAndHalls();
        $this->createBuildingAndHalls();

        $this->em->clear();

        $query = $this->em->createQuery(
            'SELECT b, h'.
            ' FROM Doctrine\Tests\ORM\Functional\Ticket\DDC3330_Building b'.
            ' LEFT JOIN b.halls h'.
            ' ORDER BY b.id ASC, h.name DESC'
        )
        ->setMaxResults(3);

        $paginator = new Paginator($query, true);

        self::assertEquals(3, count(iterator_to_array($paginator)), 'Count is not correct for pagination');
    }

    /**
     * Create a building and 10 halls
     */
    private function createBuildingAndHalls()
    {
        $building = new DDC3330_Building();

        for ($i = 0; $i < 10; $i++) {
            $hall = new DDC3330_Hall();
            $hall->name = 'HALL-'.$i;
            $building->addHall($hall);
        }

        $this->em->persist($building);
        $this->em->flush();
    }
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3330_building")
 */
class DDC3330_Building
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC3330_Hall", mappedBy="building", cascade={"persist"})
     */
    public $halls;

    public function addHall(DDC3330_Hall $hall)
    {
        $this->halls[] = $hall;
        $hall->building = $this;
    }
}

/**
 * @ORM\Entity @ORM\Table(name="ddc3330_hall")
 */
class DDC3330_Hall
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="DDC3330_Building", inversedBy="halls")
     */
    public $building;

    /**
     * @ORM\Column(type="string", length=100)
     */
    public $name;
}
