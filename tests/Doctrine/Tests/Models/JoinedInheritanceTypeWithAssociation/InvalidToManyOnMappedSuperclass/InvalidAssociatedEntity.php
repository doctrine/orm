<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\InvalidToManyOnMappedSuperclass;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * @Entity
 */
class InvalidAssociatedEntity
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    public $id;

    /**
     * @var ChildMappedSuperclass
     * @ManyToOne(targetEntity=ChildMappedSuperclass::class, inversedBy="invalidToManyAssociation")
     */
    private $childMappedSuperclass;
}
