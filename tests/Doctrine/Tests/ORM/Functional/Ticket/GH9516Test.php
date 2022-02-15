<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH9516Test extends OrmFunctionalTestCase
{
    public function testEntityCanHaveInverseOneToManyAssociationWithChildMappedSuperclass(): void
    {
        $sportsCarMetadata = $this->_em->getClassMetadata(GH9516SportsCar::class);
        $this->assertTrue($sportsCarMetadata->hasAssociation('passengers'));
    }
}

/**
 * @Entity
 */
class GH9516Passenger
{
    /**
     * @var int $id
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @var GH9516Vehicle $vehicle
     * @ManyToOne(targetEntity="GH9516Vehicle", inversedBy="passengers")
     */
    private $vehicle;
}

/**
 * @Entity
 */
abstract class GH9516Vehicle
{
    /**
     * @var int $id
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @var GH9516Passenger[] $passengers
     * @OneToMany(targetEntity="GH9516Passenger", mappedBy="vehicle")
     */
    private $passengers;
}

/**
 * @MappedSuperclass
 */
abstract class GH9516Car extends GH9516Vehicle
{
}

/**
 * @Entity
 */
class GH9516SportsCar extends GH9516Car
{
}
