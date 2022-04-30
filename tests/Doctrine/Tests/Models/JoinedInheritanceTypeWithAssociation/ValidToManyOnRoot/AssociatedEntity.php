<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ValidToManyOnRoot;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\RootEntity;

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
     * @var RootEntity
     * @ManyToOne(targetEntity=RootEntity::class, inversedBy="toManyAssociation")
     */
    private $root;
}
