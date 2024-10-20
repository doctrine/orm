<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'vct_owning_manytomany_compositeid')]
#[Entity]
class OwningManyToManyCompositeIdEntity
{
    /** @var string */
    #[Column(type: 'rot13', length: 255)]
    #[Id]
    public $id3;

    /** @var Collection<int, InversedManyToManyCompositeIdEntity> */
    #[JoinTable(name: 'vct_xref_manytomany_compositeid')]
    #[JoinColumn(name: 'owning_id', referencedColumnName: 'id3')]
    #[InverseJoinColumn(name: 'inversed_id1', referencedColumnName: 'id1')]
    #[InverseJoinColumn(name: 'inversed_id2', referencedColumnName: 'id2')]
    #[ManyToMany(targetEntity: 'InversedManyToManyCompositeIdEntity', inversedBy: 'associatedEntities')]
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
