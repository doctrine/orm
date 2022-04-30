<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\InvalidToManyOnMappedSuperclass;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\RootEntity;

/**
 * @MappedSuperclass
 */
abstract class ChildMappedSuperclass extends RootEntity
{
    /**
     * @var InvalidAssociatedEntity
     * @OneToMany(targetEntity=InvalidAssociatedEntity::class, mappedBy="childMappedSuperclass")
     */
    private $invalidToManyAssociation;
}
