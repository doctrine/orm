<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
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

/** @Entity */
class GH9516Passenger
{
    /**
     * @Id
     * @Column(type="integer")
     * @var int $id
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="GH9516Vehicle", inversedBy="passengers")
     * @var GH9516Vehicle $vehicle
     */
    private $vehicle;
}

/**
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({ "sports" = "\Doctrine\Tests\ORM\Functional\Ticket\GH9516SportsCar" })
 * @ORM\InheritanceType("SINGLE_TABLE")
 *
 * @Entity
 */
abstract class GH9516Vehicle
{
    /**
     * @Id
     * @Column(type="integer")
     * @var int $id
     */
    private $id;

    /**
     * @OneToMany(targetEntity="GH9516Passenger", mappedBy="vehicle")
     * @var GH9516Passenger[] $passengers
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
