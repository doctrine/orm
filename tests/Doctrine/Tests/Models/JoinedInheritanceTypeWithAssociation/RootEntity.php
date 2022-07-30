<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ValidToManyOnRoot\AssociatedEntity;

/**
 * @DiscriminatorMap({
 *     "validToManyOnRoot" = ValidToManyOnRoot\GrandchildEntity::class,
 *     "invalidToManyOnMappedSuperclass" = InvalidToManyOnMappedSuperclass\GreatGrandchildEntity::class,
 *     "toOneOnMappedSuperclass" = ToOneOnMappedSuperclass\GreatGrandchildEntity::class
 * })
 * @Entity
 * @InheritanceType("JOINED")
 */
class RootEntity
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;

    /**
     * @var AssociatedEntity
     * @OneToMany(targetEntity=AssociatedEntity::class, mappedBy="root")
     */
    public $toManyAssociation;
}
