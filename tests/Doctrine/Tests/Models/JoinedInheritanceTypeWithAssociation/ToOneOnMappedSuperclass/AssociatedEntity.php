<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ToOneOnMappedSuperclass;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * @Entity
 */
class AssociatedEntity
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
     * @OneToOne(targetEntity=ChildMappedSuperclass::class, inversedBy="toOneAssociation")
     */
    private $childMappedSuperclass;
}
