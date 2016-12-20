<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

        $this->_em->clear();

        $query = $this->_em->createQuery(
            'SELECT b, h'.
            ' FROM Doctrine\Tests\ORM\Functional\Ticket\DDC3330_Building b'.
            ' LEFT JOIN b.halls h'.
            ' ORDER BY b.id ASC, h.name DESC'
        )
        ->setMaxResults(3);

        $paginator = new Paginator($query, true);

        $this->assertEquals(3, count(iterator_to_array($paginator)), 'Count is not correct for pagination');
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

        $this->_em->persist($building);
        $this->_em->flush();
    }
}

/**
 * @Entity @Table(name="ddc3330_building")
 */
class DDC3330_Building
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC3330_Hall", mappedBy="building", cascade={"persist"})
     */
    public $halls;

    public function addHall(DDC3330_Hall $hall)
    {
        $this->halls[] = $hall;
        $hall->building = $this;
    }
}

/**
 * @Entity @Table(name="ddc3330_hall")
 */
class DDC3330_Hall
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC3330_Building", inversedBy="halls")
     */
    public $building;

    /**
     * @Column(type="string", length=100)
     */
    public $name;
}
