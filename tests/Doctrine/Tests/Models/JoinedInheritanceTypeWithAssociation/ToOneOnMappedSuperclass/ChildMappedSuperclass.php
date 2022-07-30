<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ToOneOnMappedSuperclass;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\RootEntity;

/**
 * @MappedSuperclass
 */
abstract class ChildMappedSuperclass extends RootEntity
{
    /**
     * @var AssociatedEntity
     * @OneToOne(targetEntity=AssociatedEntity::class, mappedBy="childMappedSuperclass")
     */
    private $toOneAssociation;
}
