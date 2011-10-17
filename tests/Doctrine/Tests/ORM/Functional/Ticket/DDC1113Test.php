<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1113
 * @group DDC-1306
 */
class DDC1113Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1113Engine'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1113Vehicle'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1113Car'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1113Bus'),
            ));
        } catch (\Exception $e) {

        }
    }

    public function testIssue()
    {
        $car = new DDC1113Car();
        $car->engine = new DDC1113Engine();

        $bus = new DDC1113Bus();
        $bus->engine = new DDC1113Engine();

        $this->_em->persist($car);
        $this->_em->flush();

        $this->_em->persist($bus);
        $this->_em->flush();

        $this->_em->remove($bus);
        $this->_em->remove($car);
        $this->_em->flush();
    }

}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({"car" = "DDC1113Car", "bus" = "DDC1113Bus"})
 */
class DDC1113Vehicle
{

    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC1113Vehicle")
     */
    public $parent;

    /** @OneToOne(targetEntity="DDC1113Engine", cascade={"persist", "remove"}) */
    public $engine;

}

/**
 * @Entity
 */
class DDC1113Car extends DDC1113Vehicle
{

}

/**
 * @Entity
 */
class DDC1113Bus extends DDC1113Vehicle
{

}

/**
 * @Entity
 */
class DDC1113Engine
{

    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

}

